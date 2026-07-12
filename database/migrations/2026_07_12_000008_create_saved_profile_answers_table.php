<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_profile_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('saved_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_option_id')->nullable()->constrained()->nullOnDelete();
            $table->text('value')->nullable();
            $table->timestamps();

            $table->index(['saved_profile_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_profile_answers');
    }
};
