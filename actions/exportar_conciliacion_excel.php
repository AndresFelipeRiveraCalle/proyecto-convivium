<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// FILTROS
// ==========================================================

$estado =
    trim(
        $_GET['estado_conciliacion'] ?? ''
    );

$buscar =
    trim(
        $_GET['buscar'] ?? ''
    );

$fechaDesde =
    trim(
        $_GET['fecha_desde'] ?? ''
    );

$fechaHasta =
    trim(
        $_GET['fecha_hasta'] ?? ''
    );

$exportarTodo =
    isset($_GET['todos'])
    && (int)$_GET['todos'] === 1;


// ==========================================================
// WHERE
// ==========================================================

$where = [
    "eb.tipo_movimiento = 'INGRESO'"
];

$params = [];


if (
    !$exportarTodo
    &&
    in_array(
        $estado,
        [
            'PENDIENTE',
            'CONCILIADO',
            'RECHAZADO',
            'CON_DIFERENCIA'
        ],
        true
    )
) {

    $where[] = "
        eb.estado_conciliacion =
            :estado_conciliacion
    ";

    $params[':estado_conciliacion'] =
        $estado;
}


if ($buscar !== '') {

    $where[] = "
        (
            eb.descripcion LIKE :buscar
            OR eb.referencia LIKE :buscar
            OR eb.numero_documento LIKE :buscar
            OR eb.archivo_origen LIKE :buscar
            OR u.codigo LIKE :buscar
            OR p.referencia LIKE :buscar
        )
    ";

    $params[':buscar'] =
        '%' . $buscar . '%';
}


if ($fechaDesde !== '') {

    $where[] = "
        eb.fecha_movimiento >= :fecha_desde
    ";

    $params[':fecha_desde'] =
        $fechaDesde;
}


if ($fechaHasta !== '') {

    $where[] = "
        eb.fecha_movimiento <= :fecha_hasta
    ";

    $params[':fecha_hasta'] =
        $fechaHasta;
}


$whereSql =
    implode(
        ' AND ',
        $where
    );


// ==========================================================
// CONSULTA
// ==========================================================

$sql = "
    SELECT
        eb.id_extracto,
        eb.fecha_movimiento,
        eb.fecha_valor,
        eb.descripcion,
        eb.referencia AS referencia_bancaria,
        eb.numero_documento,
        eb.valor AS valor_banco,
        eb.estado_conciliacion,
        eb.archivo_origen,
        eb.observaciones,

        cb.banco,
        cb.tipo_cuenta,
        cb.numero_cuenta,

        p.id_pago,
        p.fecha_pago,
        p.valor AS valor_pago,
        p.referencia AS referencia_pago,
        p.referencia_externa,
        p.fecha_conciliacion,

        u.codigo AS unidad_codigo,
        u.nombre AS unidad_nombre

    FROM extractos_bancarios eb

    LEFT JOIN cuentas_bancarias cb
        ON cb.id_cuenta_bancaria =
           eb.id_cuenta_bancaria

    LEFT JOIN pagos p
        ON p.id_extracto =
           eb.id_extracto

    LEFT JOIN unidades u
        ON u.id_unidad =
           p.id_unidad

    WHERE
        $whereSql

    ORDER BY
        eb.fecha_movimiento DESC,
        eb.id_extracto DESC
";


$stmt =
    $conexion->prepare(
        $sql
    );

$stmt->execute(
    $params
);

$registros =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );


// ==========================================================
// DESCARGA EXCEL-COMPATIBLE CSV
// ==========================================================

$nombreArchivo =
    'conciliaciones_' .
    date('Ymd_His') .
    '.csv';


header(
    'Content-Type: text/csv; charset=UTF-8'
);

header(
    'Content-Disposition: attachment; filename="' .
    $nombreArchivo .
    '"'
);

header(
    'Pragma: no-cache'
);

header(
    'Expires: 0'
);


// BOM UTF-8 para Excel
echo "\xEF\xBB\xBF";


$salida =
    fopen(
        'php://output',
        'w'
    );


// Separador compatible con Excel en configuración regional ES
$separador = ';';


fputcsv(
    $salida,
    [
        'ID movimiento',
        'Fecha movimiento',
        'Fecha valor',
        'Banco',
        'Tipo cuenta',
        'Número cuenta',
        'Descripción',
        'Referencia bancaria',
        'Número documento',
        'Valor banco',
        'Estado conciliación',
        'ID pago',
        'Unidad',
        'Nombre unidad',
        'Fecha pago',
        'Valor pago',
        'Diferencia',
        'Referencia pago',
        'Referencia externa',
        'Fecha conciliación',
        'Archivo origen',
        'Observaciones'
    ],
    $separador
);


foreach ($registros as $fila) {

    $diferencia =
        !empty($fila['id_pago'])
            ? abs(
                (float)$fila['valor_banco']
                -
                (float)$fila['valor_pago']
            )
            : null;


    fputcsv(
        $salida,
        [
            $fila['id_extracto'],
            $fila['fecha_movimiento'],
            $fila['fecha_valor'],
            $fila['banco'],
            $fila['tipo_cuenta'],
            $fila['numero_cuenta'],
            $fila['descripcion'],
            $fila['referencia_bancaria'],
            $fila['numero_documento'],
            number_format(
                (float)$fila['valor_banco'],
                2,
                ',',
                '.'
            ),
            $fila['estado_conciliacion'],
            $fila['id_pago'],
            $fila['unidad_codigo'],
            $fila['unidad_nombre'],
            $fila['fecha_pago'],
            !empty($fila['id_pago'])
                ? number_format(
                    (float)$fila['valor_pago'],
                    2,
                    ',',
                    '.'
                )
                : '',
            $diferencia !== null
                ? number_format(
                    $diferencia,
                    2,
                    ',',
                    '.'
                )
                : '',
            $fila['referencia_pago'],
            $fila['referencia_externa'],
            $fila['fecha_conciliacion'],
            $fila['archivo_origen'],
            $fila['observaciones']
        ],
        $separador
    );
}


fclose(
    $salida
);

exit;
