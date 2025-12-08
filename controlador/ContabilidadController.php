<?php
session_start();
date_default_timezone_set('America/Bogota');
require_once '../modelo/Contabilidad.php';

$contabilidad = new Contabilidad();

if (isset($_POST['funcion'])) {
    $funcion = $_POST['funcion'];

    // Verificar si necesita inicialización
    if ($funcion == 'verificar_inicializacion') {
        $contabilidad_data = $contabilidad->obtener_saldo_actual();
        
        // Verificar el campo saldo_confirmado
        if (!empty($contabilidad_data) && isset($contabilidad_data[0]->saldo_confirmado)) {
            $confirmado = $contabilidad_data[0]->saldo_confirmado;
            echo json_encode(array('requiere_inicializacion' => !$confirmado));
        } else {
            // Si no existe el campo o no hay datos, requiere inicialización
            echo json_encode(array('requiere_inicializacion' => true));
        }
        exit;
    }

    // Inicializar contabilidad
    if ($funcion == 'inicializar_contabilidad') {
        $saldo_inicial = $_POST['saldo_inicial'];
        $id_usuario = $_SESSION['usuario'];
        
        $resultado = $contabilidad->inicializar_contabilidad($saldo_inicial, $id_usuario);
        echo $resultado;
        exit;
    }

    // Obtener saldo actual
    if ($funcion == 'obtener_saldo_actual') {
        $saldo = $contabilidad->obtener_saldo_actual();
        if (!empty($saldo)) {
            echo json_encode($saldo[0]);
        } else {
            echo json_encode(array('saldo_actual' => 0));
        }
        exit;
    }

    // Registrar ingreso
    if ($funcion == 'registrar_ingreso') {
        $concepto = $_POST['concepto'];
        $monto = $_POST['monto'];
        $categoria = $_POST['categoria'];
        $descripcion = isset($_POST['descripcion']) ? $_POST['descripcion'] : '';
        $id_usuario = $_SESSION['usuario'];
        
        $resultado = $contabilidad->registrar_ingreso($concepto, $monto, $categoria, $descripcion, $id_usuario);
        echo $resultado;
        exit;
    }

    // Registrar egreso
    if ($funcion == 'registrar_egreso') {
        $concepto = $_POST['concepto'];
        $monto = $_POST['monto'];
        $categoria = $_POST['categoria'];
        $descripcion = isset($_POST['descripcion']) ? $_POST['descripcion'] : '';
        $id_usuario = $_SESSION['usuario'];
        
        $resultado = $contabilidad->registrar_egreso($concepto, $monto, $categoria, $descripcion, $id_usuario);
        echo $resultado;
        exit;
    }

    // Listar movimientos
    if ($funcion == 'listar_movimientos') {
        $fecha_inicio = isset($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : null;
        $fecha_fin = isset($_POST['fecha_fin']) ? $_POST['fecha_fin'] : null;
        $tipo = isset($_POST['tipo']) ? $_POST['tipo'] : null;
        $limit = isset($_POST['limit']) ? $_POST['limit'] : 100;
        
        $movimientos = $contabilidad->listar_movimientos($fecha_inicio, $fecha_fin, $tipo, $limit);
        echo json_encode($movimientos);
        exit;
    }

    // Obtener resumen financiero
    if ($funcion == 'obtener_resumen') {
        $resumen = $contabilidad->obtener_resumen();
        echo json_encode($resumen);
        exit;
    }

    // Obtener detalle de un movimiento
    if ($funcion == 'obtener_detalle_movimiento') {
        $id_movimiento = $_POST['id_movimiento'];
        $detalle = $contabilidad->obtener_detalle_movimiento($id_movimiento);
        if (!empty($detalle)) {
            echo json_encode($detalle[0]);
        } else {
            echo json_encode(array('error' => 'Movimiento no encontrado'));
        }
        exit;
    }
}
?>
        
        $movimientos = $contabilidad->listar_movimientos($fecha_desde, $fecha_hasta, $tipo, $limit);
        
        $json = array();
        foreach ($movimientos as $obj) {
            $json[] = array(
                'id_movimiento' => $obj->id_movimiento,
                'fecha' => date('d/m/Y H:i', strtotime($obj->fecha)),
                'tipo' => $obj->tipo,
                'concepto' => $obj->concepto,
                'monto' => number_format($obj->monto, 0, ',', '.'),
                'categoria' => $obj->categoria,
                'descripcion' => $obj->descripcion,
                'saldo_anterior' => number_format($obj->saldo_anterior, 0, ',', '.'),
                'saldo_nuevo' => number_format($obj->saldo_nuevo, 0, ',', '.'),
                'usuario' => $obj->nombre_us . ' ' . $obj->apellidos_us,
                'referencia' => $obj->referencia
            );
        }
        
        echo json_encode($json);
    }

    // Obtener estadísticas
    if ($funcion == 'obtener_estadisticas') {
        $estadisticas = $contabilidad->obtener_estadisticas();
        if (!empty($estadisticas)) {
            echo json_encode($estadisticas[0]);
        } else {
            echo json_encode(array(
                'total_ingresos_count' => 0,
                'total_egresos_count' => 0,
                'total_ingresos' => 0,
                'total_egresos' => 0
            ));
        }
    }
}
?>
