document.addEventListener("DOMContentLoaded", function () {

    const btnEditar = document.getElementById("btnEditar");
    const btnGuardar = document.getElementById("btnGuardar");
    const btnCancelar = document.getElementById("btnCancelarEdicion");

    if (!btnEditar) return;

    btnEditar.addEventListener("click", function () {

        // Habilitar todos los campos del formulario
        document.querySelectorAll("input, select, textarea").forEach(function (campo) {

            if (
                campo.type !== "hidden" &&
                campo.type !== "submit" &&
                campo.type !== "button"
            ) {
                campo.removeAttribute("readonly");
                campo.removeAttribute("disabled");
            }

        });

        btnEditar.style.display = "none";

        if (btnGuardar)
            btnGuardar.style.display = "inline-block";

        if (btnCancelar)
            btnCancelar.style.display = "inline-block";

    });

    if (btnCancelar) {

        btnCancelar.addEventListener("click", function () {

            location.reload();

        });

    }

});