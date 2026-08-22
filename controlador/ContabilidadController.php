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
        if (!isset($_SESSION['usuario'])) {
            echo 'error_sesion';
            exit;
        }
        $saldo_inicial = isset($_POST['saldo_inicial']) ? (float)$_POST['saldo_inicial'] : 0;
        if ($saldo_inicial < 0) {
            echo 'error';
            exit;
        }
        $id_usuario = $_SESSION['usuario'];
        
        $resultado = $contabilidad->inicializar_contabilidad($saldo_inicial, $id_usuario);
        echo $resultado;
        exit;
    }

    // Obtener saldo actual
    if ($funcion == 'obtener_saldo_actual') {
        $contabilidad->normalizar_saldos_globales();
        $saldo = $contabilidad->obtener_saldo_actual();
        if (!empty($saldo)) {
            $row = $saldo[0];
            // Incluir saldo_nequi si existe
            $data = (array) $row;
            if (!isset($data['saldo_nequi'])) {
                $data['saldo_nequi'] = 0;
            }
            echo json_encode($data);
        } else {
            echo json_encode(array('saldo_actual' => 0, 'saldo_nequi' => 0));
        }
        exit;
    }

    // Obtener saldo Nequi
    if ($funcion == 'obtener_saldo_nequi') {
        $contabilidad->normalizar_saldos_globales();
        $saldo_nequi = $contabilidad->obtener_saldo_nequi();
        echo json_encode(array('saldo_nequi' => $saldo_nequi));
        exit;
    }

    // Registrar ingreso
    if ($funcion == 'registrar_ingreso') {
        if (!isset($_SESSION['usuario'])) {
            echo 'error_sesion';
            exit;
        }
        $concepto = isset($_POST['concepto']) ? trim($_POST['concepto']) : '';
        $monto = isset($_POST['monto']) ? (float)$_POST['monto'] : 0;
        $categoria = isset($_POST['categoria']) ? trim($_POST['categoria']) : '';
        $cuenta = isset($_POST['cuenta']) ? strtolower(trim($_POST['cuenta'])) : 'caja';
        if ($cuenta !== 'nequi' && $cuenta !== 'caja') {
            $cuenta = 'caja';
        }
        $descripcion = isset($_POST['descripcion']) ? $_POST['descripcion'] : '';
        $id_usuario = $_SESSION['usuario'];

        if ($concepto === '' || $categoria === '' || $monto <= 0) {
            echo 'error';
            exit;
        }
        
        $resultado = $contabilidad->registrar_ingreso($concepto, $monto, $categoria, $descripcion, $id_usuario, null, $cuenta);
        echo $resultado;
        exit;
    }

    // Registrar egreso
    if ($funcion == 'registrar_egreso') {
        if (!isset($_SESSION['usuario'])) {
            echo 'error_sesion';
            exit;
        }
        $concepto = isset($_POST['concepto']) ? trim($_POST['concepto']) : '';
        $monto = isset($_POST['monto']) ? (float)$_POST['monto'] : 0;
        $categoria = isset($_POST['categoria']) ? trim($_POST['categoria']) : '';
        $cuenta = isset($_POST['cuenta']) ? strtolower(trim($_POST['cuenta'])) : 'caja';
        if ($cuenta !== 'nequi' && $cuenta !== 'caja') {
            $cuenta = 'caja';
        }
        $descripcion = isset($_POST['descripcion']) ? $_POST['descripcion'] : '';
        $id_usuario = $_SESSION['usuario'];

        if ($concepto === '' || $categoria === '' || $monto <= 0) {
            echo 'error';
            exit;
        }
        
        $resultado = $contabilidad->registrar_egreso($concepto, $monto, $categoria, $descripcion, $id_usuario, null, $cuenta);
        echo $resultado;
        exit;
    }

    // Listar movimientos
    if ($funcion == 'listar_movimientos') {
        $contabilidad->normalizar_saldos_globales();
        $fecha_inicio = isset($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : null;
        $fecha_fin = isset($_POST['fecha_fin']) ? $_POST['fecha_fin'] : null;
        $tipo = isset($_POST['tipo']) ? $_POST['tipo'] : null;
        $limit = isset($_POST['limit']) ? $_POST['limit'] : 100;
        
        $movimientos = $contabilidad->listar_movimientos($fecha_inicio, $fecha_fin, $tipo, $limit);
        
        $json = array();
        foreach ($movimientos as $obj) {
            $json[] = array(
                'id_movimiento' => $obj->id_movimiento,
                'fecha_movimiento' => $obj->fecha_movimiento,
                'tipo' => $obj->tipo,
                'cuenta' => (isset($obj->es_nequi) && (int)$obj->es_nequi === 1) ? 'Nequi' : 'Caja General',
                'concepto' => $obj->concepto,
                'monto' => (int)$obj->monto,
                'categoria' => $obj->categoria,
                'descripcion' => $obj->descripcion,
                'saldo' => (int)$obj->saldo,
                'usuario' => $obj->usuario,
                'es_nequi' => isset($obj->es_nequi) ? (int)$obj->es_nequi : 0,
                'referencia' => $obj->referencia
            );
        }
        
        echo json_encode($json);
        exit;
    }

    // Obtener resumen financiero
    if ($funcion == 'obtener_resumen') {
        $contabilidad->normalizar_saldos_globales();
        $resumen = $contabilidad->obtener_resumen();
        echo json_encode($resumen);
        exit;
    }

    // Obtener detalle de un movimiento
    if ($funcion == 'obtener_detalle_movimiento') {
        $id_movimiento = isset($_POST['id_movimiento']) ? (int)$_POST['id_movimiento'] : 0;
        if ($id_movimiento <= 0) {
            echo json_encode(array('error' => 'Movimiento no encontrado'));
            exit;
        }
        $detalle = $contabilidad->obtener_detalle_movimiento($id_movimiento);
        if (!empty($detalle)) {
            $mov = (array)$detalle[0];
            $es_nequi = isset($mov['es_nequi']) ? (int)$mov['es_nequi'] : 0;
            $mov['cuenta'] = $es_nequi === 1 ? 'Nequi' : 'Caja General';
            echo json_encode($mov);
        } else {
            echo json_encode(array('error' => 'Movimiento no encontrado'));
        }
        exit;
    }

    // Editar movimiento
    if ($funcion == 'editar_movimiento') {
        if (!isset($_SESSION['usuario'])) {
            echo 'error_sesion';
            exit;
        }
        $id_movimiento = isset($_POST['id_movimiento']) ? (int)$_POST['id_movimiento'] : 0;
        $monto_nuevo = isset($_POST['monto_nuevo']) ? (float)$_POST['monto_nuevo'] : 0;
        if ($id_movimiento <= 0 || $monto_nuevo <= 0) {
            echo 'error';
            exit;
        }
        
        $resultado = $contabilidad->editar_movimiento($id_movimiento, $monto_nuevo);
        echo $resultado;
        exit;
    }
}
?>
