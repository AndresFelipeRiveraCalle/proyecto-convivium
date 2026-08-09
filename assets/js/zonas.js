/**
 * ============================================================================================
 * MÓDULO: Zonas Comunes - Convivium
 * ARCHIVO: zonas.js
 * DESCRIPCIÓN: Archivo JavaScript para manejo de eventos de los elemenos de las Zonas comunes.
 * AUTOR: Andrés Felipe Rivera Calle
 * FECHA: 2026-08-08
 * ============================================================================================
 */

// Identificar todos los elementos que contienen la clase .btn-eliminar
const botonesEliminar = document.querySelectorAll('.btn-eliminar');

// Ejecutar este bloque de código por cada elemento identificado
botonesEliminar.forEach((boton, indice) => {

    // Escuchar la acción de cada elemento con el evento 'click'
    boton.addEventListener('click', () => {
        const confirmar = confirm('¿Está seguro de que desea eliminar esta zona? Esta acción no se puede deshacer.');

        if (!confirmar) {
            // Detener el flujo del elemento que contiene la clase .btn-eliminar
            event.preventDefault(); 
        }
    })
})

// Identificar todos los elementos que contienen la clase .mensaje
const mensaje = document.querySelector('.mensaje');

// Comprovar que exista el elemento
if (mensaje) {

    // Ejecutar esta instrucción en 5 segundos
    setTimeout(() => {

        // Agregar la clase ocultar al elemento para que entre el CSS 
        mensaje.classList.add('ocultar');
    }, 5000);
}