
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/conexion.php';

if (!isset($conexion) || !($conexion instanceof mysqli)) {
    die('No se pudo conectar con la base de datos.');
}

$conexion->set_charset('utf8mb4');

$rol = strtolower(trim($_SESSION['rol'] ?? ''));

if (
    !isset($_SESSION['id_usuario']) ||
    !in_array($rol, ['profesor', 'docente', 'administrador'], true)
) {
    header('Location: login.php');
    exit;
}

$idDocente = (int) $_SESSION['id_usuario'];
$nombreDocente = $_SESSION['nombre'] ?? 'Docente';
$mensaje = '';
$error = false;
$actividadEditar = null;

function h($valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

function fechaSQL($fecha): array
{
    $fecha = trim((string) $fecha);

    if ($fecha === '') {
        return [true, null];
    }

    $objeto = DateTime::createFromFormat('!Y-m-d\TH:i', $fecha);
    $errores = DateTime::getLastErrors();

    if (
        !$objeto ||
        ($errores !== false &&
        ($errores['warning_count'] > 0 || $errores['error_count'] > 0)) ||
        $objeto->format('Y-m-d\TH:i') !== $fecha
    ) {
        return [false, null];
    }

    return [true, $objeto->format('Y-m-d H:i:s')];
}

function volverPanel(): void
{
    header('Location: docente.php');
    exit;
}

if (empty($_SESSION['token_docente'])) {
    $_SESSION['token_docente'] = bin2hex(random_bytes(32));
}

$tokenCSRF = $_SESSION['token_docente'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenRecibido = $_POST['token_csrf'] ?? '';

    if (
        !is_string($tokenRecibido) ||
        !hash_equals($tokenCSRF, $tokenRecibido)
    ) {
        $mensaje = 'La solicitud no es válida. Recarga la página e inténtalo nuevamente.';
        $error = true;
    } else {
        $accion = $_POST['accion'] ?? '';

        if (in_array($accion, ['crear', 'actualizar'], true)) {
            $idActividad = (int) ($_POST['id_actividad'] ?? 0);
            $titulo = trim($_POST['titulo'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $fecha = trim($_POST['fecha_entrega'] ?? '');
            $puntaje = filter_var(
                $_POST['puntaje_maximo'] ?? '',
                FILTER_VALIDATE_FLOAT
            );

            [$fechaValida, $fechaSQL] = fechaSQL($fecha);

            if (
                $titulo === '' ||
                mb_strlen($titulo) > 180 ||
                $descripcion === '' ||
                $puntaje === false ||
                $puntaje <= 0 ||
                $puntaje > 100 ||
                !$fechaValida
            ) {
                $mensaje = 'Verifica los campos. El título es obligatorio, el puntaje debe estar entre 0 y 100 y la fecha debe ser válida.';
                $error = true;
            } else {
                if ($accion === 'crear') {
                    $stmt = $conexion->prepare("
                        INSERT INTO tbl_actividades
                        (id_docente, titulo, descripcion, fecha_entrega, puntaje_maximo, estado)
                        VALUES (?, ?, ?, ?, ?, 'publicada')
                    ");

                    $stmt->bind_param(
                        'isssd',
                        $idDocente,
                        $titulo,
                        $descripcion,
                        $fechaSQL,
                        $puntaje
                    );

                    if ($stmt->execute()) {
                        $stmt->close();
                        $_SESSION['mensaje_docente'] = 'La actividad se creó correctamente.';
                        volverPanel();
                    }

                    $stmt->close();
                    $mensaje = 'No se pudo crear la actividad.';
                    $error = true;
                } else {
                    $stmt = $conexion->prepare("
                        UPDATE tbl_actividades
                        SET titulo = ?,
                            descripcion = ?,
                            fecha_entrega = ?,
                            puntaje_maximo = ?
                        WHERE id_actividad = ?
                          AND id_docente = ?
                    ");

                    $stmt->bind_param(
                        'sssdii',
                        $titulo,
                        $descripcion,
                        $fechaSQL,
                        $puntaje,
                        $idActividad,
                        $idDocente
                    );

                    if ($stmt->execute() && $stmt->affected_rows >= 0) {
                        $stmt->close();
                        $_SESSION['mensaje_docente'] = 'La actividad se actualizó correctamente.';
                        volverPanel();
                    }

                    $stmt->close();
                    $mensaje = 'No se pudo actualizar la actividad.';
                    $error = true;
                }
            }
        }

        if ($accion === 'eliminar') {
            $idActividad = (int) ($_POST['id_actividad'] ?? 0);

            $stmt = $conexion->prepare("
                SELECT id_actividad
                FROM tbl_actividades
                WHERE id_actividad = ? AND id_docente = ?
                LIMIT 1
            ");

            $stmt->bind_param('ii', $idActividad, $idDocente);
            $stmt->execute();
            $existe = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$existe) {
                $mensaje = 'La actividad no existe o no tienes permiso para eliminarla.';
                $error = true;
            } else {
                $stmt = $conexion->prepare("
                    SELECT COUNT(*) AS total
                    FROM tbl_entregas
                    WHERE id_actividad = ?
                ");

                $stmt->bind_param('i', $idActividad);
                $stmt->execute();
                $resultado = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ((int) $resultado['total'] > 0) {
                    $mensaje = 'No se puede eliminar esta actividad porque tiene entregas de estudiantes registradas. Puedes cerrarla para impedir nuevas entregas.';
                    $error = true;
                } else {
                    $stmt = $conexion->prepare("
                        DELETE FROM tbl_actividades
                        WHERE id_actividad = ? AND id_docente = ?
                    ");

                    $stmt->bind_param('ii', $idActividad, $idDocente);

                    if ($stmt->execute() && $stmt->affected_rows === 1) {
                        $stmt->close();
                        $_SESSION['mensaje_docente'] = 'La actividad se eliminó correctamente.';
                        volverPanel();
                    }

                    $stmt->close();
                    $mensaje = 'No se pudo eliminar la actividad.';
                    $error = true;
                }
            }
        }

        if ($accion === 'cambiar_estado') {
            $idActividad = (int) ($_POST['id_actividad'] ?? 0);
            $nuevoEstado = $_POST['estado'] ?? '';

            if (in_array($nuevoEstado, ['publicada', 'cerrada'], true)) {
                $stmt = $conexion->prepare("
                    UPDATE tbl_actividades
                    SET estado = ?
                    WHERE id_actividad = ? AND id_docente = ?
                ");

                $stmt->bind_param(
                    'sii',
                    $nuevoEstado,
                    $idActividad,
                    $idDocente
                );

                if ($stmt->execute() && $stmt->affected_rows >= 0) {
                    $stmt->close();
                    $_SESSION['mensaje_docente'] = 'El estado de la actividad se actualizó.';
                    volverPanel();
                }

                $stmt->close();
                $mensaje = 'No se pudo actualizar el estado.';
                $error = true;
            }
        }

        if ($accion === 'calificar') {
            $idEntrega = (int) ($_POST['id_entrega'] ?? 0);
            $nota = filter_var(
                $_POST['nota'] ?? '',
                FILTER_VALIDATE_FLOAT
            );
            $observaciones = trim($_POST['observaciones'] ?? '');

            $stmt = $conexion->prepare("
                SELECT a.id_actividad, a.puntaje_maximo
                FROM tbl_entregas e
                INNER JOIN tbl_actividades a
                    ON a.id_actividad = e.id_actividad
                WHERE e.id_entrega = ?
                  AND a.id_docente = ?
                LIMIT 1
            ");

            $stmt->bind_param('ii', $idEntrega, $idDocente);
            $stmt->execute();
            $entrega = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (
                !$entrega ||
                $nota === false ||
                $nota < 0 ||
                $nota > (float) $entrega['puntaje_maximo']
            ) {
                $mensaje = 'Verifica la entrega y la nota. No puede superar el puntaje máximo.';
                $error = true;
            } else {
                $stmt = $conexion->prepare("
                    INSERT INTO tbl_calificaciones
                    (id_entrega, id_docente, nota, observaciones)
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        id_docente = VALUES(id_docente),
                        nota = VALUES(nota),
                        observaciones = VALUES(observaciones),
                        fecha_calificacion = CURRENT_TIMESTAMP
                ");

                $stmt->bind_param(
                    'iids',
                    $idEntrega,
                    $idDocente,
                    $nota,
                    $observaciones
                );

                if ($stmt->execute()) {
                    $stmt->close();
                    $_SESSION['mensaje_docente'] = 'La calificación se guardó correctamente.';
                    volverPanel();
                }

                $stmt->close();
                $mensaje = 'No se pudo guardar la calificación.';
                $error = true;
            }
        }
    }
}

if (isset($_SESSION['mensaje_docente'])) {
    $mensaje = $_SESSION['mensaje_docente'];
    unset($_SESSION['mensaje_docente']);
    $error = false;
}

if (isset($_GET['editar'])) {
    $idEditar = (int) $_GET['editar'];

    $stmt = $conexion->prepare("
        SELECT *
        FROM tbl_actividades
        WHERE id_actividad = ? AND id_docente = ?
        LIMIT 1
    ");

    $stmt->bind_param('ii', $idEditar, $idDocente);
    $stmt->execute();
    $actividadEditar = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$actividadEditar) {
        $mensaje = 'No se encontró la actividad que deseas editar.';
        $error = true;
    }
}

$stmt = $conexion->prepare("
    SELECT
        a.*,
        COUNT(DISTINCT e.id_entrega) AS total_entregas,
        COUNT(DISTINCT c.id_calificacion) AS total_calificadas
    FROM tbl_actividades a
    LEFT JOIN tbl_entregas e
        ON e.id_actividad = a.id_actividad
    LEFT JOIN tbl_calificaciones c
        ON c.id_entrega = e.id_entrega
    WHERE a.id_docente = ?
    GROUP BY a.id_actividad
    ORDER BY a.fecha_publicacion DESC
");

$stmt->bind_param('i', $idDocente);
$stmt->execute();
$actividades = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $conexion->prepare("
    SELECT
        e.id_entrega,
        e.id_actividad,
        e.respuesta,
        e.enlace,
        e.archivo,
        e.fecha_entrega AS fecha_envio,
        u.nombre_completo,
        a.titulo,
        a.puntaje_maximo,
        a.estado AS estado_actividad,
        c.nota,
        c.observaciones,
        c.fecha_calificacion
    FROM tbl_entregas e
    INNER JOIN tbl_actividades a
        ON a.id_actividad = e.id_actividad
    INNER JOIN tbl_usuarios u
        ON u.id_usuario = e.id_estudiante
    LEFT JOIN tbl_calificaciones c
        ON c.id_entrega = e.id_entrega
    WHERE a.id_docente = ?
    ORDER BY e.fecha_entrega DESC
");

$stmt->bind_param('i', $idDocente);
$stmt->execute();
$entregas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$totalEntregas = count($entregas);
$totalCalificadas = 0;

foreach ($entregas as $entrega) {
    if ($entrega['nota'] !== null) {
        $totalCalificadas++;
    }
}

$fechaFormulario = '';

if (!empty($actividadEditar['fecha_entrega'])) {
    $fechaFormulario = date(
        'Y-m-d\TH:i',
        strtotime($actividadEditar['fecha_entrega'])
    );
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panel docente | Semáforo Ambiental</title>

<style>
:root {
    --verde: #287343;
    --oscuro: #194d2c;
    --fondo: #f4f7f4;
    --borde: #dfe8e0;
    --texto: #25352a;
    --gris: #66736a;
    --blanco: #fff;
    --rojo: #a52c2c;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: var(--fondo);
    color: var(--texto);
    font-family: Arial, Helvetica, sans-serif;
    line-height: 1.5;
}

header {
    background: var(--oscuro);
    color: white;
    padding: 16px max(15px, calc((100% - 1150px) / 2));
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

header strong {
    font-size: 19px;
}

header small {
    display: block;
    opacity: .85;
}

a {
    color: var(--verde);
    overflow-wrap: anywhere;
}

.boton {
    display: inline-block;
    border: 0;
    border-radius: 7px;
    padding: 10px 13px;
    background: var(--verde);
    color: white;
    font-size: 13px;
    font-weight: bold;
    text-decoration: none;
    cursor: pointer;
    font-family: inherit;
}

.boton:hover {
    opacity: .9;
}

.boton-secundario {
    background: white;
    color: var(--oscuro);
    border: 1px solid var(--borde);
}

.boton-peligro {
    background: #a52c2c;
    color: white;
}

.contenedor {
    max-width: 1150px;
    width: calc(100% - 30px);
    margin: 25px auto 45px;
}

h1 {
    font-size: clamp(25px, 4vw, 32px);
    margin-bottom: 5px;
}

.subtitulo {
    color: var(--gris);
    margin-top: 0;
}

.resumen {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
    margin: 22px 0;
}

.tarjeta,
.panel {
    background: white;
    border: 1px solid var(--borde);
    border-radius: 11px;
    padding: 18px;
    box-shadow: 0 4px 15px rgba(25, 77, 44, .05);
    min-width: 0;
}

.tarjeta span {
    color: var(--gris);
    display: block;
    font-size: 13px;
}

.tarjeta strong {
    display: block;
    font-size: 27px;
    color: var(--oscuro);
}

.panel {
    margin-bottom: 20px;
}

.panel h2 {
    margin-top: 0;
    font-size: 20px;
}

label {
    display: block;
    margin-bottom: 6px;
    font-size: 13px;
    font-weight: bold;
}

input,
textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #cbd8cd;
    border-radius: 7px;
    font: inherit;
    background: white;
    color: var(--texto);
}

textarea {
    min-height: 95px;
    resize: vertical;
}

.campo {
    margin-bottom: 13px;
}

.dos-columnas {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}

.mensaje {
    padding: 12px 15px;
    border-radius: 8px;
    background: #e6f5e9;
    color: #205d30;
    margin-bottom: 17px;
}

.mensaje.error {
    background: #fff0f0;
    color: var(--rojo);
}

.actividad,
.entrega {
    background: white;
    border: 1px solid var(--borde);
    border-radius: 10px;
    padding: 16px;
    margin-top: 12px;
    min-width: 0;
}

.actividad h3,
.entrega h3 {
    margin: 0 0 8px;
    font-size: 17px;
    overflow-wrap: anywhere;
}

.meta {
    font-size: 13px;
    color: var(--gris);
    display: flex;
    flex-wrap: wrap;
    gap: 7px 14px;
    margin: 10px 0;
}

.estado {
    display: inline-block;
    border-radius: 20px;
    padding: 4px 9px;
    background: #e6f5e9;
    color: #205d30;
    font-size: 12px;
    font-weight: bold;
}

.estado.cerrada {
    background: #fbe7e7;
    color: #922d2d;
}

.respuesta {
    white-space: pre-wrap;
    overflow-wrap: anywhere;
}

.acciones {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 13px;
    align-items: center;
}

.acciones form {
    margin: 0;
}

.form-calificar {
    margin-top: 14px;
    padding-top: 14px;
    border-top: 1px solid var(--borde);
}

.sin-datos {
    padding: 20px;
    text-align: center;
    color: var(--gris);
    background: white;
    border: 1px dashed #cbd8cd;
    border-radius: 9px;
}

details {
    margin-top: 12px;
}

summary {
    cursor: pointer;
    color: var(--verde);
    font-weight: bold;
}

.separador {
    border: 0;
    border-top: 1px solid var(--borde);
    margin: 18px 0;
}

@media (max-width: 650px) {
    .resumen,
    .dos-columnas {
        grid-template-columns: 1fr;
    }

    .contenedor {
        width: calc(100% - 20px);
        margin-top: 17px;
    }

    .panel,
    .tarjeta {
        padding: 14px;
    }

    header {
        padding: 14px;
    }
}
</style>
</head>

<body>

<header>
    <div>
        <strong>Semáforo Ambiental</strong>
        <small>Panel de gestión docente</small>
    </div>

    <div class="acciones">
        <a class="boton boton-secundario" href="index.php">Inicio</a>
        <a class="boton boton-secundario" href="logout.php">Cerrar sesión</a>
    </div>
</header>

<main class="contenedor">

    <h1>Panel docente</h1>

    <p class="subtitulo">
        Bienvenido/a, <?= h($nombreDocente) ?>. Administra tus actividades,
        revisa las entregas y registra las calificaciones.
    </p>

    <?php if ($mensaje !== ''): ?>
        <div class="mensaje <?= $error ? 'error' : '' ?>">
            <?= h($mensaje) ?>
        </div>
    <?php endif; ?>

    <section class="resumen">
        <div class="tarjeta">
            <span>Mis actividades</span>
            <strong><?= count($actividades) ?></strong>
        </div>

        <div class="tarjeta">
            <span>Entregas recibidas</span>
            <strong><?= $totalEntregas ?></strong>
        </div>

        <div class="tarjeta">
            <span>Entregas calificadas</span>
            <strong><?= $totalCalificadas ?></strong>
        </div>
    </section>

    <section class="panel" id="formulario-actividad">
        <h2>
            <?= $actividadEditar ? 'Editar actividad' : 'Crear nueva actividad' ?>
        </h2>

        <form method="post" action="docente.php">
            <input
                type="hidden"
                name="token_csrf"
                value="<?= h($tokenCSRF) ?>"
            >

            <input
                type="hidden"
                name="accion"
                value="<?= $actividadEditar ? 'actualizar' : 'crear' ?>"
            >

            <?php if ($actividadEditar): ?>
                <input
                    type="hidden"
                    name="id_actividad"
                    value="<?= (int) $actividadEditar['id_actividad'] ?>"
                >
            <?php endif; ?>

            <div class="campo">
                <label for="titulo">Título de la actividad</label>
                <input
                    type="text"
                    id="titulo"
                    name="titulo"
                    maxlength="180"
                    value="<?= h($actividadEditar['titulo'] ?? '') ?>"
                    placeholder="Ej. Factores ambientales de la huerta"
                    required
                >
            </div>

            <div class="campo">
                <label for="descripcion">Descripción e instrucciones</label>
                <textarea
                    id="descripcion"
                    name="descripcion"
                    required
                    placeholder="Explica qué debe realizar el estudiante..."
                ><?= h($actividadEditar['descripcion'] ?? '') ?></textarea>
            </div>

            <div class="dos-columnas">
                <div class="campo">
                    <label for="fecha_entrega">Fecha y hora límite (opcional)</label>
                    <input
                        type="datetime-local"
                        id="fecha_entrega"
                        name="fecha_entrega"
                        value="<?= h($fechaFormulario) ?>"
                    >
                </div>

                <div class="campo">
                    <label for="puntaje_maximo">Puntaje máximo</label>
                    <input
                        type="number"
                        id="puntaje_maximo"
                        name="puntaje_maximo"
                        min="0.01"
                        max="100"
                        step="0.01"
                        value="<?= h($actividadEditar['puntaje_maximo'] ?? '5') ?>"
                        required
                    >
                </div>
            </div>

            <div class="acciones">
                <button class="boton" type="submit">
                    <?= $actividadEditar ? 'Guardar cambios' : 'Publicar actividad' ?>
                </button>

                <?php if ($actividadEditar): ?>
                    <a class="boton boton-secundario" href="docente.php">
                        Cancelar edición
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="panel">
        <h2>Administrar mis actividades</h2>

        <?php if (!$actividades): ?>
            <div class="sin-datos">
                Todavía no has creado actividades.
            </div>
        <?php else: ?>

            <?php foreach ($actividades as $a): ?>
                <article class="actividad">
                    <h3><?= h($a['titulo']) ?></h3>

                    <p class="respuesta"><?= h($a['descripcion']) ?></p>

                    <div class="meta">
                        <span>
                            Fecha límite:
                            <?= $a['fecha_entrega']
                                ? h(date('d/m/Y H:i', strtotime($a['fecha_entrega'])))
                                : 'Sin fecha límite' ?>
                        </span>

                        <span>
                            Puntaje máximo: <?= h($a['puntaje_maximo']) ?>
                        </span>

                        <span>
                            Entregas: <?= (int) $a['total_entregas'] ?>
                        </span>

                        <span>
                            Calificadas: <?= (int) $a['total_calificadas'] ?>
                        </span>

                        <span class="estado <?= $a['estado'] === 'cerrada' ? 'cerrada' : '' ?>">
                            <?= h(ucfirst($a['estado'])) ?>
                        </span>
                    </div>

                    <div class="acciones">
                        <a
                            class="boton"
                            href="docente.php?editar=<?= (int) $a['id_actividad'] ?>#formulario-actividad"
                        >
                            Editar
                        </a>

                        <form method="post" action="docente.php">
                            <input type="hidden" name="token_csrf" value="<?= h($tokenCSRF) ?>">
                            <input type="hidden" name="accion" value="cambiar_estado">
                            <input type="hidden" name="id_actividad" value="<?= (int) $a['id_actividad'] ?>">
                            <input
                                type="hidden"
                                name="estado"
                                value="<?= $a['estado'] === 'publicada' ? 'cerrada' : 'publicada' ?>"
                            >

                            <button
                                class="boton boton-secundario"
                                type="submit"
                            >
                                <?= $a['estado'] === 'publicada' ? 'Cerrar actividad' : 'Volver a publicar' ?>
                            </button>
                        </form>

                        <form
                            method="post"
                            action="docente.php"
                            onsubmit="return confirm('¿Deseas eliminar esta actividad? Esta acción no se puede deshacer.');"
                        >
                            <input type="hidden" name="token_csrf" value="<?= h($tokenCSRF) ?>">
                            <input type="hidden" name="accion" value="eliminar">
                            <input type="hidden" name="id_actividad" value="<?= (int) $a['id_actividad'] ?>">

                            <button class="boton boton-peligro" type="submit">
                                Eliminar
                            </button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>

        <?php endif; ?>
    </section>

    <section class="panel">
        <h2>Entregas de los estudiantes</h2>

        <?php if (!$entregas): ?>
            <div class="sin-datos">
                Aún no se han recibido entregas de tus actividades.
            </div>
        <?php else: ?>

            <?php foreach ($entregas as $e): ?>
                <article class="entrega">
                    <h3><?= h($e['titulo']) ?></h3>

                    <div class="meta">
                        <span>
                            Estudiante: <?= h($e['nombre_completo']) ?>
                        </span>

                        <span>
                            Fecha de entrega:
                            <?= h(date('d/m/Y H:i', strtotime($e['fecha_envio']))) ?>
                        </span>

                        <span>
                            Nota:
                            <?= $e['nota'] !== null
                                ? h($e['nota'])
                                : 'Pendiente de calificar' ?>
                        </span>
                    </div>

                    <?php if (!empty($e['respuesta'])): ?>
                        <p><strong>Respuesta del estudiante</strong></p>
                        <div class="respuesta"><?= h($e['respuesta']) ?></div>
                    <?php endif; ?>

                    <?php if (!empty($e['enlace'])): ?>
                        <p>
                            <strong>Enlace:</strong>
                            <a
                                href="<?= h($e['enlace']) ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                Abrir entrega
                            </a>
                        </p>
                    <?php endif; ?>

                    <?php if (!empty($e['archivo'])): ?>
                        <p>
                            <strong>Archivo:</strong>
                            <a
                                href="<?= h($e['archivo']) ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                Abrir archivo adjunto
                            </a>
                        </p>
                    <?php endif; ?>

                    <details>
                        <summary>
                            <?= $e['nota'] !== null ? 'Editar calificación' : 'Calificar entrega' ?>
                        </summary>

                        <form method="post" action="docente.php" class="form-calificar">
                            <input type="hidden" name="token_csrf" value="<?= h($tokenCSRF) ?>">
                            <input type="hidden" name="accion" value="calificar">
                            <input type="hidden" name="id_entrega" value="<?= (int) $e['id_entrega'] ?>">

                            <div class="campo">
                                <label for="nota-<?= (int) $e['id_entrega'] ?>">
                                    Nota (máximo <?= h($e['puntaje_maximo']) ?>)
                                </label>

                                <input
                                    type="number"
                                    id="nota-<?= (int) $e['id_entrega'] ?>"
                                    name="nota"
                                    min="0"
                                    max="<?= h($e['puntaje_maximo']) ?>"
                                    step="0.01"
                                    value="<?= $e['nota'] !== null ? h($e['nota']) : '' ?>"
                                    required
                                >
                            </div>

                            <div class="campo">
                                <label for="obs-<?= (int) $e['id_entrega'] ?>">
                                    Observaciones del docente
                                </label>

                                <textarea
                                    id="obs-<?= (int) $e['id_entrega'] ?>"
                                    name="observaciones"
                                    placeholder="Escribe recomendaciones o retroalimentación..."
                                ><?= h($e['observaciones']) ?></textarea>
                            </div>

                            <button class="boton" type="submit">
                                Guardar calificación
                            </button>
                        </form>
                    </details>
                </article>
            <?php endforeach; ?>

        <?php endif; ?>
    </section>

</main>

</body>
</html>
