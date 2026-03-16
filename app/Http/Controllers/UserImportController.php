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
    public function showImportForm()
    {
        return view('users.import');
    }

    public function import(Request $request)
    {
        try {
            // Validate file
            $validated = $request->validate([
                'file' => 'required|file|mimes:xlsx,xls,csv|max:2048',
                'set_as_head' => 'nullable|boolean',
            ]);

            $file = $request->file('file');
            
            if (!$file) {
                return redirect()->route('users.import')
                    ->with('error', 'File tidak ditemukan');
            }

            $setAsHead = $request->boolean('set_as_head');

            DB::beginTransaction();

            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            array_shift($rows);

            $imported = 0;
            $errors = [];
            $staffPermissions = Permission::whereIn('name', ['view_dashboard'])->pluck('id')->toArray();

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;

                if (empty(array_filter($row))) {
                    continue;
                }

                try {
                    $nik = trim($row[1] ?? '');
                    $name = trim($row[2] ?? '');
                    $organizationName = trim($row[3] ?? '');
                    $position = trim($row[4] ?? '');
                    $roleName = trim($row[5] ?? 'staff');

                    if (!$nik || !$name) {
                        $errors[] = "Baris {$rowNumber}: NIK dan Nama wajib diisi";
                        continue;
                    }

                    // Find or create organization unit
                    $orgUnit = null;
                    if ($organizationName) {
                        $orgUnit = OrganizationUnit::where('name', $organizationName)->first();
                        
                        if (!$orgUnit) {
                            $orgType = \App\Models\OrganizationType::where('name', 'department')->first();
                            
                            if (!$orgType) {
                                $errors[] = "Baris {$rowNumber}: Organization Type 'department' tidak ditemukan";
                                continue;
                            }
                            
                            $baseCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $organizationName), 0, 10));
                            $code = $baseCode;
                            $counter = 1;
                            
                            while (OrganizationUnit::where('code', $code)->exists()) {
                                $code = $baseCode . $counter;
                                $counter++;
                            }
                            
                            $orgUnit = OrganizationUnit::create([
                                'name' => $organizationName,
                                'code' => $code,
                                'type_id' => $orgType->id,
                            ]);
                        }
                    }

                    // Find or create role
                    $roleSlug = strtolower(str_replace(' ', '_', $roleName));
                    $role = Role::where('name', $roleSlug)->first();
                    
                    if (!$role) {
                        $role = Role::create([
                            'name' => $roleSlug,
                            'display_name' => $roleName,
                            'description' => "Role {$roleName}",
                        ]);
                        
                        if (!empty($staffPermissions)) {
                            $role->permissions()->sync($staffPermissions);
                        }
                    }

                    // Generate username
                    $username = strtolower(str_replace(' ', '.', preg_replace('/[^A-Za-z0-9\s]/', '', $name)));
                    $baseUsername = $username;
                    $counter = 1;
                    
                    while (User::where('username', $username)->where('nik', '!=', $nik)->exists()) {
                        $username = $baseUsername . $counter;
                        $counter++;
                    }

                    // Generate email
                    $email = $username . '@azra.com';
                    $counter = 1;
                    while (User::where('email', $email)->where('nik', '!=', $nik)->exists()) {
                        $email = $baseUsername . $counter . '@azra.com';
                        $counter++;
                    }

                    // Create or update user
                    $user = User::where('nik', $nik)->first();

                    if ($user) {
                        $user->update([
                            'name' => $name,
                            'username' => $username,
                            'email' => $email,
                            'role_id' => $role->id,
                            'organization_unit_id' => $orgUnit ? $orgUnit->id : null,
                        ]);
                    } else {
                        $user = User::create([
                            'nik' => $nik,
                            'name' => $name,
                            'username' => $username,
                            'email' => $email,
                            'password' => Hash::make('password'),
                            'role_id' => $role->id,
                            'organization_unit_id' => $orgUnit ? $orgUnit->id : null,
                        ]);
                    }

                    // Set as organization head if toggle is on
                    if ($setAsHead && $orgUnit) {
                        $orgUnit->update(['head_id' => $user->id]);
                    }

                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Baris {$rowNumber}: " . $e->getMessage();
                }
            }

            DB::commit();

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
            DB::rollBack();
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
