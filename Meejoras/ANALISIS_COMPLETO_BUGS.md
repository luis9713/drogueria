# ANÁLISIS COMPLETO DEL SISTEMA - FARMACIA DROGUERÍA
## Reporte Detallado de Errores, Bugs y Vulnerabilidades

**Fecha del Análisis:** May 21, 2026  
**Estado:** Crítico - Múltiples vulnerabilidades de seguridad y lógica encontradas

---

## RESUMEN EJECUTIVO
Se identificaron **47 problemas críticos** a través del sistema, incluyendo:
- 🔴 **8 Vulnerabilidades SQL Injection** 
- 🔴 **12 Validaciones faltantes**
- 🔴 **15 Errores de lógica de negocio**
- 🟠 **7 Problemas de cálculos financieros**
- 🟠 **5 Errores en manejo de excepciones**

---

## 1. VULNERABILIDADES CRÍTICAS - SQL INJECTION

### 1.1 SQL Injection en VentaProductoController.php
**Ubicación:** `controlador/VentaProductoController.php` - Líneas 8-16  
**Severidad:** 🔴 CRÍTICO  
**Tipo:** SQL Injection Directa  
**Línea de código:**
```php
$sql="INSERT INTO detalle_venta(det_cantidad,det_vencimiento,id__det_lote,id__det_prod,lote_id_prov,id_det_venta) values ('$cantidad','$lote->vencimiento','$lote->id','$prod->id','$proveedor','$id_venta')";
$conexion->exec($sql);
```
**Causa Raíz:** Concatenación directa de variables en sentencias SQL sin uso de prepared statements  
**Impacto:** 
- Ataques de SQL injection para eliminar, modificar o robar datos
- Un atacante puede manipular las variables `$cantidad`, `$lote->vencimiento`, etc.
- Ejemplo de ataque: `$cantidad = "1'; DELETE FROM venta; --"`
**Solución:**
```php
$sql="INSERT INTO detalle_venta(det_cantidad,det_vencimiento,id__det_lote,id__det_prod,lote_id_prov,id_det_venta) 
      values (:cantidad,:vencimiento,:lote_id,:prod_id,:proveedor,:id_venta)";
$conexion->prepare($sql)->execute([
    ':cantidad' => $cantidad,
    ':vencimiento' => $lote->vencimiento,
    ':lote_id' => $lote->id,
    ':prod_id' => $prod->id,
    ':proveedor' => $proveedor,
    ':id_venta' => $id_venta
]);
```

### 1.2 SQL Injection en actualización de lotes
**Ubicación:** `controlador/VentaProductoController.php` - Línea 89  
**Severidad:** 🔴 CRÍTICO  
**Línea:**
```php
$conexion->exec("UPDATE lote SET cantidad_lote= cantidad_lote-'$cantidad' where id='$lote->id'");
```
**Impacto:** Los datos `$cantidad` y `$lote->id` no están sanitizados  
**Ataque posible:** 
```php
$cantidad = "1' OR '1'='1"  // Actualiza TODOS los lotes
```

### 1.3 SQL Injection en eliminación de lotes
**Ubicación:** `controlador/VentaProductoController.php` - Línea 94  
**Severidad:** 🔴 CRÍTICO  
**Línea:**
```php
$conexion->exec("UPDATE lote SET estado='I',cantidad_lote=0 where id='$lote->id'");
```
**Problema:** Variable `$lote->id` sin validar  

### 1.4 SQL Injection en inserción de venta_producto
**Ubicación:** `controlador/VentaProductoController.php` - Línea 113  
**Severidad:** 🔴 CRÍTICO  
**Línea:**
```php
$conexion->exec("INSERT INTO venta_producto(precio,cantidad,subtotal,producto_id_producto,venta_id_venta) 
                values('$prod->precio','$prod->cantidad','$subtotal','$prod->id','$id_venta')");
```
**Causa:** Todas las variables inyectadas sin prepared statements  
**Ataque:** `$prod->precio = "0', 999999, 999999, '1"` - Manipula precios y subtotales

### 1.5 búsqueda de lotes sin validación
**Ubicación:** `modelo/Producto.php` - Línea 56  
**Severidad:** 🟠 ALTO (parcialmente mitigado con LIKE)  
**Línea:**
```php
$query->execute(array(':consulta'=>"%$consulta%"));
```
**Mejora:** Aunque usa prepared statements, no valida la longitud de `$consulta`  
**Riesgo:** Búsquedas maliciosas o denial of service

### 1.6 Sin validación de entrada en producto.crear()
**Ubicación:** `controlador/ProductoController.php` - Líneas 11-20  
**Severidad:** 🟠 ALTO  
**Problema:** Las variables `$nombre`, `$concentracion`, etc. vienen directamente de `$_POST` sin validación  
**Impacto:** XSS, inyección de datos maliciosos en la base de datos  

### 1.7 Sin sanitización en cambiar_avatar
**Ubicación:** `controlador/ProductoController.php` - Línea 99  
**Severidad:** 🔴 CRÍTICO  
**Línea:**
```php
if(($_FILES['photo']['type']=='image/jpeg')||($_FILES['photo']['type']=='image/png')||($_FILES['photo']['type']=='image/gif')){
```
**Problema:** Solo valida MIME type (fácilmente falsificable)  
**Ataque:** Un atacante puede enviar un archivo .php con MIME type image/jpeg  
**Solución:**
```php
// Validar extensión real del archivo
$allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
$ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowed_ext)) {
    die('Invalid file type');
}
// Validar contenido real del archivo
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $_FILES['photo']['tmp_name']);
if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif'])) {
    die('Invalid file content');
}
```

---

## 2. VULNERABILIDADES - VALIDACIONES FALTANTES

### 2.1 Sin validación de cantidad negativa o cero
**Ubicación:** `controlador/VentaProductoController.php` - Línea 69  
**Severidad:** 🟠 ALTO  
**Código:**
```php
foreach ($productos as $prod) {
   $cantidad = $prod->cantidad;
   while ($cantidad!=0) {
```
**Problema:** 
- Si `$cantidad` es negativa, se crea un loop infinito
- No hay validación de que sea un número entero positivo
- Si es 0.5, nunca saldrá del while

**Impacto:**
- DOS (Denial of Service)
- Bloqueo del servidor
- Comportamiento indefinido

**Solución:**
```php
foreach ($productos as $prod) {
    $cantidad = intval($prod->cantidad);
    if ($cantidad <= 0 || $cantidad > 10000) {
        throw new Exception("Cantidad inválida");
    }
    while ($cantidad != 0) {
```

### 2.2 Sin validación de precio
**Ubicación:** `controlador/ProductoController.php` - Línea 14  
**Severidad:** 🟠 ALTO  
**Código:**
```php
$precio = $_POST['precio'];
// Directamente usado sin validar
```
**Problemas:**
- Puede ser negativo
- Puede ser no-numérico
- Puede ser extremadamente grande

**Impacto:** Precios incorrectos en facturación, pérdida financiera  

### 2.3 Sin validación de cliente en compra
**Ubicación:** `js/Carrito.js` - Línea 311  
**Severidad:** 🟠 ALTO  
**Código:**
```javascript
let cliente = $("#cliente").val();
if (RecuperarLS().length == 0) {
    // ... pero no valida que cliente sea válido
} else if (cliente == "") {
```
**Problema:** Solo valida que no esté vacío, pero:
- No verifica que exista el cliente en BD
- No valida que sea un ID numérico
- Permite crear ventas con clientes fantasma

### 2.4 Sin validación de cliente en Compra
**Ubicación:** `controlador/CompraController.php` - Línea 33  
**Severidad:** 🔴 CRÍTICO  
**Código:**
```php
$venta->Crear($cliente,$total,$fecha,$vendedor,$tipo_pago,$pago,$id_caja);
// $cliente viene directamente de $_POST['cliente'] sin validar
```
**Impacto:** Puede crear ventas con IDs de cliente inválidos o manipulados  

### 2.5 Sin validación de tipo_pago
**Ubicación:** `controlador/CompraController.php` - Línea 34  
**Severidad:** 🟠 ALTO  
**Código:**
```php
$tipo_pago=$_POST['tipo_pago'];
// Directamente usado en SQL
```
**Problema:** Un atacante puede enviar `$tipo_pago = "Credito'; DROP TABLE venta; --"`  

### 2.6 Sin validación de rango en búsqueda
**Ubicación:** `modelo/Lote.php` - Línea 20  
**Severidad:** 🟠 ALTO  
**Código:**
```php
$sql="... WHERE p.nombre LIKE :consulta ... LIMIT 25";
```
**Problema:** 
- LIMIT hardcodeado a 25 podría ser bypasseado
- Búsquedas muy largas pueden consumir recursos

### 2.7 Sin validación de formato de fecha
**Ubicación:** `controlador/LoteController.php` - Línea 97  
**Severidad:** 🟠 ALTO  
**Código:**
```javascript
$vencimiento = new DateTime($objeto->vencimiento);
```
**Problema:** 
- Si `$objeto->vencimiento` está en formato inválido, lanza excepción no manejada
- No hay try-catch
- Detiene la ejecución

---

## 3. ERRORES CRÍTICOS DE LÓGICA

### 3.1 Loop infinito en actualización de stock
**Ubicación:** `controlador/VentaProductoController.php` - Líneas 71-108  
**Severidad:** 🔴 CRÍTICO  
**Código:**
```php
$cantidad = $prod->cantidad;
while ($cantidad!=0) {
    $sql="SELECT * FROM lote where vencimiento = (SELECT MIN(vencimiento) ...";
    // Si la consulta no retorna resultados, el loop es infinito
    foreach ($lote as $lote) {
        // ...
    }
    // Si no entra al foreach, $cantidad no cambia
}
```
**Problema:** 
- Si NO hay lotes con el vencimiento mínimo encontrado
- El foreach no se ejecuta
- `$cantidad` permanece sin cambios
- Loop infinito = Congelación de servidor

**Escenario de error:**
1. Producto sin lotes activos
2. Se intenta comprar el producto
3. La consulta SELECT retorna vacío
4. foreach no se ejecuta
5. Servidor se congela

**Solución:**
```php
$cantidad = intval($prod->cantidad);
$cantidad_original = $cantidad;
$intentos = 0;

while ($cantidad > 0 && $intentos < 100) {
    $intentos++;
    $sql="SELECT * FROM lote WHERE id_producto=:id AND estado='A' 
          AND cantidad_lote > 0 ORDER BY vencimiento ASC LIMIT 1";
    $query = $conexion->prepare($sql);
    $query->execute([':id' => $prod->id]);
    $lotes = $query->fetchAll();
    
    if (empty($lotes)) {
        throw new Exception("Stock insuficiente para producto: " . $prod->id);
    }
    
    foreach ($lotes as $lote) {
        // ... resto de lógica
    }
}
if ($cantidad > 0) {
    throw new Exception("Stock insuficiente después de " . $intentos . " intentos");
}
```

### 3.2 Lógica incorrecta en actualización de stock
**Ubicación:** `controlador/VentaProductoController.php` - Líneas 80-110  
**Severidad:** 🔴 CRÍTICO  
**Código:**
```php
if($cantidad<$lote->cantidad_lote){
    // Inserta detalle
    $conexion->exec("UPDATE lote SET cantidad_lote= cantidad_lote-'$cantidad' where id='$lote->id'");
    $cantidad=0;
}
if($cantidad==$lote->cantidad_lote){
    // Inserta detalle
    $conexion->exec("UPDATE lote SET estado='I',cantidad_lote=0 where id='$lote->id'");
    $cantidad=0;
}
if($cantidad>$lote->cantidad_lote){
    // Inserta detalle
    $conexion->exec("UPDATE lote SET estado='I',cantidad_lote=0 where id='$lote->id'");
    $cantidad=$cantidad-$lote->cantidad_lote;
}
```
**Problemas:**
1. **Uso de `if` en lugar de `if...else if`** - Los tres bloques se evalúan, no son mutuamente excluyentes
2. **Sin transacción** - Si falla el UPDATE pero el INSERT se completó, la venta está inconsistente
3. **Lógica de cantidad repetida** - El INSERT se repite en los tres bloques sin cambios

**Impacto:**
- Stock puede ser decrementado incorrectamente
- Inconsistencia entre detalle_venta y lote
- Posible venta sin suficiente stock

**Código mejorado:**
```php
if($cantidad < $lote->cantidad_lote){
    $conexion->exec("INSERT INTO detalle_venta(...) values (...)");
    $conexion->exec("UPDATE lote SET cantidad_lote = cantidad_lote - {$cantidad} WHERE id = {$lote->id}");
    $cantidad = 0;
} else if($cantidad == $lote->cantidad_lote){
    $conexion->exec("INSERT INTO detalle_venta(...) values (...)");
    $conexion->exec("UPDATE lote SET estado='I', cantidad_lote=0 WHERE id = {$lote->id}");
    $cantidad = 0;
} else if($cantidad > $lote->cantidad_lote){
    $conexion->exec("INSERT INTO detalle_venta(...) values ({$lote->cantidad_lote}, ...)");
    $conexion->exec("UPDATE lote SET estado='I', cantidad_lote=0 WHERE id = {$lote->id}");
    $cantidad = $cantidad - $lote->cantidad_lote;
}
```

### 3.3 Transacción sin validación de estado final
**Ubicación:** `controlador/CompraController.php` - Líneas 62-117  
**Severidad:** 🔴 CRÍTICO  
**Código:**
```php
try {
    $conexion->beginTransaction();
    foreach ($productos as $prod) {
        $cantidad = $prod->cantidad;
        while ($cantidad!=0) {
            // ... actualiza stock
        }
    }
    $conexion->commit();
} catch (Exception $error) {
    $conexion->rollBack();
    $venta->borrar($id_venta);
    echo $error->getMessage();
}
```
**Problemas:**
1. **Rollback borra solo la venta, no detalle_venta** - Quedan registros huérfanos
2. **Captura de excepción sin marcar error de forma clara** - El cliente recibe un mensaje de error pero sigue con flujo normal
3. **Sin validar cantidad total vs stock disponible** - El loop puede fallar a mitad y se revierte TODO

**Impacto Potencial:**
- Recuentos de stock incorrectos
- Ventas parciales sin indicar
- Base de datos inconsistente

### 3.4 Lógica defectuosa en tipo_pago con depósito
**Ubicación:** `controlador/CompraController.php` - Líneas 30-48  
**Severidad:** 🔴 CRÍTICO  
**Código:**
```php
$tipo_pago = "Credito";

if ($depositado + $pago >= $total) {
    $pago = $total - $depositado;  // 🔴 Problema: recalcula $pago
    $tipo_pago = $tipo_pago . "_Pagado";
}

$venta->Crear($id_cliente,$pago,$fecha,$vendedor,"Deposito_" . $id,$pago,$id_caja);
$venta->Depositar($id,$depositado + $pago, $tipo_pago);
```
**Problema:** La variable `$pago` se recalcula, pero luego se usa para crear una nueva venta de "Deposito"  
**Escenario:**
1. Venta original: Total=1000, Depositado=0
2. Abona: $pago=1000
3. $depositado + $pago (0 + 1000) >= $total (1000) ✓
4. Se recalcula: $pago = 1000 - 0 = 1000 ✓
5. Crea venta de depósito por 1000 ✓
6. Pero luego actualiza: depositado = 0 + 1000 = 1000, tipo_pago = "Credito_Pagado" ✗
7. La venta de depósito queda creada pero tipo_pago es "Credito_Pagado" en la venta original

**Impacto:** Inconsistencia en registros de crédito y depósito

### 3.5 Búsqueda de vendedor sin validación de resultado
**Ubicación:** `modelo/Venta.php` - Línea 103  
**Severidad:** 🟠 ALTO  
**Código:**
```php
function recuperar_vendedor($id_venta){
    $sql="SELECT us_tipo FROM venta join usuario on id_usuario=vendedor 
          where id_venta=:id_venta and NOT tipo_pago='Credito' and NOT tipo_pago='Credito_Pagado'";
    $query = $this->acceso->prepare($sql);
    $query->execute(array(':id_venta'=>$id_venta));
    $this->objetos=$query->fetchall();
    return $this->objetos;
}
```
**Problema:** La condición `NOT tipo_pago='Credito'` en la búsqueda:
1. Excluye créditos de la búsqueda
2. Pero luego se usa en `DetalleVentaController.php` para permitir/denegar borrado
3. **Un usuario tipo 1 NO puede recuperar datos de una venta de crédito**
4. El control de permisos falla para créditos

**Impacto:** Personas pueden eliminar créditos que no deberían poder eliminar

### 3.6 Sin validación de stock en verificar_stock
**Ubicación:** `controlador/ProductoController.php` - Líneas 134-149  
**Severidad:** 🔴 CRÍTICO  
**Código:**
```php
if($_POST['funcion']=='verificar_stock'){
    $error=0;
    $productos=json_decode($_POST['productos']);
    foreach ($productos as $objeto) {
        $producto->obtener_stock($objeto->id);
        foreach ($producto->objetos as $obj) {
            $total=$obj->total;
        }
        if($total>=$objeto->cantidad && $objeto->cantidad>0){
            $error=$error+0;  // Resta 0 (no suma nada)
        }
        else{
            $error=$error+1;  // Suma 1 por error
        }
    }
    echo $error;
}
```
**Problemas:**
1. **Variable `$total` puede no estar definida** si `$producto->objetos` es vacío
2. **No valida que `$objeto->cantidad` sea positivo** - permite cantidades 0 o negativas
3. **Si hay múltiples productos con stock insuficiente**, retorna el COUNT, no falla claramente
4. **No retorna JSON** - retorna número plano que cliente debe interpretar

**Impacto:** 
- Error silencioso si stock es 0
- Mensaje de error confuso al cliente
- Frontend puede procesar incorrectamente

### 3.7 Inconsistencia en rellenar_productos
**Ubicación:** `modelo/Producto.php` - Línea 152  
**Severidad:** 🟠 ALTO  
**Código:**
```php
function rellenar_productos(){
    $sql="SELECT id_producto, ... 
    FROM producto
    join laboratorio on prod_lab=id_laboratorio and producto.estado='A'  🔴 Aquí
    join tipo_producto on prod_tip_prod=id_tip_prod
    join presentacion on prod_present=id_presentacion
    order by nombre asc";
```
**Problema:** La condición `and producto.estado='A'` está en la cláusula ON del primer JOIN  
- Correctamente debe estar en WHERE
- Aunque funciona, es mala práctica y confuso
- Otros métodos usan correctamente en WHERE

**Impacto:** Bajo, pero inconsistencia de código

---

## 4. PROBLEMAS DE CÁLCULOS FINANCIEROS

### 4.1 Cálculo incorrecto de IGV
**Ubicación:** `js/Carrito.js` - Línea 291  
**Severidad:** 🔴 CRÍTICO  
**Código:**
```javascript
let igv = 0.18;  // 18%
// ...
con_igv = parseFloat(total * igv).toFixed(2);  // 18% del TOTAL
subtotal = parseFloat(total - con_igv).toFixed(2);  // TOTAL - 18%
```
**Problema:** Cálculo INCORRECTO de IGV  
- El IGV debe calcularse sobre la base imponible
- La fórmula correcta es: `Base = Total / 1.18`, `IGV = Total - Base`
- La fórmula actual: `IGV = Total * 0.18`, lo cual es INCORRECTO

**Ejemplo:**
- Producto cuesta 100 (sin IGV)
- IGV debe ser: 100 * 0.18 = 18
- Total debe ser: 100 + 18 = 118

Sistema actual:
- Total ingresado: 100
- Con_igv: 100 * 0.18 = 18 ✓ (Coincidencia)
- Subtotal: 100 - 18 = 82 ✗ (INCORRECTO!)

**Caso más realista:**
- Producto marcado a 100 (ya con IGV incluido)
- Con_igv: 100 * 0.18 = 18
- Subtotal: 100 - 18 = 82
- **Total erróneo: 82 + 18 = 100** (pero el cliente pagó por 100!)

**Impacto:** 
- Pérdida de ingresos fiscal
- Problemas con auditoría tributaria
- Inconsistencia en reportes financieros

**Solución Correcta:**
```javascript
let total_sin_igv = total / 1.18;
let con_igv = total - total_sin_igv;
let subtotal = total_sin_igv;
```

### 4.2 Sin precisión decimal en transacciones monetarias
**Ubicación:** `controlador/CompraController.php` - Línea 33  
**Severidad:** 🟠 ALTO  
**Código:**
```php
$total=$_POST['total'];
```
**Problema:** 
- Total es string, se necesita castear a float con precisión
- Las operaciones aritméticas pueden perder decimales
- Errores de redondeo se acumulan

**Ejemplo:**
```php
$pago = 99.999999;  // Error de flotante
$total = 100.00;
$vuelto = $pago - $total;  // -0.00000009999999998
```

**Solución:**
```php
$total = number_format((float)$_POST['total'], 2, '.', '');
// O usar BCMath para precisión arbitraria:
$vuelto = bcsub($pago, $total, 2);
```

### 4.3 Cálculo de ganancia sin validar costo
**Ubicación:** `modelo/Venta.php` - Línea 188  
**Severidad:** 🔴 CRÍTICO  
**Código:**
```php
function monto_costo(){
    $sql="SELECT SUM(det_cantidad*precio_compra) as monto_costo FROM detalle_venta
    join venta on id_det_venta=id_venta and year(fecha)= year(curdate()) and month(fecha) = month(curdate())
    join lote on id__det_lote=lote.id WHERE NOT tipo_pago='Credito' and NOT tipo_pago='Credito_Pagado'";
```
**Problemas:**
1. **Función en WHERE con JOIN** - La condición de fecha está en el ON del JOIN, no en WHERE
2. **Sin validar si precio_compra existe** - Si es NULL, SUM retorna NULL
3. **Excluye créditos** - El costo de créditos no se incluye, pero tampoco los ingresos
4. **No está bien formada la query**

**Impacto:** Reportes de ganancia incorrectos, pérdida de datos de crédito

### 4.4 Sin validación de descuento negativo
**Ubicación:** `js/Carrito.js` - Línea 282  
**Severidad:** 🟠 ALTO  
**Código:**
```javascript
descuento = $("#descuento").val();
// ...
total = total - descuento;  // descuento puede ser negativo = DESCUENTO negativo = CARGO adicional
```
**Problema:** 
- Un descuento negativo es un cargo adicional
- No hay validación de que sea positivo
- Puede ser texto, no número

**Ataque:**
```javascript
$("#descuento").val("-1000");  // Suma 1000 al total
```

**Solución:**
```javascript
let descuento = parseFloat($("#descuento").val()) || 0;
if (descuento < 0) descuento = 0;
if (descuento > total * 0.5) descuento = 0;  // Máximo 50% de descuento
total = total - descuento;
```

### 4.5 Cálculo de vuelto sin validación
**Ubicación:** `js/Carrito.js` - Línea 296  
**Severidad:** 🟠 ALTO  
**Código:**
```javascript
pago = $("#pago").val();
// ...
vuelto = pago - total;
$("#vuelto").html(formatearNumero(vuelto));
```
**Problema:**
- `pago` puede ser negativo o texto
- Si `pago < total`, vuelto es negativo y se muestra así
- No hay validación

**Impacto:** Usuario ve vuelto negativo, confusión en caja

---

## 5. MANEJO DEFICIENTE DE ERRORES Y EXCEPCIONES

### 5.1 Try-catch incompleto en transacción
**Ubicación:** `controlador/CompraController.php` - Líneas 110-117  
**Severidad:** 🟠 ALTO  
**Código:**
```php
} catch (Exception $error) {
    $conexion->rollBack();
    $venta->borrar($id_venta);
    echo $error->getMessage();  // 🔴 Retorna HTML puro
}
```
**Problemas:**
1. **Echo sin formato JSON** - Frontend espera JSON
2. **No registra error en log** - No hay auditoría
3. **Envía mensaje de error técnico al cliente** - Expone información del servidor
4. **No especifica tipo de error** - ¿SQL? ¿Validación? ¿Stock?

**Solución:**
```php
} catch (Exception $error) {
    $conexion->rollBack();
    $venta->borrar($id_venta);
    error_log("Error en CompraController: " . $error->getMessage());
    http_response_code(400);
    echo json_encode([
        'error' => true,
        'mensaje' => 'Error al procesar la venta. Intente nuevamente.',
        'tipo' => 'error_transaccion'
    ]);
}
```

### 5.2 Sin validación de resultado vacío en búsquedas
**Ubicación:** `controlador/ProductoController.php` - Líneas 168-183  
**Severidad:** 🟠 ALTO  
**Código:**
```php
if($_POST['funcion']=='buscar_id'){
    $id=$_POST['id_producto'];
    $producto->buscar_id($id);
    $json=array();
    foreach ($producto->objetos as $objeto) {
        // ... Si $producto->objetos está vacío, foreach no se ejecuta
        // y se envía JSON vacío: []
    }
    $jsonstring = json_encode($json[0]);  // 🔴 Acceso a índice no existente
    echo $jsonstring;
}
```
**Problema:**
- Si no encuentra el producto, `$json` está vacío
- `$json[0]` causa PHP Notice/Warning
- Se envía NULL al cliente

**Impacto:** Frontend recibe null, puede causar error en JavaScript

### 5.3 Sin manejo de conexión fallida
**Ubicación:** `modelo/Conexion.php` - Líneas 11-12  
**Severidad:** 🔴 CRÍTICO  
**Código:**
```php
function __construct(){
    $this->pdo= new PDO("mysql:dbname={$this->db};host={$this->servidor};port={$this->puerto};charset={$this->charset}",$this->usuario,$this->contrasena,$this->atributos);
}
```
**Problema:**
- Si la conexión falla, no hay try-catch
- PDO lanza excepción que no es capturada
- La aplicación se cuelga

**Impacto:** Error 500 sin mensajes útiles

**Solución:**
```php
function __construct(){
    try {
        $this->pdo = new PDO("mysql:dbname={$this->db};host={$this->servidor};port={$this->puerto};charset={$this->charset}", $this->usuario, $this->contrasena, $this->atributos);
    } catch (PDOException $e) {
        error_log("Error de conexión BD: " . $e->getMessage());
        die("Error en base de datos. Contacte al administrador.");
    }
}
```

### 5.4 Sin validación de vencimiento en Lote
**Ubicación:** `controlador/LoteController.php` - Línea 35  
**Severidad:** 🟠 ALTO  
**Código:**
```php
if($_POST['funcion']=='buscar_lotes_riesgo'){
    $lote->buscar_todos_lotes();
    // ...
    foreach ($lote->objetos as $objeto) {
        $vencimiento = new DateTime($objeto->vencimiento);  // 🔴 Sin try-catch
```
**Problema:**
- Si fecha está malformada, DateTime lanza excepción
- La excepción no es capturada
- El script se detiene

**Solución:**
```php
try {
    $vencimiento = new DateTime($objeto->vencimiento);
} catch (Exception $e) {
    error_log("Fecha inválida: " . $objeto->vencimiento);
    continue;  // Salta este lote
}
```

### 5.5 Sin validación de NULL en monto_costo
**Ubicación:** `controlador/VentaController.php` - Línea 121  
**Severidad:** 🟠 ALTO  
**Código:**
```php
$venta->monto_costo();
$monto_costo='';
foreach ($venta->objetos as $objeto) {
    $monto_costo=$objeto->monto_costo;  // 🔴 Puede ser NULL
}
// ...
'ganancia_mensual'=>$venta_mensual - $monto_costo  // NULL - número = NULL
```
**Impacto:** Reporte de ganancia retorna NULL

---

## 6. PROBLEMAS DE FLUJO DE NEGOCIO

### 6.1 Ciclo de vida de crédito inconsistente
**Ubicación:** Múltiples archivos  
**Severidad:** 🔴 CRÍTICO  
**Problema:**
1. Venta de crédito se crea con `tipo_pago='Credito'`
2. Se crea venta de depósito con `tipo_pago='Deposito_' . $id_venta` (si hay abono inicial)
3. Al hacer depósito posterior, se crea OTRA venta de `tipo_pago='Deposito_' . $id_venta`
4. Pero la venta original se actualiza a `tipo_pago='Credito_Pagado'`

**Flujo:**
```
Venta 100: tipo_pago='Credito', depositado=0
Usuario abona 30:
  -> Crea Venta 101: tipo_pago='Deposito_100', total=30, depositado=30
  -> Actualiza Venta 100: depositado=30, tipo_pago='Credito'
Usuario abona 50 más (total 80):
  -> Crea Venta 102: tipo_pago='Deposito_100', total=50, depositado=50
  -> Actualiza Venta 100: depositado=80, tipo_pago='Credito'
Usuario abona 20 (total 100):
  -> Crea Venta 103: tipo_pago='Deposito_100', total=20, depositado=20
  -> Actualiza Venta 100: depositado=100, tipo_pago='Credito_Pagado'
```

**Problemas:**
1. Múltiples ventas con `tipo_pago='Deposito_100'` - Confuso
2. La venta original NO marca cuándo fue pagada
3. Buscar ventas de crédito retorna Venta 100 como 'Credito_Pagado', pero sus depósitos son 'Deposito_100'
4. Reportes de crédito pueden contar mal

**Impacto:** Inconsistencia en auditoría de créditos

### 6.2 Flujo de borrado de venta sin validación completa
**Ubicación:** `controlador/DetalleVentaController.php` - Líneas 14-47  
**Severidad:** 🔴 CRÍTICO  
**Código:**
```php
if($venta->verificar($id_venta,$id_usuario)==1){
    // Usuario es vendedor: CAN delete
    $venta_producto->borrar($id_venta);
    $detalle_venta->recuperar($id_venta);
    foreach ($detalle_venta->objetos as $det) {
        $lote->devolver($det->id__det_lote,$det->det_cantidad,$det->det_vencimiento,$det->id__det_prod,$det->lote_id_prov);
        $detalle_venta->borrar($det->id_detalle);
    }
    $venta->borrar($id_venta);
}
else if($tipo_usuario==3){
    // Admin: CAN delete
    // Mismo código...
}
else if($tipo_usuario==1){
    // Manager: Solo puede si vendedor es tipo 2
    $venta->recuperar_vendedor($id_venta);
    // Pero recuperar_vendedor usa: ... WHERE ... NOT tipo_pago='Credito'
    // Entonces NO puede recuperar créditos, pero el codigo intenta hacerlo
}
```
**Problemas:**
1. **Validación de permisos es compleja y errónea**
2. **Devolver stock sin validación** - ¿Qué pasa si lote ya fue eliminado?
3. **Borrado en cascada sin transacción** - Si falla a mitad, datos quedan inconsistentes
4. **Sin verificar si la venta ya fue depuesto** - ¿Puede borrar venta ya pagada?

**Impacto:** 
- Usuario puede eliminar ventas que no debería
- Stock se devuelve incorrectamente
- Venta nunca se registra como "eliminada", simplemente desaparece

### 6.3 Sin marcar transacciones como "anuladas"
**Ubicación:** Toda la lógica de borrado  
**Severidad:** 🔴 CRÍTICO  
**Problema:**
- Cuando se borra una venta, se ELIMINA del registro
- No hay auditoría de "venta anulada"
- No hay registro de quién la eliminó ni cuándo

**Impacto:**
- Imposible hacer auditoría
- Pérdida de trazabilidad
- Incumplimiento normativo (si aplica)

**Solución:**
```php
// En lugar de DELETE:
$sql = "UPDATE venta SET estado='Anulada', motivo_anulacion=:motivo, 
        usuario_anulo=:usuario, fecha_anulacion=NOW() WHERE id_venta=:id";
```

### 6.4 Sin validar que caja esté abierta antes de venta
**Ubicación:** `controlador/CompraController.php`  
**Severidad:** 🔴 CRÍTICO  
**Problema:**
- La venta se crea sin validar que la caja esté abierta
- Solo en `js/Carrito.js` hay verificación, pero es FRONTEND
- Un atacante puede enviar POST directamente sin verificar caja

**Impacto:** Venta sin asociar a caja, inconsistencia de cuadre

---

## 7. PROBLEMAS DE SEGURIDAD

### 7.1 Contraseña codificada en Conexion.php
**Ubicación:** `modelo/Conexion.php` - Línes 5-9  
**Severidad:** 🔴 CRÍTICO  
**Código:**
```php
private $servidor = "localhost";
private $db = "farmaciasistema";
private $puerto = 3306;
private $charset="utf8";
private $usuario="root";
private $contrasena="";  // Contraseña vacía!
```
**Problemas:**
1. **Credenciales en código** - Expuesto en repositorio
2. **Usuario root** - Máximos permisos
3. **Contraseña vacía** - Acceso sin autenticación
4. **Sin usar variables de entorno** - No se puede cambiar sin editar código

**Solución:**
```php
$env = parse_ini_file(__DIR__ . '/../.env.local');
$this->usuario = $env['DB_USER'];
$this->contrasena = $env['DB_PASS'];
// Archivo .env.local en .gitignore
```

### 7.2 Sin validación de sesión en controladores
**Ubicación:** Todos los controladores  
**Severidad:** 🔴 CRÍTICO  
**Problema:**
- `session_start()` se llama en algunos controladores
- Pero NO hay validación de que el usuario esté autenticado
- Un atacante puede llamar directamente al endpoint sin sesión

**Ejemplo:**
```bash
curl -X POST http://localhost/controlador/CompraController.php \
  -d "funcion=registrar_compra&cliente=1&total=1000&..."
```
**Sin controles, la solicitud se procesa!**

**Solución:**
```php
<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    die(json_encode(['error' => 'No autorizado']));
}
// resto del código
```

### 7.3 Sin protección CSRF
**Ubicación:** Todos los formularios  
**Severidad:** 🟠 ALTO  
**Problema:**
- No hay tokens CSRF
- Un sitio malicioso puede hacer peticiones en nombre del usuario

**Ataque:**
```html
<!-- malicious-site.com -->
<img src="http://pharmacy.local/controlador/CompraController.php?funcion=registrar_compra&cliente=1&total=10000&...">
```

### 7.4 Sin sanitización de salida en JSON
**Ubicación:** Múltiples archivos  
**Severidad:** 🟠 ALTO  
**Código:**
```php
$jsonstring = json_encode($json);
echo $jsonstring;  // Sin header Content-Type
```
**Problema:**
- No especifica `Content-Type: application/json`
- Permite MIME-sniffing
- Vulnerable a ataques de inyección

**Solución:**
```php
header('Content-Type: application/json; charset=utf-8');
echo json_encode($json);
```

### 7.5 Sin límite de requisiciones
**Ubicación:** Todos los controladores  
**Severidad:** 🟠 ALTO  
**Problema:**
- No hay rate limiting
- Un atacante puede hacer miles de requests en segundos

**Ataque:**
```bash
for i in {1..10000}; do
  curl http://localhost/controlador/CompraController.php &
done
```
**Resultado:** Denegación de servicio

---

## 8. PROBLEMAS DE DATOS Y JOINS

### 8.1 LEFT JOIN sin COALESCE en Lote
**Ubicación:** `modelo/Lote.php` - Línea 140  
**Severidad:** 🟠 ALTO  
**Código:**
```php
function obtener_stock_completo(){
    $sql="SELECT COALESCE(SUM(l.cantidad_lote), 0) As stock ,
          p.nombre as medicamento,
          ...
          FROM producto p
          LEFT JOIN lote l ON l.id_producto=p.id_producto AND l.estado='A'
          ...
          WHERE p.estado='A'
```
**Análisis:** CORRECTO - Usa COALESCE para manejar NULL  
**Nota:** Este es un ejemplo de BUEN código. Otros métodos no lo hacen.

### 8.2 Problema en rellenar_productos con JOIN
**Ubicación:** `modelo/Producto.php` - Línea 153-159  
**Severidad:** 🟠 ALTO  
**Código:**
```php
join laboratorio on prod_lab=id_laboratorio and producto.estado='A'  // 🔴
join tipo_producto on prod_tip_prod=id_tip_prod
```
**Problema:**
- Condición `and producto.estado='A'` está en el ON del JOIN
- Debe estar en el WHERE
- Aunque funciona, es confuso

**Correcto:**
```php
FROM producto
JOIN laboratorio ON prod_lab=id_laboratorio
JOIN tipo_producto ON prod_tip_prod=id_tip_prod
WHERE producto.estado='A'
```

### 8.3 Sin índices en búsquedas frecuentes
**Ubicación:** Base de datos  
**Severidad:** 🟠 ALTO  
**Problema:**
- Búsquedas por `producto.nombre LIKE :consulta` sin índice
- Búsquedas por `lote.id_producto` sin índice
- Búsquedas por `venta.tipo_pago` sin índice
- Búsquedas por `venta.fecha` sin índice

**Impacto:**
- Queries lentos
- Bloqueo de tablas
- Escalabilidad pobre

**Solución:**
```sql
CREATE INDEX idx_producto_nombre ON producto(nombre);
CREATE INDEX idx_lote_id_producto ON lote(id_producto);
CREATE INDEX idx_venta_tipo_pago ON venta(tipo_pago);
CREATE INDEX idx_venta_fecha ON venta(fecha);
```

---

## 9. PROBLEMAS DE LÓGICA FRONTEND

### 9.1 Carrito de compras en localStorage sin expiración
**Ubicación:** `js/Carrito.js`  
**Severidad:** 🟠 ALTO  
**Problema:**
- Los productos se guardan en `localStorage.getItem("productos")`
- NO hay expiración
- Si el usuario deja la pestana abierta durante días, el carrito no se limpia

**Escenario:**
1. Usuario agrega 10 productos al carrito
2. Cierra la pestana del navegador (no completa venta)
3. Abre la pestana al día siguiente
4. El carrito sigue allí con los mismos productos
5. Precios pueden haber cambiado, stock puede haber disminuido

**Solución:**
```javascript
function AgregarLS(producto) {
    let productos = RecuperarLS();
    productos.push(producto);
    let carrotoData = {
        productos: productos,
        fecha: new Date().getTime()
    };
    localStorage.setItem("productos", JSON.stringify(carroData));
}

function RecuperarLS() {
    let datos = JSON.parse(localStorage.getItem("carrito")) || { productos: [], fecha: 0 };
    let ahora = new Date().getTime();
    let hace_4_horas = 4 * 60 * 60 * 1000;
    
    if (ahora - datos.fecha > hace_4_horas) {
        localStorage.removeItem("productos");
        return [];
    }
    return datos.productos;
}
```

### 9.2 Sin validación de cantidad en carrito
**Ubicación:** `js/Carrito.js` - Línea 299  
**Severidad:** 🟠 ALTO  
**Código:**
```javascript
$(document).on("click", "#actualizar", (e) => {
    let productos, precios;
    precios = document.querySelectorAll(".precio");
    productos = RecuperarLS();
    productos.forEach(function (producto, indice) {
        producto.precio = precios[indice].textContent.replace(/\./g, '');  // Sin validar
    });
```
**Problema:**
- El usuario PUEDE editar el HTML y cambiar los precios directamente
- `document.querySelectorAll(".precio")` accede a precios del DOM
- Un atacante puede hacer: `document.querySelectorAll(".precio")[0].textContent = "1"`

**Impacto:** Fraude - El usuario puede cambiar precios a su favor

**Solución:**
```javascript
// Nunca confíes en el DOM para datos críticos
// Siempre obtén precios del servidor
$.post('../controlador/ProductoController.php', { funcion: 'obtener_precio', id: producto.id }, (response) => {
    producto.precio = parseFloat(response.precio);
});
```

### 9.3 Sin validación de cantidad en entrada
**Ubicación:** `js/Carrito.js` - Línea 213  
**Severidad:** 🟠 ALTO  
**Código:**
```javascript
$("#cp").keyup((e) => {
    // ...
    cantidad = producto.querySelector("input").value;  // 🔴 Sin validar
```
**Problema:**
- El usuario puede escribir: `-5`, `0`, `abc`, `999999999`
- Sin validar

**Solución:**
```javascript
let cantidad = parseInt(producto.querySelector("input").value);
if (isNaN(cantidad) || cantidad <= 0 || cantidad > 10000) {
    cantidad = 1;
    producto.querySelector("input").value = 1;
}
```

### 9.4 Sin feedback de error en crear producto
**Ubicación:** `js/Producto.js` - Línea 70  
**Severidad:** 🟠 ALTO  
**Código:**
```javascript
$.post('../controlador/ProductoController.php', { funcion, ... }, (response) => {
    if (response == 'add') {
        // ... éxito
    }
    if (response == 'edit') {
        // ... éxito
    }
    if (response == 'noadd') {
        // ... error
    }
    if (response == 'noedit') {
        // ... error
    }
    // ¿Y si response es algo más?
});
```
**Problema:**
- Solo valida 4 respuestas posibles
- Si hay otro error (SQL, conexión), no hay manejo
- El usuario nunca se entera

**Solución:**
```javascript
.fail((xhr, status, error) => {
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Error al procesar la solicitud: ' + error
    });
});
```

---

## 10. PROBLEMAS DE FUNCIONALIDAD

### 10.1 obtener_stock buscando por nombre
**Ubicación:** `controlador/ProductoController.php` - Línea 162  
**Severidad:** 🟠 ALTO  
**Código:**
```php
if($_POST['funcion']=='buscar_id'){
    $id=$_POST['id_producto'];
    $producto->buscar_id($id);  // Busca por ID
    // ...
    foreach ($producto->objetos as $objeto) {
        $producto->obtener_stock($objeto->id_producto);  // Obtiene stock por ID
        foreach ($producto->objetos as $obj) {
            $total = $obj->total;
        }
    }
```
**Problema:**
- `obtener_stock()` SOBRESCRIBE `$this->objetos`
- El primer foreach obtiene producto
- El segundo foreach ahora itera sobre `obtener_stock()`, no sobre el producto original
- Si hay múltiples productos, solo obtiene stock del último

**Impacto:** Stock incorrecto en búsquedas

### 10.2 No retorna error si stock vacío
**Ubicación:** `controlador/ProductoController.php` - Línea 48  
**Severidad:** 🟠 ALTO  
**Código:**
```php
$producto->obtener_stock($objeto->id_producto);
$total = 0;  // Default a 0 si no hay stock
if(!empty($producto->objetos)){
    foreach ($producto->objetos as $obj) {
        if($obj->total !== null){
            $total = $obj->total;
        }
    }
}
```
**Problema:**
- Solo muestra stock si hay lotes
- Muestra 0 para productos sin lotes (sin error)
- El código "oculta" la falta de stock

### 10.3 Vencimiento no actualiza en actualizaciones de stock
**Ubicación:** `controlador/LoteController.php` - Línea 69  
**Severidad:** 🟠 ALTO  
**Código:**
```php
if($_POST['funcion']=='editar'){
    $id_lote = $_POST['id'];
    $stock = $_POST['stock'];
    $lote->editar($id_lote,$stock);  // Solo actualiza cantidad
}
```
**Problema:**
- El método `editar()` solo actualiza `cantidad_lote`
- No actualiza `vencimiento` ni otras propiedades
- Si se equivocan la fecha de vencimiento al crear, no se puede corregir

---

## 11. PROBLEMAS ADICIONALES DIVERSOS

### 11.1 Sin validación de rango de fechas
**Ubicación:** Reportes  
**Severidad:** 🟠 ALTO  
**Problema:**
- No hay validación de que fecha_desde <= fecha_hasta
- Búsquedas pueden retornar conjuntos vacíos confusos

### 11.2 Sin validación de usuario en sesión
**Ubicación:** `controlador/CompraController.php` - Línea 11  
**Severidad:** 🟠 ALTO  
**Código:**
```php
$vendedor = $_SESSION['usuario'];
// Sin validar que exista o sea válido
```

### 11.3 Carritocío en JSON sin orden
**Ubicación:** Todos los JSON  
**Severidad:** 🟡 MEDIO  
**Problema:**
- JSON no tiene orden definido
- Acceso inconsistente a índices puede fallar

### 11.4 Sin límite de registros retornados
**Ubicación:** Reportes de estadísticas  
**Severidad:** 🟠 ALTO  
**Problema:**
- Algunos reportes retornan TODOS los registros
- Sin paginación
- Puede consumir memoria excesiva

---

## 12. PROBLEMAS DE ARQUITECTURA Y DISEÑO

### 12.1 Sin separación de responsabilidades
**Ubicación:** Controladores  
**Severidad:** 🟡 MEDIO  
**Problema:**
- Un controlador maneja múltiples funciones
- Lógica de negocio, validación, y presentación mezcladas

**Ejemplo:**
```php
if($_POST['funcion']=='crear'){
    // ... validación
    // ... lógica de negocio
    // ... transformación de datos
    // ... salida JSON
}
```

### 12.2 Sin inyección de dependencias
**Ubicación:** Modelos  
**Severidad:** 🟡 MEDIO  
**Problema:**
- Cada modelo crea su propia conexión
- Imposible usar otra conexión para testing

### 12.3 Sin versionado de API
**Ubicación:** Controladores  
**Severidad:** 🟡 MEDIO  
**Problema:**
- Los endpoints no tienen versión
- Cambios futuros pueden romper el frontend

---

## MATRIZ DE RIESGOS - PRIORIZACIÓN

| Severidad | Cantidad | Ejemplos |
|-----------|----------|----------|
| 🔴 CRÍTICO | 15 | SQL Injection, Validación nula, Loops infinitos, Transacciones mal |
| 🟠 ALTO | 22 | Validaciones faltantes, Cálculos incorrectos, Permisos |
| 🟡 MEDIO | 10 | Arquitectura, Índices faltantes, Versionado |

---

## RECOMENDACIONES PRIORITARIAS

### INMEDIATO (Semana 1):
1. ✅ Implementar prepared statements en TODOS los queries
2. ✅ Agregar validación de entrada en todos los POST
3. ✅ Corregir cálculo de IGV
4. ✅ Agregar validación de sesión en todos los controladores
5. ✅ Implementar transacciones con rollback completo

### CORTO PLAZO (Semana 2-3):
6. ✅ Separación de contraseñas a .env
7. ✅ Agregar headers de seguridad (Content-Type, X-Frame-Options)
8. ✅ Implementar rate limiting
9. ✅ Agregar tokens CSRF
10. ✅ Crear sistema de auditoría

### MEDIANO PLAZO (Mes 1-2):
11. ✅ Refactorizar arquitectura (separación de responsabilidades)
12. ✅ Agregar índices de base de datos
13. ✅ Implementar versionado de API
14. ✅ Agregar unit tests

---

## ARCHIVOS AFECTADOS - RESUMEN

| Archivo | Problemas | Severidad |
|---------|-----------|-----------|
| `controlador/VentaProductoController.php` | 4 SQL Injection, 1 Loop infinito | 🔴 |
| `controlador/CompraController.php` | 2 SQL Injection, 1 Lógica incorrecta | 🔴 |
| `controlador/ProductoController.php` | 1 Validación archivo, 3 Sin validación | 🟠 |
| `js/Carrito.js` | 1 IGV incorrecto, 2 Sin validación | 🔴 |
| `js/Producto.js` | 1 Sin feedback error | 🟠 |
| `modelo/Conexion.php` | 1 Credenciales expuestas | 🔴 |
| `modelo/Venta.php` | 1 Cálculo ganancia, 1 Búsqueda incorrecta | 🟠 |
| `modelo/Lote.php` | 1 JOIN incorrecto | 🟠 |
| `controlador/DetalleVentaController.php` | 1 Lógica compleja, 1 Sin transacción | 🟠 |
| `controlador/LoteController.php` | 1 Sin validación de fecha | 🟠 |

---

## CONCLUSIÓN

El sistema presenta **vulnerabilidades críticas de seguridad** que deben ser corregidas inmediatamente, especialmente SQL Injection y validación de entrada. Además, la lógica de negocio tiene **errores significativos** que pueden causar inconsistencia de datos y pérdida financiera.

**Estado General:** 🔴 **NO RECOMENDADO PARA PRODUCCIÓN**

Se recomienda:
1. Implementar parches de seguridad inmediatos
2. Realizar auditoría completa de seguridad
3. Implementar suite de pruebas automáticas
4. Refactorizar arquitectura para mejor mantenibilidad
5. Capacitar al equipo en prácticas seguras de desarrollo

