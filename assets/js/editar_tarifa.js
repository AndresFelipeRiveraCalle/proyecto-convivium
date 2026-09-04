document.addEventListener("DOMContentLoaded", function () {

    const modal =
        document.getElementById("modalEditarTarifa");

    const cerrar =
        document.getElementById(
            "cerrarModalEditarTarifa"
        );

    const cancelar =
        document.getElementById(
            "cancelarModalEditarTarifa"
        );

    const selectTipoCalculo =
        document.getElementById(
            "editar_tipo_calculo"
        );
    // ==========================================================
    // FUNCIÓN PARA ACTUALIZAR AYUDA DEL VALOR
    // ==========================================================

    function actualizarAyudaValor(tipoCalculo) {
        if (selectTipoCalculo) {
            selectTipoCalculo.value =
                tipoCalculo ?? "";
        }

        const labelValor =
            document.getElementById(
                "editar_label_valor"
            );

        const ayudaValor =
            document.getElementById(
                "editar_ayuda_valor"
            );


        if (!labelValor || !ayudaValor) {
            return;
        }


        switch (tipoCalculo) {

            case "FIJO":

                labelValor.textContent =
                    "Valor *";

                ayudaValor.textContent =
                    "Ingrese el valor fijo que se cobrará a la unidad.";

                break;


            case "METRO_CUADRADO":

                labelValor.textContent =
                    "Valor por metro cuadrado *";

                ayudaValor.textContent =
                    "Ingrese el valor que se cobrará por cada metro cuadrado del área de la unidad.";

                break;


            case "COEFICIENTE":

                labelValor.textContent =
                    "Base para cálculo por coeficiente *";

                ayudaValor.textContent =
                    "Ingrese el valor base que se utilizará junto con el coeficiente de la unidad.";

                break;


            case "PORCENTAJE":

                labelValor.textContent =
                    "Porcentaje *";

                ayudaValor.textContent =
                    "Ingrese el porcentaje que se utilizará para calcular el cobro.";

                break;


            default:

                labelValor.textContent =
                    "Valor *";

                ayudaValor.textContent =
                    "Ingrese el valor correspondiente al concepto.";

                break;
        }
    }


    // ==========================================================
    // ABRIR MODAL DE EDICIÓN
    // ==========================================================

    document
        .querySelectorAll(".btnEditarTarifa")
        .forEach(function (boton) {

            boton.addEventListener(
                "click",
                function () {

                    const id =
                        this.dataset.id;


                    fetch(
                        BASE_URL +
                        "actions/obtener_tarifa.php?id=" +
                        encodeURIComponent(id)
                    )

                    .then(response => {

                        if (!response.ok) {

                            throw new Error(
                                "Error HTTP: " +
                                response.status
                            );
                        }

                        return response.json();
                    })

                    .then(data => {

                        if (!data.success) {

                            alert(
                                data.message ??
                                "No fue posible consultar la tarifa."
                            );

                            return;
                        }


                        const tarifa =
                            data.tarifa;


                        // ==========================================
                        // CARGAR DATOS
                        // ==========================================

                        document.getElementById(
                            "editar_id_tarifa"
                        ).value =
                            tarifa.id_tarifa ?? "";


                        document.getElementById(
                            "editar_id_concepto"
                        ).value =
                            tarifa.id_concepto ?? "";


                        document.getElementById(
                            "editar_id_tipo_config"
                        ).value =
                            tarifa.id_tipo_config ?? "";


                        document.getElementById(
                            "editar_nombre"
                        ).value =
                            tarifa.nombre ?? "";


                        document.getElementById(
                            "editar_valor"
                        ).value =
                            tarifa.valor ?? "";


                        document.getElementById(
                            "editar_fecha_inicio"
                        ).value =
                            tarifa.fecha_inicio ?? "";


                        document.getElementById(
                            "editar_fecha_fin"
                        ).value =
                            tarifa.fecha_fin ?? "";


                        document.getElementById(
                            "editar_observaciones"
                        ).value =
                            tarifa.observaciones ?? "";


                        document.getElementById(
                            "editar_estado"
                        ).value =
                            tarifa.estado ?? 1;


                        // ==========================================
                        // ACTUALIZAR AYUDA DEL TIPO DE CÁLCULO
                        // ==========================================

                        actualizarAyudaValor(
                            tarifa.tipo_calculo
                        );


                        // ==========================================
                        // MOSTRAR MODAL
                        // ==========================================

                        if (modal) {

                            modal.style.display =
                                "flex";
                        }

                    })

                    .catch(error => {

                        console.error(
                            "Error cargando tarifa:",
                            error
                        );

                        alert(
                            "No fue posible cargar la tarifa."
                        );

                    });

                }
            );

        });


    // ==========================================================
    // CAMBIO MANUAL DE CONCEPTO
    // ==========================================================

    const selectConcepto =
        document.getElementById(
            "editar_id_concepto"
        );


    if (selectConcepto) {

        selectConcepto.addEventListener(
            "change",
            function () {

                const opcion =
                    this.options[
                        this.selectedIndex
                    ];


                const tipoCalculo =
                    opcion
                        ? opcion.dataset.tipoCalculo
                        : "";


                actualizarAyudaValor(
                    tipoCalculo
                );

            }
        );
    }


    // ==========================================================
    // FUNCIÓN CERRAR MODAL
    // ==========================================================

    function cerrarModal()
    {
        if (modal) {

            modal.style.display =
                "none";
        }
    }


    // ==========================================================
    // CERRAR CON X
    // ==========================================================

    if (cerrar) {

        cerrar.addEventListener(
            "click",
            cerrarModal
        );
    }


    // ==========================================================
    // CERRAR CON CANCELAR
    // ==========================================================

    if (cancelar) {

        cancelar.addEventListener(
            "click",
            cerrarModal
        );
    }


    // ==========================================================
    // CERRAR AL HACER CLICK FUERA
    // ==========================================================

    if (modal) {

        modal.addEventListener(
            "click",
            function (e) {

                if (e.target === modal) {

                    cerrarModal();
                }

            }
        );
    }

});