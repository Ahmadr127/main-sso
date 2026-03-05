<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Client;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Deteksi nama kolom (Passport 13 = owner_id, lama = user_id)
        $columns = Client::getModel()->getConnection()
            ->getSchemaBuilder()->getColumnListing('oauth_clients');
        $ownerCol = in_array('owner_id', $columns) ? 'owner_id' : 'user_id';

        // Ambil semua first-party clients yang aktif untuk tombor SSO di dashboard
        $ssoClients = Client::whereNull($ownerCol)
            ->where('revoked', false)
            ->orderBy('name')
            ->get();

        return view('dashboard', compact('user', 'ssoClients'));
    }
}

