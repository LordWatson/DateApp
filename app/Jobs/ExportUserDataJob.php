<?php

namespace App\Jobs;

use App\Enums\NotificationType;
use App\Models\Achievement;
use App\Models\AppNotification;
use App\Models\DateNightPlan;
use App\Models\LoveNote;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final class ExportUserDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** A private disk: files are only handed out by DataExportController@download. */
    public const DISK = 'local';

    /** How long a finished export can be downloaded before the clean-up command removes it. */
    public const RETENTION_HOURS = 24;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(
        public readonly User $user,
    ) {
        $this->onQueue('exports');
    }

    /**
     * One file per user; a new export replaces the last one.
     */
    public static function pathFor(User $user): string
    {
        return "exports/user-{$user->id}.json";
    }

    public static function isAvailable(string $path): bool
    {
        $disk = Storage::disk(self::DISK);

        return $disk->exists($path)
            && $disk->lastModified($path) >= now()->subHours(self::RETENTION_HOURS)->timestamp;
    }

    public function handle(): void
    {
        $path = self::pathFor($this->user);

        Storage::disk(self::DISK)->put(
            $path,
            json_encode($this->data(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        );

        AppNotification::create([
            'user_id' => $this->user->id,
            'type' => NotificationType::DataExportReady,
            'title' => 'Your data export is ready',
            'body' => 'Download it from your profile settings within '.self::RETENTION_HOURS.' hours. After that the file is removed.',
            'data' => [],
        ]);

        Log::info('ExportUserDataJob completed', [
            'user' => $this->user->id,
            'file' => $path,
        ]);
    }

    /**
     * Everything that belongs to the user. Shared records (plans, love notes) are
     * included; the partner's own moments, events and answers are not.
     *
     * @return array<string, mixed>
     */
    private function data(): array
    {
        $user = $this->user;
        $responseIds = $user->responses()->pluck('id');

        return [
            'exported_at' => now()->toIso8601String(),
            'profile' => $user->only([
                'name', 'display_name', 'email', 'gender', 'date_of_birth', 'avatar', 'timezone',
                'email_notifications', 'current_streak', 'longest_streak', 'created_at',
                'terms_accepted_at', 'ai_consent_at', 'ai_consent_declined_at',
            ]),
            'questionnaire_answers' => $user->responses()
                ->with(['questionnaire:id,title', 'answers.question:id,title', 'answers.questionOption:id,title'])
                ->get()
                ->toArray(),
            'date_night_plans' => DateNightPlan::query()
                ->whereIn('partner_one_response_id', $responseIds)
                ->orWhereIn('partner_two_response_id', $responseIds)
                ->orWhere('partner_user_id', $user->id)
                ->get()
                ->toArray(),
            'moments' => $user->moments()->get()->toArray(),
            'calendar_events' => $user->calendarEvents()->get()->toArray(),
            'love_notes_sent' => LoveNote::where('sender_id', $user->id)->get()->toArray(),
            'love_notes_received' => LoveNote::where('recipient_id', $user->id)->get()->toArray(),
            'achievements' => $user->achievements()->get()->map(fn (Achievement $achievement): array => [
                'name' => $achievement->name,
                'description' => $achievement->description,
                'unlocked_at' => $achievement->pivot->getAttribute('unlocked_at'),
            ])->all(),
            'notifications' => AppNotification::where('user_id', $user->id)->get()->toArray(),
        ];
    }

    public function failed(\Throwable $e): void
    {
        Log::error('ExportUserDataJob failed', [
            'user' => $this->user->id,
            'error' => $e->getMessage(),
        ]);
    }
}
