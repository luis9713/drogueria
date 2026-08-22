<?php
include '../modelo/Venta.php';
include '../modelo/Caja.php';
include_once '../modelo/Conexion.php';
$venta = new Venta();
$caja = new Caja();
session_start();
if(!isset($_SESSION['usuario'])){
    echo 'error_sesion';
    exit;
}
if(!isset($_POST['funcion'])){
    exit;
}
$vendedor = $_SESSION['usuario'];

// Obtener id de caja abierta
$caja_abierta = $caja->verificar_caja_abierta();
$id_caja = null;
if (!empty($caja_abierta)) {
    $id_caja = $caja_abierta[0]->id_caja;
}
if($_POST['funcion']=='depositar'){
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $pago = isset($_POST['pago']) ? (float)$_POST['pago'] : 0;
    $medio_pago = isset($_POST['medio_pago']) ? $_POST['medio_pago'] : 'Efectivo';
    if ($medio_pago !== 'Nequi' && $medio_pago !== 'Efectivo') {
        $medio_pago = 'Efectivo';
    }
    if ($id <= 0 || $pago <= 0) {
        echo 'error_monto';
        return;
    }
    if ($id_caja === null) {
        echo 'error_caja_cerrada';
        return;
    }
    date_default_timezone_set('America/Bogota');
    $fecha = date('Y-m-d H:i:s');

    $venta->buscar_credito_id($id);

    if (empty($venta->objetos)) {
        echo 'error_credito';
        return;
    }

    $id_cliente = 0;
    $total = 0;
    $depositado = 0;

    foreach ($venta->objetos as $objeto) {
        $id_cliente=$objeto->id_cliente;
        $total=(float)$objeto->total;
        $depositado=(float) $objeto->depositado;
    }

    if ($depositado >= $total) {
        echo 'ya_pagado';
        return;
    }

    $tipo_pago = "Credito";

    if ($depositado + $pago >= $total) {
        $pago = $total - $depositado;
        $tipo_pago = $tipo_pago . "_Pagado";
    }

    $venta->Crear($id_cliente,$pago,$fecha,$vendedor,"Deposito_" . $id,$pago,$id_caja,$medio_pago);

    

    $venta->Depositar($id,$depositado + $pago, $tipo_pago);
    echo 'success';
    
}
if($_POST['funcion']=='registrar_compra'){
    $total=(float)$_POST['total'];
    $cliente=(int)$_POST['cliente'];
    $productos=json_decode($_POST['json']);
    $tipo_pago=$_POST['tipo_pago'];
    $pago=(float)$_POST['pago'];
    $pago_efectivo = isset($_POST['pago_efectivo']) ? (float)$_POST['pago_efectivo'] : 0;
    $pago_nequi = isset($_POST['pago_nequi']) ? (float)$_POST['pago_nequi'] : 0;
    $medio_pago_venta = 'Efectivo';
    date_default_timezone_set('America/Bogota');
    $fecha = date('Y-m-d H:i:s');

    // Pago mixto: se guarda como tipo Mixto y en `depositado` se almacena la parte Nequi.
    if ($tipo_pago === 'Mixto') {
        if ($pago_nequi < 0 || $pago_efectivo < 0 || $pago_nequi > (float)$total || ($pago_nequi + $pago_efectivo) < (float)$total) {
            echo 'error_mixto';
            return;
        }
        $pago = $pago_nequi;
        $medio_pago_venta = 'Mixto';
    } else if ($tipo_pago === 'Nequi') {
        $medio_pago_venta = 'Nequi';
    } else if ($tipo_pago === 'Credito') {
        $medio_pago_venta = 'Credito';
        if ($pago < 0) {
            echo 'error_monto_credito';
            return;
        }
        if ($pago > (float)$total) {
            $pago = (float)$total;
        }
    }

    $venta->Crear($cliente,$total,$fecha,$vendedor,$tipo_pago,$pago,$id_caja,$medio_pago_venta);
    $venta->ultima_venta();

    foreach ($venta->objetos as $objeto) {
        $id_venta = $objeto->ultima_venta;
        //echo $id_venta;
    }

    if(strcmp($tipo_pago, "Credito") == 0) {
        if ($pago > 0.0) {
            $venta->Crear($cliente,$pago,$fecha,$vendedor,"Deposito_" . $id_venta ,$pago,$id_caja,'Efectivo');
        }
        if ($pago >= (float)$total) {
            $venta->Depositar($id_venta, (float)$total, 'Credito_Pagado');
        }
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
        echo 'success';

    } catch (Exception $error) {
       
        $conexion->rollBack();
        $venta->borrar($id_venta);
        echo 'error_venta';
    }

}