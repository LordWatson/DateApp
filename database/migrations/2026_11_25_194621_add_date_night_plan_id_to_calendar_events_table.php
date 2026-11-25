<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendar_events', function (Blueprint $table) {
            $table->foreignId('date_night_plan_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });

        // Events added from a plan before the link existed: matched on the copied title and summary.
        // An event whose title was edited, or whose plan was reworded since, stays unlinked.
        DB::table('calendar_events')->where('type', 'date_night')->lazyById()->each(function (object $event): void {
            $ownResponses = DB::table('responses')->where('user_id', $event->user_id)->select('id');

            $planId = DB::table('date_night_plans')
                ->where('theme', $event->title)
                ->where('summary', $event->description)
                ->where(fn ($query) => $query
                    ->where('partner_user_id', $event->user_id)
                    ->orWhereIn('partner_one_response_id', $ownResponses)
                    ->orWhereIn('partner_two_response_id', $ownResponses))
                ->value('id');

            if ($planId !== null) {
                DB::table('calendar_events')->where('id', $event->id)->update(['date_night_plan_id' => $planId]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('calendar_events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('date_night_plan_id');
        });
    }
};
