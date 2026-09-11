$(document).ready(function() {
    buscar_cliente();
    var funcion;

    function buscar_cliente(consulta) {
        funcion = 'buscar';
        $.post('../controlador/ClienteController.php', { consulta, funcion }, (response) => {
            console.log(response);

            const clientes = JSON.parse(response);
            let template = '';
            clientes.forEach(cliente => {
                template += `
                <div cliId="${cliente.id}" cliNombre="${cliente.nombre}" cliApellido="${cliente.apellidos}" cliDni="${cliente.dni}" cliEdad="${cliente.nacimiento}" cliTelefono="${cliente.telefono}" cliCorreo="${cliente.correo}" cliSexo="${cliente.sexo}" cliAdicional="${cliente.adicional}" class="col-12 col-sm-6 col-md-4 d-flex align-items-stretch">
              <div class="card bg-light">
                <div class="card-header text-muted border-bottom-0">
                  <h1 class="badge badge-success">Cliente</h1>
                </div>
                <div class="card-body pt-0">
                  <div class="row">
                    <div class="col-7">
                      <h2 class="lead"><b>${cliente.nombre_completo || (cliente.nombre + ' ' + cliente.apellidos)}</b></h2>
                      
                      <ul class="ml-4 mb-0 fa-ul text-muted">
                       <li class="small"><span class="fa-li"><i class="fas fa-lg fa-building"></i></span> Dni: ${cliente.dni}</li>
                        <li class="small"><span class="fa-li"><i class="fas fa-lg fa-building"></i></span> Edad: ${cliente.edad}</li>
                        <li class="small"><span class="fa-li"><i class="fas fa-lg fa-phone"></i></span> Telefono #: ${cliente.telefono}</li>
                        <li class="small"><span class="fa-li"><i class="fas fa-lg fa-at"></i></span> Correo #: ${cliente.correo}</li>
                        <li class="small"><span class="fa-li"><i class="fas fa-lg fa-building"></i></span> Sexo: ${cliente.sexo}</li>
                        <li class="small"><span class="fa-li"><i class="fas fa-lg fa-building"></i></span> Adicional: ${cliente.adicional}</li>
                      </ul>
                    </div>
                    <div class="col-5 text-center">
                      <img src="${cliente.avatar}" alt="" class="img-circle img-fluid">
                    </div>
                  </div>
                </div>
                <div class="card-footer">
                  <div class="text-right">
                   
                    <button class="editar btn btn-sm btn-success" title="Editar cliente"type="button" data-toggle="modal" data-target="#editarcliente">
                      <i class="fas fa-pencil-alt"></i>
                    </button>
                    <button class="borrar btn btn-sm btn-danger" title="Borrar cliente">
                      <i class="fas fa-trash-alt"></i>
                    </button>
                    
                  </div>
                </div>
              </div>
            </div>
                `;
            });
            $('#clientes').html(template);

        });
    }
    $(document).on('keyup', '#buscar_cliente', function() {
        let valor = $(this).val();
        if (valor != '') {
            buscar_cliente(valor);
        } else {
            buscar_cliente();
        }
    });
    $('#form-crear').submit(e => {

        let nombre = $('#nombre').val();
        let apellido = $('#apellido').val();
        let dni = $('#dni').val();
        let edad = $('#edad').val();
        let telefono = $('#telefono').val();
        let correo = $('#correo').val();
        let sexo = $('#sexo').val();
        let adicional = $('#adicional').val();
        funcion = 'crear';
        $.post('../controlador/ClienteController.php', { nombre, apellido, dni, edad, telefono, correo, sexo, adicional, funcion }, (response) => {
            if (response == 'add') {
                $('#add-cli').hide('slow');
                $('#add-cli').show(1000);
                $('#add-cli').hide(2000);
                $('#form-crear').trigger('reset');
                buscar_cliente();
            }
            if (response == 'noadd') {
                $('#noadd-cli').hide('slow');
                $('#noadd-cli').show(1000);
                $('#noadd-cli').hide(2000);
                $('#form-crear').trigger('reset');
            }
        })
        e.preventDefault();
    });
    $(document).on('click', '.editar', function(e) {
        let elemento = $(this).closest('.d-flex');
        let id = $(elemento).attr('cliId');
        let nombre = $(elemento).attr('cliNombre');
        let apellido = $(elemento).attr('cliApellido');
        let dni = $(elemento).attr('cliDni');
        let edad = $(elemento).attr('cliEdad');
        let telefono = $(elemento).attr('cliTelefono');
        let correo = $(elemento).attr('cliCorreo');
        let sexo = $(elemento).attr('cliSexo');
        let adicional = $(elemento).attr('cliAdicional');

        $('#id_cliente').val(id);
        $('#nombre_edit').val(nombre);
        $('#apellido_edit').val(apellido);
        $('#dni_edit').val(dni);
        $('#edad_edit').val(edad);
        $('#telefono_edit').val(telefono);
        $('#correo_edit').val(correo);
        $('#sexo_edit').val(sexo);
        $('#adicional_edit').val(adicional);
    });
    $('#form-editar').submit(e => {

        let id = $('#id_cliente').val();
        let nombre = $('#nombre_edit').val();
        let apellido = $('#apellido_edit').val();
        let dni = $('#dni_edit').val();
        let edad = $('#edad_edit').val();
        let telefono = $('#telefono_edit').val();
        let correo = $('#correo_edit').val();
        let sexo = $('#sexo_edit').val();
        let adicional = $('#adicional_edit').val();
        funcion = 'editar';
        $.post('../controlador/ClienteController.php', { id, nombre, apellido, dni, edad, telefono, correo, sexo, adicional, funcion }, (response) => {
            console.log(response);
            if (response == 'edit') {
                $('#edit-cli').hide('slow');
                $('#edit-cli').show(1000);
                $('#edit-cli').hide(2000);
                $('#form-editar').trigger('reset');
                buscar_cliente();
            }
            if (response == 'noedit') {
                $('#noedit-cli').hide('slow');
                $('#noedit-cli').show(1000);
                $('#noedit-cli').hide(2000);
                $('#form-editar').trigger('reset');
            }
        })
        e.preventDefault();
    });
    $(document).on('click', '.borrar', function(e) {
        funcion = "borrar";
        let elemento = $(this).closest('.d-flex');
        let id = $(elemento).attr('cliId');
        let nombre = $(elemento).attr('cliNombre') + ' ' + $(elemento).attr('cliApellido');
        let avatar = '../img/avatar.png';

        const swalWithBootstrapButtons = Swal.mixin({
            customClass: {
                confirmButton: 'btn btn-success',
                cancelButton: 'btn btn-danger mr-1'
            },
            buttonsStyling: false
        })

        swalWithBootstrapButtons.fire({
            title: 'Decea eliminar ' + nombre + '?',
            text: "No podras revertir esto!",
            imageUrl: '' + avatar + '',
            imageWidth: 100,
            imageHeight: 100,
            showCancelButton: true,
            confirmButtonText: 'Si, borra esto!',
            cancelButtonText: 'No, cancelar!',
            reverseButtons: true
        }).then((result) => {
            if (result.value) {
                $.post('../controlador/ClienteController.php', { id, funcion }, (response) => {

                    if (response == 'borrado') {
                        swalWithBootstrapButtons.fire(
                            'Borrado!',
                            'El cliente ' + nombre + ' fue borrado.',
                            'success'
                        )
                        buscar_cliente();
                    } else {
                        swalWithBootstrapButtons.fire(
                            'No se pudo borrar!',
                            'El cliente ' + nombre + ' no fue borrado porque esta siendo usado en un lote.',
                            'error'
                        )
                    }
                })
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                swalWithBootstrapButtons.fire(
                    'Cancelado',
                    'El cliente ' + nombre + ' no fue borrado',
                    'error'
                )
            }
        })
    })
})