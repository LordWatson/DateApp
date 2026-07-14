<?php

namespace App\Http\Controllers;

use App\Events\MomentCreated;
use App\Http\Requests\Moment\StoreMomentRequest;
use App\Models\Moment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class MomentController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $partnerIds = array_filter([$user->partner_id]);
        $ownerIds = array_merge([$user->id], $partnerIds);

        $moments = Moment::query()
            ->whereIn('user_id', $ownerIds)
            ->with('dateNightPlan')
            ->orderByDesc('date')
            ->paginate(12);

        return Inertia::render('moments/Index', [
            'moments' => $moments->through(fn ($m) => $this->formatMoment($m, $user->id))->items(),
            'pagination' => [
                'current_page' => $moments->currentPage(),
                'last_page' => $moments->lastPage(),
                'total' => $moments->total(),
            ],
        ]);
    }

    public function show(Request $request, Moment $moment): Response
    {
        $this->authorize('view', $moment);

        $moment->load('dateNightPlan');

        return Inertia::render('moments/Show', [
            'moment' => $this->formatMoment($moment, $request->user()->id),
        ]);
    }

    public function store(StoreMomentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('moments', 'public');
        }

        $moment = $request->user()->moments()->create($data);

        MomentCreated::dispatch($request->user(), $moment);

        return redirect()->route('moments.index')->with('success', 'Moment captured!');
    }

    public function update(StoreMomentRequest $request, Moment $moment): RedirectResponse
    {
        $this->authorize('update', $moment);

        $data = $request->validated();

        if ($request->hasFile('photo')) {
            if ($moment->photo) {
                Storage::disk('public')->delete($moment->photo);
            }
            $data['photo'] = $request->file('photo')->store('moments', 'public');
        }

        $moment->update($data);

        return redirect()->route('moments.index')->with('success', 'Moment updated!');
    }

    public function destroy(Moment $moment): RedirectResponse
    {
        $this->authorize('delete', $moment);

        if ($moment->photo) {
            Storage::disk('public')->delete($moment->photo);
        }

        $moment->delete();

        return redirect()->route('moments.index')->with('success', 'Moment deleted!');
    }

    public function toggleFavourite(Moment $moment): RedirectResponse
    {
        $this->authorize('update', $moment);
        $moment->update(['is_favourite' => ! $moment->is_favourite]);

        return back();
    }

    private function formatMoment(Moment $moment, int $authUserId): array
    {
        $isOwner = $moment->user_id === $authUserId;

        return [
            'id' => $moment->id,
            'title' => $moment->title,
            'description' => $moment->description,
            'mood' => $moment->mood,
            'photo' => $moment->photo ? Storage::url($moment->photo) : null,
            'date' => $moment->date->toDateString(),
            'is_favourite' => $moment->is_favourite,
            'is_owner' => $isOwner,
            'private_notes' => $isOwner ? $moment->private_notes : null,
            'tags' => $moment->tags ?? [],
            'date_night_plan' => $moment->dateNightPlan ? [
                'id' => $moment->dateNightPlan->id,
                'theme' => $moment->dateNightPlan->theme,
            ] : null,
        ];
    }
}
