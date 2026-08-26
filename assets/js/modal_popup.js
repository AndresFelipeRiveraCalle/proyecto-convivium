

document.addEventListener("DOMContentLoaded", function () {


    // ==========================// ==========================
    //  TABLAS MAESTRAS - MODALES DEL SISTEMA
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
// MODAL NUEVO USUARIO
// =====================================================
const btnNuevoUsuario = document.getElementById("btnNuevoUsuario");
const modalUsuario = document.getElementById("modalUsuario");
const cerrarUsuario = document.getElementById("cerrarUsuario");
const cancelarUsuario = document.getElementById("cancelarUsuario");

// =====================================================
// ABRIR MODAL
// =====================================================
if (btnNuevoUsuario && modalUsuario) {
    btnNuevoUsuario.addEventListener("click", function () {
        modalUsuario.style.display = "flex";
    });
}

// =====================================================
// CERRAR CON X
// =====================================================
if (cerrarUsuario && modalUsuario) {
    cerrarUsuario.addEventListener("click", function () {
        modalUsuario.style.display = "none";
    });
}

// =====================================================
// CERRAR CON CANCELAR
// =====================================================
if (cancelarUsuario && modalUsuario) {
    cancelarUsuario.addEventListener("click", function () {
        modalUsuario.style.display = "none";
    });
}

// =====================================================
// CERRAR HACIENDO CLIC FUERA
// =====================================================
if (modalUsuario) {
    modalUsuario.addEventListener("click", function (e) {
        if (e.target === modalUsuario) {
            modalUsuario.style.display = "none";
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
/*=========================================================
            EDITAR PAÍS
=========================================================*/

document.addEventListener("DOMContentLoaded", function () {

    const modal = document.getElementById("modalEditarPais");
    const cerrar = document.getElementById("cerrarEditarPais");
    const cancelar = document.getElementById("cancelarEditarPais");

    const idPais = document.getElementById("editar_id_pais");
    const nombrePais = document.getElementById("editar_nombre_pais");
    const activoPais = document.getElementById("editar_activo_pais");

    // Abrir modal
    document.querySelectorAll(".btnEditarPais").forEach(function (boton) {
        boton.addEventListener("click", function () {
            idPais.value = this.dataset.id;
            nombrePais.value = this.dataset.nombre;
            activoPais.value = this.dataset.activo;
            modal.style.display = "flex";
        });
    });

    // Cerrar
    // Opción A: Usando ?. (La forma más corta en JS moderno)
document.getElementById('cerrarModalAgrupacion')?.addEventListener('click', function() {
    // Código para cerrar el modal
});

// Opción B: Usando validación tradicional
const btnCerrar = document.getElementById('cerrarModalAgrupacion');
if (btnCerrar) {
    btnCerrar.addEventListener('click', function() {
        // Código para cerrar el modal
    });
}
    
    /*cerrar.addEventListener("click", function () {
        modal.style.display = "none";
    });

    cancelar.addEventListener("click", function () {
        modal.style.display = "none";
    });

    // Cerrar haciendo clic fuera
    window.addEventListener("click", function (event) {
        if (event.target === modal) {
            modal.style.display = "none";
        }
    });*/
});


// ==========================================================
// EDITAR DEPARTAMENTO
// ==========================================================

const modalEditarDepartamento = document.getElementById("modalEditarDepartamento");
const cerrarEditarDepartamento = document.getElementById("cerrarEditarDepartamento");
const cancelarEditarDepartamento = document.getElementById("cancelarEditarDepartamento");

document
    .querySelectorAll(".btnEditarDepartamento")
    .forEach(function (boton) {

        boton.addEventListener("click", function () {

            const id = this.dataset.id;

            fetch(
                "../actions/obtener_departamento.php?id=" +
                encodeURIComponent(id)
            )

            .then(response => response.json())

            .then(resultado => {

                if (!resultado.success) {
                    alert(
                        resultado.mensaje
                    );
                    return;
                }

                const departamento =
                    resultado.data;

                // ID
                document.getElementById(
                    "editar_id_departamento"
                ).value =
                    departamento.id_departamento;

                // PAÍS
            document.getElementById(
                "editar_id_pais"
            ).value = departamento.id_pais;

            document.getElementById(
                "editar_pais"
            ).value = departamento.nombre_pais;

                // NOMBRE
                document.getElementById(
                    "editar_nombre_departamento"
                ).value =
                    departamento.nombre;

                // CÓDIGO
                document.getElementById(
                    "editar_codigo_departamento"
                ).value =
                    departamento.codigo ?? "";

                // ESTADO
                document.getElementById(
                    "editar_estado_departamento"
                ).value =
                    departamento.Activo;

                // ABRIR MODAL
                modalEditarDepartamento.style.display =
                    "block";
            })

            .catch(error => {
                console.error(error);
                alert(
                    "No fue posible obtener los datos del departamento."
                );
            });

        });
    });


// ==========================================================
// CERRAR CON X
// ==========================================================

if (cerrarEditarDepartamento) {
    cerrarEditarDepartamento.addEventListener(
        "click",
        function () {
            cerrarModal(
                modalEditarDepartamento
            );
        }
    );
}


// ==========================================================
// CANCELAR
// ==========================================================

if (cancelarEditarDepartamento) {
    cancelarEditarDepartamento.addEventListener(
        "click",
        function () {
            cerrarModal(
                modalEditarDepartamento
            );
        }
    );
}


// ==========================================================
// CERRAR HACIENDO CLIC FUERA
// ==========================================================

if (modalEditarDepartamento) {
    window.addEventListener(
        "click",
        function (event) {
            if (
                event.target ===
                modalEditarDepartamento
            ) {
                cerrarModal(
                    modalEditarDepartamento
                );
            }
        }
    );
}


// ==========================================================
// EDITAR CIUDAD
// ==========================================================

const modalEditarCiudad = document.getElementById("modalEditarCiudad");
const cerrarEditarCiudad = document.getElementById("cerrarEditarCiudad");
const cancelarEditarCiudad = document.getElementById("cancelarEditarCiudad");

document
    .querySelectorAll(".btnEditarCiudad")
    .forEach(function (boton) {

        boton.addEventListener("click", function () {

            const id = this.dataset.id;


            fetch(
                "../actions/obtener_ciudad.php?id=" +
                encodeURIComponent(id)
            )

            .then(response => response.json())

            .then(resultado => {
                if (!resultado.success) {
                    alert(
                        resultado.mensaje
                    );
                    return;
                }

                const ciudad =
                    resultado.data;

                // ID CIUDAD
                document.getElementById(
                    "editar_id_ciudad"
                ).value =
                    ciudad.id_ciudad;

                // ID DEPARTAMENTO
                document.getElementById(
                    "editar_id_departamento_ciudad"
                ).value =
                    ciudad.id_departamento;

                // DEPARTAMENTO
                document.getElementById(
                    "editar_departamento_ciudad"
                ).value =
                    ciudad.nombre_departamento;

                // CIUDAD
                document.getElementById(
                    "editar_nombre_ciudad"
                ).value =
                    ciudad.nombre;

                // CÓDIGO DANE
                document.getElementById(
                    "editar_codigo_dane"
                ).value =
                    ciudad.codigo_dane ?? "";

                // ESTADO
                document.getElementById(
                    "editar_estado_ciudad"
                ).value =
                    ciudad.Activo;

                // ABRIR MODAL
                modalEditarCiudad.style.display =
                    "block";
            })

            .catch(error => {
                console.error(error);
                alert(
                    "No fue posible obtener los datos de la ciudad."
                );
            });
        });
    });


// ==========================================================
// CERRAR CON X
// ==========================================================

if (cerrarEditarCiudad) {
    cerrarEditarCiudad.addEventListener(
        "click",
        function () {
            cerrarModal(
                modalEditarCiudad
            );
        }
    );
}


// ==========================================================
// CANCELAR
// ==========================================================

if (cancelarEditarCiudad) {
    cancelarEditarCiudad.addEventListener(
        "click",
        function () {
            cerrarModal(
                modalEditarCiudad
            );
        }
    );
}


// ==========================================================
// CERRAR HACIENDO CLIC FUERA
// ==========================================================

if (modalEditarCiudad) {
    window.addEventListener(
        "click",
        function (event) {
            if (
                event.target ===
                modalEditarCiudad
            ) {
                cerrarModal(
                    modalEditarCiudad
                );
            }
        }
    );
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





function cerrarModalMensaje() {

    const modal = document.getElementById("modalMensaje");

    if (modal) {
        modal.style.display = "none";
    }
}


document.addEventListener("DOMContentLoaded", function () {

    const btnCerrarMensaje =
        document.getElementById("btnCerrarMensaje");

    if (btnCerrarMensaje) {

        btnCerrarMensaje.addEventListener(
            "click",
            cerrarModalMensaje
        );

    }


    // ======================================================
    // MENSAJE ENVIADO DESDE PHP
    // ======================================================

    const parametros = new URLSearchParams(
        window.location.search
    );

    const tipo = parametros.get("tipo");
    const mensaje = parametros.get("mensaje");

    if (tipo && mensaje) {

        mostrarMensaje(
            tipo,
            mensaje
        );

    }

});


    // ======================================================
    // MODAL PAGOS
    // ======================================================

document.addEventListener("DOMContentLoaded", function () {

    const btnNuevoPago = document.getElementById("btnNuevoPago");
    const modalPago = document.getElementById("modalPago");
    const cerrarModalPago = document.getElementById("cerrarModalPago");

    if (btnNuevoPago && modalPago) {

        btnNuevoPago.addEventListener("click", function () {

            modalPago.style.display = "flex";

        });

    }

    if (cerrarModalPago && modalPago) {

        cerrarModalPago.addEventListener("click", function () {

            modalPago.style.display = "none";

        });

    }

    // Cerrar haciendo clic fuera del modal
    if (modalPago) {

        modalPago.addEventListener("click", function (e) {

            if (e.target === modalPago) {
                modalPago.style.display = "none";
            }

        });

    }

});