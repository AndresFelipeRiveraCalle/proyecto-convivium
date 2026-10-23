document.addEventListener('DOMContentLoaded', function() {
    // --- LÓGICA MODAL CREAR ---
    const modalCrear = document.getElementById("modalMantenimiento");
    const btnAbrirCrear = document.getElementById("btnAbrirModalMantenimiento");
    const spanCerrarCrear = document.querySelector(".close-modal");

    if(btnAbrirCrear && modalCrear) {
        btnAbrirCrear.addEventListener('click', function(e) {
            e.preventDefault(); 
            modalCrear.style.display = "block";
        });
    }

    if(spanCerrarCrear) {
        spanCerrarCrear.addEventListener('click', function() {
            modalCrear.style.display = "none";
        });
    }

    // --- LÓGICA MODAL EDITAR ---
    const modalEditar = document.getElementById("modalEditarMantenimiento");
    const spanCerrarEditar = document.querySelector(".close-modal-editar");
    const botonesEditar = document.querySelectorAll(".btn-abrir-editar");

    botonesEditar.forEach(boton => {
        boton.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Asignar los valores extraídos del botón (data-*) a los campos del formulario
            document.getElementById('edit_id').value = this.getAttribute('data-id');
            document.getElementById('edit_zona_id').value = this.getAttribute('data-zona');
            document.getElementById('edit_descripcion').value = this.getAttribute('data-desc');
            document.getElementById('edit_prioridad').value = this.getAttribute('data-prioridad');
            document.getElementById('edit_estado').value = this.getAttribute('data-estado');

            modalEditar.style.display = "block";
        });
    });

    if(spanCerrarEditar) {
        spanCerrarEditar.addEventListener('click', function() {
            modalEditar.style.display = "none";
        });
    }

    // --- CERRAR HACIENDO CLIC FUERA DE CUALQUIER MODAL ---
    window.addEventListener('click', function(event) {
        if (event.target === modalCrear) {
            modalCrear.style.display = "none";
        }
        if (event.target === modalEditar) {
            modalEditar.style.display = "none";
        }
    });
});