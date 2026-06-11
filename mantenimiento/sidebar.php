?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Lista de Mantenimientos</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/css/script.js" defer></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>

    <!-- HEADER -->
    <header class="header">
        <div class="logo">
            <img src="../assets/Imagenes/Logo_2.png" alt="Logo">
        </div>
        <div class="usuario">
            <img src="../assets/Imagenes/user.png" alt="Usuario">
            <span>Usuario</span>
        </div>
    </header>

    <!-- CONTENEDOR -->
    <div class="contenedor">

        <!-- MENU -->
        <aside class="menu">
            <ul>
                <li>Inicio</li>
                <li>Recaudos</li>
                <li>Cartera</li>
                <li class="activo">Mantenimiento</li>
                <li>Gastos</li>
                <li>Multas</li>
            </ul>

            <!-- CALENDARIO -->
            <div class="calendar">

                <!-- HEADER CALENDARIO -->
                <div class="calendar-header">

                    <!-- MES -->
                    <div class="month-control">
                        <span class="month-change" id="prev-month">
                            <
                                </span>
                                <span class="month-picker" id="month-picker">
                                    Mayo
                                </span>
                                <span class="month-change" id="next-month">
                                    >
                                </span>
                    </div>

                    <!-- AÑO -->
                    <div class="year-control">
                        <span class="year-change" id="prev-year">
                            <
                                </span>
                                <span id="year">
                                    2026
                                </span>
                                <span class="year-change" id="next-year">
                                    >
                                </span>
                    </div>
                </div>

                <!-- CUERPO -->
                <div class="calendar-body">

                    <!-- DIAS -->
                    <div class="calendar-week-days">
                        <div>Dom</div>
                        <div>Lun</div>
                        <div>Mar</div>
                        <div>Mie</div>
                        <div>Jue</div>
                        <div>Vie</div>
                        <div>Sab</div>
                    </div>

                    <!-- NUMEROS -->
                    <div class="calendar-days"></div>
                </div>

                <!-- FECHA -->
                <div class="date-time-formate">
                    <div class="time-formate"></div>
                    <div class="date-formate"></div>
                </div>
            </div>
        </aside>

    </div>
</body>

</html>