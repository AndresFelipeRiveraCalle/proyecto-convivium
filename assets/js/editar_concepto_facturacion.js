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

        fetch(
            BASE_URL +
            "actions/obtener_concepto_facturacion.php?id=" +
            encodeURIComponent(id)
        )

        .then(response => {

            if (!response.ok) {

                throw new Error(
                    "Error HTTP: " + response.status
                );
            }

            return response.json();

        })

        .then(data => {

            if (!data.success) {

                alert(
                    data.message ??
                    "No fue posible consultar el concepto."
                );

                return;
            }


            const concepto = data.concepto;


            // ==================================================
            // CARGAR DATOS
            // ==================================================

            document.getElementById(
                "editar_id_concepto"
            ).value =
                concepto.id_concepto ?? "";


            document.getElementById(
                "editar_nombre"
            ).value =
                concepto.nombre ?? "";


            document.getElementById(
                "editar_descripcion"
            ).value =
                concepto.descripcion ?? "";


            document.getElementById(
                "editar_tipo_calculo"
            ).value =
                concepto.tipo_calculo ?? "FIJO";


            document.getElementById(
                "editar_id_tipo_obligacion"
            ).value =
                concepto.id_tipo_obligacion ?? "";


            document.getElementById(
                "editar_id_cuenta_contable"
            ).value =
                concepto.id_cuenta_contable ?? "";


            document.getElementById(
                "editar_obligatorio"
            ).value =
                concepto.obligatorio ?? 0;


            document.getElementById(
                "editar_estado"
            ).value =
                concepto.estado ?? 1;


            // ==================================================
            // MOSTRAR MODAL
            // ==================================================

            modalEditarConcepto.style.display =
                "flex";

        })

        .catch(error => {

            console.error(
                "Error al consultar concepto:",
                error
            );

            alert(
                "No fue posible consultar el concepto de facturación."
            );

        });

    });

});


// ==========================================================
// CERRAR MODAL
// ==========================================================

function cerrarModalConcepto()
{
    if (modalEditarConcepto) {

        modalEditarConcepto.style.display =
            "none";
    }
}


// ==========================================================
// CERRAR CON X
// ==========================================================

if (cerrarModalEditarConcepto) {

    cerrarModalEditarConcepto.addEventListener(
        "click",
        cerrarModalConcepto
    );
}


// ==========================================================
// CERRAR CON CANCELAR
// ==========================================================

if (cancelarModalEditarConcepto) {

    cancelarModalEditarConcepto.addEventListener(
        "click",
        cerrarModalConcepto
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

                cerrarModalConcepto();
            }

        }
    );
}