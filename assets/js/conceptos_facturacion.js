document.addEventListener("DOMContentLoaded", function () {

    // =====================================================
    // NUEVO CONCEPTO
    // =====================================================

    const btnNuevo = document.getElementById("btnNuevoConcepto");
    const modalNuevo = document.getElementById("modalNuevoConcepto");

    const cerrarNuevo = document.getElementById("cerrarNuevoConcepto");
    const cancelarNuevo = document.getElementById("cancelarNuevoConcepto");


    if (btnNuevo && modalNuevo) {

        btnNuevo.addEventListener("click", function () {

            modalNuevo.style.display = "flex";

        });

    }


    if (cerrarNuevo && modalNuevo) {

        cerrarNuevo.addEventListener("click", function () {

            modalNuevo.style.display = "none";

        });

    }


    if (cancelarNuevo && modalNuevo) {

        cancelarNuevo.addEventListener("click", function () {

            modalNuevo.style.display = "none";

        });

    }


    if (modalNuevo) {

        modalNuevo.addEventListener("click", function (e) {

            if (e.target === modalNuevo) {

                modalNuevo.style.display = "none";

            }

        });

    }



    // =====================================================
    // EDITAR CONCEPTO
    // =====================================================

    const modalEditar =
        document.getElementById("modalEditarConcepto");

    const cerrarEditar =
        document.getElementById("cerrarEditarConcepto");

    const cancelarEditar =
        document.getElementById("cancelarEditarConcepto");


    const botonesEditar =
        document.querySelectorAll(".btnEditarConcepto");


    botonesEditar.forEach(function (boton) {

        boton.addEventListener("click", function () {

            const id =
                boton.dataset.id;

            const nombre =
                boton.dataset.nombre;

            const descripcion =
                boton.dataset.descripcion;

            const tipoCalculo =
                boton.dataset.tipoCalculo;

            const cuenta =
                boton.dataset.cuenta;

            const obligatorio =
                boton.dataset.obligatorio;

            const estado =
                boton.dataset.estado;


            // ID

            document.getElementById(
                "editar_id_concepto"
            ).value = id;


            // NOMBRE

            document.getElementById(
                "editar_nombre_concepto"
            ).value = nombre;


            // DESCRIPCIÓN

            document.getElementById(
                "editar_descripcion_concepto"
            ).value = descripcion;


            // TIPO DE CÁLCULO

            document.getElementById(
                "editar_tipo_calculo"
            ).value = tipoCalculo;


            // CUENTA CONTABLE

            document.getElementById(
                "editar_cuenta_contable"
            ).value = cuenta;


            // OBLIGATORIO

            document.getElementById(
                "editar_obligatorio"
            ).checked = obligatorio == "1";


            // ESTADO

            document.getElementById(
                "editar_estado_concepto"
            ).value = estado;


            // ABRIR MODAL

            modalEditar.style.display = "flex";

        });

    });


    // =====================================================
    // CERRAR EDITAR
    // =====================================================

    if (cerrarEditar && modalEditar) {

        cerrarEditar.addEventListener("click", function () {

            modalEditar.style.display = "none";

        });

    }


    if (cancelarEditar && modalEditar) {

        cancelarEditar.addEventListener("click", function () {

            modalEditar.style.display = "none";

        });

    }


    if (modalEditar) {

        modalEditar.addEventListener("click", function (e) {

            if (e.target === modalEditar) {

                modalEditar.style.display = "none";

            }

        });

    }

});