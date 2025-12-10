-- SGCMI: Seed Usuarios Demo
-- Ejecutar con: psql -U postgres -d sgcmi -f 09_seed_users.sql
-- Password para todos: password123 (hash bcrypt)

INSERT INTO admin.users (username, email, full_name, password, is_active, created_at, updated_at) VALUES
('admin', 'admin@sgcmi.pe', 'Administrador Sistema', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5NANh2u8/Nh7i', TRUE, NOW(), NOW()),
('planificador', 'planificador@sgcmi.pe', 'Juan Planificador', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5NANh2u8/Nh7i', TRUE, NOW(), NOW()),
('operaciones', 'operaciones@sgcmi.pe', 'María Operaciones', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5NANh2u8/Nh7i', TRUE, NOW(), NOW()),
('gates', 'gates@sgcmi.pe', 'Pedro Gates', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5NANh2u8/Nh7i', TRUE, NOW(), NOW()),
('transportista', 'transportista@sgcmi.pe', 'Carlos Transportista', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5NANh2u8/Nh7i', TRUE, NOW(), NOW()),
('aduana', 'aduana@sgcmi.pe', 'Ana Aduana', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5NANh2u8/Nh7i', TRUE, NOW(), NOW()),
('analista', 'analista@sgcmi.pe', 'Luis Analista', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5NANh2u8/Nh7i', TRUE, NOW(), NOW()),
('directivo', 'directivo@sgcmi.pe', 'Roberto Directivo', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5NANh2u8/Nh7i', TRUE, NOW(), NOW()),
('auditor', 'auditor@sgcmi.pe', 'Sofia Auditor', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5NANh2u8/Nh7i', TRUE, NOW(), NOW())
ON CONFLICT (username) DO NOTHING;

-- Asignar roles a usuarios
INSERT INTO admin.user_roles (user_id, role_id)
SELECT u.id, r.id FROM admin.users u, admin.roles r WHERE u.username = 'admin' AND r.code = 'ADMIN'
ON CONFLICT DO NOTHING;

INSERT INTO admin.user_roles (user_id, role_id)
SELECT u.id, r.id FROM admin.users u, admin.roles r WHERE u.username = 'planificador' AND r.code = 'PLANIFICADOR_PUERTO'
ON CONFLICT DO NOTHING;

INSERT INTO admin.user_roles (user_id, role_id)
SELECT u.id, r.id FROM admin.users u, admin.roles r WHERE u.username = 'operaciones' AND r.code = 'OPERACIONES_PUERTO'
ON CONFLICT DO NOTHING;

INSERT INTO admin.user_roles (user_id, role_id)
SELECT u.id, r.id FROM admin.users u, admin.roles r WHERE u.username = 'gates' AND r.code = 'OPERADOR_GATES'
ON CONFLICT DO NOTHING;

INSERT INTO admin.user_roles (user_id, role_id)
SELECT u.id, r.id FROM admin.users u, admin.roles r WHERE u.username = 'transportista' AND r.code = 'TRANSPORTISTA'
ON CONFLICT DO NOTHING;

INSERT INTO admin.user_roles (user_id, role_id)
SELECT u.id, r.id FROM admin.users u, admin.roles r WHERE u.username = 'aduana' AND r.code = 'AGENTE_ADUANA'
ON CONFLICT DO NOTHING;

INSERT INTO admin.user_roles (user_id, role_id)
SELECT u.id, r.id FROM admin.users u, admin.roles r WHERE u.username = 'analista' AND r.code = 'ANALISTA'
ON CONFLICT DO NOTHING;

INSERT INTO admin.user_roles (user_id, role_id)
SELECT u.id, r.id FROM admin.users u, admin.roles r WHERE u.username = 'directivo' AND r.code = 'DIRECTIVO'
ON CONFLICT DO NOTHING;

INSERT INTO admin.user_roles (user_id, role_id)
SELECT u.id, r.id FROM admin.users u, admin.roles r WHERE u.username = 'auditor' AND r.code = 'AUDITOR'
ON CONFLICT DO NOTHING;

-- Verificar
SELECT u.username, u.email, r.code as role
FROM admin.users u
JOIN admin.user_roles ur ON u.id = ur.user_id
JOIN admin.roles r ON ur.role_id = r.id
ORDER BY u.username;
