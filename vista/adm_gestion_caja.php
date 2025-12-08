<?php
session_start();
if($_SESSION['us_tipo']==1||$_SESSION['us_tipo']==3||$_SESSION['us_tipo']==2){
include_once 'layouts/header.php';
?>

  <title>Adm | Gestión de Caja</title>
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
            <h1><i class="fas fa-cash-register"></i> Gestión de Caja</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="adm_catalogo.php">Home</a></li>
              <li class="breadcrumb-item active">Gestión de Caja</li>
            </ol>
          </div>
        </div>
      </div>
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        
        <!-- Botón Abrir Caja y Estado -->
        <div class="row mb-3">
          <div class="col-md-12">
            <div class="card card-outline" id="card-estado-caja">
              <div class="card-body">
                <div class="row align-items-center">
                  <div class="col-md-8" id="info-estado-caja">
                    <div class="d-flex align-items-center">
                      <i class="fas fa-info-circle fa-2x text-muted mr-3"></i>
                      <div>
                        <h5 class="mb-0">Estado de Caja</h5>
                        <small class="text-muted">Cargando información...</small>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-4 text-right">
                    <button type="button" class="btn btn-success btn-lg" id="btnAbrirCajaNueva" style="display:none;">
                      <i class="fas fa-lock-open mr-2"></i>Abrir Caja
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Información de caja actual -->
        <div class="row">
          <div class="col-md-12">
            <div class="card card-success" id="card-caja-actual">
              <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Caja Actual</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-danger btn-sm" id="btnCerrarCaja">
                    <i class="fas fa-lock mr-1"></i>Cerrar Caja
                  </button>
                </div>
              </div>
              <div class="card-body" id="info-caja-actual">
                <div class="text-center">
                  <i class="fas fa-spinner fa-spin fa-2x"></i>
                  <p class="mt-2">Cargando información...</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Historial de cajas -->
        <div class="row">
          <div class="col-md-12">
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history mr-2"></i>Historial de Cajas</h3>
              </div>
              <div class="card-body">
                <table id="tabla-cajas" class="table table-bordered table-hover">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Apertura</th>
                      <th>Cierre</th>
                      <th>Monto Inicial</th>
                      <th>Ventas</th>
                      <th>Efectivo Esperado</th>
                      <th>Efectivo Real</th>
                      <th>Diferencia</th>
                      <th>Estado</th>
                      <th>Usuario</th>
                      <th>Acciones</th>
                    </tr>
                  </thead>
                  <tbody>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

      </div>
    </section>
  </div>

  <!-- Modal detalle de caja -->
  <div class="modal fade" id="modal-detalle-caja" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header bg-info">
          <h5 class="modal-title"><i class="fas fa-file-invoice mr-2"></i>Detalle de Caja</h5>
          <button type="button" class="close" data-dismiss="modal">
            <span>&times;</span>
          </button>
        </div>
        <div class="modal-body" id="contenido-detalle-caja">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>

<?php
include_once 'layouts/footer.php';
}
else{
    header('Location: ../index.php');
}
?>
<script src="../js/GestionCaja.js"></script>
