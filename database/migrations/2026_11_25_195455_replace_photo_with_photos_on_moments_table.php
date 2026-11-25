<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A moment holds up to three photos: `photo` (one path) becomes `photos` (a list of paths).
     */
    public function up(): void
    {
        Schema::table('moments', function (Blueprint $table) {
            $table->json('photos')->nullable()->after('photo');
        });

        DB::table('moments')->whereNotNull('photo')->lazyById()->each(function (object $moment): void {
            DB::table('moments')->where('id', $moment->id)->update(['photos' => json_encode([$moment->photo])]);
        });

        Schema::table('moments', function (Blueprint $table) {
            $table->dropColumn('photo');
        });
    }

    public function down(): void
    {
        Schema::table('moments', function (Blueprint $table) {
            $table->string('photo')->nullable()->after('mood');
        });

        // Only the first photo fits back into the single column.
        DB::table('moments')->whereNotNull('photos')->lazyById()->each(function (object $moment): void {
            DB::table('moments')->where('id', $moment->id)->update(['photo' => json_decode($moment->photos, true)[0] ?? null]);
        });

        Schema::table('moments', function (Blueprint $table) {
            $table->dropColumn('photos');
        });
    }
};
