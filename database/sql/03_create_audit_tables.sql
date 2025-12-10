-- SGCMI: Crear Tablas Audit
-- Ejecutar con: psql -U postgres -d sgcmi -f 03_create_audit_tables.sql

CREATE TABLE IF NOT EXISTS audit.audit_log (
    id BIGSERIAL PRIMARY KEY,
    event_ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actor_user VARCHAR(255),
    action VARCHAR(255) NOT NULL,
    object_schema VARCHAR(255),
    object_table VARCHAR(255),
    object_id BIGINT,
    details JSONB
);

CREATE INDEX IF NOT EXISTS idx_audit_log_event_ts_actor ON audit.audit_log(event_ts, actor_user);

-- Verificar tabla creada
SELECT table_name FROM information_schema.tables 
WHERE table_schema = 'audit' 
ORDER BY table_name;
