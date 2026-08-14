document.addEventListener("DOMContentLoaded", function () {

    const modal = document.getElementById("modalTipoAgrupacion");
    const btnAbrir = document.getElementById("btnAgregarTipoAgrupacion");
    const btnCerrar = document.getElementById("cerrarModalTipoAgrupacion");
    const btnCancelar = document.getElementById("cancelarModalTipoAgrupacion");

    if (!modal || !btnAbrir) {
        return;
    }

    btnAbrir.addEventListener("click", function () {
        modal.style.display = "flex";
    });

    if (btnCerrar) {
        btnCerrar.addEventListener("click", function () {
            modal.style.display = "none";
        });
    }

    if (btnCancelar) {
        btnCancelar.addEventListener("click", function () {
            modal.style.display = "none";
        });
    }

    window.addEventListener("click", function (event) {

        if (event.target === modal) {
            modal.style.display = "none";
        }

    });

});