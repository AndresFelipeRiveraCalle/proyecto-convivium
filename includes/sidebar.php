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
                        <a href="<?= BASE_URL ?>configuracion/usuarios.php">
                            Usuarios
                        </a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>configuracion/unidades.php">
                            Unidades
                        </a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>configuracion/tablas_maestras.php">
                            Tablas Maestras
                        </a>
                    </li>
                </ul>
            </li>

            <!-- FACTURACIÓN -->
            <li class="submenu">
                <div class="submenu-titulo">
                    <span><i class="fa-solid fa-wallet icono"></i> Facturación</span>
                    <i class="fa-solid fa-chevron-down flecha"></i>
                </div>
                <ul class="submenu-items">
                    <li>
                        <a href="<?= BASE_URL ?>configuracion/calendario_financiero.php">
                            Calendario financiero
                        </a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>configuracion/conceptos_facturacion.php">
                            Conceptos de facturarcion
                        </a>
                    </li>
                                        <li>
                        <a href="<?= BASE_URL ?>configuracion/tarifas.php">
                            Valores de expensas
                        </a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>configuracion/generar_obligacion.php">
                            Crear facturas
                        </a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>configuracion/obligaciones.php">
                            Ver facturas
                        </a>
                    </li>                    
                    <li>
                        <a href="<?= BASE_URL ?>configuracion/facturacion.php">
                            Facturacion
                        </a>
                    </li>

                    <li>
                        <a href="<?= BASE_URL ?>configuracion/extractos_bancarios.php">
                            Cargar extractos
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
                    <li>
                        <a href="<?= BASE_URL ?>configuracion/cartera.php">
                            Estado de cartera
                        </a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>configuracion/detalle_cartera.php">
                            Detalle de cartera
                        </a>
                    </li>                    
                    <li>
                        <a href="<?= BASE_URL ?>configuracion/tasas_interes.php">
                            Tasas de interes
                        </a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>configuracion/pagos.php">
                            Pagoss
                        </a>
                    </li>
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
                        
                    <li><a href="<?= BASE_URL ?>mantenimiento/crear.php">
                        Programación</a>
                    </li>
                    <li><a href="#">Proveedores</a></li>
                </ul>
            </li>

            <li>
                <a href="<?= BASE_URL ?>inventario/listar.php">
                    <i class="fa-solid fa-boxes-stacked icono"></i>
                    Inventario
                </a>
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