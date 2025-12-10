-- SGCMI: Seed Datos Demo
-- Ejecutar con: psql -U postgres -d sgcmi -f 10_seed_demo_data.sql

-- Portuario: Muelles
INSERT INTO portuario.berth (code, name, capacity_teorica, active, created_at, updated_at) VALUES
('M1', 'Muelle 1', 50000, TRUE, NOW(), NOW()),
('M2', 'Muelle 2', 60000, TRUE, NOW(), NOW()),
('M3', 'Muelle 3', 45000, TRUE, NOW(), NOW())
ON CONFLICT (code) DO NOTHING;

-- Portuario: Naves
INSERT INTO portuario.vessel (imo, name, flag_country, type, created_at, updated_at) VALUES
('IMO9876543', 'MSC MARINA', 'Panama', 'Container', NOW(), NOW()),
('IMO9876544', 'MAERSK LIMA', 'Denmark', 'Container', NOW(), NOW()),
('IMO9876545', 'CMA CGM ANDES', 'France', 'Container', NOW(), NOW())
ON CONFLICT (imo) DO NOTHING;

-- Portuario: Llamadas de Naves
INSERT INTO portuario.vessel_call (vessel_id, viaje_id, berth_id, eta, etb, estado_llamada, created_at, updated_at)
SELECT v.id, 'V2024001', b.id, NOW() + INTERVAL '2 days', NOW() + INTERVAL '2 days 3 hours', 'PROGRAMADA', NOW(), NOW()
FROM portuario.vessel v, portuario.berth b
WHERE v.imo = 'IMO9876543' AND b.code = 'M1'
ON CONFLICT DO NOTHING;

INSERT INTO portuario.vessel_call (vessel_id, viaje_id, berth_id, eta, etb, estado_llamada, created_at, updated_at)
SELECT v.id, 'V2024002', b.id, NOW() + INTERVAL '5 days', NOW() + INTERVAL '5 days 2 hours', 'PROGRAMADA', NOW(), NOW()
FROM portuario.vessel v, portuario.berth b
WHERE v.imo = 'IMO9876544' AND b.code = 'M2'
ON CONFLICT DO NOTHING;

-- Terrestre: Empresas
INSERT INTO terrestre.company (ruc, name, tipo, active, created_at, updated_at) VALUES
('20123456789', 'Transportes del Sur SAC', 'TRANSPORTISTA', TRUE, NOW(), NOW()),
('20987654321', 'Logística Andina EIRL', 'TRANSPORTISTA', TRUE, NOW(), NOW())
ON CONFLICT (ruc) DO NOTHING;

-- Terrestre: Camiones
INSERT INTO terrestre.truck (placa, company_id, activo, created_at, updated_at)
SELECT 'ABC123', c.id, TRUE, NOW(), NOW()
FROM terrestre.company c WHERE c.ruc = '20123456789'
ON CONFLICT (placa) DO NOTHING;

INSERT INTO terrestre.truck (placa, company_id, activo, created_at, updated_at)
SELECT 'DEF456', c.id, TRUE, NOW(), NOW()
FROM terrestre.company c WHERE c.ruc = '20123456789'
ON CONFLICT (placa) DO NOTHING;

INSERT INTO terrestre.truck (placa, company_id, activo, created_at, updated_at)
SELECT 'GHI789', c.id, TRUE, NOW(), NOW()
FROM terrestre.company c WHERE c.ruc = '20987654321'
ON CONFLICT (placa) DO NOTHING;

-- Terrestre: Gates
INSERT INTO terrestre.gate (code, name, activo, created_at, updated_at) VALUES
('G1', 'Gate 1 - Entrada Principal', TRUE, NOW(), NOW()),
('G2', 'Gate 2 - Salida Principal', TRUE, NOW(), NOW())
ON CONFLICT (code) DO NOTHING;

-- Terrestre: Citas
INSERT INTO terrestre.appointment (truck_id, company_id, vessel_call_id, hora_programada, estado, created_at, updated_at)
SELECT t.id, c.id, vc.id, NOW() + INTERVAL '2 days 10 hours', 'PROGRAMADA', NOW(), NOW()
FROM terrestre.truck t, terrestre.company c, portuario.vessel_call vc
WHERE t.placa = 'ABC123' AND c.ruc = '20123456789' AND vc.viaje_id = 'V2024001';

INSERT INTO terrestre.appointment (truck_id, company_id, vessel_call_id, hora_programada, estado, created_at, updated_at)
SELECT t.id, c.id, vc.id, NOW() + INTERVAL '2 days 11 hours', 'PROGRAMADA', NOW(), NOW()
FROM terrestre.truck t, terrestre.company c, portuario.vessel_call vc
WHERE t.placa = 'DEF456' AND c.ruc = '20123456789' AND vc.viaje_id = 'V2024001';

-- Aduanas: Entidades
INSERT INTO aduanas.entidad (code, name, created_at, updated_at) VALUES
('SUNAT', 'Superintendencia Nacional de Aduanas', NOW(), NOW()),
('VUCE', 'Ventanilla Única de Comercio Exterior', NOW(), NOW()),
('SENASA', 'Servicio Nacional de Sanidad Agraria', NOW(), NOW())
ON CONFLICT (code) DO NOTHING;

-- Aduanas: Trámites
INSERT INTO aduanas.tramite (tramite_ext_id, vessel_call_id, regimen, subpartida, estado, fecha_inicio, entidad_id, created_at, updated_at)
SELECT 'TRM2024001', vc.id, 'IMPORTACION', '8703.23.00.00', 'EN_PROCESO', NOW() - INTERVAL '5 days', e.id, NOW(), NOW()
FROM portuario.vessel_call vc, aduanas.entidad e
WHERE vc.viaje_id = 'V2024001' AND e.code = 'SUNAT'
ON CONFLICT (tramite_ext_id) DO NOTHING;

INSERT INTO aduanas.tramite (tramite_ext_id, vessel_call_id, regimen, subpartida, estado, fecha_inicio, fecha_fin, entidad_id, created_at, updated_at)
SELECT 'TRM2024002', vc.id, 'EXPORTACION', '0709.60.00.00', 'COMPLETO', NOW() - INTERVAL '10 days', NOW() - INTERVAL '2 days', e.id, NOW(), NOW()
FROM portuario.vessel_call vc, aduanas.entidad e
WHERE vc.viaje_id = 'V2024001' AND e.code = 'VUCE'
ON CONFLICT (tramite_ext_id) DO NOTHING;

-- Analytics: KPI Definitions
INSERT INTO analytics.kpi_definition (code, name, description, created_at, updated_at) VALUES
('turnaround_h', 'Turnaround Time (horas)', 'Tiempo total de permanencia de nave en puerto', NOW(), NOW()),
('espera_camion_h', 'Tiempo Espera Camión (horas)', 'Tiempo promedio de espera de camiones', NOW(), NOW()),
('cumpl_citas_pct', 'Cumplimiento Citas (%)', 'Porcentaje de citas cumplidas a tiempo', NOW(), NOW()),
('tramites_ok_pct', 'Trámites Completos (%)', 'Porcentaje de trámites completados sin incidencias', NOW(), NOW())
ON CONFLICT (code) DO NOTHING;

-- Analytics: KPI Values
INSERT INTO analytics.kpi_value (kpi_id, periodo, valor, meta, fuente, created_at, updated_at)
SELECT k.id, CURRENT_DATE - INTERVAL '7 days', 48.5, 36.0, 'portuario.vessel_call', NOW(), NOW()
FROM analytics.kpi_definition k WHERE k.code = 'turnaround_h';

-- Analytics: SLA Definitions
INSERT INTO analytics.sla_definition (code, name, umbral, comparador, created_at, updated_at) VALUES
('turnaround_max', 'Turnaround Máximo', 48.0, '<=', NOW(), NOW()),
('espera_max', 'Espera Máxima Camión', 6.0, '<=', NOW(), NOW())
ON CONFLICT (code) DO NOTHING;

-- Verificar datos insertados
SELECT 'Berths' as tabla, COUNT(*) as registros FROM portuario.berth
UNION ALL
SELECT 'Vessels', COUNT(*) FROM portuario.vessel
UNION ALL
SELECT 'Vessel Calls', COUNT(*) FROM portuario.vessel_call
UNION ALL
SELECT 'Companies', COUNT(*) FROM terrestre.company
UNION ALL
SELECT 'Trucks', COUNT(*) FROM terrestre.truck
UNION ALL
SELECT 'Gates', COUNT(*) FROM terrestre.gate
UNION ALL
SELECT 'Appointments', COUNT(*) FROM terrestre.appointment
UNION ALL
SELECT 'Entidades', COUNT(*) FROM aduanas.entidad
UNION ALL
SELECT 'Trámites', COUNT(*) FROM aduanas.tramite
UNION ALL
SELECT 'KPI Definitions', COUNT(*) FROM analytics.kpi_definition
UNION ALL
SELECT 'SLA Definitions', COUNT(*) FROM analytics.sla_definition;
