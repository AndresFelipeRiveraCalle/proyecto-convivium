document.addEventListener("DOMContentLoaded", function () {

    const modal = document.getElementById("modalEditarTarifa");

    const cerrar = document.getElementById(
        "cerrarModalEditarTarifa"
    );

    const cancelar = document.getElementById(
        "cancelarModalEditarTarifa"
    );


    document.querySelectorAll(".btnEditarTarifa").forEach(function (boton) {

        boton.addEventListener("click", function () {

            const id = this.dataset.id;

            fetch(
                BASE_URL +
                "configuracion/editar_tarifa.php?id=" +
                encodeURIComponent(id)
            )
                .then(response => response.json())

                .then(data => {

                    if (!data.success) {

                        alert(data.mensaje);
                        return;

                    }


                    const tarifa = data.tarifa;


                    document.getElementById(
                        "editar_id_tarifa"
                    ).value = tarifa.id_tarifa;


                    document.getElementById(
                        "editar_id_concepto"
                    ).value = tarifa.id_concepto;


                    document.getElementById(
                        "editar_id_tipo_config"
                    ).value = tarifa.id_tipo_config;


                    document.getElementById(
                        "editar_nombre"
                    ).value = tarifa.nombre ?? "";


                    document.getElementById(
                        "editar_valor"
                    ).value = tarifa.valor;


                    document.getElementById(
                        "editar_fecha_inicio"
                    ).value = tarifa.fecha_inicio;


                    document.getElementById(
                        "editar_fecha_fin"
                    ).value = tarifa.fecha_fin ?? "";


                    document.getElementById(
                        "editar_observaciones"
                    ).value = tarifa.observaciones ?? "";


                    document.getElementById(
                        "editar_estado"
                    ).value = tarifa.estado;


                    modal.style.display = "flex";

                })

                .catch(error => {

                    console.error(error);

                    alert(
                        "No fue posible cargar la tarifa."
                    );

                });

        });

    });


    // ==========================================================
    // CERRAR
    // ==========================================================

    if (cerrar) {

        cerrar.addEventListener("click", function () {

            modal.style.display = "none";

        });

    }


    if (cancelar) {

        cancelar.addEventListener("click", function () {

            modal.style.display = "none";

        });

    }


    // ==========================================================
    // CERRAR AL HACER CLICK FUERA
    // ==========================================================

    if (modal) {

        modal.addEventListener("click", function (e) {

            if (e.target === modal) {

                modal.style.display = "none";

            }

        });

    }

});