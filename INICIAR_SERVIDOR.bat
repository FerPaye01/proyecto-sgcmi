@echo off
echo ========================================
echo    SGCMI - Iniciando Servidor Web
echo ========================================
echo.
echo Servidor iniciando en: http://localhost:8000
echo.
echo Presiona Ctrl+C para detener el servidor
echo ========================================
echo.

cd /d "%~dp0"
php artisan serve

pause
