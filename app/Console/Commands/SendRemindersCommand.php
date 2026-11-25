<?php

namespace App\Console\Commands;

use App\Enums\NotificationType;
use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Console\Command;

final class SendRemindersCommand extends Command
{
    protected $signature = 'datenight:send-reminders';

    protected $description = 'Send questionnaire reminders to users who have not completed recently';

    public function handle(): int
    {
        $users = User::where('onboarding_completed', true)
            ->whereNotNull('partner_id')
            // The reminder is to plan something together.
            ->whereNull('connection_paused_at')
            ->where(function ($q): void {
                $q->whereNull('last_completed_questionnaire_at')
                    ->orWhere('last_completed_questionnaire_at', '<', now()->subDays(7));
            })
            // One nudge a week at most: this runs daily and the condition stays true until they act.
            ->whereNotIn('id', AppNotification::query()
                ->where('type', NotificationType::QuestionnaireReminder)
                ->where('created_at', '>', now()->subDays(7))
                ->select('user_id'))
            ->get()
            ->filter(fn (User $user): bool => $this->isDue($user));

        foreach ($users as $user) {
            AppNotification::create([
                'user_id' => $user->id,
                'type' => NotificationType::QuestionnaireReminder,
                'title' => 'Time for a date night',
                'body' => 'You haven\'t completed a questionnaire recently. Start one with your partner!',
                'data' => [],
            ]);
        }

        $this->info("Sent reminders to {$users->count()} users.");

        return self::SUCCESS;
    }

    /**
     * The query above is the weekly rule; a user can ask for monthly reminders or none.
     */
    private function isDue(User $user): bool
    {
        return match ($user->reminderFrequency()) {
            'off' => false,
            'monthly' => ($user->last_completed_questionnaire_at === null || $user->last_completed_questionnaire_at->lt(now()->subDays(30)))
                && ! AppNotification::query()
                    ->where('user_id', $user->id)
                    ->where('type', NotificationType::QuestionnaireReminder)
                    ->where('created_at', '>', now()->subDays(30))
                    ->exists(),
            default => true,
        };
    }
}
