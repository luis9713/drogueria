function cargarProductosInactivos() {
    $.post('../controlador/ProductoController.php', { funcion: 'buscar_inactivos' }, (response) => {
        let productos;
        try {
            productos = JSON.parse(response);
        } catch (error) {
            console.error('Error al cargar productos inactivos:', error);
            return;
        }

        $('#productos-inactivos').DataTable({
            data: productos,
            columns: [
                { data: 'nombre' },
                { data: 'concentracion' },
                { data: 'adicional' },
                { data: 'laboratorio' },
                { data: 'presentacion' },
                { data: 'tipo' },
                {
                    data: 'precio',
                    render: (precio) => `$${precio}`
                },
                {
                    data: null,
                    render: (data, type, producto) => `<button type="button" class="reactivar-producto btn btn-sm btn-success" data-id="${producto.id_producto}" data-nombre="${producto.nombre}">
                        <i class="fas fa-check"></i> Reactivar
                    </button>`
                }
            ],
            destroy: true,
            language: espanolProductosInactivos,
            order: [[0, 'asc']],
            pageLength: 25
        });
    }).fail(() => {
        Swal.fire('Error de comunicación', 'No fue posible cargar los productos inactivos.', 'error');
    });
}

$(document).ready(function() {
    cargarProductosInactivos();
});

$(document).on('click', '.reactivar-producto', function() {
    const id = $(this).data('id');
    const nombre = $(this).data('nombre');

    Swal.fire({
        title: `Reactivar ${nombre}?`,
        text: 'El producto volverá a aparecer en los listados operativos.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, reactivar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#28a745'
    }).then((result) => {
        if (!result.value) {
            return;
        }

        $.post('../controlador/ProductoController.php', { funcion: 'reactivar', id }, (response) => {
            if ($.trim(response) === 'reactivado') {
                Swal.fire('Producto reactivado', 'El producto ya está disponible nuevamente.', 'success');
                cargarProductosInactivos();
                return;
            }
            Swal.fire('No se pudo reactivar', 'El producto ya no está inactivo o no existe.', 'error');
        }).fail(() => {
            Swal.fire('Error de comunicación', 'No fue posible contactar al servidor.', 'error');
        });
    });
});

const espanolProductosInactivos = {
    sProcessing: 'Procesando...',
    sLengthMenu: 'Mostrar _MENU_ registros',
    sZeroRecords: 'No se encontraron productos inactivos',
    sEmptyTable: 'Ningún dato disponible en esta tabla',
    sInfo: 'Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros',
    sInfoEmpty: 'Mostrando registros del 0 al 0 de un total de 0 registros',
    sInfoFiltered: '(filtrado de un total de _MAX_ registros)',
    sSearch: 'Buscar:',
    oPaginate: {
        sFirst: 'Primero',
        sLast: 'Último',
        sNext: 'Siguiente',
        sPrevious: 'Anterior'
    }
};