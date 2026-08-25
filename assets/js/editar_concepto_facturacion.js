// ==========================================================
// EDITAR CONCEPTO DE FACTURACIÓN
// ==========================================================

const botonesEditarConcepto =
    document.querySelectorAll(".btnEditarConcepto");

const modalEditarConcepto =
    document.getElementById("modalEditarConcepto");

const cerrarModalEditarConcepto =
    document.getElementById("cerrarModalEditarConcepto");

const cancelarModalEditarConcepto =
    document.getElementById("cancelarModalEditarConcepto");


// ==========================================================
// ABRIR MODAL
// ==========================================================

botonesEditarConcepto.forEach(function (boton) {

    boton.addEventListener("click", function () {

        const id = this.dataset.id;

        console.log("Editando concepto:", id);

        fetch(
            BASE_URL +
            "configuracion/editar_concepto_facturacion.php?id=" +
            id
        )

        .then(response => response.json())

        .then(data => {

            console.log("Respuesta:", data);

            if (!data.success) {

                alert(data.mensaje);
                return;
            }

            const concepto = data.concepto;


            // ==================================================
            // CARGAR DATOS
            // ==================================================

            document.getElementById("editar_id_concepto").value =
                concepto.id_concepto;

            document.getElementById("editar_nombre").value =
                concepto.nombre;

            document.getElementById("editar_descripcion").value =
                concepto.descripcion ?? "";

            document.getElementById("editar_tipo_calculo").value =
                concepto.tipo_calculo;

            document.getElementById("editar_id_cuenta_contable").value =
                concepto.id_cuenta_contable ?? "";

            document.getElementById("editar_obligatorio").value =
                concepto.obligatorio;

            document.getElementById("editar_estado").value =
                concepto.estado;


            // ==================================================
            // MOSTRAR MODAL
            // ==================================================

            modalEditarConcepto.style.display = "flex";

        })

        .catch(error => {

            console.error(
                "Error al consultar concepto:",
                error
            );

            alert(
                "No fue posible consultar el concepto."
            );

        });

    });

});


// ==========================================================
// CERRAR CON X
// ==========================================================

if (cerrarModalEditarConcepto) {

    cerrarModalEditarConcepto.addEventListener(
        "click",
        function () {

            modalEditarConcepto.style.display = "none";

        }
    );

}


// ==========================================================
// CERRAR CON CANCELAR
// ==========================================================

if (cancelarModalEditarConcepto) {

    cancelarModalEditarConcepto.addEventListener(
        "click",
        function () {

            modalEditarConcepto.style.display = "none";

        }
    );

}


// ==========================================================
// CERRAR HACIENDO CLICK FUERA
// ==========================================================

if (modalEditarConcepto) {

    modalEditarConcepto.addEventListener(
        "click",
        function (e) {

            if (e.target === modalEditarConcepto) {

                modalEditarConcepto.style.display = "none";

            }

        }
    );

}