<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware untuk memastikan user memenuhi syarat SSO:
 * - Harus sudah login
 * - NIK harus terisi (tidak null / kosong)
 * - Username harus terisi (tidak null / kosong)
 *
 * Jika syarat tidak terpenuhi, user diarahkan ke halaman profil
 * dengan pesan peringatan untuk melengkapi data terlebih dahulu.
 */
class CheckSsoEligible
{
    public function handle(Request $request, Closure $next): Response
    {
        // Hanya aktif pada SSO authorize endpoint
        // (middleware ini juga di-attach ke web group sehingga berjalan di semua request)
        if (! $request->is('oauth/authorize')) {
            return $next($request);
        }

        $user = $request->user();

        if (!$user) {
            // Belum login — biarkan guard auth yang handle redirect ke login
            return $next($request);
        }

        // Cek NIK
        if (empty($user->nik)) {
            return redirect()->route('profile.index')
                ->with('error', 'Akun Anda belum memiliki NIK. Mohon lengkapi profil terlebih dahulu sebelum menggunakan SSO.');
        }

        // Cek Username
        if (empty($user->username)) {
            return redirect()->route('profile.index')
                ->with('error', 'Akun Anda belum memiliki Username. Mohon lengkapi profil terlebih dahulu sebelum menggunakan SSO.');
        }

        return $next($request);
    }
}
