-- SGCMI: Crear Schemas PostgreSQL
-- Ejecutar con: psql -U postgres -d sgcmi -f 01_create_schemas.sql

CREATE SCHEMA IF NOT EXISTS admin;
CREATE SCHEMA IF NOT EXISTS portuario;
CREATE SCHEMA IF NOT EXISTS terrestre;
CREATE SCHEMA IF NOT EXISTS aduanas;
CREATE SCHEMA IF NOT EXISTS analytics;
CREATE SCHEMA IF NOT EXISTS audit;
CREATE SCHEMA IF NOT EXISTS reports;

-- Verificar schemas creados
SELECT schema_name FROM information_schema.schemata 
WHERE schema_name IN ('admin', 'portuario', 'terrestre', 'aduanas', 'analytics', 'audit', 'reports')
ORDER BY schema_name;
