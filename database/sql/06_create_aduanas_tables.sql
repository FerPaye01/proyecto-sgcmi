-- SGCMI: Crear Tablas Aduanas
-- Ejecutar con: psql -U postgres -d sgcmi -f 06_create_aduanas_tables.sql

CREATE TABLE IF NOT EXISTS aduanas.entidad (
    id BIGSERIAL PRIMARY KEY,
    code VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE TABLE IF NOT EXISTS aduanas.tramite (
    id BIGSERIAL PRIMARY KEY,
    tramite_ext_id VARCHAR(255) UNIQUE NOT NULL,
    vessel_call_id BIGINT REFERENCES portuario.vessel_call(id),
    regimen VARCHAR(255) NOT NULL,
    subpartida VARCHAR(255),
    estado VARCHAR(255) DEFAULT 'INICIADO',
    fecha_inicio TIMESTAMP NOT NULL,
    fecha_fin TIMESTAMP,
    entidad_id BIGINT REFERENCES aduanas.entidad(id),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_tramite_estado_fecha ON aduanas.tramite(estado, fecha_inicio);

CREATE TABLE IF NOT EXISTS aduanas.tramite_event (
    id BIGSERIAL PRIMARY KEY,
    tramite_id BIGINT NOT NULL REFERENCES aduanas.tramite(id) ON DELETE CASCADE,
    event_ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado VARCHAR(255) NOT NULL,
    motivo TEXT
);

CREATE INDEX IF NOT EXISTS idx_tramite_event_tramite_ts ON aduanas.tramite_event(tramite_id, event_ts);

-- Verificar tablas creadas
SELECT table_name FROM information_schema.tables 
WHERE table_schema = 'aduanas' 
ORDER BY table_name;
