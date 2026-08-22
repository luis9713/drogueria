$(document).ready(function() {
    $('.select2').select2();
    rellenar_productos();
    rellenar_estado_pago();
    rellenar_proveedores();
    var prods = [];

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

    function rellenar_productos() {
        funcion = 'rellenar_productos';
        $.post('../controlador/ProductoController.php', { funcion }, (response) => {
            //console.log(response);
            let productos = JSON.parse(response);
            let template = '';
            productos.forEach(producto => {
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
    $(document).on('click', '.agregar-producto', (e) => {
        e.preventDefault();
        let producto_select2 = $('#producto').val();
        let codigo_lote = $('#codigo_lote').val();
        let cantidad = parseFloat($('#cantidad').val());
        let vencimiento = $('#vencimiento').val();
        let precio_compra = parseFloat($('#precio_compra').val());

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
                        if (!precio_compra || precio_compra <= 0) {
                            mostrarErrorProducto('Ingrese un precio de compra valido mayor a 0!');
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
                                    <td>${producto.precio_compra}</td>
                                    <td><button class="borrar-producto btn btn-danger"><i class="fas fa-times-circle"></i></button></td>
                                </tr>
                            `;
                            $('#registros_compra').append(template);
                            $('#add-prod').hide('slow');
                            $('#add-prod').show(1000);
                            $('#add-prod').hide(2000);
                            $('#producto').val('').trigger('change');
                            $('#codigo_lote').val('');
                            $('#cantidad').val('');
                            $('#vencimiento').val('');
                            $('#precio_compra').val('');
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
    })
    $(document).on('click', '.crear-compra', (e) => {
        e.preventDefault();
        let codigo = $('#codigo').val();
        let fecha_compra = $('#fecha_compra').val();
        let fecha_entrega = $('#fecha_entrega').val();
        let total = parseFloat($('#total').val());
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
                        if (!total || total <= 0) {
                            mostrarErrorCompra('Ingrese un total valido mayor a 0!');
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
                                } else {
                                    let descripcion = {
                                        codigo: codigo,
                                        fecha_compra: fecha_compra,
                                        fecha_entrega: fecha_entrega,
                                        total: total,
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
        }
    })

})