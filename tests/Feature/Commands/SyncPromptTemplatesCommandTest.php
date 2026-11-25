<?php

namespace Tests\Feature\Commands;

use App\Models\AiPromptTemplate;
use Database\Seeders\AiPromptTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncPromptTemplatesCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function seededTemplate(): array
    {
        return (new AiPromptTemplateSeeder)->templates()[0];
    }

    public function test_it_adds_every_missing_template_and_is_safe_to_run_again(): void
    {
        $expected = count((new AiPromptTemplateSeeder)->templates());
        $seeded = $this->seededTemplate();

        $this->artisan('datenight:sync-prompts')
            ->expectsOutputToContain("Added {$seeded['name']} v{$seeded['version']}")
            ->assertSuccessful();

        $this->assertDatabaseCount('ai_prompt_templates', $expected);

        $this->artisan('datenight:sync-prompts')
            ->expectsOutputToContain('Prompt templates are up to date.')
            ->assertSuccessful();

        $this->assertDatabaseCount('ai_prompt_templates', $expected);
    }

    public function test_it_leaves_a_row_an_admin_edited_at_the_same_version_alone(): void
    {
        $seeded = $this->seededTemplate();
        $edited = AiPromptTemplate::factory()->create([
            'name' => $seeded['name'],
            'version' => $seeded['version'],
            'system_prompt' => 'Edited by an admin.',
            'active' => false,
        ]);

        $this->artisan('datenight:sync-prompts')->assertSuccessful();

        $edited->refresh();
        $this->assertSame('Edited by an admin.', $edited->system_prompt);
        $this->assertFalse($edited->active);
        $this->assertSame(1, AiPromptTemplate::where('name', $seeded['name'])->count());
    }

    public function test_it_does_not_bring_back_a_version_older_than_the_admins(): void
    {
        $seeded = $this->seededTemplate();
        AiPromptTemplate::factory()->create(['name' => $seeded['name'], 'version' => $seeded['version'] + 1]);

        $this->artisan('datenight:sync-prompts')->assertSuccessful();

        $this->assertSame(1, AiPromptTemplate::where('name', $seeded['name'])->count());
    }

    public function test_it_adds_a_seeded_version_newer_than_the_stored_one(): void
    {
        $seeded = $this->seededTemplate();
        $old = AiPromptTemplate::factory()->create([
            'name' => $seeded['name'],
            'version' => $seeded['version'] - 1,
            'system_prompt' => 'Old prompt.',
        ]);

        $this->artisan('datenight:sync-prompts')->assertSuccessful();

        $this->assertSame('Old prompt.', $old->fresh()->system_prompt);
        $this->assertDatabaseHas('ai_prompt_templates', ['name' => $seeded['name'], 'version' => $seeded['version']]);
    }
}
