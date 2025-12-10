# SGCMI Pipeline - Step 3: onMigrate Execution Report

**Date**: December 3, 2025  
**Status**: ✅ MIGRATION READY  
**Environment**: Windows, PHP 8.3.26, Laravel 11.47.0, PostgreSQL 16

---

## Database Configuration

### ✅ PostgreSQL Connection

```
Host: 127.0.0.1
Port: 5432
Database: sgcmi
Username: postgres
Password: 1234
```

### ✅ Environment Configuration (.env)

```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=sgcmi
DB_USERNAME=postgres
DB_PASSWORD=1234
```

---

## Migration Execution Plan

### Phase 1: Schema Creation

**Migration**: `2024_01_01_000001_create_schemas.php`

Creates 7 PostgreSQL schemas:
- `admin`: User management and RBAC
- `portuario`: Port operations
- `terrestre`: Road/truck operations
- `aduanas`: Customs operations
- `analytics`: KPIs and SLAs
- `audit`: Audit logging
- `reports`: Materialized views (reserved)

**SQL Equivalent**: `database/sql/01_create_schemas.sql`

### Phase 2: Admin Tables

**Migration**: `2024_01_01_000002_create_admin_tables.php`

Creates 5 tables:
- `admin.users`: User accounts
- `admin.roles`: Role definitions
- `admin.permissions`: Permission definitions
- `admin.user_roles`: User-role assignments
- `admin.role_permissions`: Role-permission assignments

**SQL Equivalent**: `database/sql/02_create_admin_tables.sql`

### Phase 3: Audit Table

**Migration**: `2024_01_01_000003_create_audit_tables.php`

Creates 1 table:
- `audit.audit_log`: Audit trail for all CUD operations

**SQL Equivalent**: `database/sql/03_create_audit_tables.sql`

### Phase 4: Portuario Tables

**Migration**: `2024_01_01_000004_create_portuario_tables.php`

Creates 3 tables:
- `portuario.berth`: Port berths/docks
- `portuario.vessel`: Ship information
- `portuario.vessel_call`: Ship arrival/departure events

**Constraints**:
- `etb >= eta` (estimated berthing after arrival)
- `atb >= ata` (actual berthing after arrival)
- `atd >= atb` (actual departure after berthing)

**Indexes**:
- `idx_vessel_call_eta`: For ETA queries
- `idx_vessel_call_ata`: For ATA queries
- `idx_vessel_call_berth`: For berth queries

**SQL Equivalent**: `database/sql/04_create_portuario_tables.sql`

### Phase 5: Terrestre Tables

**Migration**: `2024_01_01_000005_create_terrestre_tables.php`

Creates 5 tables:
- `terrestre.company`: Transportation companies
- `terrestre.truck`: Truck/vehicle information
- `terrestre.gate`: Port gates
- `terrestre.appointment`: Truck appointment scheduling
- `terrestre.gate_event`: Gate entry/exit events

**Indexes**:
- `idx_appointment_hora`: For time-based queries
- `idx_appointment_company`: For company scoping
- `idx_gate_event_ts`: For event timestamp queries
- `idx_gate_event_gate`: For gate queries

**SQL Equivalent**: `database/sql/05_create_terrestre_tables.sql`

### Phase 6: Aduanas Tables

**Migration**: `2024_01_01_000006_create_aduanas_tables.php`

Creates 3 tables:
- `aduanas.entidad`: Customs entities
- `aduanas.tramite`: Customs procedures
- `aduanas.tramite_event`: Customs procedure events

**Constraints**:
- `tramite_ext_id` UNIQUE (customs transaction ID)

**Indexes**:
- `idx_tramite_vessel`: For vessel queries
- `idx_tramite_estado`: For status queries
- `idx_tramite_event_ts`: For event timestamp queries

**SQL Equivalent**: `database/sql/06_create_aduanas_tables.sql`

### Phase 7: Analytics Tables

**Migration**: `2024_01_01_000007_create_analytics_tables.php`

Creates 5 tables:
- `analytics.actor`: Actors for KPI/SLA measurement
- `analytics.kpi_definition`: KPI definitions
- `analytics.kpi_value`: KPI calculated values
- `analytics.sla_definition`: SLA definitions
- `analytics.sla_measure`: SLA measurements

**Indexes**:
- `idx_kpi_value_periodo`: For period-based queries
- `idx_sla_measure_periodo`: For period-based queries

**SQL Equivalent**: `database/sql/07_create_analytics_tables.sql`

### Phase 8: Alerts Table

**Migration**: `2024_01_01_000008_create_alerts_table.php`

Creates 1 table:
- `analytics.alerts`: System alerts for early warning

**Indexes**:
- `idx_alerts_tipo_nivel_ts`: For alert queries
- `idx_alerts_entity`: For entity-based queries

**SQL Equivalent**: `database/sql/08_create_alerts_table.sql` (included in 07)

---

## Seeding Execution Plan

### Phase 1: RBAC Seeding

**Seeder**: `RolePermissionSeeder`

Creates:
- 9 roles: ADMIN, PLANIFICADOR_PUERTO, OPERACIONES_PUERTO, OPERADOR_GATES, TRANSPORTISTA, AGENTE_ADUANA, ANALISTA, DIRECTIVO, AUDITOR
- 19 permissions: USER_ADMIN, ROLE_ADMIN, AUDIT_READ, SCHEDULE_READ, SCHEDULE_WRITE, APPOINTMENT_READ, APPOINTMENT_WRITE, GATE_EVENT_READ, GATE_EVENT_WRITE, ADUANA_READ, ADUANA_WRITE, REPORT_READ, REPORT_EXPORT, PORT_REPORT_READ, ROAD_REPORT_READ, CUS_REPORT_READ, KPI_READ, SLA_READ, SLA_ADMIN
- Role-permission mappings

**SQL Equivalent**: `database/sql/08_seed_roles_permissions.sql`

### Phase 2: User Seeding

**Seeder**: `UserSeeder`

Creates 9 demo users:
- admin (ADMIN role)
- planificador (PLANIFICADOR_PUERTO)
- operaciones (OPERACIONES_PUERTO)
- gates (OPERADOR_GATES)
- transportista (TRANSPORTISTA)
- aduana (AGENTE_ADUANA)
- analista (ANALISTA)
- directivo (DIRECTIVO)
- auditor (AUDITOR)

**Password**: password123 (bcrypt hashed)

**SQL Equivalent**: `database/sql/09_seed_users.sql`

### Phase 3: Demo Data Seeding

**Seeders**: PortuarioSeeder, TerrestreSeeder, AduanasSeeder, AnalyticsSeeder

**Portuario Data**:
- 3 berths (M1, M2, M3)
- 3 vessels (MSC MARINA, MAERSK LIMA, CMA CGM ANDES)
- 4 vessel calls with ETA/ETB/ATA/ATB/ATD

**Terrestre Data**:
- 2 companies (Transportes del Sur SAC, Logística Andina EIRL)
- 3 trucks (ABC123, DEF456, GHI789)
- 2 gates (G1, G2)
- 6 appointments with various states

**Aduanas Data**:
- 3 entities (SUNAT, VUCE, SENASA)
- 2 tramites with different regimens

**Analytics Data**:
- 4 KPI definitions (turnaround_h, espera_camion_h, cumpl_citas_pct, tramites_ok_pct)
- 2 SLA definitions (turnaround_max, espera_max)

**SQL Equivalent**: `database/sql/10_seed_demo_data.sql`

---

## Execution Commands

### Option 1: Laravel Migrations (Recommended)

```bash
# Run all migrations
php artisan migrate

# Run specific migration
php artisan migrate --path=database/migrations/2024_01_01_000001_create_schemas.php

# Rollback all migrations
php artisan migrate:rollback

# Refresh database (rollback + migrate)
php artisan migrate:refresh

# Seed database
php artisan db:seed

# Seed specific seeder
php artisan db:seed --class=RolePermissionSeeder
```

### Option 2: Direct PostgreSQL Execution

```bash
# Connect to PostgreSQL
psql -U postgres -d sgcmi

# Run all SQL scripts
\i database/sql/01_create_schemas.sql
\i database/sql/02_create_admin_tables.sql
\i database/sql/03_create_audit_tables.sql
\i database/sql/04_create_portuario_tables.sql
\i database/sql/05_create_terrestre_tables.sql
\i database/sql/06_create_aduanas_tables.sql
\i database/sql/07_create_analytics_tables.sql
\i database/sql/08_seed_roles_permissions.sql
\i database/sql/09_seed_users.sql
\i database/sql/10_seed_demo_data.sql

# Or run master script
\i database/sql/run_all_migrations.sql
```

### Option 3: Batch Script (Windows)

```batch
@echo off
REM EJECUTAR_MIGRACIONES.bat
php artisan migrate
php artisan db:seed
echo Migraciones completadas
pause
```

---

## Validation Queries

### Verify Schemas Created

```sql
SELECT schema_name 
FROM information_schema.schemata 
WHERE schema_name IN ('admin', 'portuario', 'terrestre', 'aduanas', 'analytics', 'audit')
ORDER BY schema_name;
```

**Expected Output**: 6 rows (admin, aduanas, analytics, audit, portuario, terrestre)

### Verify Tables Created

```sql
SELECT table_schema, COUNT(*) as tables_count 
FROM information_schema.tables 
WHERE table_schema IN ('admin', 'portuario', 'terrestre', 'aduanas', 'analytics', 'audit')
GROUP BY table_schema 
ORDER BY table_schema;
```

**Expected Output**:
- admin: 5 tables
- aduanas: 3 tables
- analytics: 5 tables
- audit: 1 table
- portuario: 3 tables
- terrestre: 5 tables
- **Total**: 22 tables

### Verify Roles and Permissions

```sql
SELECT r.code as role, COUNT(rp.permission_id) as permissions_count
FROM admin.roles r
LEFT JOIN admin.role_permissions rp ON r.id = rp.role_id
GROUP BY r.code
ORDER BY r.code;
```

**Expected Output**: 9 roles with varying permission counts

### Verify Users Created

```sql
SELECT u.username, r.code as role
FROM admin.users u
JOIN admin.user_roles ur ON u.id = ur.user_id
JOIN admin.roles r ON ur.role_id = r.id
ORDER BY u.username;
```

**Expected Output**: 9 users with assigned roles

### Verify Demo Data

```sql
SELECT 'Users' as tabla, COUNT(*) as registros FROM admin.users
UNION ALL SELECT 'Roles', COUNT(*) FROM admin.roles
UNION ALL SELECT 'Permissions', COUNT(*) FROM admin.permissions
UNION ALL SELECT 'Berths', COUNT(*) FROM portuario.berth
UNION ALL SELECT 'Vessels', COUNT(*) FROM portuario.vessel
UNION ALL SELECT 'Vessel Calls', COUNT(*) FROM portuario.vessel_call
UNION ALL SELECT 'Companies', COUNT(*) FROM terrestre.company
UNION ALL SELECT 'Trucks', COUNT(*) FROM terrestre.truck
UNION ALL SELECT 'Gates', COUNT(*) FROM terrestre.gate
UNION ALL SELECT 'Appointments', COUNT(*) FROM terrestre.appointment
UNION ALL SELECT 'Entidades', COUNT(*) FROM aduanas.entidad
UNION ALL SELECT 'Tramites', COUNT(*) FROM aduanas.tramite
UNION ALL SELECT 'KPI Definitions', COUNT(*) FROM analytics.kpi_definition
UNION ALL SELECT 'SLA Definitions', COUNT(*) FROM analytics.sla_definition;
```

**Expected Output**:
- Users: 9
- Roles: 9
- Permissions: 19
- Berths: 3
- Vessels: 3
- Vessel Calls: 4
- Companies: 2
- Trucks: 3
- Gates: 2
- Appointments: 6
- Entidades: 3
- Tramites: 2
- KPI Definitions: 4
- SLA Definitions: 2

---

## Validation Script

**File**: `database/sql/validate_system.sql`

Comprehensive validation of all components:
- Schemas created
- Tables created
- Roles and permissions
- Users and roles
- Demo data counts
- Relationships integrity
- Admin permissions
- Active users

**Execution**:
```bash
psql -U postgres -d sgcmi -f database/sql/validate_system.sql
```

---

## Troubleshooting

### Issue: Connection Refused

**Cause**: PostgreSQL not running or wrong credentials

**Solution**:
```bash
# Check PostgreSQL status
pg_isready -h 127.0.0.1 -p 5432

# Verify credentials in .env
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=sgcmi
# DB_USERNAME=postgres
# DB_PASSWORD=1234
```

### Issue: Schema Already Exists

**Cause**: Migrations already run

**Solution**:
```bash
# Rollback migrations
php artisan migrate:rollback

# Or drop database and recreate
psql -U postgres -c "DROP DATABASE sgcmi;"
psql -U postgres -c "CREATE DATABASE sgcmi;"
```

### Issue: Foreign Key Constraint Violation

**Cause**: Seeding order incorrect

**Solution**:
```bash
# Ensure migrations run before seeders
php artisan migrate
php artisan db:seed
```

### Issue: Permission Denied on Files

**Cause**: File permissions issue

**Solution**:
```bash
# Windows: Run as Administrator
# Linux: chmod -R 755 storage bootstrap/cache
```

---

## Post-Migration Checklist

- [ ] All 7 schemas created
- [ ] All 22 tables created
- [ ] All 9 roles created
- [ ] All 19 permissions created
- [ ] All 9 users created
- [ ] Demo data seeded (berths, vessels, appointments, etc.)
- [ ] Foreign key relationships working
- [ ] Indexes created
- [ ] Constraints enforced
- [ ] Validation queries pass

---

## Database Backup

### Create Backup

```bash
# PostgreSQL dump
pg_dump -U postgres -d sgcmi -f sgcmi_backup.sql

# Or with compression
pg_dump -U postgres -d sgcmi | gzip > sgcmi_backup.sql.gz
```

### Restore Backup

```bash
# From SQL file
psql -U postgres -d sgcmi -f sgcmi_backup.sql

# From compressed file
gunzip -c sgcmi_backup.sql.gz | psql -U postgres -d sgcmi
```

---

## Performance Optimization

### Indexes Created

- `idx_vessel_call_eta`: Vessel call ETA queries
- `idx_vessel_call_ata`: Vessel call ATA queries
- `idx_vessel_call_berth`: Vessel call berth queries
- `idx_appointment_hora`: Appointment time queries
- `idx_appointment_company`: Company scoping
- `idx_gate_event_ts`: Gate event timestamp queries
- `idx_gate_event_gate`: Gate queries
- `idx_tramite_vessel`: Tramite vessel queries
- `idx_tramite_estado`: Tramite status queries
- `idx_tramite_event_ts`: Tramite event timestamp queries
- `idx_kpi_value_periodo`: KPI period queries
- `idx_sla_measure_periodo`: SLA period queries
- `idx_alerts_tipo_nivel_ts`: Alert queries
- `idx_alerts_entity`: Alert entity queries
- `idx_audit_ts`: Audit timestamp queries
- `idx_audit_user`: Audit user queries

### Query Optimization

- Eager loading in controllers (with())
- Pagination (50 records per page)
- Selective column selection
- Proper use of indexes in WHERE clauses

---

## Conclusion

**Status**: ✅ **STEP 3 MIGRATION READY**

All migrations and seeders are prepared and ready for execution. The database schema is complete with:
- 7 PostgreSQL schemas
- 22 tables with proper relationships
- 9 roles and 19 permissions
- 9 demo users
- Demo data for all modules
- Comprehensive indexes
- Temporal constraints
- Referential integrity

**Recommendation**: Execute migrations using `php artisan migrate && php artisan db:seed` or direct SQL scripts.

---

**Preparation Date**: December 3, 2025  
**Prepared By**: Kiro AI Assistant  
**Next Step**: Step 4 - onTest (Test Execution)

