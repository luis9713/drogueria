<?php
session_start();
if($_SESSION['us_tipo']==1||$_SESSION['us_tipo']==3){
include_once 'layouts/header.php';
?>

  <title>Adm | Productos inactivos</title>
<?php
include_once 'layouts/nav.php';
?>
  <div class="content-wrapper">
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Productos inactivos</h1>
            <p class="text-muted mb-0">Productos descontinuados que pueden volver a estar disponibles.</p>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="adm_catalogo.php">Home</a></li>
              <li class="breadcrumb-item active">Productos inactivos</li>
            </ol>
          </div>
        </div>
      </div>
    </section>

    <section class="content">
      <div class="container-fluid">
        <div class="card card-secondary">
          <div class="card-header">
            <h3 class="card-title">Listado de productos descontinuados</h3>
          </div>
          <div class="card-body table-responsive">
            <table id="productos-inactivos" class="table table-hover text-nowrap">
              <thead>
                <tr>
                  <th>Producto</th>
                  <th>Concentracion</th>
                  <th>Adicional</th>
                  <th>Laboratorio</th>
                  <th>Presentacion</th>
                  <th>Tipo</th>
                  <th>Precio</th>
                  <th>Accion</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
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
<script src="../js/ProductosInactivos.js"></script>