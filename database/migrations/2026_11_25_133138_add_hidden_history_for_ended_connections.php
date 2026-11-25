<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * After a connection ends, each person can take shared plans and love
     * notes out of their own account. Nothing is deleted: it is hidden per person.
     */
    public function up(): void
    {
        Schema::create('date_night_plan_hidden', function (Blueprint $table) {
            $table->id();
            $table->foreignId('date_night_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['date_night_plan_id', 'user_id']);
        });

        Schema::table('love_notes', function (Blueprint $table) {
            $table->timestamp('hidden_for_sender_at')->nullable();
            $table->timestamp('hidden_for_recipient_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('date_night_plan_hidden');

        Schema::table('love_notes', function (Blueprint $table) {
            $table->dropColumn(['hidden_for_sender_at', 'hidden_for_recipient_at']);
        });
    }
};
