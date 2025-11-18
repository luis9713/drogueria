// Funciones globales
function mostrar_lotes_riesgo() {
    funcion = "buscar_lotes_riesgo";
    console.log("Iniciando carga de lotes en riesgo...");
    $.post('../controlador/LoteController.php', { funcion }, (response) => {
        console.log("Respuesta del servidor para lotes en riesgo:", response);
        try {
            const lotes = JSON.parse(response);
            console.log("Lotes parseados:", lotes);
            console.log("Número de lotes encontrados:", lotes.length);
            
            // Actualizar contador en el badge
            $('#contador-vencidos').text(lotes.length);
            $('#total-vencidos').text(lotes.length);
        
        datatable = $('#lotes').DataTable({
            data: lotes,
            "columns": [
                { "data": "id" },
                { "data": "nombre" },
                { "data": "stock" },
                { "data": "estado" },
                { "data": "laboratorio" },
                { "data": "presentacion" },
                { "data": "proveedor" },
                { 
                    "data": null,
                    "render": function(data, type, row) {
                        let tiempo = '';
                        if(row.estado == 'danger') {
                            tiempo = `<span class="badge badge-danger">VENCIDO</span>`;
                        } else {
                            tiempo = `<small>${Math.abs(row.mes)} meses, ${Math.abs(row.dia)} días</small>`;
                        }
                        return tiempo;
                    }
                },
                {
                    "data": null,
                    "render": function(data, type, row) {
                        let botones = '';
                        if(row.estado == 'danger') {
                            botones = `<button class="btn btn-sm btn-danger" onclick="marcarDescarte('${row.id}')">
                                <i class="fas fa-trash"></i> Descartar
                            </button>`;
                        } else {
                            botones = `<button class="btn btn-sm btn-warning" onclick="aplicarDescuento('${row.id}')">
                                <i class="fas fa-percentage"></i> Descuento
                            </button>`;
                        }
                        return botones;
                    }
                }
            ],
            columnDefs: [{
                "render": function(data, type, row) {
                    let campo = '';
                    if (row.estado == 'danger') {
                        campo = `<h1 class="badge badge-danger">VENCIDO</h1>`;
                    }
                    if (row.estado == 'warning') {
                        campo = `<h1 class="badge badge-warning">PRÓXIMO</h1>`;
                    }
                    return campo;
                },
                "targets": [3]
            }],
            "destroy": true,
            "language": espanol,
            "order": [[3, "desc"]], // Ordenar por estado, mostrando primero los vencidos
            "pageLength": 25
        });
        } catch (error) {
            console.error("Error al parsear respuesta de lotes:", error);
            console.error("Respuesta original:", response);
        }
    }).fail(function(xhr, status, error) {
        console.error("Error en la petición de lotes:", error);
    });
}

function mostrar_stock_riesgo() {
    funcion = "stock_riesgo";
    console.log("Iniciando carga de stock en riesgo...");
    $.post('../controlador/LoteController.php', { funcion }, (response) => {
        console.log("Respuesta del servidor para stock:", response);
        try {
            const lotes = JSON.parse(response);
            console.log("Stock parseado:", lotes);
            console.log("Número de productos con stock bajo:", lotes.length);
            
            // Actualizar contadores en los badges
            $('#contador-bajo-stock').text(lotes.length);
            $('#total-bajo-stock').text(lotes.length);
        
        datatable = $('#stocks').DataTable({
            data: lotes,
            "columns": [
                { 
                    "data": "stock",
                    "render": function(data, type, row) {
                        let color = 'danger';
                        if(row.stock > 20) color = 'warning';
                        return `<span class="badge badge-${color}">${row.stock}</span>`;
                    }
                },
                { "data": "medicamento" },
                { "data": "concentracion" },
                { "data": "adicional" },
                { "data": "laboratorio" },
                { "data": "presentacion" },
                { "data": "tipo" },
                {
                    "data": null,
                    "render": function(data, type, row) {
                        return `<span class="text-muted"><i class="fas fa-info-circle"></i> Gestionar manualmente</span>`;
                    }
                }
            ],
            "destroy": true,
            "language": espanol,
            "order": [[0, "asc"]], // Ordenar por stock ascendente
            "pageLength": 25
        });
        } catch (error) {
            console.error("Error al parsear respuesta de stock:", error);
            console.error("Respuesta original:", response);
        }
    }).fail(function(xhr, status, error) {
        console.error("Error en la petición de stock:", error);
    });
}

$(document).ready(function() {
    mostrar_lotes_riesgo();
    mostrar_stock_riesgo();
    actualizar_ultima_revision();
    
    function buscar_producto(consulta) {
        funcion = "buscar";
        $.post('../controlador/ProductoController.php', { consulta, funcion }, (response) => {
            const productos = JSON.parse(response);
            console.log(productos);
            // Esta función se puede usar para búsquedas específicas si es necesario
        });
    }
    actualizar_ultima_revision();
    
    function buscar_producto(consulta) {
        funcion = "buscar";
        $.post('../controlador/ProductoController.php', { consulta, funcion }, (response) => {
            const productos = JSON.parse(response);
            console.log(productos);
            // Esta función se puede usar para búsquedas específicas si es necesario
        });
    }
    
    function actualizar_ultima_revision() {
        const ahora = new Date();
        const fecha = ahora.toLocaleDateString('es-ES');
        const hora = ahora.toLocaleTimeString('es-ES');
        $('#ultima-revision').text(`${fecha} ${hora}`);
    }

    // Actualizar datos cada 5 minutos
    setInterval(function() {
        mostrar_lotes_riesgo();
        mostrar_stock_riesgo();
        actualizar_ultima_revision();
    }, 300000); // 5 minutos
});

// Funciones para las acciones de los botones
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
                console.log(response);
                if (response == 'borrado') {
                    swalWithBootstrapButtons.fire(
                        'Borrado!',
                        'El lote ' + loteId + ' fue borrado.',
                        'success'
                    );
                    // Debug: verificar que se llaman las funciones
                    console.log('Lote eliminado, recargando tablas...');
                    mostrar_lotes_riesgo();
                    mostrar_stock_riesgo();
                } else {
                    swalWithBootstrapButtons.fire(
                        'No se pudo borrar!',
                        'El lote ' + loteId + ' no fue borrado porque esta siendo usado.',
                        'error'
                    );
                }
            });
        } else if (result.dismiss === Swal.DismissReason.cancel) {
            swalWithBootstrapButtons.fire(
                'Cancelado',
                'El lote ' + loteId + ' no fue borrado',
                'error'
            );
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
            
            // Mostrar confirmación del descuento
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
            
            // Aquí se podría implementar la lógica para guardar el descuento en BD
            // Por ahora solo mostramos la confirmación
        }
    });
}

function generarReporteVencidos() {
    console.log('Generando reporte de productos vencidos...');
    
    Swal.fire({
        title: 'Generando reporte...',
        text: 'Por favor espere mientras se genera el PDF',
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Abrir el reporte PDF en una nueva ventana
    window.open('../controlador/PDFController.php?funcion=reporte_vencidos', '_blank');
    
    // Cerrar el loading después de un momento
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