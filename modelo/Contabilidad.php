<?php
include_once 'Conexion.php';

class Contabilidad {
    var $objetos;
    private $acceso;

    public function __construct() {
        $db = new Conexion();
        $this->acceso = $db->pdo;
    }

    // Inicializar contabilidad con saldo inicial
    function inicializar_contabilidad($saldo_inicial, $id_usuario) {
        date_default_timezone_set('America/Bogota');
        
        try {
            // Actualizar saldo y marcar como confirmado en la tabla contabilidad
            $sql = "UPDATE contabilidad SET 
                    saldo_actual = :saldo_inicial, 
                    saldo_confirmado = TRUE,
                    fecha_actualizacion = NOW()
                    WHERE id_contabilidad = 1";
            $query = $this->acceso->prepare($sql);
            $query->execute(array(':saldo_inicial' => $saldo_inicial));
            
            // Si el saldo inicial es mayor a 0, registrar como movimiento inicial
            if ($saldo_inicial > 0) {
                $sql = "INSERT INTO movimientos_contabilidad 
                        (fecha_movimiento, tipo, concepto, monto, categoria, descripcion, saldo, id_usuario) 
                        VALUES (NOW(), 'Ingreso', 'Saldo Inicial del Negocio', :monto, 'Saldo Inicial', 
                                'Inicialización del sistema de contabilidad con saldo existente', :saldo, :id_usuario)";
                $query = $this->acceso->prepare($sql);
                $query->execute(array(
                    ':monto' => $saldo_inicial,
                    ':saldo' => $saldo_inicial,
                    ':id_usuario' => $id_usuario
                ));
            }
            
            return 'success';
        } catch (Exception $e) {
            return 'error';
        }
    }

    // Obtener saldo actual
    function obtener_saldo_actual() {
        $sql = "SELECT * FROM contabilidad WHERE id_contabilidad = 1";
        $query = $this->acceso->prepare($sql);
        $query->execute();
        $this->objetos = $query->fetchAll();
        return $this->objetos;
    }

    // Registrar ingreso
    function registrar_ingreso($concepto, $monto, $categoria, $descripcion, $id_usuario, $referencia = null) {
        date_default_timezone_set('America/Bogota');
        
        // Obtener saldo actual
        $saldo_data = $this->obtener_saldo_actual();
        $saldo_anterior = !empty($saldo_data) ? $saldo_data[0]->saldo_actual : 0;
        $saldo_nuevo = $saldo_anterior + $monto;

        // Registrar movimiento
        $sql = "INSERT INTO movimientos_contabilidad 
                (fecha_movimiento, tipo, concepto, monto, categoria, descripcion, saldo_anterior, saldo, id_usuario, referencia) 
                VALUES (NOW(), 'Ingreso', :concepto, :monto, :categoria, :descripcion, :saldo_anterior, :saldo_nuevo, :id_usuario, :referencia)";
        $query = $this->acceso->prepare($sql);
        $resultado = $query->execute(array(
            ':concepto' => $concepto,
            ':monto' => $monto,
            ':categoria' => $categoria,
            ':descripcion' => $descripcion,
            ':saldo_anterior' => $saldo_anterior,
            ':saldo_nuevo' => $saldo_nuevo,
            ':id_usuario' => $id_usuario,
            ':referencia' => $referencia
        ));

        if ($resultado) {
            // Actualizar o crear registro de saldo en contabilidad
            $sql = "INSERT INTO contabilidad (id_contabilidad, saldo_actual, fecha_actualizacion) 
                    VALUES (1, :saldo_nuevo, NOW())
                    ON DUPLICATE KEY UPDATE 
                    saldo_actual = :saldo_nuevo, 
                    fecha_actualizacion = NOW()";
            $query = $this->acceso->prepare($sql);
            $query->execute(array(':saldo_nuevo' => $saldo_nuevo));
            
            return 'success';
        }
        
        return 'error';
    }

    // Registrar egreso
    function registrar_egreso($concepto, $monto, $categoria, $descripcion, $id_usuario, $referencia = null) {
        date_default_timezone_set('America/Bogota');
        
        // Obtener saldo actual
        $saldo_data = $this->obtener_saldo_actual();
        $saldo_anterior = !empty($saldo_data) ? $saldo_data[0]->saldo_actual : 0;
        $saldo_nuevo = $saldo_anterior - $monto;

        // Registrar movimiento
        $sql = "INSERT INTO movimientos_contabilidad 
                (fecha_movimiento, tipo, concepto, monto, categoria, descripcion, saldo_anterior, saldo, id_usuario, referencia) 
                VALUES (NOW(), 'Egreso', :concepto, :monto, :categoria, :descripcion, :saldo_anterior, :saldo_nuevo, :id_usuario, :referencia)";
        $query = $this->acceso->prepare($sql);
        $resultado = $query->execute(array(
            ':concepto' => $concepto,
            ':monto' => $monto,
            ':categoria' => $categoria,
            ':descripcion' => $descripcion,
            ':saldo_anterior' => $saldo_anterior,
            ':saldo_nuevo' => $saldo_nuevo,
            ':id_usuario' => $id_usuario,
            ':referencia' => $referencia
        ));

        if ($resultado) {
            // Actualizar o crear registro de saldo en contabilidad
            $sql = "INSERT INTO contabilidad (id_contabilidad, saldo_actual, fecha_actualizacion) 
                    VALUES (1, :saldo_nuevo, NOW())
                    ON DUPLICATE KEY UPDATE 
                    saldo_actual = :saldo_nuevo, 
                    fecha_actualizacion = NOW()";
            $query = $this->acceso->prepare($sql);
            $query->execute(array(':saldo_nuevo' => $saldo_nuevo));
            
            return 'success';
        }
        
        return 'error';
    }

    // Listar movimientos (con filtros opcionales)
    function listar_movimientos($fecha_inicio = null, $fecha_fin = null, $tipo = null, $limit = 100) {
        $sql = "SELECT 
                m.id_movimiento,
                m.fecha_movimiento,
                m.tipo,
                m.concepto,
                m.monto,
                m.categoria,
                m.saldo,
                m.descripcion,
                m.referencia,
                CONCAT(u.nombre_us, ' ', u.apellidos_us) as usuario
                FROM movimientos_contabilidad m
                JOIN usuario u ON m.id_usuario = u.id_usuario
                WHERE 1=1";
        
        $params = array();
        
        if ($fecha_inicio) {
            $sql .= " AND DATE(m.fecha_movimiento) >= :fecha_inicio";
            $params[':fecha_inicio'] = $fecha_inicio;
        }
        
        if ($fecha_fin) {
            $sql .= " AND DATE(m.fecha_movimiento) <= :fecha_fin";
            $params[':fecha_fin'] = $fecha_fin;
        }
        
        if ($tipo) {
            $sql .= " AND m.tipo = :tipo";
            $params[':tipo'] = $tipo;
        }
        
        $sql .= " ORDER BY m.id_movimiento DESC LIMIT :limit";
        
        $query = $this->acceso->prepare($sql);
        $query->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        
        foreach ($params as $key => $value) {
            $query->bindValue($key, $value);
        }
        
        $query->execute();
        $this->objetos = $query->fetchAll();
        return $this->objetos;
    }

    // Obtener resumen financiero
    function obtener_resumen() {
        $sql = "SELECT 
                COALESCE(SUM(CASE WHEN tipo = 'Ingreso' THEN monto ELSE 0 END), 0) as total_ingresos,
                COALESCE(SUM(CASE WHEN tipo = 'Egreso' THEN monto ELSE 0 END), 0) as total_egresos,
                COUNT(*) as total_movimientos,
                (SELECT saldo_actual FROM contabilidad WHERE id_contabilidad = 1) as saldo_actual
                FROM movimientos_contabilidad";
        $query = $this->acceso->prepare($sql);
        $query->execute();
        $resultado = $query->fetch(PDO::FETCH_ASSOC);
        return $resultado;
    }

    // Obtener detalle de un movimiento específico
    function obtener_detalle_movimiento($id_movimiento) {
        $sql = "SELECT 
                m.*,
                CONCAT(u.nombre_us, ' ', u.apellidos_us) as usuario
                FROM movimientos_contabilidad m
                JOIN usuario u ON m.id_usuario = u.id_usuario
                WHERE m.id_movimiento = :id_movimiento";
        $query = $this->acceso->prepare($sql);
        $query->execute(array(':id_movimiento' => $id_movimiento));
        $this->objetos = $query->fetchAll();
        return $this->objetos;
    }

    // Obtener estadísticas de contabilidad
    function obtener_estadisticas() {
        $sql = "SELECT 
                COUNT(CASE WHEN tipo = 'Ingreso' THEN 1 END) as total_ingresos_count,
                COUNT(CASE WHEN tipo = 'Egreso' THEN 1 END) as total_egresos_count,
                COALESCE(SUM(CASE WHEN tipo = 'Ingreso' THEN monto ELSE 0 END), 0) as total_ingresos,
                COALESCE(SUM(CASE WHEN tipo = 'Egreso' THEN monto ELSE 0 END), 0) as total_egresos
                FROM movimientos_contabilidad
                WHERE MONTH(fecha_movimiento) = MONTH(CURDATE()) AND YEAR(fecha_movimiento) = YEAR(CURDATE())";
        $query = $this->acceso->prepare($sql);
        $query->execute();
        $this->objetos = $query->fetchAll();
        return $this->objetos;
    }

    // ==================== FUNCIONES PARA REPORTES PDF ====================
    
    // Estado de Resultados - Ingresos vs Egresos por período
    function obtener_estado_resultados($fecha_inicio, $fecha_fin) {
        $sql = "SELECT 
                tipo,
                categoria,
                COALESCE(SUM(monto), 0) as total,
                COUNT(*) as cantidad
                FROM movimientos_contabilidad
                WHERE DATE(fecha_movimiento) BETWEEN :fecha_inicio AND :fecha_fin
                GROUP BY tipo, categoria
                ORDER BY tipo DESC, total DESC";
        $query = $this->acceso->prepare($sql);
        $query->execute(array(
            ':fecha_inicio' => $fecha_inicio,
            ':fecha_fin' => $fecha_fin
        ));
        return $query->fetchAll();
    }

    // Resumen por categorías
    function obtener_resumen_categorias($fecha_inicio, $fecha_fin) {
        $sql = "SELECT 
                tipo,
                categoria,
                COUNT(*) as cantidad,
                COALESCE(SUM(monto), 0) as total,
                ROUND(AVG(monto), 2) as promedio
                FROM movimientos_contabilidad
                WHERE DATE(fecha_movimiento) BETWEEN :fecha_inicio AND :fecha_fin
                GROUP BY tipo, categoria
                ORDER BY tipo DESC, total DESC";
        $query = $this->acceso->prepare($sql);
        $query->execute(array(
            ':fecha_inicio' => $fecha_inicio,
            ':fecha_fin' => $fecha_fin
        ));
        return $query->fetchAll();
    }

    // Obtener movimientos para flujo de caja y libro diario
    function obtener_movimientos_reporte($fecha_inicio, $fecha_fin) {
        $sql = "SELECT 
                m.id_movimiento,
                m.fecha_movimiento,
                m.tipo,
                m.concepto,
                m.monto,
                m.categoria,
                m.descripcion,
                m.saldo_anterior,
                m.saldo,
                m.referencia,
                CONCAT(u.nombre_us, ' ', u.apellidos_us) as usuario
                FROM movimientos_contabilidad m
                JOIN usuario u ON m.id_usuario = u.id_usuario
                WHERE DATE(m.fecha_movimiento) BETWEEN :fecha_inicio AND :fecha_fin
                ORDER BY m.fecha_movimiento ASC, m.id_movimiento ASC";
        $query = $this->acceso->prepare($sql);
        $query->execute(array(
            ':fecha_inicio' => $fecha_inicio,
            ':fecha_fin' => $fecha_fin
        ));
        return $query->fetchAll();
    }

    // Obtener datos para arqueo de caja
    function obtener_arqueo_caja($fecha_inicio, $fecha_fin) {
        $sql = "SELECT 
                c.id_caja,
                c.fecha_apertura,
                c.fecha_cierre,
                c.monto_inicial,
                c.monto_final,
                c.total_ventas_contado,
                c.total_ventas_credito,
                c.total_depositos_credito,
                c.efectivo_esperado,
                c.efectivo_real,
                c.diferencia,
                c.estado,
                c.observaciones,
                CONCAT(u1.nombre_us, ' ', u1.apellidos_us) as usuario_apertura,
                CONCAT(u2.nombre_us, ' ', u2.apellidos_us) as usuario_cierre
                FROM caja c
                LEFT JOIN usuario u1 ON c.id_usuario_apertura = u1.id_usuario
                LEFT JOIN usuario u2 ON c.id_usuario_cierre = u2.id_usuario
                WHERE DATE(c.fecha_apertura) BETWEEN :fecha_inicio AND :fecha_fin
                ORDER BY c.fecha_apertura DESC";
        $query = $this->acceso->prepare($sql);
        $query->execute(array(
            ':fecha_inicio' => $fecha_inicio,
            ':fecha_fin' => $fecha_fin
        ));
        return $query->fetchAll();
    }

    // Obtener totales generales para reportes
    function obtener_totales_periodo($fecha_inicio, $fecha_fin) {
        $sql = "SELECT 
                COALESCE(SUM(CASE WHEN tipo = 'Ingreso' THEN monto ELSE 0 END), 0) as total_ingresos,
                COALESCE(SUM(CASE WHEN tipo = 'Egreso' THEN monto ELSE 0 END), 0) as total_egresos,
                COUNT(CASE WHEN tipo = 'Ingreso' THEN 1 END) as count_ingresos,
                COUNT(CASE WHEN tipo = 'Egreso' THEN 1 END) as count_egresos,
                (SELECT saldo_actual FROM contabilidad WHERE id_contabilidad = 1) as saldo_actual
                FROM movimientos_contabilidad
                WHERE DATE(fecha_movimiento) BETWEEN :fecha_inicio AND :fecha_fin";
        $query = $this->acceso->prepare($sql);
        $query->execute(array(
            ':fecha_inicio' => $fecha_inicio,
            ':fecha_fin' => $fecha_fin
        ));
        return $query->fetch(PDO::FETCH_ASSOC);
    }
}
?>
