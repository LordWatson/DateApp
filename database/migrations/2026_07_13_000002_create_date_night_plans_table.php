<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('date_night_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('questionnaire_id')->constrained()->cascadeOnDelete();
            $table->foreignId('partner_one_response_id')->constrained('responses')->cascadeOnDelete();
            $table->foreignId('partner_two_response_id')->constrained('responses')->cascadeOnDelete();
            $table->foreignId('date_night_theme_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('compatibility_score')->default(0);
            $table->string('theme');
            $table->string('theme_emoji')->nullable();
            $table->text('summary');
            $table->string('meal_suggestion')->nullable();
            $table->string('drink_suggestion')->nullable();
            $table->string('music_vibe')->nullable();
            $table->string('atmosphere')->nullable();
            $table->string('activity')->nullable();
            $table->text('conversation_prompt')->nullable();
            $table->string('romantic_challenge')->nullable();
            $table->boolean('is_favourite')->default(false);
            $table->timestamps();

            $table->unique(['partner_one_response_id', 'partner_two_response_id'], 'dnp_responses_unique');
            $table->index('questionnaire_id');
            $table->index('compatibility_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('date_night_plans');
    }
};
