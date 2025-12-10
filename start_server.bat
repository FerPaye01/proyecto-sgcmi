@echo off
cd /d "%~dp0"
echo Iniciando servidor Laravel en http://localhost:8000
php -S localhost:8000 -t public
pause
