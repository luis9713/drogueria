<?php
include_once '../modelo/Compras.php';
include_once '../modelo/Lote.php';
require_once('../vendor/autoload.php');
session_start();
if(!isset($_SESSION['usuario'])){
  echo 'error_sesion';
  exit;
}
if(!isset($_POST['funcion'])){
  exit;
}
$lote = new Lote();
$compras = new Compras();
if($_POST['funcion']=='registrar_compra'){
  $descripcion = isset($_POST['descripcionString']) ? json_decode($_POST['descripcionString']) : null;
  $productos = isset($_POST['productosString']) ? json_decode($_POST['productosString']) : null;

  if (empty($descripcion) || !is_array($productos) || count($productos) === 0) {
    echo 'error_datos';
    exit;
  }

  $codigo = isset($descripcion->codigo) ? trim($descripcion->codigo) : '';
  $fecha_compra = isset($descripcion->fecha_compra) ? $descripcion->fecha_compra : '';
  $fecha_entrega = isset($descripcion->fecha_entrega) ? $descripcion->fecha_entrega : '';
  $total = isset($descripcion->total) ? (float)$descripcion->total : 0;
  $flete = isset($descripcion->flete) ? (float)$descripcion->flete : 0;
  $estado = isset($descripcion->estado) ? (int)$descripcion->estado : 0;
  $proveedor = isset($descripcion->proveedor) ? (int)$descripcion->proveedor : 0;

  if ($codigo === '' || $fecha_compra === '' || $fecha_entrega === '' || $total <= 0 || $flete < 0 || $estado <= 0 || $proveedor <= 0) {
    echo 'error_validacion';
    exit;
  }

  if (!$compras->crear($codigo, $fecha_compra, $fecha_entrega, $total, $estado, $proveedor, $flete)) {
    echo 'error_compra';
    exit;
  }

  $id_compra = (int)$compras->obtener_ultimo_id_insertado();
  if ($id_compra <= 0) {
    echo 'error_compra';
    exit;
  }

  foreach ($productos as $prod) {
    $codigo_lote = isset($prod->codigo) ? trim($prod->codigo) : '';
    $cantidad = isset($prod->cantidad) ? (float)$prod->cantidad : 0;
    $vencimiento = isset($prod->vencimiento) ? $prod->vencimiento : '';
    $precio_compra = isset($prod->precio_compra) ? (float)$prod->precio_compra : 0;
    $id_producto = isset($prod->id) ? (int)$prod->id : 0;

    if ($codigo_lote === '' || $cantidad <= 0 || $vencimiento === '' || $precio_compra <= 0 || $id_producto <= 0) {
      echo 'error_producto';
      exit;
    }

    if (!$lote->crear_lote($codigo_lote, $cantidad, $vencimiento, $precio_compra, $id_compra, $id_producto)) {
      echo 'error_lote';
      exit;
    }
  }

  echo 'add';
}
if($_POST['funcion']=='listar_compras'){
    $compras->listar_compras();
    $cont=0;
    foreach ($compras->objetos as $objeto) {
       $cont++;
       $json[]= array(
           'numeracion'=>$cont,
           'codigo'=>$objeto->codigo,
           'fecha_compra'=>$objeto->fecha_compra,
           'fecha_entrega'=>$objeto->fecha_entrega,
           'subtotal'=> $objeto->total,
           'flete'=> $objeto->flete,
           'total'=> $objeto->total_con_flete,
           'estado'=>$objeto->estado,
           'proveedor'=>$objeto->proveedor
       );
    }
    $jsonstring = json_encode($json);
    echo $jsonstring;
}
if($_POST['funcion']=='editarEstado'){
    $id_compra = $_POST['id_compra'];
    $id_estado = $_POST['id_estado'];
    $compras->editarEstado($id_compra,$id_estado);
    echo 'edit';
    
}
if($_POST['funcion']=='marcar_pagado'){
    $id_compra = isset($_POST['id_compra']) ? (int)$_POST['id_compra'] : 0;

    if ($id_compra <= 0) {
        echo 'error_datos';
        exit;
    }

    $compra_actual = $compras->obtener($id_compra);
    if (!$compra_actual) {
        echo 'error_compra';
        exit;
    }

    if ((int)$compra_actual->id_estado_pago === 1) {
        echo 'ya_pagado';
        exit;
    }

    if ($compras->marcar_pagado($id_compra)) {
        echo 'pagado';
    } else {
        echo 'error_pagar';
    }
}
if($_POST['funcion']=='obtener_compra_editar'){
    $id_compra = isset($_POST['id_compra']) ? (int)$_POST['id_compra'] : 0;

    if ($id_compra <= 0) {
        echo 'error_datos';
        exit;
    }

    $compra_actual = $compras->obtener($id_compra);
    if (!$compra_actual) {
        echo 'error_compra';
        exit;
    }

    $lote->obtener_detalle_edicion($id_compra);
    $lotes = array();
    foreach ($lote->objetos as $objeto) {
        $lotes[] = array(
            'id_lote'=>$objeto->id_lote,
            'id'=>$objeto->id_producto,
            'nombre'=>$objeto->id_producto.' | '.$objeto->producto.' | '.$objeto->concentracion.' | '.$objeto->adicional,
            'codigo'=>$objeto->codigo,
            'cantidad'=>$objeto->cantidad,
            'vencimiento'=>$objeto->vencimiento,
            'precio_compra'=>$objeto->precio_compra,
            'editable'=>((int)$objeto->ventas_asociadas === 0)
        );
    }

    $json = array(
        'compra'=>array(
            'id'=>$compra_actual->id,
            'codigo'=>$compra_actual->codigo,
            'fecha_compra'=>$compra_actual->fecha_compra,
            'fecha_entrega'=>$compra_actual->fecha_entrega,
            'id_proveedor'=>$compra_actual->id_proveedor,
            'flete'=>$compra_actual->flete
        ),
        'lotes'=>$lotes
    );
    echo json_encode($json);
}
if($_POST['funcion']=='actualizar_compra'){
    $id_compra = isset($_POST['id_compra']) ? (int)$_POST['id_compra'] : 0;
    $descripcion = isset($_POST['descripcionString']) ? json_decode($_POST['descripcionString']) : null;
    $productos = isset($_POST['productosString']) ? json_decode($_POST['productosString']) : null;

    if ($id_compra <= 0 || empty($descripcion) || !is_array($productos)) {
        echo 'error_datos';
        exit;
    }

    $codigo = isset($descripcion->codigo) ? trim($descripcion->codigo) : '';
    $fecha_compra = isset($descripcion->fecha_compra) ? $descripcion->fecha_compra : '';
    $fecha_entrega = isset($descripcion->fecha_entrega) ? $descripcion->fecha_entrega : '';
    $proveedor = isset($descripcion->proveedor) ? (int)$descripcion->proveedor : 0;
    $flete = isset($descripcion->flete) ? (float)$descripcion->flete : 0;

    if ($codigo === '' || $fecha_compra === '' || $fecha_entrega === '' || $proveedor <= 0 || $flete < 0) {
        echo 'error_validacion';
        exit;
    }

    if (count($productos) === 0) {
        echo 'error_sin_productos';
        exit;
    }

    foreach ($productos as $prod) {
        $codigo_lote = isset($prod->codigo) ? trim($prod->codigo) : '';
        $cantidad = isset($prod->cantidad) ? (float)$prod->cantidad : 0;
        $vencimiento = isset($prod->vencimiento) ? $prod->vencimiento : '';
        $precio_compra = isset($prod->precio_compra) ? (float)$prod->precio_compra : 0;
        $id_producto = isset($prod->id) ? (int)$prod->id : 0;

        if ($codigo_lote === '' || $cantidad <= 0 || $vencimiento === '' || $precio_compra <= 0 || $id_producto <= 0) {
            echo 'error_producto';
            exit;
        }
    }

    $ids_editables_actuales = $lote->ids_editables_por_compra($id_compra);
    $ids_enviados = array();
    foreach ($productos as $prod) {
        if (isset($prod->id_lote) && (int)$prod->id_lote > 0) {
            $ids_enviados[] = (int)$prod->id_lote;
        }
    }

    foreach ($ids_enviados as $id_lote_enviado) {
        if (!in_array($id_lote_enviado, $ids_editables_actuales)) {
            echo 'error_lote_bloqueado';
            exit;
        }
    }

    try {
        $conexion = new Conexion();
        $pdo = $conexion->pdo;
        $pdo->beginTransaction();

        $ids_a_eliminar = array_diff($ids_editables_actuales, $ids_enviados);
        foreach ($ids_a_eliminar as $id_lote_eliminar) {
            $lote->eliminar_por_id($id_lote_eliminar);
        }

        foreach ($productos as $prod) {
            if (!isset($prod->id_lote) || (int)$prod->id_lote <= 0) {
                if (!$lote->crear_lote(trim($prod->codigo), (float)$prod->cantidad, $prod->vencimiento, (float)$prod->precio_compra, $id_compra, (int)$prod->id)) {
                    throw new Exception('error_lote');
                }
            }
        }

        $total = $compras->calcular_total($id_compra);
        $compras->actualizar_datos($id_compra, $codigo, $fecha_compra, $fecha_entrega, $proveedor, $total, $flete);

        $pdo->commit();
        echo 'actualizado';
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo 'error_actualizar';
    }
}
if($_POST['funcion']=='eliminar_compra'){
    $id_compra = isset($_POST['id_compra']) ? (int)$_POST['id_compra'] : 0;

    if ($id_compra <= 0) {
        echo 'error_datos';
        exit;
    }

    if ($compras->tiene_ventas_asociadas($id_compra)) {
        echo 'error_venta_asociada';
        exit;
    }

    if ($compras->eliminar($id_compra)) {
        echo 'eliminado';
    } else {
        echo 'error_eliminar';
    }
}
if($_POST['funcion']=='imprimir'){
    $id_compra = $_POST['id'];
    $compras->obtenerDatos($id_compra);
   foreach ($compras->objetos as $objeto) {
        $codigo=$objeto->codigo;
        $fecha_compra=$objeto->fecha_compra;
        $fecha_entrega=$objeto->fecha_entrega;
        $total=$objeto->total;
        $flete=(float)$objeto->flete;
        $estado=$objeto->estado;
        $proveedor=$objeto->proveedor;
        $telefono=$objeto->telefono;
        $correo=$objeto->correo;
        $direccion=$objeto->direccion;
        $avatar='../img/prov/'.$objeto->avatar;
       
   }
   $lote->ver($id_compra);
   $plantilla='
    <body>
    <header class="clearfix">
      <div id="logo">
        <img src="'.$avatar.'" width="60" height="60">
      </div>
      <h1>COMPRA N. '.$codigo.'</h1>
      <div id="company" class="clearfix">
        <div id="negocio">'.$proveedor.'</div>
        <div>'.$direccion.'</div>
        <div>'.$telefono.'</div>
        <div><a href="mailto:company@example.com">'.$correo.'</a></div>
      </div>';
      $plantilla.='
    
      <div id="project">
        <div><span>Codigo de compra: </span>'.$codigo.'</div>
        <div><span>Fecha compra: </span>'.$fecha_compra.'</div>
        <div><span>Fecha entrega: </span>'.$fecha_entrega.'</div>
        <div><span>Estado: </span>'.$estado.'</div>
      </div>';
    
    $plantilla.='
    </header>
    <main>
      <table>
        <thead>
          <tr>
           
            <th class="service">#</th>
            <th class="service">Codigo</th>
            <th class="service">Cantidad</th>
            <th class="service">Vencimiento</th>
            <th class="service">Precio de compra</th>
            <th class="service">Producto</th>
            <th class="service">Laboratorio</th>
            <th class="service">Presentacion</th>
            <th class="service">Tipo</th>
          </tr>
        </thead>
        <tbody>';
        foreach ($lote->objetos as $objeto) {
         
          $plantilla.='<tr>
            
            <td class="servic">'.$objeto->producto.'</td>
            <td class="servic">'.$objeto->codigo.'</td>
            <td class="servic">'.$objeto->cantidad.'</td>
            <td class="servic">'.$objeto->vencimiento.'</td>
            <td class="servic">'.$objeto->precio_compra.'</td>
            <td class="servic">'.$objeto->producto.'|'.$objeto->concentracion.'|'.$objeto->adicional.'</td>
            <td class="servic">'.$objeto->laboratorio.'</td>
            <td class="servic">'.$objeto->presentacion.'</td>
            <td class="servic">'.$objeto->tipo.'</td>
          </tr>';
        }

          $igv=$total*0.18;
          $sub=$total-$igv;
          
          $plantilla.='
          <tr>
            <td colspan="8" class="grand total">SUBTOTAL</td>
            <td class="grand total">$'.number_format($sub,0,',','.').'</td>
          </tr>
          <tr>
            <td colspan="8" class="grand total">IGV(18%)</td>
            <td class="grand total">$'.number_format($igv,0,',','.').'</td>
          </tr>
          <tr>
            <td colspan="8" class="grand total">FLETE</td>
            <td class="grand total">$'.number_format($flete,0,',','.').'</td>
          </tr>
          <tr>
            <td colspan="8" class="grand total">TOTAL</td>
            <td class="grand total">$'.number_format($total+$flete,0,',','.').'</td>
          </tr>';


       $plantilla.='
        </tbody>
      </table>
      <div id="notices">
        <div>NOTICE:</div>
        <div class="notice">*.</div>

      </div>
    </main>
    <footer>
      Created by Warpiece (Juan Diego Polo Cosme) Ingeniero Informatico y Analista desarrollador.
    </footer>
  </body>';

    $css = file_get_contents("../css/compras.css");
    $mpdf = new \Mpdf\Mpdf();
    $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
    $mpdf->WriteHTML($plantilla, \Mpdf\HTMLParserMode::HTML_BODY);
    $mpdf->Output("../pdf/pdf-compra-".$id_compra.".pdf","F");
    
}
