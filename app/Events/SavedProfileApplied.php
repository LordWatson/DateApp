<?php

namespace App\Events;

use App\Models\Response;
use App\Models\SavedProfile;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class SavedProfileApplied
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly SavedProfile $savedProfile,
        public readonly Response $response,
    ) {}
}
