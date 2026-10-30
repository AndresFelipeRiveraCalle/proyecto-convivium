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
                        <a href="<?= BASE_URL ?>configuracion/espacios.php">
                            Espacios
                        </a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>configuracion/tablas_maestras.php">
                            Tablas Maestras
                        </a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>actions/guardar_configuracion_correo.php">
                            Configuración de correo
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
                        <a href="<?= BASE_URL ?>configuracion/factura.php">
                            Crear facturas
                        </a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>configuracion/cargos.php">
                            Cobros adicionales
                        </a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>configuracion/cargo_detalle.php">
                            Detalle cobros adicionales
                        </a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>configuracion/facturas_generadas.php">
                            Ver facturas
                        </a>
                    </li>            

                    <li>
                        <a href="<?= BASE_URL ?>configuracion/envio_facturas.php">
                            Enviar facturas
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

                    <span>
                        <i class="fa-solid fa-wallet icono"></i>
                        Cartera
                    </span>

                    <i class="fa-solid fa-chevron-down flecha"></i>

                </div>


                <ul class="submenu-items">

                    <li>
                        <a href="<?= BASE_URL ?>configuracion/cartera.php">
                            Estado de cartera
                        </a>
                    </li>

                    <li>
                        <a href="<?= BASE_URL ?>configuracion/tasas_interes.php">
                            Tasas de interés
                        </a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>configuracion/configuracion_mora.php">
                            Configuración de mora
                        </a>
                    </li>   
                    <li>
                        <a href="<?= BASE_URL ?>configuracion/pagos.php">
                            Pagos
                        </a>
                    </li>

                </ul>

            </li>

            <!-- PAGOS -->
            <li class="submenu">

                <div class="submenu-titulo">

                    <span>
                        <i class="fa-solid fa-money-bill-transfer icono"></i>
                        Pagos
                    </span>

                    <i class="fa-solid fa-chevron-down flecha"></i>

                </div>

                <ul class="submenu-items">

                    <li>
                        <a href="<?= BASE_URL ?>configuracion/pagos.php">
                            Pagos registrados
                        </a>
                    </li>

                    <li>
                        <a href="<?= BASE_URL ?>configuracion/conciliacion.php">
                            Conciliación bancaria
                        </a>
                    </li>

                    <li>
                        <a href="<?= BASE_URL ?>configuracion/aplicaciones_pagos.php">
                            Aplicaciones de pagos
                        </a>
                    </li>

                    <li>
                        <a href="<?= BASE_URL ?>configuracion/saldos_favor.php">
                            Saldos a favor
                        </a>
                    </li>

                </ul>

            </li>

            <!-- ZONAS COMUNES -->
            <li class="submenu">
                <div class="submenu-titulo">
                    <span><i class="fa-solid fa-screwdriver-wrench icono"></i> Zonas Comunes</span>
                    <i class="fa-solid fa-chevron-down flecha"></i>
                </div>

                <ul class="submenu-items">
                    <li>
                        <a href="<?= BASE_URL ?>zonas/index.php">
                        Solicitudes</a>
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

            <!-- Módulo de Mascotas -->
            <li>
                    <!-- Asegúrate de usar tu ruta absoluta o ROOT_PATH si lo tienes configurado así en el sidebar -->
                    <a href="/proyecto-convivium/mascotas/listar_mascotas.php">
                    <!-- Puedes usar un icono de FontAwesome si los tienes integrados, como una huellita -->
                    <i class="icono fa-solid fa-paw"></i> Mascotas
                    </a>
            </li>

            <li>
                <a href="<?= BASE_URL ?>inventario/listar.php">
                    <i class="fa-solid fa-boxes-stacked icono"></i>
                    Inventario
                </a>
            </li>

            <!-- PARQUEADERO -->
            <li class="submenu">
                <div class="submenu-titulo">
                    <span><i class="fa-solid fa-screwdriver-wrench icono"></i> Parqueaderos</span>
                    <i class="fa-solid fa-chevron-down flecha"></i>
                </div>
                <ul class="submenu-items">

                    <li>
                        <a href="<?= BASE_URL ?>configuracion/parqueaderos.php"><i class="fa-solid fa-car icono"></i> Parqueaderos</a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>configuracion/vehiculos.php"><i class="fa-solid fa-car icono"></i> Vehículos</a>
                    </li>
                </ul>
            


            <!-- correspondencia -->
            <li>
                <a href="<?php echo BASE_URL; ?>/correspondencia/listar_correspondencia.php">
                    <i class="fa-solid fa-box icono"></i> Correspondencia
                </a>
            </li>

            <!-- ZONAS COMUNES -->
            <li class="submenu">
                <div class="submenu-titulo">
                    <span><i class="fa-solid fa-calendar-check icono"></i> Zonas comunes</span>
                    <i class="fa-solid fa-chevron-down flecha"></i>
                </div>

                <ul class="submenu-items">
                    <li>
                        <a href="<?= BASE_URL ?>zonas/index.php">
                        Zonas comunes</a>
                    </li>
                        
                    <li><a href="<?= BASE_URL ?>reservas/index.php">
                        Reservas</a>
                    </li>
                </ul>
            </li>

            <li>
                <a href="#"><i class="fa-solid fa-bullhorn icono"></i> Comunicados</a>
            </li>

            <li>
                <a href="#"><i class="fa-solid fa-chart-pie icono"></i> Reportes</a>
            </li>

            <li class="cerrar-sesion">
                <a href="<?= BASE_URL ?>cerrar_sesion.php">
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