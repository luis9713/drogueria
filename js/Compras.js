$(document).ready(function() {
    // Función para formatear números con separador de miles
    function formatearNumero(numero) {
        return Math.round(numero).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    listar_compras();
    $('.select2').select2();
    rellenar_productos_editar();
    rellenar_proveedores_editar();
    var datatable;
    var prodsEditar = [];
    var registrosBloqueadosEditar = [];
    var totalBloqueadoEditar = 0;

    function rellenar_productos_editar() {
        funcion = 'rellenar_productos';
        $.post('../controlador/ProductoController.php', { funcion }, (response) => {
            let productos = JSON.parse(response);
            let template = '';
            productos.forEach(producto => {
                template += `<option value="${producto.nombre}">${producto.nombre}</option>`;
            });
            $('#editar_producto').html(template);
        })
    }

    function rellenar_proveedores_editar() {
        funcion = 'rellenar_proveedores';
        $.post('../controlador/ProveedorController.php', { funcion }, (response) => {
            let proveedores = JSON.parse(response);
            let template = '';
            proveedores.forEach(proveedor => {
                template += `<option value="${proveedor.id}">${proveedor.nombre}</option>`;
            });
            $('#editar_proveedor').html(template);
        })
    }

    function listar_compras() {
        funcion = 'listar_compras';
        $.post('../controlador/ComprasController.php', { funcion }, (response) => {
            console.log(response);
            let datos = JSON.parse(response);
            datatable = $('#compras').DataTable({
                data: datos,
                "columns": [
                    { "data": "numeracion" },
                    { "data": "codigo" },
                    { "data": "fecha_compra" },
                    { "data": "fecha_entrega" },
                    { 
                      "data": "total",
                      "render": function(data) {
                        return formatearNumero(data);
                      }
                    },
                    { "data": "estado" },
                    { "data": "proveedor" },
                    { "defaultContent": `<button class="ver btn btn-info" type="button" data-toggle="modal" data-target="#vista_compra"><i class="fas fa-search"></i></button>
                                        <button class="editar btn btn-success" type="button" data-toggle="modal" data-target="#editar_compra"><i class="fas fa-pencil-alt"></i></button>
                                        <button class="borrar btn btn-danger"><i class="fas fa-trash-alt"></i></button>` }
                ],
                "destroy": true,
                "language": espanol
            });
        })
    }
    function mostrarErrorEditar(mensaje) {
        $('#error-editar-compra').text(mensaje);
        $('#noedit-compra').hide('slow');
        $('#noedit-compra').show(1000);
        $('#noedit-compra').hide(2000);
    }

    function actualizar_total_editar() {
        let subtotal = totalBloqueadoEditar + prodsEditar.reduce((acumulado, prod) => acumulado + (prod.cantidad * prod.precio_compra), 0);
        let flete = parseFloat($('#editar_flete').val()) || 0;
        let total = subtotal + flete;
        $('#editar_subtotal').text(formatearNumero(subtotal));
        $('#editar_flete_mostrado').text(formatearNumero(flete));
        $('#editar_total').text(formatearNumero(total));
        return { subtotal, flete, total };
    }

    $(document).on('input', '#editar_flete', () => {
        actualizar_total_editar();
    });

    function render_registros_editar() {
        let template = '';
        registrosBloqueadosEditar.forEach(registro => {
            template += `
                <tr class="table-secondary">
                    <td>${registro.nombre.split(' | ')[1] || registro.nombre}</td>
                    <td>${registro.codigo}</td>
                    <td>${registro.cantidad}</td>
                    <td>${registro.vencimiento}</td>
                    <td>${formatearNumero(registro.precio_compra)}</td>
                    <td>${formatearNumero(registro.cantidad * registro.precio_compra)}</td>
                    <td><span class="badge badge-secondary" title="Ya tiene ventas asociadas, no editable"><i class="fas fa-lock"></i> Vendido</span></td>
                </tr>
            `;
        });
        prodsEditar.forEach((prod, index) => {
            template += `
                <tr prodIndex="${index}">
                    <td>${prod.nombre.split(' | ')[1] || prod.nombre}</td>
                    <td>${prod.codigo}</td>
                    <td>${prod.cantidad}</td>
                    <td>${prod.vencimiento}</td>
                    <td>${formatearNumero(prod.precio_compra)}</td>
                    <td>${formatearNumero(prod.cantidad * prod.precio_compra)}</td>
                    <td><button class="quitar-editar btn btn-danger"><i class="fas fa-times-circle"></i></button></td>
                </tr>
            `;
        });
        $('#editar_registros').html(template);
        actualizar_total_editar();
    }

    $('#compras tbody').on('click', '.editar', function() {
        let datos = datatable.row($(this).parents()).data();
        let codigo = datos.codigo;
        codigo = codigo.split(' | ');
        let id_compra = codigo[0];
        funcion = 'obtener_compra_editar';

        $.post('../controlador/ComprasController.php', { funcion, id_compra }, (response) => {
            let datosCompra;
            try {
                datosCompra = JSON.parse(response);
            } catch (error) {
                mostrarErrorEditar('No se pudo cargar la informacion de la compra.');
                return;
            }

            $('#editar_id_compra').val(datosCompra.compra.id);
            $('#editar_codigo').val(datosCompra.compra.codigo);
            $('#editar_fecha_compra').val(datosCompra.compra.fecha_compra);
            $('#editar_fecha_entrega').val(datosCompra.compra.fecha_entrega);
            $('#editar_proveedor').val(datosCompra.compra.id_proveedor).trigger('change');
            $('#editar_flete').val(datosCompra.compra.flete);

            let registrosBloqueados = [];
            prodsEditar = [];
            totalBloqueadoEditar = 0;

            datosCompra.lotes.forEach(registro => {
                if (registro.editable) {
                    prodsEditar.push(registro);
                } else {
                    registrosBloqueados.push(registro);
                    totalBloqueadoEditar += registro.cantidad * registro.precio_compra;
                }
            });

            registrosBloqueadosEditar = registrosBloqueados;
            render_registros_editar();
        }).fail(() => {
            mostrarErrorEditar('No se pudo conectar con el servidor.');
        });
    })

    $(document).on('click', '#editar_agregar_producto', (e) => {
        e.preventDefault();
        let producto_select2 = $('#editar_producto').val();
        let codigo_lote = $('#editar_codigo_lote').val();
        let cantidad = parseFloat($('#editar_cantidad').val());
        let vencimiento = $('#editar_vencimiento').val();
        let precio_compra = parseFloat($('#editar_precio_compra').val());

        if (!producto_select2) {
            mostrarErrorEditar('Elija un producto!');
            return;
        }
        if (!codigo_lote || codigo_lote.trim() === '') {
            mostrarErrorEditar('Ingrese un codigo!');
            return;
        }
        if (!cantidad || cantidad <= 0) {
            mostrarErrorEditar('Ingrese una cantidad valida mayor a 0!');
            return;
        }
        if (!vencimiento) {
            mostrarErrorEditar('Ingrese una fecha de vencimiento!');
            return;
        }
        if (!precio_compra || precio_compra <= 0) {
            mostrarErrorEditar('Ingrese un precio de compra valido mayor a 0!');
            return;
        }

        let fechaCompra = $('#editar_fecha_compra').val();
        if (fechaCompra && vencimiento < fechaCompra) {
            mostrarErrorEditar('El vencimiento no puede ser menor que la fecha de compra!');
            return;
        }

        let id_producto = parseInt(producto_select2.split(' | ')[0].trim(), 10);
        if (!id_producto || id_producto <= 0) {
            mostrarErrorEditar('Producto invalido, recargue la pagina e intente de nuevo.');
            return;
        }

        prodsEditar.push({
            id: id_producto,
            nombre: producto_select2,
            codigo: codigo_lote.trim(),
            cantidad: cantidad,
            vencimiento: vencimiento,
            precio_compra: precio_compra
        });

        $('#editar_producto').val('').trigger('change');
        $('#editar_codigo_lote').val('');
        $('#editar_cantidad').val('');
        $('#editar_vencimiento').val('');
        $('#editar_precio_compra').val('');

        render_registros_editar();
    })

    $(document).on('click', '.quitar-editar', function() {
        let index = $(this).closest('tr').attr('prodIndex');
        prodsEditar.splice(index, 1);
        render_registros_editar();
    })

    $(document).on('click', '#guardar_edicion_compra', () => {
        let id_compra = $('#editar_id_compra').val();
        let codigo = $('#editar_codigo').val();
        let fecha_compra = $('#editar_fecha_compra').val();
        let fecha_entrega = $('#editar_fecha_entrega').val();
        let proveedor = $('#editar_proveedor').val();

        if (!codigo || codigo.trim() === '') {
            mostrarErrorEditar('Ingrese un codigo!');
            return;
        }
        if (!fecha_compra) {
            mostrarErrorEditar('Ingrese una fecha de compra!');
            return;
        }
        if (!fecha_entrega) {
            mostrarErrorEditar('Ingrese una fecha de entrega!');
            return;
        }
        if (fecha_entrega < fecha_compra) {
            mostrarErrorEditar('La fecha de entrega no puede ser menor a la fecha de compra!');
            return;
        }
        if (!proveedor) {
            mostrarErrorEditar('Ingrese un proveedor!');
            return;
        }
        if (prodsEditar.length === 0 && totalBloqueadoEditar === 0) {
            mostrarErrorEditar('No hay productos agregados!');
            return;
        }
        let flete = parseFloat($('#editar_flete').val()) || 0;
        if (flete < 0) {
            mostrarErrorEditar('El flete no puede ser negativo!');
            return;
        }

        let descripcion = { codigo, fecha_compra, fecha_entrega, proveedor, flete };
        funcion = 'actualizar_compra';
        let productosString = JSON.stringify(prodsEditar);
        let descripcionString = JSON.stringify(descripcion);

        $.post('../controlador/ComprasController.php', { funcion, id_compra, productosString, descripcionString }, (response) => {
            const res = (response || '').trim();
            if (res === 'actualizado') {
                $('#edit-compra').hide('slow');
                $('#edit-compra').show(1000);
                $('#edit-compra').hide(2000);
                listar_compras();
                $('#editar_compra').modal('hide');
            } else {
                let mensaje = 'No se pudo actualizar la compra.';
                if (res === 'error_lote_bloqueado') {
                    mensaje = 'Uno de los productos ya tiene ventas asociadas y no puede modificarse.';
                } else if (res === 'error_producto') {
                    mensaje = 'Hay un producto con datos invalidos.';
                } else if (res === 'error_sin_productos') {
                    mensaje = 'Debe haber al menos un producto en la compra.';
                } else if (res === 'error_validacion' || res === 'error_datos') {
                    mensaje = 'Revise los datos generales de la compra.';
                }
                mostrarErrorEditar(mensaje);
            }
        }).fail(() => {
            mostrarErrorEditar('No se pudo conectar con el servidor.');
        });
    })
    $('#compras tbody').on('click', '.ver', function() {
        let datos = datatable.row($(this).parents()).data();
        let codigo = datos.codigo;
        codigo = codigo.split(' | ');
        let id = codigo[0];
        funcion = "ver";
        $('#imprimir_detalle').data('id_compra', id);
        $('#marcar_pagado_detalle').data('id_compra', id);
        if (datos.estado === 'No cancelado') {
            $('#marcar_pagado_detalle').show();
        } else {
            $('#marcar_pagado_detalle').hide();
        }
        $('#codigo_compra').html(datos.codigo);
        $('#fecha_compra').html(datos.fecha_compra);
        $('#fecha_entrega').html(datos.fecha_entrega);
        $('#estado').html(datos.estado);
        $('#proveedor').html(datos.proveedor);
        $('#subtotal_detalle').html(formatearNumero(datos.subtotal));
        $('#flete_detalle').html(formatearNumero(datos.flete));
        $('#total').html(formatearNumero(datos.total));
        $.post('../controlador/LoteController.php', { funcion, id }, (response) => {
            console.log(response);
            let registros = JSON.parse(response);
            console.log(registros);
            let template = "";
            $('#detalles').html(template);
            registros.forEach(registro => {
                template += `
                    <tr>
                        <td>${registro.numeracion}</td>
                        <td>${registro.codigo}</td>
                        <td>${registro.cantidad}</td>
                        <td>${registro.vencimiento}</td>
                        <td>${registro.precio_compra}</td>
                        <td>${registro.producto}</td>
                        <td>${registro.laboratorio}</td>
                        <td>${registro.presentacion}</td>
                        <td>${registro.tipo}</td>

                    </tr>
                `;
                $('#detalles').html(template);
            });
        })
    })
    $('#imprimir_detalle').on('click', function() {
        let id = $(this).data('id_compra');
        if (!id) {
            return;
        }
        funcion = 'imprimir';
        $.post('../controlador/ComprasController.php', { id, funcion }, (response) => {
            console.log(response);
            window.open('../pdf/pdf-compra-' + id + '.pdf', '_blank');
        })
    })
    $('#marcar_pagado_detalle').on('click', function() {
        let id_compra = $(this).data('id_compra');
        if (!id_compra) {
            return;
        }
        funcion = 'marcar_pagado';

        const swalWithBootstrapButtons = Swal.mixin({
            customClass: {
                confirmButton: 'btn btn-success m-1',
                cancelButton: 'btn btn-danger m-1'
            },
            buttonsStyling: false
        })

        swalWithBootstrapButtons.fire({
            title: 'Marcar esta compra como pagada?',
            text: 'Una vez pagada no podras volver a marcarla como No cancelado.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Si, marcar como pagada!',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then((result) => {
            if (!result.value) {
                return;
            }

            $.post('../controlador/ComprasController.php', { funcion, id_compra }, (response) => {
                const res = (response || '').trim();
                if (res === 'pagado') {
                    $('#estado').html('Cancelado');
                    $('#marcar_pagado_detalle').hide();
                    swalWithBootstrapButtons.fire('Listo!', 'La compra fue marcada como pagada.', 'success');
                    listar_compras();
                } else if (res === 'ya_pagado') {
                    $('#marcar_pagado_detalle').hide();
                    swalWithBootstrapButtons.fire('Aviso', 'Esta compra ya estaba pagada.', 'info');
                } else {
                    swalWithBootstrapButtons.fire('No se pudo actualizar', 'No se pudo marcar la compra como pagada.', 'error');
                }
            }).fail(() => {
                swalWithBootstrapButtons.fire('Error de comunicacion', 'No se pudo conectar con el servidor.', 'error');
            });
        })
    })
    $('#compras tbody').on('click', '.borrar', function() {
        let datos = datatable.row($(this).parents()).data();
        let codigo = datos.codigo;
        codigo = codigo.split(' | ');
        let id_compra = codigo[0];
        funcion = 'eliminar_compra';

        const swalWithBootstrapButtons = Swal.mixin({
            customClass: {
                confirmButton: 'btn btn-success m-1',
                cancelButton: 'btn btn-danger m-1'
            },
            buttonsStyling: false
        })

        swalWithBootstrapButtons.fire({
            title: 'Desea eliminar la compra ' + datos.codigo + '?',
            text: 'Se eliminaran tambien los lotes creados por esta compra. No podras revertir esto!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Si, eliminar!',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then((result) => {
            if (!result.value) {
                return;
            }

            $.post('../controlador/ComprasController.php', { funcion, id_compra }, (response) => {
                const res = (response || '').trim();
                if (res === 'eliminado') {
                    swalWithBootstrapButtons.fire('Eliminada!', 'La compra fue eliminada.', 'success');
                    listar_compras();
                } else {
                    let mensaje = 'No se pudo eliminar la compra.';
                    if (res === 'error_venta_asociada') {
                        mensaje = 'No se puede eliminar porque ya hay ventas asociadas a los lotes de esta compra.';
                    }
                    swalWithBootstrapButtons.fire('No se pudo eliminar', mensaje, 'error');
                }
            }).fail(() => {
                swalWithBootstrapButtons.fire('Error de comunicacion', 'No se pudo conectar con el servidor.', 'error');
            });
        })
    })

})
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