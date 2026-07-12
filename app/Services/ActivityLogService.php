<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

final class ActivityLogService
{
    public function log(User $user, string $action, ?Model $subject = null, array $metadata = []): ActivityLog
    {
        Log::info('activity', [
            'user' => $user->id,
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
        ]);

        return ActivityLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'metadata' => $metadata ?: null,
        ]);
    }
}
