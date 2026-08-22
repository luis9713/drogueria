<?php 
include '../modelo/Producto.php';
require_once('../vendor/autoload.php');
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
session_start();
if(!isset($_SESSION['usuario'])){
    echo 'error_sesion';
    exit;
}
if(!isset($_POST['funcion'])){
    exit;
}
$producto=new Producto();
if($_POST['funcion']=='crear'){
    $nombre = $_POST['nombre'];
    $concentracion = $_POST['concentracion'];
    $adicional = $_POST['adicional'];
    $precio = $_POST['precio'];
    $laboratorio = $_POST['laboratorio'];
    $tipo = $_POST['tipo'];
    $presentacion = $_POST['presentacion'];
    $avatar='prod_default.png';
    $producto->crear($nombre,$concentracion,$adicional,$precio,$laboratorio,$tipo,$presentacion,$avatar);
}
if($_POST['funcion']=='editar'){
    $id=$_POST['id'];
    $nombre = $_POST['nombre'];
    $concentracion = $_POST['concentracion'];
    $adicional = $_POST['adicional'];
    $precio = $_POST['precio'];
    $laboratorio = $_POST['laboratorio'];
    $tipo = $_POST['tipo'];
    $presentacion = $_POST['presentacion'];
    $producto->editar($id,$nombre,$concentracion,$adicional,$precio,$laboratorio,$tipo,$presentacion);
}
if($_POST['funcion']=='buscar'){
    $producto->buscar();
    $todos_productos = $producto->objetos;  // Guardar antes de sobrescribir
    $json=array();
    foreach ($todos_productos as $objeto) {
        $producto->obtener_stock($objeto->id_producto);
        $total = 0;  // Default a 0 si no hay stock
        if(!empty($producto->objetos)){
            foreach ($producto->objetos as $obj) {
                if($obj->total !== null){
                    $total = $obj->total;
                }
            }
        }
        // Mostrar TODOS los productos sin restricción de stock
        $json[]=array(
            'id'=>$objeto->id_producto,
            'nombre'=>$objeto->nombre,
            'concentracion'=>$objeto->concentracion,
            'adicional'=>$objeto->adicional,
            'precio'=>$objeto->precio,
            'stock'=>$total,
            'laboratorio'=>$objeto->laboratorio,
            'tipo'=>$objeto->tipo,
            'presentacion'=>$objeto->presentacion,
            'laboratorio_id'=>$objeto->prod_lab,
            'tipo_id'=>$objeto->prod_tip_prod,
            'presentacion_id'=>$objeto->prod_present,
            'avatar'=>'../img/prod/'.$objeto->avatar,
        );
    }
    $jsonstring = json_encode($json);
    echo $jsonstring;
}
if($_POST['funcion']=='buscar_gestion'){
    $producto->buscar();
    $todos_productos = $producto->objetos;  // Guardar antes de sobrescribir
    $json=array();
    foreach ($todos_productos as $objeto) {
        $producto->obtener_stock($objeto->id_producto);
        $total = 0;  // Default a 0 si no hay stock
        if(!empty($producto->objetos)){
            foreach ($producto->objetos as $obj) {
                if($obj->total !== null){
                    $total = $obj->total;
                }
            }
        }
        // Mostrar TODOS los productos sin restricción de stock
        $json[]=array(
            'id'=>$objeto->id_producto,
            'nombre'=>$objeto->nombre,
            'concentracion'=>$objeto->concentracion,
            'adicional'=>$objeto->adicional,
            'precio'=>$objeto->precio,
            'stock'=>$total,
            'laboratorio'=>$objeto->laboratorio,
            'tipo'=>$objeto->tipo,
            'presentacion'=>$objeto->presentacion,
            'laboratorio_id'=>$objeto->prod_lab,
            'tipo_id'=>$objeto->prod_tip_prod,
            'presentacion_id'=>$objeto->prod_present,
            'avatar'=>'../img/prod/'.$objeto->avatar,
        );
    }
    $jsonstring = json_encode($json);
    echo $jsonstring;
}
if($_POST['funcion']=='cambiar_avatar'){
    $id=$_POST['id_logo_prod'];
    $avatar=$_POST['avatar'];
    if(($_FILES['photo']['type']=='image/jpeg')||($_FILES['photo']['type']=='image/png')||($_FILES['photo']['type']=='image/gif')){
        $nombre=uniqid().'-'.$_FILES['photo']['name'];
        $ruta='../img/prod/'.$nombre;
        move_uploaded_file($_FILES['photo']['tmp_name'],$ruta);
        $producto->cambiar_logo($id,$nombre);
        if($avatar!='../img/prod/prod_default.png'){
            unlink($avatar);
        }
        
        $json= array();
        $json[]=array(
            'ruta'=>$ruta,
            'alert'=>'edit'
        );
        $jsonstring = json_encode($json[0]);
        echo $jsonstring;
    }
    else{
        $json= array();
        $json[]=array(
            'alert'=>'noedit'
        );
        $jsonstring = json_encode($json[0]);
        echo $jsonstring;
    }
}
if($_POST['funcion']=='borrar'){
    $id=$_POST['id'];
    $producto->borrar($id);
}
if($_POST['funcion']=='descontinuar_sin_stock'){
    $id=(int)$_POST['id'];
    echo $producto->descontinuar_sin_stock($id);
}
if($_POST['funcion']=='buscar_inactivos'){
    $consulta=isset($_POST['consulta']) ? trim($_POST['consulta']) : '';
    $producto->buscar_inactivos($consulta);
    echo json_encode($producto->objetos);
}
if($_POST['funcion']=='reactivar'){
    $id=(int)$_POST['id'];
    echo $producto->reactivar($id);
}
if($_POST['funcion']=='buscar_id'){
    $id=$_POST['id_producto'];
    $producto->buscar_id($id);
    $json=array();
    foreach ($producto->objetos as $objeto) {
        $producto->obtener_stock($objeto->id_producto);
        foreach ($producto->objetos as $obj) {
            $total = $obj->total;
        }
        $json[]=array(
            'id'=>$objeto->id_producto,
            'nombre'=>$objeto->nombre,
            'concentracion'=>$objeto->concentracion,
            'adicional'=>$objeto->adicional,
            'precio'=>$objeto->precio,
            'stock'=>$total,
            'laboratorio'=>$objeto->laboratorio,
            'tipo'=>$objeto->tipo,
            'presentacion'=>$objeto->presentacion,
            'laboratorio_id'=>$objeto->prod_lab,
            'tipo_id'=>$objeto->prod_tip_prod,
            'presentacion_id'=>$objeto->prod_present,
            'avatar'=>'../img/prod/'.$objeto->avatar,

        );
    }
    $jsonstring = json_encode($json[0]);
    echo $jsonstring;
}
if($_POST['funcion']=='verificar_stock'){
    $error=0;
    $productos=json_decode($_POST['productos']);
    foreach ($productos as $objeto) {
        $producto->obtener_stock($objeto->id);
        foreach ($producto->objetos as $obj) {
            $total=$obj->total;
        }
        if($total>=$objeto->cantidad && $objeto->cantidad>0){
            $error=$error+0;
        }
        else{
            $error=$error+1;
        }
    }
    echo $error;

}
if($_POST['funcion']=='traer_productos'){
    $html="";
    $productos=json_decode($_POST['productos']);
    foreach ($productos as $resultado) {
        $producto->buscar_id($resultado->id);
        foreach ($producto->objetos as $objeto) {
            if($resultado->cantidad==''){
                $resultadoCantidad=0;
            }
            else{
                $resultadoCantidad=$resultado->cantidad;
            }
            $subtotal=$objeto->precio*$resultadoCantidad;
            $producto->obtener_stock($objeto->id_producto);
            foreach ($producto->objetos as $obj) {
                $stock=$obj->total;
            }
            // Formatear números con separador de miles
            $precio_formateado = number_format($objeto->precio, 0, ',', '.');
            $subtotal_formateado = number_format($subtotal, 0, ',', '.');
           $html.="
           <tr prodId='$objeto->id_producto' prodPrecio='$objeto->precio'>
              <td>$objeto->nombre</td>
              <td>$stock</td>
              <td class='precio'>$precio_formateado</td>
              <td>$objeto->concentracion</td>
              <td>$objeto->adicional</td>
              <td>$objeto->laboratorio</td>
              <td>$objeto->presentacion</td>
              <td>
                <input type='number' min='1' class='form-control cantidad_producto' value='$resultado->cantidad'>
              </td>
              <td class='subtotales'>
                <h5>$subtotal_formateado</h5>
              </td>
              <td><button class='borrar-producto btn btn-danger'><i class='fas fa-times-circle'></i></button></td>
        </tr>
           ";
        }
    }
    echo  $html;
}
if($_POST['funcion']=='reporte_productos'){
    date_default_timezone_set('America/Bogota');
    $fecha = date('Y-m-d H:i:s');
    $html = '
    <header>
        <div id="logo">
        <img src="../img/logo.png" width="60" height="60">
        </div>
        <h1>REPORTE DE PRODUCTOS</h1>
        <div id="project">
            <div>
                <span>Fecha y Hora: </span>'.$fecha.'
            </div>
        </div>
    </header>
    <table>
        <thead>
            <tr>
                <th>N*</th>
                <th>Producto</th>
                <th>Concentracion</th>
                <th>Adicional</th>
                <th>Laboratorio</th>
                <th>Presentacion</th>
                <th>Tipo</th>
                <th>Stock</th>
                <th>Precio</th>
            </tr>
        </thead>
        <tbody>

        
    ';
    $producto->reporte_producto();
    $contador=0;
    foreach ($producto->objetos as $objeto) {
        $contador++;
        $producto->obtener_stock($objeto->id_producto);
            foreach ($producto->objetos as $obj) {
                $stock=$obj->total;
            }   
        $html.='
        <tr>
            <td class="servic">'.$contador.'</td>    
            <td class="servic">'.$objeto->nombre.'</td>
            <td class="servic">'.$objeto->concentracion.'</td>
            <td class="servic">'.$objeto->adicional.'</td>
            <td class="servic">'.$objeto->laboratorio.'</td>
            <td class="servic">'.$objeto->presentacion.'</td>
            <td class="servic">'.$objeto->tipo.'</td>
            <td class="servic">'.$stock.'</td>
            <td class="servic">'.$objeto->precio.'</td>
        </tr>
        ';
    }
    $html.='
        </tbody>
    </table>
    ';
    $css = file_get_contents("../css/pdf.css");
    $mpdf = new \Mpdf\Mpdf();
    $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
    $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
    $mpdf->Output("../pdf/pdf-".$_POST['funcion'].".pdf","F");
}
if($_POST['funcion']=='reporte_productosExcel'){
    $nombre_achivo = 'reporte_productos.xlsx';
    $producto->reporte_producto();
    $contador=0;
    foreach ($producto->objetos as $objeto) {
        $contador++;
        $producto->obtener_stock($objeto->id_producto);
            foreach ($producto->objetos as $obj) {
                $stock=$obj->total;
            }   
            $json[]=array(
                'N'=>$contador,
                'nombre'=>$objeto->nombre,
                'concentracion'=>$objeto->concentracion,
                'adicional'=>$objeto->adicional,
                'laboratorio'=>$objeto->laboratorio,
                'presentacion'=>$objeto->presentacion,
                'tipo'=>$objeto->tipo,
                'stock'=>$stock,
                'precio'=>$objeto->precio
            );
    }
    $spreadsheet = new Spreadsheet();
    $Sheet = $spreadsheet->getActiveSheet();
    $Sheet->setTitle('Reporte de productos');
    $Sheet->setCellValue('A1','Reporte de productos en Excel');
    $Sheet->getStyle('A1')->getFont()->setSize(17);
    $Sheet->fromArray(array_keys($json[0]),NULL,'A4');
    $Sheet->getStyle('A4:I4')
    ->getFill()
    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
    ->getStartColor()
    ->setARGB('2D9F39');
    $Sheet->getStyle('A4:I4')
    ->getFont()
    ->getColor()
    ->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE);
    foreach ($json  as $key => $producto) {
        $celda = (int)$key+5;
        if($producto['stock']==''){
            $Sheet->getStyle('A'.$celda.':I'.$celda)
            ->getFont()
            ->getColor()
            ->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);
        }
        $Sheet->setCellValue('A'.$celda,$producto['N']);
        $Sheet->setCellValue('B'.$celda,$producto['nombre']);
        $Sheet->setCellValue('C'.$celda,$producto['concentracion']);
        $Sheet->setCellValue('D'.$celda,$producto['adicional']);
        $Sheet->setCellValue('E'.$celda,$producto['laboratorio']);
        $Sheet->setCellValue('F'.$celda,$producto['presentacion']);
        $Sheet->setCellValue('G'.$celda,$producto['tipo']);
        $Sheet->setCellValue('H'.$celda,$producto['stock']);
        $Sheet->setCellValue('I'.$celda,$producto['precio']);
    }
    foreach (range('B','I') as $col) {
       $Sheet->getColumnDimension($col)->setAutoSize(true);
    }
    $writer = IOFactory::createWriter($spreadsheet,'Xlsx');
    $writer->save('../Excel/'.$nombre_achivo);
}
if($_POST['funcion']=='rellenar_productos'){
    $producto->rellenar_productos();
    $json = array();
    foreach ($producto->objetos as $objeto) {
        
        $json[]=array(
           
            'nombre'=>$objeto->id_producto.' | '.$objeto->nombre.' | '.$objeto->concentracion.' | '.$objeto->adicional.' | '.$objeto->laboratorio.' | '.$objeto->presentacion
        );
    }
    $jsonstring = json_encode($json);
    echo $jsonstring;
}

?>