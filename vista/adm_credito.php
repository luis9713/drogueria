<?php
session_start();
if($_SESSION['us_tipo']==3||$_SESSION['us_tipo']==1||$_SESSION['us_tipo']==2){
include_once 'layouts/header.php';
?>

  <title>Adm | Gestion Creditos</title>
  <!-- Tell the browser to be responsive to screen width -->
<?php
include_once 'layouts/nav.php';
?>

<div class="modal fade" id="vista_credito" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title">Registros de credito</h3>
                <button data-dismiss="modal" aria-label="close"class="close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="card-body">
                <div class="form-group">
                  <label for="credito_codigo_venta">Codigo Venta: </label>
                  <span id="credito_codigo_venta"></span>
                </div>
                <div class="form-group">
                  <label for="credito_fecha">Fecha: </label>
                  <span id="credito_fecha"></span>
                </div>
                <div class="form-group">
                  <label for="credito_cliente">Cliente: </label>
                  <span id="credito_cliente"></span>
                </div>
                <div class="form-group">
                  <label for="credito_dni">DNI: </label>
                  <span id="credito_dni"></span>
                </div>
                <div class="form-group">
                  <label for="credito_vendedor">Vendedor: </label>
                  <span id="credito_vendedor"></span>
                </div>
                <div class="table-responsive">
                  <table class="table table-hover table-sm">
                    <thead class="table-success">
                      <tr>
                        <th>Cant.</th>
                        <th>Precio</th>
                        <th>Producto</th>
                        <th>Conc.</th>
                        <th>Adic.</th>
                        <th>Lab.</th>
                        <th>Pres.</th>
                        <th>Tipo</th>
                        <th>Subtotal</th>
                      </tr>
                    </thead>
                    <tbody class="table-warning"id="credito_registros">

                    </tbody>
                  </table>
                </div>

                <div class="row mt-3">
                  <div class="col-md-4 ml-auto">
                    <div class="table-responsive">
                      <table class="table table-sm">
                        <tr>
                          <td class="text-right"><strong>Total:</strong></td>
                          <td class="text-right" id="credito_total"></td>
                        </tr>
                        <tr>
                          <td class="text-right"><strong>Depositado:</strong></td>
                          <td class="text-right" id="credito_depositado"></td>
                        </tr>
                        <tr class="table-danger">
                          <td class="text-right"><strong>Saldo:</strong></td>
                          <td class="text-right"><strong id="credito_saldo"></strong></td>
                        </tr>
                      </table>
                    </div>
                  </div>
                </div>

                

            </div>
            <div id="credito_footer" class="card-footer">
                <div class="card-body w-100">
                  <div class="info-box mb-3 bg-success">
                      <span class="info-box-icon"><i class="fas fa-money-bill-alt"></i></span>
                      <div class="info-box-content">
                          <span class="info-box-text text-left ">DEPOSITO</span>
                          <input type="number" id="credito_pago" min="1" placeholder="Ingresa Dinero" class="form-control">
                      </div>
                  </div>
                  <div class="form-group">
                    <label for="credito_medio_pago">Medio de pago del abono:</label>
                    <select id="credito_medio_pago" class="form-control">
                      <option value="Efectivo" selected>Efectivo</option>
                      <option value="Nequi">Nequi</option>
                    </select>
                      </div>
                  <div class="info-box mb-3 bg-info">
                      <span class="info-box-icon"><i class="fas fa-money-bill-wave"></i></span>
                      <div class="info-box-content">
                          <span class="info-box-text text-left ">VUELTO</span>
                          <span class="info-box-number" id="credito_vuelto">0</span>
                      </div>
                  </div>
                </div>
              
                <button type="button" data-dismiss="modal" class="btn btn-outline-secondary float-right m-1">Cerrar</button>
                
                <button type="button" id="credito_procesar_deposito" class="btn btn-success float-right m-1">Añadir</button>
               
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
            <h1>Gestion Creditos</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="adm_catalogo.php">Home</a></li>
              <li class="breadcrumb-item active">Gestion Creditos</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>
    
    <section>
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-3 col-6">
                  <div class="small-box bg-info">
                    <div class="inner">
                      <h3 id="total_creditos">0</h3>
                      <p>Total Créditos</p>
                    </div>
                    <div class="icon">
                      <i class="fas fa-file-invoice"></i>
                    </div>
                  </div>
                </div>
                <div class="col-lg-3 col-6">
                  <div class="small-box bg-warning">
                    <div class="inner">
                      <h3 id="monto_total_creditos">$0</h3>
                      <p>Monto Total Créditos</p>
                    </div>
                    <div class="icon">
                      <i class="fas fa-dollar-sign"></i>
                    </div>
                  </div>
                </div>
                <div class="col-lg-3 col-6">
                  <div class="small-box bg-success">
                    <div class="inner">
                      <h3 id="monto_depositado">$0</h3>
                      <p>Monto Depositado</p>
                    </div>
                    <div class="icon">
                      <i class="fas fa-hand-holding-usd"></i>
                    </div>
                  </div>
                </div>
                <div class="col-lg-3 col-6">
                  <div class="small-box bg-danger">
                    <div class="inner">
                      <h3 id="saldo_por_cobrar">$0</h3>
                      <p>Saldo por Cobrar</p>
                    </div>
                    <div class="icon">
                      <i class="fas fa-exclamation-circle"></i>
                    </div>
                  </div>
                </div>
            </div>
        </div>
    </section>
    
    <section>
        <div class="container-fluid">
            <div class="card card-success">
                <div class="card-header">
                    <h3 class="card-title">Buscar Credito</h3>
                    <div class="card-tools">
                        <div class="input-group input-group-sm" style="width: auto;">
                            <label class="mr-2 mt-1">Desde:</label>
                            <input type="date" class="form-control" id="fecha_desde" style="width: 150px;">
                            <label class="ml-3 mr-2 mt-1">Hasta:</label>
                            <input type="date" class="form-control" id="fecha_hasta" style="width: 150px;">
                            <button type="button" id="btn-reporte-creditos" class="btn btn-danger ml-3">
                                <i class="fas fa-file-pdf mr-2"></i>Generar Reporte PDF
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <table id="tabla_credito" class="display table table-hover text-nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th>Codigo</th>
                                <th>Fecha</th>
                                <th>Cliente</th>
                                <th>DNI.</th>
                                <th>Depositado</th>
                                <th>Total</th>
                                <th>Vendedor</th>
                                <th>Accion</th>
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

<script src="../js/Credito.js"></script>
