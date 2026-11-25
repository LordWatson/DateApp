<?php

namespace Tests\Feature\Admin;

use App\Contracts\AI\AIProvider;
use App\Models\AdminAuditLog;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Role;
use App\Models\User;
use App\Services\AI\NullAIProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuditGapsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role_id' => Role::factory()->superAdmin()->create()->id]);
    }

    public function test_reordering_questions_and_options_is_audited(): void
    {
        $question = Question::factory()->singleChoice()->create();
        $option = QuestionOption::factory()->create(['question_id' => $question->id]);

        $this->actingAs($this->admin)->post('/admin/questionnaires/reorder', [
            'order' => [['id' => $question->id, 'display_order' => 3]],
        ])->assertRedirect();

        $this->actingAs($this->admin)->post('/admin/questionnaires/reorder', [
            'type' => 'options',
            'order' => [['id' => $option->id, 'display_order' => 2]],
        ])->assertRedirect();

        $questions = AdminAuditLog::where('action', 'questions.reordered')->sole();
        $this->assertSame($this->admin->id, $questions->user_id);
        $this->assertSame([['id' => $question->id, 'display_order' => 3]], $questions->after['order']);

        $options = AdminAuditLog::where('action', 'options.reordered')->sole();
        $this->assertSame([['id' => $option->id, 'display_order' => 2]], $options->after['order']);
    }

    public function test_ai_test_calls_are_audited_without_the_context_values(): void
    {
        $this->app->instance(AIProvider::class, new NullAIProvider);

        $this->actingAs($this->admin)->postJson('/admin/ai-settings/test-connection')->assertOk();
        $this->actingAs($this->admin)->postJson('/admin/ai-settings/preview-response', [
            'use_case' => 'date_night_plan',
            'context' => ['answers' => 'something private'],
        ])->assertOk();

        $this->assertSame(1, AdminAuditLog::where('action', 'ai.connection.tested')->count());

        $preview = AdminAuditLog::where('action', 'ai.response.previewed')->sole();
        $this->assertSame(['answers'], $preview->after['context_keys']);
        $this->assertStringNotContainsString('something private', (string) json_encode($preview->after));
    }

    public function test_deleting_a_user_keeps_no_personal_data_in_the_audit_log(): void
    {
        $user = User::factory()->create([
            'name' => 'Jamie Private',
            'email' => 'jamie@example.com',
            'date_of_birth' => '1990-04-12',
        ]);

        $this->actingAs($this->admin)->delete("/admin/users/{$user->id}")->assertRedirect();

        $log = AdminAuditLog::where('action', 'user.deleted')->sole();
        $this->assertSame($user->id, $log->auditable_id);
        $this->assertSame(['id', 'role_id', 'created_at', 'email_verified_at'], array_keys($log->before));

        $stored = (string) json_encode([$log->before, $log->after]);
        $this->assertStringNotContainsString('jamie@example.com', $stored);
        $this->assertStringNotContainsString('Jamie Private', $stored);
        $this->assertStringNotContainsString('1990-04-12', $stored);
    }

    public function test_the_migration_scrubs_old_user_deleted_rows(): void
    {
        $old = AdminAuditLog::create(['action' => 'user.deleted', 'auditable_id' => 9, 'before' => ['email' => 'old@example.com']]);
        $other = AdminAuditLog::create(['action' => 'question.deleted', 'before' => ['title' => 'Kept']]);

        (require database_path('migrations/2026_11_25_055439_scrub_deleted_users_from_admin_audit_logs.php'))->up();

        $this->assertNull($old->fresh()->before);
        $this->assertSame(9, $old->fresh()->auditable_id);
        $this->assertSame(['title' => 'Kept'], $other->fresh()->before);
    }
}
