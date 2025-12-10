@echo off
echo ========================================
echo    SGCMI - Verificacion del Sistema
echo ========================================
echo.

echo [1/4] Verificando PHP...
php --version
if %errorlevel% neq 0 (
    echo ERROR: PHP no esta instalado o no esta en el PATH
    pause
    exit /b 1
)
echo OK - PHP instalado
echo.

echo [2/4] Verificando extension PostgreSQL...
php -m | findstr /C:"pdo_pgsql" >nul
if %errorlevel% neq 0 (
    echo ERROR: Extension pdo_pgsql no esta habilitada
    echo.
    echo Solucion:
    echo 1. Abre: C:\laragon\bin\php\php-8.3.26-Win32-vs16-x64\php.ini
    echo 2. Busca: ;extension=pdo_pgsql
    echo 3. Quita el punto y coma: extension=pdo_pgsql
    echo 4. Busca: ;extension=pgsql
    echo 5. Quita el punto y coma: extension=pgsql
    echo 6. Guarda y vuelve a ejecutar este script
    pause
    exit /b 1
)
echo OK - Extension pdo_pgsql habilitada
echo.

echo [3/4] Verificando conexion a PostgreSQL...
set PGPASSWORD=1234
psql -U postgres -d sgcmi -c "SELECT 1;" >nul 2>&1
if %errorlevel% neq 0 (
    echo ADVERTENCIA: No se pudo conectar a PostgreSQL
    echo Asegurate de que PostgreSQL este corriendo
    echo.
) else (
    echo OK - Conexion a PostgreSQL exitosa
)
echo.

echo [4/4] Verificando archivos del proyecto...
if not exist "public\index.php" (
    echo ERROR: Archivo public\index.php no encontrado
    pause
    exit /b 1
)
echo OK - Archivos del proyecto encontrados
echo.

echo ========================================
echo    Sistema Verificado Correctamente
echo ========================================
echo.
echo Puedes iniciar el servidor ejecutando:
echo    INICIAR_SERVIDOR.bat
echo.
echo O manualmente con:
echo    php -S localhost:8000 -t public
echo.
pause
