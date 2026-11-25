<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('date_night_plan_favourites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('date_night_plan_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['date_night_plan_id', 'user_id']);
        });

        // The old flag was shared, so a favourited plan stays a favourite for everyone on it.
        $now = now();

        DB::table('date_night_plans')
            ->where('is_favourite', true)
            ->orderBy('id')
            ->each(function (object $plan) use ($now): void {
                $userIds = DB::table('responses')
                    ->whereIn('id', [$plan->partner_one_response_id, $plan->partner_two_response_id])
                    ->pluck('user_id')
                    ->push($plan->partner_user_id)
                    ->filter()
                    ->unique();

                foreach ($userIds as $userId) {
                    DB::table('date_night_plan_favourites')->insert([
                        'date_night_plan_id' => $plan->id,
                        'user_id' => $userId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            });

        Schema::table('date_night_plans', function (Blueprint $table) {
            $table->dropColumn('is_favourite');
        });
    }

    public function down(): void
    {
        Schema::table('date_night_plans', function (Blueprint $table) {
            $table->boolean('is_favourite')->default(false);
        });

        DB::table('date_night_plans')
            ->whereIn('id', DB::table('date_night_plan_favourites')->select('date_night_plan_id'))
            ->update(['is_favourite' => true]);

        Schema::dropIfExists('date_night_plan_favourites');
    }
};
