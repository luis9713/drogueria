<?php
include_once '../modelo/Venta.php';
include_once '../modelo/Cliente.php';
$cliente = new Cliente();
$venta = new Venta();
session_start();
if(!isset($_SESSION['usuario'])){
    echo json_encode(array('error' => 'no_sesion'));
    exit;
}
if(!isset($_POST['funcion'])){
    exit;
}
$id_usuario =  $_SESSION['usuario'];
if($_POST['funcion']=='listar_creditos'){
    $venta->buscar_credito();
    $json=array('data'=>array());
    foreach ($venta->objetos as $objeto) {
        if (empty($objeto->id_cliente)) {
            $cliente_nombre=$objeto->cliente;
            $cliente_dni=$objeto->dni;
        }
        else{
            $cliente->buscar_datos_cliente($objeto->id_cliente);
            foreach ($cliente->objetos as $cli) {
                $cliente_nombre=$cli->nombre.' '.$cli->apellidos;
                $cliente_dni=$cli->dni;
            }
           
        }
        $json['data'][]=array(
            'id_venta'=>$objeto->id_venta,
            'fecha'=>$objeto->fecha,
            'cliente'=>$cliente_nombre,
            'dni'=>$cliente_dni,
            'total'=>(float)$objeto->total,
            'vendedor'=>$objeto->vendedor,
            'tipo_pago'=>$objeto->tipo_pago,
            'depositado'=>(float)$objeto->depositado
        );
    }
    $jsonstring = json_encode($json);
    echo $jsonstring;
}
if($_POST['funcion']=='listar_contado'){
    $venta->buscar_contado();
    $json=array('data'=>array());
    foreach ($venta->objetos as $objeto) {
        if (empty($objeto->id_cliente)) {
            $cliente_nombre=$objeto->cliente;
            $cliente_dni=$objeto->dni;
        }
        else{
            $cliente->buscar_datos_cliente($objeto->id_cliente);
            foreach ($cliente->objetos as $cli) {
                $cliente_nombre=$cli->nombre.' '.$cli->apellidos;
                $cliente_dni=$cli->dni;
            }
           
        }
        $json['data'][]=array(
            'id_venta'=>$objeto->id_venta,
            'fecha'=>$objeto->fecha,
            'cliente'=>$cliente_nombre,
            'dni'=>$cliente_dni,
            'total'=>(float)$objeto->total,
            'vendedor'=>$objeto->vendedor,
            'tipo_pago'=>$objeto->tipo_pago
        );
    }
    $jsonstring = json_encode($json);
    echo $jsonstring;
}
if($_POST['funcion']=='listar'){
    $venta->buscar();
    $json=array('data'=>array());
    foreach ($venta->objetos as $objeto) {
        if (empty($objeto->id_cliente)) {
            $cliente_nombre=$objeto->cliente;
            $cliente_dni=$objeto->dni;
        }
        else{
            $cliente->buscar_datos_cliente($objeto->id_cliente);
            foreach ($cliente->objetos as $cli) {
                $cliente_nombre=$cli->nombre.' '.$cli->apellidos;
                $cliente_dni=$cli->dni;
            }
           
        }
        $json['data'][]=array(
            'id_venta'=>$objeto->id_venta,
            'fecha'=>$objeto->fecha,
            'cliente'=>$cliente_nombre,
            'dni'=>$cliente_dni,
            'total'=>(float)$objeto->total,
            'vendedor'=>$objeto->vendedor,
            'tipo_pago'=>$objeto->tipo_pago
        );
    }
    $jsonstring = json_encode($json);
    echo $jsonstring;
}
if($_POST['funcion']=='monstrar_consultas'){
    $venta_dia_vendedor = 0;
    $venta_diaria = 0;
    $venta_mensual = 0;
    $monto_costo = 0;
    $venta_anual = 0;

    $venta->venta_dia_vendedor($id_usuario);
    foreach ($venta->objetos as $objeto) {
        $venta_dia_vendedor=(float)$objeto->venta_dia_vendedor;
    }
    $venta->venta_diaria();
    foreach ($venta->objetos as $objeto) {
        $venta_diaria=(float)$objeto->venta_diaria;
    }
    $venta->venta_mensual();
    foreach ($venta->objetos as $objeto) {
        $venta_mensual=(float)$objeto->venta_mensual;
    }
    $venta->monto_costo();
    foreach ($venta->objetos as $objeto) {
        $monto_costo=(float)$objeto->monto_costo;
    }

    $venta->venta_anual();
    foreach ($venta->objetos as $objeto) {
        $venta_anual=(float)$objeto->venta_anual;
    }
    $json = array(
        'venta_dia_vendedor'=>$venta_dia_vendedor,
        'venta_diaria'=>$venta_diaria,
        'venta_mensual'=>$venta_mensual,
        'venta_anual'=>$venta_anual,
        'ganancia_mensual'=>$venta_mensual - $monto_costo
    );
    $jsonstring = json_encode($json);
    echo $jsonstring;
}
if($_POST['funcion']=='venta_mes'){
    $venta->venta_mes();
    $json=array();
    foreach ($venta->objetos as $objeto) {
        $json[]=$objeto;
    }
    $jsonstring = json_encode($json);
    echo $jsonstring;
}
if($_POST['funcion']=='vendedor_mes'){
    $venta->vendedor_mes();
    $json=array();
    foreach ($venta->objetos as $objeto) {
        $json[]=$objeto;
    }
    $jsonstring = json_encode($json);
    echo $jsonstring;
}
if($_POST['funcion']=='ventas_anual'){
    $venta->ventas_anual();
    $json=array();
    foreach ($venta->objetos as $objeto) {
        $json[]=$objeto;
    }
    $jsonstring = json_encode($json);
    echo $jsonstring;
}
if($_POST['funcion']=='producto_mas_vendido'){
    $venta->producto_mas_vendido();
    $json=array();
    foreach ($venta->objetos as $objeto) {
        $json[]=$objeto;
    }
    $jsonstring = json_encode($json);
    echo $jsonstring;
}
if($_POST['funcion']=='cliente_mes'){
    $venta->cliente_mes();
    $json=array();
    foreach ($venta->objetos as $objeto) {
        $json[]=$objeto;
    }
    $jsonstring = json_encode($json);
    echo $jsonstring;
}
if($_POST['funcion']=='estadisticas_creditos'){
    $venta->estadisticas_creditos();
    $json=array(
        'total_creditos'=>0,
        'monto_total_creditos'=>0,
        'monto_depositado'=>0,
        'saldo_por_cobrar'=>0
    );
    foreach ($venta->objetos as $objeto) {
        $json=array(
            'total_creditos'=>(int)$objeto->total_creditos,
            'monto_total_creditos'=>(float)$objeto->monto_total_creditos,
            'monto_depositado'=>(float)$objeto->monto_depositado,
            'saldo_por_cobrar'=>(float)$objeto->saldo_por_cobrar
        );
    }
    $jsonstring = json_encode($json);
    echo $jsonstring;
}