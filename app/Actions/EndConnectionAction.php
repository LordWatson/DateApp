<?php

namespace App\Actions;

use App\Events\PartnerDisconnected;
use App\Models\User;
use App\Services\PartnerInvitationService;
use Illuminate\Support\Facades\DB;

class EndConnectionAction
{
    public function __construct(
        private readonly PartnerInvitationService $partners,
        private readonly ApplyConnectionChoicesAction $applyChoices,
    ) {}

    /**
     * End the connection for both people. The choices apply to the person
     * ending it only; the other person makes their own afterwards.
     */
    public function execute(User $user, bool $keepPlans, bool $keepNotes): void
    {
        $partner = $user->partner;

        if (! $partner) {
            return;
        }

        DB::transaction(function () use ($user, $partner, $keepPlans, $keepNotes): void {
            $this->applyChoices->execute($user, $partner, $keepPlans, $keepNotes);
            $this->partners->disconnectPartner($user);
        });

        PartnerDisconnected::dispatch($user->fresh(), $partner->fresh());
    }
}
