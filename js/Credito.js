$(document).ready(function () {
  cargar_estadisticas();
  listar_creditos();
  var datatable_credito;

  // Función para formatear números con separador de miles
  function formatearNumero(numero) {
    return Math.round(numero).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  }

  // Generar reporte PDF de créditos
  $(document).on("click", "#btn-reporte-creditos", function() {
    var fecha_desde = $("#fecha_desde").val();
    var fecha_hasta = $("#fecha_hasta").val();
    
    var url = "../controlador/PDFController.php?funcion=reporte_creditos";
    
    if (fecha_desde) {
      url += "&fecha_desde=" + fecha_desde;
    }
    if (fecha_hasta) {
      url += "&fecha_hasta=" + fecha_hasta;
    }
    
    window.open(url, "_blank");
  });

  function cargar_estadisticas() {
    let funcion = "estadisticas_creditos";
    $.post("../controlador/VentaController.php", { funcion }, (response) => {
      console.log(response);
      const stats = JSON.parse(response);
      $("#total_creditos").html(stats.total_creditos || 0);
      $("#monto_total_creditos").html("$" + formatearNumero(stats.monto_total_creditos || 0));
      $("#monto_depositado").html("$" + formatearNumero(stats.monto_depositado || 0));
      $("#saldo_por_cobrar").html("$" + formatearNumero(stats.saldo_por_cobrar || 0));
    });
  }

  $("#credito_footer").keyup((e) => {
    calcularTotal();
  });

  $(document).on("click", "#credito_procesar_deposito", (e) => {
    añadir_deposito();
    Swal.fire({
      position: "center",
      icon: "success",
      title: "Se realizo el deposito",
      showConfirmButton: false,
      timer: 1500,
    });
  });

  function calcularTotal() {
    pago = $("#credito_pago").val();

    let total = parseFloat($("#credito_total").get(0).textContent.replace(/\./g, ''));
    let depositado = parseFloat($("#credito_depositado").get(0).textContent.replace(/\./g, ''));
    let saldo = total - depositado;
    let vuelto = pago - saldo;

    $("#credito_saldo").html(formatearNumero(saldo));
    $("#credito_vuelto").html(formatearNumero(vuelto));
  }

  function añadir_deposito() {
    funcion = "depositar";

    let id = $("#credito_codigo_venta").get(0).textContent;
    let pago = parseFloat($("#credito_pago").val());

    $.post(
      "../controlador/CompraController.php",
      { funcion, id, pago },
      (response) => {
        console.log(response);
      }
    );

    listar_creditos();
    cargar_estadisticas();
  }

  function listar_creditos() {
    funcion = "listar_creditos";

    datatable_credito = $("#tabla_credito").DataTable({
      ajax: {
        url: "../controlador/VentaController.php",
        method: "POST",
        data: { funcion: funcion },
      },
      columns: [
        { data: "id_venta" },
        { data: "fecha" },
        { data: "cliente" },
        { data: "dni" },
        { data: "depositado" },
        { data: "total" },
        { data: "vendedor" },
        {
          defaultContent: `<button class="ver btn btn-success" type="button" data-toggle="modal" data-target="#vista_credito"><i class="fas fa-search"></i></button>
          <button class="borrar btn btn-danger"><i class="fas fa-window-close"></i></button>`,
        },
      ],
      destroy: true,
      language: espanol,
    });
  }

  $("#tabla_credito tbody").on("click", ".borrar", function () {
    let datos = datatable_credito.row($(this).parents()).data();
    let id = datos.id_venta;
    funcion = "borrar_venta";
    const swalWithBootstrapButtons = Swal.mixin({
      customClass: {
        confirmButton: "btn btn-success m-1",
        cancelButton: "btn btn-danger m-1",
      },
      buttonsStyling: false,
    });

    swalWithBootstrapButtons
      .fire({
        title: "Esta seguro que decea eliminar el Credito: " + id + "?",
        text: "no podras revertir esto!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Si, borra esto!",
        cancelButtonText: "No, cancelar!",
        reverseButtons: true,
      })
      .then((result) => {
        if (result.value) {
          $.post(
            "../controlador/DetalleVentaController.php",
            { funcion, id },
            (response) => {
              console.log(response);
              if (response == "delete") {
                swalWithBootstrapButtons.fire(
                  "Eliminado!",
                  "El credito: " + id + " ha sido Eliminada",
                  "success"
                );
                listar_creditos();
                cargar_estadisticas();
              } else if ((response = "nodelete")) {
                swalWithBootstrapButtons.fire(
                  "No eliminado",
                  "No tienes prioridad para eliminar este credito",
                  "error"
                );
              }
            }
          );
        } else if (result.dismiss === Swal.DismissReason.cancel) {
          swalWithBootstrapButtons.fire(
            "No eliminado",
            "El credito no se elimino :)",
            "error"
          );
        }
      });
  });

  $("#tabla_credito tbody").on("click", ".ver", function () {
    let datos = datatable_credito.row($(this).parents()).data();
    let id = datos.id_venta;
    funcion = "ver";
    $("#credito_codigo_venta").html(datos.id_venta);
    $("#credito_fecha").html(datos.fecha);
    $("#credito_cliente").html(datos.cliente);
    $("#credito_dni").html(datos.dni);
    $("#credito_vendedor").html(datos.vendedor);
    $("#credito_total").html(formatearNumero(datos.total));
    $("#credito_depositado").html(formatearNumero(datos.depositado));
    
    // Limpiar el campo de pago
    $("#credito_pago").val("");
    
    calcularTotal();

    $.post(
      "../controlador/VentaProductoController.php",
      { funcion, id },
      (response) => {
        let registros = JSON.parse(response);
        let template = "";
        $("#credito_registros").html(template);
        registros.forEach((registro) => {
          template += `
                      <tr>
                          <td>${registro.cantidad}</td>
                          <td>${formatearNumero(registro.precio)}</td>
                          <td>${registro.producto}</td>
                          <td>${registro.concentracion}</td>
                          <td>${registro.adicional}</td>
                          <td>${registro.laboratorio}</td>
                          <td>${registro.presentacion}</td>
                          <td>${registro.tipo}</td>
                          <td>${formatearNumero(registro.subtotal)}</td>

                      </tr>
                  `;
          $("#credito_registros").html(template);
        });
      }
    );
  });
});

let espanol = {
  sProcessing: "Procesando...",
  sLengthMenu: "Mostrar _MENU_ registros",
  sZeroRecords: "No se encontraron resultados",
  sEmptyTable: "Ningún dato disponible en esta tabla",
  sInfo:
    "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
  sInfoEmpty: "Mostrando registros del 0 al 0 de un total de 0 registros",
  sInfoFiltered: "(filtrado de un total de _MAX_ registros)",
  sInfoPostFix: "",
  sSearch: "Buscar:",
  sUrl: "",
  sInfoThousands: ",",
  sLoadingRecords: "Cargando...",
  oPaginate: {
    sFirst: "Primero",
    sLast: "Último",
    sNext: "Siguiente",
    sPrevious: "Anterior",
  },
  oAria: {
    sSortAscending: ": Activar para ordenar la columna de manera ascendente",
    sSortDescending: ": Activar para ordenar la columna de manera descendente",
  },
  buttons: {
    copy: "Copiar",
    colvis: "Visibilidad",
  },
};
