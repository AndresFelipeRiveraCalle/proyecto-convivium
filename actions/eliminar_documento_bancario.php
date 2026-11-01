<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/auth/requerir_sesion.php";
require_once ROOT_PATH . "/config/conexion.php";


function redirigirEliminarDocumento(
    string $tipo,
    string $mensaje
): void {
    header(
        "Location: " . BASE_URL .
        "configuracion/extractos_bancarios.php?" .
        http_build_query([
            'tipo' => $tipo,
            'mensaje' => $mensaje
        ])
    );

    exit;
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigirEliminarDocumento(
        'error',
        'Solicitud no válida.'
    );
}


$idDocumento = filter_input(
    INPUT_POST,
    'id_documento',
    FILTER_VALIDATE_INT
);


if (!$idDocumento || $idDocumento <= 0) {
    redirigirEliminarDocumento(
        'error',
        'Documento bancario no válido.'
    );
}


$nombreArchivoAEliminar = null;


try {
    $conexion->beginTransaction();


    // Bloquea el documento para evitar cambios simultáneos.
    $sqlDocumento = "
        SELECT
            id_documento,
            nombre_archivo,
            estado_procesamiento
        FROM documentos_bancarios
        WHERE id_documento = :id_documento
        LIMIT 1
        FOR UPDATE
    ";

    $stmtDocumento = $conexion->prepare($sqlDocumento);
    $stmtDocumento->execute([
        ':id_documento' => $idDocumento
    ]);

    $documento = $stmtDocumento->fetch(PDO::FETCH_ASSOC);


    if (!$documento) {
        throw new Exception(
            'El documento bancario no existe.'
        );
    }


    if ($documento['estado_procesamiento'] === 'PROCESANDO') {
        throw new Exception(
            'No se puede eliminar mientras el documento está siendo procesado.'
        );
    }


    // Bloquea los movimientos relacionados con el documento.
    $sqlMovimientos = "
        SELECT
            id_extracto,
            estado_conciliacion
        FROM extractos_bancarios
        WHERE id_documento = :id_documento
        FOR UPDATE
    ";

    $stmtMovimientos = $conexion->prepare($sqlMovimientos);
    $stmtMovimientos->execute([
        ':id_documento' => $idDocumento
    ]);

    $movimientos = $stmtMovimientos->fetchAll(PDO::FETCH_ASSOC);


    foreach ($movimientos as $movimiento) {
        if ($movimiento['estado_conciliacion'] === 'CONCILIADO') {
            throw new Exception(
                'No se puede eliminar: el documento contiene movimientos conciliados con pagos.'
            );
        }
    }


    // Verifica el vínculo real desde pagos, sin depender solo del estado.
    $sqlPagoVinculado = "
        SELECT p.id_pago
        FROM pagos p
        INNER JOIN extractos_bancarios e
            ON e.id_extracto = p.id_extracto
        WHERE e.id_documento = :id_documento
        LIMIT 1
        FOR UPDATE
    ";

    $stmtPagoVinculado = $conexion->prepare($sqlPagoVinculado);
    $stmtPagoVinculado->execute([
        ':id_documento' => $idDocumento
    ]);

    if ($stmtPagoVinculado->fetch(PDO::FETCH_ASSOC)) {
        throw new Exception(
            'No se puede eliminar: al menos un movimiento está vinculado a un pago.'
        );
    }


    // Elimina primero los movimientos para respetar las claves foráneas.
    $sqlEliminarMovimientos = "
        DELETE FROM extractos_bancarios
        WHERE id_documento = :id_documento
    ";

    $stmtEliminarMovimientos =
        $conexion->prepare($sqlEliminarMovimientos);

    $stmtEliminarMovimientos->execute([
        ':id_documento' => $idDocumento
    ]);


    // Luego elimina el documento.
    $sqlEliminarDocumento = "
        DELETE FROM documentos_bancarios
        WHERE id_documento = :id_documento
    ";

    $stmtEliminarDocumento =
        $conexion->prepare($sqlEliminarDocumento);

    $stmtEliminarDocumento->execute([
        ':id_documento' => $idDocumento
    ]);

    if ($stmtEliminarDocumento->rowCount() !== 1) {
        throw new Exception(
            'No fue posible eliminar el documento.'
        );
    }


    $nombreArchivoAEliminar =
        $documento['nombre_archivo'];

    $conexion->commit();


    // Borra el archivo físico después de confirmar la transacción.
    $mensaje =
        'El documento y sus movimientos se eliminaron correctamente.';

    $directorioDocumentos = realpath(
        ROOT_PATH . '/uploads/documentos_bancarios'
    );

    if ($directorioDocumentos !== false) {
        $rutaArchivo = $directorioDocumentos .
            DIRECTORY_SEPARATOR .
            basename($nombreArchivoAEliminar);

        if (
            is_file($rutaArchivo)
            && !unlink($rutaArchivo)
        ) {
            $mensaje .=
                ' No fue posible eliminar el archivo físico; revisa la carpeta de documentos.';
        }
    }


    redirigirEliminarDocumento(
        'success',
        $mensaje
    );

} catch (Throwable $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    redirigirEliminarDocumento(
        'error',
        $e->getMessage()
    );
}