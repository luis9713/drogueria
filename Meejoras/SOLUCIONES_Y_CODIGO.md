# SOLUCIONES Y CÓDIGO CORREGIDO
## Farmacia Droguería - Plan de Remediación

---

## 1. CORRECCIONES DE SQL INJECTION

### Solución 1.1: Corregir VentaProductoController.php - Línea 69-108

**Archivo:** `controlador/VentaProductoController.php`

**ANTES (VULNERABLE):**
```php
foreach ($productos as $prod) {
   $cantidad = $prod->cantidad;
   while ($cantidad!=0) {
        $sql="SELECT * FROM lote where vencimiento = (SELECT MIN(vencimiento) FROM lote where id_producto=:id and estado='A') and id_producto=:id";
        $query = $conexion->prepare($sql);
        $query->execute(array(':id'=>$prod->id));
        $lote=$query->fetchall();

        foreach ($lote as $lote) {
            $sql="SELECT compra.id_proveedor as proveedor FROM lote
            JOIN compra on lote.id_compra = compra.id and lote.id=:id";
            $query = $conexion->prepare($sql);
            $query->execute(array(':id'=>$lote->id));
            $prov=$query->fetchall();
            $proveedor = $prov[0]->proveedor;
           if($cantidad<$lote->cantidad_lote){
               $sql="INSERT INTO detalle_venta(det_cantidad,det_vencimiento,id__det_lote,id__det_prod,lote_id_prov,id_det_venta) values ('$cantidad','$lote->vencimiento','$lote->id','$prod->id','$proveedor','$id_venta')";
               $conexion->exec($sql);  // 🔴 SQL Injection!
               $conexion->exec("UPDATE lote SET cantidad_lote= cantidad_lote-'$cantidad' where id='$lote->id'");  // 🔴 SQL Injection!
               $cantidad=0;
           }
           // ... más código con inyección
        }
    }
    $subtotal = $prod->cantidad*$prod->precio;
    $conexion->exec("INSERT INTO venta_producto(precio,cantidad,subtotal,producto_id_producto,venta_id_venta) values('$prod->precio','$prod->cantidad','$subtotal','$prod->id','$id_venta')");  // 🔴 SQL Injection!
}
```

**DESPUÉS (CORREGIDO):**
```php
foreach ($productos as $prod) {
   $cantidad = intval($prod->cantidad);  // Validar que sea entero
   if ($cantidad <= 0 || $cantidad > 10000) {
       throw new Exception("Cantidad inválida para producto " . intval($prod->id));
   }
   
   $intentos = 0;
   $max_intentos = 100;
   
   while ($cantidad > 0 && $intentos < $max_intentos) {
       $intentos++;
       
       // Obtener lote con vencimiento más próximo
       $sql = "SELECT * FROM lote 
               WHERE id_producto = :id_producto 
               AND estado = 'A' 
               AND cantidad_lote > 0 
               ORDER BY vencimiento ASC 
               LIMIT 1";
       $query = $conexion->prepare($sql);
       $query->execute([':id_producto' => intval($prod->id)]);
       $lotes = $query->fetchAll();
       
       if (empty($lotes)) {
           throw new Exception("Stock insuficiente para producto ID: " . intval($prod->id) . 
                             ". Solicitado: $cantidad, Disponible: 0");
       }
       
       foreach ($lotes as $lote) {
           // Obtener proveedor de forma segura
           $sql = "SELECT c.id_proveedor as proveedor FROM lote l
                   JOIN compra c ON l.id_compra = c.id 
                   WHERE l.id = :id_lote";
           $query = $conexion->prepare($sql);
           $query->execute([':id_lote' => intval($lote->id)]);
           $prov_result = $query->fetch();
           
           if (!$prov_result) {
               throw new Exception("Error: No se encontró proveedor para lote " . intval($lote->id));
           }
           $proveedor = intval($prov_result->proveedor);
           
           if ($cantidad < $lote->cantidad_lote) {
               // Caso 1: Cantidad solicitada es menor que lote disponible
               $sql = "INSERT INTO detalle_venta 
                       (det_cantidad, det_vencimiento, id__det_lote, id__det_prod, lote_id_prov, id_det_venta) 
                       VALUES (:cantidad, :vencimiento, :lote_id, :prod_id, :proveedor, :venta_id)";
               $query = $conexion->prepare($sql);
               $query->execute([
                   ':cantidad' => intval($cantidad),
                   ':vencimiento' => $lote->vencimiento,
                   ':lote_id' => intval($lote->id),
                   ':prod_id' => intval($prod->id),
                   ':proveedor' => $proveedor,
                   ':venta_id' => intval($id_venta)
               ]);
               
               $sql = "UPDATE lote SET cantidad_lote = cantidad_lote - :cantidad WHERE id = :id";
               $query = $conexion->prepare($sql);
               $query->execute([
                   ':cantidad' => intval($cantidad),
                   ':id' => intval($lote->id)
               ]);
               $cantidad = 0;
               
           } else if ($cantidad == $lote->cantidad_lote) {
               // Caso 2: Cantidad exacta
               $sql = "INSERT INTO detalle_venta 
                       (det_cantidad, det_vencimiento, id__det_lote, id__det_prod, lote_id_prov, id_det_venta) 
                       VALUES (:cantidad, :vencimiento, :lote_id, :prod_id, :proveedor, :venta_id)";
               $query = $conexion->prepare($sql);
               $query->execute([
                   ':cantidad' => intval($cantidad),
                   ':vencimiento' => $lote->vencimiento,
                   ':lote_id' => intval($lote->id),
                   ':prod_id' => intval($prod->id),
                   ':proveedor' => $proveedor,
                   ':venta_id' => intval($id_venta)
               ]);
               
               $sql = "UPDATE lote SET estado = 'I', cantidad_lote = 0 WHERE id = :id";
               $query = $conexion->prepare($sql);
               $query->execute([':id' => intval($lote->id)]);
               $cantidad = 0;
               
           } else if ($cantidad > $lote->cantidad_lote) {
               // Caso 3: Cantidad mayor - usar todo el lote y continuar
               $sql = "INSERT INTO detalle_venta 
                       (det_cantidad, det_vencimiento, id__det_lote, id__det_prod, lote_id_prov, id_det_venta) 
                       VALUES (:cantidad, :vencimiento, :lote_id, :prod_id, :proveedor, :venta_id)";
               $query = $conexion->prepare($sql);
               $query->execute([
                   ':cantidad' => intval($lote->cantidad_lote),
                   ':vencimiento' => $lote->vencimiento,
                   ':lote_id' => intval($lote->id),
                   ':prod_id' => intval($prod->id),
                   ':proveedor' => $proveedor,
                   ':venta_id' => intval($id_venta)
               ]);
               
               $sql = "UPDATE lote SET estado = 'I', cantidad_lote = 0 WHERE id = :id";
               $query = $conexion->prepare($sql);
               $query->execute([':id' => intval($lote->id)]);
               
               $cantidad = $cantidad - intval($lote->cantidad_lote);
           }
       }
   }
   
   // Validar que se distribuyó toda la cantidad
   if ($cantidad > 0) {
       throw new Exception("Stock insuficiente después de $max_intentos intentos. Faltaron: $cantidad unidades");
   }
   
   // Insertar en venta_producto
   $subtotal = intval($prod->cantidad) * floatval($prod->precio);
   $sql = "INSERT INTO venta_producto 
           (precio, cantidad, subtotal, producto_id_producto, venta_id_venta) 
           VALUES (:precio, :cantidad, :subtotal, :prod_id, :venta_id)";
   $query = $conexion->prepare($sql);
   $query->execute([
       ':precio' => floatval($prod->precio),
       ':cantidad' => intval($prod->cantidad),
       ':subtotal' => $subtotal,
       ':prod_id' => intval($prod->id),
       ':venta_id' => intval($id_venta)
   ]);
}
```

---

## 2. CORRECCIONES DE VALIDACIÓN

### Solución 2.1: Crear clase de validación centralizada

**Archivo NUEVO:** `modelo/Validador.php`

```php
<?php
class Validador {
    
    /**
     * Valida que sea un entero positivo
     */
    public static function validar_cantidad($valor, $max = 10000) {
        $cantidad = intval($valor);
        if ($cantidad <= 0 || $cantidad > $max) {
            throw new Exception("Cantidad inválida: debe ser entre 1 y $max");
        }
        return $cantidad;
    }
    
    /**
     * Valida que sea un precio válido
     */
    public static function validar_precio($valor) {
        $precio = floatval($valor);
        if ($precio < 0) {
            throw new Exception("Precio no puede ser negativo");
        }
        if ($precio > 999999999.99) {
            throw new Exception("Precio excede límite máximo");
        }
        return round($precio, 2);  // Precisión de 2 decimales
    }
    
    /**
     * Valida ID de entidad
     */
    public static function validar_id($id) {
        $id = intval($id);
        if ($id <= 0) {
            throw new Exception("ID inválido");
        }
        return $id;
    }
    
    /**
     * Valida nombre/texto
     */
    public static function validar_texto($valor, $min = 1, $max = 255) {
        $texto = trim(strval($valor));
        if (strlen($texto) < $min || strlen($texto) > $max) {
            throw new Exception("Texto debe tener entre $min y $max caracteres");
        }
        return $texto;
    }
    
    /**
     * Valida email
     */
    public static function validar_email($email) {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Email inválido");
        }
        return $email;
    }
    
    /**
     * Valida fecha
     */
    public static function validar_fecha($fecha, $formato = 'Y-m-d') {
        $d = DateTime::createFromFormat($formato, $fecha);
        if ($d === false || $d->format($formato) !== $fecha) {
            throw new Exception("Fecha inválida. Formato esperado: $formato");
        }
        return $fecha;
    }
    
    /**
     * Valida tipo_pago
     */
    public static function validar_tipo_pago($tipo) {
        $tipos_validos = ['Contado', 'Credito', 'Credito_Pagado', 'Deposito'];
        $tipo = trim(strval($tipo));
        
        // Si empieza con Deposito_, validar que sea Deposito_NNN (número)
        if (strpos($tipo, 'Deposito_') === 0) {
            $venta_id = substr($tipo, strlen('Deposito_'));
            if (!is_numeric($venta_id)) {
                throw new Exception("Tipo de pago inválido");
            }
            return $tipo;
        }
        
        if (!in_array($tipo, $tipos_validos)) {
            throw new Exception("Tipo de pago no válido: $tipo");
        }
        return $tipo;
    }
    
    /**
     * Valida archivo de imagen
     */
    public static function validar_imagen($file_tmp, $file_name, $file_type) {
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        $allowed_mime = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024;  // 5MB
        
        // Validar extensión
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed_ext)) {
            throw new Exception("Extensión de archivo no permitida: $ext");
        }
        
        // Validar MIME type
        if (!in_array($file_type, $allowed_mime)) {
            throw new Exception("Tipo de archivo no permitido: $file_type");
        }
        
        // Validar MIME real del archivo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_real = finfo_file($finfo, $file_tmp);
        finfo_close($finfo);
        
        if (!in_array($mime_real, $allowed_mime)) {
            throw new Exception("Contenido del archivo no es una imagen válida");
        }
        
        // Validar tamaño
        $size = filesize($file_tmp);
        if ($size > $max_size) {
            throw new Exception("Archivo demasiado grande. Máximo: 5MB");
        }
        
        return true;
    }
}
?>
```

### Solución 2.2: Aplicar validaciones en ProductoController.php

**Archivo:** `controlador/ProductoController.php` (Líneas 11-20)

**ANTES:**
```php
if($_POST['funcion']=='crear'){
    $nombre = $_POST['nombre'];
    $concentracion = $_POST['concentracion'];
    $adicional = $_POST['adicional'];
    $precio = $_POST['precio'];
    $laboratorio = $_POST['laboratorio'];
    $tipo = $_POST['tipo'];
    $presentacion = $_POST['presentacion'];
    $avatar='prod_default.png';
    $producto->crear($nombre,$concentracion,$adicional,$precio,$laboratorio,$tipo,$presentacion,$avatar);
}
```

**DESPUÉS:**
```php
if($_POST['funcion']=='crear'){
    try {
        include_once '../modelo/Validador.php';
        
        $nombre = Validador::validar_texto($_POST['nombre'] ?? '', 2, 255);
        $concentracion = Validador::validar_texto($_POST['concentracion'] ?? '', 0, 100);
        $adicional = Validador::validar_texto($_POST['adicional'] ?? '', 0, 100);
        $precio = Validador::validar_precio($_POST['precio'] ?? 0);
        $laboratorio = Validador::validar_id($_POST['laboratorio'] ?? 0);
        $tipo = Validador::validar_id($_POST['tipo'] ?? 0);
        $presentacion = Validador::validar_id($_POST['presentacion'] ?? 0);
        $avatar = 'prod_default.png';
        
        $producto->crear($nombre, $concentracion, $adicional, $precio, $laboratorio, $tipo, $presentacion, $avatar);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => true, 'mensaje' => $e->getMessage()]);
        exit;
    }
}
```

---

## 3. CORRECCIONES DE CÁLCULOS FINANCIEROS

### Solución 3.1: Corregir cálculo de IGV

**Archivo:** `js/Carrito.js` (Línea 282-296)

**ANTES (INCORRECTO):**
```javascript
function calcularTotal() {
    let productos, subtotal, con_igv, total_sin_descuento, pago, vuelto, descuento;
    let total = 0, igv = 0.18;
    productos = RecuperarLS();
    productos.forEach((producto) => {
        let subtotal_producto = Number(producto.precio * producto.cantidad);
        total = total + subtotal_producto;
    });
    pago = $("#pago").val();
    descuento = $("#descuento").val();

    total_sin_descuento = total.toFixed(2);
    con_igv = parseFloat(total * igv).toFixed(2);  // 🔴 INCORRECTO
    subtotal = parseFloat(total - con_igv).toFixed(2);  // 🔴 INCORRECTO

    total = total - descuento;
    vuelto = pago - total;
    $("#subtotal").html(formatearNumero(subtotal));
    $("#con_igv").html(formatearNumero(con_igv));
    $("#total_sin_descuento").html(formatearNumero(total_sin_descuento));
    $("#total").html(formatearNumero(total));
    $("#vuelto").html(formatearNumero(vuelto));
}
```

**DESPUÉS (CORRECTO):**
```javascript
function calcularTotal() {
    let productos, subtotal, con_igv, total_sin_descuento, pago, vuelto, descuento;
    let total_bruto = 0;
    const IGV_RATE = 0.18;  // 18%
    
    productos = RecuperarLS();
    productos.forEach((producto) => {
        let subtotal_producto = Number(producto.precio * producto.cantidad);
        total_bruto = total_bruto + subtotal_producto;
    });
    
    // Validar descuento
    descuento = parseFloat($("#descuento").val()) || 0;
    if (descuento < 0) descuento = 0;
    if (descuento > total_bruto) descuento = total_bruto;
    
    // Aplicar descuento primero
    let total_con_descuento = total_bruto - descuento;
    
    // Calcular IGV: IGV = (Total / 1.18) * 0.18 O IGV = Total * 0.18 / 1.18
    // En realidad, si el total ya INCLUYE IGV:
    // Base = Total / 1.18
    // IGV = Total - Base
    let base_sin_igv = total_con_descuento / (1 + IGV_RATE);  // Más preciso
    con_igv = total_con_descuento - base_sin_igv;
    
    // Validar pago
    pago = parseFloat($("#pago").val()) || 0;
    if (pago < 0) pago = 0;
    
    // Calcular vuelto
    vuelto = pago - total_con_descuento;
    
    // Mostrar resultados con 2 decimales de precisión
    subtotal = base_sin_igv.toFixed(2);
    con_igv = con_igv.toFixed(2);
    total_sin_descuento = total_bruto.toFixed(2);
    let total_final = total_con_descuento.toFixed(2);
    
    // Validar que los números sean válidos
    if (isNaN(subtotal) || isNaN(con_igv) || isNaN(vuelto)) {
        console.error('Cálculo inválido');
        return;
    }
    
    $("#subtotal").html(formatearNumero(subtotal));
    $("#con_igv").html(formatearNumero(con_igv));
    $("#total_sin_descuento").html(formatearNumero(total_sin_descuento));
    $("#total").html(formatearNumero(total_final));
    $("#vuelto").html(formatearNumero(vuelto));
}
```

---

## 4. CORRECCIONES DE SEGURIDAD

### Solución 4.1: Mover credenciales a .env

**Archivo NUEVO:** `.env.local` (EN .gitignore)

```
DB_HOST=localhost
DB_PORT=3306
DB_NAME=farmaciasistema
DB_USER=farmacia_user
DB_PASS=SuperSecurePassword123!
DB_CHARSET=utf8mb4
```

**Archivo:** `.gitignore` (Agregar)

```
.env.local
.env.*.local
```

**Archivo:** `modelo/Conexion.php` (CORREGIDO)

```php
<?php
class Conexion {
    public $pdo = null;
    
    function __construct() {
        try {
            // Cargar variables de entorno
            $env_file = __DIR__ . '/../.env.local';
            
            if (!file_exists($env_file)) {
                throw new Exception(".env.local no encontrado");
            }
            
            $env = parse_ini_file($env_file);
            
            if (!$env) {
                throw new Exception("Error al leer .env.local");
            }
            
            $servidor = $env['DB_HOST'] ?? 'localhost';
            $db = $env['DB_NAME'] ?? 'farmaciasistema';
            $puerto = $env['DB_PORT'] ?? 3306;
            $charset = $env['DB_CHARSET'] ?? 'utf8mb4';
            $usuario = $env['DB_USER'] ?? null;
            $contrasena = $env['DB_PASS'] ?? null;
            
            if (!$usuario) {
                throw new Exception("DB_USER no configurado");
            }
            
            $atributos = [
                PDO::ATTR_CASE => PDO::CASE_LOWER,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_ORACLE_NULLS => PDO::NULL_EMPTY_STRING,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ];
            
            $dsn = "mysql:dbname=$db;host=$servidor;port=$puerto;charset=$charset";
            $this->pdo = new PDO($dsn, $usuario, $contrasena, $atributos);
            
        } catch (PDOException $e) {
            error_log("Error de conexión BD: " . $e->getMessage());
            http_response_code(500);
            die(json_encode(['error' => 'Error de conexión. Contacte al administrador.']));
        } catch (Exception $e) {
            error_log("Error en Conexion: " . $e->getMessage());
            http_response_code(500);
            die(json_encode(['error' => 'Error de configuración. Contacte al administrador.']));
        }
    }
}
?>
```

### Solución 4.2: Agregar validación de sesión

**Archivo NUEVO:** `modelo/SeguridadHelper.php`

```php
<?php
class SeguridadHelper {
    
    /**
     * Valida que el usuario esté autenticado
     * Si no, termina la ejecución con error 401
     */
    public static function validar_sesion() {
        session_start();
        
        if (!isset($_SESSION['usuario'])) {
            http_response_code(401);
            echo json_encode([
                'error' => true,
                'mensaje' => 'No autorizado'
            ]);
            exit;
        }
        
        return $_SESSION['usuario'];
    }
    
    /**
     * Valida que el usuario tenga un tipo específico
     */
    public static function validar_tipo_usuario($tipo_requerido) {
        $usuario = self::validar_sesion();
        $tipo = $_SESSION['us_tipo'] ?? null;
        
        if ($tipo != $tipo_requerido) {
            http_response_code(403);
            echo json_encode([
                'error' => true,
                'mensaje' => 'Permiso denegado'
            ]);
            exit;
        }
        
        return $usuario;
    }
    
    /**
     * Establece headers de seguridad
     */
    public static function establecer_headers_seguridad() {
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\'');
    }
    
    /**
     * Genera token CSRF
     */
    public static function generar_token_csrf() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Valida token CSRF
     */
    public static function validar_token_csrf($token) {
        if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            throw new Exception('Token CSRF inválido');
        }
    }
}
?>
```

### Solución 4.3: Template para controladores seguros

**Archivo:** `controlador/PlantillaSegura.php`

```php
<?php
/**
 * Plantilla para controladores seguros
 * Usar como base para todos los controladores
 */

include_once '../modelo/SeguridadHelper.php';
include_once '../modelo/Validador.php';

// Establecer headers de seguridad
SeguridadHelper::establecer_headers_seguridad();

// Validar sesión
$usuario = SeguridadHelper::validar_sesion();

try {
    // Validar que sea POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    // Validar función
    $funcion = $_POST['funcion'] ?? null;
    if (!$funcion) {
        throw new Exception('Función no especificada');
    }
    
    // Ejecutar función
    if ($funcion == 'crear') {
        // ... código de crear
    } else if ($funcion == 'editar') {
        // ... código de editar
    } else {
        throw new Exception('Función no válida: ' . $funcion);
    }
    
} catch (Exception $e) {
    error_log("Error en controlador: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'error' => true,
        'mensaje' => $e->getMessage()
    ]);
}
?>
```

---

## 5. CORRECCIONES DE TRANSACCIONES

### Solución 5.1: Transacción completa con rollback

**Archivo:** `controlador/CompraController.php` (Líneas 30-117)

```php
if($_POST['funcion']=='registrar_compra'){
    try {
        $total = Validador::validar_precio($_POST['total'] ?? 0);
        $cliente = Validador::validar_id($_POST['cliente'] ?? 0);
        $productos = json_decode($_POST['json'] ?? '[]');
        $tipo_pago = Validador::validar_tipo_pago($_POST['tipo_pago'] ?? '');
        $pago = floatval($_POST['pago'] ?? 0);
        
        if (empty($productos)) {
            throw new Exception("No hay productos para registrar");
        }
        
        date_default_timezone_set('America/Bogota');
        $fecha = date('Y-m-d H:i:s');
        
        // Obtener caja abierta
        if (!$id_caja) {
            throw new Exception("No hay caja abierta");
        }
        
        $db = new Conexion();
        $conexion = $db->pdo;
        
        // INICIAR TRANSACCIÓN
        $conexion->beginTransaction();
        
        // Crear venta principal
        $venta->Crear($cliente, $total, $fecha, $vendedor, $tipo_pago, $pago, $id_caja);
        $venta->ultima_venta();
        
        $id_venta = null;
        foreach ($venta->objetos as $objeto) {
            $id_venta = $objeto->ultima_venta;
        }
        
        if (!$id_venta) {
            throw new Exception("Error al crear la venta");
        }
        
        // Si es crédito con abono inicial
        if (strcmp($tipo_pago, "Credito") == 0 && $pago > 0) {
            $venta->Crear($cliente, $pago, $fecha, $vendedor, "Deposito_" . $id_venta, $pago, $id_caja);
        }
        
        // Procesar cada producto
        foreach ($productos as $prod) {
            $prod_id = Validador::validar_id($prod->id);
            $cantidad = Validador::validar_cantidad($prod->cantidad);
            $precio = Validador::validar_precio($prod->precio);
            
            // [Código mejorado de procesamiento de lotes aquí...]
            // (Ver Solución 1.1)
            
            $subtotal = $cantidad * $precio;
            
            $sql = "INSERT INTO venta_producto 
                    (precio, cantidad, subtotal, producto_id_producto, venta_id_venta) 
                    VALUES (:precio, :cantidad, :subtotal, :prod_id, :venta_id)";
            $query = $conexion->prepare($sql);
            $query->execute([
                ':precio' => $precio,
                ':cantidad' => $cantidad,
                ':subtotal' => $subtotal,
                ':prod_id' => $prod_id,
                ':venta_id' => $id_venta
            ]);
        }
        
        // COMMIT DE TRANSACCIÓN
        $conexion->commit();
        
        http_response_code(200);
        echo json_encode([
            'error' => false,
            'mensaje' => 'Venta registrada exitosamente',
            'id_venta' => $id_venta
        ]);
        
    } catch (Exception $error) {
        // ROLLBACK
        if (isset($conexion) && $conexion->inTransaction()) {
            $conexion->rollBack();
        }
        
        // Registrar error
        error_log("Error en registrar_compra: " . $error->getMessage());
        
        // Si se creó la venta, eliminarla
        if (isset($id_venta)) {
            $venta->borrar($id_venta);
        }
        
        http_response_code(400);
        echo json_encode([
            'error' => true,
            'mensaje' => 'Error al procesar la venta: ' . $error->getMessage(),
            'tipo' => 'error_transaccion'
        ]);
    }
}
```

---

## 6. CORRECCIONES DE PERMISOS

### Solución 6.1: Mejorar lógica de borrado de venta

**Archivo:** `controlador/DetalleVentaController.php` (Líneas 1-47)

```php
<?php
include_once '../modelo/VentaProducto.php';
include_once '../modelo/DetalleVenta.php';
include_once '../modelo/Venta.php';
include_once '../modelo/Lote.php';
include_once '../modelo/SeguridadHelper.php';
include_once '../modelo/Validador.php';

// Establecer headers de seguridad
SeguridadHelper::establecer_headers_seguridad();

// Validar sesión
$id_usuario = SeguridadHelper::validar_sesion();

$lote = new Lote();
$venta = new Venta();
$detalle_venta = new DetalleVenta();
$venta_producto = new VentaProducto();

session_start();
$tipo_usuario = $_SESSION['us_tipo'];

try {
    if ($_POST['funcion'] == 'borrar_venta') {
        $id_venta = Validador::validar_id($_POST['id'] ?? 0);
        
        // Obtener información de la venta
        $venta->buscar_por_id($id_venta);
        if (empty($venta->objetos)) {
            throw new Exception("Venta no encontrada");
        }
        
        $venta_obj = $venta->objetos[0];
        $vendedor_original = $venta_obj->vendedor;
        
        // Validar permisos
        $puede_eliminar = false;
        
        // Admin (tipo 3) puede eliminar cualquier venta
        if ($tipo_usuario == 3) {
            $puede_eliminar = true;
        }
        
        // Vendedor (tipo 2) solo puede eliminar sus propias ventas
        else if ($tipo_usuario == 2 && $vendedor_original == $id_usuario) {
            $puede_eliminar = true;
        }
        
        // Manager (tipo 1) puede eliminar ventas de vendedores
        else if ($tipo_usuario == 1) {
            $venta->recuperar_vendedor($id_venta);
            if (!empty($venta->objetos)) {
                $vendedor_tipo = $venta->objetos[0]->us_tipo;
                if ($vendedor_tipo == 2) {  // Solo si vendedor es tipo 2
                    $puede_eliminar = true;
                }
            }
        }
        
        if (!$puede_eliminar) {
            throw new Exception("No tiene permiso para eliminar esta venta");
        }
        
        // Proceder con eliminación
        $db = new Conexion();
        $conexion = $db->pdo;
        $conexion->beginTransaction();
        
        try {
            // Recuperar detalles de venta
            $detalle_venta->recuperar($id_venta);
            foreach ($detalle_venta->objetos as $det) {
                // Devolver stock al lote
                $lote->devolver(
                    $det->id__det_lote,
                    $det->det_cantidad,
                    $det->det_vencimiento,
                    $det->id__det_prod,
                    $det->lote_id_prov
                );
                // Eliminar detalle
                $detalle_venta->borrar($det->id_detalle);
            }
            
            // Eliminar venta_producto
            $venta_producto->borrar($id_venta);
            
            // Marcar venta como anulada (mejor que DELETE)
            $sql = "UPDATE venta SET estado='Anulada', usuario_anulo=:usuario, fecha_anulacion=NOW() 
                    WHERE id_venta=:id";
            $query = $conexion->prepare($sql);
            $query->execute([
                ':usuario' => $id_usuario,
                ':id' => $id_venta
            ]);
            
            $conexion->commit();
            
            echo json_encode([
                'error' => false,
                'mensaje' => 'Venta eliminada exitosamente'
            ]);
            
        } catch (Exception $e) {
            $conexion->rollBack();
            throw $e;
        }
        
    } else {
        throw new Exception("Función no válida");
    }
    
} catch (Exception $e) {
    error_log("Error en DetalleVentaController: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'error' => true,
        'mensaje' => $e->getMessage()
    ]);
}
?>
```

---

## 7. CORRECCIONES DE FRONTEND

### Solución 7.1: Validación de cantidad en carrito

**Archivo:** `js/Carrito.js` (Modificar la parte de actualización)

```javascript
$(document).on("click", "#actualizar", (e) => {
    let productos, precios;
    precios = document.querySelectorAll(".precio");
    productos = RecuperarLS();
    
    productos.forEach(function (producto, indice) {
        let input_element = document.querySelectorAll(".cantidad_producto")[indice];
        
        // Validar cantidad
        let cantidad = parseInt(input_element.value) || 0;
        if (cantidad < 1) cantidad = 1;
        if (cantidad > 10000) cantidad = 10000;
        
        // Actualizar en el DOM
        input_element.value = cantidad;
        
        // Obtener precio desde el servidor (nunca del DOM para datos críticos)
        // Por ahora, validar que sea número válido
        let precio_texto = precios[indice].textContent.replace(/\./g, '');
        let precio = parseFloat(precio_texto);
        
        if (isNaN(precio) || precio < 0) {
            console.error("Precio inválido para producto " + indice);
            return;
        }
        
        producto.cantidad = cantidad;
        producto.precio = precio;
    });
    
    localStorage.setItem("productos", JSON.stringify(productos));
    calcularTotal();
});
```

### Solución 7.2: Validar descuento en carrito

**Archivo:** `js/Carrito.js` (Función calcularTotal mejorada)

```javascript
function calcularTotal() {
    // ... [código anterior de cálculo]
    
    // Validar y sanitizar descuento
    let descuento = parseFloat($("#descuento").val()) || 0;
    descuento = Math.max(0, descuento);  // No permitir negativos
    descuento = Math.min(descuento, total_bruto * 0.5);  // Máximo 50% de descuento
    
    // Validar y sanitizar pago
    let pago = parseFloat($("#pago").val()) || 0;
    pago = Math.max(0, pago);  // No permitir negativos
    
    // Aplicar descuento
    let total_con_descuento = Math.max(0, total_bruto - descuento);
    
    // Calcular IGV correctamente
    let base_sin_igv = total_con_descuento / 1.18;
    let con_igv = total_con_descuento - base_sin_igv;
    
    // Calcular vuelto
    let vuelto = pago - total_con_descuento;
    
    // Mostrar con validación
    if (!isNaN(base_sin_igv) && !isNaN(con_igv)) {
        $("#subtotal").html(formatearNumero(base_sin_igv.toFixed(2)));
        $("#con_igv").html(formatearNumero(con_igv.toFixed(2)));
        $("#total_sin_descuento").html(formatearNumero(total_bruto.toFixed(2)));
        $("#total").html(formatearNumero(total_con_descuento.toFixed(2)));
        $("#vuelto").html(formatearNumero(Math.max(0, vuelto).toFixed(2)));
    }
}
```

---

## ORDEN DE IMPLEMENTACIÓN

### Fase 1 - CRÍTICO (1-2 días):
1. ✅ Crear Validador.php
2. ✅ Crear SeguridadHelper.php
3. ✅ Mover credenciales a .env
4. ✅ Agregar validación de sesión a TODOS los controladores
5. ✅ Reemplazar SQL injection en CompraController.php

### Fase 2 - ALTO (2-3 días):
6. ✅ Aplicar validaciones en ProductoController.php
7. ✅ Corregir cálculo de IGV en Carrito.js
8. ✅ Mejorar lógica de borrado en DetalleVentaController.php
9. ✅ Implementar transacciones completas

### Fase 3 - MEDIO (3-5 días):
10. ✅ Agregar índices en BD
11. ✅ Crear sistema de auditoría
12. ✅ Implementar rate limiting
13. ✅ Agregar tokens CSRF

