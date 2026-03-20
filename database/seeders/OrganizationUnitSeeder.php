<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OrganizationType;
use App\Models\OrganizationUnit;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OrganizationUnitSeeder extends Seeder
{
    public function run(): void
    {
        // Get types
        $holdingType = OrganizationType::where('name', 'holding')->first();
        $hospitalType = OrganizationType::where('name', 'hospital')->first();
        $directorateType = OrganizationType::where('name', 'directorate')->first();
        $departmentType = OrganizationType::where('name', 'department')->first();
        $unitType = OrganizationType::where('name', 'unit')->first();

        // Get or create manager role
        $managerRole = Role::firstOrCreate(
            ['name' => 'manager'],
            ['display_name' => 'Manager', 'description' => 'Manager unit organisasi']
        );
        
        $staffRole = Role::firstOrCreate(
            ['name' => 'staff'],
            ['display_name' => 'Staff', 'description' => 'Staff umum']
        );

        // NOTE: User creation is now handled by OrganizationUsersSeeder
        $this->command->info('User creation is now handled by OrganizationUsersSeeder');
    }

    /**
     * Helper function to create a user
     */
    private function createUser(string $name, string $username, string $email, int $roleId, int $unitId): User
    {
        return User::create([
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'password' => Hash::make('rsazra'),
            'role_id' => $roleId,
            'organization_unit_id' => $unitId,
        ]);
    }

    /**
     * Helper function to create user and unit together
     */
    private function createUserWithUnit(
        string $userName,
        string $username,
        string $email,
        string $unitName,
        string $unitCode,
        int $typeId,
        ?int $parentId,
        string $description,
        int $roleId
    ): array {
        // Create unit first
        $unit = OrganizationUnit::create([
            'name' => $unitName,
            'code' => $unitCode,
            'type_id' => $typeId,
            'parent_id' => $parentId,
            'description' => $description,
            'is_active' => true,
        ]);

        // Create user
        $user = User::create([
            'name' => $userName,
            'username' => $username,
            'email' => $email,
            'password' => Hash::make('rsazra'),
            'role_id' => $roleId,
            'organization_unit_id' => $unit->id,
        ]);

        // Set user as head
        $unit->update(['head_id' => $user->id]);

        return ['user' => $user, 'unit' => $unit];
    }
}
