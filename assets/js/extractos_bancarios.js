document.addEventListener("DOMContentLoaded", function () {

    // ==========================================================
    // MODAL NUEVO EXTRACTO
    // ==========================================================

    const modal = document.getElementById("modalNuevoExtracto");

    const btnNuevo = document.getElementById("btnNuevoExtracto");

    const btnCerrar = document.getElementById("cerrarNuevoExtracto");

    const btnCancelar = document.getElementById(
        "cancelarNuevoExtracto"
    );


    // ==========================================================
    // ABRIR MODAL
    // ==========================================================

    if (btnNuevo) {

        btnNuevo.addEventListener("click", function () {

            modal.style.display = "flex";

        });

    }


    // ==========================================================
    // CERRAR MODAL
    // ==========================================================

    function cerrarModal() {

        modal.style.display = "none";

    }


    if (btnCerrar) {

        btnCerrar.addEventListener(
            "click",
            cerrarModal
        );

    }


    if (btnCancelar) {

        btnCancelar.addEventListener(
            "click",
            cerrarModal
        );

    }


    // ==========================================================
    // CERRAR AL HACER CLICK FUERA DEL MODAL
    // ==========================================================

    window.addEventListener("click", function (event) {

        if (event.target === modal) {

            cerrarModal();

        }

    });


    // ==========================================================
    // VALIDACIÓN DEL ARCHIVO
    // ==========================================================

    const inputArchivo =
        document.getElementById("archivo");


    if (inputArchivo) {

        inputArchivo.addEventListener(
            "change",
            function () {

                if (!this.files.length) {
                    return;
                }


                const archivo = this.files[0];

                const nombre =
                    archivo.name.toLowerCase();


                const extensionesPermitidas = [
                    ".pdf",
                    ".jpg",
                    ".jpeg",
                    ".png"
                ];


                const extensionValida =
                    extensionesPermitidas.some(
                        function (extension) {

                            return nombre.endsWith(
                                extension
                            );

                        }
                    );


                if (!extensionValida) {

                    alert(
                        "El archivo debe ser PDF, JPG, JPEG o PNG."
                    );

                    this.value = "";

                    return;

                }


                // ==============================================
                // TAMAÑO MÁXIMO
                // ==============================================

                const maximo =
                    10 * 1024 * 1024;


                if (archivo.size > maximo) {

                    alert(
                        "El archivo no puede superar los 10 MB."
                    );

                    this.value = "";

                }

            }
        );

    }

});