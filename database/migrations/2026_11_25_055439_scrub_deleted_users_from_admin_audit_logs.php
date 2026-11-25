<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * "user.deleted" rows used to keep the whole user row (email, name, date of birth).
     * The row id, the admin and the time stay; the personal data goes.
     */
    public function up(): void
    {
        DB::table('admin_audit_logs')
            ->where('action', 'user.deleted')
            ->update(['before' => null, 'after' => null]);
    }

    public function down(): void
    {
        // The removed data cannot be restored.
    }
};
