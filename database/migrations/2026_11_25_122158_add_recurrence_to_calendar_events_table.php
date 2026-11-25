<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendar_events', function (Blueprint $table) {
            $table->string('recurrence')->nullable()->after('date');
            // The occurrence a repeating event was last reminded for; `reminder_sent` covers one-off events.
            $table->date('reminded_for_date')->nullable()->after('reminder_sent');
        });
    }

    public function down(): void
    {
        Schema::table('calendar_events', function (Blueprint $table) {
            $table->dropColumn(['recurrence', 'reminded_for_date']);
        });
    }
};
