-- SGCMI: Script Maestro de Migración y Seeding
-- Ejecutar con: psql -U postgres -d sgcmi -f run_all_migrations.sql

\echo '========================================='
\echo 'SGCMI - Iniciando Migración Completa'
\echo '========================================='
\echo ''

\echo '1. Creando Schemas...'
\i 01_create_schemas.sql
\echo ''

\echo '2. Creando Tablas Admin...'
\i 02_create_admin_tables.sql
\echo ''

\echo '3. Creando Tablas Audit...'
\i 03_create_audit_tables.sql
\echo ''

\echo '4. Creando Tablas Portuario...'
\i 04_create_portuario_tables.sql
\echo ''

\echo '5. Creando Tablas Terrestre...'
\i 05_create_terrestre_tables.sql
\echo ''

\echo '6. Creando Tablas Aduanas...'
\i 06_create_aduanas_tables.sql
\echo ''

\echo '7. Creando Tablas Analytics...'
\i 07_create_analytics_tables.sql
\echo ''

\echo '8. Seeding Roles y Permisos...'
\i 08_seed_roles_permissions.sql
\echo ''

\echo '9. Seeding Usuarios...'
\i 09_seed_users.sql
\echo ''

\echo '10. Seeding Datos Demo...'
\i 10_seed_demo_data.sql
\echo ''

\echo '========================================='
\echo 'SGCMI - Migración Completada'
\echo '========================================='
\echo ''
\echo 'Resumen de Tablas Creadas:'
SELECT schema_name, COUNT(*) as tables_count
FROM information_schema.tables 
WHERE table_schema IN ('admin', 'portuario', 'terrestre', 'aduanas', 'analytics', 'audit', 'reports')
GROUP BY schema_name
ORDER BY schema_name;

\echo ''
\echo 'Usuarios Demo (password: password123):'
SELECT username, email FROM admin.users ORDER BY username;
