
document.addEventListener("DOMContentLoaded", function () {

    const boton = document.getElementById("btnNuevaAgrupacion");
    const modal = document.getElementById("modalNuevaAgrupacion");
    const cerrar = document.getElementById("cerrarModalAgrupacion");
    const cancelar = document.getElementById("cancelarModalAgrupacion");


    // ABRIR
    if (boton && modal) {

        boton.addEventListener("click", function () {

            modal.style.display = "block";

        });

    }


    // CERRAR CON X
    if (cerrar && modal) {

        cerrar.addEventListener("click", function () {

            modal.style.display = "none";

        });

    }


    // CERRAR CON CANCELAR
    if (cancelar && modal) {

        cancelar.addEventListener("click", function () {

            modal.style.display = "none";

        });

    }


    // CERRAR AL HACER CLICK FUERA
    if (modal) {

        modal.addEventListener("click", function (e) {

            if (e.target === modal) {

                modal.style.display = "none";

            }

        });

    }

});