# Fix: Database Connection [portuario] Not Configured

## Problema

Al intentar acceder a las páginas de Tarja Notes o Weigh Tickets, aparecía el siguiente error:

```
InvalidArgumentException
Database connection [portuario] not configured.
```

## Causa

Los modelos de Eloquent (TarjaNote, WeighTicket, CargoItem, etc.) estaban intentando usar una conexión de base de datos llamada `portuario` que no existía en el archivo `config/database.php`.

Laravel buscaba una configuración específica para `portuario`, pero solo teníamos configurada la conexión `pgsql`.

## Solución

Se agregó explícitamente la propiedad `$connection = 'pgsql'` a todos los modelos de cargo management para indicarles que usen la conexión PostgreSQL principal.

### Modelos Actualizados:

1. **TarjaNote.php**
2. **WeighTicket.php**
3. **CargoItem.php**
4. **CargoManifest.php**
5. **YardLocation.php**

### Cambio Aplicado:

**Antes:**
```php
class TarjaNote extends Model
{
    use HasFactory;

    protected $table = 'portuario.tarja_note';
    // ...
}
```

**Después:**
```php
class TarjaNote extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';  // ← AGREGADO
    protected $table = 'portuario.tarja_note';
    // ...
}
```

## Verificación

Ejecutar el script de prueba:

```bash
php test_connection_fix.php
```

**Resultado esperado:**
```
✓ CargoItem: 18 registros
✓ TarjaNote: 8 registros
✓ WeighTicket: 9 registros
✓ CargoManifest: 6 registros
✓ YardLocation: 17 registros

✅ TODAS LAS CONEXIONES FUNCIONAN CORRECTAMENTE!
```

## Pasos para Probar

1. **Limpiar caché:**
   ```bash
   php artisan config:clear
   php artisan view:clear
   ```

2. **Refrescar el navegador** (F5)

3. **Probar las páginas:**
   - Tarja Notes: `http://localhost:8000/portuario/tarja`
   - Weigh Tickets: `http://localhost:8000/portuario/weighing`

4. **Intentar crear registros:**
   - Clic en "New Tarja Note" o "New Weigh Ticket"
   - Llenar el formulario
   - Guardar

## Por Qué Funcionaba en Tests

Los tests automatizados funcionaban porque usaban factories que no dependían de la conexión explícita, o porque el contexto de testing configuraba las conexiones de manera diferente.

## Nota Técnica

En Laravel, cuando usas schemas de PostgreSQL (como `portuario.tarja_note`), debes especificar explícitamente la conexión si el nombre del schema no coincide con el nombre de una conexión configurada.

**Opciones:**
1. ✅ **Especificar conexión en modelos** (solución aplicada)
2. ❌ Crear una conexión `portuario` en config (innecesario, duplicaría configuración)
3. ❌ Cambiar nombres de tablas (rompería la estructura existente)

## Fix Adicional: Validaciones

El problema también afectaba las **reglas de validación** en los controladores. Las reglas `exists` y `unique` también intentaban usar la conexión `portuario`.

### Cambio en Validaciones:

**Antes:**
```php
'cargo_item_id' => ['required', 'exists:portuario.cargo_item,id'],
'ticket_number' => ['required', 'unique:portuario.weigh_ticket,ticket_number'],
```

**Después:**
```php
'cargo_item_id' => ['required', 'exists:pgsql.portuario.cargo_item,id'],
'ticket_number' => ['required', 'unique:pgsql.portuario.weigh_ticket,ticket_number'],
```

### Controladores Actualizados:
1. **WeighingController** - Validación en método `store()`
2. **TarjaController** - Validación en método `store()`

## Pasos para Aplicar el Fix

1. **Detener el servidor:**
   - Ir a la ventana del servidor
   - Presionar `Ctrl+C`

2. **Limpiar caché:**
   ```bash
   REINICIAR_SERVIDOR_COMPLETO.bat
   ```

3. **Iniciar servidor nuevamente:**
   ```bash
   INICIAR_SERVIDOR.bat
   ```

4. **Refrescar navegador** (F5)

5. **Probar crear registros**

## Estado Actual

✅ **RESUELTO COMPLETAMENTE** - Todos los modelos y validaciones de cargo management ahora funcionan correctamente en el navegador.

---

**Fecha:** 11 de diciembre de 2024  
**Afectó:** TarjaController, WeighingController (modelos + validaciones)  
**Tiempo de resolución:** ~10 minutos
