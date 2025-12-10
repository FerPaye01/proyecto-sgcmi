# Implementación de Tiempo de Ciclo (tiempo_ciclo_min)

## Descripción

El cálculo de `tiempo_ciclo_min` mide el tiempo promedio que tarda un camión desde su entrada hasta su salida en un gate. Este KPI es parte del Reporte R6 - Productividad de Gates.

## Implementación

### Ubicación del Código

- **Servicio**: `app/Services/ReportService.php`
- **Método principal**: `calculateTiemposCiclo(Collection $data): Collection`
- **Integración**: Método `calculateR6Kpis()` en `ReportService`

### Algoritmo

El método `calculateTiemposCiclo()` implementa la siguiente lógica:

1. **Agrupación por camión**: Los eventos se agrupan por `truck_id`
2. **Ordenamiento temporal**: Los eventos de cada camión se ordenan por `event_ts`
3. **Emparejamiento entrada-salida**: 
   - Se buscan pares consecutivos de ENTRADA → SALIDA
   - Si se encuentra una ENTRADA seguida de otra ENTRADA, la primera no tiene salida (se ignora)
   - Solo se calculan tiempos para pares completos
4. **Cálculo del tiempo**: `(timestamp_salida - timestamp_entrada) / 60` (en minutos)
5. **Promedio**: Se calcula el promedio de todos los tiempos de ciclo encontrados

### Ejemplo de Código

```php
private function calculateTiemposCiclo(Collection $data): Collection
{
    $tiemposCiclo = collect();
    
    // Agrupar eventos por camión
    $porCamion = $data->groupBy('truck_id');
    
    foreach ($porCamion as $truckId => $eventos) {
        // Ordenar por timestamp
        $eventosOrdenados = $eventos->sortBy('event_ts')->values();
        
        // Buscar pares entrada-salida consecutivos
        $i = 0;
        while ($i < $eventosOrdenados->count() - 1) {
            $actual = $eventosOrdenados[$i];
            
            if ($actual->action === 'ENTRADA') {
                // Buscar la siguiente salida para este camión
                for ($j = $i + 1; $j < $eventosOrdenados->count(); $j++) {
                    $siguiente = $eventosOrdenados[$j];
                    
                    if ($siguiente->action === 'SALIDA') {
                        // Calcular tiempo de ciclo en minutos
                        $tiempoCiclo = ($siguiente->event_ts->timestamp - $actual->event_ts->timestamp) / 60;
                        $tiemposCiclo->push($tiempoCiclo);
                        $i = $j; // Saltar al evento de salida
                        break;
                    } elseif ($siguiente->action === 'ENTRADA') {
                        // Entrada sin salida, ignorar
                        break;
                    }
                }
            }
            $i++;
        }
    }
    
    return $tiemposCiclo;
}
```

## Casos de Uso

### Caso 1: Par completo entrada-salida
```
Camión 1:
  - ENTRADA: 10:00
  - SALIDA: 10:30
Resultado: 30 minutos
```

### Caso 2: Entrada sin salida
```
Camión 1:
  - ENTRADA: 10:00
  - (sin salida)
Resultado: No se calcula (se ignora)
```

### Caso 3: Múltiples ciclos del mismo camión
```
Camión 1:
  - ENTRADA: 10:00
  - SALIDA: 10:30  → Ciclo 1: 30 min
  - ENTRADA: 11:00
  - SALIDA: 11:45  → Ciclo 2: 45 min
Resultado promedio: (30 + 45) / 2 = 37.5 minutos
```

### Caso 4: Entrada doble (sin salida intermedia)
```
Camión 1:
  - ENTRADA: 10:00
  - ENTRADA: 10:30  → Primera entrada ignorada
  - SALIDA: 11:00   → Ciclo: 30 min (desde segunda entrada)
```

## Tests

Los siguientes tests validan la implementación:

1. **test_r6_calculates_tiempo_ciclo_min_correctly**: Verifica el cálculo correcto del promedio
2. **test_r6_handles_entries_without_exit**: Verifica que entradas sin salida se ignoran
3. **test_r6_returns_empty_data_when_no_events**: Verifica manejo de datos vacíos

### Ejecutar Tests

```bash
php artisan test --filter=test_r6_calculates_tiempo_ciclo_min_correctly
php artisan test --filter=test_r6_handles_entries_without_exit
php artisan test --filter=test_r6
```

## Integración con Reporte R6

El KPI `tiempo_ciclo_min` se incluye en el reporte R6 junto con:

- `veh_x_hora`: Vehículos por hora (promedio)
- `picos_vs_capacidad`: Porcentaje de horas pico
- `horas_pico`: Lista de horas que superan el 80% de capacidad

### Ejemplo de Respuesta

```php
[
    'data' => Collection, // Eventos de gate
    'kpis' => [
        'veh_x_hora' => 5.25,
        'tiempo_ciclo_min' => 37.5,  // ← Tiempo de ciclo promedio
        'picos_vs_capacidad' => 33.33,
        'horas_pico' => [...]
    ],
    'productividad_por_hora' => [...]
]
```

## Requisitos Cumplidos

✅ **US-2.2**: Reporte R6 - Productividad de Gates
✅ **KPI tiempo_ciclo_min**: Promedio entrada-salida en minutos
✅ **Tests de integridad**: Verificación de pares entrada-salida
✅ **Manejo de casos edge**: Entradas sin salida, múltiples ciclos

## Referencias

- **Requirements**: `.kiro/specs/sgcmi/requirements.md` (US-2.2)
- **Design**: `.kiro/specs/sgcmi/design.md` (ReportService)
- **Tasks**: `.kiro/specs/sgcmi/tasks.md` (Sprint 2)
- **Tests**: `tests/Unit/ReportServiceTest.php`
