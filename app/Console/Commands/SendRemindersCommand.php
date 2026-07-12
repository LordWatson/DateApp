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
            ->where(function ($q): void {
                $q->whereNull('last_completed_questionnaire_at')
                    ->orWhere('last_completed_questionnaire_at', '<', now()->subDays(7));
            })
            ->get();

        foreach ($users as $user) {
            AppNotification::create([
                'user_id' => $user->id,
                'type' => NotificationType::PartnerCompleted,
                'title' => '💕 Time for a Date Night!',
                'body' => 'You haven\'t completed a questionnaire recently. Start one with your partner!',
                'data' => [],
            ]);
        }

        $this->info("Sent reminders to {$users->count()} users.");

        return self::SUCCESS;
    }
}
