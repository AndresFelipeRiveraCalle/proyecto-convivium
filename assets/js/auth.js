/**
 * CONVIVIUM - Módulo de Autenticación
 * Archivo: auth.js
 * Ubicación: assets/js/auth.js
 * Funciones: Limpieza de URL, desvanecimiento de alertas del sistema y toggle de visibilidad de contraseñas.
 * @author Andrés Felipe Rivera Calle
 * @version 1.1
 */

document.addEventListener('DOMContentLoaded', () => {
    
    /**
     * 1. LIMPIAR URL (Prevención de reenvíos por F5)
     * Detecta si la barra de direcciones contiene la palabra 'registro' o 'exito'
     * y limpia los parámetros GET para que no se repitan las alertas al recargar.
     */
    if (window.location.search.includes('registro') || window.location.search.includes('exito')) {
        window.history.replaceState(null, '', window.location.pathname);
    }

    /**
     * 2. OCULTAR MENSAJES AUTOMÁTICAMENTE (Efecto Fade-out)
     * Función interna reutilizable para desvanecer y remover elementos del DOM.
     * Desaparece a los 3 segundos con una transición suave de 0.5 segundos.
     */
    const configurarDesvanecimiento = (idElemento) => {
        const elemento = document.getElementById(idElemento);
        if (elemento) {
            setTimeout(() => {
                elemento.style.transition = "opacity 0.5s ease";
                elemento.style.opacity = "0";
                setTimeout(() => elemento.remove(), 500); // Lo elimina físicamente del árbol DOM
            }, 3000);
        }
    };

    // Ejecutamos el control para ambos tipos de alertas operacionales
    configurarDesvanecimiento("mensaje-exito");
    configurarDesvanecimiento("mensaje-error");

    /**
     * 3. TOGGLE PASSWORD (Ver/Ocultar Contraseñas)
     * Función adaptada para soportar múltiples inputs de contraseña en la misma vista
     * (Aplica para el campo 'password' y 'confirmar' en el formulario de registro).
     */
    const asociarTogglePassword = (idInput, idBotonToggle) => {
        const inputPassword = document.getElementById(idInput);
        const botonToggle = document.getElementById(idBotonToggle);

        if (inputPassword && botonToggle) {
            botonToggle.addEventListener('click', () => {
                // Alterna el tipo de atributo entre text y password
                const tipoActual = inputPassword.getAttribute('type') === 'password' ? 'text' : 'password';
                inputPassword.setAttribute('type', tipoActual);
                
                // Actualiza el emoji del botón indicador
                botonToggle.textContent = tipoActual === 'password' ? '👁️' : '🙈';
                
                // Accesibilidad para lectores de pantalla
                botonToggle.setAttribute('title', tipoActual === 'password' ? 'Mostrar contraseña' : 'Ocultar contraseña');
            });
        }
    };

    // Ejecución de la lógica de visibilidad en los formularios correspondientes
    asociarTogglePassword('password', 'togglePass');         // Campo principal (Login y Registro)
    asociarTogglePassword('confirmar', 'toggleConfirmPass'); // Campo de verificación (Solo Registro)
});