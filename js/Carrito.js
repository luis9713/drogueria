$(document).ready(function () {
  $(".select2").select2();
  rellenar_clientes();
  calcularTotal();
  Contar_productos();
  RecuperarLS_carrito_compra();
  RecuperarLS_carrito();
  
  // Función para formatear números con separador de miles
  function formatearNumero(numero) {
    return Math.round(numero).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  }

  function mostrarToastAgregado(nombreProducto) {
    const Toast = Swal.mixin({
      toast: true,
      position: "top",
      showConfirmButton: false,
      timer: 1400,
      timerProgressBar: true,
    });

    Toast.fire({
      icon: "success",
      title: `${nombreProducto} agregado al carrito`,
    });
  }
  
  $(document).on("click", ".agregar-carrito", (e) => {
    const elemento =
      $(this)[0].activeElement.parentElement.parentElement.parentElement
        .parentElement;
    const id = $(elemento).attr("prodId");
    const nombre = $(elemento).attr("prodNombre");
    const concentracion = $(elemento).attr("prodConcentracion");
    const adicional = $(elemento).attr("prodAdicional");
    const precio = $(elemento).attr("prodPrecio");
    const laboratorio = $(elemento).attr("prodLaboratorio");
    const tipo = $(elemento).attr("prodTipo");
    const presentacion = $(elemento).attr("prodPresentacion");
    const avatar = $(elemento).attr("prodAvatar");
    const stock = $(elemento).attr("prodStock");
    const producto = {
      id: id,
      nombre: nombre,
      concentracion: concentracion,
      adicional: adicional,
      precio: precio,
      laboratorio: laboratorio,
      tipo: tipo,
      presentacion: presentacion,
      avatar: avatar,
      stock: stock,
      cantidad: 1,
    };
    let id_producto;
    let productos;
    productos = RecuperarLS();
    productos.forEach((prod) => {
      if (prod.id === producto.id) {
        id_producto = prod.id;
      }
    });
    if (id_producto === producto.id) {
      Swal.fire({
        icon: "error",
        title: "Oops...",
        text: "El producto ya existe!",
      });
    } else {
      template = `
            <tr prodId="${producto.id}">
                <td>${producto.id}</td>
                <td>${producto.nombre}</td>
                <td>${producto.concentracion}</td>
                <td>${producto.adicional}</td>
                <td>${formatearNumero(producto.precio)}</td>
                <td><button class="borrar-producto btn btn-danger"><i class="fas fa-times-circle"></i></button></td>
            </tr>
        `;
      $("#lista").append(template);
      AgregarLS(producto);
      let contador;
      Contar_productos();
      mostrarToastAgregado(producto.nombre);
    }
  });
  $(document).on("click", ".borrar-producto", (e) => {
    const elemento = $(this)[0].activeElement.parentElement.parentElement;
    const id = $(elemento).attr("prodId");
    elemento.remove();
    Eliminar_producto_LS(id);
    Contar_productos();
    calcularTotal();
  });
  $(document).on("click", "#vaciar-carrito", (e) => {
    $("#lista").empty();
    EliminarLS();
    Contar_productos();
  });
  $(document).on("click", "#procesar-pedido", (e) => {
    Procesar_pedido();
  });
  $(document).on("click", "#procesar-compra", (e) => {
    Procesar_compra();
  });

  $(document).on("change", "#tipo_pago", () => {
    actualizarUIPago();
    calcularTotal();
  });

  $(document).on("keyup change", "#pago_efectivo, #pago_nequi", () => {
    calcularTotal();
  });

  function RecuperarLS() {
    let productos;
    if (localStorage.getItem("productos") === null) {
      productos = [];
    } else {
      productos = JSON.parse(localStorage.getItem("productos"));
    }
    return productos;
  }

  function AgregarLS(producto) {
    let productos;
    productos = RecuperarLS();
    productos.push(producto);
    localStorage.setItem("productos", JSON.stringify(productos));
  }

  function RecuperarLS_carrito() {
    let productos, id_producto;
    productos = RecuperarLS();
    funcion = "buscar_id";
    productos.forEach((producto) => {
      id_producto = producto.id;
      $.post(
        "../controlador/ProductoController.php",
        { funcion, id_producto },
        (response) => {
          let template_carrito = "";
          let json = JSON.parse(response);
          template_carrito = `
                            <tr prodId="${json.id}">
                              <td>${json.id}</td>
                              <td>${json.nombre}</td>
                              <td>${json.concentracion}</td>
                              <td>${json.adicional}</td>
                              <td>${formatearNumero(json.precio)}</td>
                              <td><button class="borrar-producto btn btn-danger"><i class="fas fa-times-circle"></i></button></td>
                            </tr>
            `;
          $("#lista").append(template_carrito);
        }
      );
    });
  }

  function Eliminar_producto_LS(id) {
    let productos;
    productos = RecuperarLS();
    productos.forEach(function (producto, indice) {
      if (producto.id === id) {
        productos.splice(indice, 1);
      }
    });
    localStorage.setItem("productos", JSON.stringify(productos));
  }

  function EliminarLS() {
    localStorage.clear();
  }

  function Contar_productos() {
    let productos;
    let contador = 0;
    productos = RecuperarLS();
    productos.forEach((producto) => {
      contador++;
    });
    $("#contador").html(contador);
  }

  function Procesar_pedido() {
    let productos;
    productos = RecuperarLS();
    if (productos.length === 0) {
      Swal.fire({
        icon: "error",
        title: "Oops...",
        text: "El carrito esta vacio!",
      });
    } else {
      location.href = "../vista/adm_compra.php";
    }
  }

  async function RecuperarLS_carrito_compra() {
    let productos;
    productos = RecuperarLS();
    funcion = "traer_productos";
    const response = await fetch("../controlador/ProductoController.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "funcion=" + funcion + "&&productos=" + JSON.stringify(productos),
    });
    let resultado = await response.text();
    $("#lista-compra").append(resultado);
  }

  $(document).on("click", "#actualizar", (e) => {
    let productos, precios;
    precios = document.querySelectorAll(".precio");
    productos = RecuperarLS();
    productos.forEach(function (producto, indice) {
      // Quitar puntos del formato antes de guardar
      producto.precio = precios[indice].textContent.replace(/\./g, '');
    });
    localStorage.setItem("productos", JSON.stringify(productos));
    calcularTotal();
  });
  $("#cp").keyup((e) => {
    let id, cantidad, producto, productos, montos, precio;
    producto = $(this)[0].activeElement.parentElement.parentElement;
    id = $(producto).attr("prodId");
    precio = $(producto).attr("prodPrecio");
    cantidad = producto.querySelector("input").value;
    montos = document.querySelectorAll(".subtotales");
    productos = RecuperarLS();
    productos.forEach(function (prod, indice) {
      if (prod.id === id) {
        prod.cantidad = cantidad;
        prod.precio = precio;
        montos[indice].innerHTML = `<h5>${formatearNumero(cantidad * precio)}</h5>`;
      }
    });
    localStorage.setItem("productos", JSON.stringify(productos));
    calcularTotal();
  });

  function calcularTotal() {
    let productos,
      subtotal,
      con_igv,
      total_sin_descuento,
      pago,
      vuelto,
      descuento;
    let total = 0,
      igv = 0.18;
    productos = RecuperarLS();
    productos.forEach((producto) => {
      let subtotal_producto = Number(producto.precio * producto.cantidad);
      total = total + subtotal_producto;
    });
    pago = parseFloat($("#pago").val()) || 0;
    const pago_efectivo = parseFloat($("#pago_efectivo").val()) || 0;
    const pago_nequi = parseFloat($("#pago_nequi").val()) || 0;
    const tipo_pago = $("#tipo_pago").val();
    descuento = $("#descuento").val();

    total_sin_descuento = total.toFixed(2);
    con_igv = parseFloat(total * igv).toFixed(2);
    subtotal = parseFloat(total - con_igv).toFixed(2);

    total = total - descuento;
    if (tipo_pago === "Mixto") {
      vuelto = (pago_efectivo + pago_nequi) - total;
    } else if (tipo_pago === "Nequi") {
      vuelto = 0;
    } else {
      vuelto = pago - total;
    }
    $("#subtotal").html(formatearNumero(subtotal));
    $("#con_igv").html(formatearNumero(con_igv));
    $("#total_sin_descuento").html(formatearNumero(total_sin_descuento));
    $("#total").html(formatearNumero(total));
    $("#vuelto").html(formatearNumero(vuelto));
  }

  function actualizarUIPago() {
    const tipo_pago = $("#tipo_pago").val();

    if (tipo_pago === "Mixto") {
      $("#bloque-mixto").show();
      $("#pago").closest(".info-box").hide();
    } else {
      $("#bloque-mixto").hide();
      $("#pago").closest(".info-box").show();
    }
  }

  function Procesar_compra() {
    let tipo_pago_sel = $("#tipo_pago").val();

    // Si el pago es Nequi, NO requiere caja abierta (es un pago digital aparte)
    if (tipo_pago_sel === 'Nequi') {
      let cliente = $("#cliente").val();
      if (RecuperarLS().length == 0) {
        Swal.fire({ icon: "error", title: "Oops...", text: "No hay productos, Seleccione algunos!" })
          .then(function () { location.href = "../vista/adm_catalogo.php"; });
        return;
      }
      if (cliente == "") {
        Swal.fire({ icon: "error", title: "Oops...", text: "Necesitamos un cliente!" });
        return;
      }
      Verificar_stock().then((error) => {
        if (error == 0) {
          Registrar_compra(cliente).then((registrado) => {
            if (!registrado) {
              return;
            }
            Swal.fire({
              position: "center",
              icon: "success",
              title: "Venta Nequi registrada",
              html: "<small>Esta venta se registró como pago Nequi 📱</small>",
              showConfirmButton: false,
              timer: 1800,
            }).then(function () {
              EliminarLS();
              location.href = "../vista/adm_catalogo.php";
            });
          });
        } else {
          Swal.fire({ icon: "error", title: "Oops...", text: "Hay conflicto en el stock de algún producto!" });
        }
      });
      return;
    }

    // Para Contado, Crédito y Mixto: verificar si hay caja abierta
    let funcion_caja = "verificar_caja_abierta";
    $.post("../controlador/CajaController.php", { funcion: funcion_caja }, (response) => {
      const resultado = JSON.parse(response);
      
      if (resultado.estado === 'cerrada') {
        Swal.fire({
          icon: "warning",
          title: "Caja no abierta",
          html: "No se puede procesar la venta porque no hay una caja abierta.<br><br>Por favor, vaya a <strong>Gestión de Caja</strong> para abrir una caja.",
          confirmButtonText: "Entendido",
          confirmButtonColor: "#3085d6"
        });
        return;
      }
      
      let cliente = $("#cliente").val();
      if (RecuperarLS().length == 0) {
        Swal.fire({
          icon: "error",
          title: "Oops...",
          text: "No hay productos, Selecciones algunos!",
        }).then(function () {
          location.href = "../vista/adm_catalogo.php";
        });
      } else if (cliente == "") {
        Swal.fire({
          icon: "error",
          title: "Oops...",
          text: "Necesitamos un cliente!",
        });
      } else {
        Verificar_stock().then((error) => {
          if (error == 0) {
            Registrar_compra(cliente).then((registrado) => {
              if (!registrado) {
                return;
              }
              Swal.fire({
                position: "center",
                icon: "success",
                title: "Se realizo la compra",
                showConfirmButton: false,
                timer: 1500,
              }).then(function () {
                EliminarLS();
                location.href = "../vista/adm_catalogo.php";
              });
            });
          } else {
          Swal.fire({
            icon: "error",
            title: "Oops...",
            text: "Hay conflicto en el stock de algun producto!",
          });
        }
      });
      }
    });
  }

  async function Verificar_stock() {
    let productos;
    funcion = "verificar_stock";
    productos = RecuperarLS();
    const response = await fetch("../controlador/ProductoController.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "funcion=" + funcion + "&&productos=" + JSON.stringify(productos),
    });
    let error = await response.text();

    return error;
  }

  async function Registrar_compra(cliente) {
    funcion = "registrar_compra";
    let total = parseFloat($("#total").get(0).textContent.replace(/\./g, ''));
    let productos = RecuperarLS();
    let json = JSON.stringify(productos);
    let tipo_pago = $("#tipo_pago").val();
    let pago = parseFloat($("#pago").val()) || 0;
    let pago_efectivo = parseFloat($("#pago_efectivo").val()) || 0;
    let pago_nequi = parseFloat($("#pago_nequi").val()) || 0;

    if (tipo_pago == "Mixto") {
      if (pago_nequi < 0 || pago_efectivo < 0) {
        Swal.fire({ icon: "error", title: "Montos inválidos", text: "Los montos no pueden ser negativos" });
        return false;
      }
      if (pago_nequi > total) {
        Swal.fire({ icon: "error", title: "Monto Nequi inválido", text: "El pago por Nequi no puede superar el total" });
        return false;
      }
      if ((pago_efectivo + pago_nequi) < total) {
        Swal.fire({ icon: "error", title: "Pago incompleto", text: "La suma de Efectivo + Nequi debe cubrir el total" });
        return false;
      }
      pago = pago_nequi;
    }

    // Para Nequi: el pago es el total completo (es contado digital)
    if (tipo_pago == "Nequi") {
      pago = total;
      pago_nequi = total;
      pago_efectivo = 0;
    } else if (tipo_pago == "Contado" && pago >= total) {
      pago = total;
      tipo_pago = "Contado";
      pago_efectivo = pago;
      pago_nequi = 0;
    } else if (tipo_pago == "Contado") {
      if (pago < total) {
        Swal.fire({ icon: "error", title: "Pago insuficiente", text: "En contado el pago debe cubrir el total" });
        return false;
      }
      pago = total;
      pago_efectivo = pago;
      pago_nequi = 0;
    } else if (tipo_pago == "Credito") {
      pago_efectivo = pago;
      pago_nequi = 0;
    }

    try {
      const response = await $.post(
        "../controlador/CompraController.php",
        { funcion, total, cliente, json, tipo_pago, pago, pago_efectivo, pago_nequi }
      );
      const res = (response || "").toString().trim();

      if (res === "success") {
        return true;
      }

      if (res === "error_mixto") {
        Swal.fire({ icon: "error", title: "Pago mixto inválido", text: "Verifica los montos de Efectivo y Nequi" });
      } else if (res === "error_monto_credito") {
        Swal.fire({ icon: "error", title: "Abono inicial inválido", text: "El abono inicial de crédito no puede ser negativo" });
      } else if (res === "error_venta") {
        Swal.fire({ icon: "error", title: "No se pudo registrar", text: "Ocurrió un problema al registrar la venta" });
      } else if (res === "error_sesion") {
        Swal.fire({ icon: "warning", title: "Sesión expirada", text: "Vuelve a iniciar sesión" });
      } else {
        Swal.fire({ icon: "error", title: "Error", text: "No se pudo registrar la venta" });
      }
    } catch (e) {
      Swal.fire({ icon: "error", title: "Error de comunicación", text: "No se pudo conectar con el servidor" });
    }

    return false;
  }

  function rellenar_clientes() {
    funcion = "rellenar_clientes";
    $.post("../controlador/ClienteController.php", { funcion }, (response) => {
      //console.log(response);
      let clientes = JSON.parse(response);
      let template = "";
      clientes.forEach((cliente) => {
        template += `
                <option value="${cliente.id}" >${cliente.nombre}</option>
              `;
      });
      $("#cliente").html(template);
    });
  }

  actualizarUIPago();
});
