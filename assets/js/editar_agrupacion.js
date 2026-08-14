document.addEventListener("DOMContentLoaded", function () {

    const botonesEditar = document.querySelectorAll(".btnEditarAgrupacion");
        botonesEditar.forEach(function (boton) {
        boton.addEventListener("click", function () {

            const id = this.dataset.id;

            if (!id) {
                console.error("No se encontró el ID de la agrupación.");
                return;
            }

            fetch("../actions/obtener_agrupacion.php?id=" + id)
                .then(response => {

                    if (!response.ok) {
                        throw new Error(
                            "Error HTTP: " + response.status
                        );
                    }

                    return response.json();
                })
                .then(data => {

                    if (data.error) {
                        alert(data.error);
                        return;
                    }

                    document.getElementById("editar_id_agrupacion").value =data.id_agrupacion;
                    document.getElementById("editar_id_tipo_agrupacion").value =data.id_tipo_agrupacion;
                    document.getElementById("editar_nombre").value =data.nombre;
                    document.getElementById("editar_descripcion").value =data.descripcion ?? "";
                    document.getElementById("modalEditarAgrupacion").style.display ="flex";
                })
                .catch(error => {
                    console.error("Error:", error);
                });
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
