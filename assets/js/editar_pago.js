document.addEventListener("DOMContentLoaded", function () {

    document.querySelectorAll(".btnEditarPago").forEach(function (boton) {

        boton.addEventListener("click", function () {

            const id = this.dataset.id;

            if (!id) {
                alert("No se encontró el ID del pago.");
                return;
            }

            // ==================================================
            // RUTA ABSOLUTA DEL PROYECTO
            // ==================================================

            const url =
                "/proyecto-convivium/actions/editar_pago.php?id=" +
                encodeURIComponent(id);

            console.log("Consultando:", url);

            fetch(url)

                .then(function (response) {

                    if (!response.ok) {
                        throw new Error(
                            "Error HTTP " + response.status
                        );
                    }

                    return response.json();
                })

                .then(function (data) {

                    console.log("Respuesta:", data);

                    if (!data.success) {

                        alert(
                            data.mensaje ||
                            "No fue posible consultar el pago."
                        );

                        return;
                    }

                    const pago = data.pago;
                    // ==========================================================
                    // MODO EDITAR
                    // ==========================================================

                    form.action =
                        "/proyecto-convivium/actions/actualizar_pago.php";
                        
                    // ==========================================
                    // CARGAR DATOS
                    // ==========================================

                    document.getElementById("id_pago").value =
                        pago.id_pago;

                    document.getElementById("id_unidad").value =
                        pago.id_unidad;

                    document.getElementById("fecha_pago").value =
                        pago.fecha_pago;

                    document.getElementById("valor").value =
                        pago.valor;

                    document.getElementById("medio_pago").value =
                        pago.medio_pago;

                    document.getElementById("origen_pago").value =
                        pago.origen_pago;

                    document.getElementById("referencia").value =
                        pago.referencia || "";

                    document.getElementById("observaciones").value =
                        pago.observaciones || "";


                    // ==========================================
                    // CAMBIAR TÍTULO
                    // ==========================================

                    const titulo =
                        document.querySelector(
                            "#modalPago .modal-header h2"
                        );

                    if (titulo) {
                        titulo.textContent = "Editar pago";
                    }


                    // ==========================================
                    // ABRIR MODAL
                    // ==========================================

                    const modal =
                        document.getElementById("modalPago");

                    if (modal) {
                        modal.style.display = "flex";
                    }

                })

                .catch(function (error) {

                    console.error(
                        "Error al consultar pago:",
                        error
                    );

                    alert(
                        "Ocurrió un error al consultar el pago.\n\n" +
                        error.message
                    );

                });

        });

    });

});