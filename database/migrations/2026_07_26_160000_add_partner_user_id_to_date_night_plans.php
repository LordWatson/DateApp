<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('date_night_plans', function (Blueprint $table) {
            if (! Schema::hasColumn('date_night_plans', 'partner_user_id')) {
                $table->foreignId('partner_user_id')
                    ->nullable()
                    ->after('date_night_theme_id')
                    ->constrained('users')
                    ->nullOnDelete();
                $table->index('partner_user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('date_night_plans', function (Blueprint $table) {
            if (Schema::hasColumn('date_night_plans', 'partner_user_id')) {
                $table->dropForeign(['partner_user_id']);
                $table->dropIndex(['partner_user_id']);
                $table->dropColumn('partner_user_id');
            }
        });
    }
};
