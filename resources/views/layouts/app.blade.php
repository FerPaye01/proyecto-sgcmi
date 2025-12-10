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
    <nav class="bg-sgcmi-blue-900 text-white shadow-lg">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-8">
                    <h1 class="text-2xl font-bold">SGCMI</h1>
                    
                    <div class="hidden md:flex space-x-4">
                        @auth
                            @can('SCHEDULE_READ')
                                <a href="/portuario/vessel-calls" class="hover:bg-sgcmi-blue-800 px-3 py-2 rounded transition-colors">
                                    Portuario
                                </a>
                            @endcan
                            
                            @can('APPOINTMENT_READ')
                                <a href="/terrestre/appointments" class="hover:bg-sgcmi-blue-800 px-3 py-2 rounded transition-colors">
                                    Terrestre
                                </a>
                            @endcan
                            
                            @can('ADUANA_READ')
                                <a href="/aduanas/tramites" class="hover:bg-sgcmi-blue-800 px-3 py-2 rounded transition-colors">
                                    Aduanas
                                </a>
                            @endcan
                            
                            @can('REPORT_READ')
                                <a href="/reports" class="hover:bg-sgcmi-blue-800 px-3 py-2 rounded transition-colors">
                                    Reportes
                                </a>
                            @endcan
                            
                            @can('KPI_READ')
                                <a href="/reports/kpi/panel" class="hover:bg-sgcmi-blue-800 px-3 py-2 rounded transition-colors">
                                    KPIs
                                </a>
                            @endcan
                            
                            @can('ADMIN')
                                <a href="{{ route('admin.settings.thresholds.show') }}" class="hover:bg-sgcmi-blue-800 px-3 py-2 rounded transition-colors">
                                    ⚙️ Configuración
                                </a>
                            @endcan
                        @endauth
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    @auth
                        <span class="text-sm">{{ auth()->user()->full_name ?? auth()->user()->username }}</span>
                        <form action="/logout" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="hover:bg-sgcmi-blue-800 px-3 py-2 rounded transition-colors text-sm">
                                Salir
                            </button>
                        </form>
                    @else
                        <a href="/login" class="hover:bg-sgcmi-blue-800 px-3 py-2 rounded transition-colors">
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
