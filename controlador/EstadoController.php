<?php
include '../modelo/Estado.php';
session_start();
if(!isset($_SESSION['usuario'])){
    echo 'error_sesion';
    exit;
}
if(!isset($_POST['funcion'])){
    exit;
}
$estado = new Estado();

if($_POST['funcion']=='rellenar_estado'){
    $estado->rellenar_estado();
    $json = array();
    foreach ($estado->objetos as $objeto) {
        $json[]=array(
            'id'=>$objeto->id,
            'nombre'=>$objeto->nombre
        );
    }
    $jsonstring = json_encode($json);
    echo $jsonstring;

}
if($_POST['funcion']=='cambiarEstado'){
    $nombre = $_POST['estado'];
    $estado->obtenerId($nombre);
    $json = array();
    foreach ($estado->objetos as $objeto) {
        $json[]=array(
            'id'=>$objeto->id
            
        );
    }
    $jsonstring = json_encode($json);
    echo $jsonstring;
}