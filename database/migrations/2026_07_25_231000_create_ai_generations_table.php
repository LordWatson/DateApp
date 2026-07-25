<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_generations', function (Blueprint $table): void {
            $table->id();
            $table->string('provider')->index();
            $table->string('model')->nullable();
            $table->string('feature')->index();
            $table->string('prompt_template')->nullable();
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->unsignedInteger('total_tokens')->default(0);
            $table->unsignedInteger('duration_ms')->default(0);
            $table->boolean('successful')->default(false)->index();
            $table->boolean('fallback_used')->default(false)->index();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
            $table->index(['feature', 'successful']);
        });

        Schema::table('date_night_plans', function (Blueprint $table): void {
            $table->boolean('ai_enhanced')->default(false)->after('romantic_challenge');
            $table->boolean('fallback_used')->default(false)->after('ai_enhanced');
        });
    }

    public function down(): void
    {
        Schema::table('date_night_plans', function (Blueprint $table): void {
            $table->dropColumn(['ai_enhanced', 'fallback_used']);
        });

        Schema::dropIfExists('ai_generations');
    }
};
