<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('connected_at')->nullable()->after('partner_id');
        });

        // Existing couples keep the date the partner page already showed:
        // the newest invitation either of them had accepted.
        DB::table('users')->whereNotNull('partner_id')->orderBy('id')->each(function (object $user): void {
            $acceptedAt = DB::table('partner_invitations')
                ->whereNotNull('accepted_at')
                ->whereIn('sender_id', [$user->id, $user->partner_id])
                ->max('accepted_at');

            if ($acceptedAt !== null) {
                DB::table('users')->where('id', $user->id)->update(['connected_at' => $acceptedAt]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('connected_at');
        });
    }
};
