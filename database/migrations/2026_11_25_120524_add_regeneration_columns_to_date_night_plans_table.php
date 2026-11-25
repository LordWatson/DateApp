<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('date_night_plans', function (Blueprint $table) {
            $table->string('last_steer', 200)->nullable();
            $table->unsignedTinyInteger('regeneration_count')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('date_night_plans', function (Blueprint $table) {
            $table->dropColumn(['last_steer', 'regeneration_count']);
        });
    }
};
