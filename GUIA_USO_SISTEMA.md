# 🚢 SGCMI - Guía de Uso del Sistema

## 🎯 Cómo Usar el Sistema SGCMI

### 1️⃣ Iniciar el Servidor

Abre PowerShell en la carpeta del proyecto y ejecuta:

```powershell
cd E:\OSCAR\Project-Hack\sgcmi
php -S localhost:8000 -t public
```

Verás un mensaje como:
```
[Tue Oct 21 12:00:00 2025] PHP 8.3.26 Development Server (http://localhost:8000) started
```

### 2️⃣ Acceder al Sistema

Abre tu navegador y ve a:
```
http://localhost:8000
```

### 3️⃣ Iniciar Sesión

El sistema te mostrará la pantalla de login con 9 usuarios demo disponibles.

**Usuarios Demo** (todos con contraseña: `password123`):

| Usuario | Rol | Permisos Principales |
|---------|-----|---------------------|
| **admin** | ADMIN | Acceso total al sistema |
| **planificador** | PLANIFICADOR_PUERTO | Gestión de naves, reportes portuarios |
| **operaciones** | OPERACIONES_PUERTO | Visualización de operaciones |
| **gates** | OPERADOR_GATES | Gestión de citas y gates |
| **transportista** | TRANSPORTISTA | Ver sus propias citas |
| **aduana** | AGENTE_ADUANA | Gestión de trámites aduaneros |
| **analista** | ANALISTA | Reportes y KPIs |
| **directivo** | DIRECTIVO | Dashboard ejecutivo y KPIs |
| **auditor** | AUDITOR | Auditoría y reportes |

**Tip**: Haz click en cualquier badge de usuario para autocompletar el formulario.

---

## 📋 Casos de Uso Principales

### 🎯 Caso de Uso 1: Login y Dashboard

**Rol**: Cualquier usuario

1. **Login**:
   - Ingresa usuario: `admin`
   - Contraseña: `password123`
   - Click en "Iniciar Sesión"

2. **Dashboard**:
   - Verás 4 tarjetas con estadísticas en tiempo real:
     - Naves Programadas
     - Citas Pendientes
     - Trámites en Proceso
     - Usuarios Activos
   - Tablas con llamadas de naves recientes
   - Tablas con citas de camiones recientes

---

### 🚢 Caso de Uso 2: Gestión de Llamadas de Naves

**Rol**: `planificador` o `admin`

1. **Login** como `planificador`

2. **Ir a Naves**:
   - Click en "Naves" en el menú superior

3. **Ver Estadísticas**:
   - Total de llamadas
   - Programadas, En Curso, Completadas

4. **Filtrar Datos**:
   - Selecciona rango de fechas
   - Filtra por muelle (M1, M2, M3)
   - Click en "Filtrar"

5. **Ver Detalles**:
   - Tabla con todas las llamadas
   - Información: Nave, IMO, Viaje, Muelle, ETA, ETB, ATA, Estado

**Datos Demo Disponibles**:
- MSC MARINA - Viaje V2024001 - Muelle 1
- MAERSK LIMA - Viaje V2024002 - Muelle 2

---

### 🚛 Caso de Uso 3: Gestión de Citas de Camiones

**Rol**: `gates` o `admin`

1. **Login** como `gates`

2. **Ir a Citas**:
   - Click en "Citas" en el menú superior

3. **Ver Estadísticas**:
   - Total de citas
   - Programadas, Completadas, No Show

4. **Filtrar por Fecha**:
   - Selecciona una fecha
   - Filtra por estado
   - Click en "Filtrar"

5. **Ver Detalles**:
   - Placa del camión
   - Empresa transportista
   - Nave asociada
   - Hora programada vs hora de llegada
   - Estado actual

**Datos Demo Disponibles**:
- Camión ABC123 - Transportes del Sur SAC
- Camión DEF456 - Transportes del Sur SAC

---

### 📊 Caso de Uso 4: Reporte R1 - Programación vs Ejecución

**Rol**: `analista`, `planificador`, o `admin`

1. **Login** como `analista`

2. **Ir a Reportes**:
   - Click en "Reportes" en el menú superior
   - O desde Dashboard, click en "Ver Reporte R1"

3. **Ver KPIs del Reporte**:
   - Total de llamadas
   - % de Puntualidad de arribo
   - Demora promedio ETA-ATA
   - Naves con arribo real

4. **Filtrar Período**:
   - Selecciona fecha desde/hasta
   - Click en "Generar Reporte"

5. **Analizar Datos**:
   - Tabla detallada con cada nave
   - Comparación ETA vs ATA
   - Demoras en horas
   - Indicador de puntualidad (✓ Sí / ✗ No)

6. **Exportar**:
   - Click en "📥 Exportar CSV"
   - Se descarga archivo con todos los datos

**Criterios**:
- Puntual: diferencia ETA-ATA ≤ 2 horas
- Demora: tiempo en horas entre ETA y ATA

---

### ⏱️ Caso de Uso 5: Reporte R4 - Tiempo de Espera de Camiones

**Rol**: `analista`, `gates`, o `admin`

1. **Login** como `analista`

2. **Ir a Reporte R4**:
   - Desde menú "Reportes" → "Ver Reporte R4"
   - O desde página de Citas → "Ver Reporte R4"

3. **Ver KPIs**:
   - Citas con llegada registrada
   - Espera promedio en horas
   - % de esperas > 6 horas
   - Citas atendidas

4. **Filtrar Período**:
   - Selecciona rango de fechas
   - Click en "Generar Reporte"

5. **Analizar Tiempos**:
   - Tabla con cada cita
   - Tiempo de espera calculado
   - Alertas de espera excesiva
   - Colores: Verde (< 3h), Naranja (3-6h), Rojo (> 6h)

6. **Exportar**:
   - Click en "📥 Exportar XLSX"
   - Se descarga archivo CSV

**Criterios**:
- Espera Normal: < 6 horas
- Espera Excesiva: > 6 horas
- SLA Objetivo: 90% de citas < 6 horas

---

### 📋 Caso de Uso 6: Gestión de Trámites Aduaneros

**Rol**: `aduana` o `admin`

1. **Login** como `aduana`

2. **Ir a Trámites**:
   - Click en "Trámites" en el menú superior

3. **Ver Estadísticas**:
   - Total de trámites
   - En proceso
   - Completos
   - Tasa de completitud

4. **Filtrar**:
   - Por estado: Iniciado, En Proceso, Completo, Rechazado
   - Por régimen: Importación, Exportación, Tránsito
   - Click en "Filtrar"

5. **Ver Detalles**:
   - ID del trámite
   - Régimen y subpartida arancelaria
   - Nave asociada
   - Entidad (SUNAT, VUCE, SENASA)
   - Duración del trámite
   - Estado actual

**Datos Demo Disponibles**:
- TRM2024001 - Importación - En Proceso - SUNAT
- TRM2024002 - Exportación - Completo - VUCE

**SLA**: Completar trámites en < 72 horas (3 días)

---

### 📈 Caso de Uso 7: Panel de KPIs

**Rol**: `directivo`, `analista`, o `admin`

1. **Login** como `directivo`

2. **Ir a KPIs**:
   - Click en "KPIs" en el menú superior

3. **Ver KPIs en Tiempo Real**:
   - Naves programadas
   - Citas pendientes
   - Trámites en proceso
   - % de trámites completos

4. **Ver KPIs Históricos**:
   - Tabla con KPIs definidos
   - Valores actuales vs metas
   - Indicador de cumplimiento
   - Última actualización

5. **Acciones Rápidas**:
   - Acceso directo a reportes R1 y R4
   - Gestión de naves y citas

**KPIs Disponibles**:
- turnaround_h: Tiempo de permanencia de nave
- espera_camion_h: Tiempo de espera promedio
- cumpl_citas_pct: % de citas cumplidas
- tramites_ok_pct: % de trámites sin incidencias

---

### 🔐 Caso de Uso 8: Control de Acceso por Roles

**Demostración de RBAC**:

1. **Login como `transportista`**:
   - Solo verá Dashboard y Citas
   - En Citas, solo verá las de su empresa (scoping)
   - No tiene acceso a Naves, Trámites, Reportes

2. **Login como `planificador`**:
   - Acceso a Naves (lectura y escritura)
   - Acceso a Reportes portuarios
   - No tiene acceso a Trámites aduaneros

3. **Login como `admin`**:
   - Acceso total a todos los módulos
   - Todos los permisos habilitados

---

## 🎨 Características de la Interfaz

### Navegación
- **Menú Superior**: Acceso rápido a módulos según permisos
- **Usuario Actual**: Muestra nombre y rol en esquina superior derecha
- **Botón Salir**: Cierra sesión de forma segura

### Dashboard
- **Tarjetas de Estadísticas**: KPIs en tiempo real
- **Tablas Recientes**: Últimas operaciones
- **Enlaces Rápidos**: Acceso directo a módulos

### Filtros
- **Fechas**: Selección de rangos
- **Estados**: Filtrado por estado de operación
- **Entidades**: Filtrado por muelle, empresa, etc.

### Reportes
- **KPIs Calculados**: Métricas en tiempo real
- **Tablas Detalladas**: Datos completos
- **Exportación**: CSV/XLSX para análisis externo
- **Alertas Visuales**: Colores según umbrales

### Seguridad
- **Autenticación**: Login con usuario/contraseña
- **Autorización**: Permisos por rol (RBAC)
- **Auditoría**: Registro de acciones en audit.audit_log
- **Scoping**: Datos filtrados por empresa (transportistas)

---

## 🔧 Funcionalidades Técnicas

### Base de Datos
- **PostgreSQL**: 7 schemas, 22 tablas
- **Datos Demo**: 9 usuarios, 3 naves, 2 citas, 2 trámites
- **Relaciones**: Foreign keys entre tablas
- **Índices**: Optimización de consultas

### Seguridad
- **Passwords**: Hasheados con bcrypt
- **Sessions**: PHP sessions para autenticación
- **Policies**: Verificación de permisos en cada página
- **SQL Injection**: Prepared statements

### Performance
- **Consultas Optimizadas**: JOINs eficientes
- **Índices**: En campos de búsqueda frecuente
- **Cálculos en DB**: KPIs calculados en PostgreSQL

---

## 📊 Datos Demo Disponibles

### Usuarios (9)
- admin, planificador, operaciones, gates, transportista, aduana, analista, directivo, auditor

### Naves (3)
- MSC MARINA (IMO9876543)
- MAERSK LIMA (IMO9876544)
- CMA CGM ANDES (IMO9876545)

### Muelles (3)
- Muelle 1 (M1) - Capacidad 50,000
- Muelle 2 (M2) - Capacidad 60,000
- Muelle 3 (M3) - Capacidad 45,000

### Empresas (2)
- Transportes del Sur SAC (RUC: 20123456789)
- Logística Andina EIRL (RUC: 20987654321)

### Camiones (3)
- ABC123, DEF456, GHI789

### Entidades Aduaneras (3)
- SUNAT, VUCE, SENASA

---

## 🚀 Próximos Pasos

Para extender el sistema:

1. **Agregar más datos demo**:
   ```sql
   INSERT INTO portuario.vessel_call ...
   ```

2. **Implementar más reportes**:
   - R2: Turnaround de Naves
   - R3: Utilización de Muelles
   - R5-R12: Otros reportes

3. **Agregar funcionalidad CRUD**:
   - Crear nuevas naves
   - Editar citas
   - Actualizar trámites

4. **Mejorar UI**:
   - Gráficos con Chart.js
   - Tablas con DataTables
   - Notificaciones en tiempo real

---

## 🎉 ¡Listo para Usar!

El sistema SGCMI está completamente funcional con:
- ✅ Login con 9 roles diferentes
- ✅ Dashboard con estadísticas en tiempo real
- ✅ Gestión de naves y citas
- ✅ Trámites aduaneros
- ✅ 2 reportes completos (R1, R4)
- ✅ Panel de KPIs
- ✅ Exportación de datos
- ✅ Control de acceso por roles (RBAC)
- ✅ Auditoría de acciones

**¡Explora el sistema y prueba todos los casos de uso!** 🚀
