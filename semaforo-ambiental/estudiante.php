
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/conexion.php';

if (!isset($conexion) || !($conexion instanceof mysqli)) {
    die('Error: verifica la conexión en config/conexion.php.');
}

$conexion->set_charset('utf8mb4');

if (
    !isset($_SESSION['id_usuario']) ||
    strtolower(trim((string) ($_SESSION['rol'] ?? ''))) !== 'estudiante'
) {
    header('Location: login.php');
    exit;
}

$idEstudiante = (int) $_SESSION['id_usuario'];
$nombreEstudiante = $_SESSION['nombre'] ?? 'Estudiante';
$mensaje = '';
$tipoMensaje = 'success';

function h($valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

$conexion->query("
    CREATE TABLE IF NOT EXISTS tbl_actividades (
        id_actividad INT AUTO_INCREMENT PRIMARY KEY,
        id_docente INT NOT NULL,
        titulo VARCHAR(180) NOT NULL,
        descripcion TEXT NOT NULL,
        fecha_publicacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        fecha_entrega DATETIME NULL,
        puntaje_maximo DECIMAL(5,2) NOT NULL DEFAULT 5.00,
        estado ENUM('publicada','cerrada') NOT NULL DEFAULT 'publicada',
        INDEX (id_docente),
        INDEX (estado)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$conexion->query("
    CREATE TABLE IF NOT EXISTS tbl_entregas (
        id_entrega INT AUTO_INCREMENT PRIMARY KEY,
        id_actividad INT NOT NULL,
        id_estudiante INT NOT NULL,
        respuesta TEXT NULL,
        enlace VARCHAR(500) NULL,
        archivo VARCHAR(255) NULL,
        fecha_entrega TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_actividad_estudiante (id_actividad, id_estudiante),
        INDEX (id_estudiante),
        INDEX (id_actividad)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$conexion->query("
    CREATE TABLE IF NOT EXISTS tbl_calificaciones (
        id_calificacion INT AUTO_INCREMENT PRIMARY KEY,
        id_entrega INT NOT NULL,
        id_docente INT NOT NULL,
        nota DECIMAL(5,2) NOT NULL,
        observaciones TEXT NULL,
        fecha_calificacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_entrega (id_entrega),
        INDEX (id_docente)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$carpetaEntregas = __DIR__ . '/uploads/entregas';

if (!is_dir($carpetaEntregas)) {
    mkdir($carpetaEntregas, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $idActividad = (int) ($_POST['id_actividad'] ?? 0);

    if (
        $idActividad <= 0 ||
        !in_array($accion, ['entregar', 'anular'], true)
    ) {
        $mensaje = 'La solicitud no es válida.';
        $tipoMensaje = 'error';
    } else {
        $stmtActividad = $conexion->prepare("
            SELECT id_actividad, estado, fecha_entrega
            FROM tbl_actividades
            WHERE id_actividad = ?
            LIMIT 1
        ");

        $stmtActividad->bind_param('i', $idActividad);
        $stmtActividad->execute();
        $actividadBD = $stmtActividad->get_result()->fetch_assoc();
        $stmtActividad->close();

        if (!$actividadBD) {
            $mensaje = 'La actividad no existe.';
            $tipoMensaje = 'error';
        } else {
            $plazoVigente =
                $actividadBD['estado'] === 'publicada' &&
                (
                    $actividadBD['fecha_entrega'] === null ||
                    strtotime($actividadBD['fecha_entrega']) >= time()
                );

            $stmtEntrega = $conexion->prepare("
                SELECT
                    e.id_entrega,
                    e.archivo,
                    c.id_calificacion
                FROM tbl_entregas e
                LEFT JOIN tbl_calificaciones c
                    ON c.id_entrega = e.id_entrega
                WHERE e.id_actividad = ?
                  AND e.id_estudiante = ?
                LIMIT 1
            ");

            $stmtEntrega->bind_param('ii', $idActividad, $idEstudiante);
            $stmtEntrega->execute();
            $entregaAnterior = $stmtEntrega->get_result()->fetch_assoc();
            $stmtEntrega->close();

            $yaCalificada = $entregaAnterior &&
                $entregaAnterior['id_calificacion'] !== null;

            if (!$plazoVigente) {
                $mensaje = 'La actividad está cerrada o el plazo de entrega terminó.';
                $tipoMensaje = 'error';
            } elseif ($yaCalificada) {
                $mensaje = 'Esta entrega ya fue calificada y no se puede modificar ni anular.';
                $tipoMensaje = 'error';
            } elseif ($accion === 'anular') {
                if (!$entregaAnterior) {
                    $mensaje = 'No tienes una entrega registrada para anular.';
                    $tipoMensaje = 'error';
                } else {
                    $stmtEliminar = $conexion->prepare("
                        DELETE FROM tbl_entregas
                        WHERE id_entrega = ?
                          AND id_estudiante = ?
                    ");

                    $stmtEliminar->bind_param(
                        'ii',
                        $entregaAnterior['id_entrega'],
                        $idEstudiante
                    );

                    if (
                        $stmtEliminar->execute() &&
                        $stmtEliminar->affected_rows > 0
                    ) {
                        if (!empty($entregaAnterior['archivo'])) {
                            $rutaArchivo = __DIR__ . '/' .
                                $entregaAnterior['archivo'];

                            if (is_file($rutaArchivo)) {
                                unlink($rutaArchivo);
                            }
                        }

                        $mensaje = 'El envío se anuló correctamente. Puedes realizar una nueva entrega mientras el plazo esté vigente.';
                    } else {
                        $mensaje = 'No fue posible anular el envío. Inténtalo nuevamente.';
                        $tipoMensaje = 'error';
                    }

                    $stmtEliminar->close();
                }
            } elseif ($accion === 'entregar') {
                $respuesta = trim($_POST['respuesta'] ?? '');
                $enlace = trim($_POST['enlace'] ?? '');
                $archivoRuta = null;
                $errorArchivo = '';

                if (
                    $respuesta === '' &&
                    $enlace === '' &&
                    empty($_FILES['archivo']['name']) &&
                    (!$entregaAnterior || empty($entregaAnterior['archivo']))
                ) {
                    $mensaje = 'Escribe una respuesta, agrega un enlace o adjunta un archivo.';
                    $tipoMensaje = 'error';
                } elseif (
                    $enlace !== '' &&
                    !filter_var($enlace, FILTER_VALIDATE_URL)
                ) {
                    $mensaje = 'El enlace ingresado no tiene un formato válido.';
                    $tipoMensaje = 'error';
                } else {
                    if (!empty($_FILES['archivo']['name'])) {
                        $archivo = $_FILES['archivo'];

                        $permitidas = [
                            'pdf', 'doc', 'docx', 'ppt', 'pptx',
                            'xls', 'xlsx', 'txt', 'jpg', 'jpeg',
                            'png', 'zip'
                        ];

                        $extension = strtolower(
                            pathinfo($archivo['name'], PATHINFO_EXTENSION)
                        );

                        if (
                            $archivo['error'] !== UPLOAD_ERR_OK ||
                            !in_array($extension, $permitidas, true) ||
                            $archivo['size'] > 10 * 1024 * 1024
                        ) {
                            $errorArchivo = 'Archivo no válido. Usa PDF, Office, TXT, imágenes o ZIP de máximo 10 MB.';
                        } else {
                            $nombreSeguro = bin2hex(random_bytes(12)) .
                                '.' . $extension;

                            if (
                                move_uploaded_file(
                                    $archivo['tmp_name'],
                                    $carpetaEntregas . '/' . $nombreSeguro
                                )
                            ) {
                                $archivoRuta = 'uploads/entregas/' . $nombreSeguro;
                            } else {
                                $errorArchivo = 'No se pudo guardar el archivo. Verifica la carpeta uploads/entregas.';
                            }
                        }
                    }

                    if ($errorArchivo !== '') {
                        $mensaje = $errorArchivo;
                        $tipoMensaje = 'error';
                    } else {
                        if ($entregaAnterior && !$archivoRuta) {
                            $archivoRuta = $entregaAnterior['archivo'];
                        }

                        $stmtGuardar = $conexion->prepare("
                            INSERT INTO tbl_entregas
                                (
                                    id_actividad,
                                    id_estudiante,
                                    respuesta,
                                    enlace,
                                    archivo
                                )
                            VALUES (?, ?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE
                                respuesta = VALUES(respuesta),
                                enlace = VALUES(enlace),
                                archivo = VALUES(archivo),
                                fecha_entrega = CURRENT_TIMESTAMP
                        ");

                        $stmtGuardar->bind_param(
                            'iisss',
                            $idActividad,
                            $idEstudiante,
                            $respuesta,
                            $enlace,
                            $archivoRuta
                        );

                        if ($stmtGuardar->execute()) {
                            if (
                                $entregaAnterior &&
                                !empty($entregaAnterior['archivo']) &&
                                $archivoRuta !== $entregaAnterior['archivo']
                            ) {
                                $rutaAnterior = __DIR__ . '/' .
                                    $entregaAnterior['archivo'];

                                if (is_file($rutaAnterior)) {
                                    unlink($rutaAnterior);
                                }
                            }

                            $mensaje = $entregaAnterior
                                ? 'La entrega se actualizó correctamente.'
                                : 'La actividad se envió correctamente.';
                        } else {
                            $mensaje = 'No fue posible guardar la entrega.';
                            $tipoMensaje = 'error';
                        }

                        $stmtGuardar->close();
                    }
                }
            }
        }
    }
}

$actividades = [];

$sql = "
    SELECT
        a.id_actividad,
        a.titulo,
        a.descripcion,
        a.fecha_publicacion,
        a.fecha_entrega,
        a.puntaje_maximo,
        a.estado,
        e.id_entrega,
        e.respuesta,
        e.enlace,
        e.archivo,
        e.fecha_entrega AS fecha_envio,
        c.nota,
        c.observaciones,
        c.fecha_calificacion
    FROM tbl_actividades a
    LEFT JOIN tbl_entregas e
        ON e.id_actividad = a.id_actividad
        AND e.id_estudiante = ?
    LEFT JOIN tbl_calificaciones c
        ON c.id_entrega = e.id_entrega
    WHERE a.estado IN ('publicada', 'cerrada')
    ORDER BY
        CASE
            WHEN a.fecha_entrega IS NOT NULL
                AND a.fecha_entrega < NOW()
            THEN 1 ELSE 0
        END,
        a.fecha_entrega IS NULL,
        a.fecha_entrega ASC,
        a.fecha_publicacion DESC
";

$stmt = $conexion->prepare($sql);
$stmt->bind_param('i', $idEstudiante);
$stmt->execute();
$resultado = $stmt->get_result();

while ($fila = $resultado->fetch_assoc()) {
    $actividades[] = $fila;
}

$stmt->close();

$totalActividades = count($actividades);
$totalEntregadas = 0;
$totalCalificadas = 0;
$sumaNotas = 0;

foreach ($actividades as $actividad) {
    if (!empty($actividad['id_entrega'])) {
        $totalEntregadas++;
    }

    if ($actividad['nota'] !== null) {
        $totalCalificadas++;
        $sumaNotas += (float) $actividad['nota'];
    }
}

$promedio = $totalCalificadas > 0
    ? number_format($sumaNotas / $totalCalificadas, 1, ',', '.')
    : '—';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi espacio de aprendizaje | Semáforo Ambiental</title>

    <style>
        :root {
            --verde: #287343;
            --verde-oscuro: #194d2c;
            --verde-claro: #eaf5ed;
            --fondo: #f5f8f5;
            --texto: #243329;
            --gris: #647168;
            --borde: #dfe8e0;
            --rojo: #a52c2c;
            --amarillo: #8a6200;
            --sombra: 0 8px 24px rgba(25, 77, 44, .08);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--fondo);
            font-family: Arial, Helvetica, sans-serif;
            color: var(--texto);
            line-height: 1.5;
        }

        a {
            color: var(--verde);
            overflow-wrap: anywhere;
        }

        .topbar {
            background: var(--verde-oscuro);
            color: #fff;
            padding: 18px max(18px, calc((100% - 1180px) / 2));
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .brand {
            font-size: 19px;
            font-weight: 800;
        }

        .brand small {
            display: block;
            font-size: 12px;
            font-weight: 400;
            opacity: .85;
        }

        .acciones {
            display: flex;
            gap: 9px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 9px;
            padding: 10px 15px;
            background: var(--verde);
            color: #fff;
            text-decoration: none;
            font-weight: 700;
            cursor: pointer;
            font-size: 14px;
            font-family: inherit;
        }

        .btn:hover {
            filter: brightness(.94);
        }

        .btn-sec {
            background: #fff;
            color: var(--verde-oscuro);
            border: 1px solid var(--borde);
        }

        .btn-anular {
            background: #fff0f0;
            color: var(--rojo);
            border: 1px solid #efc4c4;
        }

        .btn-anular:hover {
            background: var(--rojo);
            color: #fff;
        }

        .contenedor {
            width: min(1180px, calc(100% - 32px));
            margin: 27px auto 45px;
        }

        .bienvenida {
            margin-bottom: 22px;
        }

        .bienvenida h1 {
            margin: 0 0 5px;
            font-size: clamp(25px, 4vw, 34px);
        }

        .bienvenida p,
        .seccion-titulo p {
            margin: 0;
            color: var(--gris);
        }

        .tarjetas {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 15px;
            margin: 22px 0 28px;
        }

        .tarjeta-resumen {
            background: #fff;
            border: 1px solid var(--borde);
            border-radius: 14px;
            padding: 18px;
            box-shadow: var(--sombra);
        }

        .tarjeta-resumen span {
            display: block;
            color: var(--gris);
            font-size: 14px;
        }

        .tarjeta-resumen strong {
            display: block;
            font-size: 28px;
            color: var(--verde-oscuro);
            margin-top: 5px;
        }

        .seccion-titulo {
            margin: 24px 0 14px;
        }

        .seccion-titulo h2 {
            font-size: 22px;
            margin: 0 0 3px;
        }

        .seccion-titulo p {
            font-size: 14px;
        }

        .alerta {
            padding: 13px 16px;
            border-radius: 10px;
            margin-bottom: 18px;
            background: #e7f5e9;
            color: #205d30;
            border: 1px solid #bfe2c7;
        }

        .alerta.error {
            background: #fff0f0;
            color: var(--rojo);
            border-color: #f0c4c4;
        }

        .lista {
            display: grid;
            gap: 16px;
        }

        .actividad {
            background: #fff;
            border: 1px solid var(--borde);
            border-radius: 15px;
            padding: 20px;
            box-shadow: var(--sombra);
            min-width: 0;
        }

        .actividad-cabecera {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
            flex-wrap: wrap;
        }

        .actividad h3 {
            margin: 0 0 7px;
            font-size: 19px;
        }

        .descripcion {
            white-space: pre-wrap;
            margin: 12px 0;
            color: #455249;
            overflow-wrap: anywhere;
        }

        .meta {
            display: flex;
            gap: 8px 16px;
            flex-wrap: wrap;
            color: var(--gris);
            font-size: 13px;
        }

        .estado {
            display: inline-block;
            border-radius: 30px;
            padding: 5px 10px;
            font-size: 12px;
            font-weight: 700;
            background: #edf1ee;
            color: #4c5b50;
        }

        .estado.pendiente {
            background: #fff2ce;
            color: var(--amarillo);
        }

        .estado.entregada {
            background: #e4f5e8;
            color: #236b37;
        }

        .estado.calificada {
            background: #e5efff;
            color: #234f91;
        }

        .entrega-form {
            margin-top: 17px;
            padding-top: 16px;
            border-top: 1px solid var(--borde);
        }

        .form-anular {
            margin-top: 10px;
        }

        label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            margin: 0 0 6px;
        }

        textarea,
        input[type="url"],
        input[type="file"] {
            width: 100%;
            font: inherit;
            border: 1px solid #cbd8cd;
            border-radius: 9px;
            padding: 11px;
            background: #fff;
            color: var(--texto);
        }

        textarea {
            min-height: 95px;
            resize: vertical;
        }

        .campo {
            margin-bottom: 13px;
            min-width: 0;
        }

        .dos-columnas {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .calificacion {
            margin-top: 14px;
            padding: 13px;
            border-radius: 10px;
            background: #f1f6ff;
            border: 1px solid #d8e5fb;
        }

        .calificacion strong {
            font-size: 20px;
            color: #234f91;
        }

        .vacio {
            text-align: center;
            background: #fff;
            border: 1px dashed #b9cbbd;
            border-radius: 15px;
            padding: 35px 20px;
            color: var(--gris);
        }

        .archivo-actual {
            font-size: 13px;
            margin: 6px 0 12px;
        }

        .nota-ayuda {
            font-size: 12px;
            color: var(--gris);
            margin-top: 5px;
        }

        .pie {
            text-align: center;
            color: var(--gris);
            font-size: 12px;
            margin-top: 30px;
        }

        @media (max-width: 700px) {
            .tarjetas,
            .dos-columnas {
                grid-template-columns: 1fr;
            }

            .topbar {
                align-items: flex-start;
            }

            .contenedor {
                width: calc(100% - 22px);
                margin-top: 20px;
            }

            .actividad {
                padding: 15px;
            }
        }
    </style>
</head>

<body>

<header class="topbar">
    <div class="brand">
        Semáforo Ambiental
        <small>Espacio de aprendizaje del estudiante</small>
    </div>

    <div class="acciones">
        <a class="btn btn-sec" href="index.php">Visitar sitio</a>
        <a class="btn btn-sec" href="logout.php">Cerrar sesión</a>
    </div>
</header>

<main class="contenedor">

    <section class="bienvenida">
        <h1>¡Hola, <?= h($nombreEstudiante) ?>!</h1>
        <p>Consulta tus actividades, realiza tus entregas y revisa tus calificaciones.</p>
    </section>

    <?php if ($mensaje !== ''): ?>
        <div class="alerta <?= $tipoMensaje === 'error' ? 'error' : '' ?>">
            <?= h($mensaje) ?>
        </div>
    <?php endif; ?>

    <section class="tarjetas" aria-label="Resumen académico">
        <div class="tarjeta-resumen">
            <span>Actividades publicadas</span>
            <strong><?= $totalActividades ?></strong>
        </div>

        <div class="tarjeta-resumen">
            <span>Actividades entregadas</span>
            <strong><?= $totalEntregadas ?></strong>
        </div>

        <div class="tarjeta-resumen">
            <span>Promedio de actividades calificadas</span>
            <strong><?= h($promedio) ?></strong>
        </div>
    </section>

    <section class="seccion-titulo">
        <h2>Mis actividades</h2>
        <p>Lee las instrucciones y envía tu trabajo antes de la fecha límite.</p>
    </section>

    <?php if (!$actividades): ?>

        <div class="vacio">
            <strong>Aún no tienes actividades publicadas.</strong>
            <br>
            Cuando tu docente publique una actividad, aparecerá aquí.
        </div>

    <?php else: ?>

        <section class="lista">

            <?php foreach ($actividades as $a): ?>

                <?php
                $vencida = !empty($a['fecha_entrega']) &&
                    strtotime($a['fecha_entrega']) < time();

                $entregada = !empty($a['id_entrega']);
                $calificada = $a['nota'] !== null;

                $estadoTexto = $calificada
                    ? 'Calificada'
                    : ($entregada
                        ? 'Entregada'
                        : (($vencida || $a['estado'] === 'cerrada')
                            ? 'Plazo vencido/cerrada'
                            : 'Pendiente'));

                $estadoClase = $calificada
                    ? 'calificada'
                    : ($entregada ? 'entregada' : 'pendiente');

                $permitirEntrega = !$calificada
                    && !$vencida
                    && $a['estado'] === 'publicada';
                ?>

                <article class="actividad">

                    <div class="actividad-cabecera">
                        <div>
                            <h3><?= h($a['titulo']) ?></h3>

                            <div class="meta">
                                <span>
                                    Publicada:
                                    <?= h(date('d/m/Y', strtotime($a['fecha_publicacion']))) ?>
                                </span>

                                <span>
                                    Fecha límite:
                                    <?= $a['fecha_entrega']
                                        ? h(date('d/m/Y H:i', strtotime($a['fecha_entrega'])))
                                        : 'Sin fecha límite' ?>
                                </span>

                                <span>
                                    Puntaje máximo:
                                    <?= h(number_format((float) $a['puntaje_maximo'], 1, ',', '.')) ?>
                                </span>
                            </div>
                        </div>

                        <span class="estado <?= h($estadoClase) ?>">
                            <?= h($estadoTexto) ?>
                        </span>
                    </div>

                    <div class="descripcion"><?= h($a['descripcion']) ?></div>

                    <?php if ($entregada): ?>

                        <div class="meta">
                            <span>
                                Última entrega:
                                <?= h(date('d/m/Y H:i', strtotime($a['fecha_envio']))) ?>
                            </span>
                        </div>

                        <?php if (!empty($a['respuesta'])): ?>
                            <p>
                                <strong>Tu respuesta:</strong>
                                <br>
                                <?= nl2br(h($a['respuesta'])) ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($a['enlace'])): ?>
                            <p>
                                <strong>Enlace enviado:</strong>
                                <a href="<?= h($a['enlace']) ?>" target="_blank" rel="noopener noreferrer">
                                    Abrir enlace
                                </a>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($a['archivo'])): ?>
                            <p>
                                <strong>Archivo enviado:</strong>
                                <a href="<?= h($a['archivo']) ?>" target="_blank" rel="noopener noreferrer">
                                    Ver archivo adjunto
                                </a>
                            </p>
                        <?php endif; ?>

                    <?php endif; ?>

                    <?php if ($calificada): ?>

                        <div class="calificacion">
                            <div>
                                Calificación:
                                <strong>
                                    <?= h(number_format((float) $a['nota'], 1, ',', '.')) ?>
                                    /
                                    <?= h(number_format((float) $a['puntaje_maximo'], 1, ',', '.')) ?>
                                </strong>
                            </div>

                            <?php if (!empty($a['observaciones'])): ?>
                                <p>
                                    <strong>Observaciones del docente:</strong>
                                    <br>
                                    <?= nl2br(h($a['observaciones'])) ?>
                                </p>
                            <?php endif; ?>

                            <?php if (!empty($a['fecha_calificacion'])): ?>
                                <div class="nota-ayuda">
                                    Fecha de calificación:
                                    <?= h(date('d/m/Y H:i', strtotime($a['fecha_calificacion']))) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                    <?php elseif ($permitirEntrega): ?>

                        <form class="entrega-form" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="accion" value="entregar">
                            <input type="hidden" name="id_actividad" value="<?= (int) $a['id_actividad'] ?>">

                            <div class="campo">
                                <label for="respuesta-<?= (int) $a['id_actividad'] ?>">
                                    Tu respuesta
                                </label>

                                <textarea
                                    id="respuesta-<?= (int) $a['id_actividad'] ?>"
                                    name="respuesta"
                                    placeholder="Escribe aquí tu respuesta..."
                                ><?= $entregada ? h($a['respuesta']) : '' ?></textarea>
                            </div>

                            <div class="dos-columnas">
                                <div class="campo">
                                    <label for="enlace-<?= (int) $a['id_actividad'] ?>">
                                        Enlace (opcional)
                                    </label>

                                    <input
                                        type="url"
                                        id="enlace-<?= (int) $a['id_actividad'] ?>"
                                        name="enlace"
                                        placeholder="https://..."
                                        value="<?= $entregada ? h($a['enlace']) : '' ?>"
                                    >
                                </div>

                                <div class="campo">
                                    <label for="archivo-<?= (int) $a['id_actividad'] ?>">
                                        Adjuntar archivo (opcional)
                                    </label>

                                    <input
                                        type="file"
                                        id="archivo-<?= (int) $a['id_actividad'] ?>"
                                        name="archivo"
                                        accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.jpg,.jpeg,.png,.zip"
                                    >

                                    <div class="nota-ayuda">
                                        Máximo 10 MB. Si no seleccionas otro archivo, se conserva el anterior.
                                    </div>
                                </div>
                            </div>

                            <?php if ($entregada && !empty($a['archivo'])): ?>
                                <div class="archivo-actual">
                                    Archivo actual:
                                    <a href="<?= h($a['archivo']) ?>" target="_blank" rel="noopener noreferrer">
                                        Ver archivo
                                    </a>
                                </div>
                            <?php endif; ?>

                            <button class="btn" type="submit">
                                <?= $entregada ? 'Actualizar entrega' : 'Enviar actividad' ?>
                            </button>
                        </form>

                        <?php if ($entregada): ?>
                            <form
                                method="post"
                                class="form-anular"
                                onsubmit="return confirm('¿Deseas anular este envío? Se eliminará la entrega actual y tendrás que enviarla nuevamente.');"
                            >
                                <input type="hidden" name="accion" value="anular">
                                <input
                                    type="hidden"
                                    name="id_actividad"
                                    value="<?= (int) $a['id_actividad'] ?>"
                                >

                                <button class="btn btn-anular" type="submit">
                                    Anular envío
                                </button>
                            </form>
                        <?php endif; ?>

                    <?php else: ?>

                        <p class="nota-ayuda">
                            El plazo de entrega terminó o la actividad fue cerrada.
                            Si necesitas una nueva oportunidad, comunícate con tu docente.
                        </p>

                    <?php endif; ?>

                </article>

            <?php endforeach; ?>

        </section>

    <?php endif; ?>

    <div class="pie">
        Semáforo Ambiental de la Huerta · Espacio académico
    </div>

</main>

</body>
</html>
