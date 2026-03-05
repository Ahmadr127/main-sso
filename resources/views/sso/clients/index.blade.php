@extends('layouts.app')

@section('title', 'SSO Clients')

@section('content')
<div class="container mx-auto py-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-white">SSO Client Management</h1>
                <p class="text-green-100 text-sm mt-1">Kelola aplikasi yang terintegrasi dengan SSO</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('sso.integration-guide') }}"
                   class="inline-flex items-center px-4 py-2 bg-green-500 bg-opacity-40 text-white font-semibold rounded-lg hover:bg-opacity-60 transition-colors text-sm border border-green-400">
                    <i class="fas fa-book mr-2"></i> Panduan Integrasi
                </a>
                <a href="{{ route('sso.clients.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-white text-green-700 font-semibold rounded-lg hover:bg-green-50 transition-colors shadow-sm text-sm">
                    <i class="fas fa-plus mr-2"></i> Daftarkan Client Baru
                </a>
            </div>
        </div>

        <div class="p-6">

            {{-- Credential Box: tampil sesaat setelah client baru dibuat --}}
            @if(session('success'))
            @php
                $msg = session('success');
                preg_match('/Client ID: ([\w-]+)/', $msg, $idMatch);
                preg_match('/Secret: ([\w]+)/', $msg, $secretMatch);
                $newClientId = $idMatch[1] ?? null;
                $newSecret   = $secretMatch[1] ?? null;
            @endphp
            @if($newClientId && $newSecret)
            <div class="mb-6 bg-green-50 border border-green-300 rounded-xl p-5" x-data="{}">
                <div class="flex items-center gap-2 mb-3">
                    <i class="fas fa-check-circle text-green-600"></i>
                    <span class="font-semibold text-green-800">Client berhasil dibuat! Simpan credential berikut — Secret hanya ditampilkan sekali.</span>
                </div>
                <div class="grid md:grid-cols-2 gap-3">
                    <div>
                        <div class="text-xs font-semibold text-gray-500 uppercase mb-1">Client ID</div>
                        <div class="flex items-center gap-2 bg-white border border-gray-200 rounded-lg px-3 py-2">
                            <code class="text-xs text-gray-800 flex-1 font-mono" id="cid">{{ $newClientId }}</code>
                            <button onclick="navigator.clipboard.writeText('{{ $newClientId }}');this.innerText='✓'" class="text-xs text-green-600 hover:text-green-800 flex-shrink-0 font-medium">Copy</button>
                        </div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-gray-500 uppercase mb-1">Client Secret</div>
                        <div class="flex items-center gap-2 bg-white border border-gray-200 rounded-lg px-3 py-2">
                            <code class="text-xs text-gray-800 flex-1 font-mono" id="csec">{{ $newSecret }}</code>
                            <button onclick="navigator.clipboard.writeText('{{ $newSecret }}');this.innerText='✓'" class="text-xs text-green-600 hover:text-green-800 flex-shrink-0 font-medium">Copy</button>
                        </div>
                    </div>
                </div>
                <div class="mt-3 bg-white border border-gray-200 rounded-lg p-3">
                    <div class="text-xs font-semibold text-gray-500 uppercase mb-1">Contoh isian .env sistem klien</div>
                    <pre class="text-xs font-mono text-gray-700 select-all">SSO_BASE_URL={{ config('app.url') }}
SSO_CLIENT_ID={{ $newClientId }}
SSO_CLIENT_SECRET={{ $newSecret }}
SSO_REDIRECT_URI=https://your-app.com/auth/sso/callback</pre>
                </div>
            </div>
            @else
            <div class="mb-4 bg-green-50 border border-green-200 rounded-lg px-4 py-3 text-sm text-green-700">{{ $msg }}</div>
            @endif
            @endif

            @if($clients->isEmpty())
                <div class="text-center py-12 text-gray-400">
                    <i class="fas fa-plug text-4xl mb-3"></i>
                    <p class="text-lg">Belum ada client SSO yang terdaftar.</p>
                    <p class="text-sm mt-1">Klik tombol di atas untuk mendaftarkan aplikasi pertama.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama Aplikasi</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Client ID</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Redirect URI</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Dibuat</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($clients as $client)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-9 h-9 rounded-lg bg-green-100 flex items-center justify-center flex-shrink-0">
                                            <i class="fas fa-desktop text-green-600"></i>
                                        </div>
                                        <span class="font-medium text-gray-800">{{ $client->name }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <code class="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded font-mono">{{ $client->id }}</code>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 max-w-xs truncate">
                                    {{ implode(', ', $client->redirect_uris ?? []) ?: ($client->redirect ?? '-') }}
                                </td>
                                <td class="px-4 py-3">
                                    @if($client->revoked)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                            <i class="fas fa-times-circle mr-1"></i> Dicabut
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                            <i class="fas fa-check-circle mr-1"></i> Aktif
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500">
                                    {{ $client->created_at->format('d M Y') }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <form method="POST" action="{{ route('sso.clients.destroy', $client->id) }}"
                                          onsubmit="return confirm('Hapus client {{ $client->name }}? Semua token akan dicabut.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition-colors">
                                            <i class="fas fa-trash-alt mr-1"></i> Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Info Box --}}
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-xl p-5">
        <h3 class="font-semibold text-blue-800 mb-2"><i class="fas fa-info-circle mr-2"></i>Cara Integrasi Sistem Klien</h3>
        <p class="text-sm text-blue-700 mb-3">Gunakan credential di atas di sistem klien. Tambahkan ke file <code class="bg-blue-100 px-1 rounded">.env</code>:</p>
        <pre class="bg-white border border-blue-200 rounded-lg p-3 text-xs text-gray-700 overflow-x-auto">SSO_BASE_URL={{ config('app.url') }}
SSO_CLIENT_ID=&lt;client_id_dari_tabel&gt;
SSO_CLIENT_SECRET=&lt;client_secret_dari_saat_dibuat&gt;
SSO_REDIRECT_URI=https://your-app.com/auth/sso/callback</pre>
    </div>
</div>
@endsection
