<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Create a default admin login if it does not already exist.
     */
    public function run(): void
    {
        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrator',
                'password' => 'password',
                'role' => User::ROLE_SUPER_ADMIN,
            ]
        );

        // Ensure an existing admin is promoted without touching their password.
        if (! $admin->isSuperAdmin()) {
            $admin->update(['role' => User::ROLE_SUPER_ADMIN]);
        }
    }
}
