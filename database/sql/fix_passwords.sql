-- SGCMI: Actualizar contraseñas de usuarios
-- Password para todos: password123

UPDATE admin.users 
SET password = '$2y$10$djdCV5flNpdhxnBBwb/xGOfYHgQRWPBPnisiANfWJdjygufofPiP2'
WHERE username IN ('admin', 'planificador', 'operaciones', 'gates', 'transportista', 'aduana', 'analista', 'directivo', 'auditor');

-- Verificar actualización
SELECT username, 
       CASE 
           WHEN password = '$2y$10$djdCV5flNpdhxnBBwb/xGOfYHgQRWPBPnisiANfWJdjygufofPiP2' THEN '✓ OK'
           ELSE '✗ ERROR'
       END as password_status
FROM admin.users
ORDER BY username;
