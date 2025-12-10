# KPI Calculator - Quick Reference

## Command Syntax

```bash
php artisan kpi:calculate [--period=PERIOD] [--force]
```

## Options

| Option | Values | Default | Description |
|--------|--------|---------|-------------|
| `--period` | today, yesterday, week, month | today | Period to calculate KPIs for |
| `--force` | flag | false | Force recalculation even if values exist |

## Examples

```bash
# Calculate today's KPIs
php artisan kpi:calculate

# Calculate yesterday's KPIs
php artisan kpi:calculate --period=yesterday

# Calculate KPIs from one week ago
php artisan kpi:calculate --period=week

# Calculate KPIs from one month ago
php artisan kpi:calculate --period=month

# Force recalculation of today's KPIs
php artisan kpi:calculate --force

# Force recalculation of yesterday's KPIs
php artisan kpi:calculate --period=yesterday --force
```

## KPIs Calculated

### 1. turnaround_h
- **Description**: Average vessel turnaround time (hours)
- **Source**: portuario.vessel_call
- **Calculation**: Average of (ATD - ATA) for completed vessel calls
- **Meta**: 48 hours
- **Requires**: Vessel calls with both ATA and ATD

### 2. espera_camion_h
- **Description**: Average truck waiting time (hours)
- **Source**: terrestre.appointment
- **Calculation**: Average of (first_gate_event - hora_llegada)
- **Meta**: 2 hours
- **Requires**: Appointments with hora_llegada and gate events

### 3. cumpl_citas_pct
- **Description**: Appointment compliance percentage
- **Source**: terrestre.appointment
- **Calculation**: (on_time_appointments / total_appointments) × 100
- **Meta**: 85%
- **Classification**: On time = ±15 minutes

### 4. tramites_ok_pct
- **Description**: Customs completion percentage
- **Source**: aduanas.tramite
- **Calculation**: (approved_tramites / total_tramites) × 100
- **Meta**: 90%
- **Requires**: Tramites with fecha_fin

## Output

### Success
```
Iniciando cálculo de KPIs para periodo: today
Calculando KPIs para fecha: 2025-12-01
Calculando turnaround_h...
  ✓ turnaround_h: 24.50 horas (n=5)
Calculando espera_camion_h...
  ✓ espera_camion_h: 1.75 horas (n=12)
Calculando cumpl_citas_pct...
  ✓ cumpl_citas_pct: 87.50% (n=16)
Calculando tramites_ok_pct...
  ✓ tramites_ok_pct: 92.00% (n=25)
✓ KPIs calculados exitosamente
```

### No Data
```
Iniciando cálculo de KPIs para periodo: today
Calculando KPIs para fecha: 2025-12-01
Calculando turnaround_h...
No hay vessel_calls finalizadas en el periodo
Calculando espera_camion_h...
No hay appointments atendidas en el periodo
...
✓ KPIs calculados exitosamente
```

### Already Exists (without --force)
```
Iniciando cálculo de KPIs para periodo: today
Calculando KPIs para fecha: 2025-12-01
Ya existen 4 valores de KPI para este periodo.
Use --force para recalcular.
```

### With --force
```
Iniciando cálculo de KPIs para periodo: today
Calculando KPIs para fecha: 2025-12-01
Eliminados 4 valores existentes.
Calculando turnaround_h...
  ✓ turnaround_h: 24.50 horas (n=5)
...
✓ KPIs calculados exitosamente
```

## Cron Job Setup

### Hourly Calculation
```cron
0 * * * * cd /path/to/sgcmi && php artisan kpi:calculate >> /dev/null 2>&1
```

### Daily at Midnight
```cron
0 0 * * * cd /path/to/sgcmi && php artisan kpi:calculate >> /dev/null 2>&1
```

### Daily at 1 AM with Yesterday's Data
```cron
0 1 * * * cd /path/to/sgcmi && php artisan kpi:calculate --period=yesterday >> /dev/null 2>&1
```

### Laravel Scheduler (routes/console.php)
```php
use Illuminate\Support\Facades\Schedule;

// Every hour
Schedule::command('kpi:calculate')->hourly();

// Daily at midnight
Schedule::command('kpi:calculate')->daily();

// Daily at 1 AM with yesterday's data
Schedule::command('kpi:calculate', ['--period' => 'yesterday'])->dailyAt('01:00');
```

## Database Structure

### KPI Values Table
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

### Example Output
```
code              | name                        | periodo    | valor  | meta  | fuente                  | extra
------------------+-----------------------------+------------+--------+-------+-------------------------+------------------
cumpl_citas_pct   | Cumplimiento Citas (%)      | 2025-12-01 | 87.50  | 85.00 | terrestre.appointment   | {"total":16,...}
espera_camion_h   | Tiempo Espera Camión (h)    | 2025-12-01 | 1.75   | 2.00  | terrestre.appointment   | {"count":12,...}
tramites_ok_pct   | Trámites Completos (%)      | 2025-12-01 | 92.00  | 90.00 | aduanas.tramite         | {"total":25,...}
turnaround_h      | Turnaround Time (horas)     | 2025-12-01 | 24.50  | 48.00 | portuario.vessel_call   | {"count":5,...}
```

## Troubleshooting

### Error: "KPI xxx no encontrado en definiciones"
**Solution**: Run seeder
```bash
php artisan db:seed --class=AnalyticsSeeder
```

### Error: "No hay datos en el periodo"
**Causes**:
- No records exist for the specified period
- Records don't have required fields (ata, atd, hora_llegada, etc.)

**Solution**: Check data
```sql
-- Check vessel calls
SELECT COUNT(*) FROM portuario.vessel_call 
WHERE DATE(atd) = CURRENT_DATE AND ata IS NOT NULL AND atd IS NOT NULL;

-- Check appointments
SELECT COUNT(*) FROM terrestre.appointment 
WHERE DATE(hora_llegada) = CURRENT_DATE AND estado = 'ATENDIDA';

-- Check tramites
SELECT COUNT(*) FROM aduanas.tramite 
WHERE DATE(fecha_fin) = CURRENT_DATE AND estado = 'APROBADO';
```

### Error: Transaction failed
**Solution**: Check database connection and logs
```bash
# Check connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check logs
tail -f storage/logs/laravel.log
```

## Testing

### Run KPI Calculator Tests
```bash
# All KPI tests
php artisan test --filter=KpiCalculator

# Command tests only
php artisan test --filter=CalculateKpiCommandTest

# Service tests only
php artisan test --filter=KpiCalculatorTest

# Specific test
php artisan test --filter=test_command_calculates_turnaround_kpi
```

## Performance

### Execution Time
- Small dataset (<100 records): ~1-2 seconds
- Medium dataset (100-1000 records): ~3-5 seconds
- Large dataset (>1000 records): ~5-10 seconds

### Optimization Tips
1. Run during off-peak hours
2. Use indexes on date fields
3. Consider archiving old data
4. Monitor database performance

## Integration with Reports

KPI values are used in:
- **R10**: Panel de KPIs (displays current values)
- **R11**: Alertas Tempranas (compares against thresholds)
- **R12**: Cumplimiento SLAs (tracks compliance over time)

## API Access

### Get Latest KPIs
```php
use App\Models\KpiValue;
use App\Models\KpiDefinition;

$kpis = KpiValue::with('kpiDefinition')
    ->where('periodo', now()->toDateString())
    ->get();
```

### Get KPI History
```php
$turnaroundHistory = KpiValue::whereHas('kpiDefinition', function($q) {
    $q->where('code', 'turnaround_h');
})
->whereBetween('periodo', [now()->subDays(30), now()])
->orderBy('periodo')
->get();
```

### Compare with Meta
```php
$kpi = KpiValue::with('kpiDefinition')
    ->where('periodo', now()->toDateString())
    ->whereHas('kpiDefinition', function($q) {
        $q->where('code', 'turnaround_h');
    })
    ->first();

$isAboveMeta = $kpi->valor > $kpi->meta;
$percentageOfMeta = ($kpi->valor / $kpi->meta) * 100;
```

## Best Practices

1. **Schedule Regularly**: Run hourly or daily via cron
2. **Monitor Failures**: Set up alerts for command failures
3. **Validate Data**: Ensure source data quality
4. **Archive Old Values**: Keep last 12 months, archive older
5. **Document Changes**: Log any manual recalculations
6. **Test Before Production**: Validate calculations with known data
7. **Use Transactions**: Command uses transactions for data integrity
8. **Handle Errors Gracefully**: Command continues even if one KPI fails

## Related Documentation

- Full documentation: `KPI_CALCULATOR_COMMAND.md`
- Service implementation: `app/Services/KpiCalculator.php`
- Command implementation: `app/Console/Commands/CalculateKpiCommand.php`
- Tests: `tests/Feature/CalculateKpiCommandTest.php`
- Tests: `tests/Unit/KpiCalculatorTest.php`
