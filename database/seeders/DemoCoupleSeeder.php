<?php

namespace Database\Seeders;

use App\Enums\Gender;
use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * A connected couple with a well-known password, one of them a super
 * administrator. For local development only: DatabaseSeeder never calls this
 * in production, and it refuses to run there if called directly.
 */
class DemoCoupleSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('DemoCoupleSeeder creates accounts with a known password and must not run in production.');
        }

        $alex = User::firstOrCreate(['email' => 'squatty.watson@gmail.com'], [
            'name' => 'Alex',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'gender' => Gender::Male,
            'date_of_birth' => '1993-10-16',
            'timezone' => 'UTC',
            'email_notifications' => true,
            'push_notifications' => true,
            'dark_mode' => false,
        ]);

        $rimma = User::firstOrCreate(['email' => 'alexander.watson.work@gmail.com'], [
            'name' => 'Rimma',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'gender' => Gender::Female,
            'date_of_birth' => '1992-03-22',
            'timezone' => 'UTC',
            'email_notifications' => true,
            'push_notifications' => true,
            'dark_mode' => false,
        ]);

        $superAdminRole = Role::where('name', UserRole::SuperAdministrator->value)->first();

        $alex->update(['partner_id' => $rimma->id, 'role_id' => $superAdminRole?->id, 'onboarding_completed' => true]);
        $rimma->update(['partner_id' => $alex->id, 'onboarding_completed' => true]);
    }
}
