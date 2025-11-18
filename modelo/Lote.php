<?php
include_once 'Conexion.php';
class Lote{
    var $objetos;
    public function __construct(){
        $db= new Conexion();
        $this->acceso=$db->pdo;
    }
    function crear($id_producto,$proveedor,$stock,$vencimiento){
        $sql="INSERT INTO lote(stock,vencimiento,lote_id_prod,lote_id_prov) values (:stock,:vencimiento,:id_producto,:id_proveedor)";
        $query = $this->acceso->prepare($sql);
        $query->execute(array(':stock'=>$stock,'vencimiento'=>$vencimiento,'id_producto'=>$id_producto,'id_proveedor'=>$proveedor));
        echo 'add';
    }
    function buscar(){
        if(!empty($_POST['consulta'])){
            $consulta=$_POST['consulta'];
            $sql="SELECT l.id as id_lote, concat(l.id,' | ',l.codigo) as codigo, l.cantidad_lote, l.vencimiento, 
                  p.concentracion, p.adicional, p.nombre as prod_nom, lab.nombre as lab_nom, tip.nombre as tip_nom,
                  pre.nombre as pre_nom, prov.nombre as proveedor, p.avatar as logo
                  FROM lote l
                  JOIN compra c ON l.id_compra = c.id AND l.estado='A'
                  JOIN proveedor prov ON prov.id_proveedor = c.id_proveedor
                  JOIN producto p ON p.id_producto = l.id_producto
                  JOIN laboratorio lab ON p.prod_lab = lab.id_laboratorio
                  JOIN tipo_producto tip ON p.prod_tip_prod = tip.id_tip_prod
                  JOIN presentacion pre ON p.prod_present = pre.id_presentacion 
                  WHERE p.nombre LIKE :consulta 
                  ORDER BY p.nombre 
                  LIMIT 25";
            $query = $this->acceso->prepare($sql);
            $query->execute(array(':consulta'=>"%$consulta%"));
            $this->objetos=$query->fetchall();
            return $this->objetos;
        }
        else{
            $sql="SELECT l.id as id_lote, concat(l.id,' | ',l.codigo) as codigo, l.cantidad_lote, l.vencimiento, 
                  p.concentracion, p.adicional, p.nombre as prod_nom, lab.nombre as lab_nom, tip.nombre as tip_nom,
                  pre.nombre as pre_nom, prov.nombre as proveedor, p.avatar as logo
                  FROM lote l
                  JOIN compra c ON l.id_compra = c.id AND l.estado='A'
                  JOIN proveedor prov ON prov.id_proveedor = c.id_proveedor
                  JOIN producto p ON p.id_producto = l.id_producto
                  JOIN laboratorio lab ON p.prod_lab = lab.id_laboratorio
                  JOIN tipo_producto tip ON p.prod_tip_prod = tip.id_tip_prod
                  JOIN presentacion pre ON p.prod_present = pre.id_presentacion 
                  WHERE p.nombre NOT LIKE '' 
                  ORDER BY p.nombre 
                  LIMIT 25";
            $query = $this->acceso->prepare($sql);
            $query->execute();
            $this->objetos=$query->fetchall();
            return $this->objetos;
        }
    }
    function editar($id,$stock){
        $sql="UPDATE lote SET cantidad_lote=:stock where id=:id";
        $query = $this->acceso->prepare($sql);
        $query->execute(array(':id'=>$id,':stock'=>$stock));
        echo 'edit';
    }
    function borrar($id){
        $sql="UPDATE lote SET estado='I' where id=:id";
        $query=$this->acceso->prepare($sql);
        $query->execute(array(':id'=>$id));
        if(!empty($query->execute(array(':id'=>$id)))){
            echo 'borrado';
        }
        else{
            echo 'noborrado';
        }
    }
    function devolver($id_lote,$cantidad,$vencimiento,$producto,$proveedor){
            $sql="SELECT * FROM lote WHERE id=:id_lote";
            $query = $this->acceso->prepare($sql);
            $query->execute(array(':id_lote'=>$id_lote));
            $lote=$query->fetchall();
         
                $sql="UPDATE lote SET cantidad_lote=cantidad_lote+:cantidad,estado='A' where id=:id_lote";
                $query = $this->acceso->prepare($sql);
                $query->execute(array(':cantidad'=>$cantidad,':id_lote'=>$id_lote));
            
            
    }
    ///////////////////////////////actualizacion//////////////////////////
    function crear_lote($codigo,$cantidad,$vencimiento,$precio_compra,$id_compra,$id_producto){
        $sql="INSERT INTO lote(codigo,cantidad,cantidad_lote,vencimiento,precio_compra,id_compra,id_producto) values (:codigo,:cantidad,:cantidad_lote,:vencimiento,:precio_compra,:id_compra,:id_producto)";
        $query = $this->acceso->prepare($sql);
        $query->execute(array(':codigo'=>$codigo,':cantidad'=>$cantidad,':cantidad_lote'=>$cantidad,':vencimiento'=>$vencimiento,':precio_compra'=>$precio_compra,':id_compra'=>$id_compra,':id_producto'=>$id_producto));
        echo 'add';
    }
    function ver($id){
        $sql="SELECT l.codigo as codigo, l.cantidad as cantidad, vencimiento, precio_compra, p.nombre as producto, concentracion,adicional,
            la.nombre as laboratorio, t.nombre as tipo, pre.nombre as presentacion
            FROM lote as l
            join producto as p on l.id_producto=p.id_producto and id_compra=:id
            join laboratorio as la on prod_lab=id_laboratorio
            join tipo_producto as t on prod_tip_prod=id_tip_prod
            join presentacion as pre on prod_present=id_presentacion";
            $query = $this->acceso->prepare($sql);
            $query->execute(array(':id'=>$id));
            $this->objetos=$query->fetchall();
            return $this->objetos;
    }

    function buscar_todos_lotes(){
        // Método específico para obtener TODOS los lotes activos sin límites para análisis de vencimientos
        $sql="SELECT l.id as id_lote, concat(l.id,' | ',l.codigo) as codigo, l.cantidad_lote, l.vencimiento, 
              p.concentracion, p.adicional, p.nombre as prod_nom, lab.nombre as lab_nom, tip.nombre as tip_nom,
              pre.nombre as pre_nom, prov.nombre as proveedor, p.avatar as logo, p.id_producto as id_producto
              FROM lote l
              JOIN compra c ON l.id_compra = c.id AND l.estado='A'
              JOIN proveedor prov ON prov.id_proveedor = c.id_proveedor
              JOIN producto p ON p.id_producto = l.id_producto
              JOIN laboratorio lab ON p.prod_lab = lab.id_laboratorio
              JOIN tipo_producto tip ON p.prod_tip_prod = tip.id_tip_prod
              JOIN presentacion pre ON p.prod_present = pre.id_presentacion 
              WHERE l.cantidad_lote > 0
              ORDER BY l.vencimiento ASC";
        $query = $this->acceso->prepare($sql);
        $query->execute();
        $this->objetos=$query->fetchall();
        return $this->objetos;
    }

    function obtener_stock(){
        $sql="SELECT SUM(l.cantidad_lote) As stock ,
              p.nombre as medicamento,
              p.concentracion as concentracion,
              p.adicional as adicional,
              la.nombre as laboratorio,
              t.nombre as tipo,
              pre.nombre as presentacion,
              p.id_producto as id_producto

              FROM lote l
              JOIN producto p ON l.id_producto=p.id_producto 
              join laboratorio la on p.prod_lab=id_laboratorio
              join tipo_producto t on p.prod_tip_prod=id_tip_prod
              join presentacion pre on p.prod_present=id_presentacion
              WHERE l.estado='A' AND l.cantidad_lote > 0
             GROUP BY l.id_producto
        ";
        $query=$this->acceso->prepare($sql);
        $query->execute();
        $this->objetos=$query->fetchall();
        return $this->objetos;
    }

}