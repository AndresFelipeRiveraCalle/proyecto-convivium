document.addEventListener("DOMContentLoaded", () => {
    const submenus = document.querySelectorAll(".submenu");
    submenus.forEach(submenu => {
        const titulo = submenu.querySelector(".submenu-titulo");
        titulo.addEventListener("click", () => {

            // Cerrar los demás
            submenus.forEach(item => {
                if(item !== submenu){
                    item.classList.remove("activo");
                }
            });

            // Abrir/Cerrar el seleccionado
            submenu.classList.toggle("activo");
        });
    });
});