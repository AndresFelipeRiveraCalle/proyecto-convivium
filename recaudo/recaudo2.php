<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <title>Convivium - Cartera</title>
    <link rel="stylesheet" href="style.css">
    <script src="../assets/css/script.js" defer></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

    <!-- CONTENEDOR -->


        <!-- CONTENIDO -->
        <main class="contenido">

            <!-- CARD 1 -->
            <div class="bloque">
                <h3>Recaudo</h3>
                <div class="card">
                    <div class="pie-chart">
                        <div class="pie-chart-inner">
                            70%
                        </div>
                    </div>
                </div>
            </div>

            <!-- CARD 2 -->
            <div class="bloque">
                <h3>Antigüedad promedio</h3>
                <div class="card">
                    <div class="bar-chart">
                        <div>
                            <div class="bar" style="height: 100px;"></div>
                            <div class="bar-label">45 días</div>
                        </div>

                        <div>
                            <div class="bar" style="height: 80px; background:#3498db;"></div>
                            <div class="bar-label">31-90 días</div>
                        </div>

                        <div>
                            <div class="bar" style="height: 60px; background:#9b59b6;"></div>
                            <div class="bar-label">70 días</div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- CARD 3 -->
            <div class="bloque">
                <h3>Tendencia mensual</h3>
                <div class="card">
                    <p>Aquí irá el gráfico</p>
                </div>
            </div>
        </main>
    </div>

</body>

</html>