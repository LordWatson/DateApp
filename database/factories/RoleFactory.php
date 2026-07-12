<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'name' => UserRole::Administrator->value,
            'label' => UserRole::Administrator->label(),
            'description' => 'Administrator role.',
        ];
    }

    public function superAdmin(): static
    {
        return $this->state([
            'name' => UserRole::SuperAdministrator->value,
            'label' => UserRole::SuperAdministrator->label(),
        ]);
    }

    public function support(): static
    {
        return $this->state([
            'name' => UserRole::Support->value,
            'label' => UserRole::Support->label(),
        ]);
    }

    public function moderator(): static
    {
        return $this->state([
            'name' => UserRole::Moderator->value,
            'label' => UserRole::Moderator->label(),
        ]);
    }
}
