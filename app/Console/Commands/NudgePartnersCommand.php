<?php

namespace App\Console\Commands;

use App\Actions\NudgePartnerAction;
use App\Enums\CompletionStatus;
use App\Models\Response;
use Illuminate\Console\Command;

final class NudgePartnersCommand extends Command
{
    protected $signature = 'datenight:nudge-partners';

    protected $description = 'Remind partners once about a couple questionnaire that has waited a day for their answers';

    public function handle(NudgePartnerAction $nudge): int
    {
        // Only attempts never nudged: someone who pressed the button has already reminded their partner.
        $sent = Response::with(['user.partner', 'questionnaire'])
            ->where('status', CompletionStatus::Completed)
            ->whereNull('partner_nudged_at')
            ->whereBetween('completed_at', [now()->subHours(72), now()->subHours(24)])
            ->get()
            ->filter(fn (Response $response): bool => $nudge->execute($response))
            ->count();

        $this->info("Sent {$sent} partner reminders.");

        return self::SUCCESS;
    }
}
