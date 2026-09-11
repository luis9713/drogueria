<?php
session_start();
if($_SESSION['us_tipo']==3){
include_once 'layouts/header.php';
?>

  <title>Adm | Gestion compra</title>
  <!-- Tell the browser to be responsive to screen width -->
<?php
include_once 'layouts/nav.php';
?>
<div class="modal fade" id="editar_compra" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title">Editar compra</h3>
                <button data-dismiss="modal" aria-label="close"class="close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="card-body row">
              <div class="alert alert-danger text-center col-12" id="noedit-compra" style='display:none;'>
                <span id="error-editar-compra"><i class="fas fa-times m-1"></i>No se pudo editar</span>
              </div>
              <div class="alert alert-success text-center col-12" id="edit-compra" style='display:none;'>
                <span><i class="fas fa-check m-1"></i>Se edito correctamente</span>
              </div>
              <div class="card col-sm-3 p-3">
                <input type="hidden" id="editar_id_compra">
                <div class="form-group">
                    <label for="editar_codigo">Codigo</label>
                    <input id="editar_codigo" type="text" class="form-control" placeholder="Ingrese codigo" required>
                </div>
                <div class="form-group">
                    <label for="editar_fecha_compra">Fecha de compra</label>
                    <input id="editar_fecha_compra" type="date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="editar_fecha_entrega">Fecha de entrega</label>
                    <input id="editar_fecha_entrega" type="date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="editar_proveedor">Proveedor</label>
                    <select id="editar_proveedor" class="form-control select2" style="width: 100%"></select>
                </div>
                <div class="form-group">
                    <label for="editar_flete">Flete</label>
                    <input id="editar_flete" type="number" step="any" min="0" class="form-control" value='0' placeholder="Ingrese valor del flete">
                </div>
              </div>
              <div class="card col-sm-9 p-3">
                <div class="card p-3">
                    <div class="form-group">
                        <label for="editar_producto">Producto</label>
                        <select id="editar_producto" class="form-control select2" style="width: 100%"></select>
                    </div>
                    <div class="form-group">
                        <label for="editar_codigo_lote">Codigo</label>
                        <input id="editar_codigo_lote" type="text" class="form-control" placeholder="Ingrese codigo de lote">
                    </div>
                    <div class="form-group">
                        <label for="editar_cantidad">Cantidad</label>
                        <input id="editar_cantidad" type="number" class="form-control" value='1' placeholder="Ingrese cantidad">
                    </div>
                    <div class="form-group">
                        <label for="editar_vencimiento">Vencimiento</label>
                        <input id="editar_vencimiento" type="date" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="editar_precio_compra">Precio de compra</label>
                        <input id="editar_precio_compra" type="number" step="any" class="form-control" value='1' placeholder="Ingrese precio de compra">
                    </div>
                    <div class="form-group text-right">
                        <button type="button" id="editar_agregar_producto" class="btn bg-gradient-success ml-2">Agregar</button>
                    </div>
                </div>
              </div>
              <div class="card col-sm-12">
                <table class="table table-hover text-nowrap table-responsive">
                    <thead class='table-success'>
                        <tr>
                            <th>Producto</th>
                            <th>Codigo</th>
                            <th>Cantidad</th>
                            <th>Vencimiento</th>
                            <th>Precio de compra</th>
                            <th>Subtotal</th>
                            <th>Operacion</th>
                        </tr>
                    </thead>
                    <tbody id="editar_registros" class='table-active'></tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5" class="text-right">Subtotal productos:</td>
                            <td colspan="2" id="editar_subtotal">0</td>
                        </tr>
                        <tr>
                            <td colspan="5" class="text-right">Flete:</td>
                            <td colspan="2" id="editar_flete_mostrado">0</td>
                        </tr>
                        <tr>
                            <td colspan="5" class="text-right"><strong>Total factura:</strong></td>
                            <td colspan="2"><strong id="editar_total">0</strong></td>
                        </tr>
                    </tfoot>
                </table>
              </div>
            </div>
            <div class="card-footer">
                <button type="button" id="guardar_edicion_compra" class="btn bg-gradient-primary float-right m-1">Guardar cambios</button>
                <button type="button" data-dismiss="modal"class="btn btn-outline-secondary float-right m-1">Cerrar</button>
            </div>
        </div>
    </div>
  </div>
</div>
<div class="modal fade" id="vista_compra" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title">Detalle compra</h3>
                <button data-dismiss="modal" aria-label="close"class="close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="card-body">
                <div class="form-group">
                  <label for="codigo_compra">Codigo compra: </label>
                  <span id="codigo_compra"></span>
                </div>
                <div class="form-group">
                  <label for="fecha_compra">Fecha compra: </label>
                  <span id="fecha_compra"></span>
                </div>
                <div class="form-group">
                  <label for="fecha_entrega">Fecha entrega: </label>
                  <span id="fecha_entrega"></span>
                </div>
                <div class="form-group">
                  <label for="estado">Estado: </label>
                  <span id="estado"></span>
                </div>
                <div class="form-group">
                  <label for="proveedor">Proveedor: </label>
                  <span id="proveedor"></span>
                </div>
                <table class="table table-hover text-nowrap table-responsive">
                  <thead class="table-success">
                    <tr>
                      <th>#</th>
                      <th>Codigo</th>
                      <th>Cantidad</th>
                      <th>Vencimiento</th>
                      <th>Precio Compra</th>
                      <th>Producto</th>
                      <th>Laboratorio</th>
                      <th>Presentacion</th>
                      <th>Tipo</th>
                    </tr>
                  </thead>
                  <tbody class="table-warning"id="detalles">

                  </tbody>
                </table>
                <div class="float-right input-group-append">
                  <table class="table table-sm mb-0">
                    <tr>
                      <td class="text-right">Subtotal productos:</td>
                      <td class="text-right" id="subtotal_detalle"></td>
                    </tr>
                    <tr>
                      <td class="text-right">Flete:</td>
                      <td class="text-right" id="flete_detalle"></td>
                    </tr>
                    <tr>
                      <td class="text-right"><strong>Total:</strong></td>
                      <td class="text-right"><strong id="total"></strong></td>
                    </tr>
                  </table>
                </div>
            </div>
            <div class="card-footer">
                <button type="button" id="imprimir_detalle" class="btn btn-secondary float-left m-1"><i class="fas fa-print"></i> Imprimir</button>
                <button type="button" id="marcar_pagado_detalle" class="btn bg-gradient-success float-left m-1" style="display:none;"><i class="fas fa-check"></i> Marcar como pagado</button>
                <button type="button" data-dismiss="modal"class="btn btn-outline-secondary float-right m-1">Close</button>
               
            </div>
        </div>
    </div>
  </div>
</div>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Gestion compras <a href="adm_ingresar_compra.php"class="btn bg-gradient-primary ml-2">Crear compra</a></h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="adm_catalogo.php">Home</a></li>
              <li class="breadcrumb-item active">Gestion compras</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>
    <section>
        <div class="container-fluid">
            <div class="card card-success">
                <div class="card-header">
                    <h3 class="card-title">Buscar lotes</h3>
                    <div class="input-group">
                        <input type="text" id="buscar-lote"class="form-control float-left" placeholder="Ingrese nombre de producto">
                        <div class="input-group-append">
                            <button class="btn btn-default"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                  <table id="compras" class="table table-dark table-hover"">
                    <thead>
                      <tr>
                        <th>#</th>
                        <th>ID | Codigo</th>
                        <th>Fecha de compra</th>
                        <th>Fecha de entrega</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Proveedor</th>
                        <th>Operaciones</th>
                      </tr>
                    </thead>
                    <tbody>
                    
                    </tbody>
                  </table>
                </div>
                <div class="card-footer">
                
                </div>
            </div>
        </div>
    </section>
  </div>
  <!-- /.content-wrapper -->
<?php
include_once 'layouts/footer.php';
}
else{
    header('Location: ../index.php');
}
?>
<script src="../js/Compras.js"></script>