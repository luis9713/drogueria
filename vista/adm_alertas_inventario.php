<?php
session_start();
if($_SESSION['us_tipo']==1||$_SESSION['us_tipo']==3||$_SESSION['us_tipo']==2){
include_once 'layouts/header.php';
?>

  <title>Adm | Alertas de Inventario</title>
  <!-- Tell the browser to be responsive to screen width -->
<?php
include_once 'layouts/nav.php';
?>
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="animate__animated animate__shakeY">⚠️ Alertas de Inventario</h1>
            <p class="text-muted">Monitoreo de productos críticos que requieren atención inmediata</p>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Alertas</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <!-- SECCIÓN 1: PRODUCTOS DE BAJO STOCK (CRÍTICO) -->
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
                        <th>Acción</th>
                      </tr>
                    </thead>
                    <tbody class="table-active">
                    
                    </tbody>
                  </table>
                </div>
                <div class="card-footer bg-danger text-white">
                  <small><i class="fas fa-info-circle mr-1"></i>Productos con stock menor a 50 unidades. <strong>Se recomienda realizar pedido urgente.</strong></small>
                </div>
            </div>
        </div>
    </section>

    <!-- SECCIÓN 2: PRODUCTOS PRÓXIMOS A VENCER O VENCIDOS -->
    <section>
        <div class="container-fluid">
            <div class="card card-warning">
                <div class="card-header">
                    <h3 class="card-title">🟡 PRODUCTOS PRÓXIMOS A VENCER / VENCIDOS</h3>
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
                        <th>Acción</th>
                      </tr>
                    </thead>
                    <tbody class="table-active">
                    
                    </tbody>
                  </table>
                </div>
                <div class="card-footer bg-warning">
                  <small><i class="fas fa-info-circle mr-1"></i>Productos vencidos (🔴 Rojo) o que vencen en los próximos 3 meses (🟡 Amarillo). <strong>Revisar y tomar acciones correspondientes.</strong></small>
                </div>
            </div>
        </div>
    </section>

    <!-- SECCIÓN 3: RESUMEN DE ALERTAS -->
    <section>
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-6">
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title">📊 Resumen de Alertas</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6 text-center">
                                    <div class="border-right">
                                        <h3 class="text-danger" id="total-bajo-stock">0</h3>
                                        <p class="text-muted">Productos Bajo Stock</p>
                                    </div>
                                </div>
                                <div class="col-6 text-center">
                                    <h3 class="text-warning" id="total-vencidos">0</h3>
                                    <p class="text-muted">Productos en Riesgo</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card card-secondary">
                        <div class="card-header">
                            <h3 class="card-title">🔄 Acciones Recomendadas</h3>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Reporte de productos vencidos
                                    <button class="btn btn-sm btn-warning" onclick="generarReporteVencidos()">
                                        <i class="fas fa-file-pdf"></i> Generar PDF
                                    </button>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Actualizar última revisión
                                    <small class="text-muted" id="ultima-revision">Cargando...</small>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->
<?php
include_once 'layouts/footer.php';
}
else{
    header('Location: ../index.php');
}
?>
<script src="../js/AlertasInventario.js"></script>