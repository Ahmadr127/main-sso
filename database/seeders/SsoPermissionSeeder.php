<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class SsoPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Tambah permission manage_sso_clients jika belum ada
        $permission = Permission::firstOrCreate(
            ['name' => 'manage_sso_clients'],
            [
                'display_name' => 'Kelola SSO Clients',
                'description' => 'Mengelola aplikasi klien SSO yang terintegrasi',
            ]
        );

        // Assign permission ke role admin
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole && !$adminRole->permissions()->where('name', 'manage_sso_clients')->exists()) {
            $adminRole->permissions()->attach($permission);
            $this->command->info("Permission 'manage_sso_clients' assigned to admin role.");
        } else {
            $this->command->info("Permission 'manage_sso_clients' already assigned or admin role not found.");
        }
    }
}
