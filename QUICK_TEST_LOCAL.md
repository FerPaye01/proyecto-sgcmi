# Guía Rápida: Probar SGCMI en Local

## Requisitos Previos
- PHP 8.2+
- PostgreSQL 14+
- Composer
- Node.js (para Tailwind/Vite)

## Paso 1: Configurar Base de Datos

### Opción A: Crear BD desde cero
```bash
# En PostgreSQL
createdb sgcmi
createuser -P postgres  # Si no existe (contraseña: 1234)
```

### Opción B: Usar script SQL
```bash
# En la carpeta sgcmi
psql -U postgres -d sgcmi -f database/sql/01_create_schemas.sql
psql -U postgres -d sgcmi -f database/sql/02_create_admin_tables.sql
psql -U postgres -d sgcmi -f database/sql/03_create_audit_tables.sql
psql -U postgres -d sgcmi -f database/sql/04_create_portuario_tables.sql
psql -U postgres -d sgcmi -f database/sql/05_create_terrestre_tables.sql
psql -U postgres -d sgcmi -f database/sql/06_create_aduanas_tables.sql
psql -U postgres -d sgcmi -f database/sql/07_create_analytics_tables.sql
psql -U postgres -d sgcmi -f database/sql/08_seed_roles_permissions.sql
psql -U postgres -d sgcmi -f database/sql/09_seed_users.sql
psql -U postgres -d sgcmi -f database/sql/10_seed_demo_data.sql
```

## Paso 2: Instalar Dependencias

```bash
cd sgcmi

# Instalar dependencias PHP
composer install

# Instalar dependencias Node
npm install

# Compilar assets
npm run build
```

## Paso 3: Configurar .env

```bash
# Copiar archivo de ejemplo
cp .env.example .env

# Generar APP_KEY
php artisan key:generate

# Verificar configuración de BD en .env:
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=sgcmi
# DB_USERNAME=postgres
# DB_PASSWORD=1234
```

## Paso 4: Ejecutar Migraciones (si no usaste scripts SQL)

```bash
php artisan migrate
php artisan db:seed
```

## Paso 5: Iniciar Servidor

```bash
# Terminal 1: Servidor Laravel
php artisan serve

# Terminal 2: Compilar assets en tiempo real (opcional)
npm run dev
```

El servidor estará en: **http://localhost:8000**

---

## Credenciales de Prueba

### Usuario Admin
- **Email**: admin@sgcmi.local
- **Contraseña**: password

### Usuario Operaciones Puerto
- **Email**: operaciones@sgcmi.local
- **Contraseña**: password

### Usuario Transportista
- **Email**: transportista@sgcmi.local
- **Contraseña**: password

---

## Probar Notificaciones Push (Mock)

### Opción 1: Script PHP directo
```bash
php test_notifications.php
```

Verás:
- ✅ Notificaciones creadas
- ✅ Guardadas en JSON
- ✅ Filtradas por rol
- ✅ Contadas por tipo

### Opción 2: Acceder a través del navegador

1. **Login**: http://localhost:8000/login
   - Email: `operaciones@sgcmi.local`
   - Contraseña: `password`

2. **Ver Reporte R11 (Alertas Tempranas)**:
   - URL: http://localhost:8000/analytics/early-warning
   - Verás alertas de congestión y acumulación de camiones

3. **API de Alertas (JSON)**:
   - URL: http://localhost:8000/analytics/early-warning/api
   - Retorna datos en JSON para polling con Alpine.js

### Opción 3: Ejecutar Tests

```bash
# Todos los tests
php artisan test

# Solo tests de notificaciones
php artisan test tests/Feature/PushNotificationsTest.php

# Solo tests de R11
php artisan test tests/Feature/ReportR11EarlyWarningTest.php
```

---

## Estructura de Notificaciones

Las notificaciones se guardan en: `storage/app/mocks/notifications.json`

Formato:
```json
[
  {
    "timestamp": "2025-12-02 21:39:31",
    "destinatarios": ["OPERACIONES_PUERTO", "PLANIFICADOR_PUERTO"],
    "alertas": [
      {
        "id": "ALERT_001",
        "tipo": "CONGESTIÓN_MUELLE",
        "nivel": "AMARILLO",
        "descripción": "...",
        "acciones_recomendadas": [...]
      }
    ]
  }
]
```

---

## Rutas Disponibles

### Reportes
- `GET /analytics/early-warning` - R11 (Alertas Tempranas)
- `GET /analytics/early-warning/api` - R11 API (JSON)
- `GET /portuario/vessel-calls` - R1 (Programación vs Ejecución)
- `GET /reports/port/berth-utilization` - R3 (Utilización de Muelles)
- `GET /reports/road/gate-productivity` - R6 (Productividad de Gates)
- `GET /reports/road/waiting-time` - R4 (Tiempo de Espera)
- `GET /reports/road/appointments-compliance` - R5 (Cumplimiento de Citas)
- `GET /reports/cus/status-by-vessel` - R7 (Estado de Trámites)
- `GET /reports/cus/dispatch-time` - R8 (Tiempo de Despacho)
- `GET /reports/cus/doc-incidents` - R9 (Incidencias Documentación)
- `GET /reports/kpi/panel` - R10 (Panel de KPIs)

### Módulos
- `GET /portuario/vessel-calls` - Gestión de Llamadas de Naves
- `GET /terrestre/appointments` - Gestión de Citas
- `GET /terrestre/gate-events` - Eventos de Gates
- `GET /aduanas/tramites` - Gestión de Trámites

---

## Troubleshooting

### Error: "SQLSTATE[08006]"
- Verificar que PostgreSQL está corriendo
- Verificar credenciales en `.env`

### Error: "Class not found"
```bash
composer dump-autoload
```

### Error: "No such file or directory" (assets)
```bash
npm install
npm run build
```

### Limpiar cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

---

## Archivos Importantes

- **Servicio de Notificaciones**: `app/Services/NotificationService.php`
- **Reporte R11**: `app/Services/ReportService.php` (método `generateR11()`)
- **Controlador**: `app/Http/Controllers/ReportController.php`
- **Tests**: `tests/Feature/PushNotificationsTest.php`
- **Mock File**: `storage/app/mocks/notifications.json`
- **Documentación**: `PUSH_NOTIFICATIONS_IMPLEMENTATION.md`

---

## Próximos Pasos

1. ✅ Probar notificaciones con `php test_notifications.php`
2. ✅ Acceder a http://localhost:8000 y hacer login
3. ✅ Navegar a `/analytics/early-warning` para ver alertas
4. ✅ Revisar `storage/app/mocks/notifications.json` para ver datos guardados
5. ✅ Ejecutar tests: `php artisan test`

---

## Soporte

Para más información, ver:
- `PUSH_NOTIFICATIONS_IMPLEMENTATION.md` - Documentación técnica
- `GUIA_USO_SISTEMA.md` - Guía general del sistema
- `.kiro/specs/sgcmi/design.md` - Diseño técnico
- `.kiro/specs/sgcmi/requirements.md` - Requisitos
