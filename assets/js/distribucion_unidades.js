document.addEventListener("DOMContentLoaded", function () {

    const campos = document.querySelectorAll(".cantidad-distribucion");
    const total = document.getElementById("totalDistribuido");
    const configurada = document.getElementById("cantidadConfigurada");

    if (!campos.length || !total || !configurada) {
        return;
    }

    function calcularTotal() {

        let suma = 0;

        campos.forEach(function (campo) {

            let valor = parseInt(campo.value) || 0;

            if (valor < 0) {
                valor = 0;
                campo.value = 0;
            }

            suma += valor;
        });

        total.textContent = suma;

    }

    campos.forEach(function (campo) {

        campo.addEventListener("input", calcularTotal);

    });

    calcularTotal();

});