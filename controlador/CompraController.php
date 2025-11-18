<?php
include '../modelo/Venta.php';
include_once '../modelo/Conexion.php';
$venta = new Venta();
session_start();
$vendedor = $_SESSION['usuario'];
if($_POST['funcion']=='depositar'){
    $id=$_POST['id'];
    $pago=$_POST['pago'];
    $fecha = date('Y-m-d H:i:s');

    $venta->buscar_credito_id($id);

    foreach ($venta->objetos as $objeto) {
        $id_cliente=$objeto->id_cliente;
        $total=(float)$objeto->total;
        $depositado=(float) $objeto->depositado;
    }

    $tipo_pago = "Credito";

    if ($depositado + $pago >= $total) {
        $pago = $total - $depositado;
        $tipo_pago = $tipo_pago . "_Pagado";
    }

    $venta->Crear($id_cliente,$pago,$fecha,$vendedor,"Deposito_" . $id,$pago);

    

    $venta->Depositar($id,$depositado + $pago, $tipo_pago);
    
}
if($_POST['funcion']=='registrar_compra'){
    $total=$_POST['total'];
    $cliente=$_POST['cliente'];
    $productos=json_decode($_POST['json']);
    $tipo_pago=$_POST['tipo_pago'];
    $pago=(float)$_POST['pago'];
    date_default_timezone_set('America/Bogota');
    $fecha = date('Y-m-d H:i:s');
    $venta->Crear($cliente,$total,$fecha,$vendedor,$tipo_pago,$pago);
    $venta->ultima_venta();

    foreach ($venta->objetos as $objeto) {
        $id_venta = $objeto->ultima_venta;
        //echo $id_venta;
    }

    if(strcmp($tipo_pago, "Credito") == 0 && $pago > 0.0) {  
        $venta->Crear($cliente,$pago,$fecha,$vendedor,"Deposito_" . $id_venta ,$pago);
    }
    
    try {
        $db= new Conexion();
        $conexion = $db->pdo;
        $conexion->beginTransaction();
        foreach ($productos as $prod) {
           $cantidad = $prod->cantidad;
           while ($cantidad!=0) {
                $sql="SELECT * FROM lote where vencimiento = (SELECT MIN(vencimiento) FROM lote where id_producto=:id and estado='A') and id_producto=:id";
                $query = $conexion->prepare($sql);
                $query->execute(array(':id'=>$prod->id));
                $lote=$query->fetchall();

                foreach ($lote as $lote) {
                    $sql="SELECT compra.id_proveedor as proveedor FROM lote
                    JOIN compra on lote.id_compra = compra.id and lote.id=:id";
                    $query = $conexion->prepare($sql);
                    $query->execute(array(':id'=>$lote->id));
                    $prov=$query->fetchall();
                    $proveedor = $prov[0]->proveedor;
                   if($cantidad<$lote->cantidad_lote){
                       $sql="INSERT INTO detalle_venta(det_cantidad,det_vencimiento,id__det_lote,id__det_prod,lote_id_prov,id_det_venta) values ('$cantidad','$lote->vencimiento','$lote->id','$prod->id','$proveedor','$id_venta')";
                       $conexion->exec($sql);
                       $conexion->exec("UPDATE lote SET cantidad_lote= cantidad_lote-'$cantidad' where id='$lote->id'");
                       $cantidad=0;
                   }
                   if($cantidad==$lote->cantidad_lote){
                        $sql="INSERT INTO detalle_venta(det_cantidad,det_vencimiento,id__det_lote,id__det_prod,lote_id_prov,id_det_venta) values ('$cantidad','$lote->vencimiento','$lote->id','$prod->id','$proveedor','$id_venta')";
                        $conexion->exec($sql);
                        $conexion->exec("UPDATE lote SET estado='I',cantidad_lote=0 where id='$lote->id'");
                        $cantidad=0;
                    }
                    if($cantidad>$lote->cantidad_lote){
                        $sql="INSERT INTO detalle_venta(det_cantidad,det_vencimiento,id__det_lote,id__det_prod,lote_id_prov,id_det_venta) values ('$lote->cantidad_lote','$lote->vencimiento','$lote->id','$prod->id','$proveedor','$id_venta')";
                        $conexion->exec($sql);
                       $conexion->exec("UPDATE lote SET estado='I',cantidad_lote=0 where id='$lote->id'");
                        $cantidad=$cantidad-$lote->cantidad_lote;
                    }
                }
            }
            $subtotal = $prod->cantidad*$prod->precio;
            $conexion->exec("INSERT INTO venta_producto(precio,cantidad,subtotal,producto_id_producto,venta_id_venta) values('$prod->precio','$prod->cantidad','$subtotal','$prod->id','$id_venta')");
        }
        $conexion->commit();

    } catch (Exception $error) {
       
        $conexion->rollBack();
        $venta->borrar($id_venta);
        echo $error->getMessage();
    }

}