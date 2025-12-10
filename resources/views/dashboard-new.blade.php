@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50" x-data="dashboard()">
    <!-- Header -->
    <div class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <h1 class="text-3xl font-bold text-gray-900">Dashboard de Reportes</h1>
            <p class="mt-2 text-gray-600">Accede a los 12 reportes del sistema SGCMI</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Módulo Portuario -->
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-semibold mr-3">PORTUARIO</span>
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- R1 -->
                <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow cursor-pointer" 
                     @click="openReport('r1', 'Programación vs Ejecución')">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">R1: Programación vs Ejecución</h3>
                            <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded">PORTUARIO</span>
                        </div>
                        <p class="text-gray-600 text-sm mb-4">Compara tiempos programados (ETA/ETB) con tiempos reales (ATA/ATB/ATD)</p>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">KPIs: Puntualidad, Demoras</span>
                            <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                                Ver Reporte →
                            </button>
                        </div>
                    </div>
                </div>

                <!-- R3 -->
                <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow cursor-pointer"
                     @click="openReport('r3', 'Utilización de Muelles')">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">R3: Utilización de Muelles</h3>
                            <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded">PORTUARIO</span>
                        </div>
                        <p class="text-gray-600 text-sm mb-4">Calcula utilización por franja horaria y detecta conflictos de ventana</p>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">KPIs: Utilización, Conflictos</span>
                            <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                                Ver Reporte →
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Módulo Terrestre -->
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-semibold mr-3">TERRESTRE</span>
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- R4 -->
                <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow cursor-pointer"
                     @click="openReport('r4', 'Tiempo de Espera')">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">R4: Tiempo de Espera</h3>
                            <span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded">TERRESTRE</span>
                        </div>
                        <p class="text-gray-600 text-sm mb-4">Tiempo de espera desde llegada hasta primer evento de gate</p>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">KPIs: Espera promedio</span>
                            <button class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                                Ver Reporte →
                            </button>
                        </div>
                    </div>
                </div>

                <!-- R5 -->
                <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow cursor-pointer"
                     @click="openReport('r5', 'Cumplimiento de Citas')">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">R5: Cumplimiento de Citas</h3>
                            <span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded">TERRESTRE</span>
                        </div>
                        <p class="text-gray-600 text-sm mb-4">Clasifica citas: A tiempo, Tarde, No Show con ranking de empresas</p>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">KPIs: Cumplimiento %</span>
                            <button class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                                Ver Reporte →
                            </button>
                        </div>
                    </div>
                </div>

                <!-- R6 -->
                <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow cursor-pointer"
                     @click="openReport('r6', 'Productividad de Gates')">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">R6: Productividad de Gates</h3>
                            <span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded">TERRESTRE</span>
                        </div>
                        <p class="text-gray-600 text-sm mb-4">Vehículos por hora, tiempo de ciclo e identificación de horas pico</p>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">KPIs: Veh/hora, Ciclo</span>
                            <button class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                                Ver Reporte →
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Módulo Aduanero -->
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                <span class="bg-orange-100 text-orange-800 px-3 py-1 rounded-full text-sm font-semibold mr-3">ADUANERO</span>
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- R7 -->
                <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow cursor-pointer"
                     @click="openReport('r7', 'Estado de Trámites')">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">R7: Estado de Trámites</h3>
                            <span class="bg-orange-100 text-orange-800 text-xs font-semibold px-2.5 py-0.5 rounded">ADUANERO</span>
                        </div>
                        <p class="text-gray-600 text-sm mb-4">Estado de trámites agrupados por nave con lead time</p>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">KPIs: Lead time</span>
                            <button class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                                Ver Reporte →
                            </button>
                        </div>
                    </div>
                </div>

                <!-- R8 -->
                <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow cursor-pointer"
                     @click="openReport('r8', 'Tiempo de Despacho')">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">R8: Tiempo de Despacho</h3>
                            <span class="bg-orange-100 text-orange-800 text-xs font-semibold px-2.5 py-0.5 rounded">ADUANERO</span>
                        </div>
                        <p class="text-gray-600 text-sm mb-4">Percentiles (p50, p90) por régimen aduanero</p>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">KPIs: Percentiles</span>
                            <button class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                                Ver Reporte →
                            </button>
                        </div>
                    </div>
                </div>

                <!-- R9 -->
                <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow cursor-pointer"
                     @click="openReport('r9', 'Incidencias Documentación')">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">R9: Incidencias Documentación</h3>
                            <span class="bg-orange-100 text-orange-800 text-xs font-semibold px-2.5 py-0.5 rounded">ADUANERO</span>
                        </div>
                        <p class="text-gray-600 text-sm mb-4">Rechazos, reprocesamientos y tiempos de subsanación</p>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">KPIs: Rechazos %</span>
                            <button class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                                Ver Reporte →
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Módulo Analytics -->
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                <span class="bg-purple-100 text-purple-800 px-3 py-1 rounded-full text-sm font-semibold mr-3">ANALYTICS</span>
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- R10 -->
                <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow cursor-pointer"
                     @click="openReport('r10', 'Panel de KPIs')">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">R10: Panel de KPIs</h3>
                            <span class="bg-purple-100 text-purple-800 text-xs font-semibold px-2.5 py-0.5 rounded">ANALYTICS</span>
                        </div>
                        <p class="text-gray-600 text-sm mb-4">KPIs consolidados con comparativa y tendencias</p>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">KPIs: Turnaround, Espera</span>
                            <button class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                                Ver Reporte →
                            </button>
                        </div>
                    </div>
                </div>

                <!-- R11 -->
                <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow cursor-pointer"
                     @click="openReport('r11', 'Alertas Tempranas')">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">R11: Alertas Tempranas</h3>
                            <span class="bg-purple-100 text-purple-800 text-xs font-semibold px-2.5 py-0.5 rounded">ANALYTICS</span>
                        </div>
                        <p class="text-gray-600 text-sm mb-4">Detección de congestión y acumulación con niveles VERDE/AMARILLO/ROJO</p>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">Alertas: Congestión</span>
                            <button class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                                Ver Reporte →
                            </button>
                        </div>
                    </div>
                </div>

                <!-- R12 -->
                <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow cursor-pointer"
                     @click="openReport('r12', 'Cumplimiento SLAs')">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">R12: Cumplimiento SLAs</h3>
                            <span class="bg-purple-100 text-purple-800 text-xs font-semibold px-2.5 py-0.5 rounded">ANALYTICS</span>
                        </div>
                        <p class="text-gray-600 text-sm mb-4">Cumplimiento de SLAs por actor con penalidades</p>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">KPIs: Cumplimiento %</span>
                            <button class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                                Ver Reporte →
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Reportes -->
    <div x-show="showModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" style="display: none;">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900" x-text="selectedReport"></h3>
                <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Filtros -->
            <div class="bg-gray-50 p-4 rounded-lg mb-4">
                <h4 class="font-semibold text-gray-900 mb-3">Filtros</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Desde</label>
                        <input type="date" x-model="filters.fecha_desde" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Hasta</label>
                        <input type="date" x-model="filters.fecha_hasta" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div class="flex items-end">
                        <button @click="loadReport()" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium">
                            Generar Reporte
                        </button>
                    </div>
                </div>
            </div>

            <!-- Contenido del Reporte -->
            <div id="reportContent" class="bg-white border border-gray-200 rounded-lg p-4">
                <p class="text-gray-500 text-center py-8">Cargando reporte...</p>
            </div>

            <!-- Botones de Acción -->
            <div class="mt-4 flex justify-end gap-2">
                <button @click="exportReport('csv')" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md font-medium">
                    Descargar CSV
                </button>
                <button @click="exportReport('xlsx')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium">
                    Descargar XLSX
                </button>
                <button @click="exportReport('pdf')" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md font-medium">
                    Descargar PDF
                </button>
            </div>
        </div>
    </div>
</div>

<script defer>
function dashboard() {
    return {
        showModal: false,
        selectedReport: '',
        selectedReportCode: '',
        filters: {
            fecha_desde: new Date(new Date().setDate(new Date().getDate() - 30)).toISOString().split('T')[0],
            fecha_hasta: new Date().toISOString().split('T')[0],
        },
        
        openReport(code, name) {
            this.selectedReportCode = code;
            this.selectedReport = name;
            this.showModal = true;
            this.loadReport();
        },
        
        async loadReport() {
            const url = new URL(`/api/report/${this.selectedReportCode}`, window.location.origin);
            url.searchParams.append('fecha_desde', this.filters.fecha_desde);
            url.searchParams.append('fecha_hasta', this.filters.fecha_hasta);
            
            try {
                document.getElementById('reportContent').innerHTML = '<p class="text-gray-500 text-center py-8">Cargando reporte...</p>';
                
                const response = await fetch(url);
                const data = await response.json();
                
                if (data.success && data.html) {
                    document.getElementById('reportContent').innerHTML = data.html;
                } else if (data.error) {
                    document.getElementById('reportContent').innerHTML = `<p class="text-red-600">${data.error}</p>`;
                } else {
                    document.getElementById('reportContent').innerHTML = '<p class="text-red-600">Error desconocido al cargar el reporte</p>';
                }
            } catch (error) {
                document.getElementById('reportContent').innerHTML = `<p class="text-red-600">Error: ${error.message}</p>`;
            }
        },
        
        exportReport(format) {
            alert(`Exportando ${this.selectedReport} en formato ${format.toUpperCase()}`);
            // Implementar exportación
        }
    }
}
</script>
@endsection
