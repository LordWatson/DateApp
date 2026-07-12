<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Services\AdminAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminCoupleController extends AdminController
{
    public function __construct(private readonly AdminAuditService $audit) {}

    public function index(Request $request): Response
    {
        $query = User::with(['partner', 'role'])
            ->whereNotNull('partner_id')
            ->where('id', '<', DB::raw('partner_id'))
            ->withCount(['responses as questionnaires_completed' => fn ($q) => $q->whereNotNull('completed_at')])
            ->withCount('moments');

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
            );
        }

        $couples = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('admin/couples/Index', [
            'couples' => $couples,
            'filters' => $request->only(['search']),
        ]);
    }

    public function disconnect(User $user): RedirectResponse
    {
        $partner = $user->partner;

        $user->update(['partner_id' => null]);
        $partner?->update(['partner_id' => null]);

        $this->audit->logModel('couple.disconnected', $user);

        return back()->with('success', 'Couple disconnected.');
    }

    public function reconnect(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'partner_id' => ['required', 'exists:users,id', 'different:user_id'],
        ]);

        $user = User::findOrFail($validated['user_id']);
        $partner = User::findOrFail($validated['partner_id']);

        $user->update(['partner_id' => $partner->id]);
        $partner->update(['partner_id' => $user->id]);

        $this->audit->log('couple.reconnected', User::class, $user->id, null, [
            'user_id' => $user->id,
            'partner_id' => $partner->id,
        ]);

        return back()->with('success', 'Users reconnected.');
    }
}
