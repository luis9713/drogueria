<?php
include '../modelo/Cliente.php';
$cliente = new Cliente();
if($_POST['funcion']=='buscar'){
    $cliente->buscar();
    date_default_timezone_set('America/Bogota');
    $fecha= date('Y-m-d H:i:s');
    $fecha_actual = new DateTime($fecha);
    $json=array();
    foreach ($cliente->objetos as $objeto) {
        $nac = new DateTime($objeto->edad);
        $edad = $nac->diff($fecha_actual);
        $edad_y = $edad->y;
        $json[]=array(
            'id'=>$objeto->id,
            'nombre'=>$objeto->nombre.' '.$objeto->apellidos,
            'dni'=>$objeto->dni,
            'edad'=>$edad_y,
            'telefono'=>$objeto->telefono,
            'correo'=>$objeto->correo,
            'sexo'=>$objeto->sexo,
            'adicional'=>$objeto->adicional,
            'avatar'=>'../img/avatar.png'
        );
    }
    $jsonstring = json_encode($json);
    echo $jsonstring;
}
if($_POST['funcion']=='crear'){
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $dni = $_POST['dni'];
    $edad = $_POST['edad'];
    $telefono = $_POST['telefono'];
    $correo = $_POST['correo'];
    $sexo = $_POST['sexo'];
    $adicional = $_POST['adicional'];
    $avatar = 'avatar.png';

    $cliente->crear($nombre,$apellido,$dni,$edad,$telefono,$correo,$sexo,$adicional,$avatar);
}
if($_POST['funcion']=='editar'){
    
    $id = $_POST['id'];
    $telefono = $_POST['telefono'];
    $correo = $_POST['correo'];
    $adicional = $_POST['adicional'];

    $cliente->editar($id,$telefono,$correo,$adicional);
}
if($_POST['funcion']=='borrar'){
    $id=$_POST['id'];
    $cliente->borrar($id);
}
if($_POST['funcion']=='rellenar_clientes'){
    $cliente->rellenar_clientes();
    $json = array();
    foreach ($cliente->objetos as $objeto) {
        $json[]=array(
            'id'=>$objeto->id,
            'nombre'=>$objeto->nombre.' '.$objeto->apellidos.' | '.$objeto->dni
        );
    }
    $jsonstring = json_encode($json);
    echo $jsonstring;
}