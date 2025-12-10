# R11 - Alertas Tempranas (Early Warning System)

## Implementación Completada

Se ha implementado exitosamente el sistema de alertas tempranas (R11) para el SGCMI.

## Componentes Implementados

### 1. ReportService - Método `generateR11()`

**Ubicación:** `app/Services/ReportService.php`

El método principal `generateR11()` implementa la lógica completa de detección de alertas:

```php
public function generateR11(array $filters): array
```

**Retorna:**
- `alertas`: Colección de alertas detectadas
- `kpis`: KPIs del sistema de alertas
- `estado_general`: Estado general del sistema (VERDE/AMARILLO/ROJO)

### 2. Métodos Auxiliares Implementados

#### Detección de Alertas

1. **`detectarAlertasCongestión()`**
   - Detecta congestión de muelles cuando utilización > 85%
   - Calcula utilización actual basándose en llamadas activas
   - Genera alertas con nivel de severidad

2. **`detectarAlertasAcumulación()`**
   - Detecta acumulación de camiones cuando espera promedio > 4 horas
   - Agrupa por empresa transportista
   - Calcula estadísticas de espera

#### Cálculos y Análisis

3. **`calcularUtilizaciónMuelleActual()`**
   - Calcula porcentaje de utilización de un muelle
   - Considera llamadas activas (ATB <= ahora <= ATD)

4. **`calcularEsperaPromedioPorEmpresa()`**
   - Calcula tiempo de espera promedio por empresa
   - Incluye estadísticas: mínima, máxima, promedio

5. **`determinarNivelAlerta()`**
   - Clasifica alertas en niveles: VERDE, AMARILLO, ROJO
   - VERDE: valor < umbral
   - AMARILLO: umbral <= valor <= 1.5x umbral
   - ROJO: valor > 1.5x umbral

#### Utilidades

6. **`calculateR11Kpis()`**
   - Calcula KPIs del reporte: total, por nivel, por tipo
   - Calcula porcentaje de alertas críticas

7. **`determinarEstadoGeneral()`**
   - Determina estado general del sistema basándose en alertas
   - Prioriza: ROJO > AMARILLO > VERDE

8. **`enviarNotificacionesMock()`**
   - Guarda notificaciones en `storage/app/mocks/notifications.json`
   - Destinatarios: OPERACIONES_PUERTO, PLANIFICADOR_PUERTO
   - Incluye acciones recomendadas

### 3. ReportController - Endpoints

**Ubicación:** `app/Http/Controllers/ReportController.php`

#### Endpoints Implementados

1. **`r11(Request $request): View`**
   - Renderiza la vista HTML del reporte R11
   - Aplica filtros de fecha y umbrales
   - Requiere permiso: `KPI_READ`

2. **`r11Api(Request $request)`**
   - Retorna JSON con datos de alertas
   - Diseñado para polling con Alpine.js
   - Actualización cada 5 minutos

### 4. Rutas

**Ubicación:** `routes/web.php`

```php
Route::prefix('analytics')->group(function () {
    Route::get('/early-warning', [ReportController::class, 'r11'])
        ->middleware('permission:KPI_READ')
        ->name('reports.r11');
    
    Route::get('/early-warning/api', [ReportController::class, 'r11Api'])
        ->middleware('permission:KPI_READ')
        ->name('reports.r11.api');
});
```

### 5. Vista

**Ubicación:** `resources/views/reports/analytics/early-warning.blade.php`

Interfaz completa con:
- Indicador visual de estado general (semáforo)
- KPIs en tarjetas
- Filtros configurables
- Listado de alertas con detalles
- Acciones recomendadas por alerta
- Auto-refresh cada 5 minutos

## Características Principales

### Tipos de Alertas

1. **Congestión de Muelles**
   - Umbral: Utilización > 85%
   - Información: Muelle, utilización actual, umbral
   - Acciones: Revisar programación, redistribuir, aumentar recursos

2. **Acumulación de Camiones**
   - Umbral: Espera promedio > 4 horas
   - Información: Empresa, espera promedio, citas afectadas
   - Acciones: Aumentar capacidad, contactar empresa, revisar programación

### Niveles de Severidad

- **VERDE**: Valor < umbral (Normal)
- **AMARILLO**: Umbral <= valor <= 1.5x umbral (Precaución)
- **ROJO**: Valor > 1.5x umbral (Crítico)

### KPIs Calculados

- Total de alertas
- Alertas por nivel (Rojo, Amarillo, Verde)
- Alertas por tipo (Congestión, Acumulación)
- Porcentaje de alertas críticas

### Notificaciones Mock

Las alertas se guardan en `storage/app/mocks/notifications.json` con:
- Timestamp de detección
- Destinatarios (OPERACIONES_PUERTO, PLANIFICADOR_PUERTO)
- Detalles de cada alerta
- Acciones recomendadas

## Filtros Disponibles

- **Fecha Desde**: Inicio del período de análisis
- **Fecha Hasta**: Fin del período de análisis
- **Umbral Congestión**: Porcentaje de utilización (default: 85%)
- **Umbral Acumulación**: Horas de espera (default: 4)

## Permisos Requeridos

- `KPI_READ`: Requerido para acceder a R11

## Roles Autorizados

- DIRECTIVO
- ANALISTA
- ADMIN
- AUDITOR
- OPERACIONES_PUERTO
- PLANIFICADOR_PUERTO

## Validación

✓ Sintaxis PHP válida (PSR-12)
✓ Tipos estrictos habilitados
✓ Sin errores de diagnóstico
✓ Integración con ReportService
✓ Rutas configuradas correctamente
✓ Vista Blade completa
✓ Endpoints API implementados

## Próximos Pasos

1. Crear tests unitarios para métodos de cálculo
2. Crear tests de integración para endpoints
3. Implementar tests de propiedades para validación de alertas
4. Configurar umbrales en base de datos (tabla `analytics.settings`)
5. Implementar persistencia de alertas en tabla `analytics.alerts`
6. Integrar con sistema de notificaciones real (email, SMS)

## Notas Técnicas

- El método `generateR11()` es stateless y puede ser llamado múltiples veces
- Las notificaciones mock se acumulan en el archivo JSON
- El auto-refresh de la vista es configurable (actualmente 5 minutos)
- Los cálculos de utilización consideran solo llamadas activas
- Los tiempos de espera se calculan desde `hora_llegada` hasta primer evento de gate
