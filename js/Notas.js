$(document).ready(function () {

    // Mapa de colores para estilos
    const colores = {
        yellow: { bg: '#fff9c4', border: '#ffc107', text: '#212529', header: '#ffc107' },
        blue:   { bg: '#e0f7fa', border: '#17a2b8', text: '#212529', header: '#17a2b8' },
        green:  { bg: '#e8f5e9', border: '#28a745', text: '#212529', header: '#28a745' },
        red:    { bg: '#fdecea', border: '#dc3545', text: '#212529', header: '#dc3545' },
        purple: { bg: '#f3e5f5', border: '#6f42c1', text: '#212529', header: '#6f42c1' },
        orange: { bg: '#fff3e0', border: '#fd7e14', text: '#212529', header: '#fd7e14' },
        pink:   { bg: '#fce4ec', border: '#e83e8c', text: '#212529', header: '#e83e8c' },
        gray:   { bg: '#f5f5f5', border: '#6c757d', text: '#212529', header: '#6c757d' }
    };

    // Cargar notas al iniciar
    cargarNotas();

    // ─── Selector de color ───────────────────────────────────────────────────
    $(document).on('click', '.color-opcion', function () {
        $('.color-opcion').css('border', '3px solid transparent');
        $(this).css('border', '3px solid #333');
        const color = $(this).data('color');
        $('#nota-color').val(color);
        // Actualizar header del modal
        const c = colores[color] || colores.yellow;
        $('#modal-nota-header').css('background-color', c.header);
        $('#btn-guardar-nota').css('background-color', c.header).css('border-color', c.header);
    });

    // ─── Abrir modal para NUEVA nota ─────────────────────────────────────────
    $('#btn-nueva-nota').on('click', function () {
        resetearModal();
    });

    // ─── Guardar nota (crear o editar) ───────────────────────────────────────
    $('#btn-guardar-nota').on('click', function () {
        const id        = $('#nota-id').val();
        const titulo    = $('#nota-titulo').val().trim();
        const contenido = $('#nota-contenido').val().trim();
        const color     = $('#nota-color').val() || 'yellow';

        if (titulo === '') {
            Swal.fire({ icon: 'warning', title: 'Falta el título', text: 'Por favor escribe un título para la nota.' });
            return;
        }
        if (contenido === '') {
            Swal.fire({ icon: 'warning', title: 'Falta el contenido', text: 'Por favor escribe el contenido de la nota.' });
            return;
        }

        const funcion = id === '' ? 'crear' : 'editar';
        const datos   = { funcion, titulo, contenido, color };
        if (id !== '') datos.id = id;

        $.post('../controlador/NotaController.php', datos, function (response) {
            if (response === 'add' || response === 'edit') {
                $('#modalNota').modal('hide');
                cargarNotas();
                const msg = funcion === 'crear' ? 'Nota creada correctamente' : 'Nota actualizada correctamente';
                Swal.fire({ icon: 'success', title: msg, timer: 1500, showConfirmButton: false });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo guardar la nota. Verifica los campos.' });
            }
        });
    });

    // ─── Cargar y renderizar notas ───────────────────────────────────────────
    function cargarNotas() {
        $.post('../controlador/NotaController.php', { funcion: 'listar' }, function (response) {
            const notas = JSON.parse(response);
            const contenedor = $('#contenedor-notas');

            // Limpiar notas anteriores (excepto el mensaje de vacío)
            contenedor.find('.nota-card-wrapper').remove();

            if (notas.length === 0) {
                $('#sin-notas').show();
                return;
            }

            $('#sin-notas').hide();

            notas.forEach(function (nota) {
                const c = colores[nota.color] || colores.yellow;
                const contenidoCorto = nota.contenido.length > 150
                    ? nota.contenido.substring(0, 150) + '...'
                    : nota.contenido;

                const fechaFormateada = formatearFecha(nota.fecha_actualizacion);

                const card = `
                <div class="col-12 col-sm-6 col-md-4 col-lg-3 mb-4 nota-card-wrapper" data-id="${nota.id_nota}">
                  <div class="card h-100 shadow-sm nota-card"
                       style="border-left: 5px solid ${c.border}; background-color: ${c.bg}; border-radius: 10px;">
                    <div class="card-header d-flex justify-content-between align-items-center py-2"
                         style="background-color: ${c.header}; border-radius: 8px 8px 0 0;">
                      <h6 class="mb-0 font-weight-bold text-white text-truncate" style="max-width:75%;">
                        <i class="fas fa-sticky-note mr-1"></i>${escapeHtml(nota.titulo)}
                      </h6>
                      <div class="nota-acciones">
                        <button class="btn btn-sm btn-light btn-editar-nota mr-1" data-id="${nota.id_nota}" title="Editar">
                          <i class="fas fa-pencil-alt"></i>
                        </button>
                        <button class="btn btn-sm btn-danger btn-borrar-nota" data-id="${nota.id_nota}" data-titulo="${escapeHtml(nota.titulo)}" title="Eliminar">
                          <i class="fas fa-trash-alt"></i>
                        </button>
                      </div>
                    </div>
                    <div class="card-body py-2 btn-ver-nota" data-id="${nota.id_nota}"
                         style="cursor:pointer;" title="Clic para ver completa">
                      <p class="mb-1" style="white-space:pre-wrap; font-size:0.9rem; color:${c.text};">${escapeHtml(contenidoCorto)}</p>
                    </div>
                    <div class="card-footer py-1" style="background:transparent; border-top: 1px solid ${c.border}40;">
                      <small class="text-muted"><i class="fas fa-clock mr-1"></i>${fechaFormateada}</small>
                    </div>
                  </div>
                </div>`;

                contenedor.append(card);
            });
        });
    }

    // ─── Ver nota completa ───────────────────────────────────────────────────
    $(document).on('click', '.btn-ver-nota', function () {
        const id = $(this).data('id');
        $.post('../controlador/NotaController.php', { funcion: 'obtener', id }, function (response) {
            const nota = JSON.parse(response);
            if (nota.error) return;
            const c = colores[nota.color] || colores.yellow;
            $('#ver-nota-header').css('background-color', c.header);
            $('#ver-nota-titulo').html('<i class="fas fa-sticky-note mr-2" style="color:white;"></i><span style="color:white;">' + escapeHtml(nota.titulo) + '</span>');
            $('#ver-nota-contenido').text(nota.contenido);
            $('#modalVerNota').modal('show');
        });
    });

    // ─── Editar nota ─────────────────────────────────────────────────────────
    $(document).on('click', '.btn-editar-nota', function (e) {
        e.stopPropagation();
        const id = $(this).data('id');
        $.post('../controlador/NotaController.php', { funcion: 'obtener', id }, function (response) {
            const nota = JSON.parse(response);
            if (nota.error) return;

            resetearModal();
            $('#nota-id').val(nota.id_nota);
            $('#nota-titulo').val(nota.titulo);
            $('#nota-contenido').val(nota.contenido);
            $('#nota-color').val(nota.color);

            // Marcar color seleccionado
            $('.color-opcion').css('border', '3px solid transparent');
            $(`.color-opcion[data-color="${nota.color}"]`).css('border', '3px solid #333');

            // Actualizar header del modal
            const c = colores[nota.color] || colores.yellow;
            $('#modal-nota-header').css('background-color', c.header);
            $('#btn-guardar-nota').css('background-color', c.header).css('border-color', c.header);
            $('#modal-nota-titulo').html('<i class="fas fa-edit mr-2"></i>Editar Nota');

            $('#modalNota').modal('show');
        });
    });

    // ─── Borrar nota ─────────────────────────────────────────────────────────
    $(document).on('click', '.btn-borrar-nota', function (e) {
        e.stopPropagation();
        const id     = $(this).data('id');
        const titulo = $(this).data('titulo');

        if (confirm('¿Eliminar la nota "' + titulo + '"? Esta acción no se puede deshacer.')) {
            $.post('../controlador/NotaController.php', { funcion: 'borrar', id: id }, function (response) {
                const resp = $.trim(response);
                if (resp === 'borrado') {
                    cargarNotas();
                    Swal.fire({ icon: 'success', title: 'Nota eliminada', timer: 1200, showConfirmButton: false });
                } else {
                    alert('No se pudo eliminar la nota. Respuesta del servidor: ' + resp);
                }
            });
        }
    });

    // ─── Resetear modal al cerrar ────────────────────────────────────────────
    $('#modalNota').on('hidden.bs.modal', function () {
        resetearModal();
    });

    // ─── Helpers ─────────────────────────────────────────────────────────────
    function resetearModal() {
        $('#nota-id').val('');
        $('#nota-titulo').val('');
        $('#nota-contenido').val('');
        $('#nota-color').val('yellow');
        $('.color-opcion').css('border', '3px solid transparent');
        $(`.color-opcion[data-color="yellow"]`).css('border', '3px solid #333');
        $('#modal-nota-header').css('background-color', '#ffc107');
        $('#btn-guardar-nota').css('background-color', '#ffc107').css('border-color', '#ffc107');
        $('#modal-nota-titulo').html('<i class="fas fa-sticky-note mr-2"></i>Nueva Nota');
    }

    function escapeHtml(text) {
        return $('<div>').text(text).html();
    }

    function formatearFecha(fechaStr) {
        if (!fechaStr) return '';
        const fecha = new Date(fechaStr.replace(' ', 'T'));
        const opciones = { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' };
        return fecha.toLocaleDateString('es-CO', opciones);
    }

    // Marcar amarillo por defecto al abrir
    $(`.color-opcion[data-color="yellow"]`).css('border', '3px solid #333');
});
