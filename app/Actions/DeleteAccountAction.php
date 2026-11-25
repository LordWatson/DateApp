<?php

namespace App\Actions;

use App\Enums\NotificationType;
use App\Jobs\ExportUserDataJob;
use App\Models\AppNotification;
use App\Models\DateNightPlan;
use App\Models\Moment;
use App\Models\Response;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Deletes an account without taking the partner's history with it.
 *
 * Database cascades remove everything the user owns, including their
 * responses, and every plan that references one of those responses. Shared
 * plans are therefore handed over to the partner first.
 */
class DeleteAccountAction
{
    public function execute(User $user): void
    {
        $photos = Moment::where('user_id', $user->id)->whereNotNull('photo')->pluck('photo')->all();
        $export = ExportUserDataJob::pathFor($user);

        DB::transaction(function () use ($user): void {
            $partner = $user->partner;

            if ($partner) {
                $this->handSharedPlansTo($partner, $user);

                $partner->update([
                    'partner_id' => null,
                    'connected_at' => null,
                    'connection_paused_at' => null,
                    'connection_paused_by' => null,
                    'challenge_streak' => 0,
                    'challenge_streak_date' => null,
                ]);

                $name = $user->display_name ?? $user->name;

                AppNotification::create([
                    'user_id' => $partner->id,
                    'type' => NotificationType::PartnerLeft,
                    'title' => "{$name} has closed their account",
                    'body' => 'You are no longer connected. The date nights you planned together are still in your history. Love notes between you have been removed.',
                    'data' => [],
                ]);
            }

            $user->delete();
        });

        // Files are not covered by the transaction, so remove them only once the rows are gone.
        Storage::disk(config('filesystems.uploads'))->delete($photos);
        Storage::disk(ExportUserDataJob::DISK)->delete($export);
    }

    /**
     * Re-point shared plans at the partner's own response so the cascade from
     * the leaving user's responses does not delete them.
     */
    private function handSharedPlansTo(User $partner, User $leaving): void
    {
        $leavingIds = Response::where('user_id', $leaving->id)->pluck('id');
        $partnerIds = Response::where('user_id', $partner->id)->pluck('id');

        if ($leavingIds->isEmpty() || $partnerIds->isEmpty()) {
            return;
        }

        DateNightPlan::whereIn('partner_one_response_id', $leavingIds)
            ->whereIn('partner_two_response_id', $partnerIds)
            ->update(['partner_one_response_id' => DB::raw('partner_two_response_id'), 'is_solo' => true]);

        DateNightPlan::whereIn('partner_two_response_id', $leavingIds)
            ->whereIn('partner_one_response_id', $partnerIds)
            ->update(['partner_two_response_id' => DB::raw('partner_one_response_id'), 'is_solo' => true]);
    }
}
