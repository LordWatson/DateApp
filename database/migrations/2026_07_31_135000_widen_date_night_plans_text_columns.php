<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('date_night_plans', function (Blueprint $table): void {
            $table->text('meal_suggestion')->nullable()->change();
            $table->text('drink_suggestion')->nullable()->change();
            $table->text('music_vibe')->nullable()->change();
            $table->text('atmosphere')->nullable()->change();
            $table->text('activity')->nullable()->change();
            $table->text('romantic_challenge')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('date_night_plans', function (Blueprint $table): void {
            $table->string('meal_suggestion')->nullable()->change();
            $table->string('drink_suggestion')->nullable()->change();
            $table->string('music_vibe')->nullable()->change();
            $table->string('atmosphere')->nullable()->change();
            $table->string('activity')->nullable()->change();
            $table->string('romantic_challenge')->nullable()->change();
        });
    }
};
