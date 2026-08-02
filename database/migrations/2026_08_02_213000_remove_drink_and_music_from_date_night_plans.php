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
            $table->dropColumn(['drink_suggestion', 'music_vibe']);
        });
    }

    public function down(): void
    {
        Schema::table('date_night_plans', function (Blueprint $table): void {
            $table->text('drink_suggestion')->nullable();
            $table->text('music_vibe')->nullable();
        });
    }
};
