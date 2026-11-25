<?php

namespace App\Console\Commands;

use App\Enums\NotificationType;
use App\Models\AppNotification;
use App\Models\PartnerInvitation;
use Illuminate\Console\Command;

final class ExpireInvitationsCommand extends Command
{
    protected $signature = 'datenight:expire-invitations';

    protected $description = 'Tell senders, once, that their partner invitation ran out';

    public function handle(): int
    {
        // shortcut: there is no status column, so "ran out by itself and not yet announced" is read as
        // "not touched since before it expired". Revoking, declining and replacing all set expires_at
        // and updated_at together, and announcing touches the row. Add a column if more states appear.
        $invitations = PartnerInvitation::with('sender')
            ->whereNull('accepted_at')
            ->where('expires_at', '<', now())
            // Not the backlog from before this command announced anything.
            ->where('expires_at', '>', now()->subWeek())
            ->whereColumn('updated_at', '<', 'expires_at')
            ->get();

        $notified = 0;

        foreach ($invitations as $invitation) {
            $sender = $invitation->sender;

            // Nothing to say to someone who has connected or already invited again.
            if ($sender->partner_id === null && $sender->pendingInvitation() === null) {
                AppNotification::create([
                    'user_id' => $sender->id,
                    'type' => NotificationType::InvitationExpired,
                    'title' => 'Your partner invitation expired',
                    'body' => "The invitation you sent to {$invitation->email} was not accepted in time. You can send a new one from the Partner page.",
                    'data' => [],
                ]);

                $notified++;
            }

            $invitation->touch();
        }

        $this->info("Told {$notified} senders that their invitation expired.");

        return self::SUCCESS;
    }
}
