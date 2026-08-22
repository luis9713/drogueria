<?php
session_start();
if($_SESSION['us_tipo']==1||$_SESSION['us_tipo']==3||$_SESSION['us_tipo']==2){
include_once 'layouts/header.php';
?>

  <title>Adm | Contabilidad</title>
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
            <h1><i class="fas fa-book"></i> Contabilidad General</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="adm_catalogo.php">Home</a></li>
              <li class="breadcrumb-item active">Contabilidad</li>
            </ol>
          </div>
        </div>
      </div>
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        
        <!-- Resumen financiero -->
        <div class="row">
          <div class="col-md-3">
            <div class="small-box bg-success">
              <div class="inner">
                <h3 id="total-ingresos">$0</h3>
                <p>Total Ingresos</p>
              </div>
              <div class="icon">
                <i class="fas fa-arrow-up"></i>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="small-box bg-danger">
              <div class="inner">
                <h3 id="total-egresos">$0</h3>
                <p>Total Egresos</p>
              </div>
              <div class="icon">
                <i class="fas fa-arrow-down"></i>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="small-box bg-info">
              <div class="inner">
                <h3 id="saldo-actual">$0</h3>
                <p>Saldo Actual (General)</p>
              </div>
              <div class="icon">
                <i class="fas fa-wallet"></i>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="small-box bg-warning">
              <div class="inner">
                <h3 id="total-movimientos">0</h3>
                <p>Total Movimientos</p>
              </div>
              <div class="icon">
                <i class="fas fa-exchange-alt"></i>
              </div>
            </div>
          </div>
        </div>

        <!-- Fila Nequi -->
        <div class="row mb-3">
          <div class="col-md-4">
            <div class="small-box" style="background-color:#6f42c1; color:#fff;">
              <div class="inner">
                <h3 id="saldo-nequi">$0</h3>
                <p>Saldo Nequi 📱</p>
              </div>
              <div class="icon">
                <i class="fas fa-mobile-alt"></i>
              </div>
            </div>
          </div>
          <div class="col-md-8">
            <div class="alert alert-info mb-0" style="height:100%; display:flex; align-items:center;">
              <i class="fas fa-info-circle mr-2"></i>
              <span>El <strong>Saldo Nequi</strong> acumula ventas por Nequi y también movimientos manuales (ingreso/egreso) cuando selecciones la cuenta Nequi.</span>
            </div>
          </div>
        </div>

        <div class="row" style="display:none;"><!-- fila fantasma para cerrar la fila anterior correctamente -->
        </div>

        <!-- Botones de acción -->
        <div class="row mb-3">
          <div class="col-md-12">
            <button type="button" class="btn btn-success" id="btnRegistrarIngreso">
              <i class="fas fa-plus-circle mr-1"></i>Registrar Ingreso
            </button>
            <button type="button" class="btn btn-danger" id="btnRegistrarEgreso">
              <i class="fas fa-minus-circle mr-1"></i>Registrar Egreso
            </button>
            <button type="button" class="btn btn-primary" id="btnGenerarReporte">
              <i class="fas fa-file-pdf mr-1"></i>Generar Reporte PDF
            </button>
          </div>
        </div>

        <!-- Tabla de movimientos -->
        <div class="row">
          <div class="col-md-12">
            <div class="card">
              <div class="card-header bg-primary">
                <h3 class="card-title"><i class="fas fa-list mr-2"></i>Movimientos Contables</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-tool" id="btnFiltros">
                    <i class="fas fa-filter"></i>
                  </button>
                </div>
              </div>
              <div class="card-body">
                <!-- Filtros (ocultos por defecto) -->
                <div id="filtros-contabilidad" class="mb-3" style="display:none;">
                  <div class="row">
                    <div class="col-md-3">
                      <label>Fecha Inicio:</label>
                      <input type="date" class="form-control" id="fecha_inicio">
                    </div>
                    <div class="col-md-3">
                      <label>Fecha Fin:</label>
                      <input type="date" class="form-control" id="fecha_fin">
                    </div>
                    <div class="col-md-3">
                      <label>Tipo:</label>
                      <select class="form-control" id="filtro_tipo">
                        <option value="">Todos</option>
                        <option value="Ingreso">Ingresos</option>
                        <option value="Egreso">Egresos</option>
                      </select>
                    </div>
                    <div class="col-md-3">
                      <label>&nbsp;</label><br>
                      <button type="button" class="btn btn-primary" id="btnAplicarFiltros">
                        <i class="fas fa-search mr-1"></i>Buscar
                      </button>
                      <button type="button" class="btn btn-secondary" id="btnLimpiarFiltros">
                        <i class="fas fa-eraser mr-1"></i>Limpiar
                      </button>
                    </div>
                  </div>
                </div>

                <table id="tabla-movimientos" class="table table-bordered table-hover table-sm">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Fecha</th>
                      <th>Tipo</th>
                      <th>Cuenta</th>
                      <th>Concepto</th>
                      <th>Categoría</th>
                      <th>Monto</th>
                      <th>Saldo</th>
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

  <!-- Modal Registrar Ingreso -->
  <div class="modal fade" id="modalIngreso">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header bg-success">
          <h5 class="modal-title"><i class="fas fa-arrow-up mr-2"></i>Registrar Ingreso</h5>
          <button type="button" class="close" data-dismiss="modal">
            <span>&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form id="formIngreso">
            <div class="form-group">
              <label>Concepto:</label>
              <input type="text" class="form-control" id="concepto_ingreso" required>
            </div>
            <div class="form-group">
              <label>Monto:</label>
              <input type="number" class="form-control" id="monto_ingreso" min="0" step="1000" required>
            </div>
            <div class="form-group">
              <label>Categoría:</label>
              <select class="form-control" id="categoria_ingreso">
                <option value="Venta">Venta</option>
                <option value="Cierre de Caja">Cierre de Caja</option>
                <option value="Abono Crédito">Abono Crédito</option>
                <option value="Otro Ingreso">Otro Ingreso</option>
              </select>
            </div>
            <div class="form-group">
              <label>Cuenta destino:</label>
              <select class="form-control" id="cuenta_ingreso">
                <option value="caja" selected>Caja General</option>
                <option value="nequi">Nequi</option>
              </select>
            </div>
            <div class="form-group">
              <label>Descripción (opcional):</label>
              <textarea class="form-control" id="descripcion_ingreso" rows="2"></textarea>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-success" id="btnGuardarIngreso">
            <i class="fas fa-save mr-1"></i>Guardar
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Registrar Egreso -->
  <div class="modal fade" id="modalEgreso">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header bg-danger">
          <h5 class="modal-title"><i class="fas fa-arrow-down mr-2"></i>Registrar Egreso</h5>
          <button type="button" class="close" data-dismiss="modal">
            <span>&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form id="formEgreso">
            <div class="form-group">
              <label>Concepto:</label>
              <input type="text" class="form-control" id="concepto_egreso" required>
            </div>
            <div class="form-group">
              <label>Monto:</label>
              <input type="number" class="form-control" id="monto_egreso" min="0" step="1000" required>
            </div>
            <div class="form-group">
              <label>Categoría:</label>
              <select class="form-control" id="categoria_egreso">
                <option value="Compra Inventario">Compra Inventario</option>
                <option value="Pago Proveedor">Pago Proveedor</option>
                <option value="Servicio">Servicio</option>
                <option value="Arriendo">Arriendo</option>
                <option value="Nómina">Nómina</option>
                <option value="Otro Egreso">Otro Egreso</option>
              </select>
            </div>
            <div class="form-group">
              <label>Cuenta origen:</label>
              <select class="form-control" id="cuenta_egreso">
                <option value="caja" selected>Caja General</option>
                <option value="nequi">Nequi</option>
              </select>
            </div>
            <div class="form-group">
              <label>Descripción (opcional):</label>
              <textarea class="form-control" id="descripcion_egreso" rows="2"></textarea>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-danger" id="btnGuardarEgreso">
            <i class="fas fa-save mr-1"></i>Guardar
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Detalle Movimiento -->
  <div class="modal fade" id="modalDetalle">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header bg-info">
          <h5 class="modal-title"><i class="fas fa-info-circle mr-2"></i>Detalle del Movimiento</h5>
          <button type="button" class="close" data-dismiss="modal">
            <span>&times;</span>
          </button>
        </div>
        <div class="modal-body" id="contenido-detalle-movimiento">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Generar Reporte -->
  <div class="modal fade" id="modalReporte">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header bg-primary">
          <h5 class="modal-title"><i class="fas fa-file-pdf mr-2"></i>Generar Reporte PDF</h5>
          <button type="button" class="close" data-dismiss="modal">
            <span>&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form id="formReporte">
            <div class="form-group">
              <label>Tipo de Reporte:</label>
              <select class="form-control" id="tipo_reporte" required>
                <option value="">-- Seleccione un reporte --</option>
                <option value="estado_resultados">Estado de Resultados</option>
                <option value="flujo_caja">Flujo de Caja Detallado</option>
                <option value="resumen_categorias">Resumen por Categorías</option>
                <option value="libro_diario">Libro Diario</option>
                <option value="arqueo_caja">Arqueo de Caja</option>
              </select>
            </div>
            <div class="form-group">
              <label>Fecha Inicio:</label>
              <input type="date" class="form-control" id="reporte_fecha_inicio" required>
            </div>
            <div class="form-group">
              <label>Fecha Fin:</label>
              <input type="date" class="form-control" id="reporte_fecha_fin" required>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-primary" id="btnGenerarPDF">
            <i class="fas fa-file-download mr-1"></i>Generar PDF
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Editar Movimiento -->
  <div class="modal fade" id="modalEditarMovimiento">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header bg-info">
          <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Editar Movimiento</h5>
          <button type="button" class="close" data-dismiss="modal">
            <span>&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form id="formEditarMovimiento">
            <input type="hidden" id="id_movimiento_edit">
            <div class="form-group">
              <label>Concepto:</label>
              <input type="text" class="form-control" id="concepto_edit" readonly>
            </div>
            <div class="form-group">
              <label>Fecha:</label>
              <input type="text" class="form-control" id="fecha_edit" readonly>
            </div>
            <div class="form-group">
              <label>Tipo:</label>
              <input type="text" class="form-control" id="tipo_edit" readonly>
            </div>
            <div class="form-group">
              <label>Monto Actual:</label>
              <input type="text" class="form-control" id="monto_actual_edit" readonly>
            </div>
            <div class="form-group">
              <label>Monto Nuevo:</label>
              <input type="number" class="form-control" id="monto_nuevo_edit" min="0" step="1000" required>
            </div>
            <div class="alert alert-warning">
              <i class="fas fa-info-circle mr-2"></i>
              <small>Se actualizarán automáticamente todos los saldos posteriores.</small>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-info" id="btnGuardarEdicion">
            <i class="fas fa-save mr-1"></i>Guardar Cambios
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
<script src="../js/Contabilidad.js?v=<?php echo @filemtime('../js/Contabilidad.js'); ?>"></script>
