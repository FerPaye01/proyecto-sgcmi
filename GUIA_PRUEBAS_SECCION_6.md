# Guía de Pruebas - Sección 6: Cargo Management & Yard Operations

## Resumen de Funcionalidades Implementadas

La Sección 6 incluye:
- ✅ **6.1** CargoManagementController
- ✅ **6.2** YardManagementController  
- ✅ **6.3** TarjaController
- ✅ **6.4** WeighingController (recién implementado)

---

## Opción 1: Prueba Rápida con Scripts de Verificación

### 1. Verificar Controladores y Rutas

```bash
cd sgcmi

# Verificar TarjaController
php verify_tarja_controller.php

# Verificar WeighingController
php verify_weighing_controller.php

# Verificar modelos de Cargo
php verify_cargo_models.php

# Verificar tablas de base de datos
php verify_cargo_tables.php
```

### 2. Listar Rutas Disponibles

```bash
# Ver todas las rutas de cargo management
php artisan route:list --name=tarja
php artisan route:list --name=weighing
php artisan route:list --name=yard
```

---

## Opción 2: Prueba Manual en el Navegador

### Paso 1: Iniciar el Servidor

```bash
cd sgcmi
INICIAR_SERVIDOR.bat
```

O manualmente:
```bash
php -S localhost:8000 -t public
```

### Paso 2: Iniciar Sesión

1. Abrir navegador: `http://localhost:8000`
2. Iniciar sesión con credenciales de prueba:
   - **Usuario:** `admin@sgcmi.local`
   - **Password:** `password123`

### Paso 3: Probar Módulo de Tarja (6.3)

#### Ver Lista de Tarjas
```
URL: http://localhost:8000/portuario/tarja
```

**Qué verificar:**
- ✓ Se muestra tabla con notas de tarja existentes
- ✓ Filtros funcionan (por nave, fecha, condición, inspector)
- ✓ Paginación funciona
- ✓ Botón "New Tarja Note" visible

#### Crear Nueva Tarja
```
URL: http://localhost:8000/portuario/tarja/create
```

**Qué verificar:**
- ✓ Formulario se carga correctamente
- ✓ Dropdown de cargo items poblado
- ✓ Número de tarja auto-generado (formato: TN-YYYYMMDD-####)
- ✓ Fecha/hora por defecto es actual
- ✓ Campo de condición tiene opciones: BUENO, DAÑADO, FALTANTE

**Datos de prueba:**
```
Cargo Item: [Seleccionar cualquiera]
Tarja Number: TN-20241211-0001
Tarja Date: [Fecha actual]
Inspector Name: Juan Pérez
Observations: Inspección de rutina - todo en orden
Condition: BUENO
```

**Resultado esperado:**
- Redirección a lista de tarjas
- Mensaje de éxito: "Tarja note registered successfully"
- Nueva tarja aparece en la lista

---

### Paso 4: Probar Módulo de Pesaje (6.4)

#### Ver Lista de Tickets de Pesaje
```
URL: http://localhost:8000/portuario/weighing
```

**Qué verificar:**
- ✓ Se muestra tabla con tickets de pesaje
- ✓ Columnas: Ticket Number, Weigh Date, Cargo Item, Vessel, Gross, Tare, **Net** (en azul)
- ✓ Filtros funcionan (por nave, fecha, balanza, operador)
- ✓ Botón "New Weigh Ticket" visible

#### Crear Nuevo Ticket de Pesaje
```
URL: http://localhost:8000/portuario/weighing/create
```

**Qué verificar:**
- ✓ Formulario se carga correctamente
- ✓ Dropdown de cargo items poblado
- ✓ Número de ticket auto-generado (formato: WT-YYYYMMDD-####)
- ✓ **Cálculo en tiempo real del peso neto** (Alpine.js)

**Datos de prueba:**
```
Cargo Item: [Seleccionar cualquiera]
Ticket Number: WT-20241211-0001
Weigh Date: [Fecha/hora actual]
Gross Weight: 5000.50
Tare Weight: 1250.25
Scale ID: SCALE-01
Operator Name: [Tu nombre]
```

**Resultado esperado:**
1. **Mientras escribes los pesos:**
   - El campo "Net Weight" se actualiza automáticamente
   - Debe mostrar: 3750.25 kg (5000.50 - 1250.25)

2. **Al enviar el formulario:**
   - Redirección a lista de tickets
   - Mensaje de éxito: "Weigh ticket registered successfully. Net weight: 3,750.25 kg"
   - Nuevo ticket aparece en la lista con peso neto calculado

---

### Paso 5: Probar Yard Management (6.2)

#### Ver Mapa del Patio
```
URL: http://localhost:8000/portuario/yard/map
```

**Qué verificar:**
- ✓ Se muestra mapa visual del patio
- ✓ Ubicaciones con estado (ocupado/disponible)
- ✓ Filtros por zona y tipo de ubicación

#### Ver Ubicaciones Disponibles
```
URL: http://localhost:8000/portuario/yard/available-locations
```

**Qué verificar:**
- ✓ Lista de ubicaciones disponibles
- ✓ Información de capacidad
- ✓ Filtros funcionan

---

## Opción 3: Pruebas Automatizadas con PHPUnit

### Ejecutar Tests Unitarios

```bash
cd sgcmi

# Ejecutar todos los tests de cargo management
php artisan test --filter=CargoManagement

# Ejecutar test específico de modelos
php artisan test tests/Unit/CargoManagementModelsTest.php

# Ver cobertura detallada
php artisan test --coverage
```

### Tests Disponibles

1. **CargoManagementModelsTest.php**
   - Valida relaciones entre modelos
   - Verifica cálculo automático de peso neto
   - Prueba factories

---

## Opción 4: Prueba con Datos de Ejemplo

### Generar Datos de Prueba

```bash
cd sgcmi

# Generar datos de cargo completos
php generate_cargo_test_data.php
```

Este script crea:
- 5 manifiestos de carga
- 20 items de carga
- 10 notas de tarja
- 10 tickets de pesaje
- Ubicaciones de patio

### Verificar Datos Generados

```bash
# Ver datos en la base de datos
php public/db-viewer.php
```

O consultar directamente:
```sql
-- Contar registros
SELECT 'cargo_manifest' as tabla, COUNT(*) as total FROM portuario.cargo_manifest
UNION ALL
SELECT 'cargo_item', COUNT(*) FROM portuario.cargo_item
UNION ALL
SELECT 'tarja_note', COUNT(*) FROM portuario.tarja_note
UNION ALL
SELECT 'weigh_ticket', COUNT(*) FROM portuario.weigh_ticket;
```

---

## Opción 5: Prueba de Integración Completa

### Flujo Completo de Cargo Management

```bash
cd sgcmi
php test_cargo_complete_workflow.php
```

Este script prueba el flujo completo:
1. Crear manifiesto de carga
2. Agregar items de carga
3. Asignar ubicación en patio
4. Registrar tarja
5. Registrar pesaje (con cálculo automático)
6. Verificar trazabilidad

---

## Verificaciones Clave por Funcionalidad

### ✅ TarjaController (6.3)

**Checklist:**
- [ ] Lista de tarjas se carga sin errores
- [ ] Filtros funcionan correctamente
- [ ] Formulario de creación se carga
- [ ] Se puede crear nueva tarja
- [ ] Validación funciona (campos requeridos)
- [ ] Auditoría registra la creación
- [ ] Relaciones con cargo_item funcionan

**Comando de verificación:**
```bash
php verify_tarja_controller.php
```

---

### ✅ WeighingController (6.4)

**Checklist:**
- [ ] Lista de tickets se carga sin errores
- [ ] Filtros funcionan correctamente
- [ ] Formulario de creación se carga
- [ ] **Cálculo en tiempo real funciona (Alpine.js)**
- [ ] **Cálculo automático al guardar funciona (Model)**
- [ ] Validación funciona (pesos >= 0)
- [ ] Auditoría registra la creación
- [ ] Peso neto = peso bruto - tara

**Comando de verificación:**
```bash
php verify_weighing_controller.php
```

**Prueba manual del cálculo:**
```bash
php -r "
require 'vendor/autoload.php';
\$app = require 'bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

\$ticket = new \App\Models\WeighTicket([
    'gross_weight_kg' => 1000,
    'tare_weight_kg' => 250
]);

echo 'Gross: ' . \$ticket->gross_weight_kg . ' kg' . PHP_EOL;
echo 'Tare: ' . \$ticket->tare_weight_kg . ' kg' . PHP_EOL;
echo 'Expected Net: 750 kg' . PHP_EOL;
"
```

---

### ✅ YardManagementController (6.2)

**Checklist:**
- [ ] Mapa de patio se carga
- [ ] Ubicaciones disponibles se listan
- [ ] Se puede mover carga entre ubicaciones
- [ ] Verificación de precintos funciona
- [ ] Validación de capacidad funciona

---

### ✅ CargoManagementController (6.1)

**Checklist:**
- [ ] Se puede registrar manifiesto
- [ ] Se pueden agregar items de carga
- [ ] Asignación de ubicación funciona
- [ ] Tracking de movimientos funciona

---

## Casos de Prueba Específicos

### Caso 1: Cálculo Automático de Peso Neto

**Objetivo:** Verificar que el peso neto se calcula correctamente

**Pasos:**
1. Ir a `/portuario/weighing/create`
2. Ingresar:
   - Gross Weight: 5000.75
   - Tare Weight: 1200.50
3. **Verificar en pantalla:** Net Weight muestra 3800.25 kg
4. Guardar el ticket
5. **Verificar en base de datos:**
   ```sql
   SELECT ticket_number, gross_weight_kg, tare_weight_kg, net_weight_kg
   FROM portuario.weigh_ticket
   ORDER BY created_at DESC
   LIMIT 1;
   ```
6. **Resultado esperado:** net_weight_kg = 3800.25

---

### Caso 2: Validación de Datos

**Objetivo:** Verificar que las validaciones funcionan

**Pasos:**
1. Ir a `/portuario/weighing/create`
2. Intentar guardar sin llenar campos
3. **Resultado esperado:** Mensajes de error para campos requeridos
4. Ingresar peso negativo: -100
5. **Resultado esperado:** Error de validación "must be at least 0"

---

### Caso 3: Filtros y Búsqueda

**Objetivo:** Verificar que los filtros funcionan

**Pasos:**
1. Ir a `/portuario/weighing`
2. Aplicar filtro por fecha
3. **Resultado esperado:** Solo tickets en ese rango
4. Aplicar filtro por balanza (Scale ID)
5. **Resultado esperado:** Solo tickets de esa balanza

---

## Troubleshooting

### Problema: "Class not found"
**Solución:**
```bash
composer dump-autoload
```

### Problema: "Route not found"
**Solución:**
```bash
php artisan route:clear
php artisan route:cache
```

### Problema: "View not found"
**Solución:**
```bash
php artisan view:clear
```

### Problema: No hay datos para probar
**Solución:**
```bash
php generate_cargo_test_data.php
```

---

## Resumen de URLs para Pruebas Manuales

| Funcionalidad | URL | Método |
|--------------|-----|--------|
| Lista Tarjas | `/portuario/tarja` | GET |
| Crear Tarja | `/portuario/tarja/create` | GET |
| Guardar Tarja | `/portuario/tarja` | POST |
| Lista Pesajes | `/portuario/weighing` | GET |
| Crear Pesaje | `/portuario/weighing/create` | GET |
| Guardar Pesaje | `/portuario/weighing` | POST |
| Mapa Patio | `/portuario/yard/map` | GET |
| Ubicaciones Disponibles | `/portuario/yard/available-locations` | GET |

---

## Checklist Final de Validación

### Funcionalidad Básica
- [ ] Todos los controladores cargan sin errores
- [ ] Todas las vistas se renderizan correctamente
- [ ] Todas las rutas están registradas
- [ ] No hay errores de sintaxis (diagnostics)

### Funcionalidad de Negocio
- [ ] Se pueden crear tarjas
- [ ] Se pueden crear tickets de pesaje
- [ ] **Peso neto se calcula automáticamente**
- [ ] Filtros funcionan en todas las listas
- [ ] Paginación funciona

### Seguridad y Auditoría
- [ ] Validaciones funcionan
- [ ] Auditoría registra operaciones
- [ ] Autenticación requerida
- [ ] No hay errores de SQL injection

### Requisitos del Diseño
- [ ] **Property 8:** Tarja note completeness ✓
- [ ] **Property 9:** Weigh ticket calculation correctness ✓
- [ ] **Requirement 2.3:** Tarja registration ✓
- [ ] **Requirement 2.4:** Weigh ticket with automatic calculation ✓

---

## Próximos Pasos

Una vez validada la Sección 6, puedes continuar con:

**Sección 7: Cargo Management Views**
- 7.1 Crear vistas de manifiesto de carga
- 7.2 Crear vistas de yard management
- 7.3 Crear vistas de tarja y pesaje ✅ (ya completado)

---

## Soporte

Si encuentras algún problema durante las pruebas:

1. Revisa los logs: `storage/logs/laravel.log`
2. Ejecuta los scripts de verificación
3. Consulta la documentación: `WEIGHING_CONTROLLER_IMPLEMENTATION.md`
4. Verifica la base de datos con: `php public/db-viewer.php`

---

**Última actualización:** 11 de diciembre de 2024  
**Versión:** 1.0  
**Estado:** Sección 6 completada y lista para pruebas
