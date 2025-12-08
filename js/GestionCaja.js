$(document).ready(function () {
  let id_caja_actual = null; // Variable global para almacenar el ID de la caja actual
  
  verificar_estado_caja();
  cargar_caja_actual();
  cargar_historial_cajas();

  // Botón abrir caja nueva
  $(document).on('click', '#btnAbrirCajaNueva', function() {
    mostrar_modal_abrir_caja();
  });

  // Botón cerrar caja
  $(document).on('click', '#btnCerrarCaja', function() {
    if (!id_caja_actual) {
      Swal.fire({
        icon: 'warning',
        title: 'Sin caja abierta',
        text: 'No hay una caja abierta para cerrar'
      });
      return;
    }
    mostrar_modal_cerrar_caja();
  });

  // Función para formatear números con separador de miles
  function formatearNumero(numero) {
    return Math.round(numero).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  }

  // Verificar estado de caja para mostrar botón de apertura
  function verificar_estado_caja() {
    let funcion = "verificar_caja_abierta";
    $.post("../controlador/CajaController.php", { funcion }, (response) => {
      const resultado = JSON.parse(response);
      
      if (resultado.estado === 'cerrada') {
        // No hay caja abierta - Mostrar botón de abrir
        $('#card-estado-caja').removeClass('card-success').addClass('card-warning');
        $('#info-estado-caja').html(`
          <div class="d-flex align-items-center">
            <i class="fas fa-exclamation-triangle fa-2x text-warning mr-3"></i>
            <div>
              <h5 class="mb-0 text-warning">No hay caja abierta</h5>
              <small class="text-muted">Debe abrir una caja para poder operar</small>
            </div>
          </div>
        `);
        $('#btnAbrirCajaNueva').show();
      } else {
        // Hay caja abierta - Mostrar información
        const caja = resultado.datos;
        $('#card-estado-caja').removeClass('card-warning').addClass('card-success');
        $('#info-estado-caja').html(`
          <div class="d-flex align-items-center">
            <i class="fas fa-check-circle fa-2x text-success mr-3"></i>
            <div>
              <h5 class="mb-0 text-success">Caja Abierta</h5>
              <small class="text-muted">
                <strong>Apertura:</strong> ${caja.fecha_apertura} | 
                <strong>Monto Inicial:</strong> $${formatearNumero(caja.monto_inicial)}
              </small>
            </div>
          </div>
        `);
        $('#btnAbrirCajaNueva').hide();
      }
    });
  }

  // Mostrar modal para abrir caja
  function mostrar_modal_abrir_caja() {
    Swal.fire({
      title: '🔓 Abrir Nueva Caja',
      html: `
        <div class="text-left">
          <p class="mb-3">Ingrese el monto inicial en efectivo con el que abrirá la caja.</p>
          <div class="form-group">
            <label for="monto_inicial_apertura_nueva">Monto Inicial (Efectivo):</label>
            <input type="number" id="monto_inicial_apertura_nueva" class="form-control" 
                   placeholder="Ej: 100000" min="0" step="1000" value="0" required>
          </div>
        </div>
      `,
      icon: 'info',
      showCancelButton: true,
      confirmButtonText: '✓ Abrir Caja',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#28a745',
      preConfirm: () => {
        const monto = document.getElementById('monto_inicial_apertura_nueva').value;
        if (!monto || Number(monto) < 0) {
          Swal.showValidationMessage('Debe ingresar un monto inicial válido');
          return false;
        }
        return { monto: monto };
      }
    }).then((result) => {
      if (result && (result.value || result)) {
        const datos = result.value || result;
        abrir_caja_nueva(datos.monto);
      }
    });
  }

  // Abrir caja nueva
  function abrir_caja_nueva(monto_inicial) {
    let funcion = "abrir_caja";
    $.post("../controlador/CajaController.php", 
      { funcion: funcion, monto_inicial: monto_inicial }, 
      (response) => {
        response = response.trim();
        
        if (response === 'caja_abierta') {
          Swal.fire({
            icon: 'success',
            title: '✓ Caja Abierta',
            text: 'La caja se ha abierto correctamente',
            timer: 2000,
            showConfirmButton: false
          }).then(() => {
            verificar_estado_caja();
            cargar_caja_actual();
            cargar_historial_cajas();
          });
        } else if (response === 'caja_ya_abierta') {
          Swal.fire({
            icon: 'info',
            title: 'Información',
            text: 'Ya existe una caja abierta',
            timer: 2000
          }).then(() => {
            verificar_estado_caja();
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            html: 'No se pudo abrir la caja.<br><small>' + response + '</small>'
          });
        }
      }
    );
  }

  // Cargar información de caja actual
  function cargar_caja_actual() {
    let funcion = "obtener_caja_actual";
    $.post("../controlador/CajaController.php", { funcion }, (response) => {
      const caja = JSON.parse(response);
      
      if (caja.error) {
        id_caja_actual = null; // No hay caja abierta
        $("#btnCerrarCaja").prop('disabled', true);
        $("#info-caja-actual").html(`
          <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            No hay caja abierta actualmente
          </div>
        `);
        $("#card-caja-actual").removeClass('card-success').addClass('card-warning');
        return;
      }

      // Guardar ID de caja actual
      id_caja_actual = caja.id_caja;
      $("#btnCerrarCaja").prop('disabled', false);

      const efectivo_esperado = parseFloat(caja.monto_inicial) + 
                               parseFloat(caja.total_ventas_contado) + 
                               parseFloat(caja.total_depositos_credito);

      const html = `
        <div class="row">
          <div class="col-md-6">
            <table class="table table-sm">
              <tr>
                <td><strong>Fecha Apertura:</strong></td>
                <td>${caja.fecha_apertura}</td>
              </tr>
              <tr>
                <td><strong>Usuario Apertura:</strong></td>
                <td>${caja.nombre_apertura} ${caja.apellido_apertura}</td>
              </tr>
              <tr>
                <td><strong>Monto Inicial:</strong></td>
                <td class="text-primary font-weight-bold">$${formatearNumero(caja.monto_inicial)}</td>
              </tr>
            </table>
          </div>
          <div class="col-md-6">
            <table class="table table-sm">
              <tr>
                <td><strong>Ventas Contado:</strong></td>
                <td class="text-success font-weight-bold">$${formatearNumero(caja.total_ventas_contado)} (${caja.cantidad_ventas_contado})</td>
              </tr>
              <tr>
                <td><strong>Ventas Crédito:</strong></td>
                <td class="text-info">$${formatearNumero(caja.total_ventas_credito)} (${caja.cantidad_ventas_credito})</td>
              </tr>
              <tr>
                <td><strong>Depósitos Crédito:</strong></td>
                <td class="text-info font-weight-bold">$${formatearNumero(caja.total_depositos_credito)}</td>
              </tr>
              <tr class="table-active">
                <td><strong>Efectivo Esperado:</strong></td>
                <td class="h5 mb-0 text-primary">$${formatearNumero(efectivo_esperado)}</td>
              </tr>
            </table>
          </div>
        </div>
      `;
      
      $("#info-caja-actual").html(html);
    });
  }

  // Cargar historial de cajas
  function cargar_historial_cajas() {
    let funcion = "listar_cajas";
    $.post("../controlador/CajaController.php", { funcion }, (response) => {
      const cajas = JSON.parse(response);
      
      if ($.fn.DataTable.isDataTable('#tabla-cajas')) {
        $('#tabla-cajas').DataTable().destroy();
      }

      $('#tabla-cajas').DataTable({
        data: cajas,
        columns: [
          { data: 'id_caja' },
          { data: 'fecha_apertura' },
          { data: 'fecha_cierre' },
          { data: 'monto_inicial' },
          { data: 'total_ventas_contado' },
          { data: 'efectivo_esperado' },
          { 
            data: 'monto_final',
            render: function(data, type, row) {
              if (data === '-') return '-';
              return '$' + data;
            }
          },
          { 
            data: 'diferencia',
            render: function(data, type, row) {
              if (data === '-') return '-';
              
              const valor = parseFloat(data.replace(/\./g, ''));
              let clase = '';
              let icono = '';
              
              if (valor > 0) {
                clase = 'text-success';
                icono = '<i class="fas fa-arrow-up mr-1"></i>';
              } else if (valor < 0) {
                clase = 'text-danger';
                icono = '<i class="fas fa-arrow-down mr-1"></i>';
              } else {
                clase = 'text-muted';
                icono = '<i class="fas fa-check mr-1"></i>';
              }
              
              return `<span class="${clase} font-weight-bold">${icono}$${data}</span>`;
            }
          },
          { 
            data: 'estado',
            render: function(data) {
              if (data === 'abierta') {
                return '<span class="badge badge-success"><i class="fas fa-lock-open mr-1"></i>Abierta</span>';
              } else {
                return '<span class="badge badge-secondary"><i class="fas fa-lock mr-1"></i>Cerrada</span>';
              }
            }
          },
          { data: 'usuario_apertura' },
          {
            data: null,
            render: function(data, type, row) {
              return `<button class="btn btn-sm btn-info ver-detalle" data-id="${row.id_caja}">
                        <i class="fas fa-eye"></i> Ver
                      </button>`;
            }
          }
        ],
        language: espanol,
        order: [[0, 'desc']],
        pageLength: 25
      });
    });
  }

  // Ver detalle de caja
  $(document).on('click', '.ver-detalle', function() {
    const id_caja = $(this).data('id');
    mostrar_detalle_caja(id_caja);
  });

  function mostrar_detalle_caja(id_caja) {
    let funcion = "obtener_caja_detalle";
    $.post("../controlador/CajaController.php", { funcion, id_caja }, (response) => {
      const caja = JSON.parse(response);
      
      if (caja.error) {
        Swal.fire('Error', 'No se pudo cargar el detalle de la caja', 'error');
        return;
      }

      const efectivo_esperado = parseFloat(caja.monto_inicial || 0) + 
                               parseFloat(caja.total_ventas_contado || 0) + 
                               parseFloat(caja.total_depositos_credito || 0);

      let badge_diferencia = '';
      if (caja.diferencia !== null) {
        const dif = parseFloat(caja.diferencia);
        if (dif > 0) {
          badge_diferencia = `<span class="badge badge-success">Sobrante: $${formatearNumero(dif)}</span>`;
        } else if (dif < 0) {
          badge_diferencia = `<span class="badge badge-danger">Faltante: $${formatearNumero(Math.abs(dif))}</span>`;
        } else {
          badge_diferencia = `<span class="badge badge-success">Cuadre Exacto</span>`;
        }
      }

      const html = `
        <div class="row">
          <div class="col-md-6">
            <h5 class="border-bottom pb-2">Información General</h5>
            <table class="table table-sm">
              <tr>
                <td><strong>ID Caja:</strong></td>
                <td>#${caja.id_caja}</td>
              </tr>
              <tr>
                <td><strong>Estado:</strong></td>
                <td>
                  ${caja.estado === 'abierta' 
                    ? '<span class="badge badge-success">Abierta</span>' 
                    : '<span class="badge badge-secondary">Cerrada</span>'}
                </td>
              </tr>
              <tr>
                <td><strong>Fecha Apertura:</strong></td>
                <td>${caja.fecha_apertura || '-'}</td>
              </tr>
              <tr>
                <td><strong>Usuario Apertura:</strong></td>
                <td>${caja.nombre_apertura} ${caja.apellido_apertura}</td>
              </tr>
              <tr>
                <td><strong>Fecha Cierre:</strong></td>
                <td>${caja.fecha_cierre || '-'}</td>
              </tr>
              <tr>
                <td><strong>Usuario Cierre:</strong></td>
                <td>${caja.nombre_cierre ? caja.nombre_cierre + ' ' + caja.apellido_cierre : '-'}</td>
              </tr>
            </table>
          </div>
          <div class="col-md-6">
            <h5 class="border-bottom pb-2">Movimientos</h5>
            <table class="table table-sm">
              <tr>
                <td><strong>Monto Inicial:</strong></td>
                <td>$${formatearNumero(caja.monto_inicial)}</td>
              </tr>
              <tr class="table-success">
                <td><strong>Ventas Contado:</strong></td>
                <td>$${formatearNumero(caja.total_ventas_contado)}</td>
              </tr>
              <tr class="table-info">
                <td><strong>Ventas Crédito:</strong></td>
                <td>$${formatearNumero(caja.total_ventas_credito)}</td>
              </tr>
              <tr class="table-info">
                <td><strong>Depósitos Crédito:</strong></td>
                <td>$${formatearNumero(caja.total_depositos_credito)}</td>
              </tr>
              <tr class="table-active">
                <td><strong>Efectivo Esperado:</strong></td>
                <td class="font-weight-bold">$${formatearNumero(efectivo_esperado)}</td>
              </tr>
              <tr class="table-primary">
                <td><strong>Efectivo Real:</strong></td>
                <td class="font-weight-bold">${caja.monto_final ? '$' + formatearNumero(caja.monto_final) : '-'}</td>
              </tr>
              <tr>
                <td><strong>Diferencia:</strong></td>
                <td>${badge_diferencia || '-'}</td>
              </tr>
            </table>
          </div>
        </div>
        ${caja.observaciones ? `
          <div class="row mt-3">
            <div class="col-12">
              <h5 class="border-bottom pb-2">Observaciones</h5>
              <p>${caja.observaciones}</p>
            </div>
          </div>
        ` : ''}
      `;

      $('#contenido-detalle-caja').html(html);
      $('#modal-detalle-caja').modal('show');
    });
  }

  // Mostrar modal para cerrar caja
  function mostrar_modal_cerrar_caja() {
    // Obtener información actualizada de la caja
    let funcion = "obtener_caja_actual";
    $.post("../controlador/CajaController.php", { funcion }, (response) => {
      const caja = JSON.parse(response);
      
      if (caja.error) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'No se pudo obtener la información de la caja'
        });
        return;
      }

      const efectivo_esperado = parseFloat(caja.monto_inicial) + 
                               parseFloat(caja.total_ventas_contado) + 
                               parseFloat(caja.total_depositos_credito);

      Swal.fire({
        title: '🔒 Cerrar Caja',
        html: `
          <div class="text-left">
            <div class="alert alert-info">
              <strong>Efectivo Esperado:</strong> $${formatearNumero(efectivo_esperado)}
            </div>
            <div class="form-group">
              <label for="efectivo_real_cierre">Efectivo Real Contado:</label>
              <input type="number" id="efectivo_real_cierre" class="form-control" 
                     placeholder="Ingrese el efectivo real" min="0" step="1000" value="${Math.round(efectivo_esperado)}">
            </div>
            <div class="form-group">
              <label for="observaciones_cierre">Observaciones (opcional):</label>
              <textarea id="observaciones_cierre" class="form-control" rows="3" 
                        placeholder="Ej: Todo en orden, sin novedades"></textarea>
            </div>
            <div id="info_diferencia" class="alert alert-warning" style="display:none;">
              <strong>Diferencia:</strong> <span id="valor_diferencia"></span>
            </div>
          </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '✓ Cerrar Caja',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        width: '600px',
        onOpen: () => {
          // Calcular diferencia al cambiar el efectivo real
          $('#efectivo_real_cierre').on('input', function() {
            const efectivo_real = parseFloat($(this).val()) || 0;
            const diferencia = efectivo_real - efectivo_esperado;
            
            if (diferencia !== 0) {
              $('#info_diferencia').show();
              const color = diferencia > 0 ? 'success' : 'danger';
              const signo = diferencia > 0 ? '+' : '';
              $('#valor_diferencia').html(`<span class="text-${color}">${signo}$${formatearNumero(diferencia)}</span>`);
            } else {
              $('#info_diferencia').hide();
            }
          });
        },
        preConfirm: () => {
          const efectivo_real = document.getElementById('efectivo_real_cierre').value;
          const observaciones = document.getElementById('observaciones_cierre').value;
          
          if (!efectivo_real || efectivo_real < 0) {
            Swal.showValidationMessage('Debe ingresar el efectivo real');
            return false;
          }
          
          return { 
            efectivo_real: efectivo_real,
            observaciones: observaciones
          };
        }
      }).then((result) => {
        // Compatibilidad con versiones antiguas de SweetAlert2
        if (result && (result.value || result)) {
          const datos = result.value || result;
          cerrar_caja(caja.id_caja, datos.efectivo_real, datos.observaciones);
        }
      });
    });
  }

  // Cerrar caja
  function cerrar_caja(id_caja, efectivo_real, observaciones) {
    let funcion = "cerrar_caja";
    $.post("../controlador/CajaController.php", 
      { 
        funcion: funcion,
        id_caja: id_caja,
        efectivo_real: efectivo_real,
        observaciones: observaciones
      }, 
      (response) => {
        const resultado = JSON.parse(response);
        
        if (resultado.success) {
          Swal.fire({
            icon: 'success',
            title: '✓ Caja Cerrada',
            html: `
              <p>La caja se ha cerrado correctamente</p>
              ${resultado.utilidad ? `<p class="text-success"><strong>Utilidad del día: $${formatearNumero(resultado.utilidad)}</strong></p>` : ''}
            `,
            timer: 3000,
            showConfirmButton: true
          }).then(() => {
            cargar_caja_actual();
            cargar_historial_cajas();
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo cerrar la caja'
          });
        }
      }
    );
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
    sPrevious: "Anterior"
  },
  oAria: {
    sSortAscending: ": Activar para ordenar la columna de manera ascendente",
    sSortDescending: ": Activar para ordenar la columna de manera descendente"
  }
};
