<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Otorisasi Akses SSO</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" type="image/x-icon" href="{{ asset('images/logo.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-green-50 to-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">

        {{-- Card --}}
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">

            {{-- Header --}}
            <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-5 text-center">
                <div class="w-14 h-14 bg-white rounded-xl flex items-center justify-center mx-auto mb-3 shadow-sm">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo" class="h-10 w-auto object-contain">
                </div>
                <h1 class="text-white font-bold text-lg">SSO — Single Sign-On</h1>
                <p class="text-green-100 text-sm">Permintaan Akses Aplikasi</p>
            </div>

            <div class="p-6 space-y-4">

                {{-- User Info --}}
                <div class="flex items-center space-x-3 bg-gray-50 rounded-xl px-4 py-3 border border-gray-200">
                    <div class="w-10 h-10 bg-green-600 rounded-full flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-user text-white text-sm"></i>
                    </div>
                    <div>
                        <div class="text-sm font-semibold text-gray-800">{{ auth()->user()->name }}</div>
                        <div class="text-xs text-gray-500">{{ auth()->user()->username ?? auth()->user()->email }}</div>
                    </div>
                    <div class="ml-auto">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                            <i class="fas fa-check-circle mr-1"></i> Terautentikasi
                        </span>
                    </div>
                </div>

                {{-- Client Info --}}
                <div class="text-center py-2">
                    <div class="text-2xl mb-2">🔗</div>
                    <p class="text-gray-700 text-sm">
                        Aplikasi <strong class="text-green-700">{{ $client->name }}</strong>
                        meminta akses ke akun SSO Anda.
                    </p>
                </div>

                {{-- Scopes --}}
                @if(count($scopes) > 0)
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                    <h3 class="text-sm font-semibold text-blue-800 mb-2">
                        <i class="fas fa-list-check mr-1"></i> Akses yang diminta:
                    </h3>
                    <ul class="space-y-1">
                        @foreach($scopes as $scope)
                        <li class="flex items-center text-sm text-blue-700">
                            <i class="fas fa-check text-blue-500 mr-2 text-xs"></i>
                            {{ $scope->description }}
                        </li>
                        @endforeach
                    </ul>
                </div>
                @else
                <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
                    <div class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-info-circle mr-2 text-gray-400"></i>
                        Akses dasar: membaca informasi profil Anda (nama, NIK, username, email, role).
                    </div>
                </div>
                @endif

                {{-- Note --}}
                <p class="text-xs text-gray-400 text-center">
                    Anda akan tetap login di sistem SSO. Data ini hanya digunakan untuk keperluan login di aplikasi tersebut.
                </p>

                {{-- Action Buttons --}}
                <div class="flex space-x-3 pt-2">
                    {{-- Tolak --}}
                    <form method="POST" action="{{ route('passport.authorizations.deny') }}" class="flex-1">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="state" value="{{ $request->state }}">
                        <input type="hidden" name="client_id" value="{{ $client->id }}">
                        <input type="hidden" name="auth_token" value="{{ $authToken }}">
                        <button type="submit"
                                class="w-full px-4 py-2.5 text-sm font-semibold text-gray-600 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors">
                            <i class="fas fa-times mr-1"></i> Tolak
                        </button>
                    </form>

                    {{-- Izinkan --}}
                    <form method="POST" action="{{ route('passport.authorizations.approve') }}" class="flex-1">
                        @csrf
                        <input type="hidden" name="state" value="{{ $request->state }}">
                        <input type="hidden" name="client_id" value="{{ $client->id }}">
                        <input type="hidden" name="auth_token" value="{{ $authToken }}">
                        <button type="submit"
                                class="w-full px-4 py-2.5 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-xl transition-colors shadow-sm">
                            <i class="fas fa-check mr-1"></i> Izinkan Akses
                        </button>
                    </form>
                </div>

            </div>
        </div>

        <p class="text-center text-xs text-gray-400 mt-4">
            Sistem SSO — {{ config('app.name') }}
        </p>
    </div>
</body>
</html>
