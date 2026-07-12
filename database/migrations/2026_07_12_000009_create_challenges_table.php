<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('challenges', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('emoji')->nullable();
            $table->string('difficulty')->default('easy');
            $table->boolean('active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['active', 'difficulty']);
            $table->index('display_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('challenges');
    }
};
