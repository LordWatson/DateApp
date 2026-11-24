<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questionnaires', function (Blueprint $table): void {
            $table->boolean('partner_optional')->default(false)->after('is_solo');
        });

        Schema::table('responses', function (Blueprint $table): void {
            // Mode chosen for this attempt. Null means "the questionnaire's own is_solo".
            $table->boolean('is_solo')->nullable()->after('status');
        });

        DB::table('questionnaires')->where('slug', 'plan-a-date-out')->update(['partner_optional' => true]);
    }

    public function down(): void
    {
        Schema::table('responses', function (Blueprint $table): void {
            $table->dropColumn('is_solo');
        });

        Schema::table('questionnaires', function (Blueprint $table): void {
            $table->dropColumn('partner_optional');
        });
    }
};
