<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('gender')->nullable()->after('email');
            $table->foreignId('partner_id')->nullable()->after('gender')->constrained('users')->nullOnDelete();
            $table->date('date_of_birth')->nullable()->after('partner_id');
            $table->string('avatar')->nullable()->after('date_of_birth');
            $table->string('timezone')->default('UTC')->after('avatar');
            $table->timestamp('last_completed_questionnaire_at')->nullable()->after('timezone');
            $table->unsignedInteger('current_streak')->default(0)->after('last_completed_questionnaire_at');
            $table->unsignedInteger('longest_streak')->default(0)->after('current_streak');
            $table->unsignedInteger('monthly_completion_count')->default(0)->after('longest_streak');
            $table->boolean('email_notifications')->default(true)->after('monthly_completion_count');
            $table->boolean('push_notifications')->default(true)->after('email_notifications');
            $table->boolean('dark_mode')->default(false)->after('push_notifications');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['partner_id']);
            $table->dropColumn([
                'gender',
                'partner_id',
                'date_of_birth',
                'avatar',
                'timezone',
                'last_completed_questionnaire_at',
                'current_streak',
                'longest_streak',
                'monthly_completion_count',
                'email_notifications',
                'push_notifications',
                'dark_mode',
            ]);
        });
    }
};
