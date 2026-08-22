function mostrar_lotes_riesgo() {
    let funcion = "buscar_lotes_riesgo";
    $.post('../controlador/LoteController.php', { funcion }, (response) => {
        try {
            const lotes = JSON.parse(response);
            $('#contador-vencidos').text(lotes.length);
            $('#total-vencidos').text(lotes.length);

            $('#lotes').DataTable({
                data: lotes,
                columns: [
                    { data: "id" },
                    { data: "nombre" },
                    { data: "stock" },
                    { data: "estado" },
                    { data: "laboratorio" },
                    { data: "presentacion" },
                    { data: "proveedor" },
                    {
                        data: null,
                        render: function(data, type, row) {
                            if (row.estado == 'danger') {
                                return `<span class="badge badge-danger">VENCIDO</span>`;
                            }
                            return `<small>${Math.abs(row.mes)} meses, ${Math.abs(row.dia)} días</small>`;
                        }
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            if (row.estado == 'danger') {
                                return `<button class="btn btn-sm btn-danger" onclick="marcarDescarte('${row.id}')"><i class="fas fa-trash"></i> Descartar</button>`;
                            }
                            return `<button class="btn btn-sm btn-warning" onclick="aplicarDescuento('${row.id}')"><i class="fas fa-percentage"></i> Descuento</button>`;
                        }
                    }
                ],
                columnDefs: [{
                    render: function(data, type, row) {
                        if (row.estado == 'danger') {
                            return `<h1 class="badge badge-danger">VENCIDO</h1>`;
                        }
                        if (row.estado == 'warning') {
                            return `<h1 class="badge badge-warning">PRÓXIMO</h1>`;
                        }
                        return '';
                    },
                    targets: [3]
                }],
                destroy: true,
                language: espanol,
                order: [[3, "desc"]],
                pageLength: 25
            });
        } catch (error) {
            console.error("Error al parsear lotes:", error);
        }
    }).fail(function(xhr, status, error) {
        console.error("Error en la petición de lotes:", error);
    });
}

$(document).ready(function() {
    function actualizar_ultima_revision() {
        const ahora = new Date();
        const fecha = ahora.toLocaleDateString('es-ES');
        const hora = ahora.toLocaleTimeString('es-ES');
        $('#ultima-revision').text(`${fecha} ${hora}`);
    }

    mostrar_lotes_riesgo();
    actualizar_ultima_revision();

    setInterval(function() {
        mostrar_lotes_riesgo();
        actualizar_ultima_revision();
    }, 300000);
});

function marcarDescarte(loteId) {
    const swalWithBootstrapButtons = Swal.mixin({
        customClass: {
            confirmButton: 'btn btn-success',
            cancelButton: 'btn btn-danger mr-1'
        },
        buttonsStyling: false
    });

    swalWithBootstrapButtons.fire({
        title: 'Desea eliminar lote ' + loteId + '?',
        text: "No podras revertir esto!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: 'Si, borra esto!',
        cancelButtonText: 'No, cancelar!',
        reverseButtons: true
    }).then((result) => {
        if (result.value) {
            $.post('../controlador/LoteController.php', {
                id: loteId,
                funcion: 'borrar'
            }, (response) => {
                if (response == 'borrado') {
                    swalWithBootstrapButtons.fire('Borrado!', 'El lote ' + loteId + ' fue borrado.', 'success');
                    mostrar_lotes_riesgo();
                } else {
                    swalWithBootstrapButtons.fire('No se pudo borrar!', 'El lote ' + loteId + ' no fue borrado porque esta siendo usado.', 'error');
                }
            });
        } else if (result.dismiss === Swal.DismissReason.cancel) {
            swalWithBootstrapButtons.fire('Cancelado', 'El lote ' + loteId + ' no fue borrado', 'error');
        }
    });
}

function aplicarDescuento(loteId) {
    Swal.fire({
        title: 'Aplicar descuento por vencimiento próximo',
        html: `
            <div style="text-align: left; margin: 10px 0;">
                <label>Porcentaje de descuento:</label>
                <input type="number" id="descuento" class="swal2-input" placeholder="Ej: 20" min="1" max="80">
            </div>
            <div style="text-align: left; margin: 10px 0;">
                <label>Motivo del descuento:</label>
                <textarea id="motivo" class="swal2-input" placeholder="Próximo vencimiento..."></textarea>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Aplicar Descuento',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const descuento = document.getElementById('descuento').value;
            const motivo = document.getElementById('motivo').value;

            if (!descuento || descuento < 1 || descuento > 80) {
                Swal.showValidationMessage('Por favor ingrese un descuento válido (1-80%)');
                return false;
            }

            if (!motivo.trim()) {
                Swal.showValidationMessage('Por favor ingrese un motivo');
                return false;
            }

            return { descuento: descuento, motivo: motivo };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const { descuento, motivo } = result.value;
            Swal.fire({
                title: 'Descuento Registrado',
                html: `
                    <p><strong>Lote:</strong> ${loteId}</p>
                    <p><strong>Descuento:</strong> ${descuento}%</p>
                    <p><strong>Motivo:</strong> ${motivo}</p>
                    <p style="color: #28a745;"><i class="fas fa-check-circle"></i> El descuento ha sido registrado para aplicar en ventas</p>
                `,
                icon: 'success',
                timer: 3000,
                showConfirmButton: false
            });
        }
    });
}

function generarReporteVencidos() {
    Swal.fire({
        title: 'Generando reporte...',
        text: 'Por favor espere mientras se genera el PDF',
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });

    window.open('../controlador/PDFController.php?funcion=reporte_vencidos', '_blank');
    setTimeout(() => {
        Swal.close();
    }, 1500);
}

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
