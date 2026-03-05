@extends('layouts.app')

@section('title', 'Panduan Integrasi SSO')

@section('content')
<div class="max-w-5xl mx-auto py-6 space-y-6">

    {{-- Header --}}
    <div class="bg-gradient-to-r from-green-600 to-green-700 rounded-2xl px-8 py-6 text-white shadow-lg">
        <div class="flex items-start justify-between">
            <div>
                <h1 class="text-2xl font-bold mb-1">📘 Panduan Integrasi SSO</h1>
                <p class="text-green-100 text-sm">Panduan lengkap untuk developer sistem klien yang ingin terintegrasi dengan SSO ini.</p>
            </div>
            <div class="text-right text-xs text-green-200">
                <div>SSO Provider: <strong class="text-white">{{ config('app.name') }}</strong></div>
                <div class="mt-1">Base URL: <code class="bg-green-800 px-1.5 py-0.5 rounded">{{ config('app.url') }}</code></div>
            </div>
        </div>
    </div>

    {{-- Alur SSO --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
            <h2 class="font-bold text-gray-800 text-lg flex items-center">
                <span class="w-7 h-7 bg-green-600 text-white rounded-lg flex items-center justify-center text-sm font-bold mr-3">1</span>
                Alur SSO (Authorization Code Grant)
            </h2>
        </div>
        <div class="p-6">
            <div class="flex flex-col md:flex-row items-center gap-2 text-sm text-center overflow-x-auto pb-2">
                @php
                    $steps = [
                        ['🖥️', 'Sistem Klien', 'User klik\nLogin via SSO'],
                        ['🔀', null, '→'],
                        ['🔐', 'SSO (main-sso)', 'Login (jika belum)\nKlik Izinkan'],
                        ['🔀', null, '→'],
                        ['🖥️', 'Sistem Klien', 'Terima auth_code\ndari callback'],
                        ['🔀', null, '→'],
                        ['🔐', 'SSO /oauth/token', 'Tukar code\n→ access_token'],
                        ['🔀', null, '→'],
                        ['🔐', 'SSO /api/user', 'Ambil data\nuser lengkap'],
                        ['🔀', null, '→'],
                        ['✅', 'Sistem Klien', 'User login\nlokal berhasil'],
                    ];
                @endphp
                @foreach($steps as $step)
                    @if($step[1] === null)
                        <div class="text-gray-400 text-xl flex-shrink-0">→</div>
                    @else
                        <div class="flex-shrink-0 bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 min-w-[120px]">
                            <div class="text-2xl">{{ $step[0] }}</div>
                            <div class="font-semibold text-gray-700 text-xs mt-1">{{ $step[1] }}</div>
                            <div class="text-gray-400 text-xs mt-0.5 whitespace-pre-line leading-4">{{ $step[2] }}</div>
                        </div>
                    @endif
                @endforeach
            </div>
            <div class="mt-4 bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm text-blue-700">
                <i class="fas fa-info-circle mr-1"></i>
                <strong>Penting:</strong> Session user di <strong>{{ config('app.name') }}</strong> tidak pernah logout saat SSO. User hanya login <strong>sekali</strong> di SSO, kemudian semua sistem klien bisa diakses tanpa login ulang.
            </div>
        </div>
    </div>

    {{-- Langkah 1: Daftar Client --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
            <h2 class="font-bold text-gray-800 text-lg flex items-center">
                <span class="w-7 h-7 bg-green-600 text-white rounded-lg flex items-center justify-center text-sm font-bold mr-3">2</span>
                Prasyarat: Daftarkan Sistem Klien
            </h2>
        </div>
        <div class="p-6 space-y-4">
            <p class="text-sm text-gray-600">Sebelum integrasi, admin <strong>{{ config('app.name') }}</strong> harus mendaftarkan sistem klien terlebih dahulu di
                <a href="{{ route('sso.clients.index') }}" class="text-green-600 underline font-medium">SSO Clients</a>.
            </p>
            <p class="text-sm text-gray-600">Setelah terdaftar, catat <strong>Client ID</strong> dan <strong>Client Secret</strong>, lalu tambahkan ke file <code class="bg-gray-100 px-1 rounded">.env</code> sistem klien:</p>

            <pre class="bg-gray-900 text-green-300 rounded-xl p-4 text-sm overflow-x-auto leading-relaxed"><code>SSO_BASE_URL="{{ config('app.url') }}"
SSO_CLIENT_ID=&lt;client_id_dari_tabel&gt;
SSO_CLIENT_SECRET=&lt;client_secret_dari_saat_daftar&gt;
SSO_REDIRECT_URI=https://sistem-klien-anda.com/auth/sso/callback</code></pre>

            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-sm text-yellow-700">
                <i class="fas fa-key mr-1"></i>
                Client Secret <strong>hanya ditampilkan sekali</strong> saat client dibuat. Simpan dengan aman!
            </div>
        </div>
    </div>

    {{-- Langkah 2: Kode Implementasi Laravel --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
            <h2 class="font-bold text-gray-800 text-lg flex items-center">
                <span class="w-7 h-7 bg-green-600 text-white rounded-lg flex items-center justify-center text-sm font-bold mr-3">3</span>
                Implementasi di Sistem Klien (Laravel)
            </h2>
            <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full font-medium">Laravel 10/11/12</span>
        </div>
        <div class="p-6 space-y-5">

            {{-- Syarat Akun --}}
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-sm text-red-700">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                <strong>Syarat Login SSO:</strong> Akun user di <strong>{{ config('app.name') }}</strong> harus memiliki <code>NIK</code> dan <code>username</code> yang terisi.
                Jika belum, user akan diarahkan ke halaman profil untuk melengkapinya.
            </div>

            {{-- routes/web.php di klien --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-2">📄 <code>routes/web.php</code> di sistem klien:</h3>
                <pre class="bg-gray-900 text-gray-100 rounded-xl p-4 text-xs overflow-x-auto leading-relaxed"><code>&lt;?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\User;

// Langkah 1: Redirect user ke SSO untuk login / authorize
Route::get('/auth/sso/redirect', function (Request $request) {
    $state = Str::random(40);
    $request->session()->put('state', $state);

    $query = http_build_query([
        'client_id'     => env('SSO_CLIENT_ID'),
        'redirect_uri'  => env('SSO_REDIRECT_URI'),
        'response_type' => 'code',
        'scope'         => '',
        'state'         => $state,
    ]);

    return redirect(env('SSO_BASE_URL') . '/oauth/authorize?' . $query);
})->middleware('web')->name('auth.sso.redirect');

// Langkah 2: Terima auth code dari SSO, tukar dengan token, login lokal
Route::get('/auth/sso/callback', function (Request $request) {
    // a. Validasi CSRF state
    abort_if($request->state !== session('state'), 419, 'Invalid state');

    // b. Tukar auth code dengan access token
    $response = Http::post(env('SSO_BASE_URL') . '/oauth/token', [
        'grant_type'    => 'authorization_code',
        'client_id'     => env('SSO_CLIENT_ID'),
        'client_secret' => env('SSO_CLIENT_SECRET'),
        'redirect_uri'  => env('SSO_REDIRECT_URI'),
        'code'          => $request->code,
    ]);

    if (!$response->successful()) {
        return redirect('/login')->withErrors(['sso' => 'Gagal mendapatkan token dari SSO.']);
    }

    $accessToken = $response->json('access_token');

    // c. Ambil data user dari SSO
    $ssoUser = Http::withToken($accessToken)
        ->get(env('SSO_BASE_URL') . '/api/user')
        ->json();

    // d. Buat atau update user lokal
    $localUser = User::updateOrCreate(
        ['email' => $ssoUser['email']],
        [
            'name'     => $ssoUser['name'],
            'nik'      => $ssoUser['nik'],
            'username' => $ssoUser['username'],
            'password' => bcrypt(Str::random(32)), // password random — login via SSO
        ]
    );

    // e. Login lokal
    Auth::login($localUser, true);
    $request->session()->regenerate();

    return redirect()->intended('/dashboard');
})->middleware('web')->name('auth.sso.callback');</code></pre>
            </div>

            {{-- Tombol Login di klien --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-2">🎨 Tombol Login SSO di halaman login sistem klien:</h3>
                <pre class="bg-gray-900 text-gray-100 rounded-xl p-4 text-xs overflow-x-auto leading-relaxed"><code>@verbatim{{-- resources/views/auth/login.blade.php --}}
&lt;a href="{{ route('auth.sso.redirect') }}"
   class="flex items-center justify-center gap-2 w-full px-4 py-2.5 border border-green-600 text-green-700 rounded-lg hover:bg-green-50 transition-colors font-medium"&gt;
    &lt;img src="https://ui-avatars.com/api/?name=SSO&amp;background=16a34a&amp;color=fff&amp;size=24" class="w-5 h-5 rounded" alt="SSO"&gt;
    Login via SSO (nama-aplikasi-sso)
&lt;/a&gt;@endverbatim</code></pre>
            </div>

        </div>
    </div>

    {{-- Response /api/user --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
            <h2 class="font-bold text-gray-800 text-lg flex items-center">
                <span class="w-7 h-7 bg-green-600 text-white rounded-lg flex items-center justify-center text-sm font-bold mr-3">4</span>
                Response Data User dari <code class="text-base font-mono">GET /api/user</code>
            </h2>
        </div>
        <div class="p-6 grid md:grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-gray-600 mb-3">Endpoint ini mengembalikan data user yang terautentikasi via Bearer token:</p>
                <pre class="bg-gray-900 text-green-300 rounded-xl p-4 text-xs overflow-x-auto leading-relaxed"><code>{
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
}</code></pre>
            </div>
            <div class="space-y-3">
                <h3 class="text-sm font-semibold text-gray-700">Field yang tersedia:</h3>
                <table class="text-xs w-full">
                    <thead><tr class="bg-gray-50"><th class="text-left p-2 rounded-tl-lg border">Field</th><th class="text-left p-2 rounded-tr-lg border">Keterangan</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr><td class="p-2 font-mono border-x"><code>id</code></td><td class="p-2 border-x text-gray-600">ID user di SSO</td></tr>
                        <tr><td class="p-2 font-mono border-x"><code>name</code></td><td class="p-2 border-x text-gray-600">Nama lengkap</td></tr>
                        <tr><td class="p-2 font-mono border-x"><code>nik</code></td><td class="p-2 border-x text-gray-600">NIK (identifier utama)</td></tr>
                        <tr><td class="p-2 font-mono border-x"><code>username</code></td><td class="p-2 border-x text-gray-600">Username login</td></tr>
                        <tr><td class="p-2 font-mono border-x"><code>email</code></td><td class="p-2 border-x text-gray-600">Email (untuk updateOrCreate)</td></tr>
                        <tr><td class="p-2 font-mono border-x"><code>role</code></td><td class="p-2 border-x text-gray-600">Role di SSO (id, name, display_name)</td></tr>
                        <tr><td class="p-2 font-mono border-x border-b rounded-b"><code>organization_unit</code></td><td class="p-2 border-x border-b text-gray-600">Unit organisasi (id, name)</td></tr>
                    </tbody>
                </table>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-xs text-blue-700">
                    <i class="fas fa-lightbulb mr-1"></i>
                    Rekomendasi: gunakan <code>nik</code> sebagai primary identifier di sistem klien, bukan <code>id</code> SSO.
                </div>
            </div>
        </div>
    </div>

    {{-- Endpoint Referensi --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
            <h2 class="font-bold text-gray-800 text-lg flex items-center">
                <span class="w-7 h-7 bg-green-600 text-white rounded-lg flex items-center justify-center text-sm font-bold mr-3">5</span>
                Referensi Endpoint SSO
            </h2>
        </div>
        <div class="p-6 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500 uppercase border">Method</th>
                        <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500 uppercase border">Endpoint</th>
                        <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500 uppercase border">Keterangan</th>
                        <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500 uppercase border">Auth</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach([
                        ['GET', '/oauth/authorize', 'Halaman consent / authorization screen', 'Session (harus login di SSO)'],
                        ['POST', '/oauth/token', 'Tukar auth code dengan access token', 'Client credentials (basic)'],
                        ['POST', '/oauth/token/refresh', 'Refresh access token', 'Client credentials + refresh token'],
                        ['GET', '/api/user', 'Ambil data user terautentikasi', 'Bearer access token'],
                    ] as [$method, $path, $desc, $auth])
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 border">
                            <span class="px-2 py-0.5 rounded text-xs font-bold
                                {{ $method === 'GET' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700' }}">
                                {{ $method }}
                            </span>
                        </td>
                        <td class="px-4 py-3 border font-mono text-xs">
                            <span class="text-gray-400">{{ config('app.url') }}</span>{{ $path }}
                        </td>
                        <td class="px-4 py-3 border text-gray-600 text-xs">{{ $desc }}</td>
                        <td class="px-4 py-3 border text-xs text-gray-500">{{ $auth }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- FAQ / Troubleshooting --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
            <h2 class="font-bold text-gray-800 text-lg flex items-center">
                <span class="w-7 h-7 bg-green-600 text-white rounded-lg flex items-center justify-center text-sm font-bold mr-3">6</span>
                Troubleshooting & FAQ
            </h2>
        </div>
        <div class="p-6 space-y-4" x-data="{ open: null }">
            @php
            $faqs = [
                [
                    'q' => 'User diarahkan ke halaman Profil bukan ke sistem klien?',
                    'a' => 'Artinya akun user belum memiliki NIK atau username. Akun harus dilengkapi oleh admin di sistem SSO ini sebelum bisa login via SSO.',
                ],
                [
                    'q' => 'Error "Invalid state" saat callback?',
                    'a' => 'State CSRF tidak cocok. Pastikan sistem klien menyimpan state di session dan membandingkannya dengan benar. Periksa konfigurasi session di sistem klien.',
                ],
                [
                    'q' => 'Gagal tukar token (error 401 / invalid_client)?',
                    'a' => 'Periksa: (1) CLIENT_ID dan CLIENT_SECRET benar, (2) REDIRECT_URI di .env cocok persis dengan yang didaftarkan di SSO Clients, (3) Client belum dihapus/dicabut.',
                ],
                [
                    'q' => 'Apakah user bisa langsung masuk tanpa consent screen?',
                    'a' => 'Ya. Passport otomatis skip consent screen untuk client yang sama jika user sudah pernah approve. Untuk first-party client, bisa set skip_authorization = true di tabel oauth_clients.',
                ],
                [
                    'q' => 'Bagaimana cara logout dari semua sistem sekaligus?',
                    'a' => 'Saat ini setiap sistem memiliki session sendiri. Untuk global logout, setiap sistem klien perlu menghapus token dari Passport via DELETE /oauth/tokens/{id} dan menghapus session lokal.',
                ],
            ];
            @endphp

            @foreach($faqs as $i => $faq)
            <div class="border border-gray-200 rounded-xl overflow-hidden" x-data="{ open: false }">
                <button @click="open = !open" class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-gray-50 transition-colors">
                    <span class="text-sm font-medium text-gray-700">
                        <i class="fas fa-question-circle text-green-500 mr-2"></i>
                        {{ $faq['q'] }}
                    </span>
                    <i class="fas fa-chevron-down text-gray-400 text-xs transition-transform duration-200 flex-shrink-0 ml-3" :class="{ 'rotate-180': open }"></i>
                </button>
                <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                     class="px-5 pb-4 text-sm text-gray-600 border-t border-gray-100 bg-gray-50 pt-3">
                    {{ $faq['a'] }}
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Tombol navigasi --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('sso.clients.index') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors shadow-sm">
            <i class="fas fa-plug mr-2"></i> SSO Clients
        </a>
        <span class="text-xs text-gray-400">Panduan Integrasi SSO — {{ config('app.name') }}</span>
    </div>

</div>
@endsection
