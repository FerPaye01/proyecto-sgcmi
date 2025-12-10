# SGCMI - Quick Start Guide

## System Status: ✅ OPERATIONAL

The SGCMI system is fully generated, configured, and ready for use.

---

## Local Development Setup

### Prerequisites
- PHP 8.3+
- PostgreSQL 14+
- Composer
- Node.js 18+

### Installation

```bash
# 1. Clone/navigate to project
cd sgcmi

# 2. Install PHP dependencies
composer install

# 3. Install Node dependencies
npm install

# 4. Configure environment
cp .env.example .env
# Edit .env with your database credentials:
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=sgcmi
# DB_USERNAME=postgres
# DB_PASSWORD=1234

# 5. Generate app key
php artisan key:generate

# 6. Run migrations
php artisan migrate

# 7. Seed database
php artisan db:seed

# 8. Build frontend assets
npm run build
# Or for development with hot reload:
npm run dev
```

---

## Running the Application

### Start Development Server
```bash
php artisan serve
```
Access at: http://localhost:8000

### Build Frontend Assets
```bash
# Production build
npm run build

# Development with hot reload
npm run dev
```

---

## Demo Users

All users have password: `password123`

| Username | Role | Email |
|----------|------|-------|
| admin | ADMIN | admin@sgcmi.pe |
| planificador | PLANIFICADOR_PUERTO | planificador@sgcmi.pe |
| operaciones | OPERACIONES_PUERTO | operaciones@sgcmi.pe |
| gates | OPERADOR_GATES | gates@sgcmi.pe |
| transportista | TRANSPORTISTA | transportista@sgcmi.pe |
| aduana | AGENTE_ADUANA | aduana@sgcmi.pe |
| analista | ANALISTA | analista@sgcmi.pe |
| directivo | DIRECTIVO | directivo@sgcmi.pe |
| auditor | AUDITOR | auditor@sgcmi.pe |

---

## Key URLs

### Modules
- **Portuario**: /portuario/vessel-calls
- **Terrestre**: /terrestre/appointments
- **Aduanas**: /aduanas/tramites
- **Reportes**: /reports

### Reports (R1-R12)
- R1: /reports/port/schedule-vs-actual
- R3: /reports/port/berth-utilization
- R4: /reports/road/waiting-time
- R5: /reports/road/appointments-compliance
- R6: /reports/road/gate-productivity
- R7: /reports/cus/status-by-vessel
- R8: /reports/cus/dispatch-time
- R9: /reports/cus/doc-incidents
- R10: /reports/kpi/panel
- R11: /reports/analytics/early-warning
- R12: /reports/sla/compliance

### Admin
- Settings: /admin/settings/thresholds

---

## Database

### Connection
```
Host: 127.0.0.1
Port: 5432
Database: sgcmi
User: postgres
Password: 1234
```

### Schemas
- `admin` - Users, roles, permissions
- `portuario` - Vessels, berths, calls
- `terrestre` - Companies, trucks, appointments, gates
- `aduanas` - Trámites, entidades
- `analytics` - KPIs, SLAs, actors
- `audit` - Audit logs
- `reports` - Report views (optional)

### Useful Commands
```bash
# Check migration status
php artisan migrate:status

# Reset database (WARNING: deletes all data)
php artisan migrate:refresh --seed

# Seed specific seeder
php artisan db:seed --class=PortuarioSeeder

# Access database
psql -U postgres -d sgcmi
```

---

## Testing

### Run All Tests
```bash
php artisan test
```

### Run Specific Test
```bash
php artisan test tests/Feature/VesselCallTest.php
```

### Run with Coverage
```bash
php artisan test --coverage
```

---

## KPI Calculation

### Manual Calculation
```bash
php artisan kpi:calculate
```

### Scheduled Calculation (Production)
Add to crontab:
```
* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
```

---

## Troubleshooting

### Database Connection Error
```bash
# Verify PostgreSQL is running
psql -U postgres -c "SELECT version();"

# Check .env database settings
cat .env | grep DB_
```

### Frontend Assets Not Loading
```bash
# Rebuild assets
npm run build

# Clear cache
php artisan cache:clear
php artisan view:clear
```

### Permission Denied Errors
```bash
# Fix storage permissions
chmod -R 775 storage bootstrap/cache
```

### Tests Failing
```bash
# Ensure test database exists
php artisan migrate --env=testing

# Run tests with verbose output
php artisan test --verbose
```

---

## Production Deployment

### Pre-Deployment Checklist
- [ ] Set `APP_ENV=production` in .env
- [ ] Set `APP_DEBUG=false` in .env
- [ ] Generate strong `APP_KEY`
- [ ] Configure database credentials
- [ ] Set up HTTPS/SSL
- [ ] Configure backup strategy
- [ ] Set up monitoring

### Deploy Steps
```bash
# 1. Pull latest code
git pull origin main

# 2. Install dependencies
composer install --no-dev --optimize-autoloader

# 3. Build frontend
npm run build

# 4. Run migrations
php artisan migrate --force

# 5. Clear caches
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Set permissions
chmod -R 775 storage bootstrap/cache

# 7. Restart services
systemctl restart php-fpm
systemctl restart nginx
```

---

## Support & Documentation

- **Requirements**: `.kiro/specs/sgcmi/requirements.md`
- **Design**: `.kiro/specs/sgcmi/design.md`
- **Tasks**: `.kiro/specs/sgcmi/tasks.md`
- **Frontend Setup**: `FRONTEND_SETUP.md`
- **Audit Implementation**: `AUDIT_IMPLEMENTATION.md`
- **Reports Integration**: `REPORTES_INTEGRACION_COMPLETA.md`

---

## System Architecture

```
┌─────────────────────────────────────────────────────────┐
│                    Frontend Layer                        │
│  Blade Templates + Tailwind CSS + Alpine.js             │
└─────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────┐
│                  Controller Layer                        │
│  VesselCallController, AppointmentController, etc.      │
└─────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────┐
│                   Service Layer                          │
│  ReportService, KpiCalculator, ExportService, etc.      │
└─────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────┐
│                    Model Layer                           │
│  Eloquent Models with Relationships                     │
└─────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────┐
│                  Database Layer                          │
│  PostgreSQL with 7 Schemas, 22 Tables                   │
└─────────────────────────────────────────────────────────┘
```

---

**Last Updated**: December 3, 2025  
**Status**: ✅ Production Ready

