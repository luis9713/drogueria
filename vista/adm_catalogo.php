<?php
session_start();
if($_SESSION['us_tipo']==1||$_SESSION['us_tipo']==3||$_SESSION['us_tipo']==2){
include_once 'layouts/header.php';
?>

  <title>Adm | Catalogo</title>
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
            <h1 class="animate__animated animate__shakeY">🛒 Catálogo de Productos</h1>
            <p class="text-muted">Busca y selecciona productos para la venta</p>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Catalogo</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>
    <!-- Main content -->
    <section>
        <div class="container-fluid">
            <div class="card card-success">
                <div class="card-header">
                    <h3 class="card-title">🔍 Buscar Productos para Venta</h3>
                    <div class="input-group">
                        <input type="text" id="buscar-producto"class="form-control float-left" placeholder="Ingrese nombre de producto">
                        <div class="input-group-append">
                            <button class="btn btn-default"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                  <div id="productos" class="row d-flex align-items-stretch">
                  
                  </div>
                </div>
                <div class="card-footer">
                
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
<script src="../js/Catalogo.js"></script>
<script src="../js/Carrito.js"></script>