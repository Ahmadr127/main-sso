<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistem')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" type="image/x-icon" href="{{ asset('images/logo.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    {{-- Sidebar CSS --}}
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    
    {{-- Sidebar JS - MUST load before Alpine.js to prevent flicker --}}
    <script src="{{ asset('js/sidebar.js') }}"></script>
</head>
<body class="bg-gray-100 overflow-x-hidden h-screen">
    {{-- 
        Alpine Component with separated desktop/mobile logic
        - Desktop: toggles width via CSS class on html element
        - Mobile: toggles visibility via transform
    --}}
    <div x-data="sidebarComponent()" x-init="init()" class="h-full flex overflow-x-hidden">
        
        <!-- Sidebar: No x-cloak, use CSS to control visibility -->
        <div class="sidebar fixed inset-y-0 left-0 z-50 bg-green-700 shadow-lg flex flex-col h-screen lg:static lg:inset-0"
             :class="{ 'mobile-open': mobileOpen }">
            
            <!-- Logo/Brand -->
            <div class="flex items-center justify-between h-20 px-4 border-b border-green-600 flex-shrink-0">
                <div class="flex items-center space-x-3 overflow-hidden">
                    <div class="bg-white rounded-xl border border-green-200 shadow-sm p-2 flex-shrink-0">
                        <img src="{{ asset('images/logo.png') }}" alt="Logo" class="h-8 w-auto object-contain">
                    </div>
                    {{-- Use CSS-driven visibility instead of x-show --}}
                    <h1 class="sidebar-text text-xl font-bold text-white tracking-wide truncate">Sistem</h1>
                </div>
            </div>

            <!-- Sidebar Navigation -->
            <nav class="flex-1 overflow-y-auto sidebar-scroll px-4 py-6">
                
                {{-- Dashboard --}}
                @if(auth()->user()->hasPermission('view_dashboard'))
                <div class="mb-4">
                    <a href="{{ route('dashboard') }}" 
                       class="sidebar-link flex items-center px-4 py-3 text-white rounded-lg hover:bg-green-800 transition-colors {{ request()->routeIs('dashboard') ? 'bg-green-800' : '' }}" 
                       title="Dashboard">
                        <i class="fas fa-tachometer-alt w-5 sidebar-icon mr-3"></i>
                        <span class="sidebar-text">Dashboard</span>
                    </a>
                </div>
                @endif

                {{-- User & Access Management Submenu --}}
                @if(auth()->user()->hasPermission('manage_users') || auth()->user()->hasPermission('manage_roles') || auth()->user()->hasPermission('manage_permissions'))
                <div class="mb-4" x-data="{ open: {{ request()->routeIs('users.*') || request()->routeIs('roles.*') || request()->routeIs('permissions.*') ? 'true' : 'false' }} }">
                <button @click="open = !open" 
                        class="w-full flex items-center justify-between px-4 py-3 text-white rounded-lg hover:bg-green-800 transition-colors">
                    <div class="flex items-center">
                            <i class="fas fa-users-cog w-5 sidebar-icon mr-3"></i>
                            <span class="sidebar-text">Pengguna & Akses</span>
                        </div>
                        <i class="fas fa-chevron-down sidebar-text text-xs transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
                    </button>
                    
                    <div x-show="open" 
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-2"
                         class="mt-1 ml-4 pl-4 border-l-2 border-green-600 space-y-1">
                        
                        @if(auth()->user()->hasPermission('manage_users'))
                        <a href="{{ route('users.index') }}" 
                           class="flex items-center px-3 py-2 text-green-100 rounded-lg hover:bg-green-800 hover:text-white transition-colors text-sm {{ request()->routeIs('users.*') ? 'bg-green-800 text-white' : '' }}"
                           title="Users">
                            <i class="fas fa-users w-4 mr-2"></i>
                            <span class="sidebar-text">Users</span>
                        </a>
                        @endif
                        
                        @if(auth()->user()->hasPermission('manage_roles'))
                        <a href="{{ route('roles.index') }}" 
                           class="flex items-center px-3 py-2 text-green-100 rounded-lg hover:bg-green-800 hover:text-white transition-colors text-sm {{ request()->routeIs('roles.*') ? 'bg-green-800 text-white' : '' }}"
                           title="Roles">
                            <i class="fas fa-user-shield w-4 mr-2"></i>
                            <span class="sidebar-text">Roles</span>
                        </a>
                        @endif
                        
                        @if(auth()->user()->hasPermission('manage_permissions'))
                        <a href="{{ route('permissions.index') }}" 
                           class="flex items-center px-3 py-2 text-green-100 rounded-lg hover:bg-green-800 hover:text-white transition-colors text-sm {{ request()->routeIs('permissions.*') ? 'bg-green-800 text-white' : '' }}"
                           title="Permissions">
                            <i class="fas fa-key w-4 mr-2"></i>
                            <span class="sidebar-text">Permissions</span>
                        </a>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Organization Management Submenu --}}
                @if(auth()->user()->hasPermission('manage_organization_types') || auth()->user()->hasPermission('manage_organization_units'))
                <div class="mb-4" x-data="{ open: {{ request()->routeIs('organization-types.*') || request()->routeIs('organization-units.*') ? 'true' : 'false' }} }">
                <button @click="open = !open" 
                        class="w-full flex items-center justify-between px-4 py-3 text-white rounded-lg hover:bg-green-800 transition-colors">
                    <div class="flex items-center">
                            <i class="fas fa-building w-5 sidebar-icon mr-3"></i>
                            <span class="sidebar-text">Organisasi</span>
                        </div>
                        <i class="fas fa-chevron-down sidebar-text text-xs transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
                    </button>
                    
                    <div x-show="open" 
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-2"
                         class="mt-1 ml-4 pl-4 border-l-2 border-green-600 space-y-1">
                        
                        @if(auth()->user()->hasPermission('manage_organization_types'))
                        <a href="{{ route('organization-types.index') }}" 
                           class="flex items-center px-3 py-2 text-green-100 rounded-lg hover:bg-green-800 hover:text-white transition-colors text-sm {{ request()->routeIs('organization-types.*') ? 'bg-green-800 text-white' : '' }}"
                           title="Tipe Organisasi">
                            <i class="fas fa-sitemap w-4 mr-2"></i>
                            <span class="sidebar-text">Tipe Organisasi</span>
                        </a>
                        @endif
                        
                        @if(auth()->user()->hasPermission('manage_organization_units'))
                        <a href="{{ route('organization-units.index') }}" 
                           class="flex items-center px-3 py-2 text-green-100 rounded-lg hover:bg-green-800 hover:text-white transition-colors text-sm {{ request()->routeIs('organization-units.*') ? 'bg-green-800 text-white' : '' }}"
                           title="Unit Organisasi">
                            <i class="fas fa-diagram-project w-4 mr-2"></i>
                            <span class="sidebar-text">Unit Organisasi</span>
                        </a>
                        @endif
                    </div>
                </div>
                @endif

                {{-- SSO Client Management --}}
                @if(auth()->user()->hasPermission('manage_sso_clients'))
                <div class="mb-4">
                    <a href="{{ route('sso.clients.index') }}" 
                       class="sidebar-link flex items-center px-4 py-3 text-white rounded-lg hover:bg-green-800 transition-colors {{ request()->routeIs('sso.*') ? 'bg-green-800' : '' }}" 
                       title="SSO Clients">
                        <i class="fas fa-plug w-5 sidebar-icon mr-3"></i>
                        <span class="sidebar-text">SSO Clients</span>
                    </a>
                </div>
                @endif

            </nav>
        </div>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col lg:ml-0 overflow-x-hidden max-w-full h-full">
            <!-- Top Navigation Bar -->
            <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="flex items-center justify-between h-16 px-6">
                    <div class="flex items-center space-x-4">
                        <!-- Toggle Button with separated logic -->
                        <button @click="toggle()" 
                                class="p-2 rounded-lg text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50" 
                                :title="getToggleTitle()">
                            <!-- Mobile icon -->
                            <i class="fas text-lg lg:hidden" :class="mobileOpen ? 'fa-xmark' : 'fa-bars'"></i>
                            <!-- Desktop icon -->
                            <i class="fas text-lg hidden lg:inline" :class="isCollapsed ? 'fa-angles-right' : 'fa-angles-left'"></i>
                        </button>
                        
                        <div class="hidden sm:block">
                            <h2 class="text-xl font-semibold text-gray-800">@yield('title', 'Dashboard')</h2>
                            <p class="text-sm text-gray-500">Sistem</p>
                        </div>
                        
                        <!-- Mobile Title -->
                        <div class="sm:hidden">
                            <h2 class="text-lg font-semibold text-gray-800">@yield('title', 'Dashboard')</h2>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-3">
                        <!-- User Dropdown -->
                        <div class="relative" x-data="{ open: false }" @click.away="open = false">
                            <button @click="open = !open" class="flex items-center space-x-2 px-3 py-2 rounded-lg hover:bg-gray-50 transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50">
                                <div class="w-8 h-8 bg-green-600 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-sm text-white"></i>
                                </div>
                                <div class="text-left hidden sm:block">
                                    <div class="text-sm font-medium text-gray-700">{{ auth()->user()->name }}</div>
                                </div>
                                <i class="fas fa-chevron-down text-xs text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
                            </button>
                            
                            <!-- Dropdown Menu -->
                            <div x-show="open" 
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50"
                                 x-cloak>
                                
                                <div class="py-1">
                                    @if(auth()->user()->hasPermission('view_dashboard'))
                                    <a href="{{ route('dashboard') }}" class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                        <i class="fas fa-home w-4 h-4 mr-2 text-gray-400"></i>
                                        Dashboard
                                    </a>
                                    @endif

                                    <a href="{{ route('profile.index') }}" class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                        <i class="fas fa-user-circle w-4 h-4 mr-2 text-gray-400"></i>
                                        Profil
                                    </a>
                                    
                                    <div class="border-t border-gray-100 my-1"></div>
                                    
                                    <form method="POST" action="{{ route('logout') }}" class="block">
                                        @csrf
                                        <button type="submit" 
                                                class="flex items-center w-full px-3 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors"
                                                onclick="return confirm('Apakah Anda yakin ingin keluar?')">
                                            <i class="fas fa-sign-out-alt w-4 h-4 mr-2"></i>
                                            Logout
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 p-3 sm:p-4 lg:p-5 bg-gray-50 overflow-y-auto overflow-x-hidden max-w-full">
                @yield('content')
            </main>
        </div>

        <!-- Mobile Overlay -->
        <div x-show="mobileOpen" 
             @click="mobileOpen = false" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-40 bg-black bg-opacity-50 lg:hidden"
             x-cloak>
        </div>
    </div>

    {{-- Load Alpine.js AFTER sidebar component is defined --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <script src="{{ asset('js/toast.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function(){
            @if(session('success'))
                window.Toast && Toast.success(@json(session('success')));
            @endif
            @if(session('error'))
                window.Toast && Toast.error(@json(session('error')));
            @endif
            @if($errors->any())
                @foreach($errors->all() as $err)
                    window.Toast && Toast.error(@json($err), { duration: 6000 });
                @endforeach
            @endif
        });
    </script>
    @stack('scripts')
</body>
</html>
