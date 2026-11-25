<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Written on both partners together, like partner_id.
            $table->timestamp('connection_paused_at')->nullable()->after('connected_at');
            $table->foreignId('connection_paused_by')->nullable()->after('connection_paused_at')->constrained('users')->nullOnDelete();
            // Who this person was last connected to, so they can still decide what to keep.
            $table->foreignId('former_partner_id')->nullable()->after('connection_paused_by')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('connection_paused_by');
            $table->dropConstrainedForeignId('former_partner_id');
            $table->dropColumn('connection_paused_at');
        });
    }
};
