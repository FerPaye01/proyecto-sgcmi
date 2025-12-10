-- SGCMI: Seed Roles y Permisos
-- Ejecutar con: psql -U postgres -d sgcmi -f 08_seed_roles_permissions.sql

-- Insertar Permisos
INSERT INTO admin.permissions (code, name, created_at, updated_at) VALUES
('USER_ADMIN', 'USER_ADMIN', NOW(), NOW()),
('ROLE_ADMIN', 'ROLE_ADMIN', NOW(), NOW()),
('AUDIT_READ', 'AUDIT_READ', NOW(), NOW()),
('SCHEDULE_READ', 'SCHEDULE_READ', NOW(), NOW()),
('SCHEDULE_WRITE', 'SCHEDULE_WRITE', NOW(), NOW()),
('APPOINTMENT_READ', 'APPOINTMENT_READ', NOW(), NOW()),
('APPOINTMENT_WRITE', 'APPOINTMENT_WRITE', NOW(), NOW()),
('GATE_EVENT_READ', 'GATE_EVENT_READ', NOW(), NOW()),
('GATE_EVENT_WRITE', 'GATE_EVENT_WRITE', NOW(), NOW()),
('ADUANA_READ', 'ADUANA_READ', NOW(), NOW()),
('ADUANA_WRITE', 'ADUANA_WRITE', NOW(), NOW()),
('REPORT_READ', 'REPORT_READ', NOW(), NOW()),
('REPORT_EXPORT', 'REPORT_EXPORT', NOW(), NOW()),
('PORT_REPORT_READ', 'PORT_REPORT_READ', NOW(), NOW()),
('ROAD_REPORT_READ', 'ROAD_REPORT_READ', NOW(), NOW()),
('CUS_REPORT_READ', 'CUS_REPORT_READ', NOW(), NOW()),
('KPI_READ', 'KPI_READ', NOW(), NOW()),
('SLA_READ', 'SLA_READ', NOW(), NOW()),
('SLA_ADMIN', 'SLA_ADMIN', NOW(), NOW())
ON CONFLICT (code) DO NOTHING;

-- Insertar Roles
INSERT INTO admin.roles (code, name, created_at, updated_at) VALUES
('ADMIN', 'ADMIN', NOW(), NOW()),
('PLANIFICADOR_PUERTO', 'PLANIFICADOR_PUERTO', NOW(), NOW()),
('OPERACIONES_PUERTO', 'OPERACIONES_PUERTO', NOW(), NOW()),
('OPERADOR_GATES', 'OPERADOR_GATES', NOW(), NOW()),
('TRANSPORTISTA', 'TRANSPORTISTA', NOW(), NOW()),
('AGENTE_ADUANA', 'AGENTE_ADUANA', NOW(), NOW()),
('ANALISTA', 'ANALISTA', NOW(), NOW()),
('DIRECTIVO', 'DIRECTIVO', NOW(), NOW()),
('AUDITOR', 'AUDITOR', NOW(), NOW())
ON CONFLICT (code) DO NOTHING;

-- Asignar todos los permisos a ADMIN
INSERT INTO admin.role_permissions (role_id, permission_id)
SELECT r.id, p.id 
FROM admin.roles r 
CROSS JOIN admin.permissions p 
WHERE r.code = 'ADMIN'
ON CONFLICT DO NOTHING;

-- PLANIFICADOR_PUERTO
INSERT INTO admin.role_permissions (role_id, permission_id)
SELECT r.id, p.id 
FROM admin.roles r, admin.permissions p 
WHERE r.code = 'PLANIFICADOR_PUERTO' 
AND p.code IN ('SCHEDULE_READ', 'SCHEDULE_WRITE', 'PORT_REPORT_READ', 'REPORT_READ', 'REPORT_EXPORT')
ON CONFLICT DO NOTHING;

-- OPERACIONES_PUERTO
INSERT INTO admin.role_permissions (role_id, permission_id)
SELECT r.id, p.id 
FROM admin.roles r, admin.permissions p 
WHERE r.code = 'OPERACIONES_PUERTO' 
AND p.code IN ('PORT_REPORT_READ', 'ROAD_REPORT_READ', 'REPORT_READ')
ON CONFLICT DO NOTHING;

-- OPERADOR_GATES
INSERT INTO admin.role_permissions (role_id, permission_id)
SELECT r.id, p.id 
FROM admin.roles r, admin.permissions p 
WHERE r.code = 'OPERADOR_GATES' 
AND p.code IN ('APPOINTMENT_READ', 'APPOINTMENT_WRITE', 'GATE_EVENT_READ', 'GATE_EVENT_WRITE', 'ROAD_REPORT_READ')
ON CONFLICT DO NOTHING;

-- TRANSPORTISTA
INSERT INTO admin.role_permissions (role_id, permission_id)
SELECT r.id, p.id 
FROM admin.roles r, admin.permissions p 
WHERE r.code = 'TRANSPORTISTA' 
AND p.code IN ('APPOINTMENT_READ', 'ROAD_REPORT_READ')
ON CONFLICT DO NOTHING;

-- AGENTE_ADUANA
INSERT INTO admin.role_permissions (role_id, permission_id)
SELECT r.id, p.id 
FROM admin.roles r, admin.permissions p 
WHERE r.code = 'AGENTE_ADUANA' 
AND p.code IN ('ADUANA_READ', 'CUS_REPORT_READ')
ON CONFLICT DO NOTHING;

-- ANALISTA
INSERT INTO admin.role_permissions (role_id, permission_id)
SELECT r.id, p.id 
FROM admin.roles r, admin.permissions p 
WHERE r.code = 'ANALISTA' 
AND p.code IN ('REPORT_READ', 'REPORT_EXPORT', 'KPI_READ', 'SLA_READ')
ON CONFLICT DO NOTHING;

-- DIRECTIVO
INSERT INTO admin.role_permissions (role_id, permission_id)
SELECT r.id, p.id 
FROM admin.roles r, admin.permissions p 
WHERE r.code = 'DIRECTIVO' 
AND p.code IN ('REPORT_READ', 'KPI_READ')
ON CONFLICT DO NOTHING;

-- AUDITOR
INSERT INTO admin.role_permissions (role_id, permission_id)
SELECT r.id, p.id 
FROM admin.roles r, admin.permissions p 
WHERE r.code = 'AUDITOR' 
AND p.code IN ('AUDIT_READ', 'REPORT_READ')
ON CONFLICT DO NOTHING;

-- Verificar
SELECT r.code as role, COUNT(rp.permission_id) as permissions_count
FROM admin.roles r
LEFT JOIN admin.role_permissions rp ON r.id = rp.role_id
GROUP BY r.code
ORDER BY r.code;
