<?php
require_once('../vendor/autoload.php');
require_once('../modelo/Pdf.php');
require_once('../modelo/Lote.php');
session_start();
if(!isset($_SESSION['usuario'])){
    exit;
}

// Verificar si es una petición GET para reportes de alertas
if (isset($_GET['funcion'])) {
    $funcion = $_GET['funcion'];
    
    if ($funcion == 'reporte_vencidos') {
        generarReporteVencidos();
        exit;
    }
    
    if ($funcion == 'reporte_creditos') {
        $fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : null;
        $fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : null;
        generarReporteCreditos($fecha_desde, $fecha_hasta);
        exit;
    }
}

// Código original para PDF de ventas
if (isset($_POST['id'])) {
    $id_venta = $_POST['id'];
    $html = getHtml($id_venta);
    $css = file_get_contents("../css/pdf.css");
    $mpdf = new \Mpdf\Mpdf();
    $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
    $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
    $mpdf->Output("../pdf/pdf-".$id_venta.".pdf","F");
}

function generarReporteVencidos() {
    $lote = new Lote();
    $lote->buscar_todos_lotes();
    
    $productos_vencidos = array();
    $productos_por_vencer = array();
    
    date_default_timezone_set('America/Bogota');
    $fecha = date('Y-m-d H:i:s');
    $fecha_actual = new DateTime($fecha);
    
    foreach ($lote->objetos as $objeto) {
        $vencimiento = new DateTime($objeto->vencimiento);
        $diferencia = $vencimiento->diff($fecha_actual);
        $mes = $diferencia->m;
        $verificado = $diferencia->invert;
        $estado = 'light';
        
        if($verificado == 0) {
            $estado = 'danger';
            $productos_vencidos[] = $objeto;
        } else {
            if($mes <= 3 && $diferencia->y == 0) {
                $estado = 'warning';
                $productos_por_vencer[] = $objeto;
            }
        }
    }
    
    $html = generarHTMLReporteVencidos($productos_vencidos, $productos_por_vencer);
    $css = file_get_contents("../css/pdf.css");
    
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'margin_left' => 15,
        'margin_right' => 15,
        'margin_top' => 20,
        'margin_bottom' => 20
    ]);
    
    $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
    $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
    
    $fecha_reporte = date('Y-m-d_H-i-s');
    $nombre_archivo = "reporte_productos_vencidos_" . $fecha_reporte . ".pdf";
    
    $mpdf->Output($nombre_archivo, "D"); // D = Download
}

function generarHTMLReporteVencidos($productos_vencidos, $productos_por_vencer) {
    $fecha_reporte = date('d/m/Y H:i:s');
    $total_vencidos = count($productos_vencidos);
    $total_por_vencer = count($productos_por_vencer);
    
    $html = '
    <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="color: #d9534f; margin-bottom: 10px;">🏥 FARMACIA - REPORTE DE ALERTAS</h1>
        <h2 style="color: #666;">Productos Vencidos y Próximos a Vencer</h2>
        <p style="color: #888; font-size: 12px;">Generado el: ' . $fecha_reporte . '</p>
        <hr style="border: 1px solid #ddd;">
    </div>
    
    <div style="margin-bottom: 20px;">
        <h3 style="background-color: #d9534f; color: black; padding: 10px; text-align: center;">
            🔴 PRODUCTOS VENCIDOS (' . $total_vencidos . ')
        </h3>';
    
    if ($total_vencidos > 0) {
        $html .= '
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
            <thead>
                <tr style="background-color: #f8f9fa;">
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left; color: black;">Código</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left; color: black;">Producto</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left; color: black;">Stock</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left; color: black;">Vencimiento</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left; color: black;">Proveedor</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($productos_vencidos as $producto) {
            $html .= '
                <tr>
                    <td style="border: 1px solid #ddd; padding: 8px;">' . $producto->id_lote . '</td>
                    <td style="border: 1px solid #ddd; padding: 8px;"><strong>' . $producto->prod_nom . '</strong><br>
                        <small>' . $producto->concentracion . ' | ' . $producto->adicional . '</small></td>
                    <td style="border: 1px solid #ddd; padding: 8px; text-align: center; color: #d9534f;"><strong>' . $producto->cantidad_lote . '</strong></td>
                    <td style="border: 1px solid #ddd; padding: 8px; color: #d9534f;"><strong>' . date('d/m/Y', strtotime($producto->vencimiento)) . '</strong></td>
                    <td style="border: 1px solid #ddd; padding: 8px;">' . $producto->proveedor . '</td>
                </tr>';
        }
        
        $html .= '
            </tbody>
        </table>';
    } else {
        $html .= '<p style="text-align: center; color: #28a745; font-weight: bold;">✅ No hay productos vencidos</p>';
    }
    
    $html .= '
    </div>
    
    <div style="margin-bottom: 20px;">
        <h3 style="background-color: #f0ad4e; color: black; padding: 10px; text-align: center;">
            🟡 PRODUCTOS PRÓXIMOS A VENCER (' . $total_por_vencer . ')
        </h3>';
    
    if ($total_por_vencer > 0) {
        $html .= '
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
            <thead>
                <tr style="background-color: #f8f9fa;">
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left; color: black;">Código</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left; color: black;">Producto</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left; color: black;">Stock</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left; color: black;">Vencimiento</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left; color: black;">Proveedor</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($productos_por_vencer as $producto) {
            $html .= '
                <tr>
                    <td style="border: 1px solid #ddd; padding: 8px;">' . $producto->id_lote . '</td>
                    <td style="border: 1px solid #ddd; padding: 8px;"><strong>' . $producto->prod_nom . '</strong><br>
                        <small>' . $producto->concentracion . ' | ' . $producto->adicional . '</small></td>
                    <td style="border: 1px solid #ddd; padding: 8px; text-align: center; color: #f0ad4e;"><strong>' . $producto->cantidad_lote . '</strong></td>
                    <td style="border: 1px solid #ddd; padding: 8px; color: #f0ad4e;"><strong>' . date('d/m/Y', strtotime($producto->vencimiento)) . '</strong></td>
                    <td style="border: 1px solid #ddd; padding: 8px;">' . $producto->proveedor . '</td>
                </tr>';
        }
        
        $html .= '
            </tbody>
        </table>';
    } else {
        $html .= '<p style="text-align: center; color: #28a745; font-weight: bold;">✅ No hay productos próximos a vencer</p>';
    }
    
    $html .= '
    </div>
    
    <div style="margin-top: 30px; padding: 15px; background-color: #f8f9fa; border-left: 4px solid #007bff;">
        <h4 style="color: #007bff; margin-bottom: 10px;">📋 Recomendaciones:</h4>
        <ul style="margin: 0; padding-left: 20px;">
            <li><strong>Productos Vencidos:</strong> Retirar inmediatamente del inventario y gestionar descarte apropiado.</li>
            <li><strong>Productos Próximos a Vencer:</strong> Considerar descuentos promocionales para rotación rápida.</li>
            <li><strong>Seguimiento:</strong> Revisar este reporte semanalmente para prevenir pérdidas.</li>
        </ul>
    </div>
    
    <div style="margin-top: 20px; text-align: center; font-size: 10px; color: #999;">
        <p>Este reporte fue generado automáticamente por el Sistema de Gestión Farmacéutica</p>
    </div>';
    
    return $html;
}

function generarReporteCreditos($fecha_desde = null, $fecha_hasta = null) {
    require_once('../modelo/Venta.php');
    require_once('../modelo/Cliente.php');
    
    $venta = new Venta();
    $cliente = new Cliente();
    
    $venta->listar_creditos_reporte($fecha_desde, $fecha_hasta);
    
    date_default_timezone_set('America/Bogota');
    $fecha_reporte = date('d/m/Y H:i:s');
    
    // Agrupar créditos por cliente
    $creditos_por_cliente = array();
    
    foreach ($venta->objetos as $objeto) {
        $id_cliente = $objeto->id_cliente;
        
        if (!isset($creditos_por_cliente[$id_cliente])) {
            if (empty($objeto->id_cliente)) {
                $nombre_cliente = $objeto->cliente;
            } else {
                $cliente->buscar_datos_cliente($objeto->id_cliente);
                $nombre_cliente = '';
                foreach ($cliente->objetos as $cli) {
                    $nombre_cliente = $cli->nombre . ' ' . $cli->apellidos;
                }
            }
            
            $creditos_por_cliente[$id_cliente] = array(
                'nombre' => $nombre_cliente,
                'fecha_antigua' => $objeto->fecha,
                'total' => 0,
                'depositado' => 0,
                'saldo' => 0,
                'cantidad_creditos' => 0
            );
        }
        
        $creditos_por_cliente[$id_cliente]['total'] += floatval($objeto->total);
        $creditos_por_cliente[$id_cliente]['depositado'] += floatval($objeto->depositado);
        $creditos_por_cliente[$id_cliente]['saldo'] += (floatval($objeto->total) - floatval($objeto->depositado));
        $creditos_por_cliente[$id_cliente]['cantidad_creditos']++;
        
        // Mantener la fecha más antigua
        if ($objeto->fecha < $creditos_por_cliente[$id_cliente]['fecha_antigua']) {
            $creditos_por_cliente[$id_cliente]['fecha_antigua'] = $objeto->fecha;
        }
    }
    
    $html = '
    <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="color: #d9534f; margin-bottom: 10px;">💳 FARMACIA - REPORTE DE CRÉDITOS</h1>
        <h2 style="color: #666;">Estado de Cuentas por Cobrar</h2>
        <p style="color: #888; font-size: 12px;">Generado el: ' . $fecha_reporte . '</p>';
    
    if ($fecha_desde || $fecha_hasta) {
        $html .= '<p style="color: #666; font-size: 14px; font-weight: bold;">';
        if ($fecha_desde && $fecha_hasta) {
            $html .= 'Período: ' . date('d/m/Y', strtotime($fecha_desde)) . ' - ' . date('d/m/Y', strtotime($fecha_hasta));
        } elseif ($fecha_desde) {
            $html .= 'Desde: ' . date('d/m/Y', strtotime($fecha_desde));
        } else {
            $html .= 'Hasta: ' . date('d/m/Y', strtotime($fecha_hasta));
        }
        $html .= '</p>';
    }
    
    $html .= '
        <hr style="border: 1px solid #ddd;">
    </div>
    
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
        <thead>
            <tr style="background-color: #28a745; color: white;">
                <th style="border: 1px solid #ddd; padding: 10px; text-align: left;">Cliente</th>
                <th style="border: 1px solid #ddd; padding: 10px; text-align: center;">Fecha Primer Crédito</th>
                <th style="border: 1px solid #ddd; padding: 10px; text-align: center;">Saldo</th>
            </tr>
        </thead>
        <tbody>';
    
    $total_clientes = 0;
    $total_creditos_general = 0;
    $total_depositado_general = 0;
    $total_saldo_general = 0;
    
    foreach ($creditos_por_cliente as $cliente_data) {
        $fecha_formateada = date('d/m/Y', strtotime($cliente_data['fecha_antigua']));
        $total_formateado = number_format($cliente_data['total'], 0, ',', '.');
        $depositado_formateado = number_format($cliente_data['depositado'], 0, ',', '.');
        $saldo_formateado = number_format($cliente_data['saldo'], 0, ',', '.');
        
        $color_saldo = $cliente_data['saldo'] > 0 ? '#d9534f' : '#28a745';
        
        $html .= '
            <tr>
                <td style="border: 1px solid #ddd; padding: 8px;"><strong>' . htmlspecialchars($cliente_data['nombre']) . '</strong></td>
                <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">' . $fecha_formateada . '</td>
                <td style="border: 1px solid #ddd; padding: 8px; text-align: right; color: ' . $color_saldo . '; font-weight: bold;">$' . $saldo_formateado . '</td>
            </tr>';
        
        $total_clientes++;
        $total_creditos_general += $cliente_data['total'];
        $total_depositado_general += $cliente_data['depositado'];
        $total_saldo_general += $cliente_data['saldo'];
    }
    
    $total_creditos_formateado = number_format($total_creditos_general, 0, ',', '.');
    $total_depositado_formateado = number_format($total_depositado_general, 0, ',', '.');
    $total_saldo_formateado = number_format($total_saldo_general, 0, ',', '.');
    
    $html .= '
        </tbody>
        <tfoot>
            <tr style="background-color: #f8f9fa; font-weight: bold;">
                <td colspan="2" style="border: 1px solid #ddd; padding: 10px; text-align: right;">TOTAL SALDO:</td>
                <td style="border: 1px solid #ddd; padding: 10px; text-align: right; color: #d9534f; font-weight: bold;">$' . $total_saldo_formateado . '</td>
            </tr>
        </tfoot>
    </table>
    
    <div style="margin-top: 30px; padding: 15px; background-color: #f8f9fa; border-left: 4px solid #007bff;">
        <h4 style="color: #007bff; margin-bottom: 10px;">📊 Resumen:</h4>
        <ul style="margin: 0; padding-left: 20px;">
            <li><strong>Total de Clientes con Crédito:</strong> ' . $total_clientes . '</li>
            <li><strong>Monto Total:</strong> $' . $total_creditos_formateado . '</li>
            <li><strong>Total Depositado:</strong> $' . $total_depositado_formateado . '</li>
            <li><strong>Saldo por Cobrar:</strong> <span style="color: #d9534f; font-weight: bold;">$' . $total_saldo_formateado . '</span></li>
        </ul>
    </div>
    
    <div style="margin-top: 20px; text-align: center; font-size: 10px; color: #999;">
        <p>Este reporte fue generado automáticamente por el Sistema de Gestión Farmacéutica</p>
    </div>';
    
    $css = file_get_contents("../css/pdf.css");
    
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'margin_left' => 15,
        'margin_right' => 15,
        'margin_top' => 20,
        'margin_bottom' => 20
    ]);
    
    $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
    $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
    
    $fecha_archivo = date('Y-m-d_H-i-s');
    $nombre_archivo = "reporte_creditos_" . $fecha_archivo . ".pdf";
    
    $mpdf->Output($nombre_archivo, "D"); // D = Download
}

?>