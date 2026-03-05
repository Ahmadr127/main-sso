# Panduan Integrasi SSO — untuk AI Agent

> **Berikan file ini ke AI** saat ingin mengintegrasikan Login SSO ke sistem Laravel klien.
> SSO Provider: `http://localhost` (ganti dengan URL production jika sudah deploy)

---

## Konteks

Sistem ini menggunakan **OAuth 2.0 Authorization Code Grant** via Laravel Passport 13.
- **SSO Provider (IdP):** `main-sso` — sudah berjalan dan menyediakan endpoint OAuth
- **Sistem Klien:** Aplikasi Laravel lain yang ingin menggunakan login SSO
- **Alur:** User login SEKALI di SSO → bisa masuk ke semua sistem klien tanpa login ulang

---

## Prasyarat

Sebelum mulai coding, **admin `main-sso` harus mendaftarkan client dulu** di:
- URL: `http://localhost/sso/clients/create`
- Isi **Nama Aplikasi** dan **Redirect URI** (contoh: `http://your-app.test/auth/sso/callback`)
- Setelah simpan, catat **Client ID** dan **Client Secret** yang muncul (secret hanya ditampilkan sekali)

---

## Langkah Implementasi

### 1. Konfigurasi `.env` Sistem Klien

Tambahkan ENV berikut ke file `.env`:

```env
# SSO Configuration
SSO_BASE_URL=http://localhost
SSO_CLIENT_ID=<client_id_dari_halaman_sso_clients>
SSO_CLIENT_SECRET=<client_secret_yang_muncul_saat_daftar>
SSO_REDIRECT_URI=http://your-app.test/auth/sso/callback

# WAJIB: beri nama unik agar session tidak bentrok dengan app lain di domain sama
SESSION_COOKIE=nama_app_session
```

---

### 2. Tambahkan Route di `routes/web.php`

Tambahkan 2 route berikut. **Jangan diubah logikanya**, hanya sesuaikan `redirect()->intended('/dashboard')` dengan halaman utama aplikasi.

```php
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\User;

// Route 1: Redirect user ke SSO untuk authorize
Route::get('/auth/sso/redirect', function (Request $request) {
    $state = Str::random(40);
    $request->session()->put('sso_state', $state);

    $query = http_build_query([
        'client_id'     => env('SSO_CLIENT_ID'),
        'redirect_uri'  => env('SSO_REDIRECT_URI'),
        'response_type' => 'code',
        'scope'         => '',
        'state'         => $state,
    ]);

    return redirect(env('SSO_BASE_URL') . '/oauth/authorize?' . $query);
})->middleware('web')->name('auth.sso.redirect');


// Route 2: Callback — terima auth code, tukar token, login lokal
Route::get('/auth/sso/callback', function (Request $request) {
    // a. Validasi state (CSRF protection)
    abort_if($request->state !== session('sso_state'), 419, 'Invalid SSO state.');

    // b. Tukar auth code dengan access token
    $tokenResponse = Http::post(env('SSO_BASE_URL') . '/oauth/token', [
        'grant_type'    => 'authorization_code',
        'client_id'     => env('SSO_CLIENT_ID'),
        'client_secret' => env('SSO_CLIENT_SECRET'),
        'redirect_uri'  => env('SSO_REDIRECT_URI'),
        'code'          => $request->code,
    ]);

    if (! $tokenResponse->successful()) {
        return redirect('/login')->withErrors([
            'sso' => 'Gagal mendapatkan token dari SSO: ' . $tokenResponse->json('message', 'Unknown error'),
        ]);
    }

    $accessToken = $tokenResponse->json('access_token');

    // c. Ambil data user dari SSO menggunakan access token
    $ssoUser = Http::withToken($accessToken)
        ->get(env('SSO_BASE_URL') . '/api/user')
        ->json();

    if (empty($ssoUser['email'])) {
        return redirect('/login')->withErrors(['sso' => 'Gagal mengambil data user dari SSO.']);
    }

    // d. Buat atau update user lokal berdasarkan email (atau NIK)
    // SESUAIKAN field-field ini dengan kolom tabel users di sistem klien
    $localUser = User::updateOrCreate(
        ['email' => $ssoUser['email']],
        [
            'name'     => $ssoUser['name'],
            'nik'      => $ssoUser['nik']      ?? null,  // jika ada kolom nik
            'username' => $ssoUser['username'] ?? null,  // jika ada kolom username
            'password' => bcrypt(Str::random(32)),        // password random, login via SSO
        ]
    );

    // e. Login lokal & regenerate session
    Auth::login($localUser, remember: true);
    $request->session()->regenerate();

    return redirect()->intended('/dashboard'); // SESUAIKAN dengan halaman utama app
})->middleware('web')->name('auth.sso.callback');
```

---

### 3. Tambahkan Tombol Login SSO di Halaman Login

Di view halaman login (biasanya `resources/views/auth/login.blade.php`), tambahkan tombol berikut **di dalam form atau di bawahnya**:

```blade
{{-- Divider --}}
<div class="relative my-4">
    <div class="absolute inset-0 flex items-center">
        <div class="w-full border-t border-gray-200"></div>
    </div>
    <div class="relative flex justify-center text-xs text-gray-400">
        <span class="px-2 bg-white">atau masuk dengan</span>
    </div>
</div>

{{-- Tombol SSO --}}
<a href="{{ route('auth.sso.redirect') }}"
   class="flex items-center justify-center gap-2 w-full px-4 py-2.5
          border border-green-600 text-green-700 rounded-lg
          hover:bg-green-50 transition-colors font-medium text-sm">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
    </svg>
    Login via SSO
</a>
```

---

### 4. Sinkronisasi Field User (PENTING — Baca Ini)

Data yang dikembalikan oleh `GET /api/user` dari SSO:

```json
{
  "id": 1,
  "name": "Ahmad Rizki",
  "nik": "3201234567890001",
  "username": "ahmad.rizki",
  "email": "ahmad@example.com",
  "role": {
    "id": 1,
    "name": "admin",
    "display_name": "Administrator"
  },
  "organization_unit": {
    "id": 2,
    "name": "Bagian Keuangan"
  }
}
```

**Aturan mapping:**
- Gunakan `email` sebagai key `updateOrCreate` (unique identifier)
- Jika sistem klien punya kolom `nik`, gunakan `nik` sebagai identifier utama (lebih stabil dari `id` SSO)
- `role` dan `organization_unit` adalah objek nested — map sesuai kebutuhan sistem klien
- **Jangan** simpan `id` SSO sebagai primary key lokal — buat mapping terpisah jika perlu

**Jika tabel `users` tidak punya kolom `nik` / `username`**, hapus field tersebut dari `updateOrCreate`:
```php
$localUser = User::updateOrCreate(
    ['email' => $ssoUser['email']],
    ['name' => $ssoUser['name'], 'password' => bcrypt(Str::random(32))]
);
```

**Jika perlu migrasi untuk menambah kolom**, buat migration:
```php
Schema::table('users', function (Blueprint $table) {
    $table->string('nik', 20)->nullable()->unique()->after('email');
    $table->string('username', 100)->nullable()->unique()->after('nik');
    $table->string('sso_id')->nullable()->after('username'); // opsional: simpan ID SSO
});
```

---

### 5. Menangani Error & Edge Cases

Tambahkan di view halaman login untuk menampilkan error SSO:

```blade
@error('sso')
    <div class="mt-2 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
        <i class="fas fa-exclamation-circle mr-1"></i> {{ $message }}
    </div>
@enderror
```

**Error umum yang mungkin muncul:**

| Error | Penyebab | Solusi |
|-------|----------|--------|
| `Invalid SSO state` | State CSRF tidak cocok | Periksa konfigurasi session, pastikan `SESSION_COOKIE` unik |
| `invalid_client` | Client ID/Secret salah | Cek `.env`, pastikan value benar dan tidak ada spasi/newline |
| `invalid_redirect_uri` | Redirect URI tidak cocok | Pastikan `SSO_REDIRECT_URI` di `.env` **identik** dengan yang didaftarkan di SSO Clients |
| User redirect ke profil SSO | Akun belum punya NIK/username | Admin SSO harus lengkapi profil user tersebut di `main-sso` |
| Session logout saling mempengaruhi | `SESSION_COOKIE` sama antar app | Set `SESSION_COOKIE` unik di `.env` masing-masing app |

---

## Referensi Endpoint SSO

Base URL: `http://localhost` (ganti sesuai environment)

| Method | Endpoint | Keterangan | Auth |
|--------|----------|------------|------|
| `GET` | `/oauth/authorize` | Halaman authorization / consent | Session web |
| `POST` | `/oauth/token` | Tukar auth code → access token | Client credentials |
| `GET` | `/api/user` | Ambil data user terautentikasi | Bearer token |
| `POST` | `/oauth/token/refresh` | Refresh access token | Client credentials |

---

## Checklist Verifikasi

Setelah implementasi, verifikasi hal berikut:

- [ ] `SESSION_COOKIE` unik sudah diset di `.env`
- [ ] `SSO_CLIENT_ID`, `SSO_CLIENT_SECRET`, `SSO_REDIRECT_URI` sudah diset di `.env`
- [ ] Route `/auth/sso/redirect` dan `/auth/sso/callback` ada di `web.php`
- [ ] Tombol Login SSO muncul di halaman login
- [ ] Klik tombol → redirect ke halaman login/consent SSO
- [ ] Setelah approve → redirect kembali ke sistem klien dan user berhasil login
- [ ] User yang sudah login ke SSO sebelumnya → langsung masuk tanpa login lagi
- [ ] `php artisan optimize:clear` sudah dijalankan setelah ubah `.env`

---

## Catatan Keamanan

1. **HTTPS di production** — Pastikan semua komunikasi menggunakan HTTPS
2. **Client Secret** — Jangan commit ke git, selalu gunakan `.env`
3. **State parameter** — Sudah diimplementasikan untuk mencegah CSRF pada OAuth flow
4. **Password random** — User yang login via SSO mendapat password random (tidak bisa login form biasa) — ini by design
5. **Syarat login SSO** — Akun di `main-sso` **harus memiliki NIK dan username** terisi, atau akan diredirect ke halaman profil SSO
