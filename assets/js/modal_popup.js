

document.addEventListener("DOMContentLoaded", function () {


    // ==========================
    // MODALES DEL SISTEMA
    // ==========================
    configurarModal(
        "btnNuevoPais",
        "modalPais",
        "cerrarPais",
        "cancelarPais"
    );

    configurarModal(
        "btnNuevoDepartamento",
        "modalDepartamento",
        "cerrarDepartamento",
        "cancelarDepartamento"
    );

    configurarModal(
        "btnNuevaCiudad",
        "modalCiudad",
        "cerrarCiudad",
        "cancelarCiudad"
    );
    
// ==========================
// TABLAS MAESTRAS
// ==========================

configurarModal(
    "btnNuevoTipoDocumento",
    "modalNuevoTipoDocumento",
    "cerrarNuevoTipoDocumento",
    "cancelarNuevoTipoDocumento"
);

configurarModal(
    "btnNuevoEstadoCivil",
    "modalNuevoEstadoCivil",
    "cerrarNuevoEstadoCivil",
    "cancelarNuevoEstadoCivil"
);

configurarModal(
    "btnNuevaOcupacion",
    "modalNuevaOcupacion",
    "cerrarNuevaOcupacion",
    "cancelarNuevaOcupacion"
);

configurarModal(
    "btnNuevoGenero",
    "modalNuevoGenero",
    "cerrarNuevoGenero",
    "cancelarNuevoGenero"
);

    // MODAL GRUPOS

    configurarModal(
        "btnNuevoTipo",
        "modalGrupo",
        "cerrarGrupo",
        "cancelarGrupo"
    );

    configurarModal(
    "btnNuevaUnidad",
    "modalUnidad",
    "cerrarUnidad",
    "cancelarUnidad"
    );

    // ==========================
    // MENSAJES DEL SISTEMA
    // ==========================
    mostrarMensaje();

});


/*=====================================================
=            CONFIGURAR MODALES DEL SISTEMA            =
=====================================================*/

function configurarModal(idBoton, idModal, idCerrar, idCancelar) {
    const boton = document.getElementById(idBoton);
    const modal = document.getElementById(idModal);
    const cerrar = document.getElementById(idCerrar);
    const cancelar = document.getElementById(idCancelar);

    if (!boton || !modal) {
        return;
    }

    // Abrir modal
    boton.addEventListener("click", function () {
        modal.style.display = "block";
    });

    // Cerrar con la X
    if (cerrar) {
        cerrar.addEventListener("click", function () {
            cerrarModal(modal);
        });
    }

    // Botón Cancelar
    if (cancelar) {
        cancelar.addEventListener("click", function () {
            const formulario = modal.querySelector("form");
            if (formulario) {
                formulario.reset();
            }
            cerrarModal(modal);
        });
    }

    // Cerrar haciendo clic fuera
    window.addEventListener("click", function (event) {
        if (event.target === modal) {
            cerrarModal(modal);
        }
    });
}


/*=====================================================
=                  MODAL MENSAJES                      =
=====================================================*/

function mostrarMensaje() {

    const modal = document.getElementById("modalMensaje");

    if (!modal) return;

    const titulo = document.getElementById("tituloMensaje");
    const texto = document.getElementById("textoMensaje");
    const btnCerrar = document.getElementById("btnCerrarMensaje");
    const parametros = new URLSearchParams(window.location.search);
    const tipo = parametros.get("tipo");
    const mensaje = parametros.get("texto");

    if (!tipo || !mensaje) {
        return;
    }

    switch (tipo) {

        case "success":
            titulo.innerHTML = "✅ Éxito";
            break;

        case "warning":
            titulo.innerHTML = "⚠ Advertencia";
            break;

        case "error":
            titulo.innerHTML = "❌ Error";
            break;

        case "info":
            titulo.innerHTML = "ℹ Información";
            break;

        default:
            titulo.innerHTML = "Mensaje";
    }

    texto.innerHTML = mensaje;

    modal.style.display = "block";

    btnCerrar.addEventListener("click", function () {
        cerrarModal(modal);
        // Elimina los parámetros de la URL
        history.replaceState({}, "", window.location.pathname);
    });
}


/*=====================================================
=            CERRAR CUALQUIER MODAL                    =
=====================================================*/

function cerrarModal(modal) {
    modal.style.display = "none";
}


/*=====================================================
=              EDITAR UNIDAD
=====================================================*/

document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("modalUnidad");
    document.querySelectorAll(".btnEditarUnidad").forEach(function (boton) {
        boton.addEventListener("click", function () {
            // Solo para probar
            modal.style.display = "block";
        });
    });
});


// =====================================================
// MODAL PERSONA
// =====================================================

const btnNuevaPersona = document.getElementById("btnNuevaPersona");
const modalPersona = document.getElementById("modalPersona");
const cerrarPersona = document.getElementById("cerrarPersona");
const cancelarPersona = document.getElementById("cancelarPersona");


// Abrir modal
if (btnNuevaPersona && modalPersona) {
    btnNuevaPersona.addEventListener("click", function () {
        modalPersona.style.display = "flex";
    });
}

// Cerrar con X
if (cerrarPersona && modalPersona) {
    cerrarPersona.addEventListener("click", function () {
        modalPersona.style.display = "none";
    });
}

// Cerrar con Cancelar
if (cancelarPersona && modalPersona) {
    cancelarPersona.addEventListener("click", function () {
        modalPersona.style.display = "none";
    });
}

// Cerrar haciendo clic fuera del contenido
if (modalPersona) {
    modalPersona.addEventListener("click", function (e) {
        if (e.target === modalPersona) {
            modalPersona.style.display = "none";
        }
    });
}


// ==========================================================
// MODAL NUEVO TIPO DE DOCUMENTO
// ==========================================================

const btnNuevoTipoDocumento = document.querySelector(".btnNuevoTipoDocumento");
const modalNuevoTipoDocumento = document.getElementById("modalNuevoTipoDocumento");
const cerrarNuevoTipoDocumento = document.getElementById("cerrarNuevoTipoDocumento");
const cancelarNuevoTipoDocumento = document.getElementById("cancelarNuevoTipoDocumento");

if (btnNuevoTipoDocumento && modalNuevoTipoDocumento) {

    btnNuevoTipoDocumento.addEventListener("click", function () {
        modalNuevoTipoDocumento.style.display = "flex";
    });

}

if (cerrarNuevoTipoDocumento) {

    cerrarNuevoTipoDocumento.addEventListener("click", function () {
        modalNuevoTipoDocumento.style.display = "none";
    });

}

if (cancelarNuevoTipoDocumento) {

    cancelarNuevoTipoDocumento.addEventListener("click", function () {
        modalNuevoTipoDocumento.style.display = "none";
    });

}


// ==========================================================
// MODAL NUEVO ESTADO CIVIL
// ==========================================================

const btnNuevoEstadoCivil = document.querySelector(".btnNuevoEstadoCivil");
const modalNuevoEstadoCivil = document.getElementById("modalNuevoEstadoCivil");
const cerrarNuevoEstadoCivil = document.getElementById("cerrarNuevoEstadoCivil");
const cancelarNuevoEstadoCivil = document.getElementById("cancelarNuevoEstadoCivil");

if (btnNuevoEstadoCivil && modalNuevoEstadoCivil) {

    btnNuevoEstadoCivil.addEventListener("click", function () {
        modalNuevoEstadoCivil.style.display = "flex";
    });

}

if (cerrarNuevoEstadoCivil) {

    cerrarNuevoEstadoCivil.addEventListener("click", function () {
        modalNuevoEstadoCivil.style.display = "none";
    });

}

if (cancelarNuevoEstadoCivil) {

    cancelarNuevoEstadoCivil.addEventListener("click", function () {
        modalNuevoEstadoCivil.style.display = "none";
    });

}


// ==========================================================
// MODAL NUEVA OCUPACIÓN
// ==========================================================

const btnNuevaOcupacion = document.querySelector(".btnNuevaOcupacion");
const modalNuevaOcupacion = document.getElementById("modalNuevaOcupacion");
const cerrarNuevaOcupacion = document.getElementById("cerrarNuevaOcupacion");
const cancelarNuevaOcupacion = document.getElementById("cancelarNuevaOcupacion");

if (btnNuevaOcupacion && modalNuevaOcupacion) {

    btnNuevaOcupacion.addEventListener("click", function () {
        modalNuevaOcupacion.style.display = "flex";
    });

}

if (cerrarNuevaOcupacion) {

    cerrarNuevaOcupacion.addEventListener("click", function () {
        modalNuevaOcupacion.style.display = "none";
    });

}

if (cancelarNuevaOcupacion) {

    cancelarNuevaOcupacion.addEventListener("click", function () {
        modalNuevaOcupacion.style.display = "none";
    });

}


// ==========================================================
// MODAL NUEVO GÉNERO
// ==========================================================

const btnNuevoGenero = document.querySelector(".btnNuevoGenero");
const modalNuevoGenero = document.getElementById("modalNuevoGenero");
const cerrarNuevoGenero = document.getElementById("cerrarNuevoGenero");
const cancelarNuevoGenero = document.getElementById("cancelarNuevoGenero");

if (btnNuevoGenero && modalNuevoGenero) {

    btnNuevoGenero.addEventListener("click", function () {
        modalNuevoGenero.style.display = "flex";
    });

}

if (cerrarNuevoGenero) {

    cerrarNuevoGenero.addEventListener("click", function () {
        modalNuevoGenero.style.display = "none";
    });

}

if (cancelarNuevoGenero) {

    cancelarNuevoGenero.addEventListener("click", function () {
        modalNuevoGenero.style.display = "none";
    });

}


// ==========================================================
// EDITAR TIPO DE DOCUMENTO
// ==========================================================
const modalEditarTipoDocumento = document.getElementById("modalEditarTipoDocumento");
const cerrarEditarTipoDocumento = document.getElementById("cerrarEditarTipoDocumento");
const cancelarEditarTipoDocumento = document.getElementById("cancelarEditarTipoDocumento");

document
    .querySelectorAll(".btnEditarTipoDocumento")
    .forEach(function (boton) {

        boton.addEventListener("click", function () {
            const id = this.dataset.id;

            fetch(
                "../actions/obtener_tipo_documento.php?id=" +
                encodeURIComponent(id)
            )

            .then(response => response.json())

            .then(resultado => {

                if (!resultado.success) {
                    alert(resultado.mensaje);
                    return;
                }

                const tipoDocumento = resultado.data;

                // ID
                document.getElementById(
                    "editar_id_tipo_documento"
                ).value =
                    tipoDocumento.id_tipo_documento;

                // CÓDIGO
                document.getElementById(
                    "editar_codigo_tipo_documento"
                ).value =
                    tipoDocumento.codigo;

                // NOMBRE
                document.getElementById(
                    "editar_nombre_tipo_documento"
                ).value =
                    tipoDocumento.nombre;

                // ESTADO
                document.getElementById(
                    "editar_estado_tipo_documento"
                ).value =
                    tipoDocumento.estado;


                // ABRIR MODAL
                modalEditarTipoDocumento.style.display =
                    "block";
            })

            .catch(error => {
                console.error(error);
                alert(
                    "No fue posible obtener los datos del tipo de documento."
                );
            });
        });
    });


// ==========================================================
// CERRAR CON X
// ==========================================================

if (cerrarEditarTipoDocumento) {
    cerrarEditarTipoDocumento.addEventListener(
        "click",
        function () {
            cerrarModal(
                modalEditarTipoDocumento
            );
        }
    );
}


// ==========================================================
// CANCELAR
// ==========================================================

if (cancelarEditarTipoDocumento) {
    cancelarEditarTipoDocumento.addEventListener(
        "click",
        function () {
            cerrarModal(
                modalEditarTipoDocumento
            );
        }
    );
}


// ==========================================================
// CERRAR AL HACER CLIC FUERA
// ==========================================================

if (modalEditarTipoDocumento) {
    window.addEventListener(
        "click",
        function (event) {
            if (
                event.target ===
                modalEditarTipoDocumento
            ) {
                cerrarModal(
                    modalEditarTipoDocumento
                );
            }
        }
    );
}



// ==========================================================
// EDITAR ESTADO CIVIL
// ==========================================================

const modalEditarEstadoCivil = document.getElementById("modalEditarEstadoCivil");
const cerrarEditarEstadoCivil = document.getElementById("cerrarEditarEstadoCivil");
const cancelarEditarEstadoCivil =  document.getElementById("cancelarEditarEstadoCivil");

document.querySelectorAll(".btnEditarEstadoCivil").forEach(function (boton) {

    boton.addEventListener("click", function () {

        const id = this.dataset.id;

        fetch(
            "../actions/obtener_estado_civil.php?id=" + encodeURIComponent(id)
        )

        .then(response => response.json())

        .then(resultado => {

            if (!resultado.success) {
                alert(resultado.mensaje);
                return;
            }

            const estadoCivil = resultado.data;

            document.getElementById(
                "editar_id_estado_civil"
            ).value = estadoCivil.id_estado_civil;

            document.getElementById(
                "editar_nombre_estado_civil"
            ).value = estadoCivil.nombre;

            document.getElementById(
                "editar_estado_estado_civil"
            ).value = estadoCivil.estado;

            modalEditarEstadoCivil.style.display = "block";
        })
        .catch(error => {
            console.error(error);
            alert(
                "No fue posible obtener los datos del estado civil."
            );
        });
    });
});


// Cerrar con X
if (cerrarEditarEstadoCivil) {
    cerrarEditarEstadoCivil.addEventListener("click", function () {
        cerrarModal(modalEditarEstadoCivil);
    });
}

// Cancelar

if (cancelarEditarEstadoCivil) {
    cancelarEditarEstadoCivil.addEventListener("click", function () {
        cerrarModal(modalEditarEstadoCivil);
    });
}


// Cerrar haciendo clic fuera
if (modalEditarEstadoCivil) {
    window.addEventListener("click", function (event) {
        if (event.target === modalEditarEstadoCivil) {
            cerrarModal(modalEditarEstadoCivil);
        }
    });

}

// ==========================================================
// EDITAR OCUPACIÓN
// ==========================================================

const modalEditarOcupacion = document.getElementById("modalEditarOcupacion");
const cerrarEditarOcupacion = document.getElementById("cerrarEditarOcupacion");
const cancelarEditarOcupacion = document.getElementById("cancelarEditarOcupacion");


document.querySelectorAll(".btnEditarOcupacion").forEach(function (boton) {

    boton.addEventListener("click", function () {

        const id = this.dataset.id;

        fetch(
            "../actions/obtener_ocupacion.php?id=" +
            encodeURIComponent(id)
        )

        .then(response => response.json())

        .then(resultado => {

            if (!resultado.success) {
                alert(resultado.mensaje);
                return;
            }

            const ocupacion = resultado.data;

            document.getElementById(
                "editar_id_ocupacion"
            ).value = ocupacion.id_ocupacion;

            document.getElementById(
                "editar_nombre_ocupacion"
            ).value = ocupacion.nombre;

            document.getElementById(
                "editar_estado_ocupacion"
            ).value = ocupacion.estado;

            modalEditarOcupacion.style.display = "block";

        })

        .catch(error => {
            console.error(error);
            alert(
                "No fue posible obtener los datos de la ocupación."
            );
        });
    });
});


// Cerrar con X

if (cerrarEditarOcupacion) {
    cerrarEditarOcupacion.addEventListener("click", function () {
        cerrarModal(modalEditarOcupacion);
    });
}

// Cancelar

if (cancelarEditarOcupacion) {
    cancelarEditarOcupacion.addEventListener("click", function () {
        cerrarModal(modalEditarOcupacion);
    });
}

// Cerrar haciendo clic fuera

if (modalEditarOcupacion) {
    window.addEventListener("click", function (event) {
        if (event.target === modalEditarOcupacion) {
            cerrarModal(modalEditarOcupacion);
        }
    });
}

// ==========================================================
// EDITAR GÉNERO
// ==========================================================

const modalEditarGenero = document.getElementById("modalEditarGenero");
const cerrarEditarGenero = document.getElementById("cerrarEditarGenero");
const cancelarEditarGenero = document.getElementById("cancelarEditarGenero");


document.querySelectorAll(".btnEditarGenero").forEach(function (boton) {

    boton.addEventListener("click", function () {

        const id = this.dataset.id;

        fetch(
            "../actions/obtener_genero.php?id=" +
            encodeURIComponent(id)
        )

        .then(response => response.json())

        .then(resultado => {

            if (!resultado.success) {
                alert(resultado.mensaje);
                return;
            }

            const genero = resultado.data;

            document.getElementById(
                "editar_id_genero"
            ).value = genero.id_genero;

            document.getElementById(
                "editar_nombre_genero"
            ).value = genero.nombre;

            document.getElementById(
                "editar_estado_genero"
            ).value = genero.estado;

            modalEditarGenero.style.display = "block";
        })

        .catch(error => {
            console.error(error);
            alert(
                "No fue posible obtener los datos del género."
            );
        });
    });
});


// Cerrar con X

if (cerrarEditarGenero) {
    cerrarEditarGenero.addEventListener("click", function () {
        cerrarModal(modalEditarGenero);
    });
}


// Cancelar

if (cancelarEditarGenero) {
    cancelarEditarGenero.addEventListener("click", function () {
        cerrarModal(modalEditarGenero);
    });
}


// Cerrar haciendo clic fuera

if (modalEditarGenero) {
    window.addEventListener("click", function (event) {
        if (event.target === modalEditarGenero) {
            cerrarModal(modalEditarGenero);
        }
    });
}