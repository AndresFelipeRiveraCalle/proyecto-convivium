document.addEventListener("DOMContentLoaded", function () {

    const form = document.getElementById("formTipoUnidad");

    const btnEditar = document.getElementById("btnEditarTipoUnidad");
    const btnGuardar = document.getElementById("btnGuardarTipoUnidad");
    const btnCancelar = document.getElementById("btnCancelarTipoUnidad");


    if (!form || !btnEditar) {
        return;
    }


    // ==========================================================
    // CAMPOS EDITABLES
    // ==========================================================

    const camposEditables = form.querySelectorAll(
        'input[name="cantidad_unidades"], ' +
        'input[name="area_total"], ' +
        'input[name="coeficiente_total"], ' +
        'textarea[name="observaciones"]'
    );


    // ==========================================================
    // GUARDAR VALORES ORIGINALES
    // ==========================================================

    const valoresOriginales = {};


    camposEditables.forEach(function (campo) {

        valoresOriginales[campo.name] = campo.value;

    });


    // ==========================================================
    // ACTIVAR MODO EDICIÓN
    // ==========================================================

    function activarEdicion() {

        camposEditables.forEach(function (campo) {

            campo.removeAttribute("readonly");

        });


        btnEditar.style.display = "none";


        if (btnGuardar) {

            btnGuardar.style.display = "inline-block";

        }


        if (btnCancelar) {

            btnCancelar.style.display = "inline-block";

        }

    }


    // ==========================================================
    // DESACTIVAR MODO EDICIÓN
    // ==========================================================

    function desactivarEdicion() {

        camposEditables.forEach(function (campo) {

            campo.setAttribute("readonly", "readonly");

        });


        btnEditar.style.display = "inline-block";


        if (btnGuardar) {

            btnGuardar.style.display = "none";

        }


        if (btnCancelar) {

            btnCancelar.style.display = "none";

        }

    }


    // ==========================================================
    // EDITAR
    // ==========================================================

    btnEditar.addEventListener("click", function () {

        activarEdicion();

    });


    // ==========================================================
    // CANCELAR
    // ==========================================================

    if (btnCancelar) {

        btnCancelar.addEventListener("click", function () {

            camposEditables.forEach(function (campo) {

                if (
                    Object.prototype.hasOwnProperty.call(
                        valoresOriginales,
                        campo.name
                    )
                ) {

                    campo.value =
                        valoresOriginales[campo.name];

                }

            });


            desactivarEdicion();

        });

    }

});