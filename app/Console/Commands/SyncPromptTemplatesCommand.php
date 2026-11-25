<?php

namespace App\Console\Commands;

use App\Models\AiPromptTemplate;
use Database\Seeders\AiPromptTemplateSeeder;
use Illuminate\Console\Command;

final class SyncPromptTemplatesCommand extends Command
{
    protected $signature = 'datenight:sync-prompts';

    protected $description = 'Add AI prompt template versions that are missing, without touching existing rows';

    public function handle(AiPromptTemplateSeeder $seeder): int
    {
        $added = 0;

        foreach ($seeder->templates() as $template) {
            // An admin edit raises a row's version in place, so "same or newer exists" is the test:
            // re-adding an older version could bring back a prompt an admin replaced or switched off.
            $current = AiPromptTemplate::query()
                ->where('name', $template['name'])
                ->where('version', '>=', $template['version'])
                ->exists();

            if ($current) {
                continue;
            }

            AiPromptTemplate::query()->create($template);
            $this->line("Added {$template['name']} v{$template['version']}");
            $added++;
        }

        $this->info($added === 0 ? 'Prompt templates are up to date.' : "Added {$added} prompt template(s).");

        return self::SUCCESS;
    }
}
