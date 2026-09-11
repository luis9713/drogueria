function mostrar_stock_riesgo() {
    let funcion = "stock_riesgo";
    $.post('../controlador/LoteController.php', { funcion }, (response) => {
        try {
            const lotes = JSON.parse(response);
            $('#contador-bajo-stock').text(lotes.length);
            $('#total-bajo-stock').text(lotes.length);

            $('#stocks').DataTable({
                data: lotes,
                columns: [
                    {
                        data: "stock",
                        render: function(data, type, row) {
                            return `<span class="badge badge-danger">${row.stock || 0}</span>`;
                        }
                    },
                    { data: "medicamento" },
                    { data: "concentracion" },
                    { data: "adicional" },
                    { data: "laboratorio" },
                    { data: "presentacion" },
                    { data: "tipo" },
                    {
                        data: null,
                        render: function(data, type, row) {
                            return `<button type="button" class="descontinuar-producto btn btn-sm btn-outline-danger" data-id="${row.id_producto}" data-nombre="${row.medicamento}" title="Descontinuar producto">
                                <i class="fas fa-ban"></i> Descontinuar
                            </button>`;
                        }
                    }
                ],
                destroy: true,
                language: espanol,
                order: [[0, "asc"]],
                pageLength: 25
            });
        } catch (error) {
            console.error("Error al parsear stock:", error);
        }
    }).fail(function(xhr, status, error) {
        console.error("Error en la petición de stock:", error);
    });
}

$(document).ready(function() {
    function actualizar_ultima_revision() {
        const ahora = new Date();
        const fecha = ahora.toLocaleDateString('es-ES');
        const hora = ahora.toLocaleTimeString('es-ES');
        $('#ultima-revision').text(`${fecha} ${hora}`);
    }

    mostrar_stock_riesgo();
    actualizar_ultima_revision();

    setInterval(function() {
        mostrar_stock_riesgo();
        actualizar_ultima_revision();
    }, 300000);
});

$(document).on('click', '.descontinuar-producto', function() {
    const id = $(this).data('id');
    const nombre = $(this).data('nombre');

    Swal.fire({
        title: `Descontinuar ${nombre}?`,
        text: 'El producto dejará de aparecer en las alertas y en los listados operativos. Las ventas históricas se conservarán.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, descontinuar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc3545'
    }).then((result) => {
        if (!result.value) {
            return;
        }

        $.post('../controlador/ProductoController.php', { funcion: 'descontinuar_sin_stock', id }, (response) => {
            if ($.trim(response) === 'descontinuado') {
                Swal.fire('Producto descontinuado', 'Las ventas históricas se conservaron.', 'success');
                mostrar_stock_riesgo();
                return;
            }

            const mensaje = $.trim(response) === 'error_stock'
                ? 'No se puede descontinuar porque el producto tiene existencias.'
                : 'No fue posible descontinuar el producto. Inténtalo de nuevo.';
            Swal.fire('No se pudo descontinuar', mensaje, 'error');
        }).fail(() => {
            Swal.fire('Error de comunicación', 'No fue posible contactar al servidor.', 'error');
        });
    });
});

let espanol = {
    "sProcessing": "Procesando...",
    "sLengthMenu": "Mostrar _MENU_ registros",
    "sZeroRecords": "No se encontraron resultados",
    "sEmptyTable": "Ningún dato disponible en esta tabla",
    "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
    "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
    "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
    "sInfoPostFix": "",
    "sSearch": "Buscar:",
    "sUrl": "",
    "sInfoThousands": ",",
    "sLoadingRecords": "Cargando...",
    "oPaginate": {
        "sFirst": "Primero",
        "sLast": "Último",
        "sNext": "Siguiente",
        "sPrevious": "Anterior"
    },
    "oAria": {
        "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
        "sSortDescending": ": Activar para ordenar la columna de manera descendente"
    },
    "buttons": {
        "copy": "Copiar",
        "colvis": "Visibilidad"
    }
};
