<aside class="menu">
    <nav>
        <ul class="menu-principal">
            <li>
                <a href="<?= BASE_URL ?>inicio.php">
                    <i class="fa-solid fa-house icono"></i>
                    <span>Inicio</span>
                </a>
            </li>

            <!-- CONFIGURACIÓN -->
            <li class="submenu">
                <div class="submenu-titulo">
                    <span><i class="fa-solid fa-gear icono"></i> Configuración</span>
                    <i class="fa-solid fa-chevron-down flecha"></i>
                </div>

                <ul class="submenu-items">
                    <li>
                        <a href="<?= BASE_URL ?>configuracion/datos.php">
                            Datos de la copropiedad
                        </a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>configuracion/basico.php">
                            Tipos de unidades
                        </a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>configuracion/unidades.php">
                            Unidades
                        </a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>configuracion/personas.php">
                            Personas
                        </a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>configuracion/usuarios.php">
                            Usuarios
                        </a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>configuracion/tablas_maestras.php">
                            Tablas Maestras
                        </a>
                    </li>
                </ul>
            </li>

            <!-- CARTERA -->
            <li class="submenu">
                <div class="submenu-titulo">
                    <span><i class="fa-solid fa-wallet icono"></i> Cartera</span>
                    <i class="fa-solid fa-chevron-down flecha"></i>
                </div>

                <ul class="submenu-items">
                    <li><a href="#">Estado de cuenta</a></li>
                    <li><a href="#">Pagos</a></li>
                    <li><a href="#">Recaudos</a></li>
                </ul>
            </li>

            <!-- MANTENIMIENTO -->
            <li class="submenu">
                <div class="submenu-titulo">
                    <span><i class="fa-solid fa-screwdriver-wrench icono"></i> Mantenimiento</span>
                    <i class="fa-solid fa-chevron-down flecha"></i>
                </div>

                <ul class="submenu-items">
                    <li>
                        <a href="<?= BASE_URL ?>mantenimiento/listar.php">
                        Solicitudes</a>
                    </li>
                        
                    <li><a href="#">Programación</a></li>
                    <li><a href="#">Proveedores</a></li>
                </ul>
            </li>

            <li>
                <a href="#"><i class="fa-solid fa-boxes-stacked icono"></i> Inventario</a>
            </li>

            <li>
                <a href="#"><i class="fa-solid fa-car icono"></i> Vehículos</a>
            </li>

            <li>
                <a href="#"><i class="fa-solid fa-dog icono"></i> Mascotas</a>
            </li>

            <li>
                <a href="#"><i class="fa-solid fa-envelope-open-text icono"></i> Correspondencia</a>
            </li>

            <li>
                <a href="#"><i class="fa-solid fa-calendar-check icono"></i> Reservas</a>
            </li>

            <li>
                <a href="#"><i class="fa-solid fa-bullhorn icono"></i> Comunicados</a>
            </li>

            <li>
                <a href="#"><i class="fa-solid fa-chart-pie icono"></i> Reportes</a>
            </li>

            <li class="cerrar-sesion">
                <a href="<?= BASE_URL ?>logout.php">
                    <i class="fa-solid fa-right-from-bracket icono"></i>
                    <span>Cerrar sesión</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- CALENDARIO -->
    <div class="calendar">
        <div class="calendar-header">
            <div class="month-control">
                <span class="month-change" id="prev-month">
                    <i class="fa-solid fa-chevron-left"></i>
                </span>
                <span class="month-picker" id="month-picker">Mayo</span>
                <span class="month-change" id="next-month">
                    <i class="fa-solid fa-chevron-right"></i>
                </span>
            </div>

            <div class="year-control">
                <span class="year-change" id="prev-year">
                    <i class="fa-solid fa-chevron-left"></i>
                </span>
                <span id="year">2026</span>
                <span class="year-change" id="next-year">
                    <i class="fa-solid fa-chevron-right"></i>
                </span>
            </div>
        </div>

        <div class="calendar-body">
            <div class="calendar-week-days">
                <div>Dom</div>
                <div>Lun</div>
                <div>Mar</div>
                <div>Mie</div>
                <div>Jue</div>
                <div>Vie</div>
                <div>Sab</div>
            </div>
            <div class="calendar-days"></div>
        </div>

        <div class="date-time-formate">
            <div class="time-formate"></div>
            <div class="date-formate"></div>
        </div>
    </div>
</aside>