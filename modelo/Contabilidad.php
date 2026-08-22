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
    function registrar_ingreso($concepto, $monto, $categoria, $descripcion, $id_usuario, $referencia = null, $cuenta = 'caja') {
        date_default_timezone_set('America/Bogota');

        if ($cuenta === 'nequi') {
            return $this->registrar_ingreso_nequi($concepto, $monto, $categoria, $descripcion, $id_usuario, $referencia);
        }
        
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

    // Registrar ingreso Nequi (suma a saldo_nequi y tambien al saldo general)
    function registrar_ingreso_nequi($concepto, $monto, $categoria, $descripcion, $id_usuario, $referencia = null) {
        date_default_timezone_set('America/Bogota');

        // Obtener saldos actuales
        $saldo_data = $this->obtener_saldo_actual();
        $saldo_general_anterior = !empty($saldo_data) ? (float)$saldo_data[0]->saldo_actual : 0;
        $saldo_nequi_anterior = 0;
        if (!empty($saldo_data)) {
            $row = $saldo_data[0];
            $saldo_nequi_anterior = isset($row->saldo_nequi) ? (float)$row->saldo_nequi : 0;
        }

        $saldo_general_nuevo = $saldo_general_anterior + $monto;
        $saldo_nequi_nuevo = $saldo_nequi_anterior + $monto;

        // Registrar movimiento marcado como Nequi
        try {
            $sql = "INSERT INTO movimientos_contabilidad 
                    (fecha_movimiento, tipo, concepto, monto, categoria, descripcion, saldo_anterior, saldo, id_usuario, referencia, es_nequi) 
                    VALUES (NOW(), 'Ingreso', :concepto, :monto, :categoria, :descripcion, :saldo_anterior, :saldo, :id_usuario, :referencia, 1)";
            $query = $this->acceso->prepare($sql);
            $query->execute(array(
                ':concepto' => $concepto,
                ':monto' => $monto,
                ':categoria' => $categoria,
                ':descripcion' => $descripcion,
                ':saldo_anterior' => $saldo_general_anterior,
                ':saldo' => $saldo_general_nuevo,
                ':id_usuario' => $id_usuario,
                ':referencia' => $referencia
            ));
        } catch (Exception $e) {
            // Si la columna es_nequi no existe aún, insertar sin ella
            $sql = "INSERT INTO movimientos_contabilidad 
                    (fecha_movimiento, tipo, concepto, monto, categoria, descripcion, saldo_anterior, saldo, id_usuario, referencia) 
                    VALUES (NOW(), 'Ingreso', :concepto, :monto, :categoria, :descripcion, :saldo_anterior, :saldo, :id_usuario, :referencia)";
            $query = $this->acceso->prepare($sql);
            $query->execute(array(
                ':concepto' => $concepto,
                ':monto' => $monto,
                ':categoria' => $categoria,
                ':descripcion' => $descripcion,
                ':saldo_anterior' => $saldo_general_anterior,
                ':saldo' => $saldo_general_nuevo,
                ':id_usuario' => $id_usuario,
                ':referencia' => $referencia
            ));
        }

        // Actualizar saldos en tabla contabilidad
        try {
            $sql = "UPDATE contabilidad SET saldo_actual = :saldo_actual, saldo_nequi = :saldo_nequi, fecha_actualizacion = NOW() WHERE id_contabilidad = 1";
            $query = $this->acceso->prepare($sql);
            $query->execute(array(
                ':saldo_actual' => $saldo_general_nuevo,
                ':saldo_nequi' => $saldo_nequi_nuevo
            ));
        } catch (Exception $e) { /* columna aún no existe */ }

        return 'success';
    }

    // Obtener saldo Nequi actual
    function obtener_saldo_nequi() {
        try {
            $sql = "SELECT COALESCE(saldo_nequi, 0) as saldo_nequi FROM contabilidad WHERE id_contabilidad = 1";
            $query = $this->acceso->prepare($sql);
            $query->execute();
            $row = $query->fetch(PDO::FETCH_OBJ);
            return $row ? (float)$row->saldo_nequi : 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    // Acumular saldo Nequi (solo contador informativo, NO afecta saldo_actual de caja)
    function acumular_saldo_nequi($monto) {
        try {
            $sql = "UPDATE contabilidad SET saldo_nequi = COALESCE(saldo_nequi, 0) + :monto, fecha_actualizacion = NOW() WHERE id_contabilidad = 1";
            $query = $this->acceso->prepare($sql);
            $query->execute(array(':monto' => $monto));
        } catch (Exception $e) { /* columna aún no existe */ }
    }

    // Registrar egreso
    function registrar_egreso($concepto, $monto, $categoria, $descripcion, $id_usuario, $referencia = null, $cuenta = 'caja') {
        date_default_timezone_set('America/Bogota');

        if ($cuenta === 'nequi') {
            return $this->registrar_egreso_nequi($concepto, $monto, $categoria, $descripcion, $id_usuario, $referencia);
        }
        
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

    // Registrar egreso Nequi (resta al saldo_nequi y tambien al saldo general)
    function registrar_egreso_nequi($concepto, $monto, $categoria, $descripcion, $id_usuario, $referencia = null) {
        date_default_timezone_set('America/Bogota');

        $saldo_data = $this->obtener_saldo_actual();
        $saldo_general_anterior = !empty($saldo_data) ? (float)$saldo_data[0]->saldo_actual : 0;
        $saldo_nequi_anterior = 0;
        if (!empty($saldo_data)) {
            $row = $saldo_data[0];
            $saldo_nequi_anterior = isset($row->saldo_nequi) ? (float)$row->saldo_nequi : 0;
        }

        if ((float)$monto > $saldo_nequi_anterior) {
            return 'error_saldo_insuficiente';
        }

        $saldo_general_nuevo = $saldo_general_anterior - $monto;
        $saldo_nequi_nuevo = $saldo_nequi_anterior - $monto;

        try {
            $sql = "INSERT INTO movimientos_contabilidad 
                    (fecha_movimiento, tipo, concepto, monto, categoria, descripcion, saldo_anterior, saldo, id_usuario, referencia, es_nequi) 
                    VALUES (NOW(), 'Egreso', :concepto, :monto, :categoria, :descripcion, :saldo_anterior, :saldo, :id_usuario, :referencia, 1)";
            $query = $this->acceso->prepare($sql);
            $query->execute(array(
                ':concepto' => $concepto,
                ':monto' => $monto,
                ':categoria' => $categoria,
                ':descripcion' => $descripcion,
                ':saldo_anterior' => $saldo_general_anterior,
                ':saldo' => $saldo_general_nuevo,
                ':id_usuario' => $id_usuario,
                ':referencia' => $referencia
            ));
        } catch (Exception $e) {
            $sql = "INSERT INTO movimientos_contabilidad 
                    (fecha_movimiento, tipo, concepto, monto, categoria, descripcion, saldo_anterior, saldo, id_usuario, referencia) 
                    VALUES (NOW(), 'Egreso', :concepto, :monto, :categoria, :descripcion, :saldo_anterior, :saldo, :id_usuario, :referencia)";
            $query = $this->acceso->prepare($sql);
            $query->execute(array(
                ':concepto' => $concepto,
                ':monto' => $monto,
                ':categoria' => $categoria,
                ':descripcion' => $descripcion,
                ':saldo_anterior' => $saldo_general_anterior,
                ':saldo' => $saldo_general_nuevo,
                ':id_usuario' => $id_usuario,
                ':referencia' => $referencia
            ));
        }

        try {
            $sql = "UPDATE contabilidad SET saldo_actual = :saldo_actual, saldo_nequi = :saldo_nequi, fecha_actualizacion = NOW() WHERE id_contabilidad = 1";
            $query = $this->acceso->prepare($sql);
            $query->execute(array(
                ':saldo_actual' => $saldo_general_nuevo,
                ':saldo_nequi' => $saldo_nequi_nuevo
            ));
        } catch (Exception $e) { /* columna aún no existe */ }

        return 'success';
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
                COALESCE(m.es_nequi, 0) as es_nequi,
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
                COALESCE(m.es_nequi, 0) as es_nequi,
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

    // Recalcular saldos globales historicos (saldo unico en movimientos)
    function normalizar_saldos_globales() {
        try {
            $this->acceso->beginTransaction();

            $sql = "SELECT id_movimiento, tipo, monto, COALESCE(es_nequi, 0) as es_nequi
                    FROM movimientos_contabilidad
                    ORDER BY fecha_movimiento ASC, id_movimiento ASC";
            $query = $this->acceso->prepare($sql);
            $query->execute();
            $movimientos = $query->fetchAll(PDO::FETCH_OBJ);

            $saldo_general = 0;
            $saldo_nequi = 0;

            $sqlUpdate = "UPDATE movimientos_contabilidad
                          SET saldo_anterior = :saldo_anterior, saldo = :saldo
                          WHERE id_movimiento = :id_movimiento";
            $update = $this->acceso->prepare($sqlUpdate);

            foreach ($movimientos as $mov) {
                $saldo_anterior = $saldo_general;
                $monto = (float)$mov->monto;
                $es_nequi = (int)$mov->es_nequi;

                if ($mov->tipo === 'Ingreso') {
                    $saldo_general += $monto;
                    if ($es_nequi === 1) {
                        $saldo_nequi += $monto;
                    }
                } else {
                    $saldo_general -= $monto;
                    if ($es_nequi === 1) {
                        $saldo_nequi -= $monto;
                    }
                }

                $update->execute(array(
                    ':saldo_anterior' => $saldo_anterior,
                    ':saldo' => $saldo_general,
                    ':id_movimiento' => $mov->id_movimiento
                ));
            }

            $sqlSaldo = "UPDATE contabilidad
                         SET saldo_actual = :saldo_actual,
                             saldo_nequi = :saldo_nequi,
                             fecha_actualizacion = NOW()
                         WHERE id_contabilidad = 1";
            $qSaldo = $this->acceso->prepare($sqlSaldo);
            $qSaldo->execute(array(
                ':saldo_actual' => $saldo_general,
                ':saldo_nequi' => $saldo_nequi
            ));

            $this->acceso->commit();
            return true;
        } catch (Exception $e) {
            if ($this->acceso->inTransaction()) {
                $this->acceso->rollBack();
            }
            return false;
        }
    }

    // Editar movimiento y recalcular saldos posteriores
    function editar_movimiento($id_movimiento, $monto_nuevo) {
        date_default_timezone_set('America/Bogota');
        
        try {
            // Obtener el movimiento original
            $sql = "SELECT * FROM movimientos_contabilidad WHERE id_movimiento = :id";
            $query = $this->acceso->prepare($sql);
            $query->execute(array(':id' => $id_movimiento));
            $movimiento_original = $query->fetch(PDO::FETCH_OBJ);
            
            if (!$movimiento_original) {
                return 'error_no_encontrado';
            }
            
            $es_nequi = isset($movimiento_original->es_nequi) ? (int)$movimiento_original->es_nequi : 0;

            // Calcular impacto en saldo general
            $diferencia_monto = $monto_nuevo - $movimiento_original->monto;
            $impacto_saldo_general = ($movimiento_original->tipo === 'Ingreso') ? $diferencia_monto : -$diferencia_monto;

            // Si es Nequi, validar que no deje saldo_nequi negativo
            if ($es_nequi === 1) {
                $saldo_data = $this->obtener_saldo_actual();
                $saldo_nequi_actual = !empty($saldo_data) && isset($saldo_data[0]->saldo_nequi) ? (float)$saldo_data[0]->saldo_nequi : 0;
                $impacto_saldo_nequi = ($movimiento_original->tipo === 'Ingreso') ? $diferencia_monto : -$diferencia_monto;
                $saldo_nequi_nuevo = $saldo_nequi_actual + $impacto_saldo_nequi;

                if ($saldo_nequi_nuevo < 0) {
                    return 'error_saldo_insuficiente';
                }
            }
            
            // Actualizar el movimiento
            $sql = "UPDATE movimientos_contabilidad 
                    SET monto = :monto_nuevo,
                        saldo = saldo + :impacto_saldo_general
                    WHERE id_movimiento = :id";
            $query = $this->acceso->prepare($sql);
            $query->execute(array(
                ':monto_nuevo' => $monto_nuevo,
                ':impacto_saldo_general' => $impacto_saldo_general,
                ':id' => $id_movimiento
            ));
            
            // Actualizar todos los movimientos posteriores (saldo general unico)
            $sql = "UPDATE movimientos_contabilidad 
                    SET saldo = saldo + :impacto_saldo_general
                    WHERE (fecha_movimiento > :fecha_movimiento
                    OR (fecha_movimiento = :fecha_movimiento AND id_movimiento > :id))";
            $query = $this->acceso->prepare($sql);
            $query->execute(array(
                ':impacto_saldo_general' => $impacto_saldo_general,
                ':fecha_movimiento' => $movimiento_original->fecha_movimiento,
                ':id' => $id_movimiento
            ));
            
            // Actualizar saldo general actual
            $sql = "SELECT saldo FROM movimientos_contabilidad ORDER BY fecha_movimiento DESC, id_movimiento DESC LIMIT 1";
            $query = $this->acceso->prepare($sql);
            $query->execute();
            $ultimo_saldo = $query->fetch(PDO::FETCH_OBJ);
            
            $saldo_general_final = $ultimo_saldo ? $ultimo_saldo->saldo : 0;

            if ($es_nequi === 1) {
                $sql = "UPDATE contabilidad SET saldo_actual = :saldo_actual, saldo_nequi = saldo_nequi + :impacto_nequi WHERE id_contabilidad = 1";
                $query = $this->acceso->prepare($sql);
                $query->execute(array(
                    ':saldo_actual' => $saldo_general_final,
                    ':impacto_nequi' => (($movimiento_original->tipo === 'Ingreso') ? $diferencia_monto : -$diferencia_monto)
                ));
            } else {
                $sql = "UPDATE contabilidad SET saldo_actual = :saldo_actual WHERE id_contabilidad = 1";
                $query = $this->acceso->prepare($sql);
                $query->execute(array(':saldo_actual' => $saldo_general_final));
            }
            
            return 'success';
        } catch (Exception $e) {
            return 'error';
        }
    }
}
?>
