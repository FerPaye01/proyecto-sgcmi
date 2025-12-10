# Guía de Migración de PHP Simple a Laravel Blade

## ¿Por qué migrar?

Las páginas PHP en `public/pages/` son simples pero limitadas. Laravel Blade ofrece:

✅ **Autenticación integrada** - Sistema de login robusto  
✅ **Middleware de permisos** - Control de acceso automático  
✅ **Componentes reutilizables** - DRY (Don't Repeat Yourself)  
✅ **Assets compilados** - Vite con Alpine.js y Tailwind  
✅ **Tablas interactivas** - Componente `<x-interactive-table>` listo  
✅ **Políticas de seguridad** - RBAC enforced  
✅ **Testing** - PHPUnit integrado  

## Rutas Ya Configuradas

Todas las rutas están en `routes/web.php`:

### Autenticación
- `GET /login` → Login form
- `POST /login` → Procesar login
- `POST /logout` → Cerrar sesión

### Dashboard
- `GET /` → Dashboard principal

### Llamadas de Naves (Portuario)
- `GET /portuario/vessel-calls` → Listado
- `GET /portuario/vessel-calls/create` → Formulario crear
- `POST /portuario/vessel-calls` → Guardar
- `GET /portuario/vessel-calls/{id}` → Ver detalle
- `GET /portuario/vessel-calls/{id}/edit` → Formulario editar
- `PATCH /portuario/vessel-calls/{id}` → Actualizar

### Citas (Terrestre)
- `GET /terrestre/appointments` → Listado
- `GET /terrestre/appointments/create` → Formulario crear
- `POST /terrestre/appointments` → Guardar

### Trámites (Aduanas)
- `GET /aduanas/tramites` → Listado
- `GET /aduanas/tramites/create` → Formulario crear
- `POST /aduanas/tramites` → Guardar
- `GET /aduanas/tramites/{id}` → Ver detalle

### Reportes
- `GET /reports/port/schedule-vs-actual` → R1
- `GET /reports/port/berth-utilization` → R3
- `GET /reports/road/waiting-time` → R4
- `GET /reports/road/appointments-compliance` → R5
- `GET /reports/road/gate-productivity` → R6
- `GET /reports/cus/status-by-vessel` → R7
- `GET /reports/cus/dispatch-time` → R8
- `GET /reports/cus/doc-incidents` → R9
- `GET /reports/kpi/panel` → R10
- `GET /reports/analytics/early-warning` → R11
- `GET /reports/sla/compliance` → R12

## Paso 1: Actualizar bootstrap/app.php

Asegúrate de que las rutas de autenticación estén cargadas:

```php
// En bootstrap/app.php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
    then: function () {
        Route::middleware('web')
            ->group(base_path('routes/auth.php'));
    }
)
```

## Paso 2: Crear Vista de Login

```blade
{{-- resources/views/auth/login.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SGCMI</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-sgcmi-blue-900">🚢 SGCMI</h1>
                <p class="text-gray-600 mt-2">Sistema de Gestión de Comercio Marítimo Internacional</p>
            </div>

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}">
                @csrf

                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        Email
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="{{ old('email') }}"
                        required 
                        autofocus
                        class="input-field w-full"
                    >
                </div>

                <div class="mb-6">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        Contraseña
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                        class="input-field w-full"
                    >
                </div>

                <div class="mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="remember" class="rounded border-gray-300">
                        <span class="ml-2 text-sm text-gray-600">Recordarme</span>
                    </label>
                </div>

                <button type="submit" class="btn-primary w-full">
                    Iniciar Sesión
                </button>
            </form>
        </div>
    </div>
</body>
</html>
```

## Paso 3: Usar el Layout Principal

Todas las vistas deben extender `layouts/app.blade.php`:

```blade
@extends('layouts.app')

@section('title', 'Llamadas de Naves')

@section('content')
<div class="max-w-7xl mx-auto">
    <h1 class="text-3xl font-bold mb-6">Llamadas de Naves</h1>
    
    {{-- Tu contenido aquí --}}
</div>
@endsection
```

## Paso 4: Usar Tablas Interactivas

### Preparar datos en el Controlador

```php
public function index()
{
    $vesselCalls = VesselCall::with(['vessel', 'berth'])
        ->latest('eta')
        ->get();

    // Preparar datos para tabla interactiva
    $tableData = $vesselCalls->map(function ($call) {
        return [
            'id' => $call->id,
            'nave' => $call->vessel->name,
            'imo' => $call->vessel->imo,
            'viaje' => $call->viaje_id,
            'muelle' => $call->berth->name ?? 'N/A',
            'eta' => $call->eta?->format('Y-m-d H:i'),
            'estado' => $call->estado_llamada,
        ];
    })->toArray();

    $tableHeaders = [
        ['key' => 'id', 'label' => 'ID', 'sortable' => true],
        ['key' => 'nave', 'label' => 'Nave', 'sortable' => true],
        ['key' => 'imo', 'label' => 'IMO', 'sortable' => true],
        ['key' => 'viaje', 'label' => 'Viaje', 'sortable' => true],
        ['key' => 'muelle', 'label' => 'Muelle', 'sortable' => true],
        ['key' => 'eta', 'label' => 'ETA', 'sortable' => true],
        [
            'key' => 'estado',
            'label' => 'Estado',
            'sortable' => true,
            'format' => 'function(val) {
                const badges = {
                    "PROGRAMADA": "<span class=\"badge-info\">PROGRAMADA</span>",
                    "EN_TRANSITO": "<span class=\"badge-warning\">EN_TRANSITO</span>",
                    "ATRACADA": "<span class=\"badge-success\">ATRACADA</span>"
                };
                return badges[val] || val;
            }'
        ],
    ];

    return view('portuario.vessel-calls.index', compact('tableData', 'tableHeaders'));
}
```

### Usar en la Vista

```blade
@extends('layouts.app')

@section('title', 'Llamadas de Naves')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-sgcmi-blue-900">Llamadas de Naves</h1>
        
        @can('SCHEDULE_WRITE')
            <a href="{{ route('vessel-calls.create') }}" class="btn-primary">
                Nueva Llamada
            </a>
        @endcan
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Listado de Llamadas</h3>
        </div>
        <div class="card-body">
            <x-interactive-table 
                :headers="$tableHeaders"
                :data="$tableData"
                :searchable="true"
                :sortable="true"
                :paginate="true"
                :perPage="10"
                :columnToggle="true"
            />
        </div>
    </div>
</div>
@endsection
```

## Paso 5: Compilar Assets

```bash
npm run build
```

Para desarrollo con hot-reload:
```bash
npm run dev
```

## Paso 6: Acceder al Sistema

1. **Iniciar servidor:**
   ```bash
   php artisan serve
   ```

2. **Acceder:**
   - URL: `http://localhost:8000`
   - Login: `http://localhost:8000/login`

3. **Credenciales de prueba:**
   - Admin: `admin@sgcmi.gob.pe` / `password`
   - Operador: `operador@sgcmi.gob.pe` / `password`
   - Transportista: `transportista@acme.com` / `password`

## Comparación: PHP vs Blade

### Antes (PHP Simple)

```php
<?php
// public/pages/vessel-calls.php
if(!hasPermission($pdo,$currentUser['id'],'SCHEDULE_READ'))die('Acceso denegado');
$vesselCalls=$pdo->query("SELECT * FROM vessel_call")->fetchAll();
include 'layout/header.php';
?>
<table class="data-table">
    <?php foreach($vesselCalls as $vc): ?>
        <tr>
            <td><?=$vc['id']?></td>
            <td><?=$vc['vessel_name']?></td>
        </tr>
    <?php endforeach; ?>
</table>
<?php include 'layout/footer.php'; ?>
```

### Después (Laravel Blade)

```php
// app/Http/Controllers/VesselCallController.php
public function index()
{
    $this->authorize('viewAny', VesselCall::class);
    
    $vesselCalls = VesselCall::with('vessel')->get();
    
    return view('portuario.vessel-calls.index', compact('vesselCalls'));
}
```

```blade
{{-- resources/views/portuario/vessel-calls/index.blade.php --}}
@extends('layouts.app')

@section('content')
<x-interactive-table 
    :headers="$tableHeaders"
    :data="$tableData"
/>
@endsection
```

## Ventajas de Blade

### 1. Seguridad
- ✅ CSRF protection automático
- ✅ XSS protection con `{{ }}` 
- ✅ SQL injection prevention (Eloquent)
- ✅ Políticas de autorización

### 2. Mantenibilidad
- ✅ Componentes reutilizables
- ✅ Layouts heredables
- ✅ Directivas Blade (@if, @foreach, @can)
- ✅ Código más limpio

### 3. Testing
- ✅ PHPUnit integrado
- ✅ Feature tests
- ✅ Browser tests (Dusk)

### 4. Performance
- ✅ Vistas compiladas y cacheadas
- ✅ Assets optimizados con Vite
- ✅ Lazy loading de relaciones

## Migración Gradual

No necesitas migrar todo de una vez:

1. **Fase 1:** Login y Dashboard (ya hecho)
2. **Fase 2:** Vessel Calls (ya hecho)
3. **Fase 3:** Appointments y Gate Events
4. **Fase 4:** Trámites Aduaneros
5. **Fase 5:** Reportes (R1-R12)

Mientras tanto, ambos sistemas pueden coexistir:
- PHP simple: `http://localhost:8000/index.php?page=vessel-calls`
- Laravel Blade: `http://localhost:8000/portuario/vessel-calls`

## Troubleshooting

### Error: "Target class [LoginController] does not exist"
```bash
composer dump-autoload
```

### Error: "Vite manifest not found"
```bash
npm run build
```

### Error: "SQLSTATE[HY000] [2002] Connection refused"
Verificar `.env`:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=sgcmi
DB_USERNAME=postgres
DB_PASSWORD=tu_password
```

### Las tablas no son interactivas
1. Verificar que assets estén compilados: `npm run build`
2. Verificar que Alpine.js esté cargado en `app.blade.php`
3. Revisar consola del navegador (F12)

## Próximos Pasos

1. ✅ Crear `LoginController`
2. ✅ Crear vista `auth/login.blade.php`
3. ✅ Actualizar `bootstrap/app.php` para cargar `routes/auth.php`
4. ✅ Compilar assets: `npm run build`
5. ✅ Probar login en `http://localhost:8000/login`
6. ✅ Acceder a vessel-calls: `http://localhost:8000/portuario/vessel-calls`

## Documentación Adicional

- `INTERACTIVE_TABLES_GUIDE.md` - Guía completa de tablas interactivas
- `FRONTEND_INTERACTIVITY_IMPLEMENTATION.md` - Detalles de implementación
- `QUICK_START.md` - Inicio rápido del sistema
