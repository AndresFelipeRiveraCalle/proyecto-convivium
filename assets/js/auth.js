/**
 * auth.js - Funcionalidad JavaScript para pantallas de autenticación
 * Funciones: Toggle password, ocultar mensajes automáticos
 * @author Andrés Felipe Rivera Calle
 * @version 1.0
 */

// Ejecuta cuando el DOM ya cargó todo el HTML
document.addEventListener('DOMContentLoaded', () => {
    
    /**
     * 1. LIMPIAR URL: Quita ?registro=1 de la barra sin recargar
     * Evita que al dar F5 vuelva a salir "Cuenta creada exitosamente"
     */
    if (window.location.search.includes('registro')) {
        window.history.replaceState(null, '', window.location.pathname);
    }

    /**
     * 2. OCULTAR MENSAJES AUTOMÁTICAMENTE
     * Después de 3 segundos, desaparece el mensaje de éxito o error
     */
    setTimeout(() => {
        const mensajeExito = document.getElementById("mensaje-exito");
        if (mensajeExito) {
            mensajeExito.style.transition = "opacity 0.5s";
            mensajeExito.style.opacity = "0";
            setTimeout(() => mensajeExito.remove(), 500); // Lo elimina del DOM
        }
    }, 3000);

    setTimeout(() => {
        const mensajeError = document.getElementById("mensaje-error");
        if (mensajeError) {
            mensajeError.style.transition = "opacity 0.5s";
            mensajeError.style.opacity = "0";
            setTimeout(() => mensajeError.remove(), 500);
        }
    }, 3000);

    /**
     * 3. TOGGLE PASSWORD: Cambiar entre ver/ocultar contraseña
     */
    const togglePass = document.getElementById('togglePass');
    const passwordInput = document.getElementById('password');
    
    // Solo ejecuta si existe el botón en la página
    if (togglePass && passwordInput) {
        togglePass.addEventListener('click', () => {
            // Si es password, cambia a text. Si es text, cambia a password
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Cambia el ícono: 👁️ cerrado, 🙈 abierto
            togglePass.textContent = type === 'password' ? '👁️' : '🙈';
            
            // Cambia el title para accesibilidad
            togglePass.setAttribute('title', type === 'password' ? 'Mostrar contraseña' : 'Ocultar contraseña');
        });
    }
});