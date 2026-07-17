<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add a supporting index first so the foreign keys on user_id and
        // questionnaire_id do not rely on the composite unique index that we
        // are about to drop. Without this, MySQL raises errno 1553.
        Schema::table('responses', function (Blueprint $table) {
            $table->index(['user_id', 'questionnaire_id', 'status'], 'responses_user_questionnaire_status_index');
        });

        Schema::table('responses', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'questionnaire_id']);
        });
    }

    public function down(): void
    {
        Schema::table('responses', function (Blueprint $table) {
            $table->unique(['user_id', 'questionnaire_id']);
        });

        Schema::table('responses', function (Blueprint $table) {
            $table->dropIndex('responses_user_questionnaire_status_index');
        });
    }
};
