<?php
include_once 'Conexion.php';

class Caja {
    var $objetos;
    private $acceso;

    public function __construct() {
        $db = new Conexion();
        $this->acceso = $db->pdo;
    }

    // Verificar si hay una caja abierta
    function verificar_caja_abierta() {
        $sql = "SELECT * FROM caja WHERE estado = 'abierta' ORDER BY fecha_apertura DESC LIMIT 1";
        $query = $this->acceso->prepare($sql);
        $query->execute();
        $this->objetos = $query->fetchAll();
        return $this->objetos;
    }

    // Abrir caja
    function abrir_caja($monto_inicial, $id_usuario) {
        // Verificar que no haya caja abierta
        $caja_abierta = $this->verificar_caja_abierta();
        if (!empty($caja_abierta)) {
            return 'caja_ya_abierta';
        }

        date_default_timezone_set('America/Bogota');
        $fecha_apertura = date('Y-m-d H:i:s');

        $sql = "INSERT INTO caja (fecha_apertura, monto_inicial, estado, id_usuario_apertura) 
                VALUES (:fecha_apertura, :monto_inicial, 'abierta', :id_usuario)";
        $query = $this->acceso->prepare($sql);
        $resultado = $query->execute(array(
            ':fecha_apertura' => $fecha_apertura,
            ':monto_inicial' => $monto_inicial,
            ':id_usuario' => $id_usuario
        ));

        if ($resultado) {
            return 'caja_abierta';
        }
        return 'error';
    }

    // Obtener información de la caja abierta actual
    function obtener_caja_actual() {
        $sql = "SELECT c.*, 
                u1.nombre_us as nombre_apertura, u1.apellidos_us as apellido_apertura,
                COALESCE(SUM(CASE WHEN v.tipo_pago = 'Contado' THEN v.total ELSE 0 END), 0) as total_ventas_contado,
                COALESCE(SUM(CASE WHEN v.tipo_pago = 'Credito' THEN v.total ELSE 0 END), 0) as total_ventas_credito,
                COALESCE(SUM(CASE WHEN v.tipo_pago LIKE 'Deposito_%' THEN v.depositado ELSE 0 END), 0) as total_depositos_credito,
                COUNT(CASE WHEN v.tipo_pago = 'Contado' THEN 1 END) as cantidad_ventas_contado,
                COUNT(CASE WHEN v.tipo_pago = 'Credito' THEN 1 END) as cantidad_ventas_credito
                FROM caja c
                LEFT JOIN usuario u1 ON c.id_usuario_apertura = u1.id_usuario
                LEFT JOIN venta v ON v.id_caja = c.id_caja
                WHERE c.estado = 'abierta'
                GROUP BY c.id_caja
                ORDER BY c.fecha_apertura DESC 
                LIMIT 1";
        $query = $this->acceso->prepare($sql);
        $query->execute();
        $this->objetos = $query->fetchAll();
        return $this->objetos;
    }

    // Cerrar caja
    function cerrar_caja($id_caja, $efectivo_real, $id_usuario, $observaciones = '') {
        date_default_timezone_set('America/Bogota');
        $fecha_cierre = date('Y-m-d H:i:s');

        // Obtener totales de la caja
        $sql = "SELECT 
                c.monto_inicial,
                COALESCE(SUM(CASE WHEN v.tipo_pago = 'Contado' THEN v.total ELSE 0 END), 0) as total_ventas_contado,
                COALESCE(SUM(CASE WHEN v.tipo_pago LIKE 'Deposito_%' THEN v.depositado ELSE 0 END), 0) as total_depositos_credito,
                COALESCE(SUM(CASE WHEN v.tipo_pago = 'Credito' THEN v.total ELSE 0 END), 0) as total_ventas_credito
                FROM caja c
                LEFT JOIN venta v ON v.id_caja = c.id_caja
                WHERE c.id_caja = :id_caja
                GROUP BY c.id_caja, c.monto_inicial";
        $query = $this->acceso->prepare($sql);
        $query->execute(array(':id_caja' => $id_caja));
        $datos = $query->fetch(PDO::FETCH_OBJ);

        $efectivo_esperado = $datos->monto_inicial + $datos->total_ventas_contado + $datos->total_depositos_credito;
        $diferencia = $efectivo_real - $efectivo_esperado;

        // Actualizar caja
        $sql = "UPDATE caja SET 
                fecha_cierre = :fecha_cierre,
                monto_final = :monto_final,
                total_ventas_contado = :total_ventas_contado,
                total_ventas_credito = :total_ventas_credito,
                total_depositos_credito = :total_depositos_credito,
                efectivo_esperado = :efectivo_esperado,
                efectivo_real = :efectivo_real,
                diferencia = :diferencia,
                estado = 'cerrada',
                id_usuario_cierre = :id_usuario,
                observaciones = :observaciones
                WHERE id_caja = :id_caja";
        
        $query = $this->acceso->prepare($sql);
        $result = $query->execute(array(
            ':fecha_cierre' => $fecha_cierre,
            ':monto_final' => $efectivo_real,
            ':total_ventas_contado' => $datos->total_ventas_contado,
            ':total_ventas_credito' => $datos->total_ventas_credito,
            ':total_depositos_credito' => $datos->total_depositos_credito,
            ':efectivo_esperado' => $efectivo_esperado,
            ':efectivo_real' => $efectivo_real,
            ':diferencia' => $diferencia,
            ':id_usuario' => $id_usuario,
            ':observaciones' => $observaciones,
            ':id_caja' => $id_caja
        ));

        if ($result) {
            // Calcular utilidad del día (lo que se suma a contabilidad)
            $utilidad = $efectivo_real - $datos->monto_inicial;
            return array('success' => true, 'utilidad' => $utilidad, 'id_caja' => $id_caja);
        }
        return array('success' => false);
    }

    // Listar todas las cajas (historial)
    function listar_cajas($limit = 50) {
        $sql = "SELECT c.*, 
                u1.nombre_us as nombre_apertura, u1.apellidos_us as apellido_apertura,
                u2.nombre_us as nombre_cierre, u2.apellidos_us as apellido_cierre
                FROM caja c
                LEFT JOIN usuario u1 ON c.id_usuario_apertura = u1.id_usuario
                LEFT JOIN usuario u2 ON c.id_usuario_cierre = u2.id_usuario
                ORDER BY c.fecha_apertura DESC
                LIMIT :limit";
        $query = $this->acceso->prepare($sql);
        $query->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $query->execute();
        $this->objetos = $query->fetchAll();
        return $this->objetos;
    }

    // Obtener detalles de una caja específica
    function obtener_caja_por_id($id_caja) {
        $sql = "SELECT c.*, 
                u1.nombre_us as nombre_apertura, u1.apellidos_us as apellido_apertura,
                u2.nombre_us as nombre_cierre, u2.apellidos_us as apellido_cierre
                FROM caja c
                LEFT JOIN usuario u1 ON c.id_usuario_apertura = u1.id_usuario
                LEFT JOIN usuario u2 ON c.id_usuario_cierre = u2.id_usuario
                WHERE c.id_caja = :id_caja";
        $query = $this->acceso->prepare($sql);
        $query->execute(array(':id_caja' => $id_caja));
        $this->objetos = $query->fetchAll();
        return $this->objetos;
    }
}
?>
