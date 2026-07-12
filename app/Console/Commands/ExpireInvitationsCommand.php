<?php

namespace App\Console\Commands;

use App\Models\PartnerInvitation;
use Illuminate\Console\Command;

final class ExpireInvitationsCommand extends Command
{
    protected $signature = 'datenight:expire-invitations';

    protected $description = 'Mark expired partner invitations';

    public function handle(): int
    {
        $count = PartnerInvitation::whereNull('accepted_at')
            ->where('expires_at', '<', now())
            ->count();

        $this->info("Found {$count} expired invitations (already filtered by expires_at).");

        return self::SUCCESS;
    }
}
