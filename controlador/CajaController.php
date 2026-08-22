<?php
session_start();
date_default_timezone_set('America/Bogota');
require_once '../modelo/Caja.php';
require_once '../modelo/Contabilidad.php';

$caja = new Caja();
$contabilidad = new Contabilidad();

if (isset($_POST['funcion'])) {
    $funcion = $_POST['funcion'];

    // Verificar si hay caja abierta
    if ($funcion == 'verificar_caja_abierta') {
        $caja_abierta = $caja->verificar_caja_abierta();
        if (!empty($caja_abierta)) {
            // Convertir objeto a array para JSON
            $datos = (array) $caja_abierta[0];
            echo json_encode(array('estado' => 'abierta', 'datos' => $datos));
        } else {
            echo json_encode(array('estado' => 'cerrada'));
        }
    }

    // Abrir caja
    if ($funcion == 'abrir_caja') {
        if (!isset($_SESSION['usuario'])) {
            echo 'error_sesion';
            exit;
        }
        $monto_inicial = isset($_POST['monto_inicial']) ? (float)$_POST['monto_inicial'] : 0;
        if ($monto_inicial <= 0) {
            echo 'error_monto';
            exit;
        }
        $id_usuario = $_SESSION['usuario'];
        $resultado = $caja->abrir_caja($monto_inicial, $id_usuario);
        echo $resultado;
        exit;
    }

    // Obtener información de caja actual
    if ($funcion == 'obtener_caja_actual') {
        $caja_actual = $caja->obtener_caja_actual();
        if (!empty($caja_actual)) {
            echo json_encode($caja_actual[0]);
        } else {
            echo json_encode(array('error' => 'no_caja_abierta'));
        }
    }

    // Cerrar caja
    if ($funcion == 'cerrar_caja') {
        if (!isset($_SESSION['usuario'])) {
            echo json_encode(array('success' => false, 'error' => 'sesion_invalida'));
            exit;
        }
        $id_caja = isset($_POST['id_caja']) ? (int)$_POST['id_caja'] : 0;
        $efectivo_real = isset($_POST['efectivo_real']) ? (float)$_POST['efectivo_real'] : -1;
        $observaciones = isset($_POST['observaciones']) ? $_POST['observaciones'] : '';
        $id_usuario = $_SESSION['usuario'];
        
        // Validación de seguridad: no permitir cerrar caja con valores vacíos
        if ($id_caja <= 0 || !is_numeric($efectivo_real) || $efectivo_real < 0) {
            echo json_encode(array('success' => false, 'error' => 'Monto inválido'));
            exit;
        }
        
        $resultado = $caja->cerrar_caja($id_caja, $efectivo_real, $id_usuario, $observaciones);
        
        if ($resultado['success']) {
            $utilidad = $resultado['utilidad'];
            $id_caja_ref = $resultado['id_caja'];
            $total_nequi = isset($resultado['total_ventas_nequi']) ? (float)$resultado['total_ventas_nequi'] : 0;
            
            // Obtener la fecha de apertura de la caja para el concepto
            $fecha_caja = $caja->obtener_caja_por_id($id_caja_ref);
            $fecha_apertura = !empty($fecha_caja) ? $fecha_caja[0]->fecha_apertura : date('Y-m-d H:i:s');
            $fecha_formateada = date('d/m/Y', strtotime($fecha_apertura));
            
            // Registrar utilidad de caja (efectivo) en cuenta caja.
            if (is_numeric($utilidad) && (float)$utilidad > 0) {
                $contabilidad->registrar_ingreso(
                    "Cierre de caja - $fecha_formateada",
                    (float)$utilidad,
                    'Cierre de Caja',
                    "Utilidad del día - Caja #$id_caja_ref ($fecha_formateada)",
                    $id_usuario,
                    "CAJA_$id_caja_ref"
                );
            }

            // Registrar Nequi como movimiento Nequi para que actualice banner y trazabilidad.
            if ($total_nequi > 0) {
                $contabilidad->registrar_ingreso(
                    "Ventas Nequi - $fecha_formateada",
                    (float)$total_nequi,
                    'Nequi',
                    "Ventas y abonos Nequi del día - Caja #$id_caja_ref ($fecha_formateada)",
                    $id_usuario,
                    "CAJA_NEQUI_$id_caja_ref",
                    'nequi'
                );
            }
            
            echo json_encode(array('success' => true, 'utilidad' => $utilidad, 'total_nequi' => $total_nequi));
        } else {
            echo json_encode(array('success' => false));
        }
    }

    // Listar cajas (historial)
    if ($funcion == 'listar_cajas') {
        $limit = isset($_POST['limit']) ? $_POST['limit'] : 50;
        $cajas = $caja->listar_cajas($limit);
        
        $json = array();
        foreach ($cajas as $obj) {
        $json[] = array(
                'id_caja' => $obj->id_caja,
                'fecha_apertura' => date('d/m/Y H:i', strtotime($obj->fecha_apertura)),
                'fecha_cierre' => $obj->fecha_cierre ? date('d/m/Y H:i', strtotime($obj->fecha_cierre)) : '-',
                'monto_inicial' => number_format($obj->monto_inicial, 0, ',', '.'),
                'monto_final' => $obj->monto_final ? number_format($obj->monto_final, 0, ',', '.') : '-',
                'total_ventas_contado' => number_format(isset($obj->total_ventas_contado) ? $obj->total_ventas_contado : 0, 0, ',', '.'),
                'total_depositos_credito' => number_format(isset($obj->total_depositos_credito) ? $obj->total_depositos_credito : 0, 0, ',', '.'),
                'total_ventas_nequi' => number_format(isset($obj->total_ventas_nequi) ? $obj->total_ventas_nequi : 0, 0, ',', '.'),
                'efectivo_esperado' => $obj->efectivo_esperado ? number_format($obj->efectivo_esperado, 0, ',', '.') : '-',
                'diferencia' => $obj->diferencia !== null ? number_format($obj->diferencia, 0, ',', '.') : '-',
                'estado' => $obj->estado,
                'usuario_apertura' => $obj->nombre_apertura . ' ' . $obj->apellido_apertura,
                'usuario_cierre' => $obj->nombre_cierre ? $obj->nombre_cierre . ' ' . $obj->apellido_cierre : '-',
                'observaciones' => $obj->observaciones
            );
        }
        
        echo json_encode($json);
    }

    // Obtener detalles de una caja específica
    if ($funcion == 'obtener_caja_detalle') {
        $id_caja = $_POST['id_caja'];
        $caja_detalle = $caja->obtener_caja_por_id($id_caja);
        
        if (!empty($caja_detalle)) {
            echo json_encode($caja_detalle[0]);
        } else {
            echo json_encode(array('error' => 'caja_no_encontrada'));
        }
    }
}
?>
