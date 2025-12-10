# SGCMI - Sistema de Gestión y Coordinación Multimodal Integrado

Sistema para optimizar la coordinación logística en el corredor Matarani–Sur Andino.

## Stack Tecnológico

- **Backend**: Laravel 11, PHP 8.2+
- **Frontend**: Blade + Tailwind CSS + Alpine.js
- **Base de Datos**: PostgreSQL 14+
- **Queue**: Database driver
- **Cache/Sessions**: Filesystem

## Requisitos Previos

- PHP 8.2 o superior
- Composer
- PostgreSQL 14+
- Node.js y NPM (para assets)

## Instalación Local

### 1. Configurar Base de Datos

Crear la base de datos PostgreSQL:

```bash
psql -U postgres
CREATE DATABASE sgcmi;
\q
```

### 2. Configurar Variables de Entorno

El archivo `.env` ya está configurado con:

```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=sgcmi
DB_USERNAME=postgres
DB_PASSWORD=1234
```

### 3. Instalar Dependencias

```bash
cd sgcmi
composer install
npm install
```

### 4. Generar Application Key

```bash
php artisan key:generate
```

### 5. Ejecutar Migraciones y Seeders

```bash
php artisan migrate
php artisan db:seed
```

Esto creará:
- 8 schemas PostgreSQL (admin, portuario, terrestre, aduanas, analytics, audit, reports)
- Todas las tablas del sistema
- 9 usuarios demo (uno por rol)
- Datos de ejemplo (muelles, naves, citas, trámites)

### 6. Compilar Assets

```bash
npm run dev
```

### 7. Iniciar Servidor de Desarrollo

```bash
php artisan serve
```

El sistema estará disponible en: `http://localhost:8000`

## Usuarios Demo

Todos los usuarios tienen contraseña: `password123`

| Usuario | Email | Rol |
|---------|-------|-----|
| admin | admin@sgcmi.pe | ADMIN |
| planificador | planificador@sgcmi.pe | PLANIFICADOR_PUERTO |
| operaciones | operaciones@sgcmi.pe | OPERACIONES_PUERTO |
| gates | gates@sgcmi.pe | OPERADOR_GATES |
| transportista | transportista@sgcmi.pe | TRANSPORTISTA |
| aduana | aduana@sgcmi.pe | AGENTE_ADUANA |
| analista | analista@sgcmi.pe | ANALISTA |
| directivo | directivo@sgcmi.pe | DIRECTIVO |
| auditor | auditor@sgcmi.pe | AUDITOR |

## Módulos del Sistema

### Portuario
- Gestión de naves y llamadas
- Programación vs ejecución
- Turnaround de naves
- Utilización de muelles

### Terrestre
- Gestión de citas de camiones
- Control de gates
- Tiempos de espera
- Productividad de gates

### Aduanas
- Gestión de trámites
- Estado por nave
- Tiempos de despacho
- Incidencias documentales

### Analytics
- Panel de KPIs
- Early Warning
- Cumplimiento de SLAs

## Reportes Disponibles

1. **R1**: Programación vs Ejecución
2. **R2**: Turnaround de Naves
3. **R3**: Utilización de Muelles
4. **R4**: Espera de Camiones
5. **R5**: Cumplimiento de Citas
6. **R6**: Productividad de Gates
7. **R7**: Estado de Trámites por Nave
8. **R8**: Tiempo de Despacho por Régimen
9. **R9**: Incidencias de Documentación
10. **R10**: Panel de KPIs
11. **R11**: Early Warning
12. **R12**: Cumplimiento de SLAs

## Comandos Útiles

```bash
# Ejecutar tests
php artisan test

# Limpiar cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Ver rutas
php artisan route:list

# Ejecutar queue worker
php artisan queue:work

# Crear nueva migración
php artisan make:migration nombre_migracion

# Crear nuevo modelo
php artisan make:model NombreModelo

# Crear nuevo controller
php artisan make:controller NombreController
```

## Arquitectura

El proyecto sigue una arquitectura en capas:

- **Controllers**: Manejo de requests HTTP
- **Requests**: Validación de datos (FormRequest)
- **Policies**: Autorización y permisos
- **Services**: Lógica de negocio
- **Repositories**: Acceso a datos
- **Models**: Eloquent ORM
- **Jobs**: Tareas asíncronas

## Seguridad

- RBAC implementado con 9 roles y 19 permisos
- Políticas de autorización en todas las rutas protegidas
- PII enmascarado en logs (placa, tramite_ext_id)
- CSRF y CORS habilitados
- Rate limiting en exportaciones (5/minuto)

## Desarrollo

El proyecto sigue:
- PSR-12 para estilo de código
- Strict types en todos los archivos PHP
- Snake_case para columnas de BD
- StudlyCase para modelos Eloquent
- PascalCase para controllers

## Soporte

Para más información, consultar la especificación completa en `.kiro/specs/sgcmi.yml`
