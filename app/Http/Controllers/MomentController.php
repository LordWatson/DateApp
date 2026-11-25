<?php

namespace App\Http\Controllers;

use App\Events\MomentCreated;
use App\Http\Requests\Moment\StoreMomentRequest;
use App\Models\DateNightPlan;
use App\Models\Moment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
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
            'prefill' => $this->prefillFromPlan($request),
        ]);
    }

    /**
     * `?plan=` opens the form ready to capture a moment from that date night.
     *
     * @return array{date_night_plan_id: int, title: string, date: string}|null
     */
    private function prefillFromPlan(Request $request): ?array
    {
        $user = $request->user();
        $plan = $request->filled('plan') ? DateNightPlan::find($request->integer('plan')) : null;

        if (! $plan || $user->cannot('view', $plan)) {
            return null;
        }

        $completedOn = DB::table('date_night_plan_completions')
            ->where('date_night_plan_id', $plan->id)
            ->where('user_id', $user->id)
            ->value('completed_on');

        return [
            'date_night_plan_id' => $plan->id,
            'title' => $plan->theme,
            'date' => $completedOn ? substr((string) $completedOn, 0, 10) : $user->today()->toDateString(),
        ];
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
    public function photo(Moment $moment, int $index = 0): StreamedResponse
    {
        $this->authorize('view', $moment);

        $disk = Storage::disk(config('filesystems.uploads'));
        $path = $moment->photos[$index] ?? null;

        abort_unless($path && $disk->exists($path), 404);

        return $disk->response($path, null, ['Cache-Control' => 'private, max-age=3600']);
    }

    /** @return list<string> */
    private function storePhotos(StoreMomentRequest $request): array
    {
        return array_values(array_map(
            // A failed write must not end up in the list as `false`.
            static fn (UploadedFile $file): string => $file->store('moments', config('filesystems.uploads'))
                ?: throw new RuntimeException('Could not store the photo.'),
            $request->file('photos', []),
        ));
    }

    public function store(StoreMomentRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['photos', 'remove_photos']);
        $data['photos'] = $this->storePhotos($request);

        $moment = $request->user()->moments()->create($data);

        MomentCreated::dispatch($request->user(), $moment);

        return redirect()->route('moments.index')->with('success', 'Moment captured!');
    }

    public function update(StoreMomentRequest $request, Moment $moment): RedirectResponse
    {
        $this->authorize('update', $moment);

        $data = $request->safe()->except(['photos', 'remove_photos']);
        // A multipart form cannot send an empty list, so no tags means "none".
        $data['tags'] ??= [];

        // Saved photos stay unless their position is in `remove_photos`; new files go after them.
        $saved = collect($moment->photos);
        $removed = $saved->only($request->input('remove_photos', []));
        $data['photos'] = $saved->except($removed->keys())->values()->merge($this->storePhotos($request))->all();

        $moment->update($data);

        Storage::disk(config('filesystems.uploads'))->delete($removed->values()->all());

        return redirect()->route('moments.index')->with('success', 'Moment updated!');
    }

    public function destroy(Moment $moment): RedirectResponse
    {
        $this->authorize('delete', $moment);

        Storage::disk(config('filesystems.uploads'))->delete($moment->photos ?? []);

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
            'photos' => $moment->photoUrls(),
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
