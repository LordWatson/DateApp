<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nothing ever read or wrote these tables: prompts live in ai_prompt_templates
     * and the timeline is built on the fly by TimelineBuilderService.
     */
    public function up(): void
    {
        Schema::dropIfExists('ai_prompts');
        Schema::dropIfExists('timeline_entries');

        // Prompts for AI features that were removed without ever running.
        DB::table('ai_prompt_templates')->whereIn('name', [
            'conversation_prompt', 'relationship_insight', 'compatibility_summary',
            'challenge_variation', 'theme_description', 'moment_caption',
        ])->delete();
    }

    public function down(): void
    {
        Schema::create('ai_prompts', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->string('type');
            $table->longText('content');
            $table->text('description')->nullable();
            $table->integer('version')->default(1);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('timeline_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('emoji')->nullable();
            $table->morphs('subject');
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['user_id', 'occurred_at']);
            $table->index('type');
        });
    }
};
