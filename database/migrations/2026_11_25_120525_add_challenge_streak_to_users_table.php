<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('challenge_streak')->default(0);
            $table->unsignedInteger('longest_challenge_streak')->default(0);
            $table->date('challenge_streak_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['challenge_streak', 'longest_challenge_streak', 'challenge_streak_date']);
        });
    }
};
