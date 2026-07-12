<?php

namespace App\Policies;

use App\Models\SavedProfile;
use App\Models\User;

class SavedProfilePolicy
{
    public function view(User $user, SavedProfile $savedProfile): bool
    {
        return $user->id === $savedProfile->user_id;
    }

    public function update(User $user, SavedProfile $savedProfile): bool
    {
        return $user->id === $savedProfile->user_id;
    }

    public function delete(User $user, SavedProfile $savedProfile): bool
    {
        return $user->id === $savedProfile->user_id;
    }
}
