<?php

namespace Tests\Feature;

use App\Models\Questionnaire;
use App\Models\Response;
use App\Models\SavedProfile;
use App\Models\SavedProfileAnswer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SavedProfilePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_lists_only_the_users_own_profiles_with_answer_counts(): void
    {
        $user = User::factory()->create();
        $mine = SavedProfile::factory()->create(['user_id' => $user->id, 'name' => 'Movie Night']);
        SavedProfileAnswer::factory()->count(2)->create(['saved_profile_id' => $mine->id]);
        SavedProfile::factory()->create(['name' => 'Someone Else']);

        $this->actingAs($user)
            ->get('/saved-profiles')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('saved-profiles/Index')
                ->has('profiles', 1)
                ->where('profiles.0.id', $mine->id)
                ->where('profiles.0.name', 'Movie Night')
                ->where('profiles.0.answer_count', 2)
            );
    }

    public function test_owner_can_rename_and_restyle_a_profile(): void
    {
        $user = User::factory()->create();
        $profile = SavedProfile::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->putJson("/saved-profiles/{$profile->id}", ['name' => 'Lazy Sunday', 'emoji' => '🌙', 'colour' => '#10B981'])
            ->assertOk()
            ->assertJson(['updated' => true]);

        $this->assertDatabaseHas('saved_profiles', [
            'id' => $profile->id,
            'name' => 'Lazy Sunday',
            'emoji' => '🌙',
            'colour' => '#10B981',
        ]);
    }

    public function test_rename_requires_a_name(): void
    {
        $user = User::factory()->create();
        $profile = SavedProfile::factory()->create(['user_id' => $user->id, 'name' => 'Keep Me']);

        $this->actingAs($user)
            ->putJson("/saved-profiles/{$profile->id}", ['name' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        $this->assertSame('Keep Me', $profile->fresh()->name);
    }

    public function test_another_user_cannot_rename_or_delete_a_profile(): void
    {
        $profile = SavedProfile::factory()->create(['name' => 'Private']);
        $intruder = User::factory()->create();

        $this->actingAs($intruder)
            ->putJson("/saved-profiles/{$profile->id}", ['name' => 'Hijacked'])
            ->assertForbidden();

        $this->actingAs($intruder)
            ->deleteJson("/saved-profiles/{$profile->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('saved_profiles', ['id' => $profile->id, 'name' => 'Private']);
    }

    public function test_owner_can_delete_a_profile_and_its_answers(): void
    {
        $user = User::factory()->create();
        $profile = SavedProfile::factory()->create(['user_id' => $user->id]);
        SavedProfileAnswer::factory()->create(['saved_profile_id' => $profile->id]);

        $this->actingAs($user)
            ->deleteJson("/saved-profiles/{$profile->id}")
            ->assertOk()
            ->assertJson(['deleted' => true]);

        $this->assertDatabaseMissing('saved_profiles', ['id' => $profile->id]);
        $this->assertDatabaseCount('saved_profile_answers', 0);
    }

    public function test_profile_saved_without_emoji_or_colour_stores_null(): void
    {
        $user = User::factory()->create();
        $questionnaire = Questionnaire::factory()->create();
        Response::factory()->create(['user_id' => $user->id, 'questionnaire_id' => $questionnaire->id]);

        $this->actingAs($user)
            ->postJson('/saved-profiles', ['name' => 'Plain', 'questionnaire_id' => $questionnaire->id])
            ->assertOk()
            ->assertJsonPath('profile.emoji', null);

        $this->assertDatabaseHas('saved_profiles', ['name' => 'Plain', 'emoji' => null, 'colour' => null]);
    }
}
