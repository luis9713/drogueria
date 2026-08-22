<?php
session_start();
if($_SESSION['us_tipo']==1||$_SESSION['us_tipo']==3||$_SESSION['us_tipo']==2){
include_once 'layouts/header.php';
?>

  <title>Adm | Alertas de Vencimientos</title>
<?php
include_once 'layouts/nav.php';
?>
  <div class="content-wrapper">
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="animate__animated animate__shakeY">🟡 Alertas de Vencimientos</h1>
            <p class="text-muted">Productos proximos a vencer y vencidos</p>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Vencimientos</li>
            </ol>
          </div>
        </div>
      </div>
    </section>

    <section>
      <div class="container-fluid">
        <div class="card card-warning">
          <div class="card-header">
            <h3 class="card-title">🟡 PRODUCTOS PROXIMOS A VENCER / VENCIDOS</h3>
            <div class="card-tools">
              <span class="badge badge-warning" id="contador-vencidos">0</span>
            </div>
          </div>
          <div class="card-body p-0 table-responsive">
            <table id="lotes" class="animate__animated animate__fadeIn table table-hover text-nowrap">
              <thead class="table-warning">
                <tr>
                  <th>Cod</th>
                  <th>Producto</th>
                  <th>Stock</th>
                  <th>Estado</th>
                  <th>Laboratorio</th>
                  <th>Presentacion</th>
                  <th>Proveedor</th>
                  <th>Tiempo Restante</th>
                  <th>Accion</th>
                </tr>
              </thead>
              <tbody class="table-active">
              </tbody>
            </table>
          </div>
          <div class="card-footer bg-warning">
            <small><i class="fas fa-info-circle mr-1"></i>Productos vencidos (rojo) o que vencen en los proximos 3 meses (amarillo).</small>
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
                <h3 class="card-title">📊 Resumen de Vencimientos</h3>
              </div>
              <div class="card-body text-center">
                <h3 class="text-warning" id="total-vencidos">0</h3>
                <p class="text-muted">Productos en Riesgo</p>
              </div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="card card-secondary">
              <div class="card-header">
                <h3 class="card-title">🔄 Acciones</h3>
              </div>
              <div class="card-body">
                <button class="btn btn-sm btn-warning" onclick="generarReporteVencidos()">
                  <i class="fas fa-file-pdf"></i> Generar PDF de vencimientos
                </button>
                <p class="mt-2 mb-0">Ultima revision: <strong id="ultima-revision">Cargando...</strong></p>
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
<script src="../js/AlertasVencimientos.js"></script>
