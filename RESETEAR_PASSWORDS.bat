@echo off
echo ========================================
echo    SGCMI - Resetear Passwords
echo ========================================
echo.
echo Este script resetea todas las passwords a: password123
echo.
pause

cd /d "%~dp0"

echo Actualizando passwords en la base de datos...
set PGPASSWORD=1234
psql -U postgres -d sgcmi -f database\sql\fix_passwords.sql

echo.
echo ========================================
echo    Passwords Actualizadas
echo ========================================
echo.
echo Todos los usuarios ahora tienen password: password123
echo.
echo Usuarios disponibles:
echo   - admin
echo   - planificador
echo   - operaciones
echo   - gates
echo   - transportista
echo   - aduana
echo   - analista
echo   - directivo
echo   - auditor
echo.
pause
