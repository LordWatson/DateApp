<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('date_night_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('mood')->nullable();
            $table->string('photo')->nullable();
            $table->date('date');
            $table->boolean('is_favourite')->default(false);
            $table->text('private_notes')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'date']);
            $table->index('is_favourite');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moments');
    }
};
