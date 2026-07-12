<?php

namespace App\Http\Controllers\Admin;

use App\Models\AdminAuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminAuditLogController extends AdminController
{
    public function index(Request $request): Response
    {
        $query = AdminAuditLog::with('user');

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(fn ($q) => $q
                ->where('action', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"))
            );
        }

        if ($request->filled('action')) {
            $query->where('action', 'like', '%'.$request->string('action').'%');
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        $logs = $query->latest()->paginate(50)->withQueryString();

        return Inertia::render('admin/audit-logs/Index', [
            'logs' => $logs,
            'filters' => $request->only(['search', 'action', 'user_id']),
        ]);
    }
}
