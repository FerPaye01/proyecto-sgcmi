-- SGCMI: Crear Tablas Terrestre
-- Ejecutar con: psql -U postgres -d sgcmi -f 05_create_terrestre_tables.sql

CREATE TABLE IF NOT EXISTS terrestre.company (
    id BIGSERIAL PRIMARY KEY,
    ruc VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    tipo VARCHAR(255),
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE TABLE IF NOT EXISTS terrestre.truck (
    id BIGSERIAL PRIMARY KEY,
    placa VARCHAR(255) UNIQUE NOT NULL,
    company_id BIGINT NOT NULL REFERENCES terrestre.company(id),
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE TABLE IF NOT EXISTS terrestre.gate (
    id BIGSERIAL PRIMARY KEY,
    code VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE TABLE IF NOT EXISTS terrestre.appointment (
    id BIGSERIAL PRIMARY KEY,
    truck_id BIGINT NOT NULL REFERENCES terrestre.truck(id),
    company_id BIGINT NOT NULL REFERENCES terrestre.company(id),
    vessel_call_id BIGINT REFERENCES portuario.vessel_call(id),
    hora_programada TIMESTAMP NOT NULL,
    hora_llegada TIMESTAMP,
    estado VARCHAR(255) DEFAULT 'PROGRAMADA',
    motivo TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_appointment_hora_estado ON terrestre.appointment(hora_programada, estado);

CREATE TABLE IF NOT EXISTS terrestre.gate_event (
    id BIGSERIAL PRIMARY KEY,
    gate_id BIGINT NOT NULL REFERENCES terrestre.gate(id),
    truck_id BIGINT NOT NULL REFERENCES terrestre.truck(id),
    action VARCHAR(255) NOT NULL,
    event_ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    cita_id BIGINT REFERENCES terrestre.appointment(id),
    extra JSONB
);

CREATE INDEX IF NOT EXISTS idx_gate_event_ts_gate ON terrestre.gate_event(event_ts, gate_id);

-- Verificar tablas creadas
SELECT table_name FROM information_schema.tables 
WHERE table_schema = 'terrestre' 
ORDER BY table_name;
