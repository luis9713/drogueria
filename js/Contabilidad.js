$(document).ready(function () {
  function parsearJSONSeguro(response) {
    try {
      return JSON.parse(response);
    } catch (e) {
      return null;
    }
  }

  // Cargar datos directamente
  cargar_resumen();
  cargar_movimientos();

  // Función para formatear números con separador de miles
  function formatearNumero(numero) {
    return Math.round(numero).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  }

  // Mostrar/ocultar filtros
  $(document).on('click', '#btnFiltros', function() {
    $('#filtros-contabilidad').slideToggle();
  });

  // Botón registrar ingreso
  $(document).on('click', '#btnRegistrarIngreso', function() {
    limpiarFormularioIngreso();
    $('#modalIngreso').modal('show');
  });

  // Botón registrar egreso
  $(document).on('click', '#btnRegistrarEgreso', function() {
    limpiarFormularioEgreso();
    $('#modalEgreso').modal('show');
  });

  // Guardar ingreso
  $(document).on('click', '#btnGuardarIngreso', function() {
    let concepto = $('#concepto_ingreso').val();
    let monto = $('#monto_ingreso').val();
    let categoria = $('#categoria_ingreso').val();
    let cuenta = $('#cuenta_ingreso').val();
    let descripcion = $('#descripcion_ingreso').val();

    if (!concepto || !monto || monto <= 0) {
      Swal.fire({
        icon: 'error',
        title: 'Campos incompletos',
        text: 'Por favor complete todos los campos requeridos'
      });
      return;
    }

    registrar_ingreso(concepto, monto, categoria, cuenta, descripcion);
  });

  // Guardar egreso
  $(document).on('click', '#btnGuardarEgreso', function() {
    let concepto = $('#concepto_egreso').val();
    let monto = $('#monto_egreso').val();
    let categoria = $('#categoria_egreso').val();
    let cuenta = $('#cuenta_egreso').val();
    let descripcion = $('#descripcion_egreso').val();

    if (!concepto || !monto || monto <= 0) {
      Swal.fire({
        icon: 'error',
        title: 'Campos incompletos',
        text: 'Por favor complete todos los campos requeridos'
      });
      return;
    }

    registrar_egreso(concepto, monto, categoria, cuenta, descripcion);
  });

  // Aplicar filtros
  $(document).on('click', '#btnAplicarFiltros', function() {
    cargar_movimientos();
  });

  // Limpiar filtros
  $(document).on('click', '#btnLimpiarFiltros', function() {
    $('#fecha_inicio').val('');
    $('#fecha_fin').val('');
    $('#filtro_tipo').val('');
    cargar_movimientos();
  });

  // Botón generar reporte
  $(document).on('click', '#btnGenerarReporte', function() {
    // Establecer fechas por defecto (mes actual)
    let hoy = new Date();
    let primerDia = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
    $('#reporte_fecha_inicio').val(primerDia.toISOString().split('T')[0]);
    $('#reporte_fecha_fin').val(hoy.toISOString().split('T')[0]);
    $('#modalReporte').modal('show');
  });

  // Generar PDF
  $(document).on('click', '#btnGenerarPDF', function() {
    let tipo_reporte = $('#tipo_reporte').val();
    let fecha_inicio = $('#reporte_fecha_inicio').val();
    let fecha_fin = $('#reporte_fecha_fin').val();

    if (!tipo_reporte || !fecha_inicio || !fecha_fin) {
      Swal.fire({
        icon: 'error',
        title: 'Campos incompletos',
        text: 'Por favor complete todos los campos'
      });
      return;
    }

    // Validar que fecha_inicio no sea mayor que fecha_fin
    if (new Date(fecha_inicio) > new Date(fecha_fin)) {
      Swal.fire({
        icon: 'error',
        title: 'Fechas inválidas',
        text: 'La fecha de inicio no puede ser mayor que la fecha final'
      });
      return;
    }

    // Abrir PDF en nueva ventana
    let url = '../controlador/PDFContabilidadController.php?tipo=' + tipo_reporte + 
              '&fecha_inicio=' + fecha_inicio + '&fecha_fin=' + fecha_fin;
    
    window.open(url, '_blank');
    $('#modalReporte').modal('hide');
  });

  // Cargar resumen financiero
  function cargar_resumen() {
    let funcion = "obtener_resumen";
    $.post("../controlador/ContabilidadController.php", { funcion }, (response) => {
      const resumen = parsearJSONSeguro(response);
      if (!resumen) {
        return;
      }
      
      $('#total-ingresos').text('$' + formatearNumero(resumen.total_ingresos));
      $('#total-egresos').text('$' + formatearNumero(resumen.total_egresos));
      $('#saldo-actual').text('$' + formatearNumero(resumen.saldo_actual));
      $('#total-movimientos').text(resumen.total_movimientos);
    }).fail(function() {
      // Evitar bloquear toda la pantalla por un error temporal.
    });

    // Cargar saldo Nequi por separado
    $.post("../controlador/ContabilidadController.php", { funcion: 'obtener_saldo_nequi' }, (response) => {
      const data = parsearJSONSeguro(response);
      if (data) {
        $('#saldo-nequi').text('$' + formatearNumero(data.saldo_nequi || 0));
      } else {
        $('#saldo-nequi').text('$0');
      }
    }).fail(function() {
      $('#saldo-nequi').text('$0');
    });
  }

  // Cargar movimientos
  function cargar_movimientos() {
    let funcion = "listar_movimientos";
    let fecha_inicio = $('#fecha_inicio').val();
    let fecha_fin = $('#fecha_fin').val();
    let tipo = $('#filtro_tipo').val();

    $.post("../controlador/ContabilidadController.php", 
      { funcion, fecha_inicio, fecha_fin, tipo }, 
      (response) => {
        const movimientos = parsearJSONSeguro(response);
        if (!movimientos) {
          return;
        }
        
        if ($.fn.DataTable.isDataTable('#tabla-movimientos')) {
          $('#tabla-movimientos').DataTable().destroy();
        }

        $('#tabla-movimientos').DataTable({
          data: movimientos,
          columns: [
            { data: 'id_movimiento' },
            { data: 'fecha_movimiento' },
            { 
              data: 'tipo',
              render: function(data) {
                if (data === 'Ingreso') {
                  return '<span class="badge badge-success">Ingreso</span>';
                } else {
                  return '<span class="badge badge-danger">Egreso</span>';
                }
              }
            },
            {
              data: 'cuenta',
              render: function(data) {
                if (data === 'Nequi') {
                  return '<span class="badge" style="background-color:#6f42c1; color:#fff;">Nequi</span>';
                }
                return '<span class="badge badge-primary">Caja General</span>';
              }
            },
            { data: 'concepto' },
            { data: 'categoria' },
            { 
              data: 'monto',
              render: function(data, type, row) {
                let monto = '$' + formatearNumero(data);
                if (row.tipo === 'Ingreso') {
                  return '<span class="text-success font-weight-bold">+' + monto + '</span>';
                } else {
                  return '<span class="text-danger font-weight-bold">-' + monto + '</span>';
                }
              }
            },
            { 
              data: 'saldo',
              render: function(data) {
                return '$' + formatearNumero(data);
              }
            },
            { data: 'usuario' },
            {
              data: null,
              render: function(data) {
                return `
                  <button class="btn btn-info btn-sm ver-detalle" data-id="${data.id_movimiento}" title="Ver detalle">
                    <i class="fas fa-eye"></i>
                  </button>
                  <button class="btn btn-warning btn-sm editar-movimiento" data-id="${data.id_movimiento}" title="Editar monto">
                    <i class="fas fa-pencil-alt"></i>
                  </button>
                `;
              }
            }
          ],
          language: espanol,
          order: [[0, 'desc']],
          pageLength: 25
        });
      }
    ).fail(function() {
      if ($.fn.DataTable.isDataTable('#tabla-movimientos')) {
        $('#tabla-movimientos').DataTable().clear().draw();
      }
    });
  }

  // Ver detalle de movimiento
  $(document).on('click', '.ver-detalle', function() {
    let id_movimiento = $(this).data('id');
    let funcion = "obtener_detalle_movimiento";
    
    $.post("../controlador/ContabilidadController.php", 
      { funcion, id_movimiento }, 
      (response) => {
        const movimiento = parsearJSONSeguro(response);
        if (!movimiento || movimiento.error) {
          Swal.fire({
            icon: 'warning',
            title: 'No disponible',
            text: 'No se pudo cargar el detalle del movimiento'
          });
          return;
        }
        
        let tipo_badge = movimiento.tipo === 'Ingreso' 
          ? '<span class="badge badge-success">Ingreso</span>' 
          : '<span class="badge badge-danger">Egreso</span>';
        
        let html = `
          <table class="table table-sm">
            <tr>
              <td><strong>ID:</strong></td>
              <td>${movimiento.id_movimiento}</td>
            </tr>
            <tr>
              <td><strong>Fecha:</strong></td>
              <td>${movimiento.fecha_movimiento}</td>
            </tr>
            <tr>
              <td><strong>Tipo:</strong></td>
              <td>${tipo_badge}</td>
            </tr>
            <tr>
              <td><strong>Concepto:</strong></td>
              <td>${movimiento.concepto}</td>
            </tr>
            <tr>
              <td><strong>Cuenta:</strong></td>
              <td>${movimiento.cuenta || 'Caja General'}</td>
            </tr>
            <tr>
              <td><strong>Categoría:</strong></td>
              <td>${movimiento.categoria}</td>
            </tr>
            <tr>
              <td><strong>Monto:</strong></td>
              <td class="${movimiento.tipo === 'Ingreso' ? 'text-success' : 'text-danger'} font-weight-bold">
                ${movimiento.tipo === 'Ingreso' ? '+' : '-'}$${formatearNumero(movimiento.monto)}
              </td>
            </tr>
            <tr>
              <td><strong>Saldo Resultante:</strong></td>
              <td class="font-weight-bold">$${formatearNumero(movimiento.saldo)}</td>
            </tr>
            <tr>
              <td><strong>Usuario:</strong></td>
              <td>${movimiento.usuario}</td>
            </tr>
            ${movimiento.descripcion ? `
            <tr>
              <td><strong>Descripción:</strong></td>
              <td>${movimiento.descripcion}</td>
            </tr>
            ` : ''}
            ${movimiento.referencia ? `
            <tr>
              <td><strong>Referencia:</strong></td>
              <td>${movimiento.referencia}</td>
            </tr>
            ` : ''}
          </table>
        `;
        
        $('#contenido-detalle-movimiento').html(html);
        $('#modalDetalle').modal('show');
      }
    ).fail(function() {
      Swal.fire({
        icon: 'error',
        title: 'Error de conexión',
        text: 'No se pudo consultar el detalle del movimiento'
      });
    });
  });

  // Editar movimiento
  $(document).on('click', '.editar-movimiento', function() {
    let id_movimiento = $(this).data('id');
    let funcion = "obtener_detalle_movimiento";
    
    $.post("../controlador/ContabilidadController.php", 
      { funcion, id_movimiento }, 
      (response) => {
        const movimiento = parsearJSONSeguro(response);
        if (!movimiento || movimiento.error) {
          Swal.fire({
            icon: 'warning',
            title: 'No disponible',
            text: 'No se pudo cargar el movimiento para edición'
          });
          return;
        }
        
        $('#id_movimiento_edit').val(movimiento.id_movimiento);
        $('#concepto_edit').val(movimiento.concepto);
        $('#fecha_edit').val(movimiento.fecha_movimiento);
        $('#tipo_edit').val(movimiento.tipo);
        $('#monto_actual_edit').val('$' + formatearNumero(movimiento.monto));
        $('#monto_nuevo_edit').val(movimiento.monto);
        
        $('#modalEditarMovimiento').modal('show');
      }
    ).fail(function() {
      Swal.fire({
        icon: 'error',
        title: 'Error de conexión',
        text: 'No se pudo cargar el movimiento'
      });
    });
  });

  // Guardar edición de movimiento
  $(document).on('click', '#btnGuardarEdicion', function() {
    let id_movimiento = $('#id_movimiento_edit').val();
    let monto_nuevo = $('#monto_nuevo_edit').val();

    if (!monto_nuevo || monto_nuevo <= 0) {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'El monto debe ser mayor a 0'
      });
      return;
    }

    let funcion = "editar_movimiento";
    
    $.post("../controlador/ContabilidadController.php", 
      { funcion, id_movimiento, monto_nuevo }, 
      (response) => {
        if ((response || '').trim() === 'success') {
          $('#modalEditarMovimiento').modal('hide');
          Swal.fire({
            icon: 'success',
            title: 'Éxito',
            text: 'Movimiento editado correctamente. Se han actualizado todos los saldos posteriores.'
          });
          cargar_movimientos();
          cargar_resumen();
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo editar el movimiento'
          });
        }
      }
    ).fail(function() {
      Swal.fire({
        icon: 'error',
        title: 'Error de conexión',
        text: 'No se pudo guardar la edición'
      });
    });
  });

  // Registrar ingreso
  function registrar_ingreso(concepto, monto, categoria, cuenta, descripcion) {
    let funcion = "registrar_ingreso";
    
    $.post("../controlador/ContabilidadController.php", 
      { funcion, concepto, monto, categoria, cuenta, descripcion }, 
      (response) => {
        const respuesta = (response || '').trim();
        if (respuesta === 'success') {
          Swal.fire({
            icon: 'success',
            title: 'Ingreso Registrado',
            text: 'El ingreso se ha registrado correctamente',
            timer: 2000,
            showConfirmButton: false
          }).then(() => {
            $('#modalIngreso').modal('hide');
            cargar_resumen();
            cargar_movimientos();
          });
        } else if (respuesta === 'error_saldo_insuficiente') {
          Swal.fire({
            icon: 'warning',
            title: 'Saldo insuficiente',
            text: 'No hay saldo suficiente en la cuenta seleccionada'
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo registrar el ingreso'
          });
        }
      }
    ).fail(function() {
      Swal.fire({
        icon: 'error',
        title: 'Error de conexión',
        text: 'No se pudo registrar el ingreso por un problema de red'
      });
    });
  }

  // Registrar egreso
  function registrar_egreso(concepto, monto, categoria, cuenta, descripcion) {
    let funcion = "registrar_egreso";
    
    $.post("../controlador/ContabilidadController.php", 
      { funcion, concepto, monto, categoria, cuenta, descripcion }, 
      (response) => {
        const respuesta = (response || '').trim();
        if (respuesta === 'success') {
          Swal.fire({
            icon: 'success',
            title: 'Egreso Registrado',
            text: 'El egreso se ha registrado correctamente',
            timer: 2000,
            showConfirmButton: false
          }).then(() => {
            $('#modalEgreso').modal('hide');
            cargar_resumen();
            cargar_movimientos();
          });
        } else if (respuesta === 'error_saldo_insuficiente') {
          Swal.fire({
            icon: 'warning',
            title: 'Saldo insuficiente',
            text: 'No hay saldo suficiente en la cuenta seleccionada'
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo registrar el egreso'
          });
        }
      }
    ).fail(function() {
      Swal.fire({
        icon: 'error',
        title: 'Error de conexión',
        text: 'No se pudo registrar el egreso por un problema de red'
      });
    });
  }

  // Limpiar formularios
  function limpiarFormularioIngreso() {
    $('#concepto_ingreso').val('');
    $('#monto_ingreso').val('');
    $('#categoria_ingreso').val('Venta');
    $('#cuenta_ingreso').val('caja');
    $('#descripcion_ingreso').val('');
  }

  function limpiarFormularioEgreso() {
    $('#concepto_egreso').val('');
    $('#monto_egreso').val('');
    $('#categoria_egreso').val('Compra Inventario');
    $('#cuenta_egreso').val('caja');
    $('#descripcion_egreso').val('');
  }
});

let espanol = {
  sProcessing: "Procesando...",
  sLengthMenu: "Mostrar _MENU_ registros",
  sZeroRecords: "No se encontraron resultados",
  sEmptyTable: "Ningún dato disponible en esta tabla",
  sInfo: "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
  sInfoEmpty: "Mostrando registros del 0 al 0 de un total de 0 registros",
  sInfoFiltered: "(filtrado de un total de _MAX_ registros)",
  sInfoPostFix: "",
  sSearch: "Buscar:",
  sUrl: "",
  sInfoThousands: ",",
  sLoadingRecords: "Cargando...",
  oPaginate: {
    sFirst: "Primero",
    sLast: "Último",
    sNext: "Siguiente",
    sPrevious: "Anterior",
  },
  oAria: {
    sSortAscending: ": Activar para ordenar la columna de manera ascendente",
    sSortDescending: ": Activar para ordenar la columna de manera descendente",
  },
};
