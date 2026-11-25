<?php

namespace Tests\Feature\Admin;

use App\Models\AdminAuditLog;
use App\Models\DateNightPlan;
use App\Models\LoveNote;
use App\Models\Response;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminImpersonationTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $target;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create(['role_id' => Role::factory()->superAdmin()->create()->id]);
        $this->target = User::factory()->create(['display_name' => 'Sam']);
    }

    public function test_a_super_admin_can_view_the_app_as_a_user_and_it_is_audited(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('admin.users.view-as', $this->target))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('impersonator_id', $this->superAdmin->id);

        $this->assertAuthenticatedAs($this->target);

        $entry = AdminAuditLog::where('action', 'user.view_as.started')->sole();
        $this->assertSame($this->superAdmin->id, $entry->user_id);
        $this->assertSame($this->target->id, $entry->auditable_id);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function lesserRoles(): array
    {
        return ['administrator' => ['administrator'], 'support' => ['support'], 'moderator' => ['moderator']];
    }

    #[DataProvider('lesserRoles')]
    public function test_other_admin_roles_cannot_view_as_a_user(string $role): void
    {
        $factory = Role::factory();
        $admin = User::factory()->create([
            'role_id' => ($role === 'administrator' ? $factory : $factory->{$role}())->create()->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.view-as', $this->target))
            ->assertForbidden();

        $this->assertAuthenticatedAs($admin);
        $this->assertDatabaseCount('admin_audit_logs', 0);
    }

    public function test_an_admin_account_or_your_own_cannot_be_viewed_as(): void
    {
        $otherAdmin = User::factory()->create(['role_id' => Role::factory()->support()->create()->id]);

        $this->actingAs($this->superAdmin)->post(route('admin.users.view-as', $otherAdmin))->assertForbidden();
        $this->actingAs($this->superAdmin)->post(route('admin.users.view-as', $this->superAdmin))->assertForbidden();

        $this->assertAuthenticatedAs($this->superAdmin);
    }

    public function test_the_user_page_offers_view_as_only_to_super_admins_and_only_for_users(): void
    {
        $admin = User::factory()->create(['role_id' => Role::factory()->create()->id]);

        $this->actingAs($this->superAdmin)
            ->get(route('admin.users.show', $this->target))
            ->assertInertia(fn ($page) => $page->where('canViewAs', true));

        $this->actingAs($this->superAdmin)
            ->get(route('admin.users.show', $admin))
            ->assertInertia(fn ($page) => $page->where('canViewAs', false));

        $this->actingAs($admin)
            ->get(route('admin.users.show', $this->target))
            ->assertInertia(fn ($page) => $page->where('canViewAs', false));
    }

    public function test_nothing_can_be_changed_while_viewing_as_a_user(): void
    {
        $partner = User::factory()->create(['partner_id' => $this->target->id]);
        $this->target->update(['partner_id' => $partner->id]);

        $this->viewingAsTarget()
            ->post(route('love-notes.store'), ['message' => 'Not from Sam'])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->viewingAsTarget()
            ->postJson(route('love-notes.mark-read'))
            ->assertForbidden();

        $this->viewingAsTarget()->delete(route('partner.disconnect'));

        $this->assertDatabaseCount('love_notes', 0);
        $this->assertSame($partner->id, $this->target->fresh()->partner_id);
    }

    public function test_pages_can_be_read_and_show_the_banner_data(): void
    {
        $this->viewingAsTarget()
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('impersonating.name', 'Sam'));
    }

    public function test_an_ordinary_session_has_no_banner_data(): void
    {
        $this->actingAs($this->target)
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page->where('impersonating', null));
    }

    public function test_opening_pages_does_not_write_on_the_users_behalf(): void
    {
        $partner = User::factory()->create(['partner_id' => $this->target->id]);
        $this->target->update(['partner_id' => $partner->id]);
        $plan = DateNightPlan::factory()->create([
            'partner_one_response_id' => Response::factory()->completed()->create(['user_id' => $this->target->id])->id,
            'partner_two_response_id' => Response::factory()->completed()->create(['user_id' => $partner->id])->id,
        ]);
        $note = LoveNote::factory()->create(['sender_id' => $partner->id, 'recipient_id' => $this->target->id, 'read_at' => null]);

        $this->viewingAsTarget()->get(route('date-night.show', $plan))->assertOk();
        $this->viewingAsTarget()->get(route('love-notes.show', $note))->assertOk();

        $this->assertDatabaseCount('app_notifications', 0);
        $this->assertNull($note->fresh()->read_at);
    }

    public function test_account_pages_and_pages_that_generate_are_closed(): void
    {
        foreach (['security.edit', 'weekly-reflection.index', 'data-export.download'] as $name) {
            $this->viewingAsTarget()
                ->get(route($name))
                ->assertRedirect()
                ->assertSessionHas('error');
        }

        $this->assertDatabaseCount('weekly_reflections', 0);
    }

    public function test_stopping_returns_to_the_admin_account_and_is_audited(): void
    {
        $this->viewingAsTarget()
            ->post(route('impersonation.stop'))
            ->assertRedirect(route('admin.users.show', $this->target))
            ->assertSessionMissing('impersonator_id');

        $this->assertAuthenticatedAs($this->superAdmin);

        $entry = AdminAuditLog::where('action', 'user.view_as.stopped')->sole();
        $this->assertSame($this->superAdmin->id, $entry->user_id);
        $this->assertSame($this->target->id, $entry->auditable_id);
    }

    public function test_viewing_ends_by_itself_after_thirty_minutes(): void
    {
        $this->viewingAsTarget(startedMinutesAgo: 31)
            ->get(route('dashboard'))
            ->assertRedirect(route('admin.users.show', $this->target))
            ->assertSessionMissing('impersonator_id');

        $this->assertAuthenticatedAs($this->superAdmin);
    }

    public function test_stop_is_refused_when_not_viewing_as_anyone(): void
    {
        $this->actingAs($this->target)->post(route('impersonation.stop'))->assertForbidden();
    }

    /**
     * A request made as the target, with the session an administrator has while viewing as them.
     */
    private function viewingAsTarget(int $startedMinutesAgo = 0): static
    {
        return $this->actingAs($this->target)->withSession([
            'impersonator_id' => $this->superAdmin->id,
            'impersonation_started_at' => now()->subMinutes($startedMinutesAgo)->getTimestamp(),
        ]);
    }
}
