<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('date_night_plan_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('date_night_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->string('note', 500)->nullable();
            $table->date('completed_on');
            $table->timestamps();

            $table->unique(['date_night_plan_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('date_night_plan_completions');
    }
};
