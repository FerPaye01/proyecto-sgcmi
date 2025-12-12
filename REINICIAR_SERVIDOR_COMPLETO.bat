@echo off
echo ========================================
echo REINICIANDO SERVIDOR SGCMI
echo ========================================
echo.

echo [1/3] Limpiando cache de Laravel...
php artisan config:clear
php artisan view:clear
echo.

echo [2/3] Deteniendo servidor anterior...
echo Presiona Ctrl+C en la ventana del servidor para detenerlo
echo.

echo [3/3] Para iniciar el servidor nuevamente:
echo Ejecuta: INICIAR_SERVIDOR.bat
echo.

echo ========================================
echo LISTO! Ahora ejecuta INICIAR_SERVIDOR.bat
echo ========================================
pause
