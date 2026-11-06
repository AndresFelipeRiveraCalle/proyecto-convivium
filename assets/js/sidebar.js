document.addEventListener("DOMContentLoaded", () => {
    const submenus = document.querySelectorAll(".submenu");

    // Marca el elemento correspondiente a la ruta actual sin cambiar los enlaces existentes.
    const rutaActual = window.location.pathname.replace(/\/+$/, "");

    document.querySelectorAll(".menu-principal a[href]").forEach(enlace => {
        try {
            const rutaEnlace = new URL(enlace.href, window.location.origin)
                .pathname
                .replace(/\/+$/, "");

            if (rutaEnlace === rutaActual) {
                enlace.classList.add("activo");

                const elemento = enlace.closest("li");
                if (elemento) {
                    elemento.classList.add("activo");
                }

                const submenuPadre = enlace.closest(".submenu");
                if (submenuPadre) {
                    submenuPadre.classList.add("activo");
                }

                enlace.setAttribute("aria-current", "page");
            }
        } catch (error) {
            // Un enlace no válido no debe impedir el funcionamiento del menú.
        }
    });

    submenus.forEach(submenu => {
        const titulo = submenu.querySelector(".submenu-titulo");

        if (!titulo) {
            return;
        }

        titulo.addEventListener("click", () => {
            // Cerrar los demás submenús antes de abrir el seleccionado.
            submenus.forEach(item => {
                if (item !== submenu) {
                    item.classList.remove("activo");
                }
            });

            submenu.classList.toggle("activo");
        });
    });
});