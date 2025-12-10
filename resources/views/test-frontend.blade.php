@extends('layouts.app')

@section('title', 'Test Frontend - Tailwind & Alpine.js')

@section('content')
<div class="max-w-6xl mx-auto">
    <h1 class="text-3xl font-bold text-sgcmi-blue-900 mb-6">
        Test de Configuración Frontend
    </h1>
    
    <!-- Test Tailwind CSS -->
    <div class="card mb-6">
        <h2 class="text-xl font-semibold mb-4">✓ Tailwind CSS</h2>
        <p class="text-gray-600 mb-4">
            Si ves este card con estilos correctos, Tailwind está funcionando.
        </p>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <button class="btn-primary">Botón Primary</button>
            <button class="btn-secondary">Botón Secondary</button>
            <button class="btn-danger">Botón Danger</button>
        </div>
        
        <div class="mt-4 flex gap-2">
            <span class="badge-success">Success</span>
            <span class="badge-warning">Warning</span>
            <span class="badge-danger">Danger</span>
            <span class="badge-info">Info</span>
        </div>
    </div>
    
    <!-- Test Alpine.js - Counter -->
    <div class="card mb-6" x-data="{ count: 0 }">
        <h2 class="text-xl font-semibold mb-4">✓ Alpine.js - Contador</h2>
        <p class="text-gray-600 mb-4">
            Si el contador funciona, Alpine.js está activo.
        </p>
        
        <div class="flex items-center gap-4">
            <button @click="count--" class="btn-secondary">-</button>
            <span class="text-2xl font-bold" x-text="count"></span>
            <button @click="count++" class="btn-primary">+</button>
        </div>
    </div>
    
    <!-- Test Alpine.js - Modal -->
    <div class="card mb-6" x-data="modal()">
        <h2 class="text-xl font-semibold mb-4">✓ Alpine.js - Modal</h2>
        <p class="text-gray-600 mb-4">
            Test del componente modal personalizado.
        </p>
        
        <button @click="show()" class="btn-primary">
            Abrir Modal
        </button>
        
        <!-- Modal -->
        <div x-show="open" 
             x-transition
             class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
             @click.self="hide()">
            <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-xl font-bold mb-4">Modal de Prueba</h3>
                <p class="text-gray-600 mb-4">
                    Este es un modal funcional usando Alpine.js.
                </p>
                <button @click="hide()" class="btn-primary">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
    
    <!-- Test Alpine.js - Date Validator -->
    <div class="card mb-6" x-data="dateValidator()">
        <h2 class="text-xl font-semibold mb-4">✓ Alpine.js - Validador de Fechas</h2>
        <p class="text-gray-600 mb-4">
            Test del validador de fechas (ETB debe ser >= ETA).
        </p>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    ETA (Estimated Time of Arrival)
                </label>
                <input 
                    type="datetime-local" 
                    x-model="eta"
                    @change="validateDates()"
                    class="input-field"
                >
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    ETB (Estimated Time of Berthing)
                </label>
                <input 
                    type="datetime-local" 
                    x-model="etb"
                    @change="validateDates()"
                    class="input-field"
                    :class="{ 'border-red-500': hasError('etb') }"
                >
                <p x-show="hasError('etb')" 
                   x-text="getError('etb')" 
                   class="text-red-500 text-sm mt-1">
                </p>
            </div>
        </div>
    </div>
    
    <!-- Test Alpine.js - Report Filters -->
    <div class="card mb-6" x-data="reportFilters()">
        <h2 class="text-xl font-semibold mb-4">✓ Alpine.js - Filtros de Reporte</h2>
        <p class="text-gray-600 mb-4">
            Test del componente de filtros con persistencia en URL.
        </p>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Fecha Desde
                </label>
                <input 
                    type="date" 
                    x-model="filters.fecha_desde"
                    class="input-field"
                >
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Fecha Hasta
                </label>
                <input 
                    type="date" 
                    x-model="filters.fecha_hasta"
                    class="input-field"
                >
            </div>
        </div>
        
        <div class="flex gap-2">
            <button @click="applyFilters()" class="btn-primary">
                Aplicar Filtros
            </button>
            <button @click="clearFilters()" class="btn-secondary">
                Limpiar Filtros
            </button>
        </div>
        
        <div class="mt-4 p-4 bg-gray-100 rounded">
            <p class="text-sm font-mono">
                Filtros actuales: <span x-text="JSON.stringify(filters, null, 2)"></span>
            </p>
        </div>
    </div>
    
    <!-- Test Table -->
    <div class="card">
        <h2 class="text-xl font-semibold mb-4">✓ Tabla con Estilos</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="table-header">
                    <tr>
                        <th class="px-4 py-2 text-left">ID</th>
                        <th class="px-4 py-2 text-left">Nombre</th>
                        <th class="px-4 py-2 text-left">Estado</th>
                        <th class="px-4 py-2 text-left">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="table-row">
                        <td class="px-4 py-2">1</td>
                        <td class="px-4 py-2">Ejemplo 1</td>
                        <td class="px-4 py-2">
                            <span class="badge-success">Activo</span>
                        </td>
                        <td class="px-4 py-2">
                            <button class="text-blue-600 hover:underline">Ver</button>
                        </td>
                    </tr>
                    <tr class="table-row">
                        <td class="px-4 py-2">2</td>
                        <td class="px-4 py-2">Ejemplo 2</td>
                        <td class="px-4 py-2">
                            <span class="badge-warning">Pendiente</span>
                        </td>
                        <td class="px-4 py-2">
                            <button class="text-blue-600 hover:underline">Ver</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
