# Guía de Tablas Interactivas

## Descripción

Sistema de tablas interactivas implementado con Alpine.js que proporciona:

- ✅ **Búsqueda en tiempo real** - Filtra datos mientras escribes
- ✅ **Ordenamiento por columnas** - Click en encabezados para ordenar
- ✅ **Paginación dinámica** - Navega entre páginas con selector de tamaño
- ✅ **Toggle de columnas** - Muestra/oculta columnas según necesites
- ✅ **Exportación a CSV** - Descarga los datos filtrados
- ✅ **Responsive** - Funciona en móviles y tablets

## Uso Básico

### 1. Componente Blade

```blade
<x-interactive-table 
    :headers="$headers"
    :data="$data"
    :searchable="true"
    :sortable="true"
    :paginate="true"
    :perPage="10"
    :columnToggle="true"
/>
```

### 2. Preparar Datos en el Controlador

```php
public function index()
{
    $data = VesselCall::with(['vessel', 'berth'])
        ->get()
        ->map(function($call) {
            return [
                'id' => $call->id,
                'nave' => $call->vessel->name,
                'viaje' => $call->viaje_id,
                'muelle' => $call->berth->name ?? 'N/A',
                'eta' => $call->eta?->format('Y-m-d H:i'),
                'estado' => $call->estado_llamada
            ];
        })->toArray();
    
    $headers = [
        ['key' => 'id', 'label' => 'ID', 'sortable' => true],
        ['key' => 'nave', 'label' => 'Nave', 'sortable' => true],
        ['key' => 'viaje', 'label' => 'Viaje', 'sortable' => true],
        ['key' => 'muelle', 'label' => 'Muelle', 'sortable' => true],
        ['key' => 'eta', 'label' => 'ETA', 'sortable' => true],
        [
            'key' => 'estado', 
            'label' => 'Estado', 
            'sortable' => true,
            'format' => 'function(val) {
                const badges = {
                    "COMPLETADA": "<span class=\"badge-success\">COMPLETADA</span>",
                    "EN_CURSO": "<span class=\"badge-warning\">EN_CURSO</span>"
                };
                return badges[val] || val;
            }'
        ]
    ];
    
    return view('vessel-calls.index', compact('data', 'headers'));
}
```

## Configuración de Headers

### Propiedades Disponibles

```php
[
    'key' => 'nombre_campo',        // Requerido: clave del dato
    'label' => 'Etiqueta Visible',  // Requerido: texto del encabezado
    'sortable' => true,             // Opcional: permite ordenar (default: true)
    'visible' => true,              // Opcional: visible por defecto (default: true)
    'format' => 'function(val) {}', // Opcional: función de formato
    'class' => 'text-right'         // Opcional: clases CSS adicionales
]
```

### Funciones de Formato

Las funciones de formato permiten personalizar cómo se muestra cada celda:

```php
// Formato simple
'format' => 'function(val) { return val + " horas"; }'

// Formato condicional con HTML
'format' => 'function(val) {
    if (val > 100) {
        return "<span class=\"text-red-600 font-bold\">" + val + "</span>";
    }
    return val;
}'

// Formato de badges
'format' => 'function(val) {
    const badges = {
        "ACTIVO": "<span class=\"badge-success\">ACTIVO</span>",
        "INACTIVO": "<span class=\"badge-danger\">INACTIVO</span>"
    };
    return badges[val] || val;
}'

// Formato de fechas
'format' => 'function(val) {
    if (!val || val === "N/A") return "N/A";
    const date = new Date(val);
    return date.toLocaleDateString("es-PE");
}'

// Formato numérico
'format' => 'function(val) {
    return parseFloat(val).toFixed(2) + "%";
}'
```

## Propiedades del Componente

| Propiedad | Tipo | Default | Descripción |
|-----------|------|---------|-------------|
| `headers` | Array | `[]` | Configuración de columnas |
| `data` | Array | `[]` | Datos a mostrar |
| `searchable` | Boolean | `true` | Habilita búsqueda |
| `sortable` | Boolean | `true` | Habilita ordenamiento |
| `paginate` | Boolean | `true` | Habilita paginación |
| `perPage` | Integer | `10` | Filas por página |
| `columnToggle` | Boolean | `true` | Habilita toggle de columnas |
| `exportable` | Boolean | `false` | Habilita exportación CSV |

## Ejemplos Avanzados

### Tabla con Datos Anidados

```php
$data = [
    [
        'id' => 1,
        'empresa' => ['nombre' => 'ACME Corp', 'ruc' => '12345678901'],
        'contacto' => ['email' => 'info@acme.com', 'telefono' => '555-1234']
    ]
];

$headers = [
    ['key' => 'id', 'label' => 'ID'],
    ['key' => 'empresa.nombre', 'label' => 'Empresa'],
    ['key' => 'empresa.ruc', 'label' => 'RUC'],
    ['key' => 'contacto.email', 'label' => 'Email']
];
```

### Tabla con Acciones

```php
$data = $users->map(function($user) {
    return [
        'id' => $user->id,
        'nombre' => $user->name,
        'email' => $user->email,
        'acciones' => $user->id // Guardamos el ID para las acciones
    ];
})->toArray();

$headers = [
    ['key' => 'id', 'label' => 'ID'],
    ['key' => 'nombre', 'label' => 'Nombre'],
    ['key' => 'email', 'label' => 'Email'],
    [
        'key' => 'acciones',
        'label' => 'Acciones',
        'sortable' => false,
        'format' => 'function(id) {
            return `
                <div class="flex gap-2">
                    <a href="/users/${id}/edit" class="text-blue-600 hover:underline">Editar</a>
                    <button onclick="deleteUser(${id})" class="text-red-600 hover:underline">Eliminar</button>
                </div>
            `;
        }'
    ]
];
```

### Tabla con Indicadores Visuales

```php
$headers = [
    ['key' => 'id', 'label' => 'ID'],
    ['key' => 'nombre', 'label' => 'Nombre'],
    [
        'key' => 'utilizacion',
        'label' => 'Utilización',
        'format' => 'function(val) {
            const pct = parseFloat(val);
            let color = "bg-green-500";
            if (pct >= 85) color = "bg-red-600";
            else if (pct >= 50) color = "bg-yellow-500";
            
            return `
                <div class="flex items-center gap-2">
                    <div class="w-full bg-gray-200 rounded-full h-4">
                        <div class="${color} h-4 rounded-full" style="width: ${pct}%"></div>
                    </div>
                    <span class="text-sm font-medium">${pct.toFixed(1)}%</span>
                </div>
            `;
        }'
    ]
];
```

## Integración con Filtros Existentes

Las tablas interactivas funcionan perfectamente con los filtros del servidor:

```blade
<!-- Filtros del servidor -->
<x-filter-panel>
    <form method="GET" action="{{ route('reports.r3') }}">
        <!-- Campos de filtro -->
        <button type="submit" class="btn-primary">Aplicar Filtros</button>
    </form>
</x-filter-panel>

<!-- Tabla interactiva con datos filtrados -->
<x-interactive-table 
    :headers="$headers"
    :data="$filteredData"
    :searchable="true"
/>
```

## Personalización de Estilos

Las tablas usan las clases CSS existentes del proyecto:

- `.table-header` - Encabezados de tabla
- `.table-row` - Filas de tabla
- `.badge-success`, `.badge-warning`, `.badge-danger` - Badges de estado
- `.btn-primary`, `.btn-secondary` - Botones
- `.input-field` - Campos de entrada

## Performance

### Recomendaciones

1. **Paginación del servidor**: Para más de 1000 registros, usa paginación del servidor
2. **Lazy loading**: Carga datos bajo demanda para grandes volúmenes
3. **Índices de búsqueda**: Considera usar búsqueda del servidor para datasets grandes

### Ejemplo con Paginación del Servidor

```php
public function index(Request $request)
{
    $query = VesselCall::with(['vessel', 'berth']);
    
    // Aplicar filtros del servidor
    if ($request->filled('fecha_desde')) {
        $query->where('eta', '>=', $request->fecha_desde);
    }
    
    // Paginar en el servidor
    $vesselCalls = $query->paginate(100);
    
    // Preparar datos para la tabla interactiva
    $data = $vesselCalls->map(/* ... */)->toArray();
    
    return view('vessel-calls.index', compact('data', 'vesselCalls'));
}
```

## Troubleshooting

### La búsqueda no funciona
- Verifica que `searchable="true"` esté configurado
- Asegúrate de que los datos sean strings o convertibles a string

### El ordenamiento no funciona correctamente
- Para números, asegúrate de que sean numéricos, no strings
- Para fechas, usa formato ISO (YYYY-MM-DD HH:mm:ss)

### Las funciones de formato no se aplican
- Verifica la sintaxis de la función JavaScript
- Usa comillas simples dentro de la función
- Escapa caracteres especiales correctamente

### La tabla no se muestra
- Verifica que Alpine.js esté cargado
- Revisa la consola del navegador para errores
- Asegúrate de que `npm run build` se haya ejecutado

## Compilación de Assets

Después de modificar archivos JavaScript:

```bash
npm run build
```

Para desarrollo con hot-reload:

```bash
npm run dev
```

## Compatibilidad

- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Edge 90+
- ✅ Móviles (iOS Safari, Chrome Mobile)

## Próximas Mejoras

- [ ] Exportación a Excel (XLSX)
- [ ] Exportación a PDF
- [ ] Filtros por columna
- [ ] Selección múltiple de filas
- [ ] Drag & drop para reordenar columnas
- [ ] Guardado de preferencias de usuario
- [ ] Búsqueda avanzada con operadores
