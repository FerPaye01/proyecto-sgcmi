# Guía Rápida: Tailwind CSS y Alpine.js

## Inicio Rápido

### 1. Compilar Assets

**Desarrollo (con hot reload):**
```bash
npm run dev
```

**Producción:**
```bash
npm run build
```

### 2. Usar en Blade

```blade
@extends('layouts.app')

@section('content')
    <div class="card">
        <h1 class="text-2xl font-bold">Mi Contenido</h1>
    </div>
@endsection
```

## Clases Tailwind Más Usadas

### Layout
```blade
<div class="container mx-auto px-4">           <!-- Contenedor centrado -->
<div class="grid grid-cols-3 gap-4">          <!-- Grid de 3 columnas -->
<div class="flex justify-between items-center"> <!-- Flexbox -->
```

### Espaciado
```blade
<div class="p-4">      <!-- Padding 1rem -->
<div class="m-4">      <!-- Margin 1rem -->
<div class="mt-4">     <!-- Margin top -->
<div class="space-y-4"> <!-- Espacio vertical entre hijos -->
```

### Colores
```blade
<div class="bg-sgcmi-blue-900 text-white">  <!-- Fondo azul, texto blanco -->
<div class="bg-gray-100">                    <!-- Fondo gris claro -->
<div class="text-red-600">                   <!-- Texto rojo -->
```

### Tipografía
```blade
<h1 class="text-3xl font-bold">             <!-- Grande y negrita -->
<p class="text-sm text-gray-600">           <!-- Pequeño y gris -->
```

### Bordes y Sombras
```blade
<div class="rounded-lg shadow-md border border-gray-300">
```

### Responsive
```blade
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
<!-- 1 columna en móvil, 2 en tablet, 3 en desktop -->
```

## Componentes Alpine.js

### Datos Reactivos
```blade
<div x-data="{ open: false }">
    <button @click="open = !open">Toggle</button>
    <div x-show="open">Contenido</div>
</div>
```

### Formularios
```blade
<div x-data="{ nombre: '' }">
    <input x-model="nombre" class="input-field" />
    <p>Hola, <span x-text="nombre"></span></p>
</div>
```

### Listas
```blade
<div x-data="{ items: ['A', 'B', 'C'] }">
    <template x-for="item in items" :key="item">
        <div x-text="item"></div>
    </template>
</div>
```

### Eventos
```blade
<button @click="alert('Clicked!')">Click</button>
<input @keyup.enter="submit()">
<div @click.away="close()">Modal</div>
```

### Transiciones
```blade
<div x-show="open" x-transition>
    Aparece/desaparece con animación
</div>
```

## Componentes Personalizados SGCMI

### Filtros de Reporte
```blade
<div x-data="reportFilters()">
    <input type="date" x-model="filters.fecha_desde" />
    <button @click="applyFilters()">Aplicar</button>
</div>
```

### Validador de Fechas
```blade
<div x-data="dateValidator()">
    <input type="datetime-local" x-model="eta" @change="validateDates()" />
    <input type="datetime-local" x-model="etb" @change="validateDates()" />
    <span x-show="hasError('etb')" x-text="getError('etb')"></span>
</div>
```

### Modal
```blade
<div x-data="modal()">
    <button @click="show()">Abrir</button>
    <div x-show="open" @click.away="hide()">
        <!-- Contenido -->
    </div>
</div>
```

### Panel KPI con Auto-refresh
```blade
<div x-data="kpiPanel(300000)">
    <button @click="refresh()">Actualizar</button>
    <span x-text="getLastUpdateText()"></span>
</div>
```

## Clases Personalizadas SGCMI

### Botones
```blade
<button class="btn-primary">Guardar</button>
<button class="btn-secondary">Cancelar</button>
<button class="btn-danger">Eliminar</button>
```

### Cards
```blade
<div class="card">
    <h3>Título</h3>
    <p>Contenido</p>
</div>
```

### Inputs
```blade
<input type="text" class="input-field" />
<select class="input-field">...</select>
<textarea class="input-field"></textarea>
```

### Badges
```blade
<span class="badge-success">Aprobado</span>
<span class="badge-warning">Pendiente</span>
<span class="badge-danger">Rechazado</span>
<span class="badge-info">En Proceso</span>
```

### Tablas
```blade
<table class="min-w-full">
    <thead class="table-header">
        <tr><th class="px-4 py-2">Columna</th></tr>
    </thead>
    <tbody>
        <tr class="table-row">
            <td class="px-4 py-2">Dato</td>
        </tr>
    </tbody>
</table>
```

## Patrones Comunes

### Formulario con Validación
```blade
<div x-data="{ 
    form: { nombre: '', email: '' },
    errors: {},
    submit() {
        this.errors = {};
        if (!this.form.nombre) this.errors.nombre = 'Requerido';
        if (!this.form.email) this.errors.email = 'Requerido';
        if (Object.keys(this.errors).length === 0) {
            // Enviar formulario
        }
    }
}">
    <form @submit.prevent="submit()">
        <input x-model="form.nombre" class="input-field" />
        <span x-show="errors.nombre" x-text="errors.nombre" class="text-red-500"></span>
        
        <button type="submit" class="btn-primary">Enviar</button>
    </form>
</div>
```

### Tabla con Búsqueda
```blade
<div x-data="{ 
    search: '',
    items: [/* datos */],
    get filteredItems() {
        return this.items.filter(i => 
            i.name.toLowerCase().includes(this.search.toLowerCase())
        );
    }
}">
    <input x-model="search" placeholder="Buscar..." class="input-field" />
    
    <table>
        <template x-for="item in filteredItems" :key="item.id">
            <tr><td x-text="item.name"></td></tr>
        </template>
    </table>
</div>
```

### Confirmación de Eliminación
```blade
<div x-data="confirmDialog()">
    <button @click="confirm('¿Eliminar este registro?', () => {
        // Lógica de eliminación
        window.location.href = '/delete/123';
    })" class="btn-danger">
        Eliminar
    </button>
    
    <div x-show="show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white p-6 rounded-lg">
            <p x-text="message"></p>
            <button @click="handleConfirm()" class="btn-danger">Confirmar</button>
            <button @click="handleCancel()" class="btn-secondary">Cancelar</button>
        </div>
    </div>
</div>
```

## Tips y Mejores Prácticas

1. **Usar clases personalizadas** para componentes repetidos
2. **Extraer componentes Alpine** complejos a archivos separados
3. **Usar x-cloak** para evitar flash de contenido sin estilo
4. **Preferir @click sobre onclick** para consistencia
5. **Usar x-transition** para animaciones suaves
6. **Validar en cliente y servidor** siempre

## Debugging

### Ver datos Alpine en consola
```blade
<div x-data="{ count: 0 }" x-init="console.log($data)">
```

### Inspeccionar con DevTools
- Instalar Alpine.js DevTools extension
- Usar `$inspect()` en x-init

### Verificar Tailwind
- Inspeccionar elemento en DevTools
- Verificar que las clases se aplican
- Revisar `tailwind.config.js` si falta algo

## Recursos

- [Tailwind CSS Docs](https://tailwindcss.com/docs)
- [Alpine.js Docs](https://alpinejs.dev/)
- [Tailwind UI Components](https://tailwindui.com/)
- [Alpine.js Examples](https://alpinejs.dev/examples)
