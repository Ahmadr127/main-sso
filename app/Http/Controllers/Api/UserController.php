<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * GET /api/user
     *
     * Returns the authenticated user's profile for mobile & SSO clients.
     * Protected by `auth:api` (Laravel Passport Bearer token).
     *
     * Response:
     * {
     *   "id":               1,
     *   "name":             "Budi Santoso",
     *   "nik":              "1234567890",
     *   "username":         "budi.santoso",
     *   "email":            "budi@example.com",
     *   "role": {
     *     "id": 2,
     *     "name": "admin",
     *     "display_name": "Administrator"
     *   },
     *   "organization_unit": {
     *     "id": 3,
     *     "name": "Instalasi Farmasi",
     *     "type": "unit"
     *   }
     * }
     */
    public function show(Request $request)
    {
        $user = $request->user()->load(['role', 'organizationUnit.type']);

        return response()->json([
            'status' => 'success',
            'data'   => [
                'id'                => $user->id,
                'name'              => $user->name,
                'nik'               => $user->nik,
                'username'          => $user->username,
                'email'             => $user->email,
                'role'              => $user->role?->only(['id', 'name', 'display_name']),
                'organization_unit' => $user->organizationUnit
                    ? [
                        'id'   => $user->organizationUnit->id,
                        'name' => $user->organizationUnit->name,
                        'type' => $user->organizationUnit->type?->name ?? null,
                    ]
                    : null,
            ],
        ]);
    }
}
