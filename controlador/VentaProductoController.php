<?php
include_once '../modelo/VentaProducto.php';
include_once '../modelo/Venta.php';
session_start();
if(!isset($_SESSION['usuario'])){
    echo 'error_sesion';
    exit;
}
if(!isset($_POST['funcion'])){
    exit;
}
$venta_producto = new VentaProducto();
$venta = new Venta();
if($_POST['funcion']=='ver'){
    $id=$_POST['id'];
    
    // Verificar si es un depósito y obtener la venta original
    $venta->buscar_por_id($id);
    $id_venta_original = $id;
    
    foreach ($venta->objetos as $objeto) {
        // Si es un depósito, extraer el ID de la venta original
        if(strpos($objeto->tipo_pago, 'Deposito_') === 0) {
            $id_venta_original = str_replace('Deposito_', '', $objeto->tipo_pago);
        }
    }
    
    // Ver los productos de la venta (original o la misma si no es depósito)
    $venta_producto->ver($id_venta_original);
    $json=array();
    foreach ($venta_producto->objetos as $objeto) {
        $json[]=$objeto;
    }
    $jsonstring = json_encode($json);
    echo $jsonstring;
}