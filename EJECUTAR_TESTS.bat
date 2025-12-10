@echo off
echo ========================================
echo SGCMI - Ejecutar Tests
echo ========================================
echo.

echo Ejecutando suite de tests...
php artisan test
echo.

echo ========================================
echo Ejecutando PHPStan (Analisis Estatico)
echo ========================================
echo.
vendor\bin\phpstan analyse
echo.

echo ========================================
echo Tests Completados
echo ========================================
echo.
echo Revise los resultados arriba.
echo Target: 25 tests minimo, 50%% coverage
echo.
pause
