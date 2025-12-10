-- SGCMI: Crear Tablas Admin
-- Ejecutar con: psql -U postgres -d sgcmi -f 02_create_admin_tables.sql

CREATE TABLE IF NOT EXISTS admin.users (
    id BIGSERIAL PRIMARY KEY,
    username VARCHAR(255) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    remember_token VARCHAR(100),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE TABLE IF NOT EXISTS admin.roles (
    id BIGSERIAL PRIMARY KEY,
    code VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE TABLE IF NOT EXISTS admin.permissions (
    id BIGSERIAL PRIMARY KEY,
    code VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE TABLE IF NOT EXISTS admin.user_roles (
    user_id BIGINT NOT NULL REFERENCES admin.users(id) ON DELETE CASCADE,
    role_id BIGINT NOT NULL REFERENCES admin.roles(id) ON DELETE CASCADE,
    PRIMARY KEY (user_id, role_id)
);

CREATE TABLE IF NOT EXISTS admin.role_permissions (
    role_id BIGINT NOT NULL REFERENCES admin.roles(id) ON DELETE CASCADE,
    permission_id BIGINT NOT NULL REFERENCES admin.permissions(id) ON DELETE CASCADE,
    PRIMARY KEY (role_id, permission_id)
);

-- Verificar tablas creadas
SELECT table_name FROM information_schema.tables 
WHERE table_schema = 'admin' 
ORDER BY table_name;
