<?php

namespace App\Http\Controllers;

use App\Actions\SendLoveNoteAction;
use App\Enums\NotificationType;
use App\Http\Requests\LoveNote\ReactToLoveNoteRequest;
use App\Http\Requests\LoveNote\SendLoveNoteRequest;
use App\Models\AppNotification;
use App\Models\LoveNote;
use App\Services\ImpersonationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class LoveNoteController extends Controller
{
    public function __construct(
        private readonly SendLoveNoteAction $sendLoveNoteAction,
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();

        $sent = LoveNote::with('recipient')
            ->visibleTo($user)
            ->where('sender_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $received = LoveNote::with('sender')
            ->visibleTo($user)
            ->where('recipient_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return Inertia::render('love-notes/Index', [
            'sent' => $sent->map(fn ($n) => $this->formatNote($n, 'sent')),
            'received' => $received->map(fn ($n) => $this->formatNote($n, 'received')),
            'partner' => ($partner = $user->activePartner()) ? [
                'id' => $partner->id,
                'name' => $partner->display_name ?? $partner->name,
                'avatar' => $partner->avatar,
            ] : null,
            'connection_paused' => $user->isConnectionPaused(),
            'default_messages' => $this->defaultMessages(),
            'reactions' => LoveNote::REACTIONS,
        ]);
    }

    public function store(SendLoveNoteRequest $request): RedirectResponse
    {
        $user = $request->user();
        $partner = $user->activePartner();

        if (! $partner) {
            return back()->withErrors(['partner' => $user->isConnectionPaused()
                ? 'Your connection is paused, so love notes cannot be sent.'
                : 'You need a partner to send love notes.']);
        }

        $this->sendLoveNoteAction->execute($user, $partner, $request->validated('message'));

        return back()->with('success', 'Love note sent! 💌');
    }

    public function show(Request $request, LoveNote $loveNote): InertiaResponse
    {
        $user = $request->user();

        if ($loveNote->recipient_id !== $user->id && $loveNote->sender_id !== $user->id) {
            abort(403);
        }

        // Taken out of this person's account after the connection ended.
        abort_unless(LoveNote::visibleTo($user)->whereKey($loveNote->id)->exists(), 404);

        // Not when an administrator is viewing as the recipient: they have not read it.
        if ($loveNote->recipient_id === $user->id && ! app(ImpersonationService::class)->isActive()) {
            $loveNote->markAsRead();
        }

        return Inertia::render('love-notes/Show', [
            'note' => $this->formatNote($loveNote, $loveNote->sender_id === $user->id ? 'sent' : 'received'),
            'reactions' => LoveNote::REACTIONS,
        ]);
    }

    public function react(ReactToLoveNoteRequest $request, LoveNote $loveNote): RedirectResponse
    {
        $reaction = $request->validated('reaction');
        $isFirstReaction = $loveNote->reaction === null && $reaction !== null;

        $loveNote->update([
            'reaction' => $reaction,
            'reacted_at' => $reaction === null ? null : now(),
        ]);

        // Changing or clearing a reaction is not news; only the first one is announced.
        if ($isFirstReaction) {
            $user = $request->user();
            $name = $user->display_name ?? $user->name;

            AppNotification::create([
                'user_id' => $loveNote->sender_id,
                'type' => NotificationType::LoveNoteReaction,
                'title' => "{$name} reacted {$reaction} to your love note",
                'body' => $loveNote->message,
                'data' => ['love_note_id' => $loveNote->id],
            ]);
        }

        return back();
    }

    /**
     * Called when the Received tab is opened.
     */
    public function markRead(Request $request): JsonResponse
    {
        LoveNote::where('recipient_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['read' => true]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = LoveNote::visibleTo($request->user())
            ->where('recipient_id', $request->user()->id)
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatNote(LoveNote $note, string $direction): array
    {
        $person = $direction === 'sent' ? $note->recipient : $note->sender;

        return [
            'id' => $note->id,
            'message' => $note->message,
            'person_name' => $person->display_name ?? $person?->name,
            'person_avatar' => $person?->avatar,
            'direction' => $direction,
            'reaction' => $note->reaction,
            'read_at' => $note->read_at?->toISOString(),
            'created_at' => $note->created_at?->toISOString(),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function defaultMessages(): array
    {
        return [
            '❤️ Thinking of you',
            '🥰 Can\'t wait for tonight',
            '🌹 You looked amazing today',
            '☕ Fancy a date night?',
            '✨ Missing you',
            '😘 Hope you\'re smiling',
            '🔥 Looking forward to later',
            '😊 You make my day better',
        ];
    }
}
