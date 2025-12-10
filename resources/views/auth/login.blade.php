<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - SGCMI</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full">
            <div class="bg-white rounded-lg shadow-lg p-8">
                <div class="text-center mb-8">
                    <h1 class="text-4xl font-bold text-sgcmi-blue-900 mb-2">🚢 SGCMI</h1>
                    <p class="text-gray-600">Sistema de Gestión de Coordinación Marítima Integrada</p>
                    <p class="text-sm text-gray-500 mt-1">Corredor Logístico Matarani–Sur Andino</p>
                </div>

                @if ($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
                        <ul class="list-disc list-inside text-sm">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (session('status'))
                    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-6">
                        <p class="text-sm">{{ session('status') }}</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.post') }}">
                    @csrf

                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            Email
                        </label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            value="{{ old('email') }}"
                            required 
                            autofocus
                            class="input-field w-full @error('email') border-red-500 @enderror"
                            placeholder="usuario@sgcmi.pe"
                        >
                    </div>

                    <div class="mb-6">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            Contraseña
                        </label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required
                            class="input-field w-full @error('password') border-red-500 @enderror"
                            placeholder="••••••••"
                        >
                    </div>

                    <div class="mb-6">
                        <label class="flex items-center">
                            <input 
                                type="checkbox" 
                                name="remember" 
                                class="rounded border-gray-300 text-sgcmi-blue-900 focus:ring-sgcmi-blue-500"
                            >
                            <span class="ml-2 text-sm text-gray-600">Recordarme</span>
                        </label>
                    </div>

                    <button type="submit" class="btn-primary w-full">
                        Iniciar Sesión
                    </button>
                </form>

                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="bg-blue-50 border border-blue-200 rounded p-4">
                        <p class="text-xs font-semibold text-blue-900 mb-2">Usuarios de Prueba:</p>
                        <ul class="text-xs text-blue-800 space-y-1">
                            <li><strong>Admin:</strong> admin@sgcmi.pe / password123</li>
                            <li><strong>Planificador:</strong> planificador@sgcmi.pe / password123</li>
                            <li><strong>Transportista:</strong> transportista@sgcmi.pe / password123</li>
                        </ul>
                    </div>
                </div>
            </div>

            <p class="text-center text-sm text-gray-500 mt-4">
                &copy; {{ date('Y') }} SGCMI - Todos los derechos reservados
            </p>
        </div>
    </div>
</body>
</html>
