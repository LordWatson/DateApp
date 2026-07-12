<?php

namespace App\Services;

use App\Models\AdminAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuditService
{
    public function __construct(private readonly Request $request) {}

    public function log(
        string $action,
        ?string $auditableType = null,
        ?int $auditableId = null,
        ?array $before = null,
        ?array $after = null,
    ): AdminAuditLog {
        return AdminAuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'before' => $before,
            'after' => $after,
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
        ]);
    }

    public function logModel(string $action, object $model, ?array $before = null, ?array $after = null): AdminAuditLog
    {
        return $this->log(
            action: $action,
            auditableType: get_class($model),
            auditableId: $model->getKey(),
            before: $before,
            after: $after,
        );
    }
}
