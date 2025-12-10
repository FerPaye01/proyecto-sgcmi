-- SGCMI: Crear Tablas Portuario
-- Ejecutar con: psql -U postgres -d sgcmi -f 04_create_portuario_tables.sql

CREATE TABLE IF NOT EXISTS portuario.berth (
    id BIGSERIAL PRIMARY KEY,
    code VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    capacity_teorica INTEGER,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE TABLE IF NOT EXISTS portuario.vessel (
    id BIGSERIAL PRIMARY KEY,
    imo VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    flag_country VARCHAR(255),
    type VARCHAR(255),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE TABLE IF NOT EXISTS portuario.vessel_call (
    id BIGSERIAL PRIMARY KEY,
    vessel_id BIGINT NOT NULL REFERENCES portuario.vessel(id),
    viaje_id VARCHAR(255),
    berth_id BIGINT REFERENCES portuario.berth(id),
    eta TIMESTAMP,
    etb TIMESTAMP,
    ata TIMESTAMP,
    atb TIMESTAMP,
    atd TIMESTAMP,
    estado_llamada VARCHAR(255) DEFAULT 'PROGRAMADA',
    motivo_demora TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_vessel_call_vessel_eta ON portuario.vessel_call(vessel_id, eta);

-- Verificar tablas creadas
SELECT table_name FROM information_schema.tables 
WHERE table_schema = 'portuario' 
ORDER BY table_name;
