<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questionnaires', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('emoji')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('status')->default('draft');
            $table->string('visibility')->default('public');
            $table->unsignedTinyInteger('estimated_minutes')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamp('active_from')->nullable();
            $table->timestamp('active_until')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('display_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questionnaires');
    }
};
