<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('questionnaire_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('emoji')->nullable();
            $table->string('type');
            $table->boolean('required')->default(true);
            $table->integer('minimum_value')->nullable();
            $table->integer('maximum_value')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['questionnaire_id', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
