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

      const total_nequi = parseFloat(caja.total_ventas_nequi || 0);
      const efectivo_esperado = parseFloat(caja.monto_inicial) + 
                               parseFloat(caja.total_ventas_contado) + 
                               parseFloat(caja.total_depositos_credito);
      const total_ventas_dia = parseFloat(caja.total_ventas_contado) + 
                               parseFloat(caja.total_ventas_credito) + 
                               total_nequi;

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
              <tr style="background-color:#f3e5f5;">
                <td><strong>Ventas Nequi 📱:</strong></td>
                <td style="color:#6f42c1;" class="font-weight-bold">$${formatearNumero(total_nequi)} (${caja.cantidad_ventas_nequi || 0})</td>
              </tr>
              <tr>
                <td><strong>Depósitos Crédito:</strong></td>
                <td class="text-info font-weight-bold">$${formatearNumero(caja.total_depositos_credito)}</td>
              </tr>
              <tr class="table-warning">
                <td><strong>Total Ventas del Día:</strong></td>
                <td class="font-weight-bold">$${formatearNumero(total_ventas_dia)}</td>
              </tr>
              <tr class="table-active">
                <td><strong>Efectivo Esperado (sin Nequi):</strong></td>
                <td class="h5 mb-0 text-primary">$${formatearNumero(efectivo_esperado)}</td>
              </tr>
            </table>
          </div>
        </div>
        ${total_nequi > 0 ? `
        <div class="alert alert-info mt-2 mb-0 py-2">
          <i class="fas fa-mobile-alt mr-1"></i>
          <strong>Nequi:</strong> $${formatearNumero(total_nequi)} en ventas digitales (no se cuentan en el efectivo físico de la caja).
        </div>` : ''}
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
          { 
            data: null,
            render: function(data, type, row) {
              // Ventas totales = contado + depósitos de crédito + nequi
              const contado = parseFloat((row.total_ventas_contado || '0').replace(/\./g, '').replace(',', '.')) || 0;
              const depositos = parseFloat((row.total_depositos_credito || '0').replace(/\./g, '').replace(',', '.')) || 0;
              const nequi = parseFloat((row.total_ventas_nequi || '0').replace(/\./g, '').replace(',', '.')) || 0;
              const total = contado + depositos + nequi;
              return formatearNumero(total);
            }
          },
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

      const total_nequi_det = parseFloat(caja.total_ventas_nequi_calc || caja.total_ventas_nequi || 0);
      const total_credito_det = parseFloat(caja.total_ventas_credito_calc || caja.total_ventas_credito || 0);
      const total_depositos_det = parseFloat(caja.total_depositos_credito || 0);
      const efectivo_esperado = parseFloat(caja.monto_inicial || 0) + 
                               parseFloat(caja.total_ventas_contado || 0) + 
                               total_depositos_det;
      // Total ventas del día = contado + depósitos de crédito + nequi (NO ventas a crédito, esas no son dinero recibido)
      const total_ventas_det = parseFloat(caja.total_ventas_contado || 0) + total_depositos_det + total_nequi_det;

      let badge_diferencia = '';
      if (caja.diferencia !== null && caja.diferencia !== undefined) {
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
                <td>$${formatearNumero(total_credito_det)}</td>
              </tr>
              <tr style="background-color:#f3e5f5;">
                <td><strong><i class="fas fa-mobile-alt" style="color:#6f42c1;"></i> Ventas Nequi:</strong></td>
                <td style="color:#6f42c1;" class="font-weight-bold">$${formatearNumero(total_nequi_det)}</td>
              </tr>
              <tr class="table-info">
                <td><strong>Depósitos Crédito:</strong></td>
                <td>$${formatearNumero(caja.total_depositos_credito)}</td>
              </tr>
              <tr class="table-warning">
                <td><strong>Total Ventas del Día:</strong></td>
                <td class="font-weight-bold">$${formatearNumero(total_ventas_det)}</td>
              </tr>
              <tr class="table-active">
                <td><strong>Efectivo Esperado (sin Nequi):</strong></td>
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
        ${total_nequi_det > 0 ? `
          <div class="alert alert-info mt-2 mb-0 py-2">
            <i class="fas fa-mobile-alt mr-1"></i>
            <strong>Nequi:</strong> $${formatearNumero(total_nequi_det)} en ventas digitales (no se cuentan en el efectivo físico de la caja).
          </div>` : ''}
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

      const total_nequi_modal = parseFloat(caja.total_ventas_nequi || 0);
      const efectivo_esperado = parseFloat(caja.monto_inicial) + 
                               parseFloat(caja.total_ventas_contado) + 
                               parseFloat(caja.total_depositos_credito);

      const nequi_info = total_nequi_modal > 0 
        ? `<div class="alert mb-2 py-2" style="background-color:#f3e5f5; border-left:4px solid #6f42c1;">
             <i class="fas fa-mobile-alt mr-1" style="color:#6f42c1;"></i>
             <strong style="color:#6f42c1;">Nequi esperado en cuenta:</strong>
             <span class="font-weight-bold" style="color:#6f42c1; float:right;">$${formatearNumero(total_nequi_modal)}</span>
           </div>`
        : '';

      Swal.fire({
        title: '🔒 Cerrar Caja',
        html: `
          <div class="text-left">
            <div class="alert alert-info mb-2">
              <strong>Efectivo Esperado (caja física):</strong> $${formatearNumero(efectivo_esperado)}
            </div>
            ${nequi_info}
            <div class="form-group">
              <label for="efectivo_real_cierre">Efectivo Real Contado:</label>
              <input type="number" id="efectivo_real_cierre" class="form-control" 
                     placeholder="Ingrese el efectivo real" min="0" step="1000" value="${Math.round(efectivo_esperado)}">
            </div>
            <div class="form-group">
              <label for="observaciones_cierre">Observaciones (opcional):</label>
              <textarea id="observaciones_cierre" class="form-control" rows="2" 
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
          
          if (!efectivo_real || Number(efectivo_real) < 0) {
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
          const utilidad = parseFloat(resultado.utilidad || 0);
          const total_nequi = parseFloat(resultado.total_nequi || 0);
          const total_consolidado = utilidad + total_nequi;

          let desglose = `
            <table class="table table-sm mt-2 mb-0">
              <tr>
                <td class="text-left"><i class="fas fa-money-bill-wave text-success mr-1"></i><strong>Efectivo en caja:</strong></td>
                <td class="text-right text-success font-weight-bold">$${formatearNumero(utilidad)}</td>
              </tr>`;
          if (total_nequi > 0) {
            desglose += `
              <tr>
                <td class="text-left"><i class="fas fa-mobile-alt mr-1" style="color:#6f42c1;"></i><strong>Ventas Nequi 📱:</strong></td>
                <td class="text-right font-weight-bold" style="color:#6f42c1;">$${formatearNumero(total_nequi)}</td>
              </tr>`;
          }
          desglose += `
              <tr class="table-active">
                <td class="text-left"><strong>Total consolidado:</strong></td>
                <td class="text-right font-weight-bold text-primary">$${formatearNumero(total_consolidado)}</td>
              </tr>
            </table>`;

          if (total_nequi > 0) {
            desglose += `<div class="alert alert-info mt-2 mb-0 py-2 text-left" style="font-size:0.85em;">
              <i class="fas fa-info-circle mr-1"></i>
              Recuerde verificar <strong>$${formatearNumero(total_nequi)}</strong> en su cuenta Nequi.
            </div>`;
          }

          Swal.fire({
            icon: 'success',
            title: '✓ Caja Cerrada',
            html: `<p class="mb-1">La caja se ha cerrado correctamente.</p>${desglose}`,
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#28a745'
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
