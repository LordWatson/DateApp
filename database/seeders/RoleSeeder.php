<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => UserRole::SuperAdministrator->value,
                'label' => UserRole::SuperAdministrator->label(),
                'description' => 'Full access to all admin features and settings.',
            ],
            [
                'name' => UserRole::Administrator->value,
                'label' => UserRole::Administrator->label(),
                'description' => 'Manage users, content, and most admin features.',
            ],
            [
                'name' => UserRole::Support->value,
                'label' => UserRole::Support->label(),
                'description' => 'View users and analytics. Handle support requests.',
            ],
            [
                'name' => UserRole::Moderator->value,
                'label' => UserRole::Moderator->label(),
                'description' => 'Manage content, challenges, and questionnaires.',
            ],
            [
                'name' => UserRole::User->value,
                'label' => UserRole::User->label(),
                'description' => 'Standard application user.',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['name' => $role['name']], $role);
        }
    }
}
