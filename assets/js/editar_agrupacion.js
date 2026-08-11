
document.addEventListener("DOMContentLoaded", function () {

    const modal = document.getElementById("modalEditarAgrupacion");
    const cerrar = document.getElementById("cerrarModalEditarAgrupacion");
    const cancelar = document.getElementById("cancelarModalEditarAgrupacion");


    // ==========================================================
    // BOTONES EDITAR
    // ==========================================================

    document.querySelectorAll(".btnEditarAgrupacion").forEach(function (boton) {
        boton.addEventListener("click", function () {
            const id = this.dataset.id;

            // Consultar agrupación
            fetch(
                "<?= BASE_URL ?>actions/obtener_agrupacion.php?id=" + id
            )
                .then(response => response.json())

                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }

                    // Cargar información
                    document.getElementById("editar_id_agrupacion").value = data.id_agrupacion;
                    document.getElementById("editar_id_tipo_agrupacion").value = data.id_tipo_agrupacion;
                    document.getElementById("editar_nombre_agrupacion").value = data.nombre;
                    document.getElementById("editar_descripcion_agrupacion").value = data.descripcion ?? "";

                    // Abrir modal
                    modal.style.display = "block";

                })

                .catch(error => {
                    console.error(error);
                    alert(
                        "No fue posible cargar la agrupación."
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
    // CLICK FUERA
    // ==========================================================

    if (modal) {
        modal.addEventListener("click", function (e) {
            if (e.target === modal) {
                modal.style.display = "none";
            }
        });
    }
});