$(document).ready(function() {
    $('.select2').select2();
    rellenar_productos();
    rellenar_estado_pago();
    rellenar_proveedores();
    var prods = [];
    var preciosVenta = {};

    function mostrarErrorProducto(mensaje) {
        $('#error').text(mensaje);
        $('#noadd-prod').hide('slow');
        $('#noadd-prod').show(1000);
        $('#noadd-prod').hide(2000);
    }

    function mostrarErrorCompra(mensaje) {
        $('#error-compra').text(mensaje);
        $('#noadd-compra').hide('slow');
        $('#noadd-compra').show(1000);
        $('#noadd-compra').hide(2000);
    }

    function formatearNumero(numero) {
        return Math.round(numero).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function formatearDecimal(numero) {
        let partes = numero.toFixed(2).split('.');
        partes[0] = partes[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        return partes[0] + ',' + partes[1];
    }

    function actualizar_total() {
        let subtotal = prods.reduce((acumulado, prod) => acumulado + (prod.cantidad * prod.precio_compra), 0);
        let flete = parseFloat($('#flete').val()) || 0;
        let total = subtotal + flete;
        $('#total_compra').text(formatearNumero(subtotal));
        $('#flete_mostrado').text(formatearNumero(flete));
        $('#total_factura').text(formatearNumero(total));
        return { subtotal, flete, total };
    }

    $(document).on('input', '#flete', () => {
        actualizar_total();
    });

    function rellenar_productos() {
        funcion = 'rellenar_productos';
        $.post('../controlador/ProductoController.php', { funcion }, (response) => {
            //console.log(response);
            let productos = JSON.parse(response);
            let template = '';
            productos.forEach(producto => {
                    let id_producto = producto.nombre.split(' | ')[0].trim();
                    preciosVenta[id_producto] = producto.precio;
                    template += `
                <option value="${producto.nombre}" >${producto.nombre}</option>
              `
                }

            );
            $('#producto').html(template);
        })
    }

    function rellenar_estado_pago() {
        funcion = 'rellenar_estado';
        $.post('../controlador/EstadoController.php', { funcion }, (response) => {
            //console.log(response);
            let estados = JSON.parse(response);
            let template = '';
            estados.forEach(estado => {
                    template += `
                <option value="${estado.id}" >${estado.nombre}</option>
              `
                }

            );
            $('#estado').html(template);
        })
    }

    function rellenar_proveedores() {
        funcion = 'rellenar_proveedores';
        $.post('../controlador/ProveedorController.php', { funcion }, (response) => {
            //console.log(response);
            let proveedores = JSON.parse(response);
            let template = '';
            proveedores.forEach(proveedor => {
                    template += `
                <option value="${proveedor.id}" >${proveedor.nombre}</option>
              `
                }

            );
            $('#proveedor').html(template);
        })
    }
    $(document).on('change', '#producto', () => {
        let producto_select2 = $('#producto').val();
        if (!producto_select2) {
            $('#precio_venta_actual').text('');
            return;
        }
        let id_producto = producto_select2.split(' | ')[0].trim();
        let precio = preciosVenta[id_producto];
        if (precio === undefined) {
            $('#precio_venta_actual').text('');
        } else {
            $('#precio_venta_actual').text('Precio de venta actual: $' + formatearNumero(precio));
        }
    });

    function actualizar_preview_precio_unitario() {
        let modo = $('input[name="modo_precio"]:checked').val();
        if (modo !== 'total') {
            $('#precio_unitario_calculado').text('');
            return;
        }
        let cantidad = parseFloat($('#cantidad').val());
        let valor_total_linea = parseFloat($('#precio_compra').val());
        if (!cantidad || cantidad <= 0 || !valor_total_linea || valor_total_linea <= 0) {
            $('#precio_unitario_calculado').text('');
            return;
        }
        let unitario = valor_total_linea / cantidad;
        $('#precio_unitario_calculado').text('Precio unitario calculado: $' + formatearDecimal(unitario));
    }

    $(document).on('change', 'input[name="modo_precio"]', () => {
        let modo = $('input[name="modo_precio"]:checked').val();
        if (modo === 'total') {
            $('#label_precio_compra').text('Valor total de la linea');
            $('#precio_compra').attr('placeholder', 'Ingrese el valor total de la linea');
        } else {
            $('#label_precio_compra').text('Precio de compra (unitario)');
            $('#precio_compra').attr('placeholder', 'Ingrese precio de compra');
        }
        actualizar_preview_precio_unitario();
    });

    $(document).on('input', '#cantidad, #precio_compra', () => {
        actualizar_preview_precio_unitario();
    });

    $(document).on('click', '.agregar-producto', (e) => {
        e.preventDefault();
        let producto_select2 = $('#producto').val();
        let codigo_lote = $('#codigo_lote').val();
        let cantidad = parseFloat($('#cantidad').val());
        let vencimiento = $('#vencimiento').val();
        let valor_ingresado = parseFloat($('#precio_compra').val());
        let modo_precio = $('input[name="modo_precio"]:checked').val();

        if (producto_select2 == null) {
            mostrarErrorProducto('Elija un producto!');
        } else {
            if (codigo_lote.trim() == '') {
                mostrarErrorProducto('Ingrese un codigo!');
            } else {
                if (!cantidad || cantidad <= 0) {
                    mostrarErrorProducto('Ingrese una cantidad valida mayor a 0!');
                } else {
                    if (vencimiento == '') {
                        mostrarErrorProducto('Ingrese una fecha de vencimiento!');
                    } else {
                        if (!valor_ingresado || valor_ingresado <= 0) {
                            mostrarErrorProducto(modo_precio === 'total' ? 'Ingrese un valor total de linea valido mayor a 0!' : 'Ingrese un precio de compra valido mayor a 0!');
                        } else {
                            let fechaCompra = $('#fecha_compra').val();
                            if (fechaCompra && vencimiento < fechaCompra) {
                                mostrarErrorProducto('El vencimiento no puede ser menor que la fecha de compra!');
                                return;
                            }

                            let producto_array = producto_select2.split(' | ');
                            let id_producto = parseInt((producto_array['0'] || '').trim(), 10);
                            if (!id_producto || id_producto <= 0) {
                                mostrarErrorProducto('Producto invalido, recargue la pagina e intente de nuevo.');
                                return;
                            }

                            let existe = prods.some(prod => prod.id == id_producto && prod.codigo === codigo_lote.trim());
                            if (existe) {
                                mostrarErrorProducto('Ya agrego ese lote para este producto.');
                                return;
                            }

                            let precio_compra = modo_precio === 'total' ? (valor_ingresado / cantidad) : valor_ingresado;

                            let producto = {
                                id: id_producto,
                                nombre: producto_select2,
                                codigo: codigo_lote.trim(),
                                cantidad: cantidad,
                                vencimiento: vencimiento,
                                precio_compra: precio_compra
                            }
                            prods.push(producto);
                            let template = '';
                            template = `
                                <tr prodId="${producto.id}">
                                    <td>${producto.nombre}</td>
                                    <td>${producto.codigo}</td>
                                    <td>${producto.cantidad}</td>
                                    <td>${producto.vencimiento}</td>
                                    <td>${formatearDecimal(producto.precio_compra)}</td>
                                    <td>${formatearNumero(producto.cantidad * producto.precio_compra)}</td>
                                    <td><button class="borrar-producto btn btn-danger"><i class="fas fa-times-circle"></i></button></td>
                                </tr>
                            `;
                            $('#registros_compra').append(template);
                            actualizar_total();
                            $('#add-prod').hide('slow');
                            $('#add-prod').show(1000);
                            $('#add-prod').hide(2000);
                            $('#producto').val('').trigger('change');
                            $('#codigo_lote').val('');
                            $('#cantidad').val('');
                            $('#vencimiento').val('');
                            $('#precio_compra').val('');
                            $('#modo_precio_unitario').prop('checked', true).trigger('change');
                        }
                    }
                }
            }
        }


    })
    $(document).on('click', '.borrar-producto', (e) => {
        e.preventDefault();
        let elemento = $(e.currentTarget).closest('tr');
        let id = $(elemento).attr('prodId');
        prods.forEach(function(prod, index) {
            if (prod.id == id) {
                prods.splice(index, 1);
            }
        })
        $(elemento).remove();
        actualizar_total();
    })
    $(document).on('click', '.crear-compra', (e) => {
        e.preventDefault();
        let codigo = $('#codigo').val();
        let fecha_compra = $('#fecha_compra').val();
        let fecha_entrega = $('#fecha_entrega').val();
        let totales = actualizar_total();
        let total = totales.subtotal;
        let flete = totales.flete;
        let estado = $('#estado').val();
        let proveedor = $('#proveedor').val();
        if (codigo.trim() == '') {
            mostrarErrorCompra('Ingrese un codigo!');
        } else {
            if (fecha_compra == '') {
                mostrarErrorCompra('Ingrese una fecha de compra!');
            } else {
                if (fecha_entrega == '') {
                    mostrarErrorCompra('Ingrese una fecha de entrega!');
                } else {
                    if (fecha_entrega < fecha_compra) {
                        mostrarErrorCompra('La fecha de entrega no puede ser menor a la fecha de compra!');
                    } else {
                        if (estado == null) {
                            mostrarErrorCompra('Ingrese un estado!');
                        } else {
                            if (proveedor == null) {
                                mostrarErrorCompra('Ingrese un proveedor!');
                            } else {
                                if (prods.length === 0) {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Oops...',
                                        text: 'No hay productos agregados!',

                                    })
                                } else if (!total || total <= 0) {
                                    mostrarErrorCompra('El total calculado debe ser mayor a 0, revise los precios y cantidades!');
                                } else if (flete < 0) {
                                    mostrarErrorCompra('El flete no puede ser negativo!');
                                } else {
                                    let descripcion = {
                                        codigo: codigo,
                                        fecha_compra: fecha_compra,
                                        fecha_entrega: fecha_entrega,
                                        total: total,
                                        flete: flete,
                                        estado: estado,
                                        proveedor: proveedor
                                    }
                                    funcion = 'registrar_compra'
                                    let productosString = JSON.stringify(prods);
                                    let descripcionString = JSON.stringify(descripcion);
                                    $.post('../controlador/ComprasController.php', { funcion, productosString, descripcionString }, (response) => {
                                            const res = (response || '').trim();
                                            if (res === 'add') {
                                                Swal.fire({
                                                    position: 'center',
                                                    icon: 'success',
                                                    title: 'Se realizo la compra',
                                                    showConfirmButton: false,
                                                    timer: 1500
                                                }).then(function() {

                                                    location.href = '../vista/adm_compras.php'
                                                })
                                            } else {
                                                let mensaje = 'Error en el servidor!';
                                                if (res === 'error_datos' || res === 'error_validacion') {
                                                    mensaje = 'Revise los datos generales de la compra.';
                                                } else if (res === 'error_producto') {
                                                    mensaje = 'Hay un producto/lote con datos invalidos.';
                                                } else if (res === 'error_lote') {
                                                    mensaje = 'No se pudo guardar uno de los lotes.';
                                                }
                                                Swal.fire({
                                                    icon: 'error',
                                                    title: 'Oops...',
                                                    text: mensaje,

                                                })
                                            }
                                        })
                                        .fail(() => {
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'Error de comunicacion',
                                                text: 'No se pudo conectar con el servidor.'
                                            })
                                        })
                                        /*
                                        Swal.fire({
                                            position: 'center',
                                            icon: 'success',
                                            title: 'Se realizo la compra',
                                            showConfirmButton: false,
                                            timer: 1500
                                        }).then(function() {

                                            location.href = '../vista/adm_compras.php'
                                        })*/
                                }
                            }
                        }
                    }
                }
            }
        }
    })

})