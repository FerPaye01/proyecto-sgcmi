<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SGCMI - @yield('title', 'Sistema de Gestión de Coordinación Marítima Integrada')</title>
    
    <!-- Vite CSS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-blue-900 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <div class="flex-shrink-0">
                    <a href="/" class="text-2xl font-bold text-white hover:text-gray-200">SGCMI</a>
                </div>
                
                <!-- Navigation Links - SIEMPRE VISIBLES -->
                <div class="flex items-center space-x-1">
                    <a href="/portuario/vessel-calls" class="text-white hover:bg-blue-800 px-3 py-2 rounded-md text-sm font-medium transition-colors">
                        Portuario
                    </a>
                    <a href="/terrestre/appointments" class="text-white hover:bg-blue-800 px-3 py-2 rounded-md text-sm font-medium transition-colors">
                        Terrestre
                    </a>
                    <a href="/aduanas/tramites" class="text-white hover:bg-blue-800 px-3 py-2 rounded-md text-sm font-medium transition-colors">
                        Aduanas
                    </a>
                    <a href="/" class="text-white hover:bg-blue-800 px-3 py-2 rounded-md text-sm font-medium transition-colors">
                        Reportes
                    </a>
                    <a href="/reports/kpi/panel" class="text-white hover:bg-blue-800 px-3 py-2 rounded-md text-sm font-medium transition-colors">
                        KPIs
                    </a>
                    @auth
                        @can('USER_ADMIN')
                            <a href="{{ route('admin.settings.thresholds.show') }}" class="text-white hover:bg-blue-800 px-3 py-2 rounded-md text-sm font-medium transition-colors">
                                ⚙️ Config
                            </a>
                        @endcan
                    @endauth
                </div>
                
                <!-- User Menu -->
                <div class="flex items-center space-x-3">
                    @auth
                        <span class="text-white text-sm">{{ auth()->user()->full_name ?? auth()->user()->username }}</span>
                        <form action="/logout" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-white hover:bg-blue-800 px-3 py-2 rounded-md text-sm font-medium transition-colors">
                                Salir
                            </button>
                        </form>
                    @else
                        <a href="/login" class="text-white hover:bg-blue-800 px-3 py-2 rounded-md text-sm font-medium transition-colors">
                            Iniciar Sesión
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <!-- Flash Messages -->
        @if(session('success'))
            <div x-data="{ show: true }" 
                 x-show="show" 
                 x-transition
                 class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                <span class="block sm:inline">{{ session('success') }}</span>
                <button @click="show = false" class="absolute top-0 bottom-0 right-0 px-4 py-3">
                    <span class="text-2xl">&times;</span>
                </button>
            </div>
        @endif
        
        @if(session('error'))
            <div x-data="{ show: true }" 
                 x-show="show" 
                 x-transition
                 class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                <span class="block sm:inline">{{ session('error') }}</span>
                <button @click="show = false" class="absolute top-0 bottom-0 right-0 px-4 py-3">
                    <span class="text-2xl">&times;</span>
                </button>
            </div>
        @endif
        
        @if($errors->any())
            <div x-data="{ show: true }" 
                 x-show="show" 
                 x-transition
                 class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button @click="show = false" class="absolute top-0 bottom-0 right-0 px-4 py-3">
                    <span class="text-2xl">&times;</span>
                </button>
            </div>
        @endif
        
        @yield('content')
    </main>
    
    <!-- Footer -->
    <footer class="bg-gray-800 text-white mt-12">
        <div class="container mx-auto px-4 py-6">
            <div class="text-center text-sm">
                <p>&copy; {{ date('Y') }} SGCMI - Sistema de Gestión de Coordinación Marítima Integrada</p>
                <p class="mt-2 text-gray-400">Corredor Logístico Matarani–Sur Andino</p>
            </div>
        </div>
    </footer>
</body>
</html>
