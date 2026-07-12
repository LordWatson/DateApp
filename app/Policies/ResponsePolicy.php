<?php

namespace App\Policies;

use App\Models\Response;
use App\Models\User;

class ResponsePolicy
{
    public function view(User $user, Response $response): bool
    {
        return $user->id === $response->user_id;
    }

    public function update(User $user, Response $response): bool
    {
        return $user->id === $response->user_id;
    }

    public function delete(User $user, Response $response): bool
    {
        return $user->id === $response->user_id;
    }
}
