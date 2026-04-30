<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\OrganizationUnit;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class UserImportController extends Controller
{
    /**
     * Extract first and last name only (without middle names and titles)
     * Takes text before comma, then extracts first and last word
     */
    private function extractNameWithoutTitle($fullName)
    {
        // Get text before the first comma
        $parts = explode(',', $fullName);
        $name = trim($parts[0]);
        
        // Split by spaces and get first and last word
        $words = array_filter(explode(' ', $name));
        $words = array_values($words); // Re-index array
        
        if (count($words) == 0) {
            return '';
        } elseif (count($words) == 1) {
            return $words[0];
        } else {
            // Return first and last name only
            return $words[0] . ' ' . $words[1];
        }
    }

    public function showImportForm()
    {
        return view('users.import');
    }

    public function import(Request $request)
    {
        // Prevent PHP from timing out during large imports
        set_time_limit(0);
        ini_set('memory_limit', '256M');

        try {
            // Validate file
            $validated = $request->validate([
                'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
                'set_as_head' => 'nullable|boolean',
            ]);

            $file = $request->file('file');
            
            if (!$file) {
                return redirect()->route('users.import')
                    ->with('error', 'File tidak ditemukan');
            }

            $setAsHead = $request->boolean('set_as_head');

            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            array_shift($rows); // Remove header row

            $imported = 0;
            $errors = [];

            // --- Pre-load lookups into memory to minimize per-row DB queries ---
            $managerRole = Role::where('name', 'manager')->first();
            $managerPermissions = $managerRole
                ? $managerRole->permissions()->pluck('permissions.id')->toArray()
                : Permission::whereIn('name', ['view_dashboard'])->pluck('id')->toArray();

            // Cache: orgUnit name -> model, role slug -> model
            $orgUnitCache = OrganizationUnit::all()->keyBy('name')->toArray();
            $roleCache    = Role::all()->keyBy('name')->toArray();

            $orgType = \App\Models\OrganizationType::where('name', 'department')->first();

            // PostgreSQL: wrap each row in its own transaction
            // so one failed row does NOT abort subsequent rows (unlike MySQL)
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;

                if (empty(array_filter($row))) {
                    continue;
                }

                DB::beginTransaction();
                try {
                    $nik              = trim($row[1] ?? '');
                    $name             = trim($row[2] ?? '');
                    $organizationName = trim($row[3] ?? '');
                    $roleName         = trim($row[5] ?? 'staff');

                    if (!$nik || !$name) {
                        DB::rollBack();
                        $errors[] = "Baris {$rowNumber}: NIK dan Nama wajib diisi";
                        continue;
                    }

                    // --- Organization Unit (cached, safe against duplicate codes) ---
                    $orgUnit = null;
                    if ($organizationName) {
                        if (isset($orgUnitCache[$organizationName])) {
                            // Already cached — just find by ID
                            $orgUnit = OrganizationUnit::find($orgUnitCache[$organizationName]['id']);
                        } else {
                            if (!$orgType) {
                                DB::rollBack();
                                $errors[] = "Baris {$rowNumber}: Organization Type 'department' tidak ditemukan";
                                continue;
                            }

                            // Generate unique code
                            $baseCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $organizationName), 0, 10));
                            $code     = $baseCode;
                            $counter  = 1;
                            while (OrganizationUnit::where('code', $code)->exists()) {
                                $code = $baseCode . $counter++;
                            }

                            // Use firstOrCreate by name to avoid duplicate inserts
                            $orgUnit = OrganizationUnit::firstOrCreate(
                                ['name' => $organizationName],
                                ['code' => $code, 'type_id' => $orgType->id]
                            );

                            // Update cache
                            $orgUnitCache[$organizationName] = $orgUnit->toArray();
                        }
                    }

                    // --- Role (cached) ---
                    $roleSlug = strtolower(str_replace(' ', '_', $roleName));
                    if (isset($roleCache[$roleSlug])) {
                        $role = Role::find($roleCache[$roleSlug]['id']);
                    } else {
                        $role = Role::firstOrCreate(
                            ['name' => $roleSlug],
                            ['display_name' => $roleName, 'description' => "Role {$roleName}"]
                        );
                        if ($role->wasRecentlyCreated && !empty($managerPermissions)) {
                            $role->permissions()->sync($managerPermissions);
                        }
                        $roleCache[$roleSlug] = $role->toArray();
                    }

                    // --- Username (unique) ---
                    $nameWithoutTitle = $this->extractNameWithoutTitle($name);
                    $baseUsername = strtolower(str_replace(' ', '.', preg_replace('/[^A-Za-z0-9\s]/', '', $nameWithoutTitle)));
                    $username = $baseUsername;
                    $counter  = 1;
                    while (User::where('username', $username)->where('nik', '!=', $nik)->exists()) {
                        $username = $baseUsername . $counter++;
                    }

                    // --- Email (unique) ---
                    $email   = $username . '@azra.com';
                    $counter = 1;
                    while (User::where('email', $email)->where('nik', '!=', $nik)->exists()) {
                        $email = $baseUsername . $counter++ . '@azra.com';
                    }

                    // --- Create or Update User ---
                    $user = User::where('nik', $nik)->first();
                    if ($user) {
                        $user->update([
                            'name'                 => $name,
                            'username'             => $username,
                            'email'                => $email,
                            'role_id'              => $role->id,
                            'organization_unit_id' => $orgUnit ? $orgUnit->id : null,
                        ]);
                    } else {
                        $user = User::create([
                            'nik'                  => $nik,
                            'name'                 => $name,
                            'username'             => $username,
                            'email'                => $email,
                            'password'             => Hash::make('rsazra'),
                            'role_id'              => $role->id,
                            'organization_unit_id' => $orgUnit ? $orgUnit->id : null,
                        ]);
                    }

                    if ($setAsHead && $orgUnit) {
                        $orgUnit->update(['head_id' => $user->id]);
                    }

                    DB::commit();
                    $imported++;
                } catch (\Exception $e) {
                    DB::rollBack();
                    $errors[] = "Baris {$rowNumber}: " . $e->getMessage();
                }
            }

            if (!empty($errors)) {
                return redirect()->route('users.import')
                    ->with('warning', "Import selesai dengan {$imported} user berhasil. Beberapa error: " . implode('; ', array_slice($errors, 0, 5)));
            }

            return redirect()->route('users.index')
                ->with('success', "Berhasil mengimport {$imported} user");

        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('users.import')
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            return redirect()->route('users.import')
                ->with('error', 'Gagal mengimport file: ' . $e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = ['NO', 'NIP', 'Nama Karyawan', 'Organisasi', 'Posisi Pekerjaan', 'Jabatan'];
        $sheet->fromArray($headers, null, 'A1');

        $sampleData = [
            [1, '20141969', 'DIENI ANANDA PUTRI, DR., MARS', 'MUTU', 'MANAGER MUTU', 'MANAGER'],
            [2, '20061105', 'GARCINIA SATIVA FIZRIA SETIADI, Dr, MKM', 'PENUNJANG MEDIK', 'MANAGER PENUNJANG MEDIK', 'MANAGER'],
            [3, '20253017', 'INDRA THALIB, B.SN., MM', 'SDM', 'MANAGER SDM', 'MANAGER'],
        ];
        $sheet->fromArray($sampleData, null, 'A2');

        $headerStyle = $sheet->getStyle('A1:F1');
        $headerStyle->getFont()->setBold(true);
        $headerStyle->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF4472C4');
        $headerStyle->getFont()->getColor()->setARGB('FFFFFFFF');

        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        
        $filename = 'template_import_users.xlsx';
        $temp_file = tempnam(sys_get_temp_dir(), $filename);
        $writer->save($temp_file);

        return response()->download($temp_file, $filename)->deleteFileAfterSend(true);
    }
}

