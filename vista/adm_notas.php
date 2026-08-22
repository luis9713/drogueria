<?php
session_start();
if($_SESSION['us_tipo']==1||$_SESSION['us_tipo']==3||$_SESSION['us_tipo']==2){
include_once 'layouts/header.php';
?>

  <title>Adm | Notas</title>
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
            <h1><i class="fas fa-sticky-note text-warning"></i> Notas y Recordatorios</h1>
            <p class="text-muted">Escribe y organiza tus notas personales</p>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="adm_catalogo.php">Home</a></li>
              <li class="breadcrumb-item active">Notas</li>
            </ol>
          </div>
        </div>
      </div>
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">

        <!-- Botón nueva nota -->
        <div class="row mb-3">
          <div class="col-12">
            <button type="button" class="btn btn-warning btn-lg" data-toggle="modal" data-target="#modalNota" id="btn-nueva-nota">
              <i class="fas fa-plus mr-2"></i>Nueva Nota
            </button>
          </div>
        </div>

        <!-- Contenedor de notas -->
        <div class="row" id="contenedor-notas">
          <!-- Las notas se cargan aquí dinámicamente -->
          <div class="col-12 text-center py-5" id="sin-notas" style="display:none;">
            <i class="fas fa-sticky-note fa-4x text-muted mb-3"></i>
            <h4 class="text-muted">No tienes notas aún</h4>
            <p class="text-muted">Haz clic en "Nueva Nota" para crear tu primera nota</p>
          </div>
        </div>

      </div>
    </section>
  </div>
  <!-- /.content-wrapper -->

  <!-- Modal Crear / Editar Nota -->
  <div class="modal fade" id="modalNota" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header" id="modal-nota-header" style="background-color:#ffc107;">
          <h5 class="modal-title" id="modal-nota-titulo">
            <i class="fas fa-sticky-note mr-2"></i>Nueva Nota
          </h5>
          <button type="button" class="close" data-dismiss="modal">
            <span>&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form id="form-nota">
            <input type="hidden" id="nota-id" value="">

            <!-- Título -->
            <div class="form-group">
              <label for="nota-titulo"><i class="fas fa-heading mr-1"></i>Título <span class="text-danger">*</span></label>
              <input type="text" class="form-control form-control-lg" id="nota-titulo"
                     placeholder="Ej: Préstamo a María, Recordatorio del lunes..." maxlength="100" required>
            </div>

            <!-- Contenido -->
            <div class="form-group">
              <label for="nota-contenido"><i class="fas fa-align-left mr-1"></i>Contenido <span class="text-danger">*</span></label>
              <textarea class="form-control" id="nota-contenido" rows="6"
                        placeholder="Escribe aquí tu nota... Ej: Le presté $50.000 a María el día de hoy, debe devolver el viernes." required></textarea>
            </div>

            <!-- Color -->
            <div class="form-group">
              <label><i class="fas fa-palette mr-1"></i>Color de la nota</label>
              <div class="d-flex flex-wrap" id="selector-colores">
                <div class="color-opcion mr-2 mb-2" data-color="yellow" style="background:#ffc107;width:40px;height:40px;border-radius:8px;cursor:pointer;border:3px solid transparent;" title="Amarillo"></div>
                <div class="color-opcion mr-2 mb-2" data-color="blue" style="background:#17a2b8;width:40px;height:40px;border-radius:8px;cursor:pointer;border:3px solid transparent;" title="Azul"></div>
                <div class="color-opcion mr-2 mb-2" data-color="green" style="background:#28a745;width:40px;height:40px;border-radius:8px;cursor:pointer;border:3px solid transparent;" title="Verde"></div>
                <div class="color-opcion mr-2 mb-2" data-color="red" style="background:#dc3545;width:40px;height:40px;border-radius:8px;cursor:pointer;border:3px solid transparent;" title="Rojo"></div>
                <div class="color-opcion mr-2 mb-2" data-color="purple" style="background:#6f42c1;width:40px;height:40px;border-radius:8px;cursor:pointer;border:3px solid transparent;" title="Morado"></div>
                <div class="color-opcion mr-2 mb-2" data-color="orange" style="background:#fd7e14;width:40px;height:40px;border-radius:8px;cursor:pointer;border:3px solid transparent;" title="Naranja"></div>
                <div class="color-opcion mr-2 mb-2" data-color="pink" style="background:#e83e8c;width:40px;height:40px;border-radius:8px;cursor:pointer;border:3px solid transparent;" title="Rosa"></div>
                <div class="color-opcion mr-2 mb-2" data-color="gray" style="background:#6c757d;width:40px;height:40px;border-radius:8px;cursor:pointer;border:3px solid transparent;" title="Gris"></div>
              </div>
              <input type="hidden" id="nota-color" value="yellow">
            </div>

          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">
            <i class="fas fa-times mr-1"></i>Cancelar
          </button>
          <button type="button" class="btn btn-warning" id="btn-guardar-nota">
            <i class="fas fa-save mr-1"></i>Guardar Nota
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Ver Nota Completa -->
  <div class="modal fade" id="modalVerNota" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header" id="ver-nota-header">
          <h5 class="modal-title" id="ver-nota-titulo"></h5>
          <button type="button" class="close" data-dismiss="modal">
            <span>&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <p id="ver-nota-contenido" style="white-space:pre-wrap;font-size:1.1rem;line-height:1.7;"></p>
          <hr>
          <small class="text-muted" id="ver-nota-fecha"></small>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">
            <i class="fas fa-times mr-1"></i>Cerrar
          </button>
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
<script src="../js/Notas.js"></script>
