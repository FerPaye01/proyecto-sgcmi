# Frontend Setup - Tailwind CSS y Alpine.js

## Configuración Completada

Este proyecto utiliza **Tailwind CSS** para estilos y **Alpine.js** para interactividad del lado del cliente.

## Stack Frontend

- **Tailwind CSS 3.4**: Framework de utilidades CSS
- **Alpine.js 3.13**: Framework JavaScript ligero para interactividad
- **Vite 5.0**: Build tool y dev server
- **Laravel Vite Plugin**: Integración con Laravel

## Estructura de Archivos

```
sgcmi/
├── package.json              # Dependencias npm
├── vite.config.js           # Configuración de Vite
├── postcss.config.js        # Configuración de PostCSS
├── tailwind.config.js       # Configuración de Tailwind
├── resources/
│   ├── css/
│   │   └── app.css          # Estilos principales con Tailwind
│   ├── js/
│   │   ├── app.js           # Punto de entrada JS con Alpine
│   │   └── bootstrap.js     # Configuración de Axios
│   └── views/
│       ├── layouts/
│       │   └── app.blade.php    # Layout principal
│       └── components/
│           └── filter-panel.blade.php  # Componente de filtros
└── public/
    └── build/               # Assets compilados (generados)
```

## Comandos Disponibles

### Desarrollo
```bash
npm run dev
```
Inicia el servidor de desarrollo de Vite con hot reload.

### Producción
```bash
npm run build
```
Compila los assets para producción (minificados y optimizados).

## Uso en Blade Templates

### Incluir Assets en Layout

```blade
<!DOCTYPE html>
<html>
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <!-- Contenido -->
</body>
</html>
```

### Clases Tailwind Personalizadas

El proyecto incluye clases de utilidad personalizadas:

```blade
<!-- Botones -->
<button class="btn-primary">Guardar</button>
<button class="btn-secondary">Cancelar</button>
<button class="btn-danger">Eliminar</button>

<!-- Cards -->
<div class="card">
    <h3>Título</h3>
    <p>Contenido</p>
</div>

<!-- Inputs -->
<input type="text" class="input-field" />

<!-- Badges -->
<span class="badge-success">Aprobado</span>
<span class="badge-warning">Pendiente</span>
<span class="badge-danger">Rechazado</span>
<span class="badge-info">En Proceso</span>

<!-- Tablas -->
<table>
    <thead class="table-header">
        <tr><th>Columna</th></tr>
    </thead>
    <tbody>
        <tr class="table-row"><td>Dato</td></tr>
    </tbody>
</table>
```

## Componentes Alpine.js Disponibles

### 1. Report Filters
Componente para filtros de reportes con persistencia en URL.

```blade
<div x-data="reportFilters()">
    <input type="date" x-model="filters.fecha_desde" />
    <button @click="applyFilters()">Aplicar</button>
    <button @click="clearFilters()">Limpiar</button>
</div>
```

### 2. Date Validator
Validación de fechas con reglas de negocio (ETB >= ETA, etc.).

```blade
<div x-data="dateValidator()">
    <input type="datetime-local" x-model="eta" @change="validateDates()" />
    <input type="datetime-local" x-model="etb" @change="validateDates()" />
    <span x-show="hasError('etb')" x-text="getError('etb')"></span>
</div>
```

### 3. KPI Panel
Panel de KPIs con auto-refresh cada 5 minutos.

```blade
<div x-data="kpiPanel(300000)">
    <button @click="refresh()" :disabled="loading">
        Actualizar
    </button>
    <span x-text="getLastUpdateText()"></span>
</div>
```

### 4. Modal
Modal reutilizable.

```blade
<div x-data="modal()">
    <button @click="show()">Abrir Modal</button>
    
    <div x-show="open" @click.away="hide()">
        <!-- Contenido del modal -->
        <button @click="hide()">Cerrar</button>
    </div>
</div>
```

### 5. Confirm Dialog
Diálogo de confirmación.

```blade
<div x-data="confirmDialog()">
    <button @click="confirm('¿Está seguro?', () => { /* acción */ })">
        Eliminar
    </button>
    
    <div x-show="show">
        <p x-text="message"></p>
        <button @click="handleConfirm()">Confirmar</button>
        <button @click="handleCancel()">Cancelar</button>
    </div>
</div>
```

### 6. Appointment Validator
Validador de capacidad de citas.

```blade
<div x-data="appointmentValidator(10)">
    <input type="datetime-local" x-model="hora_programada" />
    <span x-text="getCapacityText()"></span>
    <span x-show="isOverCapacity()">Capacidad excedida</span>
</div>
```

## Colores Personalizados

El proyecto incluye una paleta de colores personalizada `sgcmi-blue`:

```javascript
colors: {
    'sgcmi-blue': {
        50: '#eff6ff',
        100: '#dbeafe',
        // ... hasta 950
        900: '#1e3a8a',  // Color principal de navegación
        950: '#172554',
    }
}
```

Uso:
```blade
<div class="bg-sgcmi-blue-900 text-white">
    Contenido
</div>
```

## Directivas Alpine.js Comunes

```blade
<!-- Binding de datos -->
<input x-model="variable" />

<!-- Eventos -->
<button @click="funcion()">Click</button>

<!-- Condicionales -->
<div x-show="condicion">Visible si true</div>
<div x-if="condicion">Renderizado si true</div>

<!-- Loops -->
<template x-for="item in items" :key="item.id">
    <div x-text="item.name"></div>
</template>

<!-- Transiciones -->
<div x-show="open" x-transition>
    Contenido con transición
</div>
```

## Integración con Laravel

### CSRF Token
El archivo `bootstrap.js` configura automáticamente el token CSRF en todas las peticiones Axios:

```javascript
window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
```

### Flash Messages
El layout principal incluye manejo automático de mensajes flash con Alpine.js:

```php
// En el controlador
return redirect()->back()->with('success', 'Operación exitosa');
```

## Troubleshooting

### Assets no se cargan
1. Verificar que Vite está corriendo: `npm run dev`
2. O compilar para producción: `npm run build`
3. Verificar que existe `public/build/manifest.json`

### Alpine.js no funciona
1. Verificar que se incluye `@vite(['resources/css/app.css', 'resources/js/app.js'])`
2. Abrir consola del navegador para ver errores
3. Verificar que Alpine.start() se ejecuta

### Tailwind no aplica estilos
1. Verificar que el archivo está en `content` de `tailwind.config.js`
2. Recompilar: `npm run build`
3. Limpiar caché del navegador

## Próximos Pasos

1. Crear vistas Blade para cada módulo usando el layout principal
2. Implementar componentes Alpine.js específicos según necesidades
3. Agregar más utilidades Tailwind personalizadas según diseño
4. Configurar hot reload para desarrollo: `npm run dev`

## Referencias

- [Tailwind CSS Documentation](https://tailwindcss.com/docs)
- [Alpine.js Documentation](https://alpinejs.dev/)
- [Vite Documentation](https://vitejs.dev/)
- [Laravel Vite Documentation](https://laravel.com/docs/11.x/vite)
