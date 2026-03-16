<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call RolePermissionSeeder first
        $this->call([
            RolePermissionSeeder::class,
            OrganizationTypeSeeder::class,
            OrganizationUnitSeeder::class,
            OrganizationUsersSeeder::class, // Create organization users from structure
        ]);

        // NOTE: Admin user creation is now handled by OrganizationUsersSeeder
        $this->command->info('Admin user creation is now handled by OrganizationUsersSeeder');
    }
}
