<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            if (! Schema::hasColumn('questions', 'step_value')) {
                $table->integer('step_value')->nullable()->after('maximum_value');
            }
            if (! Schema::hasColumn('questions', 'unit')) {
                $table->string('unit', 32)->nullable()->after('step_value');
            }
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['step_value', 'unit']);
        });
    }
};
