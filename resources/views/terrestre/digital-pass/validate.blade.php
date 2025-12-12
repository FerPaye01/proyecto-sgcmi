@extends('layouts.app')

@section('title', 'Validar Pase Digital')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Validar Pase Digital</h1>
        <a href="{{ route('digital-pass.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium">
            ← Volver al Listado
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Formulario de Validación -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Escanear o Ingresar Código</h2>
            
            <!-- Mock QR Scanner Interface -->
            <div class="mb-6">
                <div class="bg-gray-100 border-4 border-dashed border-gray-300 rounded-lg p-8 text-center">
                    <div class="text-6xl mb-4">📷</div>
                    <p class="text-gray-600 font-medium mb-2">Escáner QR (Simulado)</p>
                    <p class="text-sm text-gray-500">En producción, aquí se activaría la cámara del dispositivo</p>
                    <button 
                        type="button"
                        onclick="alert('En producción, esto activaría la cámara del dispositivo para escanear códigos QR')"
                        class="mt-4 bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md font-medium"
                    >
                        🎥 Activar Cámara (Mock)
                    </button>
                </div>
            </div>

            <div class="relative mb-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-300"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-2 bg-white text-gray-500">O ingrese el código manualmente</span>
                </div>
            </div>

            <!-- Formulario Manual -->
            <form id="validate-form" class="space-y-4">
                @csrf
                <div>
                    <label for="pass_code" class="block text-sm font-medium text-gray-700 mb-1">
                        Código del Pase <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="pass_code" 
                        name="pass_code" 
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Ej: DP-2024-001234"
                    >
                    <p class="text-xs text-gray-500 mt-1">Ingrese el código del pase digital que desea validar</p>
                </div>

                <button 
                    type="submit" 
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-md font-medium text-lg"
                >
                    🔍 Validar Pase
                </button>
            </form>

            <!-- Ejemplos de Códigos para Testing -->
            <div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h3 class="font-semibold text-yellow-900 mb-2">💡 Para Pruebas</h3>
                <p class="text-sm text-yellow-800 mb-2">Puede usar estos códigos de ejemplo:</p>
                <div class="space-y-1">
                    <button 
                        type="button"
                        onclick="document.getElementById('pass_code').value = 'DP-2024-001234'; document.getElementById('validate-form').dispatchEvent(new Event('submit'));"
                        class="block w-full text-left text-xs font-mono bg-white px-2 py-1 rounded hover:bg-yellow-100"
                    >
                        DP-2024-001234 (Ejemplo válido)
                    </button>
                    <button 
                        type="button"
                        onclick="document.getElementById('pass_code').value = 'DP-2024-999999'; document.getElementById('validate-form').dispatchEvent(new Event('submit'));"
                        class="block w-full text-left text-xs font-mono bg-white px-2 py-1 rounded hover:bg-yellow-100"
                    >
                        DP-2024-999999 (Ejemplo no encontrado)
                    </button>
                </div>
            </div>
        </div>

        <!-- Resultado de la Validación -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Resultado de la Validación</h2>
            
            <div id="validation-result" class="hidden">
                <!-- El resultado se mostrará aquí dinámicamente -->
            </div>

            <div id="validation-placeholder" class="text-center py-12">
                <div class="text-6xl mb-4">🔍</div>
                <p class="text-gray-500 font-medium">Esperando validación...</p>
                <p class="text-sm text-gray-400 mt-2">Escanee o ingrese un código para validar</p>
            </div>
        </div>
    </div>

    <!-- Información Adicional -->
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h3 class="font-semibold text-blue-900 mb-2">ℹ️ Información sobre la Validación</h3>
        <ul class="text-sm text-blue-800 space-y-1">
            <li>• La validación verifica que el pase esté <strong>ACTIVO</strong> y dentro del período de validez</li>
            <li>• Los pases <strong>REVOCADOS</strong> o <strong>VENCIDOS</strong> no pasarán la validación</li>
            <li>• El sistema verifica automáticamente las fechas de validez (desde/hasta)</li>
            <li>• En producción, el escáner QR utilizaría la cámara del dispositivo</li>
            <li>• Los resultados de validación se muestran en tiempo real</li>
        </ul>
    </div>
</div>

<script>
document.getElementById('validate-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const passCode = document.getElementById('pass_code').value;
    const resultDiv = document.getElementById('validation-result');
    const placeholderDiv = document.getElementById('validation-placeholder');
    
    // Mostrar loading
    placeholderDiv.innerHTML = `
        <div class="text-center py-12">
            <div class="text-6xl mb-4">⏳</div>
            <p class="text-gray-500 font-medium">Validando...</p>
        </div>
    `;
    
    try {
        const response = await fetch('{{ route('digital-pass.validate') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ pass_code: passCode })
        });
        
        const data = await response.json();
        
        // Ocultar placeholder y mostrar resultado
        placeholderDiv.classList.add('hidden');
        resultDiv.classList.remove('hidden');
        
        if (data.valid) {
            // Pase válido
            resultDiv.innerHTML = `
                <div class="bg-green-50 border-2 border-green-500 rounded-lg p-6">
                    <div class="text-center mb-4">
                        <div class="text-6xl mb-2">✅</div>
                        <h3 class="text-2xl font-bold text-green-800">Pase Válido</h3>
                        <p class="text-green-700 mt-2">${data.message}</p>
                    </div>
                    
                    <div class="border-t border-green-200 pt-4 mt-4">
                        <h4 class="font-semibold text-green-900 mb-3">Información del Pase:</h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Código:</span>
                                <span class="font-mono font-semibold text-gray-900">${data.pass.pass_code}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Tipo:</span>
                                <span class="font-semibold text-gray-900">${data.pass.pass_type}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Titular:</span>
                                <span class="font-semibold text-gray-900">${data.pass.holder_name}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">DNI:</span>
                                <span class="font-semibold text-gray-900">${data.pass.holder_dni}</span>
                            </div>
                            ${data.pass.truck_placa ? `
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Vehículo:</span>
                                    <span class="font-semibold text-gray-900">${data.pass.truck_placa}</span>
                                </div>
                            ` : ''}
                            <div class="flex justify-between">
                                <span class="text-gray-600">Válido desde:</span>
                                <span class="font-semibold text-gray-900">${data.pass.valid_from}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Válido hasta:</span>
                                <span class="font-semibold text-gray-900">${data.pass.valid_until}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Estado:</span>
                                <span class="px-2 py-1 text-xs font-semibold rounded bg-green-100 text-green-800">${data.pass.status}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4 pt-4 border-t border-green-200">
                        <button onclick="location.reload()" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md font-medium">
                            Validar Otro Pase
                        </button>
                    </div>
                </div>
            `;
        } else {
            // Pase no válido
            resultDiv.innerHTML = `
                <div class="bg-red-50 border-2 border-red-500 rounded-lg p-6">
                    <div class="text-center mb-4">
                        <div class="text-6xl mb-2">❌</div>
                        <h3 class="text-2xl font-bold text-red-800">Pase No Válido</h3>
                        <p class="text-red-700 mt-2">${data.message}</p>
                    </div>
                    
                    ${data.pass ? `
                        <div class="border-t border-red-200 pt-4 mt-4">
                            <h4 class="font-semibold text-red-900 mb-3">Información del Pase:</h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Código:</span>
                                    <span class="font-mono font-semibold text-gray-900">${data.pass.pass_code}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Titular:</span>
                                    <span class="font-semibold text-gray-900">${data.pass.holder_name}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Estado:</span>
                                    <span class="px-2 py-1 text-xs font-semibold rounded bg-red-100 text-red-800">${data.pass.status}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Válido hasta:</span>
                                    <span class="font-semibold text-gray-900">${data.pass.valid_until}</span>
                                </div>
                            </div>
                        </div>
                    ` : ''}
                    
                    <div class="mt-4 pt-4 border-t border-red-200">
                        <button onclick="location.reload()" class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md font-medium">
                            Validar Otro Pase
                        </button>
                    </div>
                </div>
            `;
        }
    } catch (error) {
        // Error en la validación
        placeholderDiv.classList.add('hidden');
        resultDiv.classList.remove('hidden');
        resultDiv.innerHTML = `
            <div class="bg-red-50 border-2 border-red-500 rounded-lg p-6">
                <div class="text-center">
                    <div class="text-6xl mb-2">⚠️</div>
                    <h3 class="text-2xl font-bold text-red-800">Error de Validación</h3>
                    <p class="text-red-700 mt-2">No se pudo validar el pase. Por favor, intente nuevamente.</p>
                    <button onclick="location.reload()" class="mt-4 bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-md font-medium">
                        Intentar Nuevamente
                    </button>
                </div>
            </div>
        `;
    }
});
</script>
@endsection
