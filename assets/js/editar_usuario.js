document.addEventListener("DOMContentLoaded", function () {

    // ==========================================================
    // ELEMENTOS
    // ==========================================================

    const modal = document.getElementById("modalEditarUsuario");

    const btnCerrar = document.getElementById("cerrarEditarUsuario");

    const btnCancelar = document.getElementById("cancelarEditarUsuario");


    // ==========================================================
    // BOTONES EDITAR
    // ==========================================================

    const botonesEditar = document.querySelectorAll(".btnEditarUsuario");


    botonesEditar.forEach(function (boton) {

        boton.addEventListener("click", function () {

            const id = this.dataset.id;

            if (!id) {
                alert("No se encontró el ID del usuario.");
                return;
            }

            cargarUsuario(id);

        });

    });


    // ==========================================================
    // CARGAR USUARIO
    // ==========================================================

    function cargarUsuario(id) {

        fetch("editar_usuario.php?id=" + encodeURIComponent(id))

            .then(function (response) {

                if (!response.ok) {
                    throw new Error(
                        "Error HTTP: " + response.status
                    );
                }

                return response.json();

            })

            .then(function (data) {

                console.log("Respuesta editar usuario:", data);


                if (!data.success) {

                    alert(
                        data.mensaje ||
                        "No fue posible cargar el usuario."
                    );

                    return;
                }


                const usuario = data.usuario;


                // ==================================================
                // DATOS
                // ==================================================

                document.getElementById("editar_id").value =
                    usuario.id ?? "";


                document.getElementById("editar_nombres").value =
                    usuario.nombres ?? "";


                document.getElementById("editar_apellidos").value =
                    usuario.apellidos ?? "";


                document.getElementById("editar_id_tipo_documento").value =
                    usuario.id_tipo_documento ?? "";


                document.getElementById("editar_numero_documento").value =
                    usuario.numero_documento ?? "";


                document.getElementById("editar_correo").value =
                    usuario.correo ?? "";


                document.getElementById("editar_telefono").value =
                    usuario.telefono ?? "";


                document.getElementById("editar_celular").value =
                    usuario.celular ?? "";


                document.getElementById("editar_fecha_nacimiento").value =
                    usuario.fecha_nacimiento ?? "";


                document.getElementById("editar_id_genero").value =
                    usuario.id_genero ?? "";


                document.getElementById("editar_id_estado_civil").value =
                    usuario.id_estado_civil ?? "";


                document.getElementById("editar_id_ocupacion").value =
                    usuario.id_ocupacion ?? "";


                document.getElementById("editar_direccion").value =
                    usuario.direccion ?? "";


                document.getElementById("editar_estado").value =
                    usuario.estado ?? "1";


                // ==================================================
                // FOTO ACTUAL
                // ==================================================

                const fotoActual =
                    document.getElementById("editar_foto_actual");


                if (usuario.foto) {

                    fotoActual.innerHTML = `
                        <div>
                            <p><strong>Foto actual:</strong></p>

                            <img
                                src="../${usuario.foto}"
                                class="foto-persona-listado"
                                alt="Foto actual">
                        </div>
                    `;

                } else {

                    fotoActual.innerHTML = `
                        <span class="sin-foto">
                            El usuario no tiene foto.
                        </span>
                    `;

                }


                // ==================================================
                // MOSTRAR MODAL
                // ==================================================

                modal.style.display = "flex";

            })

            .catch(function (error) {

                console.error(
                    "Error cargando usuario:",
                    error
                );

                alert(
                    "Ocurrió un error al cargar los datos del usuario."
                );

            });

    }


    // ==========================================================
    // CERRAR
    // ==========================================================

    if (btnCerrar) {

        btnCerrar.addEventListener("click", function () {

            modal.style.display = "none";

        });

    }


    // ==========================================================
    // CANCELAR
    // ==========================================================

    if (btnCancelar) {

        btnCancelar.addEventListener("click", function () {

            modal.style.display = "none";

        });

    }


    // ==========================================================
    // CERRAR AL HACER CLICK FUERA
    // ==========================================================

    window.addEventListener("click", function (event) {

        if (event.target === modal) {

            modal.style.display = "none";

        }

    });

});