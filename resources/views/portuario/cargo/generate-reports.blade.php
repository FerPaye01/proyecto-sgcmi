@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">
            🚢 Generación de Reportes COARRI/CODECO
        </h1>

        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
            <p class="text-sm text-blue-700">
                <strong>NUEVO:</strong> Esta funcionalidad genera reportes estandarizados para comunicar el estado de carga a sistemas externos (agencias marítimas, almacenes).
            </p>
        </div>

        <!-- Selector de Vessel Call -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Seleccionar Llamada de Nave
            </label>
            <select id="vesselCallSelect" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">-- Seleccione una nave --</option>
                @foreach($vesselCalls as $vc)
                    <option value="{{ $vc->id }}">
                        {{ $vc->vessel->name ?? 'N/A' }} - Viaje: {{ $vc->voyage_number ?? 'N/A' }} - 
                        ATA: {{ $vc->ata ? $vc->ata->format('Y-m-d H:i') : 'Pendiente' }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Botones de Generación -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- COARRI Reports -->
            <div class="border-2 border-green-200 rounded-lg p-4 bg-green-50">
                <h3 class="text-lg font-semibold text-green-800 mb-3">📦 Reporte COARRI (Descarga)</h3>
                <p class="text-sm text-gray-600 mb-4">Detalla la carga descargada de la nave</p>
                
                <div class="space-y-2">
                    <button onclick="generateReport('COARRI', 'json')" 
                            class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded transition">
                        Generar COARRI (JSON)
                    </button>
                    <button onclick="generateReport('COARRI', 'xml')" 
                            class="w-full bg-green-500 hover:bg-green-600 text-white font-medium py-2 px-4 rounded transition">
                        Generar COARRI (XML)
                    </button>
                </div>
            </div>

            <!-- CODECO Reports -->
            <div class="border-2 border-blue-200 rounded-lg p-4 bg-blue-50">
                <h3 class="text-lg font-semibold text-blue-800 mb-3">📋 Reporte CODECO (Contenedores)</h3>
                <p class="text-sm text-gray-600 mb-4">Estado de contenedores y movimientos</p>
                
                <div class="space-y-2">
                    <button onclick="generateReport('CODECO', 'json')" 
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded transition">
                        Generar CODECO (JSON)
                    </button>
                    <button onclick="generateReport('CODECO', 'xml')" 
                            class="w-full bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded transition">
                        Generar CODECO (XML)
                    </button>
                </div>
            </div>
        </div>

        <!-- Área de Resultados -->
        <div id="resultArea" class="hidden">
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="text-lg font-semibold text-gray-800">Resultado</h3>
                    <button onclick="closeResult()" class="text-gray-500 hover:text-gray-700">✕</button>
                </div>
                <div id="resultContent" class="space-y-3"></div>
            </div>
        </div>

        <!-- Diferencias con Funcionalidad Anterior -->
        <div class="mt-8 border-t pt-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">🆕 ¿Qué hay de nuevo?</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-700 mb-2">✅ Funcionalidad Anterior</h4>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• Registrar manifiestos de carga</li>
                        <li>• Registrar ítems de carga</li>
                        <li>• Asignar ubicaciones de patio</li>
                        <li>• Registrar movimientos internos</li>
                        <li>• Datos solo en base de datos</li>
                    </ul>
                </div>
                
                <div class="bg-green-50 p-4 rounded-lg border-2 border-green-200">
                    <h4 class="font-semibold text-green-700 mb-2">🆕 Nueva Funcionalidad</h4>
                    <ul class="text-sm text-green-700 space-y-1">
                        <li>• <strong>Generar reportes COARRI/CODECO</strong></li>
                        <li>• <strong>Exportar en JSON y XML</strong></li>
                        <li>• <strong>Envío a sistemas externos (mock)</strong></li>
                        <li>• <strong>Log de transmisiones</strong></li>
                        <li>• <strong>Integración con agencias marítimas</strong></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Log de Transmisiones -->
        <div class="mt-6">
            <button onclick="viewTransmissionLog()" 
                    class="bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-4 rounded transition">
                📊 Ver Log de Transmisiones Mock
            </button>
        </div>

        <!-- Modal para Log de Transmisiones -->
        <div id="logModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 shadow-lg rounded-md bg-white">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-800">📊 Log de Transmisiones Mock</h3>
                    <button onclick="closeLogModal()" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
                </div>
                <div id="logContent" class="max-h-96 overflow-y-auto">
                    <div class="text-center py-8">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600 mx-auto"></div>
                        <p class="mt-4 text-gray-600">Cargando transmisiones...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function generateReport(reportType, format) {
    const vesselCallId = document.getElementById('vesselCallSelect').value;
    
    if (!vesselCallId) {
        alert('Por favor seleccione una llamada de nave');
        return;
    }

    // Mostrar loading
    const resultArea = document.getElementById('resultArea');
    const resultContent = document.getElementById('resultContent');
    resultArea.classList.remove('hidden');
    resultContent.innerHTML = '<div class="text-center py-4"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div><p class="mt-2 text-gray-600">Generando reporte...</p></div>';

    // Hacer petición
    fetch('/portuario/cargo/generate-report', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            vessel_call_id: parseInt(vesselCallId),
            report_type: reportType,
            format: format
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displaySuccess(data, reportType, format);
        } else {
            displayError(data.message);
        }
    })
    .catch(error => {
        displayError('Error al generar reporte: ' + error.message);
    });
}

function displaySuccess(data, reportType, format) {
    const resultContent = document.getElementById('resultContent');
    const report = data.report;
    
    let contentPreview = report.content.substring(0, 500);
    if (report.content.length > 500) {
        contentPreview += '...';
    }
    
    resultContent.innerHTML = `
        <div class="bg-green-50 border-l-4 border-green-500 p-4">
            <p class="font-semibold text-green-800">✓ ${data.message}</p>
        </div>
        
        <div class="bg-white border rounded p-4">
            <h4 class="font-semibold mb-2">Detalles del Reporte:</h4>
            <ul class="text-sm space-y-1">
                <li><strong>Tipo:</strong> ${reportType}</li>
                <li><strong>Formato:</strong> ${format.toUpperCase()}</li>
                <li><strong>Vessel Call ID:</strong> ${report.vessel_call_id}</li>
                <li><strong>Generado:</strong> ${report.generated_at}</li>
            </ul>
        </div>
        
        <div class="bg-gray-100 border rounded p-4">
            <h4 class="font-semibold mb-2">Preview del Contenido:</h4>
            <pre class="text-xs overflow-x-auto bg-white p-3 rounded border">${contentPreview}</pre>
        </div>
        
        <div class="bg-blue-50 border-l-4 border-blue-500 p-4">
            <p class="text-sm text-blue-700">
                <strong>📡 Transmisión Mock:</strong> El reporte fue "enviado" a sistemas externos simulados.
                Ver log completo en: <code class="bg-white px-2 py-1 rounded">storage/app/mocks/operation_reports.json</code>
            </p>
        </div>
    `;
}

function displayError(message) {
    const resultContent = document.getElementById('resultContent');
    resultContent.innerHTML = `
        <div class="bg-red-50 border-l-4 border-red-500 p-4">
            <p class="font-semibold text-red-800">✗ Error</p>
            <p class="text-sm text-red-700 mt-1">${message}</p>
        </div>
    `;
}

function closeResult() {
    document.getElementById('resultArea').classList.add('hidden');
}

function viewTransmissionLog() {
    const modal = document.getElementById('logModal');
    const logContent = document.getElementById('logContent');
    
    modal.classList.remove('hidden');
    logContent.innerHTML = '<div class="text-center py-8"><div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600 mx-auto"></div><p class="mt-4 text-gray-600">Cargando transmisiones...</p></div>';
    
    // Fetch transmission log
    fetch('/portuario/cargo/transmission-log', {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }
        return response.json();
    })
    .then(data => {
        if (data.success && data.logs.length > 0) {
            displayTransmissionLog(data.logs);
        } else {
            logContent.innerHTML = '<div class="text-center py-8 text-gray-500"><p class="text-lg">📭 No hay transmisiones registradas aún</p><p class="text-sm mt-2">Genera un reporte para ver las transmisiones aquí</p></div>';
        }
    })
    .catch(error => {
        logContent.innerHTML = '<div class="bg-red-50 border-l-4 border-red-500 p-4"><p class="text-red-700">Error al cargar el log: ' + error.message + '</p></div>';
    });
}

function displayTransmissionLog(logs) {
    const logContent = document.getElementById('logContent');
    
    let html = '<div class="space-y-4">';
    
    logs.reverse().forEach((log, index) => {
        const bgColor = log.report_type === 'COARRI' ? 'bg-green-50 border-green-200' : 'bg-blue-50 border-blue-200';
        const badgeColor = log.report_type === 'COARRI' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800';
        
        html += `
            <div class="border-2 ${bgColor} rounded-lg p-4">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <span class="inline-block px-3 py-1 ${badgeColor} text-sm font-semibold rounded-full">${log.report_type}</span>
                        <span class="ml-2 text-sm text-gray-600">Transmisión #${logs.length - index}</span>
                    </div>
                    <span class="text-xs text-gray-500">${new Date(log.transmitted_at).toLocaleString('es-PE')}</span>
                </div>
                
                <div class="grid grid-cols-2 gap-3 text-sm mt-3">
                    <div>
                        <span class="font-semibold text-gray-700">Vessel Call ID:</span>
                        <span class="text-gray-600">${log.vessel_call_id}</span>
                    </div>
                    <div>
                        <span class="font-semibold text-gray-700">Formato:</span>
                        <span class="text-gray-600">${log.format.toUpperCase()}</span>
                    </div>
                    <div>
                        <span class="font-semibold text-gray-700">Estado:</span>
                        <span class="text-green-600 font-semibold">✓ ${log.status}</span>
                    </div>
                    <div>
                        <span class="font-semibold text-gray-700">Generado:</span>
                        <span class="text-gray-600">${new Date(log.generated_at).toLocaleString('es-PE')}</span>
                    </div>
                </div>
                
                <div class="mt-3 pt-3 border-t border-gray-200">
                    <p class="text-xs font-semibold text-gray-700 mb-1">Endpoints de destino:</p>
                    <ul class="text-xs text-gray-600 space-y-1">
                        ${Object.entries(log.endpoints).map(([name, url]) => `
                            <li>• <strong>${name}:</strong> ${url}</li>
                        `).join('')}
                    </ul>
                </div>
                
                <details class="mt-3">
                    <summary class="cursor-pointer text-xs text-purple-600 hover:text-purple-800 font-medium">Ver preview del contenido</summary>
                    <pre class="mt-2 text-xs bg-white p-2 rounded border overflow-x-auto">${log.content_preview}</pre>
                </details>
            </div>
        `;
    });
    
    html += '</div>';
    logContent.innerHTML = html;
}

function closeLogModal() {
    document.getElementById('logModal').classList.add('hidden');
}

// Cerrar modal al hacer clic fuera
document.addEventListener('click', function(event) {
    const modal = document.getElementById('logModal');
    if (event.target === modal) {
        closeLogModal();
    }
});
</script>
@endsection
