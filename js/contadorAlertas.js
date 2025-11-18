// Script para mostrar el número de alertas en el menú lateral
$(document).ready(function() {
    if (typeof cargarContadorAlertas === 'undefined') {
        cargarContadorAlertas();
    }
});

function cargarContadorAlertas() {
    // Cargar stock bajo
    $.post('../controlador/LoteController.php', {
        funcion: 'stock_riesgo'
    }, (response) => {
        const stockBajo = JSON.parse(response);
        
        // Cargar productos vencidos
        $.post('../controlador/LoteController.php', {
            funcion: 'buscar_lotes_riesgo'
        }, (response2) => {
            const vencidos = JSON.parse(response2);
            const totalAlertas = stockBajo.length + vencidos.length;
            
            // Actualizar badge en el menú
            if (totalAlertas > 0) {
                $('#badge-alertas-total').text(totalAlertas);
                $('#badge-alertas-total').show();
                
                // Agregar efecto visual si hay muchas alertas
                if (totalAlertas > 10) {
                    $('#badge-alertas-total').removeClass('badge-warning').addClass('badge-danger');
                    $('#badge-alertas-total').addClass('animate__animated animate__pulse animate__infinite');
                }
            } else {
                $('#badge-alertas-total').hide();
            }
        }).fail(() => {
            console.log('Error al cargar alertas de vencimientos');
        });
    }).fail(() => {
        console.log('Error al cargar alertas de stock');
    });
}

// Actualizar cada 10 minutos
if (typeof intervalAlertas === 'undefined') {
    var intervalAlertas = setInterval(cargarContadorAlertas, 600000);
}