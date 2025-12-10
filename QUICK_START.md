# SGCMI - Quick Start Guide

**Last Updated**: November 29, 2025  
**Status**: ✅ All tests passing (24/24)

---

## 🚀 Getting Started (5 Minutes)

### 1. Database Setup
```bash
# Option A: Laravel Migrations (Recommended)
php artisan migrate
php artisan db:seed

# Option B: Direct SQL
psql -U postgres -d sgcmi -f database/sql/run_all_migrations.sql
```

### 2. Verify Installation
```bash
# Run tests
php artisan test

# Validate database
psql -U postgres -d sgcmi -f database/sql/validate_system.sql
```

### 3. Start Development
```bash
# Terminal 1: Frontend build
npm run dev

# Terminal 2: Laravel server
php artisan serve
```

### 4. Access Application
- **Main App**: http://localhost:8000
- **Test Frontend**: http://localhost:8000/test-frontend (add route first)

---

## 👤 Demo Users

All users have password: `password123`

| Username | Role | Email | Permissions |
|----------|------|-------|-------------|
| admin | ADMIN | admin@sgcmi.pe | All permissions |
| planificador | PLANIFICADOR_PUERTO | planificador@sgcmi.pe | Schedule read/write |
| operaciones | OPERACIONES_PUERTO | operaciones@sgcmi.pe | Port reports |
| gates | OPERADOR_GATES | gates@sgcmi.pe | Appointments, gate events |
| transportista | TRANSPORTISTA | transportista@sgcmi.pe | Own appointments only |
| aduana | AGENTE_ADUANA | aduana@sgcmi.pe | Customs read |
| analista | ANALISTA | analista@sgcmi.pe | Reports, KPIs |
| directivo | DIRECTIVO | directivo@sgcmi.pe | Reports, KPIs (read) |
| auditor | AUDITOR | auditor@sgcmi.pe | Audit logs |

---

## 📁 Project Structure

```
sgcmi/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── VesselCallController.php ✅
│   │   │   └── AppointmentController.php ✅
│   │   ├── Middleware/
│   │   │   └── CheckPermission.php ✅
│   │   ├── Policies/
│   │   │   ├── VesselCallPolicy.php ✅
│   │   │   └── AppointmentPolicy.php ✅
│   │   └── Requests/
│   │       ├── StoreVesselCallRequest.php ✅
│   │       ├── UpdateVesselCallRequest.php ✅
│   │       ├── StoreAppointmentRequest.php ✅
│   │       └── UpdateAppointmentRequest.php ✅
│   ├── Models/ (19 models) ✅
│   └── Services/
│       └── AuditService.php ✅
├── database/
│   ├── factories/ (9 factories) ✅
│   ├── migrations/ (7 migrations) ✅
│   ├── seeders/ (6 seeders) ✅
│   └── sql/ (10 SQL scripts) ✅
├── resources/
│   ├── css/app.css ✅
│   ├── js/app.js ✅
│   └── views/
│       ├── layouts/app.blade.php ✅
│       ├── components/filter-panel.blade.php ✅
│       └── portuario/vessel-calls/
│           ├── index.blade.php ✅
│           └── create.blade.php ✅
└── tests/ (24 tests passing) ✅
```

---

## 🧪 Running Tests

```bash
# All tests
php artisan test

# Specific test file
php artisan test tests/Feature/VesselCallTest.php

# Specific test method
php artisan test --filter test_planificador_can_create_vessel_call

# With coverage (requires Xdebug)
php artisan test --coverage
```

---

## 🎨 Frontend Development

### Build Commands
```bash
# Development (with hot reload)
npm run dev

# Production build
npm run build
```

### Custom Tailwind Classes
```html
<!-- Buttons -->
<button class="btn-primary">Primary</button>
<button class="btn-secondary">Secondary</button>
<button class="btn-danger">Danger</button>

<!-- Cards -->
<div class="card">Content</div>

<!-- Inputs -->
<input type="text" class="input-field" />

<!-- Badges -->
<span class="badge-success">Success</span>
<span class="badge-warning">Warning</span>
<span class="badge-danger">Danger</span>
<span class="badge-info">Info</span>
```

### Alpine.js Components
```html
<!-- Report Filters -->
<div x-data="reportFilters()">
    <input type="date" x-model="filters.fecha_desde" />
    <button @click="applyFilters()">Apply</button>
</div>

<!-- Date Validator -->
<div x-data="dateValidator()">
    <input type="datetime-local" x-model="eta" @change="validateDates()" />
    <input type="datetime-local" x-model="etb" @change="validateDates()" />
    <span x-show="hasError('etb')" x-text="getError('etb')"></span>
</div>

<!-- Modal -->
<div x-data="modal()">
    <button @click="show()">Open</button>
    <div x-show="open" @click.away="hide()">Modal content</div>
</div>
```

---

## 🔐 Security Features

### PII Masking
```php
// Automatically masked in audit logs
$piiFields = ['placa', 'tramite_ext_id', 'password', 'token', 'secret'];
// Result: '***MASKED***'
```

### RBAC Usage
```php
// In controllers
$this->authorize('create', VesselCall::class);

// In Blade
@can('SCHEDULE_WRITE')
    <button>Create</button>
@endcan

// In routes
Route::middleware(['auth', 'permission:SCHEDULE_READ'])->group(function () {
    // Protected routes
});
```

### Audit Logging
```php
// In controllers
$this->auditService->log(
    action: 'CREATE',
    objectSchema: 'portuario',
    objectTable: 'vessel_call',
    objectId: $vesselCall->id,
    details: ['viaje_id' => $vesselCall->viaje_id]
);
```

---

## 📊 Database Queries

### Using Eloquent
```php
// Get vessel calls with relationships
$vesselCalls = VesselCall::with(['vessel', 'berth'])
    ->where('estado_llamada', 'PROGRAMADA')
    ->orderBy('eta')
    ->paginate(50);

// Company scoping for appointments
$appointments = Appointment::where('company_id', auth()->user()->company_id)
    ->with(['truck', 'vesselCall'])
    ->get();
```

### Direct SQL (if needed)
```bash
# Connect to database
psql -U postgres -d sgcmi

# Query examples
SELECT * FROM portuario.vessel_call;
SELECT * FROM admin.users u JOIN admin.user_roles ur ON u.id = ur.user_id;
SELECT * FROM audit.audit_log WHERE action = 'CREATE' ORDER BY event_ts DESC;
```

---

## 🐛 Troubleshooting

### Tests Failing
```bash
# Clear cache
php artisan config:clear
php artisan cache:clear

# Recreate test database
php artisan migrate:fresh --env=testing
php artisan db:seed --env=testing
```

### Frontend Not Loading
```bash
# Rebuild assets
npm run build

# Clear view cache
php artisan view:clear

# Check Vite is running
npm run dev
```

### Database Connection Issues
```bash
# Check .env file
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=sgcmi
DB_USERNAME=postgres
DB_PASSWORD=1234

# Test connection
php artisan tinker
>>> DB::connection()->getPdo();
```

---

## 📝 Common Tasks

### Create New Controller
```bash
php artisan make:controller TramiteController --resource
```

### Create New Model with Factory
```bash
php artisan make:model Gate -mf
# -m: migration, -f: factory
```

### Create New Policy
```bash
php artisan make:policy TramitePolicy --model=Tramite
```

### Create New Test
```bash
php artisan make:test TramiteTest --unit
php artisan make:test TramiteControllerTest
```

### Create New Blade View
```bash
# Create directory if needed
mkdir -p resources/views/aduanas/tramites

# Create view file
touch resources/views/aduanas/tramites/index.blade.php
```

---

## 📚 Documentation

- **Architecture**: See `.kiro/specs/sgcmi/design.md`
- **Tasks**: See `.kiro/specs/sgcmi/tasks.md`
- **Audit System**: See `AUDIT_IMPLEMENTATION.md`
- **Frontend**: See `FRONTEND_SETUP.md` and `TAILWIND_ALPINE_QUICKSTART.md`
- **Pipeline**: See `PIPELINE_SUCCESS_REPORT.md`

---

## 🎯 Next Development Steps

### Priority 1: Complete Sprint 1
1. Create edit/show views for vessel-calls
2. Implement ReportService with R1 method
3. Create ExportService (CSV, XLSX, PDF)
4. Add report controller with authorization

### Priority 2: Sprint 2-3
5. Implement TramiteController with CRUD
6. Implement GateEventController
7. Create all remaining CRUD views
8. Implement R2-R12 report methods

### Priority 3: Optimization
9. Add database indexes
10. Implement caching for KPIs
11. Add queue for exports
12. Implement rate limiting

---

## 💡 Tips

1. **Always use factories in tests** - Don't create records manually
2. **Use model-based validation** - `exists:App\Models\X,id` not `exists:schema.table,id`
3. **Check permissions in controllers** - Use `$this->authorize()` or policies
4. **Log all CUD operations** - Use AuditService in controllers
5. **Use Blade components** - Reuse filter-panel and other components
6. **Test after changes** - Run `php artisan test` frequently

---

## 🆘 Getting Help

1. Check documentation in project root
2. Review test files for usage examples
3. Check steering rules in `.kiro/steering/steering.json.md`
4. Review existing controllers for patterns

---

**Happy Coding! 🚀**

