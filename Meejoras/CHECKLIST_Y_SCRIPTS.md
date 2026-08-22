# CHECKLIST Y SCRIPTS DE CORRECCIÓN
## Farmacia Droguería - Plan de Acción Ejecutable

---

## FASE 1: CRÍTICA (1-2 DÍAS)

### ✅ Tarea 1.1: Crear clase Validador

**Ubicación:** `modelo/Validador.php`  
**Archivo:** SOLUCIONES_Y_CODIGO.md (Sección 2.1)  
**Status:** [ ] No iniciado [ ] En progreso [ ] Completado  
**Responsable:** ___________  
**Fecha:** ___________

**Checklist:**
- [ ] Crear archivo `modelo/Validador.php`
- [ ] Implementar método `validar_cantidad()`
- [ ] Implementar método `validar_precio()`
- [ ] Implementar método `validar_id()`
- [ ] Implementar método `validar_texto()`
- [ ] Implementar método `validar_email()`
- [ ] Implementar método `validar_fecha()`
- [ ] Implementar método `validar_tipo_pago()`
- [ ] Implementar método `validar_imagen()`
- [ ] Testing unitario básico

---

### ✅ Tarea 1.2: Crear clase SeguridadHelper

**Ubicación:** `modelo/SeguridadHelper.php`  
**Archivo:** SOLUCIONES_Y_CODIGO.md (Sección 4.2)  
**Status:** [ ] No iniciado [ ] En progreso [ ] Completado  
**Responsable:** ___________  
**Fecha:** ___________

**Checklist:**
- [ ] Crear archivo `modelo/SeguridadHelper.php`
- [ ] Implementar `validar_sesion()`
- [ ] Implementar `validar_tipo_usuario()`
- [ ] Implementar `establecer_headers_seguridad()`
- [ ] Implementar `generar_token_csrf()`
- [ ] Implementar `validar_token_csrf()`

---

### ✅ Tarea 1.3: Migrar credenciales a .env

**Archivo:** `modelo/Conexion.php` + `.env.local`  
**Status:** [ ] No iniciado [ ] En progreso [ ] Completado  
**Responsable:** ___________  
**Fecha:** ___________

**Checklist:**
- [ ] Crear archivo `.env.local` (NO en repositorio)
- [ ] Agregar `.env.local` a `.gitignore`
- [ ] Configurar DB_HOST, DB_USER, DB_PASS, DB_NAME en .env.local
- [ ] Modificar `modelo/Conexion.php` para leer .env.local
- [ ] Crear usuario MySQL específico (no root)
- [ ] Otorgar permisos específicos al usuario (no todos)
- [ ] Testear conexión con nuevas credenciales
- [ ] Remover datos de conexión del código

**Script SQL para crear usuario:**
```sql
-- Crear usuario con permisos específicos
CREATE USER 'farmacia_user'@'localhost' IDENTIFIED BY 'SuperSecurePassword123!';

-- Otorgar permisos específicos (no ALL)
GRANT SELECT, INSERT, UPDATE, DELETE ON farmaciasistema.* TO 'farmacia_user'@'localhost';
GRANT EXECUTE ON farmaciasistema.* TO 'farmacia_user'@'localhost';

-- Aplicar cambios
FLUSH PRIVILEGES;

-- Verificar
SHOW GRANTS FOR 'farmacia_user'@'localhost';
```

---

### ✅ Tarea 1.4: Corregir SQL Injection en CompraController.php

**Archivo:** `controlador/CompraController.php` (Líneas 30-117)  
**Referencia:** SOLUCIONES_Y_CODIGO.md (Sección 1.1 y 5.1)  
**Status:** [ ] No iniciado [ ] En progreso [ ] Completado  
**Responsable:** ___________  
**Fecha:** ___________

**Cambios requeridos:**
- [ ] Reemplazar todos los `$conexion->exec()` con prepared statements
- [ ] Validar cantidad > 0 y < 10000
- [ ] Manejar lote no encontrado
- [ ] Usar prepared statements para INSERT en detalle_venta
- [ ] Usar prepared statements para UPDATE en lote
- [ ] Agregar try-catch con rollback completo
- [ ] Registrar errores en error_log
- [ ] Testing con datos edge cases

**Test Cases:**
```php
// Caso 1: Cantidad = 0
$cantidad = 0;  // Debe lanzar Exception

// Caso 2: Cantidad > 10000
$cantidad = 999999;  // Debe lanzar Exception

// Caso 3: Producto sin lotes
$prod->id = 999;  // Debe lanzar Exception con mensaje claro

// Caso 4: Cantidad > stock disponible
$cantidad = 100;
$stock_disponible = 50;  // Debe lanzar Exception

// Caso 5: Crédito exitoso
$tipo_pago = 'Credito';
$pago = 100;
// Debe crear Venta + Venta de depósito
```

---

### ✅ Tarea 1.5: Agregar validación de sesión a TODOS los controladores

**Archivos afectados:**
- controlador/ProductoController.php
- controlador/VentaProductoController.php
- controlador/CompraController.php
- controlador/LoteController.php
- controlador/DetalleVentaController.php
- controlador/VentaController.php
- (Todos los demás controladores)

**Status:** [ ] No iniciado [ ] En progreso [ ] Completado  
**Responsable:** ___________  
**Fecha:** ___________

**Template para cada controlador:**
```php
<?php
include_once '../modelo/SeguridadHelper.php';
include_once '../modelo/Validador.php';

// Establecer headers de seguridad
SeguridadHelper::establecer_headers_seguridad();

// Validar sesión
$id_usuario = SeguridadHelper::validar_sesion();

try {
    // Validar método POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    // ... resto del código
    
} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(400);
    echo json_encode(['error' => true, 'mensaje' => $e->getMessage()]);
}
?>
```

**Checklist:**
- [ ] ProductoController.php
- [ ] VentaProductoController.php
- [ ] CompraController.php
- [ ] LoteController.php
- [ ] DetalleVentaController.php
- [ ] VentaController.php
- [ ] ClienteController.php
- [ ] UsuarioController.php
- [ ] CajaController.php
- [ ] ContabilidadController.php
- [ ] (Todos los demás)

---

### ✅ Tarea 1.6: Corregir validación de archivo en ProductoController

**Archivo:** `controlador/ProductoController.php` (Línea 99-109)  
**Referencia:** SOLUCIONES_Y_CODIGO.md (Sección 4.1)  
**Status:** [ ] No iniciado [ ] En progreso [ ] Completado  
**Responsable:** ___________  
**Fecha:** ___________

**Cambios:**
- [ ] Usar Validador::validar_imagen()
- [ ] Validar extensión real del archivo
- [ ] Validar MIME type real con finfo
- [ ] Validar tamaño máximo (5MB)
- [ ] Generar nombre único seguro
- [ ] Remover archivo viejo después de validar nuevo

---

## FASE 2: ALTA (2-3 DÍAS)

### ✅ Tarea 2.1: Corregir cálculo de IGV

**Archivo:** `js/Carrito.js` (Línea 282-296)  
**Referencia:** SOLUCIONES_Y_CODIGO.md (Sección 3.1)  
**Status:** [ ] No iniciado [ ] En progreso [ ] Completado  
**Responsable:** ___________  
**Fecha:** ___________

**Cambios:**
- [ ] Reemplazar fórmula de IGV con cálculo correcto
- [ ] Validar descuento >= 0
- [ ] Validar descuento <= 50% del total
- [ ] Validar pago >= 0
- [ ] Calcular vuelto correctamente
- [ ] Testing con múltiples casos

**Test Cases:**
```javascript
// Caso 1: Producto 100 (sin IGV)
total_bruto = 100;
esperado_base = 100 / 1.18 = 84.75
esperado_igv = 100 - 84.75 = 15.25

// Caso 2: Con descuento
total_bruto = 100;
descuento = 10;
total_con_descuento = 90;
esperado_base = 90 / 1.18 = 76.27
esperado_igv = 90 - 76.27 = 13.73

// Caso 3: Con pago
pago = 105;
vuelto = 105 - 100 = 5 ✓
```

---

### ✅ Tarea 2.2: Corregir obtener_stock en ProductoController

**Archivo:** `controlador/ProductoController.php` (Línea 44-65, 160-180)  
**Status:** [ ] No iniciado [ ] En progreso [ ] Completado  
**Responsable:** ___________  
**Fecha:** ___________

**Problema:**
- `$producto->obtener_stock()` sobrescribe `$this->objetos`
- El foreach para productos itera sobre resultado de stock

**Solución:**
```php
// ANTES - INCORRECTO
foreach ($todos_productos as $objeto) {
    $producto->obtener_stock($objeto->id_producto);  // Sobrescribe $this->objetos
    foreach ($producto->objetos as $obj) {  // Ahora itera sobre stock, no producto!
        $total = $obj->total;
    }
}

// DESPUÉS - CORRECTO
foreach ($todos_productos as $objeto) {
    $query = "SELECT SUM(cantidad_lote) as total FROM lote WHERE id_producto=:id AND estado='A'";
    $query_obj = $conexion->prepare($query);
    $query_obj->execute([':id' => $objeto->id_producto]);
    $stock_result = $query_obj->fetch();
    $total = $stock_result->total ?? 0;
}
```

**Checklist:**
- [ ] Crear método auxiliar en Producto.php para obtener stock sin sobrescribir
- [ ] Modificar ProductoController para usar nuevo método
- [ ] Testing con múltiples productos

---

### ✅ Tarea 2.3: Crear índices en base de datos

**Archivo:** BD - Script SQL  
**Status:** [ ] No iniciado [ ] En progreso [ ] Completado  
**Responsable:** ___________  
**Fecha:** ___________

**Script SQL:**
```sql
-- Índices para búsquedas frecuentes
CREATE INDEX idx_producto_nombre ON producto(nombre);
CREATE INDEX idx_producto_estado ON producto(estado);
CREATE INDEX idx_lote_id_producto ON lote(id_producto, estado);
CREATE INDEX idx_lote_vencimiento ON lote(vencimiento);
CREATE INDEX idx_venta_tipo_pago ON venta(tipo_pago);
CREATE INDEX idx_venta_fecha ON venta(fecha);
CREATE INDEX idx_venta_vendedor ON venta(vendedor);
CREATE INDEX idx_venta_cliente ON venta(id_cliente);
CREATE INDEX idx_detalle_venta_venta ON detalle_venta(id_det_venta);
CREATE INDEX idx_detalle_venta_lote ON detalle_venta(id__det_lote);
CREATE INDEX idx_venta_producto_venta ON venta_producto(venta_id_venta);

-- Verificar índices creados
SHOW INDEX FROM producto;
SHOW INDEX FROM lote;
SHOW INDEX FROM venta;
SHOW INDEX FROM detalle_venta;
SHOW INDEX FROM venta_producto;
```

**Checklist:**
- [ ] Conectar a BD
- [ ] Ejecutar script de índices
- [ ] Verificar que se crearon correctamente
- [ ] Monitorear performance antes/después (opcional)

---

### ✅ Tarea 2.4: Aplicar validaciones en ProductoController

**Archivo:** `controlador/ProductoController.php`  
**Referencia:** SOLUCIONES_Y_CODIGO.md (Sección 2.2)  
**Status:** [ ] No iniciado [ ] En progreso [ ] Completado  
**Responsable:** ___________  
**Fecha:** ___________

**Cambios:**
- [ ] Validar nombre (2-255 caracteres)
- [ ] Validar concentración (0-100 caracteres)
- [ ] Validar precio (positivo, máximo 999999.99)
- [ ] Validar laboratorio (ID válido)
- [ ] Validar tipo (ID válido)
- [ ] Validar presentación (ID válido)
- [ ] Retornar JSON con error claro si valida

---

### ✅ Tarea 2.5: Mejorar DetalleVentaController

**Archivo:** `controlador/DetalleVentaController.php`  
**Referencia:** SOLUCIONES_Y_CODIGO.md (Sección 6.1)  
**Status:** [ ] No iniciado [ ] En progreso [ ] Completado  
**Responsable:** ___________  
**Fecha:** ___________

**Cambios:**
- [ ] Mejorar lógica de permisos
- [ ] Agregar transacción con rollback
- [ ] Marcar venta como "Anulada" (no DELETE)
- [ ] Registrar quién anuló y cuándo
- [ ] Validar que venta exista

---

## FASE 3: MEDIA (3-5 DÍAS)

### ✅ Tarea 3.1: Crear sistema de auditoría

**Archivos nuevos:**
- `modelo/Auditoria.php`
- Script SQL para tabla audit_log

**Status:** [ ] No iniciado [ ] En progreso [ ] Completado  
**Responsable:** ___________  
**Fecha:** ___________

**Tabla SQL:**
```sql
CREATE TABLE audit_log (
    id_auditoria INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    accion VARCHAR(50),
    tabla_afectada VARCHAR(100),
    registro_id INT,
    valores_anteriores JSON,
    valores_nuevos JSON,
    ip_address VARCHAR(45),
    fecha_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario (usuario_id),
    INDEX idx_tabla (tabla_afectada),
    INDEX idx_fecha (fecha_hora)
);
```

**Clase Auditoria.php:**
```php
<?php
class Auditoria {
    public function registrar($usuario_id, $accion, $tabla, $registro_id, $valores_antes, $valores_despues) {
        // Registrar acción en audit_log
    }
}
?>
```

**Checklist:**
- [ ] Crear tabla audit_log
- [ ] Crear clase Auditoria
- [ ] Registrar borrados de venta
- [ ] Registrar modificaciones de stock
- [ ] Registrar login/logout
- [ ] Registrar cambios de precios

---

### ✅ Tarea 3.2: Agregar campos de auditoría a tablas

**Tabla venta:**
```sql
ALTER TABLE venta ADD COLUMN estado VARCHAR(20) DEFAULT 'Activa';
ALTER TABLE venta ADD COLUMN usuario_anulo INT;
ALTER TABLE venta ADD COLUMN fecha_anulacion DATETIME;
ALTER TABLE venta ADD COLUMN motivo_anulacion VARCHAR(255);

CREATE INDEX idx_venta_estado ON venta(estado);
```

**Checklist:**
- [ ] Agregar campos a venta
- [ ] Agregar campos a detalle_venta
- [ ] Agregar campos a lote

---

### ✅ Tarea 3.3: Validar cantidad en carrito (Frontend)

**Archivo:** `js/Carrito.js`  
**Referencia:** SOLUCIONES_Y_CODIGO.md (Sección 7.1)  
**Status:** [ ] No iniciado [ ] En progreso [ ] Completado  
**Responsable:** ___________  
**Fecha:** ___________

**Cambios:**
- [ ] Validar que cantidad > 0
- [ ] Validar que cantidad <= 10000
- [ ] No permitir decimales
- [ ] Mostrar error si inválido

---

### ✅ Tarea 3.4: Agregar expiración al carrito (localStorage)

**Archivo:** `js/Carrito.js`  
**Status:** [ ] No iniciado [ ] En progreso [ ] Completado  
**Responsable:** ___________  
**Fecha:** ___________

**Cambios:**
- [ ] Guardar timestamp con carrito
- [ ] Validar que no sea más de 4 horas antiguo
- [ ] Limpiar carrito si expiró

---

## FASE 4: MEJORAS (1-2 SEMANAS)

### ✅ Tarea 4.1: Implementar rate limiting

**Archivo:** `modelo/RateLimiter.php`  
**Status:** [ ] No iniciado [ ] En progreso [ ] Completado  

### ✅ Tarea 4.2: Agregar tokens CSRF

**Archivo:** Modificar SeguridadHelper.php  
**Status:** [ ] No iniciado [ ] En progreso [ ] Completado  

### ✅ Tarea 4.3: Refactorizar arquitectura

**Status:** [ ] No iniciado [ ] En progreso [ ] Completado  

### ✅ Tarea 4.4: Agregar unit tests

**Status:** [ ] No iniciado [ ] En progreso [ ] Completado  

---

## SCRIPTS DE TESTING

### Test 1: Verificar SQL Injection está corregido

```bash
# Intentar SQL Injection en búsqueda de producto
curl -X POST http://localhost/controlador/ProductoController.php \
  -d "funcion=buscar&consulta=producto' OR '1'='1"

# Debe retornar error validado, no ejecutar inyección
```

### Test 2: Verificar validación de sesión

```bash
# Intentar sin sesión
curl -X POST http://localhost/controlador/ProductoController.php \
  -d "funcion=crear&nombre=Test"

# Debe retornar 401 Unauthorized
```

### Test 3: Verificar validación de entrada

```bash
# Intentar precio negativo
curl -X POST http://localhost/controlador/ProductoController.php \
  -d "funcion=crear&precio=-100"

# Debe validar y rechazar
```

### Test 4: Verificar cálculo de IGV

```javascript
// En consola del navegador
total_bruto = 100;
base = total_bruto / 1.18;
igv = total_bruto - base;
console.log('Base:', base.toFixed(2));  // 84.75
console.log('IGV:', igv.toFixed(2));    // 15.25
console.log('Total:', (base + igv).toFixed(2));  // 100.00
```

---

## VERIFICACIÓN FINAL

### ✅ Checklist de Seguridad

- [ ] Todas las credenciales en .env.local
- [ ] Sin credenciales en código fuente
- [ ] Todas las entrada validadas
- [ ] Todos los SQL son prepared statements
- [ ] Todos los controladores validan sesión
- [ ] Headers de seguridad en respuestas JSON
- [ ] Errores no exponen información sensible
- [ ] Error logging implementado
- [ ] HTTPS habilitado (si en producción)

### ✅ Checklist de Funcionalidad

- [ ] Stock se decrementa correctamente
- [ ] Créditos se crean correctamente
- [ ] IGV se calcula correctamente
- [ ] Depósitos se registran correctamente
- [ ] Ventas se pueden eliminar
- [ ] Permisos funcionan correctamente
- [ ] Reportes son precisos
- [ ] Alertas de vencimiento funcionan

### ✅ Checklist de Performance

- [ ] Índices creados en BD
- [ ] Queries usan índices (EXPLAIN)
- [ ] Sin N+1 queries
- [ ] Transacciones son rápidas
- [ ] Reportes no cuelgan

---

## MONITOREO POST-IMPLEMENTACIÓN

### KPIs a Monitorear

1. **Seguridad:**
   - Errores SQL Injection: 0
   - Accesos sin autenticación: 0
   - Errores de validación: % de intentos bloqueados

2. **Funcionalidad:**
   - Errores de transacción: < 0.1%
   - Inconsistencias de stock: 0
   - Discrepancias en IGV: 0

3. **Performance:**
   - Tiempo promedio de venta: < 2 segundos
   - Tiempo promedio de reporte: < 5 segundos
   - Uptime: > 99.9%

---

## NOTAS IMPORTANTES

⚠️ **CRÍTICO:**
- Implementar Fase 1 completa antes de usar en producción
- Backup completo de BD antes de migración
- Testing exhaustivo en ambiente de desarrollo

📝 **RECOMENDACIONES:**
- Crear rama de git para cambios
- Revisar cambios en pair-programming
- Documentar cambios en CHANGELOG
- Capacitar a equipo en nuevo código

🚀 **PRÓXIMOS PASOS:**
- Implementar Phase 1-2 esta semana
- Validar con testing en desarrollo
- Desplegar a producción con cautela
- Monitorear 24/7 primeros 3 días

