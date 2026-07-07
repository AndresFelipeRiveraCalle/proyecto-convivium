
<div class="contenedor">
    <aside class="menu">
        <nav>
            <ul class="menu-principal">
                <li>
                    <a href="<?= BASE_URL ?>inicio.php">
                        <span class="icono">🏠</span>
                        Inicio
                    </a>
                </li>

                <!-- CONFIGURACIÓN -->

                <li class="submenu">
                    <div class="submenu-titulo">
                        <span>⚙ Configuración</span>
                        <span class="flecha">▼</span>
                    </div>

                    <ul class="submenu-items">
                        <li>
                            <a href="configuracion/datos.php">
                                Datos de la copropiedad
                            </a>
                        </li>

                        <li>
                            <a href="../configuracion/basico.php">
                                Tipos de unidades
                            </a>
                        </li>

                        <li>
                            <a href="../configuracion/unidades.php">
                                Unidades
                            </a>
                        </li>

                        <li>
                            <a href="#">
                                Personas
                            </a>
                        </li>

                        <li>
                            <a href="#">
                                Usuarios
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- CARTERA -->

                <li class="submenu">
                    <div class="submenu-titulo">
                        <span>💰 Cartera </span>
                        <span class="flecha">▼</span>
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
                        <span>🔧 Mantenimiento</span>

                        <span class="flecha">▼</span>
                    </div>

                    <ul class="submenu-items">
                        <li><a href="#">Solicitudes</a></li>
                        <li><a href="#">Programación</a></li>
                        <li><a href="#">Proveedores</a></li>
                    </ul>
                </li>

                <li>
                    <a href="#">📦 Inventario</a>
                </li>

                <li>
                    <a href="#">🚗 Vehículos</a>
                </li>

                <li>
                    <a href="#">🐶 Mascotas</a>
                </li>

                <li>
                    <a href="#">📄 Correspondencia</a>
                </li>

                <li>
                    <a href="#">📅 Reservas</a>
                </li>

                <li>
                    <a href="#">📢 Comunicados</a>
                </li>

                <li>
                    <a href="#">📊 Reportes</a>
                </li>
            </ul>

        </nav>

        <div class="calendar">

            <div class="calendar-header">
                <div class="month-control">
                    <span class="month-change" id="prev-month"><</span>
                    <span class="month-picker" id="month-picker">
                        Mayo
                    </span>
                    <span class="month-change" id="next-month">></span>
                </div>

                <div class="year-control">
                    <span class="year-change" id="prev-year"></span>
                    <span id="year">2026</span>
                    <span class="year-change" id="next-year">></span>
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
    </div>
</aside>