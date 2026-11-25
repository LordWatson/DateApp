<?php

namespace Tests\Feature\Security;

use App\Actions\DeleteAccountAction;
use App\Enums\NotificationType;
use App\Models\AppNotification;
use App\Models\DateNightPlan;
use App\Models\LoveNote;
use App\Models\Moment;
use App\Models\Questionnaire;
use App\Models\Response;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoCoupleSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class AccountSafetyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: User}
     */
    private function couple(): array
    {
        $one = User::factory()->create();
        $two = User::factory()->create(['partner_id' => $one->id]);
        $one->update(['partner_id' => $two->id]);

        return [$one, $two];
    }

    public function test_deleting_an_account_leaves_the_partner_their_history(): void
    {
        Storage::fake(config('filesystems.uploads'));
        [$leaving, $partner] = $this->couple();

        $questionnaire = Questionnaire::factory()->create();
        $leavingResponse = Response::factory()->completed()->create(['user_id' => $leaving->id, 'questionnaire_id' => $questionnaire->id]);
        $partnerResponse = Response::factory()->completed()->create(['user_id' => $partner->id, 'questionnaire_id' => $questionnaire->id]);
        $sharedPlan = DateNightPlan::factory()->create([
            'questionnaire_id' => $questionnaire->id,
            'partner_one_response_id' => $leavingResponse->id,
            'partner_two_response_id' => $partnerResponse->id,
        ]);

        Storage::disk(config('filesystems.uploads'))->put('moments/leaving.jpg', 'x');
        Moment::factory()->create(['user_id' => $leaving->id, 'photo' => 'moments/leaving.jpg']);
        $partnerMoment = Moment::factory()->create(['user_id' => $partner->id]);
        LoveNote::factory()->create(['sender_id' => $partner->id, 'recipient_id' => $leaving->id]);

        app(DeleteAccountAction::class)->execute($leaving);

        $this->assertNull(User::find($leaving->id));
        $this->assertNull($partner->fresh()->partner_id);

        // The partner still has the plan and can open it.
        $this->assertNotNull($sharedPlan->fresh());
        $this->assertTrue($partner->fresh()->can('view', $sharedPlan->fresh()));
        $this->actingAs($partner->fresh())->get(route('date-night.show', $sharedPlan))->assertOk();

        $this->assertNotNull($partnerMoment->fresh());
        Storage::disk(config('filesystems.uploads'))->assertMissing('moments/leaving.jpg');
        $this->assertSame(1, AppNotification::where('user_id', $partner->id)->where('type', NotificationType::PartnerLeft)->count());
    }

    public function test_a_suspended_user_is_signed_out_on_their_next_request(): void
    {
        $user = User::factory()->create(['is_suspended' => true, 'suspended_at' => now()]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_registration_sends_a_verification_email_and_unverified_users_are_held_back(): void
    {
        Notification::fake();

        $this->post(route('register.store'), [
            'name' => 'New Person',
            'email' => 'new@example.com',
            'password' => 'a-Long-password-123!',
            'password_confirmation' => 'a-Long-password-123!',
            'terms' => true,
        ]);

        $user = User::where('email', 'new@example.com')->sole();
        Notification::assertSentTo($user, VerifyEmail::class);

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('verification.notice'));
    }

    public function test_a_moment_cannot_be_linked_to_another_couples_plan(): void
    {
        [$user] = $this->couple();
        $strangersPlan = DateNightPlan::factory()->create();

        $this->actingAs($user)
            ->post(route('moments.store'), [
                'title' => 'A moment',
                'date' => now()->toDateString(),
                'date_night_plan_id' => $strangersPlan->id,
            ])
            ->assertSessionHasErrors('date_night_plan_id');

        $this->assertSame(0, Moment::count());
    }

    public function test_seeding_twice_is_safe_and_production_gets_no_demo_accounts(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(2, User::count());
        $this->assertSame(1, Questionnaire::where('slug', 'tonight')->count());

        User::query()->delete();
        $this->app->detectEnvironment(fn () => 'production');

        try {
            // Called directly: the artisan command would stop to ask for confirmation in production.
            (new DatabaseSeeder)->setContainer($this->app)->run();
            $this->assertSame(0, User::count());

            $this->expectException(RuntimeException::class);
            (new DemoCoupleSeeder)->run();
        } finally {
            $this->app->detectEnvironment(fn () => 'testing');
        }
    }
}
