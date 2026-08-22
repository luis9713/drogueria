$(document).ready(function () {
  function parsearJSONSeguro(response) {
    try {
      return JSON.parse(response);
    } catch (e) {
      return null;
    }
  }

  // Verificar estado de caja para mostrar badge en navbar
  verificar_estado_caja_navbar();
  
  // Actualizar cada 30 segundos
  setInterval(verificar_estado_caja_navbar, 30000);

  // Función para verificar y actualizar badge en navbar
  function verificar_estado_caja_navbar() {
    let funcion = "verificar_caja_abierta";
    $.post("../controlador/CajaController.php", { funcion }, (response) => {
      try {
        const resultado = JSON.parse(response);
        
        if (resultado.estado === 'cerrada') {
          // Caja cerrada - Badge rojo
          $('#badge-estado-caja').html(`
            <i class="fas fa-lock mr-1"></i>
            <span class="badge badge-danger">Caja Cerrada</span>
          `);
        } else {
          // Caja abierta - Badge verde
          const caja = resultado.datos;
          $('#badge-estado-caja').html(`
            <i class="fas fa-cash-register mr-1"></i>
            <span class="badge badge-success">Caja Abierta</span>
          `);
        }
      } catch (e) {
        $('#badge-estado-caja').html(`
          <i class="fas fa-exclamation-triangle mr-1"></i>
          <span class="badge badge-warning">Error</span>
        `);
      }
    }).fail(function() {
      $('#badge-estado-caja').html(`
        <i class="fas fa-times mr-1"></i>
        <span class="badge badge-danger">Error</span>
      `);
    });
  }

  // Función para formatear números con separador de miles
  function formatearNumero(numero) {
    return Math.round(numero).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  }

  // Verificar si hay caja abierta al cargar cualquier página
  function verificar_caja_abierta() {
    let funcion = "verificar_caja_abierta";
    $.post("../controlador/CajaController.php", { funcion }, (response) => {
      try {
        const resultado = JSON.parse(response);
        
        if (resultado.estado === 'cerrada') {
          // No hay caja abierta - Mostrar modal obligatorio
          mostrar_modal_apertura();
        } else {
          // Hay caja abierta - Mostrar información en badge
          mostrar_info_caja(resultado.datos);
        }
      } catch (e) {
        console.error("Error parsing JSON:", e);
        console.log("Response:", response);
      }
    }).fail(function(xhr, status, error) {
      console.error("Error en la petición:", error);
    });
  }

  // Mostrar modal de apertura (no se puede cerrar sin abrir caja)
  function mostrar_modal_apertura() {
    Swal.fire({
      title: '🔒 Apertura de Caja Requerida',
      html: `
        <div class="text-left">
          <p class="mb-3">No hay una caja abierta. Debe abrir caja para continuar operando.</p>
          <div class="form-group">
            <label for="monto_inicial_apertura">Monto Inicial (Efectivo):</label>
            <input type="number" id="monto_inicial_apertura" class="form-control" 
                   placeholder="Ej: 100000" min="0" step="1000" required>
          </div>
        </div>
      `,
      icon: 'warning',
      showCancelButton: false,
      confirmButtonText: '✓ Abrir Caja',
      allowOutsideClick: false,
      allowEscapeKey: false,
      preConfirm: () => {
        const monto = document.getElementById('monto_inicial_apertura').value;
        if (!monto || Number(monto) <= 0) {
          Swal.showValidationMessage('Debe ingresar un monto inicial válido');
          return false;
        }
        return { monto: monto };
      }
    }).then((result) => {
      // Compatibilidad con versiones antiguas de SweetAlert2
      if (result.value || (result.isConfirmed !== undefined && result.isConfirmed)) {
        abrir_caja(result.value.monto);
      }
    });
  }

  // Abrir caja
  function abrir_caja(monto_inicial) {
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
            location.reload();
          });
        } else if (response === 'caja_ya_abierta') {
          Swal.fire({
            icon: 'info',
            title: 'Información',
            text: 'Ya existe una caja abierta',
            timer: 2000
          }).then(() => {
            location.reload();
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            html: 'No se pudo abrir la caja.<br><br><small>Respuesta del servidor: ' + response + '</small>',
            confirmButtonText: 'Entendido'
          });
        }
      }
    ).fail(function(xhr, status, error) {
      Swal.fire({
        icon: 'error',
        title: 'Error de Conexión',
        html: 'No se pudo conectar con el servidor.<br><br>' + 
              '<small>Error: ' + error + '</small>',
        confirmButtonText: 'Entendido'
      });
    });
  }

  // Mostrar información de caja abierta en badge
  function mostrar_info_caja(datos) {
    let funcion = "obtener_caja_actual";
    $.post("../controlador/CajaController.php", { funcion }, (response) => {
      const caja = parsearJSONSeguro(response);
      if (!caja || caja.error) {
        return;
      }
      
      const fecha_apertura = new Date(caja.fecha_apertura);
      const hora_apertura = fecha_apertura.toLocaleTimeString('es-CO', { 
        hour: '2-digit', 
        minute: '2-digit' 
      });

      const total_efectivo = parseFloat(caja.monto_inicial) + 
                            parseFloat(caja.total_ventas_contado) + 
                            parseFloat(caja.total_depositos_credito);

      const badge_html = `
        <div id="info-caja-badge" class="position-fixed" style="top: 70px; right: 20px; z-index: 1040;">
          <div class="card shadow-lg" style="min-width: 280px;">
            <div class="card-header bg-success text-white">
              <h6 class="mb-0">
                <i class="fas fa-cash-register mr-2"></i>Caja Abierta
                <button type="button" class="close text-white" id="btn-cerrar-caja-modal">
                  <i class="fas fa-lock"></i>
                </button>
              </h6>
            </div>
            <div class="card-body p-3">
              <small class="text-muted">Apertura: ${hora_apertura}</small>
              <hr class="my-2">
              <div class="row">
                <div class="col-12 mb-2">
                  <small class="text-muted">Monto Inicial:</small>
                  <div class="font-weight-bold">$${formatearNumero(caja.monto_inicial)}</div>
                </div>
                <div class="col-12 mb-2">
                  <small class="text-muted">Ventas Contado:</small>
                  <div class="text-success font-weight-bold">$${formatearNumero(caja.total_ventas_contado)}</div>
                </div>
                <div class="col-12 mb-2">
                  <small class="text-muted">Depósitos:</small>
                  <div class="text-info font-weight-bold">$${formatearNumero(caja.total_depositos_credito)}</div>
                </div>
                <div class="col-12">
                  <hr class="my-2">
                  <small class="text-muted">Total Efectivo:</small>
                  <div class="text-primary font-weight-bold h5 mb-0">$${formatearNumero(total_efectivo)}</div>
                </div>
              </div>
              <small class="text-muted d-block mt-2">
                <i class="fas fa-receipt mr-1"></i>${caja.cantidad_ventas_contado} ventas
              </small>
            </div>
          </div>
        </div>
      `;

      // Agregar o actualizar badge
      if ($('#info-caja-badge').length) {
        $('#info-caja-badge').replaceWith(badge_html);
      } else {
        $('body').append(badge_html);
      }
    }).fail(function() {
      // No interrumpir flujo de usuario por error temporal de red.
    });
  }

  // Evento para cerrar caja
  $(document).on('click', '#btn-cerrar-caja-modal', function() {
    mostrar_modal_cierre_caja();
  });

  // Modal para cerrar caja
  function mostrar_modal_cierre_caja() {
    let funcion = "obtener_caja_actual";
    $.post("../controlador/CajaController.php", { funcion }, (response) => {
      const caja = parsearJSONSeguro(response);
      if (!caja || caja.error) {
        Swal.fire({
          icon: 'warning',
          title: 'Sin caja activa',
          text: 'No se pudo obtener la caja actual para cerrar.'
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
                     placeholder="Ingrese el efectivo contado" min="0" step="1000" required>
            </div>
            <div class="form-group">
              <label for="observaciones_cierre">Observaciones (opcional):</label>
              <textarea id="observaciones_cierre" class="form-control" rows="3" 
                        placeholder="Ej: Faltante por error en cambio"></textarea>
            </div>
            <div id="diferencia_info" class="alert d-none"></div>
          </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '✓ Cerrar Caja',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#d33',
        preConfirm: () => {
          const efectivo_real = document.getElementById('efectivo_real_cierre').value;
          const observaciones = document.getElementById('observaciones_cierre').value;
          
          if (!efectivo_real || efectivo_real < 0) {
            Swal.showValidationMessage('Debe ingresar el efectivo contado');
            return false;
          }
          
          const diferencia = parseFloat(efectivo_real) - efectivo_esperado;
          
          return { 
            efectivo_real: efectivo_real, 
            observaciones: observaciones,
            diferencia: diferencia,
            id_caja: caja.id_caja
          };
        },
        onOpen: () => {
          // Calcular diferencia al escribir
          $('#efectivo_real_cierre').on('input', function() {
            const efectivo_real = parseFloat($(this).val()) || 0;
            const diferencia = efectivo_real - efectivo_esperado;
            const div_diferencia = $('#diferencia_info');
            
            if (efectivo_real > 0) {
              div_diferencia.removeClass('d-none alert-success alert-danger alert-warning');
              
              if (diferencia > 0) {
                div_diferencia.addClass('alert-success');
                div_diferencia.html(`<strong>Sobrante:</strong> $${formatearNumero(diferencia)}`);
              } else if (diferencia < 0) {
                div_diferencia.addClass('alert-danger');
                div_diferencia.html(`<strong>Faltante:</strong> $${formatearNumero(Math.abs(diferencia))}`);
              } else {
                div_diferencia.addClass('alert-success');
                div_diferencia.html(`<strong>✓ Cuadre exacto</strong>`);
              }
            }
          });
        }
      }).then((result) => {
        // Compatibilidad con versiones antiguas de SweetAlert2
        if (result && (result.value || result.isConfirmed)) {
          cerrar_caja(result.value || result);
        }
      });
    }).fail(function() {
      Swal.fire({
        icon: 'error',
        title: 'Error de conexión',
        text: 'No se pudo consultar la caja actual.'
      });
    });
  }

  // Cerrar caja
  function cerrar_caja(datos) {
    let funcion = "cerrar_caja";
    $.post("../controlador/CajaController.php", 
      { 
        funcion, 
        id_caja: datos.id_caja,
        efectivo_real: datos.efectivo_real,
        observaciones: datos.observaciones
      }, 
      (response) => {
        const resultado = parsearJSONSeguro(response);
        if (!resultado) {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Respuesta inválida del servidor al cerrar caja'
          });
          return;
        }
        
        if (resultado.success) {
          const diferencia = datos.diferencia;
          let mensaje = `Utilidad del día: $${formatearNumero(resultado.utilidad)}`;
          
          if (diferencia !== 0) {
            mensaje += `<br><br>Diferencia: $${formatearNumero(Math.abs(diferencia))} `;
            mensaje += diferencia > 0 ? '(Sobrante)' : '(Faltante)';
          }
          
          Swal.fire({
            icon: 'success',
            title: '✓ Caja Cerrada',
            html: mensaje,
            confirmButtonText: 'Entendido'
          }).then(() => {
            location.reload();
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo cerrar la caja'
          });
        }
      }
    ).fail(function() {
      Swal.fire({
        icon: 'error',
        title: 'Error de conexión',
        text: 'No se pudo cerrar la caja por un problema de red'
      });
    });
  }

  // Actualizar info cada 30 segundos si hay caja abierta
  setInterval(() => {
    if ($('#info-caja-badge').length) {
      let funcion = "verificar_caja_abierta";
      $.post("../controlador/CajaController.php", { funcion }, (response) => {
        const resultado = parsearJSONSeguro(response);
        if (!resultado) {
          return;
        }
        if (resultado.estado === 'abierta') {
          mostrar_info_caja(resultado.datos);
        }
      }).fail(function() {
        // Error temporal, reintenta en el próximo intervalo.
      });
    }
  }, 30000);
});
