<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/conexion.php';

if (!isset($conexion) || !($conexion instanceof mysqli)) {
    http_response_code(500);
    die('No se pudo establecer la conexión con la base de datos. Revisa config/conexion.php.');
}

$conexion->set_charset('utf8mb4');

function e($valor): string
{
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

$logueado = isset($_SESSION['id_usuario']) && (int)$_SESSION['id_usuario'] > 0;
$nombreEstudiante = $logueado
    ? (string)($_SESSION['nombre_completo'] ?? $_SESSION['nombre'] ?? $_SESSION['usuario'] ?? 'Usuario')
    : '';

/**
 * Devuelve una ruta web relativa si la imagen existe dentro del proyecto.
 * En tbl_novedades.imagen puede guardarse solo el nombre (foto.jpg) o una ruta
 * relativa (imagenes/foto.jpg, imagenes/novedades/foto.jpg, uploads/novedades/foto.jpg).
 */
function obtenerImagenNovedad($imagen): ?string
{
    $imagen = trim((string)($imagen ?? ''));
    if ($imagen === '') {
        return null;
    }

    // No aceptar URLs externas, rutas absolutas ni intentos de salir del proyecto.
    if (preg_match('~^(https?:)?//~i', $imagen) || str_contains($imagen, '..') || str_contains($imagen, "\0")) {
        return null;
    }

    $imagen = str_replace('\\', '/', ltrim($imagen, '/'));
    $nombreArchivo = basename($imagen);
    $rutasPosibles = [
        __DIR__ . '/' . $imagen,
        __DIR__ . '/imagenes/' . $nombreArchivo,
        __DIR__ . '/imagenes/novedades/' . $nombreArchivo,
        __DIR__ . '/img/novedades/' . $nombreArchivo,
        __DIR__ . '/uploads/novedades/' . $nombreArchivo,
    ];

    $directorioProyecto = realpath(__DIR__);
    foreach ($rutasPosibles as $ruta) {
        if (!is_file($ruta)) {
            continue;
        }
        $rutaReal = realpath($ruta);
        if ($rutaReal === false || $directorioProyecto === false) {
            continue;
        }
        $prefijo = $directorioProyecto . DIRECTORY_SEPARATOR;
        if (strpos($rutaReal, $prefijo) !== 0) {
            continue;
        }
        return str_replace(DIRECTORY_SEPARATOR, '/', substr($rutaReal, strlen($prefijo)));
    }

    return null;
}

$novedades = [];
$errorConsulta = false;
$sql = "SELECT id_novedad, titulo, descripcion, fecha_publicacion, imagen
        FROM tbl_novedades
        WHERE activo = 1
        ORDER BY fecha_publicacion DESC, id_novedad DESC";

$resultado = $conexion->query($sql);
if ($resultado instanceof mysqli_result) {
    while ($fila = $resultado->fetch_assoc()) {
        $novedades[] = $fila;
    }
    $resultado->free();
} else {
    $errorConsulta = true;
    error_log('Error al consultar tbl_novedades: ' . $conexion->error);
}

function fechaNovedad($valor): string
{
    if (empty($valor)) {
        return 'Fecha no disponible';
    }
    try {
        $fecha = new DateTime((string)$valor);
        $meses = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        return $fecha->format('j') . ' de ' . $meses[(int)$fecha->format('n')] . ' de ' . $fecha->format('Y');
    } catch (Throwable $ex) {
        return (string)$valor;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Consulta las novedades, actividades y avances del proyecto Semáforo Ambiental de la Huerta.">
    <title>Novedades | Semáforo Ambiental de la Huerta</title>
    <style>
        :root{--verde-oscuro:#1a5214;--verde:#388e3c;--verde-claro:#9dde89;--fondo:#f4faf2;--texto:#2d2d2d;--blanco:#fff}
        *{box-sizing:border-box;margin:0;padding:0}html{scroll-behavior:smooth}
        body{font-family:Arial,Helvetica,sans-serif;background:var(--fondo);color:var(--texto);min-height:100vh;display:flex;flex-direction:column}
        .barra-navegacion{position:sticky;top:0;z-index:1000;width:100%;min-height:67px;padding:8px 2.5%;background:var(--verde-oscuro);display:flex;align-items:center;justify-content:space-between;gap:15px;box-shadow:0 3px 12px #0002}
        .nav-marca{display:flex;align-items:center;gap:12px;text-decoration:none;flex-shrink:0}.nav-marca img{width:48px;height:48px;object-fit:contain;background:#fff;border-radius:50%;padding:3px}.nav-marca span{color:#fff;font-size:1rem;font-weight:bold;line-height:1.25;max-width:230px}
        .nav-enlaces{display:flex;align-items:center;justify-content:flex-end;flex-wrap:wrap;gap:5px}.nav-enlaces a{color:#fff;text-decoration:none;padding:12px 13px;border-radius:9px;font-size:.9rem;white-space:nowrap;transition:background .2s}.nav-enlaces a:hover,.nav-enlaces a.activo{background:#ffffff26}.nav-enlaces .boton-login{background:var(--verde-claro);color:var(--verde-oscuro);font-weight:bold}.nav-enlaces .boton-login:hover{background:#b5e8a5}.nav-enlaces .usuario{color:#fff;padding:8px;font-size:.85rem}
        .encabezado-pagina{background:linear-gradient(110deg,var(--verde-oscuro) 0%,#4a9e3f 100%);color:#fff;padding:60px 8.9%;min-height:314px;display:flex;align-items:center;justify-content:space-between;gap:35px}.texto-encabezado{max-width:850px;flex:1}.encabezado-pagina h1{font-family:Georgia,'Times New Roman',serif;font-size:clamp(2.8rem,5vw,4.5rem);line-height:1.1;margin-bottom:20px}.encabezado-pagina p{max-width:850px;font-size:1.08rem;line-height:1.75}
        .semaforo-encabezado{flex:0 0 auto;display:flex;align-items:center;justify-content:center;gap:15px;width:180px;height:71px;padding:12px 17px;background:#143d18;border-radius:50px;box-shadow:0 5px 14px #0002}.luz-encabezado{display:block;flex:0 0 35px;width:35px;height:35px;border-radius:50%}.luz-encabezado.roja{background:#e93636}.luz-encabezado.amarilla{background:#ffb81c}.luz-encabezado.verde{background:#32b44a}
        main{flex:1;width:100%;padding:42px 20px}.contenedor{width:100%;max-width:1100px;margin:0 auto}.introduccion{max-width:850px;margin:0 auto 30px;text-align:center}.introduccion h2{color:var(--verde-oscuro);font-family:Georgia,'Times New Roman',serif;font-size:clamp(1.7rem,4vw,2.2rem);margin-bottom:12px}.introduccion p{color:#555;font-size:1rem;line-height:1.8}
        .aviso-actualizacion{width:100%;margin-bottom:25px;padding:18px 22px;background:linear-gradient(135deg,var(--verde-oscuro),#2d8020);color:#fff;border-radius:12px;text-align:center;font-size:.95rem;line-height:1.7}.lista-novedades{display:grid;gap:25px}.tarjeta-novedad{display:grid;grid-template-columns:minmax(0,1fr) minmax(260px,.85fr);align-items:center;gap:28px;padding:30px;background:#fff;border-radius:16px;border-left:6px solid var(--verde-claro);box-shadow:0 4px 20px #0000000d;transition:transform .2s,box-shadow .2s}.tarjeta-novedad:hover{transform:translateY(-3px);box-shadow:0 8px 24px #00000012}
        .fecha-novedad{display:inline-block;margin-bottom:14px;padding:7px 12px;border-radius:20px;background:#edf7e8;color:var(--verde-oscuro);font-size:.82rem;font-weight:bold;text-transform:uppercase;letter-spacing:.5px}.texto-novedad h2{margin-bottom:15px;color:var(--verde-oscuro);font-family:Georgia,'Times New Roman',serif;font-size:1.6rem;line-height:1.35}.texto-novedad p{color:#555;font-size:.96rem;line-height:1.85;margin-bottom:12px;overflow-wrap:anywhere;white-space:normal}.imagen-novedad{display:block;width:100%;height:300px;object-fit:cover;border-radius:12px;box-shadow:0 4px 16px #00000015;background:#e4efdf}.imagen-no-disponible{display:flex;align-items:center;justify-content:center;min-height:220px;padding:20px;border-radius:12px;background:#e4efdf;color:var(--verde-oscuro);text-align:center;font-weight:bold}
        .mensaje-estado{padding:25px;background:#fff;border:1px solid #dcebd5;border-radius:14px;text-align:center;color:#555;line-height:1.8}.mensaje-estado h2{color:var(--verde-oscuro);font-family:Georgia,'Times New Roman',serif;margin-bottom:10px}.mensaje-ambiental{margin-top:28px;padding:25px;background:#fff;border-radius:14px;border:1px solid #dcebd5;text-align:center}.mensaje-ambiental h2{color:var(--verde-oscuro);font-family:Georgia,'Times New Roman',serif;font-size:1.5rem;margin-bottom:10px}.mensaje-ambiental p{max-width:800px;margin:0 auto;color:#555;font-size:.96rem;line-height:1.8}
        .pie-pagina{margin-top:15px;padding:28px 20px 18px;background:var(--verde-oscuro);color:#fff;text-align:center}.logos-institucionales{display:flex;align-items:center;justify-content:center;flex-wrap:wrap;gap:30px;margin-bottom:20px}.logo-institucional{display:flex;align-items:center;gap:10px;text-align:left}.logo-institucional img{width:58px;height:58px;object-fit:contain;background:#fff;padding:4px;border-radius:8px}.logo-institucional span{font-size:.84rem;line-height:1.5;font-weight:bold}.iconos-redes{display:flex;justify-content:center;gap:12px;margin-bottom:18px}.iconos-redes a{color:#fff;text-decoration:none;width:42px;height:42px;background:#ffffff24;border-radius:10px;display:flex;align-items:center;justify-content:center;border:1px solid #ffffff30;transition:background .2s,transform .2s}.iconos-redes a:hover{background:#ffffff40;transform:translateY(-2px)}.iconos-redes svg{width:22px;height:22px;fill:currentColor}.pie-pagina p{font-size:.82rem;line-height:1.7;opacity:.85}
        @media(max-width:1150px){.barra-navegacion{align-items:flex-start;flex-direction:column;gap:10px;padding:10px 3%}.nav-enlaces{width:100%;justify-content:flex-start}.encabezado-pagina{padding:45px 6%;min-height:270px}.tarjeta-novedad{grid-template-columns:1fr .8fr;padding:24px}.imagen-novedad{height:270px}}
        @media(max-width:700px){.barra-navegacion{padding:10px 4%}.nav-marca img{width:42px;height:42px}.nav-marca span{font-size:.85rem;max-width:210px}.nav-enlaces{gap:4px}.nav-enlaces a{padding:8px;font-size:.76rem}.encabezado-pagina{flex-direction:column;align-items:flex-start;padding:38px 7%;gap:25px;min-height:auto}.encabezado-pagina h1{font-size:2.8rem;margin-bottom:15px}.encabezado-pagina p{font-size:1rem;line-height:1.7}.semaforo-encabezado{width:155px;height:61px;gap:11px;padding:10px 14px}.luz-encabezado{flex-basis:29px;width:29px;height:29px}main{padding:28px 13px}.tarjeta-novedad{grid-template-columns:1fr;padding:22px 19px;gap:20px}.texto-novedad h2{font-size:1.35rem}.imagen-novedad{height:230px}.mensaje-ambiental{padding:22px 18px}.logos-institucionales{gap:18px}}
        @media(prefers-reduced-motion:reduce){*,*::before,*::after{animation-duration:.01ms!important;transition-duration:.01ms!important;scroll-behavior:auto!important}}
    </style>
</head>
<body>
<nav class="barra-navegacion" aria-label="Menú principal">
    <a href="./index.php" class="nav-marca">
        <img src="./img/logo.jpeg" alt="Logo del proyecto" onerror="this.onerror=null;this.src='./img/logo.png'">
        <span>Semáforo Ambiental de la Huerta</span>
    </a>
    <div class="nav-enlaces">
        <a href="./index.php">Inicio</a>
        <a href="./quienes-somos.php">Quiénes Somos</a>
        <a href="./fundamentos.php">Fundamentos</a>
        <a href="./galeria.php">Galería</a>
        <a href="./contacto.php">Contactos</a>
        <a href="./nuestras-politicas.php">Nuestras Políticas</a>
        <a href="./servicios.php">Servicios</a>
        <a href="./novedades.php" class="activo" aria-current="page">Novedades</a>
        <?php if ($logueado): ?>
            <span class="usuario">Hola, <?= e($nombreEstudiante) ?></span>
            <a href="./logout.php" class="boton-login">Cerrar sesión</a>
        <?php else: ?>
            <a href="./login.php" class="boton-login">Iniciar sesión</a>
        <?php endif; ?>
    </div>
</nav>

<header class="encabezado-pagina">
    <div class="texto-encabezado">
        <h1>Novedades</h1>
        <p>Cultivando conciencia para cuidar el planeta y compartir los avances de nuestra huerta escolar.</p>
    </div>
    <div class="semaforo-encabezado" role="img" aria-label="Semáforo ambiental con luces roja, amarilla y verde">
        <span class="luz-encabezado roja"></span><span class="luz-encabezado amarilla"></span><span class="luz-encabezado verde"></span>
    </div>
</header>

<main>
    <div class="contenedor">
        <section class="introduccion">
            <h2>Noticias y actividades de la huerta</h2>
            <p>En este espacio se comparten las actividades, experiencias y avances relacionados con el cuidado de la huerta escolar y la promoción de prácticas ambientales responsables.</p>
        </section>
        <div class="aviso-actualizacion">Consulta las novedades del proyecto y conoce las actividades que contribuyen al cuidado de nuestra huerta escolar.</div>

        <?php if ($errorConsulta): ?>
            <section class="mensaje-estado">
                <h2>No fue posible cargar las novedades</h2>
                <p>Ocurrió un problema al consultar la información. Verifica la conexión con la base de datos y que exista la tabla <strong>tbl_novedades</strong>.</p>
            </section>
        <?php elseif (empty($novedades)): ?>
            <section class="mensaje-estado">
                <h2>Próximamente tendremos novedades</h2>
                <p>Aún no hay publicaciones activas disponibles. Cuando el administrador registre y active nuevas actividades, aparecerán en este espacio.</p>
            </section>
        <?php else: ?>
            <section class="lista-novedades" aria-label="Novedades publicadas">
                <?php foreach ($novedades as $novedad): ?>
                    <?php $imagenLocal = obtenerImagenNovedad($novedad['imagen'] ?? ''); ?>
                    <article class="tarjeta-novedad">
                        <div class="texto-novedad">
                            <span class="fecha-novedad"><?= e(fechaNovedad($novedad['fecha_publicacion'] ?? null)) ?></span>
                            <h2><?= e($novedad['titulo'] ?? '') ?></h2>
                            <p><?= nl2br(e($novedad['descripcion'] ?? '')) ?></p>
                        </div>
                        <div>
                            <?php if ($imagenLocal !== null): ?>
                                <img src="<?= e($imagenLocal) ?>" class="imagen-novedad" alt="<?= e($novedad['titulo'] ?? 'Imagen de la novedad') ?>" loading="lazy">
                            <?php else: ?>
                                <div class="imagen-no-disponible">Imagen no disponible</div>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <section class="mensaje-ambiental">
            <h2>Pequeñas acciones, grandes cambios</h2>
            <p>Cada actividad de la huerta representa una oportunidad para aprender a cuidar los recursos naturales, proteger la biodiversidad y construir hábitos sostenibles dentro y fuera de la institución educativa.</p>
        </section>
    </div>
</main>

<footer class="pie-pagina">
    <div class="logos-institucionales">
        <div class="logo-institucional">
            <img src="./img/republica-uruguay.png" alt="Logo de la Institución Educativa República de Uruguay" onerror="this.onerror=null;this.style.display='none'">
            <span>I.E. República de Uruguay<br>Medellín, Antioquia</span>
        </div>
        <div class="logo-institucional">
            <img src="./img/pascual-bravo.png" alt="Logo de la Institución Universitaria Pascual Bravo" onerror="this.onerror=null;this.style.display='none'">
            <span>Institución Universitaria<br>Pascual Bravo</span>
        </div>
    </div>
    <div class="iconos-redes" aria-label="Redes sociales">
        <a href="https://www.facebook.com/share/1cmyzR78o5/" target="_blank" rel="noopener noreferrer" title="Facebook" aria-label="Facebook">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
        </a>
        <a href="https://www.instagram.com/i.edu.republicadeuruguay" target="_blank" rel="noopener noreferrer" title="Instagram" aria-label="Instagram">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/></svg>
        </a>
        <a href="https://www.youtube.com/@canalieru4941" target="_blank" rel="noopener noreferrer" title="YouTube" aria-label="YouTube">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M23.495 6.205a3.007 3.007 0 0 0-2.088-2.088c-1.87-.501-9.396-.501-9.396-.501s-7.507-.01-9.396.501A3.007 3.007 0 0 0 .527 6.205a31.247 31.247 0 0 0-.522 5.805 31.247 31.247 0 0 0 .522 5.783 3.007 3.007 0 0 0 2.088 2.088c1.868.502 9.396.502 9.396.502s7.506 0 9.396-.502a3.007 3.007 0 0 0 2.088-2.088 31.247 31.247 0 0 0 .5-5.783 31.247 31.247 0 0 0-.5-5.805zM9.609 15.601V8.408l6.264 3.602z"/></svg>
        </a>
    </div>
    <p>© 2026 Semáforo Ambiental de la Huerta<br>I.E. República de Uruguay · Medellín, Antioquia<br>Educación ambiental · Uso responsable del agua</p>
</footer>
</body>
</html>
