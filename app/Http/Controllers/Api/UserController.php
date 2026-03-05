<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Get the authenticated user's info for SSO clients.
     */
    public function show(Request $request)
    {
        $user = $request->user()->load(['role', 'organizationUnit']);

        return response()->json([
            'id'                => $user->id,
            'name'              => $user->name,
            'nik'               => $user->nik,
            'username'          => $user->username,
            'email'             => $user->email,
            'role'              => $user->role?->only(['id', 'name', 'display_name']),
            'organization_unit' => $user->organizationUnit?->only(['id', 'name']),
        ]);
    }
}
