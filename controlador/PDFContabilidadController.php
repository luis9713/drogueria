<?php
session_start();
require_once '../vendor/autoload.php';
require_once '../modelo/Contabilidad.php';

$contabilidad = new Contabilidad();

// Configuración de zona horaria
date_default_timezone_set('America/Bogota');

// Obtener parámetros
$tipo_reporte = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01');
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d');

// Función para formatear números con separador de miles colombiano
function formatearNumero($numero) {
    return number_format($numero, 0, ',', '.');
}

// Crear PDF según el tipo
switch($tipo_reporte) {
    case 'estado_resultados':
        generar_estado_resultados($contabilidad, $fecha_inicio, $fecha_fin);
        break;
    case 'flujo_caja':
        generar_flujo_caja($contabilidad, $fecha_inicio, $fecha_fin);
        break;
    case 'resumen_categorias':
        generar_resumen_categorias($contabilidad, $fecha_inicio, $fecha_fin);
        break;
    case 'libro_diario':
        generar_libro_diario($contabilidad, $fecha_inicio, $fecha_fin);
        break;
    case 'arqueo_caja':
        generar_arqueo_caja($contabilidad, $fecha_inicio, $fecha_fin);
        break;
    default:
        die('Tipo de reporte no válido');
}

// ==================== ESTADO DE RESULTADOS ====================
function generar_estado_resultados($contabilidad, $fecha_inicio, $fecha_fin) {
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'Letter',
        'margin_left' => 15,
        'margin_right' => 15,
        'margin_top' => 45,
        'margin_bottom' => 20,
        'margin_header' => 10,
        'margin_footer' => 10
    ]);

    $datos = $contabilidad->obtener_estado_resultados($fecha_inicio, $fecha_fin);
    $totales = $contabilidad->obtener_totales_periodo($fecha_inicio, $fecha_fin);

    $ingresos = [];
    $egresos = [];
    
    foreach($datos as $row) {
        if($row->tipo == 'Ingreso') {
            $ingresos[] = $row;
        } else {
            $egresos[] = $row;
        }
    }

    $total_ingresos = $totales['total_ingresos'];
    $total_egresos = $totales['total_egresos'];
    $utilidad = $total_ingresos - $total_egresos;

    $html = '
    <style>
        body { font-family: Arial, sans-serif; font-size: 11pt; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 5px 0; color: #2c3e50; }
        .periodo { text-align: center; color: #7f8c8d; margin-bottom: 20px; font-size: 10pt; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th { background-color: #3498db; color: white; padding: 8px; text-align: left; font-size: 10pt; }
        td { padding: 6px 8px; border-bottom: 1px solid #ecf0f1; font-size: 10pt; }
        .ingreso { background-color: #d5f4e6; }
        .egreso { background-color: #fadbd8; }
        .total-row { background-color: #f8f9fa; font-weight: bold; border-top: 2px solid #34495e; }
        .utilidad-positiva { background-color: #d5f4e6; color: #27ae60; font-weight: bold; font-size: 12pt; }
        .utilidad-negativa { background-color: #fadbd8; color: #e74c3c; font-weight: bold; font-size: 12pt; }
        .text-right { text-align: right; }
        .section-title { background-color: #34495e; color: white; padding: 8px; margin-top: 15px; font-size: 11pt; }
    </style>

    <div class="header">
        <h2>🏢 DROGUERÍA</h2>
        <h3>ESTADO DE RESULTADOS</h3>
    </div>
    <div class="periodo">
        Período: ' . date('d/m/Y', strtotime($fecha_inicio)) . ' - ' . date('d/m/Y', strtotime($fecha_fin)) . '
    </div>

    <div class="section-title">💰 INGRESOS</div>
    <table>
        <thead>
            <tr>
                <th>Categoría</th>
                <th style="text-align: center;">Cantidad</th>
                <th style="text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>';
    
    if(count($ingresos) > 0) {
        foreach($ingresos as $ing) {
            $html .= '<tr class="ingreso">
                <td>' . htmlspecialchars($ing->categoria) . '</td>
                <td style="text-align: center;">' . $ing->cantidad . '</td>
                <td class="text-right">$' . formatearNumero($ing->total) . '</td>
            </tr>';
        }
    } else {
        $html .= '<tr><td colspan="3" style="text-align: center; color: #95a5a6;">Sin ingresos en el período</td></tr>';
    }

    $html .= '<tr class="total-row">
            <td colspan="2">TOTAL INGRESOS</td>
            <td class="text-right">$' . formatearNumero($total_ingresos) . '</td>
        </tr>
        </tbody>
    </table>

    <div class="section-title">📉 EGRESOS</div>
    <table>
        <thead>
            <tr>
                <th>Categoría</th>
                <th style="text-align: center;">Cantidad</th>
                <th style="text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>';
    
    if(count($egresos) > 0) {
        foreach($egresos as $egr) {
            $html .= '<tr class="egreso">
                <td>' . htmlspecialchars($egr->categoria) . '</td>
                <td style="text-align: center;">' . $egr->cantidad . '</td>
                <td class="text-right">$' . formatearNumero($egr->total) . '</td>
            </tr>';
        }
    } else {
        $html .= '<tr><td colspan="3" style="text-align: center; color: #95a5a6;">Sin egresos en el período</td></tr>';
    }

    $html .= '<tr class="total-row">
            <td colspan="2">TOTAL EGRESOS</td>
            <td class="text-right">$' . formatearNumero($total_egresos) . '</td>
        </tr>
        </tbody>
    </table>

    <table style="margin-top: 20px;">
        <tr class="' . ($utilidad >= 0 ? 'utilidad-positiva' : 'utilidad-negativa') . '">
            <td style="padding: 12px; font-size: 12pt;">
                ' . ($utilidad >= 0 ? '✅ UTILIDAD DEL PERÍODO' : '⚠️ PÉRDIDA DEL PERÍODO') . '
            </td>
            <td class="text-right" style="padding: 12px; font-size: 14pt;">
                $' . formatearNumero(abs($utilidad)) . '
            </td>
        </tr>
    </table>

    <div style="margin-top: 30px; font-size: 9pt; color: #7f8c8d; text-align: center;">
        Generado el ' . date('d/m/Y H:i:s') . ' | Usuario: ' . $_SESSION['nombre_us'] . '
    </div>';

    $mpdf->WriteHTML($html);
    $mpdf->Output('Estado_Resultados_' . date('Y-m-d') . '.pdf', 'I');
}

// ==================== FLUJO DE CAJA DETALLADO ====================
function generar_flujo_caja($contabilidad, $fecha_inicio, $fecha_fin) {
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'Letter',
        'orientation' => 'L',
        'margin_left' => 10,
        'margin_right' => 10,
        'margin_top' => 40,
        'margin_bottom' => 15,
        'margin_header' => 10,
        'margin_footer' => 10
    ]);

    $movimientos = $contabilidad->obtener_movimientos_reporte($fecha_inicio, $fecha_fin);
    $totales = $contabilidad->obtener_totales_periodo($fecha_inicio, $fecha_fin);

    $html = '
    <style>
        body { font-family: Arial, sans-serif; font-size: 9pt; }
        .header { text-align: center; margin-bottom: 15px; }
        .header h2 { margin: 3px 0; color: #2c3e50; font-size: 14pt; }
        .periodo { text-align: center; color: #7f8c8d; margin-bottom: 15px; font-size: 9pt; }
        table { width: 100%; border-collapse: collapse; font-size: 8pt; }
        th { background-color: #3498db; color: white; padding: 6px 4px; text-align: left; }
        td { padding: 4px; border-bottom: 1px solid #ecf0f1; }
        .ingreso-row { background-color: #d5f4e6; }
        .egreso-row { background-color: #fadbd8; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .badge-ingreso { background-color: #27ae60; color: white; padding: 2px 6px; border-radius: 3px; font-size: 7pt; }
        .badge-egreso { background-color: #e74c3c; color: white; padding: 2px 6px; border-radius: 3px; font-size: 7pt; }
    </style>

    <div class="header">
        <h2>🏢 DROGUERÍA - FLUJO DE CAJA DETALLADO</h2>
    </div>
    <div class="periodo">
        Período: ' . date('d/m/Y', strtotime($fecha_inicio)) . ' - ' . date('d/m/Y', strtotime($fecha_fin)) . '
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 8%;">#</th>
                <th style="width: 12%;">Fecha/Hora</th>
                <th style="width: 8%;">Tipo</th>
                <th style="width: 17%;">Concepto</th>
                <th style="width: 12%;">Categoría</th>
                <th style="width: 11%;" class="text-right">Ingreso</th>
                <th style="width: 11%;" class="text-right">Egreso</th>
                <th style="width: 11%;" class="text-right">Saldo</th>
                <th style="width: 10%;">Usuario</th>
            </tr>
        </thead>
        <tbody>';
    
    if(count($movimientos) > 0) {
        foreach($movimientos as $mov) {
            $clase = $mov->tipo == 'Ingreso' ? 'ingreso-row' : 'egreso-row';
            $badge = $mov->tipo == 'Ingreso' ? 'badge-ingreso' : 'badge-egreso';
            
            $html .= '<tr class="' . $clase . '">
                <td class="text-center">' . $mov->id_movimiento . '</td>
                <td>' . date('d/m/Y H:i', strtotime($mov->fecha_movimiento)) . '</td>
                <td><span class="' . $badge . '">' . $mov->tipo . '</span></td>
                <td>' . htmlspecialchars($mov->concepto) . '</td>
                <td>' . htmlspecialchars($mov->categoria) . '</td>
                <td class="text-right">' . ($mov->tipo == 'Ingreso' ? '$' . formatearNumero($mov->monto) : '-') . '</td>
                <td class="text-right">' . ($mov->tipo == 'Egreso' ? '$' . formatearNumero($mov->monto) : '-') . '</td>
                <td class="text-right" style="font-weight: bold;">$' . formatearNumero($mov->saldo) . '</td>
                <td style="font-size: 7pt;">' . htmlspecialchars($mov->usuario) . '</td>
            </tr>';
        }
    } else {
        $html .= '<tr><td colspan="9" style="text-align: center; color: #95a5a6; padding: 20px;">Sin movimientos en el período seleccionado</td></tr>';
    }

    $html .= '</tbody>
        <tfoot>
            <tr style="background-color: #34495e; color: white; font-weight: bold;">
                <td colspan="5" style="padding: 8px;">TOTALES</td>
                <td class="text-right" style="padding: 8px;">$' . formatearNumero($totales['total_ingresos']) . '</td>
                <td class="text-right" style="padding: 8px;">$' . formatearNumero($totales['total_egresos']) . '</td>
                <td class="text-right" style="padding: 8px;">$' . formatearNumero($totales['saldo_actual']) . '</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top: 15px; font-size: 8pt; color: #7f8c8d; text-align: center;">
        Generado el ' . date('d/m/Y H:i:s') . ' | Usuario: ' . $_SESSION['nombre_us'] . '
    </div>';

    $mpdf->WriteHTML($html);
    $mpdf->Output('Flujo_Caja_' . date('Y-m-d') . '.pdf', 'I');
}

// ==================== RESUMEN POR CATEGORÍAS ====================
function generar_resumen_categorias($contabilidad, $fecha_inicio, $fecha_fin) {
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'Letter',
        'margin_left' => 15,
        'margin_right' => 15,
        'margin_top' => 45,
        'margin_bottom' => 20
    ]);

    $categorias = $contabilidad->obtener_resumen_categorias($fecha_inicio, $fecha_fin);
    $totales = $contabilidad->obtener_totales_periodo($fecha_inicio, $fecha_fin);

    $ingresos_cat = [];
    $egresos_cat = [];
    
    foreach($categorias as $cat) {
        if($cat->tipo == 'Ingreso') {
            $ingresos_cat[] = $cat;
        } else {
            $egresos_cat[] = $cat;
        }
    }

    $html = '
    <style>
        body { font-family: Arial, sans-serif; font-size: 10pt; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 5px 0; color: #2c3e50; }
        .periodo { text-align: center; color: #7f8c8d; margin-bottom: 20px; font-size: 9pt; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th { background-color: #3498db; color: white; padding: 8px; text-align: left; font-size: 9pt; }
        td { padding: 6px 8px; border-bottom: 1px solid #ecf0f1; font-size: 9pt; }
        .ingreso { background-color: #d5f4e6; }
        .egreso { background-color: #fadbd8; }
        .total-row { background-color: #34495e; color: white; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .section-title { background-color: #34495e; color: white; padding: 8px; margin-top: 15px; }
    </style>

    <div class="header">
        <h2>🏢 DROGUERÍA</h2>
        <h3>RESUMEN POR CATEGORÍAS</h3>
    </div>
    <div class="periodo">
        Período: ' . date('d/m/Y', strtotime($fecha_inicio)) . ' - ' . date('d/m/Y', strtotime($fecha_fin)) . '
    </div>

    <div class="section-title">💰 INGRESOS POR CATEGORÍA</div>
    <table>
        <thead>
            <tr>
                <th>Categoría</th>
                <th class="text-center">Cantidad</th>
                <th class="text-right">Total</th>
                <th class="text-right">Promedio</th>
                <th class="text-right">% del Total</th>
            </tr>
        </thead>
        <tbody>';
    
    if(count($ingresos_cat) > 0) {
        foreach($ingresos_cat as $ing) {
            $porcentaje = $totales['total_ingresos'] > 0 ? ($ing->total / $totales['total_ingresos']) * 100 : 0;
            $html .= '<tr class="ingreso">
                <td>' . htmlspecialchars($ing->categoria) . '</td>
                <td class="text-center">' . $ing->cantidad . '</td>
                <td class="text-right">$' . formatearNumero($ing->total) . '</td>
                <td class="text-right">$' . formatearNumero($ing->promedio) . '</td>
                <td class="text-right">' . number_format($porcentaje, 1) . '%</td>
            </tr>';
        }
    } else {
        $html .= '<tr><td colspan="5" style="text-align: center; color: #95a5a6;">Sin ingresos</td></tr>';
    }

    $html .= '<tr class="total-row">
            <td colspan="2">TOTAL INGRESOS</td>
            <td class="text-right">$' . formatearNumero($totales['total_ingresos']) . '</td>
            <td colspan="2"></td>
        </tr>
        </tbody>
    </table>

    <div class="section-title">📉 EGRESOS POR CATEGORÍA</div>
    <table>
        <thead>
            <tr>
                <th>Categoría</th>
                <th class="text-center">Cantidad</th>
                <th class="text-right">Total</th>
                <th class="text-right">Promedio</th>
                <th class="text-right">% del Total</th>
            </tr>
        </thead>
        <tbody>';
    
    if(count($egresos_cat) > 0) {
        foreach($egresos_cat as $egr) {
            $porcentaje = $totales['total_egresos'] > 0 ? ($egr->total / $totales['total_egresos']) * 100 : 0;
            $html .= '<tr class="egreso">
                <td>' . htmlspecialchars($egr->categoria) . '</td>
                <td class="text-center">' . $egr->cantidad . '</td>
                <td class="text-right">$' . formatearNumero($egr->total) . '</td>
                <td class="text-right">$' . formatearNumero($egr->promedio) . '</td>
                <td class="text-right">' . number_format($porcentaje, 1) . '%</td>
            </tr>';
        }
    } else {
        $html .= '<tr><td colspan="5" style="text-align: center; color: #95a5a6;">Sin egresos</td></tr>';
    }

    $html .= '<tr class="total-row">
            <td colspan="2">TOTAL EGRESOS</td>
            <td class="text-right">$' . formatearNumero($totales['total_egresos']) . '</td>
            <td colspan="2"></td>
        </tr>
        </tbody>
    </table>

    <div style="margin-top: 20px; font-size: 9pt; color: #7f8c8d; text-align: center;">
        Generado el ' . date('d/m/Y H:i:s') . ' | Usuario: ' . $_SESSION['nombre_us'] . '
    </div>';

    $mpdf->WriteHTML($html);
    $mpdf->Output('Resumen_Categorias_' . date('Y-m-d') . '.pdf', 'I');
}

// ==================== LIBRO DIARIO ====================
function generar_libro_diario($contabilidad, $fecha_inicio, $fecha_fin) {
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'Letter',
        'margin_left' => 15,
        'margin_right' => 15,
        'margin_top' => 45,
        'margin_bottom' => 20
    ]);

    $movimientos = $contabilidad->obtener_movimientos_reporte($fecha_inicio, $fecha_fin);
    $totales = $contabilidad->obtener_totales_periodo($fecha_inicio, $fecha_fin);

    $html = '
    <style>
        body { font-family: Arial, sans-serif; font-size: 9pt; }
        .header { text-align: center; margin-bottom: 15px; }
        .header h2 { margin: 3px 0; color: #2c3e50; }
        .periodo { text-align: center; color: #7f8c8d; margin-bottom: 15px; font-size: 8pt; }
        table { width: 100%; border-collapse: collapse; font-size: 8pt; }
        th { background-color: #2c3e50; color: white; padding: 6px 4px; }
        td { padding: 4px; border-bottom: 1px solid #ecf0f1; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .ingreso-row { background-color: #d5f4e6; }
        .egreso-row { background-color: #fadbd8; }
        .total-row { background-color: #34495e; color: white; font-weight: bold; }
    </style>

    <div class="header">
        <h2>🏢 DROGUERÍA - LIBRO DIARIO</h2>
    </div>
    <div class="periodo">
        Período: ' . date('d/m/Y', strtotime($fecha_inicio)) . ' - ' . date('d/m/Y', strtotime($fecha_fin)) . '
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 8%;">#</th>
                <th style="width: 14%;">Fecha</th>
                <th style="width: 10%;">Tipo</th>
                <th style="width: 25%;">Concepto</th>
                <th style="width: 15%;">Categoría</th>
                <th style="width: 13%;" class="text-right">Debe</th>
                <th style="width: 13%;" class="text-right">Haber</th>
            </tr>
        </thead>
        <tbody>';
    
    if(count($movimientos) > 0) {
        foreach($movimientos as $mov) {
            $clase = $mov->tipo == 'Ingreso' ? 'ingreso-row' : 'egreso-row';
            $html .= '<tr class="' . $clase . '">
                <td class="text-center">' . $mov->id_movimiento . '</td>
                <td>' . date('d/m/Y H:i', strtotime($mov->fecha_movimiento)) . '</td>
                <td>' . $mov->tipo . '</td>
                <td>' . htmlspecialchars($mov->concepto) . '</td>
                <td>' . htmlspecialchars($mov->categoria) . '</td>
                <td class="text-right">' . ($mov->tipo == 'Ingreso' ? '$' . formatearNumero($mov->monto) : '-') . '</td>
                <td class="text-right">' . ($mov->tipo == 'Egreso' ? '$' . formatearNumero($mov->monto) : '-') . '</td>
            </tr>';
            
            if($mov->descripcion) {
                $html .= '<tr class="' . $clase . '">
                    <td colspan="7" style="font-size: 7pt; font-style: italic; padding-left: 15px;">
                        📝 ' . htmlspecialchars($mov->descripcion) . '
                    </td>
                </tr>';
            }
        }
    } else {
        $html .= '<tr><td colspan="7" style="text-align: center; color: #95a5a6; padding: 20px;">Sin movimientos registrados</td></tr>';
    }

    $html .= '</tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="5" style="padding: 8px;">TOTALES</td>
                <td class="text-right" style="padding: 8px;">$' . formatearNumero($totales['total_ingresos']) . '</td>
                <td class="text-right" style="padding: 8px;">$' . formatearNumero($totales['total_egresos']) . '</td>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top: 15px; padding: 10px; background-color: #ecf0f1; border-left: 4px solid #3498db;">
        <strong>Saldo Final del Período:</strong> $' . formatearNumero($totales['saldo_actual']) . '
    </div>

    <div style="margin-top: 15px; font-size: 8pt; color: #7f8c8d; text-align: center;">
        Generado el ' . date('d/m/Y H:i:s') . ' | Usuario: ' . $_SESSION['nombre_us'] . '
    </div>';

    $mpdf->WriteHTML($html);
    $mpdf->Output('Libro_Diario_' . date('Y-m-d') . '.pdf', 'I');
}

// ==================== ARQUEO DE CAJA ====================
function generar_arqueo_caja($contabilidad, $fecha_inicio, $fecha_fin) {
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'Letter',
        'margin_left' => 15,
        'margin_right' => 15,
        'margin_top' => 45,
        'margin_bottom' => 20
    ]);

    $cajas = $contabilidad->obtener_arqueo_caja($fecha_inicio, $fecha_fin);

    $html = '
    <style>
        body { font-family: Arial, sans-serif; font-size: 10pt; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 5px 0; color: #2c3e50; }
        .periodo { text-align: center; color: #7f8c8d; margin-bottom: 20px; font-size: 9pt; }
        .caja-card { border: 2px solid #3498db; border-radius: 5px; padding: 15px; margin-bottom: 20px; page-break-inside: avoid; }
        .caja-header { background-color: #3498db; color: white; padding: 10px; margin: -15px -15px 15px -15px; border-radius: 3px 3px 0 0; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 8px; padding: 5px; }
        .info-label { font-weight: bold; color: #34495e; }
        .info-value { color: #2c3e50; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th { background-color: #ecf0f1; padding: 8px; text-align: left; }
        td { padding: 8px; border-bottom: 1px solid #ecf0f1; }
        .text-right { text-align: right; }
        .positivo { color: #27ae60; font-weight: bold; }
        .negativo { color: #e74c3c; font-weight: bold; }
        .estado-abierta { background-color: #27ae60; color: white; padding: 4px 8px; border-radius: 3px; font-size: 9pt; }
        .estado-cerrada { background-color: #95a5a6; color: white; padding: 4px 8px; border-radius: 3px; font-size: 9pt; }
    </style>

    <div class="header">
        <h2>🏢 DROGUERÍA</h2>
        <h3>ARQUEO DE CAJA</h3>
    </div>
    <div class="periodo">
        Período: ' . date('d/m/Y', strtotime($fecha_inicio)) . ' - ' . date('d/m/Y', strtotime($fecha_fin)) . '
    </div>';

    if(count($cajas) > 0) {
        foreach($cajas as $caja) {
            $estado_clase = $caja->estado == 'abierta' ? 'estado-abierta' : 'estado-cerrada';
            $diferencia_clase = $caja->diferencia >= 0 ? 'positivo' : 'negativo';
            
            $html .= '
            <div class="caja-card">
                <div class="caja-header">
                    <strong>Caja #' . $caja->id_caja . '</strong>
                    <span style="float: right;" class="' . $estado_clase . '">' . strtoupper($caja->estado) . '</span>
                </div>
                
                <table style="font-size: 9pt;">
                    <tr>
                        <td style="width: 50%;"><span class="info-label">Apertura:</span> ' . date('d/m/Y H:i', strtotime($caja->fecha_apertura)) . '</td>
                        <td><span class="info-label">Usuario:</span> ' . htmlspecialchars($caja->usuario_apertura) . '</td>
                    </tr>';
            
            if($caja->fecha_cierre) {
                $html .= '<tr>
                    <td><span class="info-label">Cierre:</span> ' . date('d/m/Y H:i', strtotime($caja->fecha_cierre)) . '</td>
                    <td><span class="info-label">Usuario:</span> ' . htmlspecialchars($caja->usuario_cierre) . '</td>
                </tr>';
            }
            
            $html .= '</table>
                
                <table style="margin-top: 10px;">
                    <tr>
                        <th colspan="2" style="background-color: #34495e; color: white;">MOVIMIENTOS DEL DÍA</th>
                    </tr>
                    <tr>
                        <td>Monto Inicial (Base)</td>
                        <td class="text-right">$' . formatearNumero($caja->monto_inicial) . '</td>
                    </tr>
                    <tr>
                        <td>Ventas de Contado</td>
                        <td class="text-right positivo">+ $' . formatearNumero($caja->total_ventas_contado) . '</td>
                    </tr>
                    <tr>
                        <td>Depósitos de Créditos</td>
                        <td class="text-right positivo">+ $' . formatearNumero($caja->total_depositos_credito) . '</td>
                    </tr>
                    <tr style="background-color: #ecf0f1;">
                        <td><strong>Efectivo Esperado</strong></td>
                        <td class="text-right"><strong>$' . formatearNumero($caja->efectivo_esperado) . '</strong></td>
                    </tr>';
            
            if($caja->efectivo_real !== null) {
                $html .= '<tr>
                    <td><strong>Efectivo Real (Contado)</strong></td>
                    <td class="text-right"><strong>$' . formatearNumero($caja->efectivo_real) . '</strong></td>
                </tr>
                <tr style="background-color: ' . ($caja->diferencia >= 0 ? '#d5f4e6' : '#fadbd8') . ';">
                    <td><strong>Diferencia</strong></td>
                    <td class="text-right ' . $diferencia_clase . '">
                        ' . ($caja->diferencia >= 0 ? '+' : '') . ' $' . formatearNumero($caja->diferencia) . '
                    </td>
                </tr>';
            }
            
            $html .= '</table>';
            
            if($caja->observaciones) {
                $html .= '<div style="margin-top: 10px; padding: 8px; background-color: #fff3cd; border-left: 4px solid #ffc107;">
                    <strong>Observaciones:</strong><br>' . nl2br(htmlspecialchars($caja->observaciones)) . '
                </div>';
            }
            
            $html .= '</div>';
        }
        
        // Resumen total
        $total_ventas_contado = array_sum(array_column($cajas, 'total_ventas_contado'));
        $total_depositos = array_sum(array_column($cajas, 'total_depositos_credito'));
        $total_diferencias = array_sum(array_column($cajas, 'diferencia'));
        
        $html .= '
        <div style="margin-top: 20px; padding: 15px; background-color: #34495e; color: white; border-radius: 5px;">
            <h4 style="margin: 0 0 10px 0;">RESUMEN DEL PERÍODO</h4>
            <table style="color: white;">
                <tr>
                    <td>Total Cajas:</td>
                    <td class="text-right"><strong>' . count($cajas) . '</strong></td>
                </tr>
                <tr>
                    <td>Total Ventas Contado:</td>
                    <td class="text-right"><strong>$' . formatearNumero($total_ventas_contado) . '</strong></td>
                </tr>
                <tr>
                    <td>Total Depósitos Créditos:</td>
                    <td class="text-right"><strong>$' . formatearNumero($total_depositos) . '</strong></td>
                </tr>
                <tr>
                    <td>Diferencia Total:</td>
                    <td class="text-right"><strong>' . ($total_diferencias >= 0 ? '+' : '') . ' $' . formatearNumero($total_diferencias) . '</strong></td>
                </tr>
            </table>
        </div>';
        
    } else {
        $html .= '<div style="text-align: center; color: #95a5a6; padding: 40px;">
            <h3>📭 Sin cajas registradas en el período seleccionado</h3>
        </div>';
    }

    $html .= '
    <div style="margin-top: 20px; font-size: 9pt; color: #7f8c8d; text-align: center;">
        Generado el ' . date('d/m/Y H:i:s') . ' | Usuario: ' . $_SESSION['nombre_us'] . '
    </div>';

    $mpdf->WriteHTML($html);
    $mpdf->Output('Arqueo_Caja_' . date('Y-m-d') . '.pdf', 'I');
}
?>
