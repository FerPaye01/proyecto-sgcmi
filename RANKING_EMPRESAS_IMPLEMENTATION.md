# Implementación de Ranking de Empresas - Reporte R5

## Resumen

Se ha implementado exitosamente el ranking de empresas por cumplimiento de citas en el Reporte R5 (Cumplimiento de Citas). Esta funcionalidad está **oculta para usuarios con rol TRANSPORTISTA** según los requisitos de seguridad y scoping del sistema.

## Funcionalidad Implementada

### 1. Cálculo de Ranking (`ReportService::calculateRankingEmpresas()`)

**Ubicación:** `app/Services/ReportService.php`

El método calcula el ranking de empresas basándose en:
- **Total de citas:** Número total de citas programadas
- **Citas a tiempo:** Citas con llegada dentro de ±15 minutos de la hora programada
- **No Show:** Citas sin registro de llegada
- **% Cumplimiento:** Porcentaje de citas a tiempo sobre el total
- **% No Show:** Porcentaje de citas sin presentación

```php
private function calculateRankingEmpresas(array $filters): Collection
{
    // Obtiene todas las citas según filtros
    // Agrupa por empresa
    // Calcula métricas de cumplimiento
    // Ordena por % cumplimiento descendente
    return $ranking;
}
```

### 2. Integración en Reporte R5 (`ReportService::generateR5()`)

El método `generateR5()` incluye lógica condicional para:
- **Generar ranking:** Solo si el usuario NO es TRANSPORTISTA
- **Retornar null:** Si el usuario es TRANSPORTISTA

```php
// Calcular ranking de empresas (solo si el usuario NO es TRANSPORTISTA)
$ranking = null;
if ($user === null || !$user->hasRole('TRANSPORTISTA')) {
    $ranking = $this->calculateRankingEmpresas($filters);
}

return [
    'data' => $dataConClasificacion,
    'kpis' => $kpis,
    'ranking' => $ranking,
];
```

### 3. Controlador (`ReportController::r5()`)

**Ubicación:** `app/Http/Controllers/ReportController.php`

El controlador:
- Obtiene el usuario autenticado
- Pasa el usuario al servicio para aplicar scoping
- Pasa el ranking a la vista (será null para TRANSPORTISTA)

```php
public function r5(Request $request): View
{
    $user = auth()->user();
    $report = $this->reportService->generateR5($filters, $user);
    
    return view('reports.road.appointments-compliance', [
        'data' => $report['data'],
        'kpis' => $report['kpis'],
        'ranking' => $report['ranking'],
        'isTransportista' => $user ? $user->hasRole('TRANSPORTISTA') : false,
    ]);
}
```

### 4. Vista Blade

**Ubicación:** `resources/views/reports/road/appointments-compliance.blade.php`

La vista incluye una sección condicional que:
- **Muestra el ranking:** Solo si `!$isTransportista && $ranking !== null && $ranking->count() > 0`
- **Oculta el ranking:** Para usuarios TRANSPORTISTA

```blade
@if(!$isTransportista && $ranking !== null && $ranking->count() > 0)
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h3 class="text-xl font-semibold mb-4">Ranking de Empresas por Cumplimiento</h3>
        
        <table class="min-w-full">
            <!-- Tabla con ranking -->
        </table>
    </div>
@endif
```

## Características del Ranking

### Columnas Mostradas

1. **Posición:** Ranking con medallas para top 3 (🥇🥈🥉)
2. **Empresa:** Nombre de la empresa
3. **Total Citas:** Número total de citas
4. **A Tiempo:** Cantidad de citas a tiempo
5. **No Show:** Cantidad de citas sin presentación
6. **% Cumplimiento:** Porcentaje de cumplimiento (ordenado descendente)
7. **% No Show:** Porcentaje de no show
8. **Calificación:** Badge visual (Excelente/Bueno/Mejorable)

### Criterios de Calificación

- **Excelente:** ≥ 80% de cumplimiento (badge verde)
- **Bueno:** ≥ 60% de cumplimiento (badge amarillo)
- **Mejorable:** < 60% de cumplimiento (badge rojo)

## Seguridad y Scoping

### Reglas de Visibilidad

1. **TRANSPORTISTA:**
   - ❌ NO puede ver el ranking
   - ✅ Solo ve sus propias citas (scoping por company_id)
   - ✅ Ve sus propios KPIs

2. **Otros roles (ANALISTA, OPERADOR_GATES, etc.):**
   - ✅ Pueden ver el ranking completo
   - ✅ Ven todas las empresas
   - ✅ Ven KPIs globales

### Implementación de Scoping

El scoping se aplica mediante `ScopingService::applyCompanyScope()`:

```php
if ($user !== null) {
    $query = ScopingService::applyCompanyScope($query, $user);
}
```

## Tests Implementados

**Ubicación:** `tests/Feature/ReportR5ScopingTest.php`

### Tests Existentes

1. ✅ `test_r5_report_applies_scoping_for_transportista`
   - Verifica que TRANSPORTISTA solo ve sus citas

2. ✅ `test_r5_report_hides_ranking_for_transportista`
   - Verifica que ranking es null para TRANSPORTISTA

3. ✅ `test_r5_report_shows_ranking_for_analista`
   - Verifica que ANALISTA ve el ranking completo
   - Verifica ordenamiento correcto por % cumplimiento

4. ✅ `test_r5_report_classifies_appointments_correctly`
   - Verifica clasificación correcta (A_TIEMPO, TARDE, NO_SHOW)
   - Verifica cálculo de KPIs

### Resultados de Tests

```
PHPUnit 11.5.44 by Sebastian Bergmann and contributors.

....                                                                4 / 4 (100%)

Time: 00:03.093, Memory: 40.00 MB

OK (4 tests, 16 assertions)
```

## Ruta Web

**Definición:** `routes/web.php`

```php
Route::get('/road/appointments-compliance', [ReportController::class, 'r5'])
    ->middleware('permission:ROAD_REPORT_READ')
    ->name('reports.r5');
```

**URL:** `/reports/road/appointments-compliance`

**Permisos requeridos:** `ROAD_REPORT_READ`

## Ejemplo de Uso

### Para ANALISTA (ve ranking)

```
GET /reports/road/appointments-compliance?fecha_desde=2025-01-01&fecha_hasta=2025-01-31

Respuesta:
- Tabla de citas con clasificación
- KPIs globales
- Ranking de empresas (visible)
```

### Para TRANSPORTISTA (no ve ranking)

```
GET /reports/road/appointments-compliance?fecha_desde=2025-01-01&fecha_hasta=2025-01-31

Respuesta:
- Tabla de citas de su empresa únicamente
- KPIs de su empresa
- Ranking NO visible
```

## Cumplimiento de Requisitos

### Requisitos del Sistema (US-3.3)

✅ **Scoping por company_id para TRANSPORTISTA**
- Implementado en `ReportService::generateR5()`
- Aplicado mediante `ScopingService::applyCompanyScope()`

✅ **Ranking de empresas (visible solo para roles no-TRANSPORTISTA)**
- Implementado en `ReportService::calculateRankingEmpresas()`
- Condicional en vista Blade

✅ **KPIs calculados correctamente**
- pct_no_show
- pct_tarde
- desvio_medio_min
- total_citas

✅ **Clasificación de citas**
- A tiempo (±15 min)
- Tarde (>15 min)
- No Show (sin llegada)

### Requisitos de Seguridad (steering.json.md)

✅ **RBAC enforced:** Middleware `permission:ROAD_REPORT_READ`
✅ **Scoping aplicado:** Solo TRANSPORTISTA ve sus datos
✅ **No PII en logs:** Datos sensibles no expuestos
✅ **PSR-12 compliance:** Código formateado correctamente
✅ **Strict types:** `declare(strict_types=1);` en todos los archivos

## Archivos Modificados/Creados

### Archivos Existentes (ya implementados)

1. `app/Services/ReportService.php`
   - Método `calculateRankingEmpresas()` agregado
   - Método `generateR5()` modificado para incluir ranking

2. `app/Http/Controllers/ReportController.php`
   - Método `r5()` ya pasa ranking a vista

3. `resources/views/reports/road/appointments-compliance.blade.php`
   - Sección de ranking con condicional `@if(!$isTransportista)`

4. `tests/Feature/ReportR5ScopingTest.php`
   - Tests completos para ranking y scoping

5. `routes/web.php`
   - Ruta `reports.r5` definida

### Archivos Nuevos

1. `sgcmi/RANKING_EMPRESAS_IMPLEMENTATION.md` (este documento)

## Conclusión

La funcionalidad de ranking de empresas está **completamente implementada y testeada**. El sistema cumple con todos los requisitos de:

- ✅ Cálculo correcto de métricas de cumplimiento
- ✅ Ordenamiento por % cumplimiento descendente
- ✅ Ocultamiento para usuarios TRANSPORTISTA
- ✅ Visibilidad para otros roles autorizados
- ✅ Scoping de datos por empresa
- ✅ Tests unitarios y de integración
- ✅ Cumplimiento de estándares de seguridad

**Estado:** ✅ COMPLETADO
