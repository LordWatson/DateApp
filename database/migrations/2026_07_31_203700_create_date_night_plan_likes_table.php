<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('date_night_plan_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('date_night_plan_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['date_night_plan_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('date_night_plan_likes');
    }
};
