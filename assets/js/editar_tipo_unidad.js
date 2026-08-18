document.addEventListener("DOMContentLoaded", function () {

    const btnEditar = document.getElementById("btnEditarTipoUnidad");
    const btnGuardar = document.getElementById("btnGuardarTipoUnidad");
    const btnCancelar = document.getElementById("btnCancelarTipoUnidad");

    if (!btnEditar) {
        return;
    }


    // ==========================================================
    // EDITAR
    // ==========================================================

    btnEditar.addEventListener("click", function () {

        document
            .querySelectorAll("#formTipoUnidad input, #formTipoUnidad textarea")
            .forEach(function (campo) {

                campo.removeAttribute("readonly");

            });


        btnEditar.style.display = "none";

        btnGuardar.style.display = "inline-block";

        btnCancelar.style.display = "inline-block";

    });


    // ==========================================================
    // CANCELAR
    // ==========================================================

    if (btnCancelar) {

        btnCancelar.addEventListener("click", function () {

            location.reload();

        });

    }

});