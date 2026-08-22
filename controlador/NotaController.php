<?php
include_once '../modelo/Nota.php';
session_start();

if (!isset($_SESSION['usuario'])) {
    echo json_encode(array('error' => 'no_sesion'));
    exit;
}

$id_usuario = $_SESSION['usuario'];
$nota = new Nota();

if (!isset($_POST['funcion'])) {
    exit;
}

$funcion = $_POST['funcion'];

if ($funcion == 'listar') {
    $nota->listar($id_usuario);
    $json = array();
    foreach ($nota->objetos as $obj) {
        $json[] = array(
            'id_nota'             => $obj->id_nota,
            'titulo'              => $obj->titulo,
            'contenido'           => $obj->contenido,
            'color'               => $obj->color,
            'fecha_creacion'      => $obj->fecha_creacion,
            'fecha_actualizacion' => $obj->fecha_actualizacion
        );
    }
    echo json_encode($json);
}

if ($funcion == 'crear') {
    $titulo    = isset($_POST['titulo'])    ? trim($_POST['titulo'])    : '';
    $contenido = isset($_POST['contenido']) ? trim($_POST['contenido']) : '';
    $color     = isset($_POST['color'])     ? trim($_POST['color'])     : 'yellow';

    if (empty($titulo) || empty($contenido)) {
        echo 'error_vacio';
        exit;
    }

    $nota->crear($titulo, $contenido, $color, $id_usuario);
}

if ($funcion == 'editar') {
    $id        = isset($_POST['id'])        ? (int)$_POST['id']         : 0;
    $titulo    = isset($_POST['titulo'])    ? trim($_POST['titulo'])    : '';
    $contenido = isset($_POST['contenido']) ? trim($_POST['contenido']) : '';
    $color     = isset($_POST['color'])     ? trim($_POST['color'])     : 'yellow';

    if ($id <= 0 || empty($titulo) || empty($contenido)) {
        echo 'error_vacio';
        exit;
    }

    $nota->editar($id, $titulo, $contenido, $color, $id_usuario);
}

if ($funcion == 'borrar') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) {
        echo 'error';
        exit;
    }
    $nota->borrar($id, $id_usuario);
}

if ($funcion == 'obtener') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $nota->obtener($id, $id_usuario);
    $json = array();
    foreach ($nota->objetos as $obj) {
        $json[] = array(
            'id_nota'   => $obj->id_nota,
            'titulo'    => $obj->titulo,
            'contenido' => $obj->contenido,
            'color'     => $obj->color
        );
    }
    if (!empty($json)) {
        echo json_encode($json[0]);
    } else {
        echo json_encode(array('error' => 'no_encontrado'));
    }
}
?>
