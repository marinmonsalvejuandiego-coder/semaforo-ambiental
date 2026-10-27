<?php
session_start();
require_once __DIR__ . '/config/conexion.php';

if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}

$idUsuario = (int) $_SESSION['id_usuario'];
$rol = strtolower(trim((string) ($_SESSION['rol'] ?? '')));

if ($idUsuario <= 0 || !in_array($rol, ['administrador', 'profesor'], true)) {
    header('Location: login.php');
    exit;
}

if (!isset($conexion) || !($conexion instanceof mysqli)) {
    exit('No se pudo establecer la conexión con la base de datos.');
}

$conexion->set_charset('utf8mb4');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function e($valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

function redirigir(string $seccion, string $mensaje): void
{
    header('Location: admin.php?seccion=' . urlencode($seccion) . '&mensaje=' . urlencode($mensaje));
    exit;
}

function campoTexto(string $nombre): string
{
    return trim((string) ($_POST[$nombre] ?? ''));
}

function consultarTodos(mysqli $conexion, string $sql): array
{
    $resultado = $conexion->query($sql);
    return $resultado->fetch_all(MYSQLI_ASSOC);
}

$seccion = (string) ($_GET['seccion'] ?? 'inicio');

$modulos = [
    'galeria' => ['tabla' => 'tbl_galeria', 'pk' => 'id_galeria', 'nombre' => 'Galería'],
    'servicios' => ['tabla' => 'tbl_servicios', 'pk' => 'id_servicio', 'nombre' => 'Servicios'],
    'novedades' => ['tabla' => 'tbl_novedades', 'pk' => 'id_novedad', 'nombre' => 'Novedades']
];

if (!isset($_SESSION['token_admin'])) {
    $_SESSION['token_admin'] = bin2hex(random_bytes(32));
}

$carpetaImagenes = __DIR__ . '/imagenes/';
if (!is_dir($carpetaImagenes) && !mkdir($carpetaImagenes, 0755, true) && !is_dir($carpetaImagenes)) {
    exit('No se pudo crear la carpeta imagenes. Créala manualmente dentro del proyecto.');
}

function guardarImagen(string $campo, string $carpeta): ?string
{
    if (!isset($_FILES[$campo]) || $_FILES[$campo]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $archivo = $_FILES[$campo];

    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('La subida de la imagen falló. Código: ' . (int)$archivo['error']);
    }
    if ((int)$archivo['size'] <= 0 || (int)$archivo['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('La imagen debe pesar más de 0 bytes y máximo 5 MB.');
    }

    $info = new finfo(FILEINFO_MIME_TYPE);
    $mime = $info->file($archivo['tmp_name']);
    $extensiones = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif'
    ];

    if (!isset($extensiones[$mime]) || @getimagesize($archivo['tmp_name']) === false) {
        throw new RuntimeException('Selecciona una imagen JPG, PNG, WEBP o GIF válida.');
    }

    $nombre = 'admin_' . bin2hex(random_bytes(12)) . '.' . $extensiones[$mime];
    if (!move_uploaded_file($archivo['tmp_name'], $carpeta . $nombre)) {
        throw new RuntimeException('No se pudo guardar la imagen. Comprueba que la carpeta imagenes tenga permisos de escritura.');
    }

    return $nombre;
}

function borrarImagen(string $nombre, string $carpeta): void
{
    $nombre = basename($nombre);
    // Solo elimina archivos creados por este panel; no borra las imágenes precargadas.
    if (str_starts_with($nombre, 'admin_')) {
        $ruta = $carpeta . $nombre;
        if (is_file($ruta)) {
            @unlink($ruta);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals((string)$_SESSION['token_admin'], (string)($_POST['token'] ?? ''))) {
        http_response_code(403);
        exit('La solicitud no es válida. Recarga la página e inténtalo de nuevo.');
    }

    $tipo = (string)($_POST['tipo'] ?? '');
    $operacion = (string)($_POST['operacion'] ?? '');
    $id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
    $imagenNueva = null;

    try {
        if ($tipo === 'contacto' && $operacion === 'eliminar') {
            if ($id <= 0) {
                throw new RuntimeException('El mensaje seleccionado no es válido.');
            }
            $stmt = $conexion->prepare('DELETE FROM tbl_contactos WHERE id_contacto = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $afectadas = $stmt->affected_rows;
            $stmt->close();
            redirigir('contactos', $afectadas ? 'Mensaje eliminado correctamente.' : 'No se encontró el mensaje seleccionado.');
        }

        if (isset($modulos[$tipo])) {
            $modulo = $modulos[$tipo];
            $tabla = $modulo['tabla'];
            $pk = $modulo['pk'];

            if (in_array($operacion, ['crear', 'editar'], true)) {
                $titulo = campoTexto('titulo');
                $descripcion = campoTexto('descripcion');
                $activo = isset($_POST['activo']) ? 1 : 0;

                if ($titulo === '' || $descripcion === '') {
                    throw new RuntimeException('El título y la descripción son obligatorios.');
                }

                $imagenNueva = guardarImagen('imagen', $carpetaImagenes);

                if ($operacion === 'crear') {
                    if ($imagenNueva === null) {
                        throw new RuntimeException('Selecciona una imagen para crear el registro.');
                    }

                    if ($tipo === 'novedades') {
                        $fecha = date('Y-m-d');
                        $stmt = $conexion->prepare(
                            'INSERT INTO tbl_novedades (id_usuario, titulo, descripcion, fecha_publicacion, imagen, activo)
                             VALUES (?, ?, ?, ?, ?, ?)'
                        );
                        $stmt->bind_param('issssi', $idUsuario, $titulo, $descripcion, $fecha, $imagenNueva, $activo);
                    } else {
                        $stmt = $conexion->prepare(
                            "INSERT INTO `$tabla` (id_usuario, titulo, descripcion, imagen, activo)
                             VALUES (?, ?, ?, ?, ?)"
                        );
                        $stmt->bind_param('isssi', $idUsuario, $titulo, $descripcion, $imagenNueva, $activo);
                    }

                    $stmt->execute();
                    $stmt->close();
                    redirigir($tipo, 'Registro creado correctamente.');
                }

                if ($id <= 0) {
                    throw new RuntimeException('El registro seleccionado no es válido.');
                }

                $stmt = $conexion->prepare("SELECT imagen FROM `$tabla` WHERE `$pk` = ?");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $anterior = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$anterior) {
                    throw new RuntimeException('No se encontró el registro que intentas editar.');
                }

                if ($imagenNueva !== null) {
                    $stmt = $conexion->prepare(
                        "UPDATE `$tabla` SET titulo = ?, descripcion = ?, imagen = ?, activo = ? WHERE `$pk` = ?"
                    );
                    $stmt->bind_param('sssii', $titulo, $descripcion, $imagenNueva, $activo, $id);
                } else {
                    $stmt = $conexion->prepare(
                        "UPDATE `$tabla` SET titulo = ?, descripcion = ?, activo = ? WHERE `$pk` = ?"
                    );
                    $stmt->bind_param('ssii', $titulo, $descripcion, $activo, $id);
                }

                $stmt->execute();
                $stmt->close();

                if ($imagenNueva !== null) {
                    borrarImagen((string)($anterior['imagen'] ?? ''), $carpetaImagenes);
                }
                redirigir($tipo, 'Registro actualizado correctamente.');
            }

            if ($operacion === 'eliminar') {
                if ($id <= 0) {
                    throw new RuntimeException('El registro seleccionado no es válido.');
                }

                $stmt = $conexion->prepare("SELECT imagen FROM `$tabla` WHERE `$pk` = ?");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $registro = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$registro) {
                    throw new RuntimeException('No se encontró el registro que intentas eliminar.');
                }

                $stmt = $conexion->prepare("DELETE FROM `$tabla` WHERE `$pk` = ?");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $stmt->close();

                borrarImagen((string)($registro['imagen'] ?? ''), $carpetaImagenes);
                redirigir($tipo, 'Registro eliminado correctamente.');
            }

            throw new RuntimeException('La operación solicitada no es válida.');
        }

        if ($tipo === 'usuario' && $rol === 'administrador') {
            $nombre = campoTexto('nombre_completo');
            $correo = campoTexto('correo');
            $clave = (string)($_POST['password'] ?? '');
            $rolUsuario = (string)($_POST['rol'] ?? 'estudiante');

            if ($nombre === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Ingresa un nombre y un correo válido.');
            }
            if (!in_array($rolUsuario, ['administrador', 'profesor', 'estudiante'], true)) {
                throw new RuntimeException('El rol seleccionado no es válido.');
            }

            if ($operacion === 'crear') {
                if (strlen($clave) < 8) {
                    throw new RuntimeException('La contraseña debe tener al menos 8 caracteres.');
                }
                $hash = password_hash($clave, PASSWORD_DEFAULT);
                $stmt = $conexion->prepare(
                    'INSERT INTO tbl_usuarios (nombre_completo, correo, password, rol) VALUES (?, ?, ?, ?)'
                );
                $stmt->bind_param('ssss', $nombre, $correo, $hash, $rolUsuario);
                $stmt->execute();
                $stmt->close();
                redirigir('usuarios', 'Usuario creado correctamente.');
            }

            if ($operacion === 'editar' && $id > 0) {
                if ($id === $idUsuario && $rolUsuario !== 'administrador') {
                    throw new RuntimeException('No puedes quitarte tu propio rol de administrador.');
                }

                if ($clave !== '') {
                    if (strlen($clave) < 8) {
                        throw new RuntimeException('La contraseña debe tener al menos 8 caracteres.');
                    }
                    $hash = password_hash($clave, PASSWORD_DEFAULT);
                    $stmt = $conexion->prepare(
                        'UPDATE tbl_usuarios SET nombre_completo = ?, correo = ?, password = ?, rol = ? WHERE id_usuario = ?'
                    );
                    $stmt->bind_param('ssssi', $nombre, $correo, $hash, $rolUsuario, $id);
                } else {
                    $stmt = $conexion->prepare(
                        'UPDATE tbl_usuarios SET nombre_completo = ?, correo = ?, rol = ? WHERE id_usuario = ?'
                    );
                    $stmt->bind_param('sssi', $nombre, $correo, $rolUsuario, $id);
                }
                $stmt->execute();
                $stmt->close();

                if ($id === $idUsuario) {
                    $_SESSION['nombre_completo'] = $nombre;
                    $_SESSION['rol'] = $rolUsuario;
                }
                redirigir('usuarios', 'Usuario actualizado correctamente.');
            }

            if ($operacion === 'eliminar' && $id > 0) {
                if ($id === $idUsuario) {
                    throw new RuntimeException('No puedes eliminar tu propia cuenta.');
                }
                $stmt = $conexion->prepare('DELETE FROM tbl_usuarios WHERE id_usuario = ?');
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $stmt->close();
                redirigir('usuarios', 'Usuario eliminado correctamente.');
            }

            throw new RuntimeException('La operación de usuario no es válida.');
        }

        throw new RuntimeException('No tienes permiso para realizar esta operación.');
    } catch (Throwable $ex) {
        if ($imagenNueva !== null) {
            borrarImagen($imagenNueva, $carpetaImagenes);
        }

        $destino = isset($modulos[$tipo]) ? $tipo
            : ($tipo === 'usuario' ? 'usuarios'
            : ($tipo === 'contacto' ? 'contactos' : 'inicio'));

        if ($ex instanceof mysqli_sql_exception && (int)$ex->getCode() === 1062) {
            $mensajeError = 'Ese correo ya está registrado.';
        } elseif ($ex instanceof mysqli_sql_exception && (int)$ex->getCode() === 1451) {
            $mensajeError = 'No se puede eliminar este registro porque tiene información relacionada en otras tablas.';
        } else {
            $mensajeError = $ex->getMessage();
        }
        redirigir($destino, 'Error: ' . $mensajeError);
    }
}

$nombreAdmin = $_SESSION['nombre_completo'] ?? 'Usuario';
$mensaje = (string)($_GET['mensaje'] ?? '');
$accion = (string)($_GET['accion'] ?? 'listar');
$idEditar = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;

$titulos = [
    'inicio' => 'Resumen general',
    'galeria' => 'Galería',
    'servicios' => 'Servicios',
    'novedades' => 'Novedades',
    'contactos' => 'Mensajes de contacto',
    'usuarios' => 'Usuarios',
    'actividades' => 'Actividades',
    'entregas' => 'Entregas',
    'calificaciones' => 'Calificaciones'
];

if (!isset($titulos[$seccion]) || ($seccion === 'usuarios' && $rol !== 'administrador')) {
    $seccion = 'inicio';
}

$contadores = [];
foreach ([
    'galeria' => 'tbl_galeria',
    'servicios' => 'tbl_servicios',
    'novedades' => 'tbl_novedades',
    'contactos' => 'tbl_contactos',
    'usuarios' => 'tbl_usuarios',
    'actividades' => 'tbl_actividades',
    'entregas' => 'tbl_entregas',
    'calificaciones' => 'tbl_calificaciones'
] as $clave => $tabla) {
    $resultado = $conexion->query("SELECT COUNT(*) AS total FROM `$tabla`");
    $contadores[$clave] = (int)$resultado->fetch_assoc()['total'];
}

$editar = null;
$lista = [];
$tablaActual = '';
$pkActual = '';

if (isset($modulos[$seccion])) {
    $tablaActual = $modulos[$seccion]['tabla'];
    $pkActual = $modulos[$seccion]['pk'];

    if ($accion === 'editar' && $idEditar > 0) {
        $stmt = $conexion->prepare("SELECT * FROM `$tablaActual` WHERE `$pkActual` = ?");
        $stmt->bind_param('i', $idEditar);
        $stmt->execute();
        $editar = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$editar) {
            $mensaje = 'No se encontró el registro seleccionado.';
            $accion = 'listar';
        }
    }
    $lista = consultarTodos($conexion, "SELECT * FROM `$tablaActual` ORDER BY `$pkActual` DESC");
} elseif ($seccion === 'contactos') {
    $lista = consultarTodos($conexion, 'SELECT * FROM tbl_contactos ORDER BY id_contacto DESC');
} elseif ($seccion === 'usuarios' && $rol === 'administrador') {
    $lista = consultarTodos(
        $conexion,
        'SELECT id_usuario, nombre_completo, correo, rol, fecha_registro FROM tbl_usuarios ORDER BY id_usuario DESC'
    );
    if ($accion === 'editar' && $idEditar > 0) {
        $stmt = $conexion->prepare(
            'SELECT id_usuario, nombre_completo, correo, rol FROM tbl_usuarios WHERE id_usuario = ?'
        );
        $stmt->bind_param('i', $idEditar);
        $stmt->execute();
        $editar = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$editar) {
            $mensaje = 'No se encontró el usuario seleccionado.';
            $accion = 'listar';
        }
    }
} elseif (in_array($seccion, ['actividades', 'entregas', 'calificaciones'], true)) {
    $consultas = [
        'actividades' => 'SELECT a.*, u.nombre_completo AS docente FROM tbl_actividades a INNER JOIN tbl_usuarios u ON u.id_usuario = a.id_docente ORDER BY a.id_actividad DESC',
        'entregas' => 'SELECT e.*, a.titulo AS actividad, u.nombre_completo AS estudiante FROM tbl_entregas e INNER JOIN tbl_actividades a ON a.id_actividad = e.id_actividad INNER JOIN tbl_usuarios u ON u.id_usuario = e.id_estudiante ORDER BY e.id_entrega DESC',
        'calificaciones' => 'SELECT c.*, u.nombre_completo AS docente, e.id_actividad, e.id_estudiante FROM tbl_calificaciones c INNER JOIN tbl_usuarios u ON u.id_usuario = c.id_docente INNER JOIN tbl_entregas e ON e.id_entrega = c.id_entrega ORDER BY c.id_calificacion DESC'
    ];
    $lista = consultarTodos($conexion, $consultas[$seccion]);
}

$tipoFormulario = $seccion;
$editando = $accion === 'editar' && is_array($editar);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titulos[$seccion]) ?> | Semáforo Ambiental</title>
<style>
:root{--verde:#286b3a;--oscuro:#1b4729;--fondo:#f4f7f4;--borde:#dce5dd;--texto:#243329;--gris:#6b776e;--blanco:#fff}
*{box-sizing:border-box}
body{margin:0;font-family:Arial,sans-serif;background:var(--fondo);color:var(--texto);font-size:14px}
a{text-decoration:none;color:inherit}button,input,textarea,select{font:inherit}
.layout{min-height:100vh;display:grid;grid-template-columns:220px minmax(0,1fr)}
.sidebar{background:var(--oscuro);color:#fff;padding:20px 13px}
.brand{font-size:18px;font-weight:bold;line-height:1.4;padding:5px 8px 20px}
.brand small{display:block;font-size:11px;font-weight:normal;color:#d5e7d8}
.nav{display:grid;gap:5px}.nav a{display:block;padding:11px;border-radius:7px;color:#eaf4eb}
.nav a:hover,.nav a.active{background:#ffffff20}
.main{padding:23px;min-width:0}.top{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:20px}
h1{font-size:25px;margin:0 0 5px}h2{font-size:17px;margin:0 0 16px}
.muted{color:var(--gris);margin:0;line-height:1.5}
.user{background:white;border:1px solid var(--borde);border-radius:8px;padding:10px 12px}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:5px;padding:9px 12px;border:0;border-radius:7px;background:var(--verde);color:#fff;cursor:pointer;font-weight:bold;font-size:13px}
.btn:hover{filter:brightness(.94)}.btn.light{background:#eaf0eb;color:var(--texto)}.btn.danger{background:#b42318}.btn.outline{background:#fff;color:var(--oscuro);border:1px solid var(--borde)}
.top-actions,.actions{display:flex;align-items:center;gap:7px;flex-wrap:wrap}
.panel,.stat{background:#fff;border:1px solid var(--borde);border-radius:11px;padding:17px;margin-bottom:17px;min-width:0}
.stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:17px}
.stat span{display:block;color:var(--gris);font-size:12px;margin-bottom:8px}.stat strong{font-size:26px;color:var(--verde)}
.notice{padding:12px 14px;border-radius:8px;background:#e5f4e8;color:#205b2e;margin-bottom:16px;overflow-wrap:anywhere}.notice.error{background:#fff0ef;color:#a51d16}
.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:13px}.field{display:grid;gap:6px;min-width:0}.field.full{grid-column:1/-1}
.field label{font-size:12px;font-weight:bold}.field input,.field textarea,.field select{width:100%;padding:10px;border:1px solid #cbd8cd;border-radius:7px;background:#fff;color:var(--texto)}
.field textarea{min-height:85px;resize:vertical}.field input[type=file]{font-size:12px}
.current-image{width:100px;height:75px;object-fit:cover;border-radius:7px}.check{display:flex;align-items:center;gap:8px}
.tablewrap{width:100%;overflow-x:auto}table{width:100%;border-collapse:collapse;min-width:590px}
th,td{text-align:left;padding:11px 9px;border-bottom:1px solid #e6ece7;vertical-align:middle;overflow-wrap:anywhere}
th{font-size:11px;text-transform:uppercase;color:#536156;background:#f7faf7;white-space:nowrap}td{font-size:13px;line-height:1.45}
td img{width:55px;height:45px;object-fit:cover;border-radius:5px}td form{margin:0}
.badge{display:inline-block;padding:4px 7px;border-radius:5px;background:#e7f4e9;color:#256437;font-size:11px}.badge.off{background:#f0f0f0;color:#555}
.empty{text-align:center;color:var(--gris);padding:22px}.actions form{display:inline-flex}.small{font-size:12px;color:var(--gris)}
@media(max-width:1000px){.layout{grid-template-columns:1fr}.sidebar{padding:13px}.brand{padding:2px 5px 12px}.nav{grid-template-columns:repeat(3,minmax(0,1fr))}.main{padding:16px}.stats{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:560px){.main{padding:11px}.top{align-items:flex-start}.top-actions{width:100%}.nav{grid-template-columns:repeat(2,minmax(0,1fr))}.stats{gap:8px}.stat{padding:12px}.stat strong{font-size:23px}.panel{padding:12px}.form-grid{grid-template-columns:1fr}.field.full{grid-column:auto}h1{font-size:22px}.top-actions .user{width:100%}}
</style>
</head>
<body>
<div class="layout">
<aside class="sidebar">
    <div class="brand">Semáforo Ambiental<small>Panel de administración</small></div>
    <nav class="nav">
        <a class="<?= $seccion === 'inicio' ? 'active' : '' ?>" href="admin.php">Inicio</a>
        <a class="<?= $seccion === 'galeria' ? 'active' : '' ?>" href="admin.php?seccion=galeria">Galería</a>
        <a class="<?= $seccion === 'servicios' ? 'active' : '' ?>" href="admin.php?seccion=servicios">Servicios</a>
        <a class="<?= $seccion === 'novedades' ? 'active' : '' ?>" href="admin.php?seccion=novedades">Novedades</a>
        <a class="<?= $seccion === 'contactos' ? 'active' : '' ?>" href="admin.php?seccion=contactos">Contactos</a>
        <?php if ($rol === 'administrador'): ?><a class="<?= $seccion === 'usuarios' ? 'active' : '' ?>" href="admin.php?seccion=usuarios">Usuarios</a><?php endif; ?>
        <a class="<?= $seccion === 'actividades' ? 'active' : '' ?>" href="admin.php?seccion=actividades">Actividades</a>
        <a class="<?= $seccion === 'entregas' ? 'active' : '' ?>" href="admin.php?seccion=entregas">Entregas</a>
        <a class="<?= $seccion === 'calificaciones' ? 'active' : '' ?>" href="admin.php?seccion=calificaciones">Calificaciones</a>
        <a href="index.php" target="_blank" rel="noopener noreferrer">Ver sitio web ↗</a>
        <a href="logout.php">Cerrar sesión</a>
    </nav>
</aside>
<main class="main">
<header class="top">
    <div><h1><?= e($titulos[$seccion]) ?></h1><p class="muted">Gestiona la información del Semáforo Ambiental.</p></div>
    <div class="top-actions"><div class="user"><?= e($nombreAdmin) ?> <span class="small">(<?= e($rol) ?>)</span></div><a class="btn outline" href="logout.php">Salir</a></div>
</header>

<?php if ($mensaje !== ''): ?>
    <div class="notice <?= str_starts_with($mensaje, 'Error:') ? 'error' : '' ?>"><?= e($mensaje) ?></div>
<?php endif; ?>

<?php if ($seccion === 'inicio'): ?>
    <div class="stats">
        <div class="stat"><span>Imágenes de galería</span><strong><?= $contadores['galeria'] ?></strong></div>
        <div class="stat"><span>Servicios</span><strong><?= $contadores['servicios'] ?></strong></div>
        <div class="stat"><span>Novedades</span><strong><?= $contadores['novedades'] ?></strong></div>
        <div class="stat"><span>Mensajes recibidos</span><strong><?= $contadores['contactos'] ?></strong></div>
        <div class="stat"><span>Usuarios registrados</span><strong><?= $contadores['usuarios'] ?></strong></div>
        <div class="stat"><span>Actividades</span><strong><?= $contadores['actividades'] ?></strong></div>
        <div class="stat"><span>Entregas</span><strong><?= $contadores['entregas'] ?></strong></div>
        <div class="stat"><span>Calificaciones</span><strong><?= $contadores['calificaciones'] ?></strong></div>
    </div>
    <section class="panel"><h2>Bienvenido al panel</h2><p class="muted">Selecciona una sección del menú para consultar y administrar los registros de la base de datos.</p></section>

<?php elseif (isset($modulos[$seccion])): ?>
    <section class="panel">
        <h2><?= $editando ? 'Editar registro' : 'Agregar ' . strtolower($titulos[$seccion]) ?></h2>
        <form method="POST" action="admin.php?seccion=<?= e($seccion) ?>" enctype="multipart/form-data">
            <input type="hidden" name="token" value="<?= e($_SESSION['token_admin']) ?>">
            <!-- El tipo debe coincidir con la clave del módulo: galeria, servicios o novedades -->
            <input type="hidden" name="tipo" value="<?= e($seccion) ?>">
            <input type="hidden" name="operacion" value="<?= $editando ? 'editar' : 'crear' ?>">
            <?php if ($editando): ?><input type="hidden" name="id" value="<?= (int)$editar[$pkActual] ?>"><?php endif; ?>
            <div class="form-grid">
                <div class="field full"><label for="titulo">Título</label><input id="titulo" name="titulo" maxlength="180" required value="<?= e($editar['titulo'] ?? '') ?>"></div>
                <div class="field full"><label for="descripcion">Descripción</label><textarea id="descripcion" name="descripcion" required><?= e($editar['descripcion'] ?? '') ?></textarea></div>
                <div class="field full">
                    <label for="imagen">Imagen (máximo 5 MB)</label>
                    <input id="imagen" type="file" name="imagen" accept=".jpg,.jpeg,.png,.webp,.gif" <?= $editando ? '' : 'required' ?>>
                    <?php if (!empty($editar['imagen'])): ?><div><p class="small">Imagen actual</p><img class="current-image" src="<?= e('imagenes/' . rawurlencode(basename($editar['imagen']))) ?>" alt="Imagen actual"></div><?php endif; ?>
                </div>
                <div class="field full"><label class="check"><input type="checkbox" name="activo" value="1" <?= !isset($editar['activo']) || (int)$editar['activo'] === 1 ? 'checked' : '' ?>> Mostrar como activo</label></div>
                <div class="field full"><div class="actions"><button class="btn" type="submit"><?= $editando ? 'Guardar cambios' : 'Crear registro' ?></button><?php if ($editando): ?><a class="btn light" href="admin.php?seccion=<?= e($seccion) ?>">Cancelar</a><?php endif; ?></div></div>
            </div>
        </form>
    </section>
    <section class="panel">
        <h2>Registros existentes (<?= count($lista) ?>)</h2>
        <?php if (!$lista): ?><div class="empty">Todavía no hay registros en esta sección.</div><?php else: ?>
        <div class="tablewrap"><table><thead><tr><th>Imagen</th><th>Título</th><th>Descripción</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
        <?php foreach ($lista as $fila): ?><tr>
            <td><?php if (!empty($fila['imagen'])): ?><img src="<?= e('imagenes/' . rawurlencode(basename($fila['imagen']))) ?>" alt="Imagen"><?php else: ?>Sin imagen<?php endif; ?></td>
            <td><?= e($fila['titulo']) ?></td>
            <td><?= e(function_exists('mb_strimwidth') ? mb_strimwidth((string)$fila['descripcion'], 0, 85, '…', 'UTF-8') : substr((string)$fila['descripcion'], 0, 85)) ?></td>
            <td><span class="badge <?= (int)$fila['activo'] === 1 ? '' : 'off' ?>"><?= (int)$fila['activo'] === 1 ? 'Activo' : 'Inactivo' ?></span></td>
            <td><div class="actions">
                <a class="btn light" href="admin.php?seccion=<?= e($seccion) ?>&accion=editar&id=<?= (int)$fila[$pkActual] ?>">Editar</a>
                <form method="POST" action="admin.php?seccion=<?= e($seccion) ?>" onsubmit="return confirm('¿Seguro que deseas eliminar este registro?')">
                    <input type="hidden" name="token" value="<?= e($_SESSION['token_admin']) ?>"><input type="hidden" name="tipo" value="<?= e($seccion) ?>"><input type="hidden" name="operacion" value="eliminar"><input type="hidden" name="id" value="<?= (int)$fila[$pkActual] ?>">
                    <button class="btn danger" type="submit">Eliminar</button>
                </form>
            </div></td>
        </tr><?php endforeach; ?>
        </tbody></table></div><?php endif; ?>
    </section>

<?php elseif ($seccion === 'usuarios' && $rol === 'administrador'): ?>
    <section class="panel">
        <h2><?= $editando ? 'Editar usuario' : 'Crear usuario' ?></h2>
        <form method="POST" action="admin.php?seccion=usuarios">
            <input type="hidden" name="token" value="<?= e($_SESSION['token_admin']) ?>"><input type="hidden" name="tipo" value="usuario"><input type="hidden" name="operacion" value="<?= $editando ? 'editar' : 'crear' ?>">
            <?php if ($editando): ?><input type="hidden" name="id" value="<?= (int)$editar['id_usuario'] ?>"><?php endif; ?>
            <div class="form-grid">
                <div class="field"><label for="nombre_completo">Nombre completo</label><input id="nombre_completo" name="nombre_completo" maxlength="150" required value="<?= e($editar['nombre_completo'] ?? '') ?>"></div>
                <div class="field"><label for="correo">Correo electrónico</label><input id="correo" type="email" name="correo" maxlength="150" required value="<?= e($editar['correo'] ?? '') ?>"></div>
                <div class="field"><label for="password">Contraseña <?= $editando ? '(opcional)' : '' ?></label><input id="password" type="password" name="password" minlength="8" autocomplete="new-password" <?= $editando ? '' : 'required' ?>><span class="small"><?= $editando ? 'Déjala vacía para conservar la contraseña actual.' : 'Mínimo 8 caracteres.' ?></span></div>
                <div class="field"><label for="rol">Rol</label><select id="rol" name="rol" required><?php foreach (['administrador','profesor','estudiante'] as $opcion): ?><option value="<?= e($opcion) ?>" <?= ($editar['rol'] ?? 'estudiante') === $opcion ? 'selected' : '' ?>><?= ucfirst($opcion) ?></option><?php endforeach; ?></select></div>
                <div class="field full"><div class="actions"><button class="btn" type="submit"><?= $editando ? 'Guardar cambios' : 'Crear usuario' ?></button><?php if ($editando): ?><a class="btn light" href="admin.php?seccion=usuarios">Cancelar</a><?php endif; ?></div></div>
            </div>
        </form>
    </section>
    <section class="panel"><h2>Usuarios registrados (<?= count($lista) ?>)</h2><div class="tablewrap"><table>
        <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Registro</th><th>Acciones</th></tr></thead><tbody>
        <?php foreach ($lista as $fila): ?><tr>
            <td><?= e($fila['nombre_completo']) ?></td><td><?= e($fila['correo']) ?></td><td><?= e(ucfirst($fila['rol'])) ?></td><td><?= e($fila['fecha_registro']) ?></td>
            <td><div class="actions"><a class="btn light" href="admin.php?seccion=usuarios&accion=editar&id=<?= (int)$fila['id_usuario'] ?>">Editar</a>
            <?php if ((int)$fila['id_usuario'] !== $idUsuario): ?><form method="POST" action="admin.php?seccion=usuarios" onsubmit="return confirm('¿Eliminar este usuario? Si tiene registros relacionados, la base de datos puede impedirlo.')">
                <input type="hidden" name="token" value="<?= e($_SESSION['token_admin']) ?>"><input type="hidden" name="tipo" value="usuario"><input type="hidden" name="operacion" value="eliminar"><input type="hidden" name="id" value="<?= (int)$fila['id_usuario'] ?>"><button class="btn danger" type="submit">Eliminar</button>
            </form><?php endif; ?></div></td>
        </tr><?php endforeach; ?>
        </tbody></table></div></section>

<?php elseif ($seccion === 'contactos'): ?>
    <section class="panel"><h2>Mensajes recibidos (<?= count($lista) ?>)</h2>
    <?php if (!$lista): ?><div class="empty">No hay mensajes recibidos.</div><?php else: ?><div class="tablewrap"><table>
        <thead><tr><th>Nombre</th><th>Correo</th><th>Mensaje</th><th>Fecha</th><th>Acción</th></tr></thead><tbody>
        <?php foreach ($lista as $fila): ?><tr><td><?= e($fila['nombre']) ?></td><td><?= e($fila['correo']) ?></td><td><?= nl2br(e($fila['mensaje'])) ?></td><td><?= e($fila['fecha']) ?></td><td>
            <form method="POST" action="admin.php?seccion=contactos" onsubmit="return confirm('¿Eliminar este mensaje?')">
                <input type="hidden" name="token" value="<?= e($_SESSION['token_admin']) ?>"><input type="hidden" name="tipo" value="contacto"><input type="hidden" name="operacion" value="eliminar"><input type="hidden" name="id" value="<?= (int)$fila['id_contacto'] ?>"><button class="btn danger" type="submit">Eliminar</button>
            </form>
        </td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>

<?php elseif (in_array($seccion, ['actividades','entregas','calificaciones'], true)): ?>
    <section class="panel"><h2><?= e($titulos[$seccion]) ?> (<?= count($lista) ?>)</h2><p class="muted">Consulta de registros relacionados con las actividades académicas.</p>
    <?php if (!$lista): ?><div class="empty">No hay registros para mostrar.</div><?php else: ?><div class="tablewrap"><table>
        <thead><tr><?php foreach (array_keys($lista[0]) as $columna): ?><th><?= e(ucwords(str_replace('_',' ',$columna))) ?></th><?php endforeach; ?></tr></thead><tbody>
        <?php foreach ($lista as $fila): ?><tr><?php foreach ($fila as $valor): ?><td><?= e($valor) ?></td><?php endforeach; ?></tr><?php endforeach; ?>
        </tbody></table></div><?php endif; ?></section>
<?php endif; ?>
</main>
</div>
</body>
</html>
