<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\DateNightPlan;
use App\Models\LoveNote;
use App\Models\Response;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class EndConnectionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $partner;

    private DateNightPlan $together;

    private DateNightPlan $mySolo;

    private DateNightPlan $theirSolo;

    private LoveNote $sent;

    private LoveNote $received;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['display_name' => 'Alex']);
        $this->partner = User::factory()->create(['display_name' => 'Sam', 'partner_id' => $this->user->id]);
        $this->user->update(['partner_id' => $this->partner->id]);

        $mine = Response::factory()->completed()->create(['user_id' => $this->user->id]);
        $theirs = Response::factory()->completed()->create(['user_id' => $this->partner->id]);

        $this->together = DateNightPlan::factory()->create([
            'partner_one_response_id' => $mine->id, 'partner_two_response_id' => $theirs->id, 'theme' => 'Together Night',
        ]);
        $this->mySolo = DateNightPlan::factory()->create([
            'partner_one_response_id' => $mine->id, 'partner_two_response_id' => $mine->id,
            'partner_user_id' => $this->partner->id, 'is_solo' => true, 'theme' => 'My Solo Night',
        ]);
        $this->theirSolo = DateNightPlan::factory()->create([
            'partner_one_response_id' => $theirs->id, 'partner_two_response_id' => $theirs->id,
            'partner_user_id' => $this->user->id, 'is_solo' => true, 'theme' => 'Their Solo Night',
        ]);

        $this->sent = LoveNote::factory()->create(['sender_id' => $this->user->id, 'recipient_id' => $this->partner->id, 'message' => 'From me']);
        $this->received = LoveNote::factory()->create(['sender_id' => $this->partner->id, 'recipient_id' => $this->user->id, 'message' => 'From them']);
    }

    public function test_the_partner_page_counts_what_there_is_to_decide(): void
    {
        $this->actingAs($this->user)
            ->get(route('partner.index'))
            ->assertInertia(fn ($page) => $page
                ->where('shared.plans', 2)
                ->where('shared.notes', 2)
                ->where('shared.keeping_plans', true)
                ->where('shared.keeping_notes', true)
            );
    }

    public function test_ending_and_keeping_everything_hides_nothing(): void
    {
        $this->end(keepPlans: true, keepNotes: true)->assertRedirect(route('partner.index'));

        $user = $this->user->fresh();
        $this->assertNull($user->partner_id);
        $this->assertNull($this->partner->fresh()->partner_id);
        $this->assertSame($this->partner->id, $user->former_partner_id);
        $this->assertSame($this->user->id, $this->partner->fresh()->former_partner_id);

        $this->assertSame(3, $this->historyThemes($user)->count());
        $this->actingAs($user)->get(route('love-notes.index'))->assertInertia(fn ($page) => $page->has('sent', 1)->has('received', 1));

        $this->assertSame('partner_left', AppNotification::where('user_id', $this->partner->id)->sole()->type->value);
    }

    public function test_hiding_plans_removes_shared_ones_from_my_account_only(): void
    {
        $this->end(keepPlans: false, keepNotes: true);
        $user = $this->user->fresh();

        // My own solo plan is mine; the ones built on their answers go.
        $this->assertSame(['My Solo Night'], $this->historyThemes($user)->all());
        $this->actingAs($user)->get(route('date-night.show', $this->together))->assertForbidden();
        $this->actingAs($user)->get(route('date-night.show', $this->theirSolo))->assertForbidden();
        $this->actingAs($user)->get(route('date-night.show', $this->mySolo))->assertOk();

        $this->actingAs($user)
            ->get(route('search.index', ['q' => 'Together']))
            ->assertInertia(fn ($page) => $page->missing('results.plans'));
        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page->has('recentDateNights', 1));

        // The other person's account is untouched.
        $partner = $this->partner->fresh();
        $this->assertContains('Together Night', $this->historyThemes($partner)->all());
        $this->actingAs($partner)->get(route('date-night.show', $this->together))->assertOk();
    }

    public function test_hiding_notes_removes_them_from_my_account_only(): void
    {
        $this->end(keepPlans: true, keepNotes: false);
        $user = $this->user->fresh();

        $this->actingAs($user)->get(route('love-notes.index'))->assertInertia(fn ($page) => $page->has('sent', 0)->has('received', 0));
        $this->actingAs($user)->get(route('love-notes.show', $this->sent))->assertNotFound();
        $this->actingAs($user)->get(route('love-notes.show', $this->received))->assertNotFound();
        $this->actingAs($user)
            ->get(route('search.index', ['q' => 'From']))
            ->assertInertia(fn ($page) => $page->missing('results.love_notes'));

        $this->actingAs($this->partner->fresh())
            ->get(route('love-notes.index'))
            ->assertInertia(fn ($page) => $page->has('sent', 1)->has('received', 1));
        $this->assertDatabaseCount('love_notes', 2);
    }

    public function test_the_password_and_both_choices_are_required(): void
    {
        $this->actingAs($this->user)
            ->delete(route('partner.disconnect'), ['keep_plans' => true, 'keep_notes' => true, 'password' => 'wrong'])
            ->assertSessionHasErrors('password');

        $this->actingAs($this->user)
            ->delete(route('partner.disconnect'), ['password' => 'password'])
            ->assertSessionHasErrors(['keep_plans', 'keep_notes']);

        $this->assertSame($this->partner->id, $this->user->fresh()->partner_id);
    }

    public function test_the_other_person_decides_for_themselves_afterwards_and_can_change_their_mind(): void
    {
        $this->end(keepPlans: true, keepNotes: true);
        $partner = $this->partner->fresh();

        $this->actingAs($partner)
            ->get(route('partner.after'))
            ->assertInertia(fn ($page) => $page
                ->component('PartnerAfter')
                ->where('formerPartner.name', 'Alex')
                ->where('shared.plans', 2)
                ->where('shared.keeping_plans', true)
            );

        $this->actingAs($partner)
            ->post(route('partner.apply-choices'), ['keep_plans' => false, 'keep_notes' => false])
            ->assertRedirect(route('partner.index'));

        // Their own solo plan stays; the two built on my answers go. My account is untouched.
        $this->assertSame(['Their Solo Night'], $this->historyThemes($partner)->all());
        $this->actingAs($partner)->get(route('love-notes.index'))->assertInertia(fn ($page) => $page->has('sent', 0)->has('received', 0));
        $this->assertSame(3, $this->historyThemes($this->user->fresh())->count());

        $this->actingAs($partner)
            ->get(route('partner.after'))
            ->assertInertia(fn ($page) => $page->where('shared.keeping_plans', false)->where('shared.keeping_notes', false));

        $this->actingAs($partner)->post(route('partner.apply-choices'), ['keep_plans' => true, 'keep_notes' => true]);

        $this->assertSame(3, $this->historyThemes($partner)->count());
        $this->actingAs($partner)->get(route('love-notes.index'))->assertInertia(fn ($page) => $page->has('sent', 1)->has('received', 1));
    }

    public function test_the_choices_page_is_only_for_someone_whose_connection_has_ended(): void
    {
        $this->actingAs($this->user)->get(route('partner.after'))->assertRedirect(route('partner.index'));
        $this->actingAs(User::factory()->create())->get(route('partner.after'))->assertRedirect(route('partner.index'));

        // Posting choices while still connected changes nothing.
        $this->actingAs($this->user)->post(route('partner.apply-choices'), ['keep_plans' => false, 'keep_notes' => false]);

        $this->assertDatabaseCount('date_night_plan_hidden', 0);
        $this->assertNull($this->sent->fresh()->hidden_for_sender_at);
    }

    private function end(bool $keepPlans, bool $keepNotes): TestResponse
    {
        return $this->actingAs($this->user)->delete(route('partner.disconnect'), [
            'keep_plans' => $keepPlans,
            'keep_notes' => $keepNotes,
            'password' => 'password',
        ]);
    }

    /**
     * @return Collection<int, string>
     */
    private function historyThemes(User $user): Collection
    {
        return collect($this->actingAs($user)->get(route('date-night.history'))->inertiaProps('plans.data'))
            ->pluck('theme')
            ->sort()
            ->values();
    }
}
