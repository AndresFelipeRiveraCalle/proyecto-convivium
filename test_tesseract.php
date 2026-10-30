<?php
require_once __DIR__ . "/auth/requerir_sesion.php";

$output = [];
$return = 0;

exec("tesseract --version 2>&1", $output, $return);

echo "<pre>";
echo "Código de retorno: " . $return . PHP_EOL;
echo implode(PHP_EOL, $output);
echo "</pre>";