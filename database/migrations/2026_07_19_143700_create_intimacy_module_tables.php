<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questionnaires', function (Blueprint $table) {
            $table->boolean('is_intimacy')->default(false)->after('is_seasonal');
            $table->index('is_intimacy');
        });

        Schema::create('intimacy_games', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('emoji')->nullable();
            $table->string('tagline')->nullable();
            $table->text('description');
            $table->text('how_to_play');
            $table->unsignedTinyInteger('players')->default(2);
            $table->unsignedSmallInteger('estimated_minutes')->nullable();
            $table->string('intensity')->default('flirty'); // flirty, spicy, wild
            $table->string('category')->default('game');    // game, dare, roleplay, truth
            $table->json('prompts')->nullable();            // list of prompts / cards
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
            $table->index('display_order');
            $table->index('intensity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intimacy_games');

        Schema::table('questionnaires', function (Blueprint $table) {
            $table->dropIndex(['is_intimacy']);
            $table->dropColumn('is_intimacy');
        });
    }
};
