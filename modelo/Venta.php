<?php
include_once 'Conexion.php';
class Venta{
    var $objetos;
    public function __construct(){
        $db= new Conexion();
        $this->acceso=$db->pdo;
    }
    function Crear($cliente,$total,$fecha,$vendedor,$tipo_pago,$pago,$id_caja=null,$medio_pago='Efectivo'){
        try {
            $sql="INSERT INTO venta(fecha,total,vendedor,id_cliente,tipo_pago,depositado,id_caja,medio_pago) values(:fecha,:total,:vendedor,:cliente,:tipo_pago,:pago,:id_caja,:medio_pago)";
            $query = $this->acceso->prepare($sql);
            $query->execute(array(':fecha'=>$fecha,':cliente'=>$cliente,':total'=>$total,':vendedor'=>$vendedor, ':tipo_pago'=>$tipo_pago, ':pago'=>$pago, ':id_caja'=>$id_caja, ':medio_pago'=>$medio_pago));
        } catch (Exception $e) {
            // Compatibilidad con esquemas antiguos sin columna medio_pago
            $sql="INSERT INTO venta(fecha,total,vendedor,id_cliente,tipo_pago,depositado,id_caja) values(:fecha,:total,:vendedor,:cliente,:tipo_pago,:pago,:id_caja)";
            $query = $this->acceso->prepare($sql);
            $query->execute(array(':fecha'=>$fecha,':cliente'=>$cliente,':total'=>$total,':vendedor'=>$vendedor, ':tipo_pago'=>$tipo_pago, ':pago'=>$pago, ':id_caja'=>$id_caja));
        }
    }
    function Depositar($id,$depositado,$tipo_pago){
        $sql="UPDATE venta SET depositado=:depositado, tipo_pago=:tipo_pago where id_venta=:id";
        $query = $this->acceso->prepare($sql);
        $query->execute(array(':id'=>$id,':depositado'=>$depositado, ':tipo_pago'=>$tipo_pago));
        
    }
    function ultima_venta(){
        $sql="SELECT MAX(id_venta) as ultima_venta FROM venta";
        $query = $this->acceso->prepare($sql);
        $query->execute();
        $this->objetos=$query->fetchall();
        return $this->objetos;
    }
    function borrar($id_venta){
        $sql="DELETE FROM venta where id_venta=:id_venta";
        $query = $this->acceso->prepare($sql);
        $query->execute(array(':id_venta'=>$id_venta));

        $sql="DELETE FROM venta where tipo_pago=:tipo_pago";
        $query = $this->acceso->prepare($sql);
        $query->execute(array(':tipo_pago'=>"Deposito_" . $id_venta));
        echo 'delete';
    }
    function buscar(){
        $sql="SELECT id_venta,fecha,cliente,dni,total, CONCAT(usuario.nombre_us,' ',usuario.apellidos_us) as vendedor,id_cliente,tipo_pago FROM venta join usuario on vendedor=id_usuario WHERE NOT tipo_pago='Credito' and NOT tipo_pago='Credito_Pagado' ORDER BY id_venta DESC";
        $query = $this->acceso->prepare($sql);
        $query->execute();
        $this->objetos=$query->fetchall();
        return $this->objetos;
    }
    function buscar_credito(){
        $sql="SELECT id_venta,fecha,cliente,dni,total, CONCAT(usuario.nombre_us,' ',usuario.apellidos_us) as vendedor,id_cliente,tipo_pago,depositado FROM venta join usuario on vendedor=id_usuario WHERE tipo_pago='Credito' ORDER BY id_venta DESC";
        $query = $this->acceso->prepare($sql);
        $query->execute();
        $this->objetos=$query->fetchall();
        return $this->objetos;
    }
    function buscar_contado(){
        $sql="SELECT id_venta,fecha,cliente,dni,total, CONCAT(usuario.nombre_us,' ',usuario.apellidos_us) as vendedor,id_cliente,tipo_pago FROM venta join usuario on vendedor=id_usuario WHERE NOT tipo_pago='Credito' ORDER BY id_venta DESC";
        $query = $this->acceso->prepare($sql);
        $query->execute();
        $this->objetos=$query->fetchall();
        return $this->objetos;
    }
    function verificar($id_venta,$id_usuario){
        $sql="SELECT * FROM venta WHERE vendedor=:id_usuario and id_venta=:id_venta";
        $query = $this->acceso->prepare($sql);
        $query->execute(array(':id_usuario'=>$id_usuario,':id_venta'=>$id_venta));
        $this->objetos=$query->fetchall();
        if(!empty($this->objetos)){
            return 1;
        }
        else{
            return 0;
        }
    }
    function recuperar_vendedor($id_venta){
        $sql="SELECT us_tipo FROM venta join usuario on id_usuario=vendedor where id_venta=:id_venta and NOT tipo_pago='Credito' and NOT tipo_pago='Credito_Pagado'";
        $query = $this->acceso->prepare($sql);
        $query->execute(array(':id_venta'=>$id_venta));
        $this->objetos=$query->fetchall();
        return $this->objetos;
    }
    function venta_dia_vendedor($id_usuario){
        $sql="SELECT SUM(total) as venta_dia_vendedor FROM `venta` WHERE vendedor=:id_usuario and date(fecha)= date(curdate()) and NOT tipo_pago='Credito' and NOT tipo_pago='Credito_Pagado'";
        $query = $this->acceso->prepare($sql);
        $query->execute(array(':id_usuario'=>$id_usuario));
        $this->objetos=$query->fetchall();
        return $this->objetos;
    }
    function venta_diaria(){
        // Incluye Nequi en el total diario
        $sql="SELECT SUM(total) as venta_diaria FROM `venta` WHERE date(fecha)= date(curdate()) and NOT tipo_pago='Credito' and NOT tipo_pago='Credito_Pagado'";
        $query = $this->acceso->prepare($sql);
        $query->execute();
        $this->objetos=$query->fetchall();
        return $this->objetos;
    }
    function venta_diaria_nequi(){
        $sql="SELECT SUM(total) as venta_diaria_nequi FROM `venta` WHERE date(fecha)= date(curdate()) and tipo_pago='Nequi'";
        $query = $this->acceso->prepare($sql);
        $query->execute();
        $this->objetos=$query->fetchall();
        return $this->objetos;
    }
    function venta_mensual(){
        // Incluye Nequi en el total mensual
        $sql="SELECT SUM(total) as venta_mensual FROM `venta` WHERE year(fecha)= year(curdate()) and month(fecha) = month(curdate()) and NOT tipo_pago='Credito' and NOT tipo_pago='Credito_Pagado'";
        $query = $this->acceso->prepare($sql);
        $query->execute();
        $this->objetos=$query->fetchall();
        return $this->objetos;
    }
    function venta_anual(){
        // Incluye Nequi en el total anual
        $sql="SELECT SUM(total) as venta_anual FROM `venta` WHERE year(fecha)= year(curdate()) and NOT tipo_pago='Credito' and NOT tipo_pago='Credito_Pagado'";
        $query = $this->acceso->prepare($sql);
        $query->execute();
        $this->objetos=$query->fetchall();
        return $this->objetos;
    }
    function buscar_id($id_venta){
        $sql="SELECT id_venta,fecha,cliente,dni,total, CONCAT(usuario.nombre_us,' ',usuario.apellidos_us) as vendedor,id_cliente,tipo_pago FROM venta join usuario on vendedor=id_usuario and id_venta=:id_venta WHERE NOT tipo_pago='Credito' and NOT tipo_pago='Credito_Pagado'";
        $query = $this->acceso->prepare($sql);
        $query->execute(array(':id_venta'=>$id_venta));
        $this->objetos=$query->fetchall();
        return $this->objetos;
    }
    function buscar_credito_id($id_venta){
        $sql="SELECT id_venta,fecha,cliente,dni,total, CONCAT(usuario.nombre_us,' ',usuario.apellidos_us) as vendedor,id_cliente,tipo_pago,depositado FROM venta join usuario on vendedor=id_usuario and id_venta=:id_venta";
        $query = $this->acceso->prepare($sql);
        $query->execute(array(':id_venta'=>$id_venta));
        $this->objetos=$query->fetchall();
        return $this->objetos;
    }
    function venta_mes(){
        $sql="SELECT sum(total) as cantidad, month(fecha) as mes FROM `venta` WHERE year(fecha) = year(curdate()) and NOT tipo_pago='Credito' and NOT tipo_pago='Credito_Pagado' group by month(fecha)";
        $query = $this->acceso->prepare($sql);
        $query->execute();
        $this->objetos=$query->fetchall();
        return $this->objetos;
    }
    function vendedor_mes(){
        $sql="SELECT CONCAT(usuario.nombre_us,' ',usuario.apellidos_us) as vendedor_nombre ,sum(total) as cantidad FROM `venta` join usuario on id_usuario=vendedor where month(fecha)=month(curdate()) and year(fecha)=year(curdate()) and NOT tipo_pago='Credito' and NOT tipo_pago='Credito_Pagado' group by vendedor order by cantidad DESC LIMIT 3";
        $query = $this->acceso->prepare($sql);
        $query->execute();
        $this->objetos=$query->fetchall();
        return $this->objetos;
    }
    function ventas_anual(){
        $sql="SELECT sum(total) as cantidad, month(fecha) as mes FROM `venta` WHERE year(fecha) = year(date_add(curdate(),INTERVAL -1 YEAR)) and NOT tipo_pago='Credito' and NOT tipo_pago='Credito_Pagado' group by month(fecha)";
        $query = $this->acceso->prepare($sql);
        $query->execute();
        $this->objetos=$query->fetchall();
        return $this->objetos;
    }
    function producto_mas_vendido(){
        $sql="SELECT nombre,concentracion,adicional,SUM(cantidad) as total FROM `venta` JOIN venta_producto ON id_venta=venta_id_venta JOIN producto ON id_producto=producto_id_producto WHERE year(fecha)=year(curdate()) and month(fecha) = month(curdate()) and NOT tipo_pago='Credito' and NOT tipo_pago='Credito_Pagado' group by producto_id_producto order by total desc limit 5";
        $query = $this->acceso->prepare($sql);
        $query->execute();
        $this->objetos=$query->fetchall();
        return $this->objetos;
    }
    function cliente_mes(){
        $sql="SELECT CONCAT(cliente.nombre,' ',cliente.apellidos) as cliente_nombre ,sum(total) as cantidad 
        FROM `venta` 
        join cliente on id_cliente=id 
        where month(fecha)=month(curdate()) 
        and year(fecha)=year(curdate()) 
        and NOT tipo_pago='Credito' and NOT tipo_pago='Credito_Pagado'
        group by id_cliente 
        order by cantidad 
        DESC LIMIT 3";
        $query = $this->acceso->prepare($sql);
        $query->execute();
        $this->objetos=$query->fetchall();
        return $this->objetos;
    }
    function monto_costo(){
        $sql="SELECT SUM(det_cantidad*precio_compra) as monto_costo FROM detalle_venta
        join venta on id_det_venta=id_venta and year(fecha)= year(curdate()) and month(fecha) = month(curdate())
        join lote on id__det_lote=lote.id WHERE NOT tipo_pago='Credito' and NOT tipo_pago='Credito_Pagado'";
        $query = $this->acceso->prepare($sql);
        $query->execute();
        $this->objetos=$query->fetchall();
        return $this->objetos;
    }
    function estadisticas_creditos(){
        $sql="SELECT 
            COUNT(*) as total_creditos,
            SUM(total) as monto_total_creditos,
            SUM(depositado) as monto_depositado,
            SUM(total - depositado) as saldo_por_cobrar
        FROM venta 
        WHERE tipo_pago='Credito'";
        $query = $this->acceso->prepare($sql);
        $query->execute();
        $this->objetos=$query->fetchall();
        return $this->objetos;
    }
    
    function listar_creditos_reporte($fecha_desde = null, $fecha_hasta = null){
        $sql = "SELECT 
            id_venta,
            fecha,
            cliente,
            dni,
            total,
            depositado,
            id_cliente,
            tipo_pago
        FROM venta 
        WHERE tipo_pago='Credito'";
        
        if ($fecha_desde) {
            $sql .= " AND DATE(fecha) >= :fecha_desde";
        }
        if ($fecha_hasta) {
            $sql .= " AND DATE(fecha) <= :fecha_hasta";
        }
        
        $sql .= " ORDER BY fecha ASC";
        
        $query = $this->acceso->prepare($sql);
        
        if ($fecha_desde && $fecha_hasta) {
            $query->execute(array(':fecha_desde' => $fecha_desde, ':fecha_hasta' => $fecha_hasta));
        } elseif ($fecha_desde) {
            $query->execute(array(':fecha_desde' => $fecha_desde));
        } elseif ($fecha_hasta) {
            $query->execute(array(':fecha_hasta' => $fecha_hasta));
        } else {
            $query->execute();
        }
        
        $this->objetos = $query->fetchall();
        return $this->objetos;
    }
    
    function buscar_por_id($id_venta){
        $sql="SELECT id_venta,fecha,cliente,dni,total,tipo_pago FROM venta WHERE id_venta=:id_venta";
        $query = $this->acceso->prepare($sql);
        $query->execute(array(':id_venta'=>$id_venta));
        $this->objetos=$query->fetchall();
        return $this->objetos;
    }
    
}