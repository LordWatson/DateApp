<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('responses', function (Blueprint $table) {
            if (! Schema::hasColumn('responses', 'location_latitude')) {
                $table->decimal('location_latitude', 10, 7)->nullable()->after('location_country');
            }
            if (! Schema::hasColumn('responses', 'location_longitude')) {
                $table->decimal('location_longitude', 10, 7)->nullable()->after('location_latitude');
            }
            if (! Schema::hasColumn('responses', 'travel_radius_minutes')) {
                $table->unsignedSmallInteger('travel_radius_minutes')->nullable()->after('location_longitude');
            }
        });
    }

    public function down(): void
    {
        Schema::table('responses', function (Blueprint $table) {
            $table->dropColumn([
                'location_latitude',
                'location_longitude',
                'travel_radius_minutes',
            ]);
        });
    }
};
