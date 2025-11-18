<?php
include_once '../modelo/Compras.php';
include_once '../modelo/Lote.php';
require_once('../vendor/autoload.php');
$lote = new Lote();
$compras = new Compras();
if($_POST['funcion']=='registrar_compra'){
    $descripcion = json_decode($_POST['descripcionString']);
    $productos = json_decode($_POST['productosString']);
    $compras->crear($descripcion->codigo,$descripcion->fecha_compra,$descripcion->fecha_entrega,$descripcion->total,$descripcion->estado,$descripcion->proveedor);
    $compras->ultima_compra();
    foreach ($compras->objetos as $objeto) {
        $id_compra = $objeto->ultima_compra;
    }
    foreach ($productos as $prod) {
       $lote->crear_lote($prod->codigo,$prod->cantidad,$prod->vencimiento,$prod->precio_compra,$id_compra,$prod->id);
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
           'total'=> $objeto->total,
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
if($_POST['funcion']=='imprimir'){
    $id_compra = $_POST['id'];
    $compras->obtenerDatos($id_compra);
   foreach ($compras->objetos as $objeto) {
        $codigo=$objeto->codigo;
        $fecha_compra=$objeto->fecha_compra;
        $fecha_entrega=$objeto->fecha_entrega;
        $total=$objeto->total;
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
            <td class="grand total">S/.'.$sub.'</td>
          </tr>
          <tr>
            <td colspan="8" class="grand total">IGV(18%)</td>
            <td class="grand total">S/.'.$igv.'</td>
          </tr>
          <tr>
            <td colspan="8" class="grand total">TOTAL</td>
            <td class="grand total">S/.'.$total.'</td>
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
