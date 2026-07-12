<?php

namespace App\Http\Controllers;

use App\Http\Requests\SavedProfile\StoreSavedProfileRequest;
use App\Models\Questionnaire;
use App\Models\SavedProfile;
use App\Models\SavedProfileAnswer;
use App\Services\QuestionnaireService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SavedProfileController extends Controller
{
    public function __construct(
        private readonly QuestionnaireService $questionnaireService,
    ) {}

    public function index(Request $request): Response
    {
        $profiles = $request->user()->savedProfiles()->with('answers')->get();

        return Inertia::render('saved-profiles/Index', [
            'profiles' => $profiles->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'emoji' => $p->emoji,
                'colour' => $p->colour,
                'answer_count' => $p->answers->count(),
            ]),
        ]);
    }

    public function store(StoreSavedProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $questionnaire = Questionnaire::findOrFail($request->integer('questionnaire_id'));
        $response = $this->questionnaireService->getResponseWithAnswers($user, $questionnaire);

        if (! $response) {
            return response()->json(['error' => 'No response found.'], 422);
        }

        $profile = SavedProfile::create([
            'user_id' => $user->id,
            'name' => $request->string('name'),
            'emoji' => $request->string('emoji') ?: null,
            'colour' => $request->string('colour') ?: null,
        ]);

        $answers = $this->questionnaireService->getAnswersForResponse($response);

        foreach ($answers as $answer) {
            SavedProfileAnswer::create([
                'saved_profile_id' => $profile->id,
                'question_id' => $answer->question_id,
                'question_option_id' => $answer->question_option_id,
                'value' => $answer->value,
            ]);
        }

        return response()->json([
            'profile' => [
                'id' => $profile->id,
                'name' => $profile->name,
                'emoji' => $profile->emoji,
                'colour' => $profile->colour,
            ],
        ]);
    }

    public function update(Request $request, SavedProfile $savedProfile): JsonResponse
    {
        Gate::authorize('update', $savedProfile);

        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'emoji' => ['nullable', 'string', 'max:10'],
            'colour' => ['nullable', 'string', 'max:20'],
        ]);

        $savedProfile->update($request->only('name', 'emoji', 'colour'));

        return response()->json(['updated' => true]);
    }

    public function destroy(SavedProfile $savedProfile): JsonResponse
    {
        Gate::authorize('delete', $savedProfile);
        $savedProfile->delete();

        return response()->json(['deleted' => true]);
    }

    public function apply(Request $request, SavedProfile $savedProfile, string $questionnaireSlug): JsonResponse
    {
        Gate::authorize('view', $savedProfile);

        $questionnaire = Questionnaire::where('slug', $questionnaireSlug)->firstOrFail();
        $user = $request->user();
        $response = $this->questionnaireService->findOrCreateResponse($user, $questionnaire);

        $this->questionnaireService->applyProfile($response, $savedProfile);

        return response()->json(['applied' => true]);
    }
}
