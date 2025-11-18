$(document).ready(function(){
    var funcion='';
    var id_usuario = $('#id_usuario').val();
        buscar_usuario(id_usuario);

function buscar_usuario(dato) {
    funcion='buscar_usuario';
    $.post('../controlador/UsuarioController.php',{dato,funcion},(response)=>{
        console.log(response);
        let nombre='';
        let apellidos='';
        let edad='';
        let dni='';
        let tipo='';
        let telefono='';
        let domicilio='';
        let email='';
        let sexo='';
        let adicional='';
        const usuario = JSON.parse(response);
        nombre+=`${usuario.nombre}`;
        apellidos+=`${usuario.apellidos}`;
        edad+=`${usuario.edad}`;
        dni+=`${usuario.dni}`;
        tipo+=`${usuario.tipo}`;
        telefono+=`${usuario.telefono}`;
        domicilio+=`${usuario.domicilio}`;
        email+=`${usuario.email}`;
        sexo+=`${usuario.sexo}`;
        adicional+=`${usuario.adicional}`;
        $('#nombre_us').html(nombre);

    })
    
}
     })