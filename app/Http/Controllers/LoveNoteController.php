<?php

namespace App\Http\Controllers;

use App\Actions\SendLoveNoteAction;
use App\Http\Requests\LoveNote\SendLoveNoteRequest;
use App\Models\LoveNote;
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
            ->where('sender_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $received = LoveNote::with('sender')
            ->where('recipient_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $received->each->markAsRead();

        return Inertia::render('love-notes/Index', [
            'sent' => $sent->map(fn ($n) => $this->formatNote($n, 'sent')),
            'received' => $received->map(fn ($n) => $this->formatNote($n, 'received')),
            'partner' => $user->partner ? [
                'id' => $user->partner->id,
                'name' => $user->partner->display_name ?? $user->partner->name,
                'avatar' => $user->partner->avatar,
            ] : null,
            'default_messages' => $this->defaultMessages(),
        ]);
    }

    public function store(SendLoveNoteRequest $request): RedirectResponse
    {
        $user = $request->user();
        $partner = $user->partner;

        if (! $partner) {
            return back()->withErrors(['partner' => 'You need a partner to send love notes.']);
        }

        $this->sendLoveNoteAction->execute($user, $partner, $request->validated('message'));

        return back()->with('success', 'Love note sent! 💌');
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = LoveNote::where('recipient_id', $request->user()->id)
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
            'person_name' => $person?->display_name ?? $person?->name,
            'person_avatar' => $person?->avatar,
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
