<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OrganizationUnit;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class OrganizationUsersSeeder extends Seeder
{
    /**
     * Seed users based on organizational structure from image
     */
    public function run(): void
    {
        $this->command->info('📋 Creating Organization Users from Structure...');
        $this->command->newLine();

        // Create organization units first
        $orgUnits = $this->createOrganizationUnits();
        
        // Create users with their organization units
        $this->createUsers($orgUnits);

        $this->command->newLine();
        $this->command->info('✅ Organization users seeded successfully!');
    }

    private function createOrganizationUnits(): array
    {
        // Get department type (default type for all units)
        $departmentType = \App\Models\OrganizationType::where('name', 'department')->first();
        
        if (!$departmentType) {
            $this->command->error('OrganizationType "department" not found. Please run OrganizationTypeSeeder first.');
            return [];
        }

        $orgUnitsData = [
            ['name' => 'MUTU', 'code' => 'MUTU'],
            ['name' => 'PENUNJANG MEDIK', 'code' => 'PENMED'],
            ['name' => 'SDM', 'code' => 'SDM'],
            ['name' => 'DIREKTUR', 'code' => 'DIR'],
            ['name' => 'PT. ASP', 'code' => 'PTASP'],
            ['name' => 'PELAYANAN MEDIK', 'code' => 'PELMED'],
            ['name' => 'KEUANGAN', 'code' => 'KEU'],
            ['name' => 'IT', 'code' => 'IT'],
            ['name' => 'AKUNTANSI & PAJAK', 'code' => 'AKPAJ'],
            ['name' => 'LEGAL', 'code' => 'LEGAL'],
            ['name' => 'DIVISI KEPERAWATAN', 'code' => 'DIVKEP'],
            ['name' => 'SEKRETARIAT', 'code' => 'SEKR'],
            ['name' => 'UMUM', 'code' => 'UMUM'],
            ['name' => 'MARKETING', 'code' => 'MARK'],
        ];

        $orgUnits = [];
        foreach ($orgUnitsData as $data) {
            $orgUnit = OrganizationUnit::firstOrCreate(
                ['code' => $data['code']],
                [
                    'name' => $data['name'],
                    'type_id' => $departmentType->id,
                ]
            );
            $orgUnits[$data['code']] = $orgUnit;
            $this->command->info("  ✓ Organization Unit '{$orgUnit->name}' created/found");
        }

        return $orgUnits;
    }

    private function createUsers(array $orgUnits): void
    {
        // Get or create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['display_name' => 'Administrator']);
        $managerRole = Role::firstOrCreate(['name' => 'manager'], ['display_name' => 'Manager']);
        $directorRole = Role::firstOrCreate(['name' => 'direktur'], ['display_name' => 'Direktur']);
        $hospitalDirectorRole = Role::firstOrCreate(['name' => 'hospital_director'], ['display_name' => 'Hospital Director']);
        $presidentRole = Role::firstOrCreate(['name' => 'presiden_komisaris'], ['display_name' => 'Presiden Komisaris']);
        $headRole = Role::firstOrCreate(['name' => 'kepala'], ['display_name' => 'Kepala']);
        $staffRole = Role::firstOrCreate(['name' => 'staff'], ['display_name' => 'Staff']);

        $usersData = [
            ['nik' => '20141969', 'name' => 'DIENI ANANDA PUTRI, DR., MARS', 'username' => 'dieni.ananda', 'email' => 'dieni.ananda@azra.com', 'org_code' => 'MUTU', 'role' => $managerRole],
            ['nik' => '20061105', 'name' => 'GARCINIA SATIVA FIZRIA SETIADI, Dr, MKM', 'username' => 'garcinia.sativa', 'email' => 'garcinia.sativa@azra.com', 'org_code' => 'PENMED', 'role' => $managerRole],
            ['nik' => '20253017', 'name' => 'INDRA THALIB, B.SN., MM', 'username' => 'indra.thalib', 'email' => 'indra.thalib@azra.com', 'org_code' => 'SDM', 'role' => $managerRole],
            ['nik' => '20253030', 'name' => 'IRMA RISMAYANTI, dr, MM', 'username' => 'irma.rismayanti', 'email' => 'irma.rismayanti@azra.com', 'org_code' => 'DIR', 'role' => $hospitalDirectorRole],
            ['nik' => '19950015', 'name' => 'LAILA AZRA, DRA.', 'username' => 'laila.azra', 'email' => 'laila.azra@azra.com', 'org_code' => 'PTASP', 'role' => $presidentRole],
            ['nik' => '20253062', 'name' => 'LILI MARLIANI, DR., MARS', 'username' => 'lili.marliani', 'email' => 'lili.marliani@azra.com', 'org_code' => 'PELMED', 'role' => $managerRole],
            ['nik' => '20212767', 'name' => 'METRI JULIANTI, SE', 'username' => 'metri.julianti', 'email' => 'metri.julianti@azra.com', 'org_code' => 'KEU', 'role' => $managerRole],
            ['nik' => '20071107', 'name' => 'M. RANGGA ADITYA', 'username' => 'm.rangga', 'email' => 'm.rangga@azra.com', 'org_code' => 'PTASP', 'role' => $directorRole],
            ['nik' => '20242964', 'name' => 'MUHAMAD MIFTAHUDIN, M. KOM', 'username' => 'muhamad.miftahudin', 'email' => 'muhamad.miftahudin@azra.com', 'org_code' => 'IT', 'role' => $adminRole],
            ['nik' => '20242967', 'name' => 'RIA FAJARROHMI, SE', 'username' => 'ria.fajarrohmi', 'email' => 'ria.fajarrohmi@azra.com', 'org_code' => 'AKPAJ', 'role' => $headRole],
            ['nik' => '20111600', 'name' => 'RIYADI MAULANA, SH., MH., CLA., CCD', 'username' => 'riyadi.maulana', 'email' => 'riyadi.maulana@azra.com', 'org_code' => 'LEGAL', 'role' => $managerRole],
            ['nik' => '19940189', 'name' => 'SENI MAULIDA FITALOKA, S.Kep,Ns, M.Kep', 'username' => 'seni.maulida', 'email' => 'seni.maulida@azra.com', 'org_code' => 'DIVKEP', 'role' => $managerRole],
            ['nik' => '20020462', 'name' => 'SITI KHOIRIAH', 'username' => 'siti.khoiriah', 'email' => 'siti.khoiriah@azra.com', 'org_code' => 'SEKR', 'role' => $staffRole],
            ['nik' => '20253070', 'name' => 'THORIO FARIED ISHAQ, S.I. KOM', 'username' => 'thorio.faried', 'email' => 'thorio.faried@azra.com', 'org_code' => 'UMUM', 'role' => $managerRole],
            ['nik' => '20253008', 'name' => 'TUMPAS BANGKIT PRAYUDA, SE', 'username' => 'tumpas.bangkit', 'email' => 'tumpas.bangkit@azra.com', 'org_code' => 'MARK', 'role' => $managerRole],
            ['nik' => '20242988', 'name' => 'VERONIKA RINI HANDAYANI, A. MD', 'username' => 'veronika.rini', 'email' => 'veronika.rini@azra.com', 'org_code' => 'SEKR', 'role' => $staffRole],
            // Additional users
            ['nik' => '99999002', 'name' => 'Admin System', 'username' => 'admin', 'email' => 'admin@azra.com', 'org_code' => 'IT', 'role' => $adminRole],
        ];

        foreach ($usersData as $userData) {
            $orgCode = $userData['org_code'];
            $role = $userData['role'];
            
            unset($userData['org_code'], $userData['role']);

            // Create or update user
            $user = User::where('nik', $userData['nik'])
                ->orWhere('username', $userData['username'])
                ->orWhere('email', $userData['email'])
                ->first();

            if ($user) {
                // Update existing user
                $user->update([
                    'nik' => $userData['nik'],
                    'name' => $userData['name'],
                    'username' => $userData['username'],
                    'email' => $userData['email'],
                    'password' => Hash::make('password'),
                    'role_id' => $role->id,
                    'organization_unit_id' => $orgUnits[$orgCode]->id ?? null,
                ]);
            } else {
                // Create new user
                $user = User::create([
                    'nik' => $userData['nik'],
                    'name' => $userData['name'],
                    'username' => $userData['username'],
                    'email' => $userData['email'],
                    'password' => Hash::make('password'),
                    'role_id' => $role->id,
                    'organization_unit_id' => $orgUnits[$orgCode]->id ?? null,
                ]);
            }

            $this->command->info("  ✓ User '{$user->name}' created/updated");
        }
    }
}
