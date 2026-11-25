<?php

namespace Tests\Feature\Security;

use App\Enums\QuestionnaireStatus;
use App\Enums\UserRole;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\Response;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPermissionsTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, Role> */
    private array $roles = [];

    private function userWithRole(UserRole $role): User
    {
        $this->roles[$role->value] ??= Role::factory()->create(['name' => $role->value, 'label' => $role->label()]);

        return User::factory()->create(['role_id' => $this->roles[$role->value]->id]);
    }

    public function test_support_can_look_at_users_but_not_change_them(): void
    {
        $support = $this->userWithRole(UserRole::Support);
        $target = User::factory()->create();

        $this->actingAs($support)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($support)->post(route('admin.users.suspend', $target))->assertForbidden();
        $this->actingAs($support)->delete(route('admin.users.destroy', $target))->assertForbidden();
        $this->actingAs($support)
            ->put(route('admin.users.update', $target), ['name' => 'Changed', 'email' => $target->email])
            ->assertForbidden();

        $this->assertNotNull($target->fresh());
        $this->assertFalse($target->fresh()->is_suspended);
    }

    public function test_each_section_is_limited_to_the_roles_that_own_it(): void
    {
        $moderator = $this->userWithRole(UserRole::Moderator);
        $support = $this->userWithRole(UserRole::Support);
        $admin = $this->userWithRole(UserRole::Administrator);

        $this->actingAs($support)->get(route('admin.questionnaires.index'))->assertForbidden();
        $this->actingAs($moderator)->get(route('admin.analytics.index'))->assertForbidden();
        $this->actingAs($moderator)->get(route('admin.couples.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.feature-flags.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.settings.index'))->assertForbidden();

        $this->actingAs($moderator)->get(route('admin.questionnaires.index'))->assertOk();
    }

    public function test_only_a_super_admin_can_change_roles_and_never_their_own(): void
    {
        $admin = $this->userWithRole(UserRole::Administrator);
        $superAdmin = $this->userWithRole(UserRole::SuperAdministrator);
        $otherSuperAdmin = $this->userWithRole(UserRole::SuperAdministrator);
        $superRoleId = $this->roles[UserRole::SuperAdministrator->value]->id;
        $target = User::factory()->create();

        // An administrator may edit a name, but not hand out a role, least of all to themselves.
        $this->actingAs($admin)
            ->put(route('admin.users.update', $target), ['name' => 'Renamed', 'email' => $target->email, 'role_id' => null])
            ->assertRedirect();
        $this->actingAs($admin)
            ->put(route('admin.users.update', $admin), ['name' => $admin->name, 'email' => $admin->email, 'role_id' => $superRoleId])
            ->assertForbidden();
        $this->actingAs($admin)
            ->put(route('admin.users.update', $target), ['name' => $target->name, 'email' => $target->email, 'role_id' => $superRoleId])
            ->assertForbidden();

        $this->actingAs($superAdmin)
            ->put(route('admin.users.update', $superAdmin), ['name' => $superAdmin->name, 'email' => $superAdmin->email, 'role_id' => null])
            ->assertForbidden();
        $this->actingAs($superAdmin)
            ->put(route('admin.users.update', $target), ['name' => 'Renamed', 'email' => $target->email, 'role_id' => $superRoleId])
            ->assertRedirect();

        $this->assertSame('Renamed', $target->fresh()->name);
        $this->assertSame($superRoleId, $target->fresh()->role_id);
        $this->assertSame($this->roles[UserRole::Administrator->value]->id, $admin->fresh()->role_id);
        $this->assertNotNull($otherSuperAdmin->fresh());
    }

    public function test_super_admins_are_protected_from_other_admins_and_from_themselves(): void
    {
        $admin = $this->userWithRole(UserRole::Administrator);
        $superAdmin = $this->userWithRole(UserRole::SuperAdministrator);

        $this->actingAs($admin)->delete(route('admin.users.destroy', $superAdmin))->assertForbidden();
        $this->actingAs($admin)->post(route('admin.users.suspend', $superAdmin))->assertForbidden();
        $this->actingAs($superAdmin)->delete(route('admin.users.destroy', $superAdmin))->assertForbidden();
        $this->actingAs($admin)->post(route('admin.users.suspend', $admin))->assertForbidden();

        $this->assertNotNull($superAdmin->fresh());
        $this->assertFalse($superAdmin->fresh()->is_suspended);
    }

    public function test_a_questionnaire_people_have_answered_is_archived_not_deleted(): void
    {
        $moderator = $this->userWithRole(UserRole::Moderator);
        $answered = Questionnaire::factory()->create();
        $question = Question::factory()->create(['questionnaire_id' => $answered->id]);
        $response = Response::factory()->create(['questionnaire_id' => $answered->id]);
        Answer::factory()->create(['response_id' => $response->id, 'question_id' => $question->id]);
        $unused = Questionnaire::factory()->create();

        $this->actingAs($moderator)->delete(route('admin.questionnaires.destroy', $answered))->assertRedirect();
        $this->actingAs($moderator)->delete(route('admin.questions.destroy', $question))->assertSessionHas('error');
        $this->actingAs($moderator)->delete(route('admin.questionnaires.destroy', $unused))->assertRedirect();

        $this->assertSame(QuestionnaireStatus::Archived, $answered->fresh()->status);
        $this->assertNotNull($response->fresh());
        $this->assertNotNull($question->fresh());
        $this->assertNull($unused->fresh());
    }
}
