document.addEventListener("DOMContentLoaded", function () {

    // ==========================================================
    // CAMPOS DEL FORMULARIO NUEVA TARIFA
    // ==========================================================

    const selectConcepto =
        document.getElementById(
            "nuevo_id_concepto"
        );

    const labelValor =
        document.getElementById(
            "nuevo_label_valor"
        );

    const ayudaValor =
        document.getElementById(
            "nuevo_ayuda_valor"
        );

    const ayudaTipoCalculo =
        document.getElementById(
            "nuevo_ayuda_tipo_calculo"
        );

    const inputValor =
        document.getElementById(
            "nuevo_valor"
        );

    const inputTipoCalculo =
        document.getElementById(
            "nuevo_tipo_calculo"
        );

    // ==========================================================
    // ACTUALIZAR INFORMACIÓN SEGÚN TIPO DE CÁLCULO
    // ==========================================================

    function actualizarTipoCalculo(tipoCalculo) {

        if (
            !labelValor ||
            !ayudaValor ||
            !ayudaTipoCalculo
        ) {
            return;
        }


        // ======================================================
        // FIJO
        // ======================================================

        if (tipoCalculo === "FIJO") {

            if (inputTipoCalculo) {
                inputTipoCalculo.value =
                    "FIJO";
            }

            labelValor.textContent =
                "Valor fijo *";

            ayudaTipoCalculo.textContent =
                "Este concepto se cobrará como un valor fijo.";

            ayudaValor.textContent =
                "Ingrese el valor total que se cobrará a cada unidad.";

            if (inputValor) {

                inputValor.placeholder =
                    "Ej. 500000";

                inputValor.step =
                    "0.01";
            }

            return;
        }


        // ======================================================
        // METRO CUADRADO
        // ======================================================

        if (tipoCalculo === "METRO_CUADRADO") {

            if (inputTipoCalculo) {
                inputTipoCalculo.value =
                    "METRO_CUADRADO";
            }

            labelValor.textContent =
                "Valor por metro cuadrado *";

            ayudaTipoCalculo.textContent =
                "El valor será multiplicado por el área de cada unidad.";

            ayudaValor.textContent =
                "Ingrese el valor correspondiente a un metro cuadrado.";

            if (inputValor) {

                inputValor.placeholder =
                    "Ej. 4500";

                inputValor.step =
                    "0.01";
            }

            return;
        }


        if (tipoCalculo === "COEFICIENTE") {

            if (inputTipoCalculo) {
                inputTipoCalculo.value =
                    "COEFICIENTE";
            }

            labelValor.textContent =
                "Valor base para coeficiente *";

            ayudaTipoCalculo.textContent =
                "El cobro se calculará utilizando el coeficiente de copropiedad de cada unidad.";

            ayudaValor.textContent =
                "Ingrese el valor base sobre el cual se aplicará el coeficiente.";

            if (inputValor) {

                inputValor.placeholder =
                    "Ej. 50000000";

                inputValor.step =
                    "0.01";
            }

            return;
        }


        // ======================================================
        // PORCENTAJE
        // ======================================================

        if (tipoCalculo === "PORCENTAJE") {

            if (inputTipoCalculo) {
                inputTipoCalculo.value =
                    "PORCENTAJE";
            }

            labelValor.textContent =
                "Porcentaje *";

            ayudaTipoCalculo.textContent =
                "Este concepto se calculará utilizando un porcentaje.";

            ayudaValor.textContent =
                "Ingrese el porcentaje. Ejemplo: 2.5 corresponde al 2,5 %.";

            if (inputValor) {

                inputValor.placeholder =
                    "Ej. 2.5";

                inputValor.step =
                    "0.0001";
            }

            return;
        }


        // ======================================================
        // SIN CONCEPTO SELECCIONADO
        // ======================================================

        if (inputTipoCalculo) {
            inputTipoCalculo.value = "";
        }

        labelValor.textContent =
            "Valor *";

        ayudaTipoCalculo.textContent =
            "Seleccione un concepto para conocer cómo se calculará la tarifa.";

        ayudaValor.textContent =
            "Ingrese el valor correspondiente al concepto.";

        if (inputValor) {

            inputValor.placeholder =
                "0.00";

            inputValor.step =
                "0.01";
        }
    }


    // ==========================================================
    // CAMBIO DE CONCEPTO
    // ==========================================================

    if (selectConcepto) {

        selectConcepto.addEventListener(
            "change",
            function () {

                const opcionSeleccionada =
                    this.options[
                        this.selectedIndex
                    ];


                const tipoCalculo =
                    opcionSeleccionada
                        ? opcionSeleccionada.dataset.tipoCalculo
                        : "";


                actualizarTipoCalculo(
                    tipoCalculo
                );

            }
        );
    }


    // ==========================================================
    // ESTADO INICIAL
    // ==========================================================

    actualizarTipoCalculo("");

});