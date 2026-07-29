<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_prompt_templates', function (Blueprint $table): void {
            // Nullable per-template override for the provider's max_tokens
            // budget. NULL means "fall back to config('ai.defaults.max_tokens')".
            $table->unsignedInteger('max_tokens')->nullable()->after('active');
        });
    }

    public function down(): void
    {
        Schema::table('ai_prompt_templates', function (Blueprint $table): void {
            $table->dropColumn('max_tokens');
        });
    }
};
