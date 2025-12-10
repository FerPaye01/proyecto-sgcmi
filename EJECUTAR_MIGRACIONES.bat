@echo off
echo ========================================
echo SGCMI - Ejecutar Migraciones
echo ========================================
echo.

echo Verificando Laravel...
php artisan --version
if errorlevel 1 (
    echo ERROR: Laravel no esta disponible
    pause
    exit /b 1
)
echo.

echo ========================================
echo OPCION 1: Migraciones Laravel
echo ========================================
echo.
echo Ejecutando migraciones...
php artisan migrate
if errorlevel 1 (
    echo ERROR: Fallo la migracion
    echo.
    echo Intente la Opcion 2: SQL Directo
    pause
    exit /b 1
)
echo.

echo Ejecutando seeders...
php artisan db:seed
if errorlevel 1 (
    echo ERROR: Fallo el seeding
    pause
    exit /b 1
)
echo.

echo ========================================
echo MIGRACIONES COMPLETADAS
echo ========================================
echo.
echo Usuarios demo creados (password: password123):
echo - admin@sgcmi.pe (ADMIN)
echo - planificador@sgcmi.pe (PLANIFICADOR_PUERTO)
echo - operaciones@sgcmi.pe (OPERACIONES_PUERTO)
echo - gates@sgcmi.pe (OPERADOR_GATES)
echo - transportista@sgcmi.pe (TRANSPORTISTA)
echo - aduana@sgcmi.pe (AGENTE_ADUANA)
echo - analista@sgcmi.pe (ANALISTA)
echo - directivo@sgcmi.pe (DIRECTIVO)
echo - auditor@sgcmi.pe (AUDITOR)
echo.
echo Datos demo:
echo - 3 Muelles
echo - 3 Naves
echo - 4 Llamadas de Naves
echo - 2 Empresas
echo - 3 Camiones
echo - 6 Citas
echo - 2 Tramites Aduaneros
echo.
pause
