<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;

class SsoClientController extends Controller
{
    protected ClientRepository $clients;

    public function __construct(ClientRepository $clients)
    {
        $this->clients = $clients;
    }

    /**
     * Tampilkan semua SSO clients yang terdaftar.
     * Passport 13: kolom 'user_id' diganti menjadi 'owner_id'.
     */
    public function index()
    {
        $columns = Client::getModel()->getConnection()
            ->getSchemaBuilder()->getColumnListing('oauth_clients');

        // Kompatibel dengan Passport lama (user_id) dan baru (owner_id)
        $ownerColumn = in_array('owner_id', $columns) ? 'owner_id' : 'user_id';

        $clients = Client::whereNull($ownerColumn)
            ->where('revoked', false)
            ->orderByDesc('created_at')
            ->get();

        return view('sso.clients.index', compact('clients'));
    }

    /**
     * Form untuk membuat SSO client baru.
     */
    public function create()
    {
        return view('sso.clients.create');
    }

    /**
     * Simpan SSO client baru ke database.
     * Passport 13: gunakan createAuthorizationCodeGrantClient() bukan create() yang protected.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'redirect'     => 'required|url',
            'confidential' => 'sometimes|boolean',
        ]);

        // createAuthorizationCodeGrantClient adalah public method di Passport 13
        $client = $this->clients->createAuthorizationCodeGrantClient(
            name: $request->name,
            redirectUris: [$request->redirect],
            confidential: $request->boolean('confidential', true),
        );

        $secret = $client->plainSecret ?? '(tidak tersedia — hash sudah disimpan)';

        return redirect()->route('sso.clients.index')
            ->with('success', "Client \"{$client->name}\" berhasil dibuat. Client ID: {$client->id} | Secret: {$secret}");
    }

    /**
     * Hapus SSO client (revoke semua token terkait).
     */
    public function destroy(string $clientId)
    {
        $client = Client::findOrFail($clientId);

        // Revoke semua token terkait, lalu hapus client
        $client->tokens()->each(function ($token) {
            $token->refreshToken?->forceFill(['revoked' => true])->save();
            $token->forceFill(['revoked' => true])->save();
        });

        $client->delete();

        return redirect()->route('sso.clients.index')
            ->with('success', "Client \"{$client->name}\" berhasil dihapus.");
    }
}
