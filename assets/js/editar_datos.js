document.addEventListener("DOMContentLoaded", function () {

    const btnEditar = document.getElementById("btnEditar");
    const btnGuardar = document.getElementById("btnGuardar");
    const btnCancelar = document.getElementById("btnCancelarEdicion");

    if (!btnEditar) return;

    btnEditar.addEventListener("click", function () {

        // Habilitar inputs de texto
        document.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"]').forEach(function(campo){
            campo.removeAttribute("readonly");
        });

        // Habilitar archivos
        document.querySelectorAll('input[type="file"]').forEach(function(campo){
            campo.removeAttribute("disabled");
        });

        // Habilitar selects
        document.querySelectorAll("select").forEach(function(campo){
            campo.removeAttribute("disabled");
        });

        // Habilitar textarea
        document.querySelectorAll("textarea").forEach(function(campo){
            campo.removeAttribute("readonly");
        });

        // Cambiar botones
        btnEditar.style.display = "none";
        btnGuardar.style.display = "inline-block";
        btnCancelar.style.display = "inline-block";

    });

    if (btnCancelar) {
        btnCancelar.addEventListener("click", function () {
            location.reload();
        });
    }

});