<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('challenges', function (Blueprint $table) {
            $table->string('category')->nullable()->after('emoji');
            $table->string('season')->nullable()->after('category');
            $table->integer('weight')->default(1)->after('season');
            $table->boolean('archived')->default(false)->after('active');
        });
    }

    public function down(): void
    {
        Schema::table('challenges', function (Blueprint $table) {
            $table->dropColumn(['category', 'season', 'weight', 'archived']);
        });
    }
};
