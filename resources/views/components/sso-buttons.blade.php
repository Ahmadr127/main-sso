@props(['clients'])

@php
    use Illuminate\Support\Str;
    // Palet warna bergantian untuk card tiap client
    $palettes = [
        ['bg' => 'bg-indigo-50', 'hover' => 'hover:bg-indigo-100', 'icon_bg' => 'bg-indigo-600', 'hover_icon' => 'group-hover:bg-indigo-700', 'text' => 'text-indigo-900', 'sub' => 'text-indigo-600'],
        ['bg' => 'bg-emerald-50', 'hover' => 'hover:bg-emerald-100', 'icon_bg' => 'bg-emerald-600', 'hover_icon' => 'group-hover:bg-emerald-700', 'text' => 'text-emerald-900', 'sub' => 'text-emerald-600'],
        ['bg' => 'bg-sky-50', 'hover' => 'hover:bg-sky-100', 'icon_bg' => 'bg-sky-600', 'hover_icon' => 'group-hover:bg-sky-700', 'text' => 'text-sky-900', 'sub' => 'text-sky-600'],
        ['bg' => 'bg-violet-50', 'hover' => 'hover:bg-violet-100', 'icon_bg' => 'bg-violet-600', 'hover_icon' => 'group-hover:bg-violet-700', 'text' => 'text-violet-900', 'sub' => 'text-violet-600'],
        ['bg' => 'bg-amber-50', 'hover' => 'hover:bg-amber-100', 'icon_bg' => 'bg-amber-600', 'hover_icon' => 'group-hover:bg-amber-700', 'text' => 'text-amber-900', 'sub' => 'text-amber-600'],
        ['bg' => 'bg-rose-50', 'hover' => 'hover:bg-rose-100', 'icon_bg' => 'bg-rose-600', 'hover_icon' => 'group-hover:bg-rose-700', 'text' => 'text-rose-900', 'sub' => 'text-rose-600'],
    ];
@endphp

@if($clients->isNotEmpty())
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                    <span class="w-7 h-7 bg-green-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-th-large text-white text-xs"></i>
                    </span>
                    Aplikasi Terintegrasi
                </h3>
                <p class="text-sm text-gray-500 mt-0.5">Klik untuk masuk langsung — tanpa login ulang</p>
            </div>
            <span class="text-xs bg-green-100 text-green-700 px-2.5 py-1 rounded-full font-medium">
                {{ $clients->count() }} Sistem
            </span>
        </div>

        {{-- Grid tombol per client --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($clients as $i => $client)
                @php
                    $p = $palettes[$i % count($palettes)];

                    // Ambil redirect URI pertama dari client
                    $redirectUri = is_array($client->redirect_uris) && count($client->redirect_uris) > 0
                        ? $client->redirect_uris[0]
                        : ($client->redirect ?? null);

                    // Bangun URL callback sistem klien (asumsi /auth/sso/callback)
                    // Jika redirect_uris sudah setting ke callback URL maka langsung pakai
                    $callbackUrl = $redirectUri;

                    // Bangun authorize URL — user sudah login, Passport akan auto-redirect
                    $state = Str::random(20);
                    $authorizeUrl = url('/oauth/authorize') . '?' . http_build_query([
                        'client_id'     => $client->id,
                        'redirect_uri'  => $callbackUrl,
                        'response_type' => 'code',
                        'scope'         => '',
                    ]);

                    // Infer nama domain dari redirect untuk label
                    $parsedHost = $callbackUrl ? parse_url($callbackUrl, PHP_URL_HOST) : null;
                    $displayHost = $parsedHost ?? '-';
                @endphp

                <a href="{{ $authorizeUrl }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   title="Masuk ke {{ $client->name }}"
                   class="flex items-center p-4 {{ $p['bg'] }} {{ $p['hover'] }} rounded-xl transition-all duration-150 group border border-transparent hover:border-gray-200 hover:shadow-sm">

                    {{-- Icon --}}
                    <div class="w-11 h-11 {{ $p['icon_bg'] }} {{ $p['hover_icon'] }} rounded-xl flex items-center justify-center mr-4 flex-shrink-0 transition-colors shadow-sm">
                        <i class="fas fa-desktop text-white text-sm"></i>
                    </div>

                    {{-- Info --}}
                    <div class="flex-1 min-w-0">
                        <div class="{{ $p['text'] }} font-semibold text-sm truncate">{{ $client->name }}</div>
                        <div class="{{ $p['sub'] }} text-xs mt-0.5 truncate">{{ $displayHost }}</div>
                    </div>

                    {{-- Arrow icon --}}
                    <i class="fas fa-arrow-right text-gray-300 group-hover:text-gray-500 text-xs ml-2 transition-colors flex-shrink-0"></i>
                </a>
            @endforeach
        </div>

        {{-- Catatan jika user belum eligible SSO --}}
        @php $authUser = auth()->user(); @endphp
        @if(empty($authUser->nik) || empty($authUser->username))
        <div class="mt-4 flex items-start gap-2 bg-yellow-50 border border-yellow-200 rounded-lg px-4 py-3 text-sm text-yellow-700">
            <i class="fas fa-exclamation-triangle mt-0.5 flex-shrink-0"></i>
            <span>
                Akun Anda belum memiliki <strong>NIK</strong> atau <strong>username</strong>.
                <a href="{{ route('profile.index') }}" class="underline font-medium">Lengkapi profil</a>
                agar bisa masuk ke sistem terintegrasi.
            </span>
        </div>
        @endif
    </div>
</div>
@endif
