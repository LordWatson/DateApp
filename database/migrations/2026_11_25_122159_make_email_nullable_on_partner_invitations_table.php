<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * An invitation shared as a link has no email address.
     */
    public function up(): void
    {
        Schema::table('partner_invitations', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('partner_invitations')->whereNull('email')->delete();

        Schema::table('partner_invitations', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });
    }
};
