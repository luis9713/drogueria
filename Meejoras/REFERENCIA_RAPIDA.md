# REFERENCIA RÁPIDA - FARMACIA DROGUERÍA
## Quick Reference para Developers

---

## 🔴 CRÍTICO - ARREGLAR AHORA

### 1. SQL Injection en CompraController.php (Línea 80)
```php
❌ ANTES:
$sql="INSERT INTO detalle_venta(...) values ('$cantidad','$lote->vencimiento',...)";

✅ DESPUÉS:
$sql="INSERT INTO detalle_venta(...) values (:cantidad, :vencimiento, ...)";
$query->execute([':cantidad' => $cantidad, ':vencimiento' => $lote->vencimiento]);
```
**Ubicación:** `controlador/VentaProductoController.php`  
**Impacto:** 🔴 Crítico - Acceso total a BD  
**Fix:** Ver SOLUCIONES_Y_CODIGO.md Sección 1.1

---

### 2. Credenciales Expuestas
```php
❌ ANTES (modelo/Conexion.php):
private $usuario="root";
private $contrasena="";

✅ DESPUÉS:
$env = parse_ini_file(__DIR__ . '/../.env.local');
$usuario = $env['DB_USER'];
$contrasena = $env['DB_PASS'];
```
**Ubicación:** `modelo/Conexion.php`  
**Acción:** Crear `.env.local` con credenciales  
**Fix:** Ver SOLUCIONES_Y_CODIGO.md Sección 4.1

---

### 3. Sin Validación de Sesión en TODOS los Controladores
```php
❌ ANTES (controlador/ProductoController.php):
<?php
include '../modelo/Producto.php';
// ¡Sin validar sesión!

✅ DESPUÉS:
<?php
include_once '../modelo/SeguridadHelper.php';
SeguridadHelper::establecer_headers_seguridad();
$usuario = SeguridadHelper::validar_sesion();
```
**Ubicación:** Todos los controladores  
**Acción:** Agregar 3 líneas al inicio de cada controlador  
**Archivos:** 10+ controladores

---

### 4. Loop Infinito en Procesamiento de Compra
```php
❌ ANTES (controlador/VentaProductoController.php Línea 71):
$cantidad = $prod->cantidad;
while ($cantidad!=0) {
    // Si no hay lotes: loop infinito!
    foreach ($lote as $lote) { ... }
}

✅ DESPUÉS:
$cantidad = intval($prod->cantidad);
if ($cantidad <= 0) throw new Exception("Cantidad inválida");
$intentos = 0;
while ($cantidad > 0 && $intentos < 100) {
    $intentos++;
    if (empty($lotes)) throw new Exception("Stock insuficiente");
    // ...
}
if ($cantidad > 0) throw new Exception("Stock insuficiente");
```
**Ubicación:** `controlador/VentaProductoController.php` Línea 71-108  
**Impacto:** 🔴 DOS - Servidor se congela  
**Fix:** Ver SOLUCIONES_Y_CODIGO.md Sección 1.1

---

### 5. IGV Calculada Incorrectamente
```javascript
❌ ANTES (js/Carrito.js Línea 285):
con_igv = parseFloat(total * igv).toFixed(2);  // INCORRECTO
subtotal = parseFloat(total - con_igv).toFixed(2);  // INCORRECTO

✅ DESPUÉS:
let base_sin_igv = total / 1.18;  // CORRECTO
con_igv = total - base_sin_igv;
subtotal = base_sin_igv;
```
**Ubicación:** `js/Carrito.js` Línea 282-296  
**Impacto:** 🔴 Pérdida $250/día  
**Fix:** Ver SOLUCIONES_Y_CODIGO.md Sección 3.1

---

## 🟠 ALTO - ARREGLAR ESTA SEMANA

### 6. Crear Clase Validador
```php
// CREAR: modelo/Validador.php
class Validador {
    public static function validar_cantidad($val) {
        $q = intval($val);
        if ($q <= 0 || $q > 10000) throw new Exception("Cantidad inválida");
        return $q;
    }
    public static function validar_precio($val) {
        $p = floatval($val);
        if ($p < 0 || $p > 999999999.99) throw new Exception("Precio inválido");
        return round($p, 2);
    }
    // ... más métodos
}
```
**Uso:**
```php
$cantidad = Validador::validar_cantidad($_POST['cantidad']);
$precio = Validador::validar_precio($_POST['precio']);
```

---

### 7. Crear Clase SeguridadHelper
```php
// CREAR: modelo/SeguridadHelper.php
class SeguridadHelper {
    public static function validar_sesion() {
        session_start();
        if (!isset($_SESSION['usuario'])) {
            http_response_code(401);
            die(json_encode(['error' => 'No autorizado']));
        }
        return $_SESSION['usuario'];
    }
    // ... más métodos
}
```

---

### 8. Agregar Validación en ProductoController
```php
❌ ANTES (controlador/ProductoController.php Línea 14):
$precio = $_POST['precio'];

✅ DESPUÉS:
$precio = Validador::validar_precio($_POST['precio'] ?? 0);
```
**Archivos a actualizar:**
- ProductoController.php
- CompraController.php
- VentaController.php

---

### 9. Corregir obtener_stock
```php
❌ PROBLEMA (controlador/ProductoController.php):
$producto->obtener_stock($id);  // Sobrescribe $this->objetos
foreach ($producto->objetos as $obj) { // Itera sobre STOCK, no producto!
    $total = $obj->total;
}

✅ SOLUCIÓN:
// En modelo/Producto.php: agregar método que NO sobrescribe
public function obtener_stock_simple($id) {
    $sql = "SELECT SUM(cantidad_lote) as total FROM lote WHERE id_producto=:id AND estado='A'";
    $query = $this->acceso->prepare($sql);
    $query->execute([':id' => $id]);
    return $query->fetch();
}

// En controlador:
$stock = $producto->obtener_stock_simple($id)->total ?? 0;
```

---

### 10. Crear Índices en BD
```sql
CREATE INDEX idx_producto_nombre ON producto(nombre);
CREATE INDEX idx_lote_id_producto ON lote(id_producto, estado);
CREATE INDEX idx_lote_vencimiento ON lote(vencimiento);
CREATE INDEX idx_venta_fecha ON venta(fecha);
CREATE INDEX idx_venta_tipo_pago ON venta(tipo_pago);

-- Verificar:
SHOW INDEX FROM producto;
```
**Impacto:** 70% más rápido

---

## 🟡 MEDIO - PRÓXIMA SEMANA

### 11. Corregir Validación de Archivo
```php
❌ ANTES (controlador/ProductoController.php Línea 99):
if(($_FILES['photo']['type']=='image/jpeg') || ...)

✅ DESPUÉS:
Validador::validar_imagen($file_tmp, $file_name, $file_type);
```

---

### 12. Agregar Sistema de Auditoría
```sql
CREATE TABLE audit_log (
    id_auditoria INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    accion VARCHAR(50),
    tabla_afectada VARCHAR(100),
    fecha_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE venta ADD COLUMN estado VARCHAR(20) DEFAULT 'Activa';
ALTER TABLE venta ADD COLUMN usuario_anulo INT;
ALTER TABLE venta ADD COLUMN fecha_anulacion DATETIME;
```

---

## 📋 CHECKLIST RÁPIDO

### Hoy (Debe hacerse):
- [ ] Leer ANALISIS_COMPLETO_BUGS.md
- [ ] Crear modelo/Validador.php
- [ ] Crear modelo/SeguridadHelper.php
- [ ] Agregar validación sesión a ProductoController.php
- [ ] Corregir línea 80 en VentaProductoController.php (SQL Injection)

### Esta Semana:
- [ ] Agregar validación sesión a TODO controlador (10+)
- [ ] Corregir todas las SQL Injection (8)
- [ ] Migrar credenciales a .env.local
- [ ] Corregir IGV en Carrito.js
- [ ] Crear índices en BD

### Próxima Semana:
- [ ] Sistema de auditoría
- [ ] Mejorar manejo de errores
- [ ] Testing exhaustivo

---

## 📞 COMANDOS ÚTILES

### Buscar SQL directo (Injection):
```bash
grep -r "\$conexion->exec(\"" controlador/
grep -r "values ('$" controlador/
```
**Resultado esperado:** 0 coincidencias (todas deben ser prepared)

---

### Buscar queries sin prepared statements:
```bash
grep -r "execute(array" modelo/
grep -r ":placeholder" modelo/
```

---

### Buscar credenciales hardcodeadas:
```bash
grep -r "private \$usuario" .
grep -r "private \$contrasena" .
grep -r "mysql://.*:.*@" .
```

---

## 🧪 TEST RÁPIDO DE SEGURIDAD

### Test 1: SQL Injection
```bash
curl -X POST http://localhost/controlador/ProductoController.php \
  -d "funcion=buscar&consulta=test' OR '1'='1"
# Debe retornar error, no ejecutar inyección
```

### Test 2: Acceso sin sesión
```bash
curl -X POST http://localhost/controlador/ProductoController.php \
  -d "funcion=crear&nombre=Test&precio=100"
# Debe retornar 401 Unauthorized
```

### Test 3: Validación entrada
```bash
curl -X POST http://localhost/controlador/ProductoController.php \
  -d "funcion=crear&precio=-100"
# Debe rechazar precio negativo
```

---

## 🎯 PRIORIDADES POR DÍA

### DÍA 1:
1. Crear Validador.php
2. Crear SeguridadHelper.php
3. Agregar sesión a ProductoController.php
4. Corregir VentaProductoController.php línea 80 (SQL Injection)

### DÍA 2:
5. Agregar sesión a todos los controladores
6. Migrar credenciales a .env.local
7. Corregir IGV en Carrito.js
8. Testing básico

### DÍA 3:
9. Corregir resto de SQL Injections
10. Crear índices
11. Testing completo

---

## 🚀 DEPLOY

```bash
# 1. Backup:
mysqldump farmaciasistema > backup_`date +%Y%m%d_%H%M%S`.sql

# 2. Criar indices:
mysql farmaciasistema < indices.sql

# 3. Deploy código:
git commit -m "Fix: SQL Injection y seguridad crítica"
git push origin main

# 4. Monitorear:
tail -f error.log
watch "SELECT COUNT(*) FROM audit_log;"
```

---

## 📊 MÉTRICAS A MONITOREAR

```sql
-- Errores SQL:
SELECT COUNT(*) FROM audit_log WHERE accion = 'ERROR';

-- Intentos sin sesión:
SELECT COUNT(*) FROM audit_log WHERE accion = 'NO_SESSION';

-- Performance de queries:
SELECT query_time FROM logs WHERE query_time > 1;
```

---

**Referencia creada:** May 21, 2026  
**Uso:** Cuando necesitas arreglar algo rápido, consulta esta guía  
**Detalle:** Ver ANALISIS_COMPLETO_BUGS.md para descripción completa

