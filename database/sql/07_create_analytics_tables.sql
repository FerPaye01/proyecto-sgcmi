-- SGCMI: Crear Tablas Analytics
-- Ejecutar con: psql -U postgres -d sgcmi -f 07_create_analytics_tables.sql

CREATE TABLE IF NOT EXISTS analytics.actor (
    id BIGSERIAL PRIMARY KEY,
    ref_table VARCHAR(255) NOT NULL,
    ref_id BIGINT NOT NULL,
    tipo VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE(ref_table, ref_id)
);

CREATE TABLE IF NOT EXISTS analytics.kpi_definition (
    id BIGSERIAL PRIMARY KEY,
    code VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE TABLE IF NOT EXISTS analytics.kpi_value (
    id BIGSERIAL PRIMARY KEY,
    kpi_id BIGINT NOT NULL REFERENCES analytics.kpi_definition(id),
    periodo DATE NOT NULL,
    valor DECIMAL(12, 4) NOT NULL,
    meta DECIMAL(12, 4),
    fuente VARCHAR(255),
    extra JSONB,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_kpi_value_kpi_periodo ON analytics.kpi_value(kpi_id, periodo);

CREATE TABLE IF NOT EXISTS analytics.sla_definition (
    id BIGSERIAL PRIMARY KEY,
    code VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    umbral DECIMAL(12, 4) NOT NULL,
    comparador VARCHAR(10) DEFAULT '<=',
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE TABLE IF NOT EXISTS analytics.sla_measure (
    id BIGSERIAL PRIMARY KEY,
    sla_id BIGINT NOT NULL REFERENCES analytics.sla_definition(id),
    actor_id BIGINT REFERENCES analytics.actor(id),
    periodo DATE NOT NULL,
    valor DECIMAL(12, 4) NOT NULL,
    cumplio BOOLEAN DEFAULT FALSE,
    extra JSONB,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_sla_measure_sla_periodo_actor ON analytics.sla_measure(sla_id, periodo, actor_id);

-- Verificar tablas creadas
SELECT table_name FROM information_schema.tables 
WHERE table_schema = 'analytics' 
ORDER BY table_name;
