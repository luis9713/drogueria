<?php
include_once 'Conexion.php';

class Nota {
    var $objetos;
    private $acceso;

    public function __construct() {
        $db = new Conexion();
        $this->acceso = $db->pdo;
    }

    function crear($titulo, $contenido, $color, $id_usuario) {
        date_default_timezone_set('America/Bogota');
        $fecha = date('Y-m-d H:i:s');
        $sql = "INSERT INTO notas (titulo, contenido, color, id_usuario, fecha_creacion, fecha_actualizacion)
                VALUES (:titulo, :contenido, :color, :id_usuario, :fecha, :fecha)";
        $query = $this->acceso->prepare($sql);
        $query->execute(array(
            ':titulo'     => $titulo,
            ':contenido'  => $contenido,
            ':color'      => $color,
            ':id_usuario' => $id_usuario,
            ':fecha'      => $fecha
        ));
        echo 'add';
    }

    function editar($id, $titulo, $contenido, $color, $id_usuario) {
        date_default_timezone_set('America/Bogota');
        $fecha = date('Y-m-d H:i:s');
        $sql = "UPDATE notas SET titulo=:titulo, contenido=:contenido, color=:color, fecha_actualizacion=:fecha
                WHERE id_nota=:id AND id_usuario=:id_usuario";
        $query = $this->acceso->prepare($sql);
        $query->execute(array(
            ':id'         => $id,
            ':titulo'     => $titulo,
            ':contenido'  => $contenido,
            ':color'      => $color,
            ':fecha'      => $fecha,
            ':id_usuario' => $id_usuario
        ));
        echo 'edit';
    }

    function borrar($id, $id_usuario) {
        $sql = "DELETE FROM notas WHERE id_nota=:id AND id_usuario=:id_usuario";
        $query = $this->acceso->prepare($sql);
        $query->execute(array(':id' => $id, ':id_usuario' => $id_usuario));
        echo 'borrado';
    }

    function listar($id_usuario) {
        $sql = "SELECT id_nota, titulo, contenido, color, fecha_creacion, fecha_actualizacion
                FROM notas
                WHERE id_usuario=:id_usuario
                ORDER BY fecha_actualizacion DESC";
        $query = $this->acceso->prepare($sql);
        $query->execute(array(':id_usuario' => $id_usuario));
        $this->objetos = $query->fetchAll();
        return $this->objetos;
    }

    function obtener($id, $id_usuario) {
        $sql = "SELECT id_nota, titulo, contenido, color, fecha_creacion, fecha_actualizacion
                FROM notas
                WHERE id_nota=:id AND id_usuario=:id_usuario";
        $query = $this->acceso->prepare($sql);
        $query->execute(array(':id' => $id, ':id_usuario' => $id_usuario));
        $this->objetos = $query->fetchAll();
        return $this->objetos;
    }
}
?>
