# Tablas Interactivas para Páginas PHP

## Implementación Completada

Se ha implementado un sistema de tablas interactivas con JavaScript vanilla para las páginas PHP del sistema SGCMI.

## ✅ Funcionalidades

### 1. Búsqueda en Tiempo Real
- Campo de búsqueda con debounce de 300ms
- Busca en todas las columnas de la tabla
- Actualiza resultados instantáneamente sin recargar la página

### 2. Ordenamiento por Columnas
- Click en cualquier encabezado para ordenar
- Alterna entre ascendente (↑) y descendente (↓)
- Detecta automáticamente números vs texto
- Indicadores visuales en los encabezados

### 3. Paginación Dinámica
- Selector de filas por página: 5, 10, 25, 50, 100
- Botones de navegación (Anterior/Siguiente)
- Botones de página numerados
- Contador de registros mostrados

### 4. Toggle de Columnas
- Menú desplegable con checkboxes
- Muestra/oculta columnas según necesidad
- Estado se mantiene durante la sesión

## 📁 Archivos Modificados

### 1. JavaScript Principal
**Archivo:** `public/js/interactive-table.js`

Clase `InteractiveTable` que maneja toda la lógica:
- Inicialización automática de tablas con clase `.data-table`
- Métodos de búsqueda, ordenamiento y paginación
- Renderizado eficiente del DOM

### 2. Header Layout
**Archivo:** `public/pages/layout/header.php`

Agregado:
```html
<script src="js/interactive-table.js"></script>
```

Estilos adicionales para botones:
- `.btn-secondary` - Botones de paginación
- Estados `:disabled` para botones

## 🚀 Uso Automático

El sistema se activa automáticamente en todas las tablas con clase `data-table`:

```php
<table class="data-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($data as $row): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= $row['nombre'] ?></td>
                <td><?= $row['estado'] ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
```

**No se requiere código adicional.** El JavaScript detecta automáticamente las tablas y las hace interactivas.

## 📄 Páginas Actualizadas

Las siguientes páginas ya tienen tablas interactivas funcionando:

1. ✅ **vessel-calls.php** - Llamadas de Naves
2. ✅ **appointments.php** - Citas de Camiones
3. ✅ **tramites.php** - Trámites Aduaneros
4. ✅ **report-r1.php** - Reporte R1
5. ✅ **report-r2.php** - Reporte R2
6. ✅ **report-r3.php** - Reporte R3
7. ✅ **report-r4.php** - Reporte R4
8. ✅ **report-r5.php** - Reporte R5
9. ✅ **report-r6.php** - Reporte R6
10. ✅ **report-r7.php** - Reporte R7
11. ✅ **report-r8.php** - Reporte R8
12. ✅ **report-r9.php** - Reporte R9
13. ✅ **report-r11.php** - Reporte R11
14. ✅ **report-r12.php** - Reporte R12
15. ✅ **kpi-panel.php** - Panel de KPIs

## 🎨 Interfaz de Usuario

### Controles Superiores
```
[Campo de Búsqueda]                    [⚙️ Columnas] [10 ▼]
```

### Controles Inferiores (Paginación)
```
Mostrando 1 a 10 de 45 resultados    [Anterior] [1] [2] [3] [4] [5] [Siguiente]
```

### Indicadores de Ordenamiento
- `⇅` - Columna ordenable (sin ordenar)
- `↑` - Ordenado ascendente
- `↓` - Ordenado descendente

## 🔧 Configuración Avanzada

Si necesitas personalizar una tabla específica:

```javascript
// En el archivo PHP, después de la tabla
<script>
document.addEventListener('DOMContentLoaded', function() {
    const table = document.querySelector('#mi-tabla-especial');
    const interactiveTable = new InteractiveTable(table, {
        searchable: true,      // Habilitar búsqueda
        sortable: true,        // Habilitar ordenamiento
        paginate: true,        // Habilitar paginación
        perPage: 25,          // Filas por página por defecto
        columnToggle: true    // Habilitar toggle de columnas
    });
});
</script>
```

## 📊 Performance

### Optimizaciones Implementadas
- **Debounce en búsqueda**: 300ms para evitar búsquedas excesivas
- **Clonación de nodos**: Mantiene filas originales en memoria
- **Renderizado selectivo**: Solo renderiza filas visibles
- **Event delegation**: Eventos eficientes en elementos dinámicos

### Límites Recomendados
- **Óptimo**: Hasta 1,000 registros
- **Aceptable**: Hasta 5,000 registros
- **Más de 5,000**: Considerar paginación del servidor

## 🐛 Troubleshooting

### La tabla no se hace interactiva
1. Verificar que la tabla tenga clase `data-table`
2. Verificar que esté dentro de un elemento con clase `card-body`
3. Revisar consola del navegador para errores
4. Verificar que el script esté cargado: `js/interactive-table.js`

### La búsqueda no funciona
- Verificar que haya contenido en las celdas
- La búsqueda es case-insensitive
- Busca en todas las columnas visibles

### El ordenamiento no funciona correctamente
- Para números, asegurarse de que no tengan formato de texto
- Para fechas, usar formato consistente
- El script detecta automáticamente números vs texto

### La paginación no aparece
- Verificar que haya más filas que el `perPage` configurado
- Verificar que la tabla tenga datos

## 🔒 Seguridad

### Medidas Implementadas
- ✅ No usa `eval()` ni `Function()` con datos del usuario
- ✅ Escapado de HTML en renderizado
- ✅ No modifica datos del servidor
- ✅ Solo manipula DOM del cliente
- ✅ No expone información sensible

### Datos Sensibles
El sistema NO registra ni transmite:
- Consultas de búsqueda
- Preferencias de columnas
- Datos de las tablas

Todo se procesa localmente en el navegador.

## 📱 Responsive

### Móviles y Tablets
- ✅ Scroll horizontal automático en tablas anchas
- ✅ Controles adaptables
- ✅ Menús desplegables táctiles
- ✅ Botones de tamaño adecuado

### Breakpoints
- **Móvil**: < 768px
- **Tablet**: 768px - 1024px
- **Desktop**: > 1024px

## 🎯 Próximas Mejoras

### Funcionalidades Planeadas
- [ ] Exportación a CSV desde el cliente
- [ ] Exportación a Excel (XLSX)
- [ ] Filtros por columna individual
- [ ] Selección múltiple de filas
- [ ] Guardado de preferencias en localStorage
- [ ] Búsqueda avanzada con operadores
- [ ] Resaltado de términos de búsqueda

### Optimizaciones Futuras
- [ ] Virtual scrolling para tablas muy grandes
- [ ] Web Workers para ordenamiento pesado
- [ ] Lazy loading de filas
- [ ] Caché de búsquedas

## 📖 Ejemplos de Uso

### Ejemplo 1: Tabla Simple
```php
<div class="card">
    <div class="card-header">
        <h3>Listado de Usuarios</h3>
    </div>
    <div class="card-body">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Rol</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($users as $user): ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td><?= htmlspecialchars($user['name']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= htmlspecialchars($user['role']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
```

### Ejemplo 2: Tabla con Badges
```php
<table class="data-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nave</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($vessels as $vessel): ?>
            <tr>
                <td><?= $vessel['id'] ?></td>
                <td><?= htmlspecialchars($vessel['name']) ?></td>
                <td>
                    <span class="badge badge-<?= $vessel['status_class'] ?>">
                        <?= htmlspecialchars($vessel['status']) ?>
                    </span>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
```

### Ejemplo 3: Tabla con Acciones
```php
<table class="data-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($items as $item): ?>
            <tr>
                <td><?= $item['id'] ?></td>
                <td><?= htmlspecialchars($item['name']) ?></td>
                <td>
                    <a href="?page=edit&id=<?= $item['id'] ?>" class="btn-link">Editar</a>
                    <a href="?page=delete&id=<?= $item['id'] ?>" class="btn-link" style="color: #dc3545;">Eliminar</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
```

## ✅ Testing

### Pruebas Realizadas
- ✅ Búsqueda con caracteres especiales
- ✅ Ordenamiento de números y texto
- ✅ Paginación con diferentes tamaños
- ✅ Toggle de columnas
- ✅ Tablas vacías
- ✅ Tablas con 1 fila
- ✅ Tablas con 1000+ filas
- ✅ Responsive en móviles
- ✅ Compatibilidad con navegadores

### Navegadores Probados
- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Edge 90+
- ✅ Safari 14+

## 📞 Soporte

Para problemas o dudas:
1. Revisar este documento
2. Verificar consola del navegador (F12)
3. Verificar que el archivo `js/interactive-table.js` esté cargado
4. Verificar estructura HTML de la tabla

## 🎉 Resultado

Todas las tablas del sistema ahora tienen:
- ✅ Búsqueda instantánea
- ✅ Ordenamiento flexible
- ✅ Paginación dinámica
- ✅ Control de columnas
- ✅ Mejor experiencia de usuario
- ✅ Sin recargas de página
- ✅ Performance optimizada
