<?php
include_once 'Conexion.php';
class Compras{
    var $objetos;
    public function __construct(){
        $db= new Conexion();
        $this->acceso=$db->pdo;
    }
    function crear($codigo,$fecha_compra,$fecha_entrega,$total,$id_estado,$id_proveedor,$flete=0){
        $sql="INSERT INTO compra(codigo,fecha_compra,fecha_entrega,total,flete,id_estado_pago,id_proveedor) values (:codigo,:fecha_compra,:fecha_entrega,:total,:flete,:id_estado_pago,:id_proveedor);";
            $query = $this->acceso->prepare($sql);
            return $query->execute(array(':codigo'=>$codigo,':fecha_compra'=>$fecha_compra,':fecha_entrega'=>$fecha_entrega,':total'=>$total,':flete'=>$flete,':id_estado_pago'=>$id_estado,':id_proveedor'=>$id_proveedor));
       
    }
    function obtener_ultimo_id_insertado(){
        return $this->acceso->lastInsertId();
    }
    function ultima_compra(){
        $sql="SELECT MAX(id) as ultima_compra FROM compra";
        $query = $this->acceso->prepare($sql);
        $query->execute();
        $this->objetos=$query->fetchall();
        return $this->objetos;
    }
    function listar_compras(){
        $sql="SELECT c.id as id, concat(c.id,' | ',c.codigo) as codigo, fecha_compra,fecha_entrega,total,flete,(total+flete) as total_con_flete,e.nombre as estado, p.nombre as proveedor FROM compra as c
        join estado_pago as e on e.id = id_estado_pago
        join proveedor as p on p.id_proveedor = c.id_proveedor
        ORDER BY c.id DESC";
        $query = $this->acceso->prepare($sql);
        $query->execute();
        $this->objetos=$query->fetchall();
        return $this->objetos;
    }
    function tiene_ventas_asociadas($id_compra){
        $sql="SELECT COUNT(*) as total FROM detalle_venta dv
        JOIN lote l ON l.id = dv.id__det_lote
        WHERE l.id_compra=:id_compra";
        $query = $this->acceso->prepare($sql);
        $query->execute(array(':id_compra'=>$id_compra));
        $resultado = $query->fetch();
        return $resultado && (int)$resultado->total > 0;
    }
    function eliminar($id_compra){
        try{
            $this->acceso->beginTransaction();

            $sql="DELETE FROM lote WHERE id_compra=:id_compra";
            $query = $this->acceso->prepare($sql);
            $query->execute(array(':id_compra'=>$id_compra));

            $sql="DELETE FROM compra WHERE id=:id_compra";
            $query = $this->acceso->prepare($sql);
            $query->execute(array(':id_compra'=>$id_compra));

            $this->acceso->commit();
            return true;
        } catch (Exception $e){
            $this->acceso->rollBack();
            return false;
        }
    }
    function obtener($id_compra){
        $sql="SELECT id, codigo, fecha_compra, fecha_entrega, id_proveedor, id_estado_pago, total, flete FROM compra WHERE id=:id_compra";
        $query = $this->acceso->prepare($sql);
        $query->execute(array(':id_compra'=>$id_compra));
        return $query->fetch();
    }
    function actualizar_datos($id_compra,$codigo,$fecha_compra,$fecha_entrega,$id_proveedor,$total,$flete=0){
        $sql="UPDATE compra SET codigo=:codigo, fecha_compra=:fecha_compra, fecha_entrega=:fecha_entrega, id_proveedor=:id_proveedor, total=:total, flete=:flete WHERE id=:id_compra";
        $query = $this->acceso->prepare($sql);
        return $query->execute(array(
            ':codigo'=>$codigo,
            ':fecha_compra'=>$fecha_compra,
            ':fecha_entrega'=>$fecha_entrega,
            ':id_proveedor'=>$id_proveedor,
            ':total'=>$total,
            ':flete'=>$flete,
            ':id_compra'=>$id_compra
        ));
    }
    function calcular_total($id_compra){
        $sql="SELECT COALESCE(SUM(cantidad*precio_compra),0) as total FROM lote WHERE id_compra=:id_compra";
        $query = $this->acceso->prepare($sql);
        $query->execute(array(':id_compra'=>$id_compra));
        $resultado = $query->fetch();
        return $resultado ? (float)$resultado->total : 0;
    }
    function marcar_pagado($id_compra){
        $sql="UPDATE compra SET id_estado_pago=1 WHERE id=:id_compra AND id_estado_pago<>1";
        $query = $this->acceso->prepare($sql);
        $query->execute(array(':id_compra'=>$id_compra));
        return $query->rowCount() === 1;
    }
    function editarEstado($id_compra,$id_estado){
        $sql="UPDATE compra SET id_estado_pago=:id_estado where id=:id_compra";
        $query=$this->acceso->prepare($sql);
        $query->execute(array(':id_estado'=>$id_estado,':id_compra'=>$id_compra));
    }
    function obtenerDatos($id){
        $sql="SELECT concat(c.id,' | ',c.codigo) as codigo, fecha_compra,fecha_entrega,total,flete,e.nombre as estado, p.nombre as proveedor,
        telefono,correo,direccion,p.avatar as avatar 
        FROM compra as c
        join estado_pago as e on e.id = id_estado_pago and c.id=:id
        join proveedor as p on p.id_proveedor = c.id_proveedor";
        $query = $this->acceso->prepare($sql);
        $query->execute(array(':id'=>$id));
        $this->objetos=$query->fetchall();
        return $this->objetos;
    }

}