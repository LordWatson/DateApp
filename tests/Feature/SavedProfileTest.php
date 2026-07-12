<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\SavedProfile;
use App\Models\SavedProfileAnswer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavedProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_saved_profile_can_be_created(): void
    {
        $user = User::factory()->create();

        $profile = SavedProfile::factory()->create([
            'user_id' => $user->id,
            'name' => 'Romantic Evening',
            'emoji' => '❤️',
            'colour' => '#EC4899',
        ]);

        $this->assertEquals('Romantic Evening', $profile->name);
        $this->assertEquals('❤️', $profile->emoji);
        $this->assertEquals('#EC4899', $profile->colour);
        $this->assertEquals($user->id, $profile->user_id);
    }

    public function test_saved_profile_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $profile = SavedProfile::factory()->create(['user_id' => $user->id]);

        $this->assertEquals($user->id, $profile->user->id);
    }

    public function test_user_has_many_saved_profiles(): void
    {
        $user = User::factory()->create();
        SavedProfile::factory()->count(3)->create(['user_id' => $user->id]);

        $this->assertCount(3, $user->savedProfiles);
    }

    public function test_saved_profile_can_store_answers(): void
    {
        $profile = SavedProfile::factory()->create();
        $question = Question::factory()->singleChoice()->create();
        $option = QuestionOption::factory()->create(['question_id' => $question->id]);

        $answer = SavedProfileAnswer::factory()->create([
            'saved_profile_id' => $profile->id,
            'question_id' => $question->id,
            'question_option_id' => $option->id,
            'value' => null,
        ]);

        $this->assertEquals($profile->id, $answer->saved_profile_id);
        $this->assertEquals($option->id, $answer->question_option_id);
    }

    public function test_saved_profile_has_many_answers(): void
    {
        $profile = SavedProfile::factory()->create();
        SavedProfileAnswer::factory()->count(4)->create(['saved_profile_id' => $profile->id]);

        $this->assertCount(4, $profile->answers);
    }

    public function test_saved_profile_answers_deleted_when_profile_deleted(): void
    {
        $profile = SavedProfile::factory()->create();
        SavedProfileAnswer::factory()->count(3)->create(['saved_profile_id' => $profile->id]);

        $profile->delete();

        $this->assertEquals(0, SavedProfileAnswer::count());
    }

    public function test_saved_profiles_deleted_when_user_deleted(): void
    {
        $user = User::factory()->create();
        SavedProfile::factory()->count(2)->create(['user_id' => $user->id]);

        $user->delete();

        $this->assertEquals(0, SavedProfile::count());
    }
}
