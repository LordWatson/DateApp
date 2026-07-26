<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('questionnaires', 'is_solo')) {
            Schema::table('questionnaires', function (Blueprint $table) {
                $table->boolean('is_solo')->default(false)->after('is_intimacy');
                $table->index('is_solo');
            });
        }

        Schema::table('responses', function (Blueprint $table) {
            if (! Schema::hasColumn('responses', 'location_label')) {
                $table->string('location_label')->nullable()->after('compatibility_score');
            }
            if (! Schema::hasColumn('responses', 'location_city')) {
                $table->string('location_city')->nullable()->after('location_label');
            }
            if (! Schema::hasColumn('responses', 'location_region')) {
                $table->string('location_region')->nullable()->after('location_city');
            }
            if (! Schema::hasColumn('responses', 'location_country')) {
                $table->string('location_country')->nullable()->after('location_region');
            }
        });

        // Drop the FKs that reference the composite unique index, then drop the
        // unique itself so a single response can be used for both refs (solo
        // plans). The FKs and a plain composite index are re-added below.
        Schema::table('date_night_plans', function (Blueprint $table) {
            $table->dropForeign(['partner_one_response_id']);
            $table->dropForeign(['partner_two_response_id']);
        });

        Schema::table('date_night_plans', function (Blueprint $table) {
            $table->dropUnique('dnp_responses_unique');
        });

        Schema::table('date_night_plans', function (Blueprint $table) {
            $table->boolean('is_solo')->default(false)->after('compatibility_score');
            $table->json('local_suggestions')->nullable()->after('romantic_challenge');
            $table->string('location_label')->nullable()->after('local_suggestions');
            $table->index(['partner_one_response_id', 'partner_two_response_id'], 'dnp_responses_index');

            $table->foreign('partner_one_response_id')
                ->references('id')->on('responses')->cascadeOnDelete();
            $table->foreign('partner_two_response_id')
                ->references('id')->on('responses')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('date_night_plans', function (Blueprint $table) {
            $table->dropIndex('dnp_responses_index');
            $table->dropColumn(['is_solo', 'local_suggestions', 'location_label']);
        });

        Schema::table('date_night_plans', function (Blueprint $table) {
            $table->unique(['partner_one_response_id', 'partner_two_response_id'], 'dnp_responses_unique');
        });

        Schema::table('responses', function (Blueprint $table) {
            $table->dropColumn(['location_label', 'location_city', 'location_region', 'location_country']);
        });

        Schema::table('questionnaires', function (Blueprint $table) {
            $table->dropIndex(['is_solo']);
            $table->dropColumn('is_solo');
        });
    }
};
