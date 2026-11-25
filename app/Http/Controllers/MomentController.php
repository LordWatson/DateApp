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
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            // The user's own "today", used as the default date of a new moment.
            'today' => $user->today()->toDateString(),
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

    /**
     * Photos sit on a private disk; this is the only way to read one.
     */
    public function photo(Moment $moment): StreamedResponse
    {
        $this->authorize('view', $moment);

        $disk = Storage::disk(config('filesystems.uploads'));

        abort_unless($moment->photo && $disk->exists($moment->photo), 404);

        return $disk->response($moment->photo, null, ['Cache-Control' => 'private, max-age=3600']);
    }

    public function store(StoreMomentRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['photo', 'remove_photo']);

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('moments', config('filesystems.uploads'));
        }

        $moment = $request->user()->moments()->create($data);

        MomentCreated::dispatch($request->user(), $moment);

        return redirect()->route('moments.index')->with('success', 'Moment captured!');
    }

    public function update(StoreMomentRequest $request, Moment $moment): RedirectResponse
    {
        $this->authorize('update', $moment);

        // `photo` is only written when a file arrives or removal is asked for:
        // the edit form sends null when the picture is left alone.
        $data = $request->safe()->except(['photo', 'remove_photo']);
        // A multipart form cannot send an empty list, so no tags means "none".
        $data['tags'] ??= [];

        if ($request->hasFile('photo') || $request->boolean('remove_photo')) {
            if ($moment->photo) {
                Storage::disk(config('filesystems.uploads'))->delete($moment->photo);
            }
            $data['photo'] = $request->file('photo')?->store('moments', config('filesystems.uploads'));
        }

        $moment->update($data);

        return redirect()->route('moments.index')->with('success', 'Moment updated!');
    }

    public function destroy(Moment $moment): RedirectResponse
    {
        $this->authorize('delete', $moment);

        if ($moment->photo) {
            Storage::disk(config('filesystems.uploads'))->delete($moment->photo);
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

    /** @return array<string, mixed> */
    private function formatMoment(Moment $moment, int $authUserId): array
    {
        $isOwner = $moment->user_id === $authUserId;

        return [
            'id' => $moment->id,
            'title' => $moment->title,
            'description' => $moment->description,
            'mood' => $moment->mood,
            // The file name changes with the photo, so a replaced photo is not served from cache.
            'photo' => $moment->photo ? route('moments.photo', [$moment, 'v' => basename($moment->photo)]) : null,
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
