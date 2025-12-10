# Comando KPI Calculator

## Descripción

El comando `php artisan kpi:calculate` calcula KPIs agregados del sistema y los almacena en la tabla `analytics.kpi_value` para análisis histórico y reportes ejecutivos.

## Uso

```bash
php artisan kpi:calculate [--period=PERIOD] [--force]
```

### Opciones

- `--period=PERIOD`: Periodo a calcular (default: `today`)
  - `today`: Calcula KPIs para el día actual
  - `yesterday`: Calcula KPIs para el día anterior
  - `week`: Calcula KPIs para hace una semana
  - `month`: Calcula KPIs para hace un mes

- `--force`: Fuerza el recálculo incluso si ya existen valores para el periodo

### Ejemplos

```bash
# Calcular KPIs del día actual
php artisan kpi:calculate

# Calcular KPIs de ayer
php artisan kpi:calculate --period=yesterday

# Recalcular KPIs del día actual (sobrescribe valores existentes)
php artisan kpi:calculate --force

# Calcular KPIs de hace una semana
php artisan kpi:calculate --period=week
```

## KPIs Calculados

El comando calcula los siguientes KPIs:

### 1. Turnaround Time (turnaround_h)
- **Descripción**: Tiempo promedio de permanencia de naves en puerto
- **Fuente**: `portuario.vessel_call`
- **Cálculo**: Promedio de (ATD - ATA) para todas las naves que finalizaron en el periodo
- **Meta**: 48 horas
- **Extra**: Incluye count, min, max

### 2. Tiempo de Espera de Camiones (espera_camion_h)
- **Descripción**: Tiempo promedio de espera de camiones desde llegada hasta primer evento
- **Fuente**: `terrestre.appointment`
- **Cálculo**: Promedio de (primer_evento - hora_llegada) para appointments atendidas
- **Meta**: 2 horas
- **Extra**: Incluye count, min, max

### 3. Cumplimiento de Citas (cumpl_citas_pct)
- **Descripción**: Porcentaje de citas cumplidas a tiempo (±15 minutos)
- **Fuente**: `terrestre.appointment`
- **Cálculo**: (citas_a_tiempo / total_citas) * 100
- **Meta**: 85%
- **Extra**: Incluye total, a_tiempo, tarde

### 4. Trámites Completados (tramites_ok_pct)
- **Descripción**: Porcentaje de trámites aprobados sin incidencias
- **Fuente**: `aduanas.tramite`
- **Cálculo**: (tramites_aprobados / total_tramites) * 100
- **Meta**: 90%
- **Extra**: Incluye total, aprobados, rechazados, observados

## Comportamiento

### Sin --force
- Si ya existen valores de KPI para el periodo, el comando no recalcula
- Muestra un mensaje de advertencia indicando cuántos valores existen
- Retorna código de salida 0 (éxito)

### Con --force
- Elimina todos los valores existentes para el periodo
- Recalcula todos los KPIs
- Muestra cuántos valores fueron eliminados

### Sin Datos
- Si no hay datos para calcular un KPI específico, muestra una advertencia
- Continúa con los demás KPIs
- No crea valores en la base de datos para KPIs sin datos

## Programación Automática

Para ejecutar el comando automáticamente cada hora, agregar a `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('kpi:calculate')->hourly();
```

O configurar un cron job en el servidor:

```cron
0 * * * * cd /path/to/sgcmi && php artisan kpi:calculate >> /dev/null 2>&1
```

## Estructura de Datos

Los valores calculados se almacenan en `analytics.kpi_value`:

```sql
SELECT 
    kd.code,
    kd.name,
    kv.periodo,
    kv.valor,
    kv.meta,
    kv.fuente,
    kv.extra
FROM analytics.kpi_value kv
JOIN analytics.kpi_definition kd ON kv.kpi_id = kd.id
WHERE kv.periodo = CURRENT_DATE
ORDER BY kd.code;
```

## Tests

El comando incluye tests completos en `tests/Feature/CalculateKpiCommandTest.php`:

```bash
php artisan test --filter=CalculateKpiCommandTest
```

Tests incluidos:
- ✓ Calcula turnaround KPI correctamente
- ✓ Calcula tiempo de espera KPI correctamente
- ✓ Calcula cumplimiento de citas KPI correctamente
- ✓ Calcula trámites completados KPI correctamente
- ✓ No recalcula sin --force
- ✓ Recalcula con --force
- ✓ Maneja periodos inválidos
- ✓ Maneja ausencia de datos gracefully

## Troubleshooting

### "KPI xxx no encontrado en definiciones"
- Ejecutar seeder: `php artisan db:seed --class=AnalyticsSeeder`

### "No hay datos en el periodo"
- Verificar que existan registros con fechas en el periodo especificado
- Revisar que los datos tengan los campos requeridos (ata, atd, hora_llegada, etc.)

### Error de transacción
- Verificar conexión a base de datos
- Revisar logs en `storage/logs/laravel.log`
