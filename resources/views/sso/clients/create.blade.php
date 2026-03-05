@extends('layouts.app')

@section('title', 'Daftarkan Client SSO')

@section('content')
<div class="max-w-2xl mx-auto py-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
            <h1 class="text-xl font-bold text-white">Daftarkan Aplikasi SSO Client</h1>
            <p class="text-green-100 text-sm mt-1">Tambahkan sistem baru yang akan terintegrasi via SSO</p>
        </div>

        <form method="POST" action="{{ route('sso.clients.store') }}" class="p-6 space-y-5">
            @csrf

            {{-- Nama Aplikasi --}}
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                    Nama Aplikasi <span class="text-red-500">*</span>
                </label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required
                       placeholder="Contoh: Sistem PUM, Sistem ATK..."
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition @error('name') border-red-400 @enderror">
                @error('name')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Redirect URI --}}
            <div>
                <label for="redirect" class="block text-sm font-medium text-gray-700 mb-1">
                    Callback / Redirect URI <span class="text-red-500">*</span>
                </label>
                <input type="url" id="redirect" name="redirect" value="{{ old('redirect') }}" required
                       placeholder="https://sistem-klien.com/auth/sso/callback"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition @error('redirect') border-red-400 @enderror">
                @error('redirect')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-400">URL ini harus dikonfigurasi di sistem klien sebagai <code>SSO_REDIRECT_URI</code>.</p>
            </div>

            {{-- Confidential Client --}}
            <div class="flex items-start space-x-3 p-4 bg-gray-50 rounded-lg border border-gray-200">
                <input type="checkbox" id="confidential" name="confidential" value="1"
                       {{ old('confidential', 1) ? 'checked' : '' }}
                       class="mt-0.5 h-4 w-4 text-green-600 rounded border-gray-300 focus:ring-green-500">
                <div>
                    <label for="confidential" class="text-sm font-medium text-gray-700 cursor-pointer">Client Confidential (disarankan)</label>
                    <p class="text-xs text-gray-500 mt-0.5">Client confidential perlu mengirim <code>client_secret</code> saat tukar token — lebih aman untuk aplikasi server-side.</p>
                </div>
            </div>

            {{-- Note tentang First Party --}}
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-sm text-yellow-700">
                <i class="fas fa-lightbulb mr-1"></i>
                <strong>Catatan:</strong> Semua client internal akan di-approve otomatis tanpa consent screen
                (Passport mendeteksi first-party via <code>skip_authorization</code>).
                Untuk mengecualikan, tambahkan <code>first_party = false</code> di tabel <code>oauth_clients</code>.
            </div>

            {{-- Buttons --}}
            <div class="flex items-center justify-between pt-2">
                <a href="{{ route('sso.clients.index') }}"
                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i> Kembali
                </a>
                <button type="submit"
                        class="inline-flex items-center px-6 py-2 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors shadow-sm">
                    <i class="fas fa-plus-circle mr-2"></i> Daftarkan Client
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
