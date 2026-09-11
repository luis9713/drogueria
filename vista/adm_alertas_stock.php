<?php
session_start();
if($_SESSION['us_tipo']==1||$_SESSION['us_tipo']==3||$_SESSION['us_tipo']==2){
include_once 'layouts/header.php';
?>

  <title>Adm | Alertas Stock Bajo</title>
<?php
include_once 'layouts/nav.php';
?>
  <div class="content-wrapper">
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="animate__animated animate__shakeY">🔴 Alertas de Stock Bajo</h1>
            <p class="text-muted">Productos con bajo inventario para reabastecer</p>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Stock Bajo</li>
            </ol>
          </div>
        </div>
      </div>
    </section>

    <section>
      <div class="container-fluid">
        <div class="card card-danger">
          <div class="card-header">
            <h3 class="card-title">🔴 PRODUCTOS CON BAJO STOCK - REABASTECER URGENTE</h3>
            <div class="card-tools">
              <span class="badge badge-danger" id="contador-bajo-stock">0</span>
            </div>
          </div>
          <div class="card-body p-0 table-responsive">
            <table id="stocks" class="animate__animated animate__fadeIn table table-hover text-nowrap">
              <thead class="table-danger">
                <tr>
                  <th>Stock</th>
                  <th>Medicamento</th>
                  <th>Concentracion</th>
                  <th>Adicional</th>
                  <th>Laboratorio</th>
                  <th>Presentacion</th>
                  <th>Tipo</th>
                  <th>Accion</th>
                </tr>
              </thead>
              <tbody class="table-active">
              </tbody>
            </table>
          </div>
          <div class="card-footer bg-danger text-white">
            <small><i class="fas fa-info-circle mr-1"></i>Productos con stock en 0 unidades (agotados). <strong>Se recomienda realizar pedido urgente o descontinuar.</strong></small>
          </div>
        </div>
      </div>
    </section>

    <section>
      <div class="container-fluid">
        <div class="row">
          <div class="col-lg-6">
            <div class="card card-info">
              <div class="card-header">
                <h3 class="card-title">📊 Resumen de Stock</h3>
              </div>
              <div class="card-body text-center">
                <h3 class="text-danger" id="total-bajo-stock">0</h3>
                <p class="text-muted">Productos Bajo Stock</p>
              </div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="card card-secondary">
              <div class="card-header">
                <h3 class="card-title">🔄 Control</h3>
              </div>
              <div class="card-body">
                <p class="mb-0">Ultima revision: <strong id="ultima-revision">Cargando...</strong></p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
<?php
include_once 'layouts/footer.php';
}
else{
    header('Location: ../index.php');
}
?>
<script src="../js/AlertasStock.js"></script>
