
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/conexion.php';

$logueado = isset($_SESSION['id_usuario'])
    && (int) $_SESSION['id_usuario'] > 0;

$nombreEstudiante = (string) ($_SESSION['nombre'] ?? 'Usuario');

function e($valor): string
{
    return htmlspecialchars(
        (string) ($valor ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

$servicios = [];
$errorServicios = false;

try {
    $consulta = $conexion->prepare(
        "SELECT id_servicio, titulo, descripcion, imagen
         FROM tbl_servicios
         WHERE activo = 1
         ORDER BY id_servicio ASC"
    );

    $consulta->execute();
    $resultado = $consulta->get_result();

    while ($fila = $resultado->fetch_assoc()) {
        $servicios[] = $fila;
    }

    $consulta->close();
} catch (Throwable $error) {
    $errorServicios = true;
}

function imagenServicioLocal($imagen): ?string
{
    $imagen = trim((string) $imagen);

    if (
        $imagen === '' ||
        str_contains($imagen, '://') ||
        str_contains($imagen, '..') ||
        str_contains($imagen, '\\') ||
        str_starts_with($imagen, '/')
    ) {
        return null;
    }

    $nombre = basename($imagen);

    $rutasPermitidas = [
        __DIR__ . '/uploads/servicios/' . $nombre,
        __DIR__ . '/imagenes/' . $nombre
    ];

    foreach ($rutasPermitidas as $ruta) {
        if (is_file($ruta)) {
            return str_contains($ruta, '/uploads/servicios/')
                ? './uploads/servicios/' . rawurlencode($nombre)
                : './imagenes/' . rawurlencode($nombre);
        }
    }

    return null;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description" content="Servicios del proyecto Semáforo Ambiental de la Huerta, educación ambiental y cuidado de los recursos naturales.">

    <title>Servicios | Semáforo Ambiental de la Huerta</title>

    <style>
        :root {
            --verde-oscuro: #1a5214;
            --verde: #388e3c;
            --verde-claro: #9dde89;
            --fondo: #f4faf2;
            --texto: #2d2d2d;
            --blanco: #ffffff;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: var(--fondo);
            color: var(--texto);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .barra-navegacion {
            position: sticky;
            top: 0;
            z-index: 1000;
            width: 100%;
            min-height: 67px;
            padding: 8px 2.5%;
            background: var(--verde-oscuro);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            box-shadow: 0 3px 12px #0002;
        }

        .nav-marca {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            flex-shrink: 0;
        }

        .nav-marca img {
            width: 48px;
            height: 48px;
            object-fit: contain;
            background: white;
            border-radius: 50%;
            padding: 3px;
        }

        .nav-marca span {
            color: white;
            font-size: 1rem;
            font-weight: bold;
            line-height: 1.25;
            max-width: 230px;
        }

        .nav-enlaces {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 5px;
        }

        .nav-enlaces a {
            color: white;
            text-decoration: none;
            padding: 10px;
            border-radius: 9px;
            font-size: 0.86rem;
            white-space: nowrap;
            transition: background 0.2s;
        }

        .nav-enlaces a:hover,
        .nav-enlaces a.activo {
            background: #ffffff26;
        }

        .nav-enlaces .boton-login {
            background: var(--verde-claro);
            color: var(--verde-oscuro);
            font-weight: bold;
        }

        .nav-enlaces .boton-cerrar {
            background: white;
            color: var(--verde-oscuro);
            font-weight: bold;
        }

        .usuario-sesion {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }

        .nombre-usuario {
            color: white;
            font-size: 0.85rem;
            font-weight: bold;
        }

        .encabezado-pagina {
            background: linear-gradient(
                110deg,
                var(--verde-oscuro) 0%,
                #4a9e3f 100%
            );
            color: white;
            padding: 60px 8.9%;
            min-height: 314px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 35px;
        }

        .texto-encabezado {
            max-width: 850px;
            flex: 1;
        }

        .encabezado-pagina h1 {
            font-family: Georgia, "Times New Roman", serif;
            font-size: clamp(2.8rem, 5vw, 4.5rem);
            line-height: 1.1;
            margin-bottom: 20px;
        }

        .encabezado-pagina p {
            max-width: 850px;
            font-size: 1.08rem;
            line-height: 1.75;
        }

        .semaforo-encabezado {
            flex: 0 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            width: 180px;
            height: 71px;
            padding: 12px 17px;
            background: #143d18;
            border-radius: 50px;
            box-shadow: 0 5px 14px #0002;
        }

        .luz-encabezado {
            display: block;
            flex: 0 0 35px;
            width: 35px;
            height: 35px;
            border-radius: 50%;
        }

        .luz-encabezado.roja {
            background: #e93636;
        }

        .luz-encabezado.amarilla {
            background: #ffb81c;
        }

        .luz-encabezado.verde {
            background: #32b44a;
        }

        main {
            flex: 1;
            width: 100%;
            padding: 42px 20px;
        }

        .contenedor {
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
        }

        .introduccion {
            text-align: center;
            max-width: 850px;
            margin: 0 auto 35px;
        }

        .introduccion h2 {
            color: var(--verde-oscuro);
            font-family: Georgia, "Times New Roman", serif;
            font-size: clamp(1.7rem, 4vw, 2.2rem);
            margin-bottom: 12px;
        }

        .introduccion p {
            color: #555;
            font-size: 1rem;
            line-height: 1.8;
        }

        .tarjetas-servicios {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 25px;
        }

        .tarjeta-servicio {
            min-width: 0;
            background: var(--blanco);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px #0000000d;
            border: 1px solid #e0ebdc;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .tarjeta-servicio:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 22px #00000015;
        }

        .imagen-servicio {
            width: 100%;
            height: 230px;
            display: block;
            object-fit: cover;
            background: #e4efdf;
        }

        .imagen-no-disponible {
            width: 100%;
            height: 230px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e4efdf;
            color: #42623d;
            font-weight: bold;
            text-align: center;
            padding: 15px;
        }

        .contenido-tarjeta {
            padding: 25px;
        }

        .contenido-tarjeta h3 {
            color: var(--verde-oscuro);
            font-family: Georgia, "Times New Roman", serif;
            font-size: 1.4rem;
            margin-bottom: 13px;
            overflow-wrap: anywhere;
        }

        .contenido-tarjeta p {
            color: #555;
            font-size: 0.96rem;
            line-height: 1.8;
            white-space: pre-line;
            overflow-wrap: anywhere;
        }

        .mensaje {
            padding: 22px;
            margin-bottom: 25px;
            border-radius: 12px;
            background: white;
            border: 1px solid #dcebd5;
            color: #365d30;
            line-height: 1.7;
            text-align: center;
        }

        .seccion-compromiso {
            margin-top: 35px;
            padding: 30px;
            background: white;
            border-left: 6px solid var(--verde-claro);
            border-radius: 14px;
            box-shadow: 0 4px 20px #0000000d;
        }

        .seccion-compromiso h2 {
            color: var(--verde-oscuro);
            font-family: Georgia, "Times New Roman", serif;
            font-size: 1.6rem;
            margin-bottom: 12px;
        }

        .seccion-compromiso p {
            color: #555;
            line-height: 1.8;
            font-size: 0.97rem;
        }

        .pie-pagina {
            margin-top: 15px;
            padding: 28px 20px 18px;
            background: var(--verde-oscuro);
            color: white;
            text-align: center;
        }

        .logos-institucionales {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 30px;
            margin-bottom: 20px;
        }

        .logo-institucional {
            display: flex;
            align-items: center;
            gap: 10px;
            text-align: left;
        }

        .logo-institucional img {
            width: 58px;
            height: 58px;
            object-fit: contain;
            background: white;
            padding: 4px;
            border-radius: 8px;
        }

        .logo-institucional span {
            font-size: 0.84rem;
            line-height: 1.5;
            font-weight: bold;
        }

        .pie-pagina p {
            font-size: 0.82rem;
            line-height: 1.7;
            opacity: 0.85;
        }

        @media (max-width: 1150px) {
            .barra-navegacion {
                align-items: flex-start;
                flex-direction: column;
                gap: 10px;
                padding: 10px 3%;
            }

            .nav-enlaces {
                width: 100%;
                justify-content: flex-start;
            }

            .encabezado-pagina {
                padding: 45px 6%;
                min-height: 270px;
            }

            .encabezado-pagina h1 {
                font-size: clamp(2.5rem, 5vw, 3.5rem);
            }

            .semaforo-encabezado {
                width: 165px;
                height: 65px;
                gap: 12px;
            }

            .luz-encabezado {
                flex-basis: 31px;
                width: 31px;
                height: 31px;
            }
        }

        @media (max-width: 700px) {
            .barra-navegacion {
                padding: 10px 4%;
            }

            .nav-marca img {
                width: 42px;
                height: 42px;
            }

            .nav-marca span {
                font-size: 0.85rem;
                max-width: 210px;
            }

            .nav-enlaces {
                gap: 4px;
            }

            .nav-enlaces a {
                padding: 8px;
                font-size: 0.76rem;
            }

            .usuario-sesion {
                width: 100%;
                justify-content: flex-start;
            }

            .encabezado-pagina {
                flex-direction: column;
                align-items: flex-start;
                padding: 38px 7%;
                gap: 25px;
                min-height: auto;
            }

            .encabezado-pagina h1 {
                font-size: 2.8rem;
                margin-bottom: 15px;
            }

            .encabezado-pagina p {
                font-size: 1rem;
                line-height: 1.7;
            }

            .semaforo-encabezado {
                align-self: flex-start;
                width: 155px;
                height: 61px;
                gap: 11px;
                padding: 10px 14px;
            }

            .luz-encabezado {
                flex-basis: 29px;
                width: 29px;
                height: 29px;
            }

            main {
                padding: 28px 13px;
            }

            .tarjetas-servicios {
                grid-template-columns: 1fr;
            }

            .imagen-servicio,
            .imagen-no-disponible {
                height: 210px;
            }

            .contenido-tarjeta {
                padding: 21px;
            }

            .seccion-compromiso {
                padding: 23px 19px;
            }

            .logos-institucionales {
                gap: 18px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
        }
    </style>
</head>

<body>

<nav class="barra-navegacion" aria-label="Menú principal">
    <a href="./index.php" class="nav-marca">
        <img src="./img/logo.jpeg"
             alt="Logo del proyecto"
             onerror="this.onerror=null;this.src='./img/logo.png'">
        <span>Semáforo Ambiental de la Huerta</span>
    </a>

    <div class="nav-enlaces">
        <a href="./index.php">Inicio</a>
        <a href="./quienes-somos.php">Quiénes Somos</a>
        <a href="./fundamentos.php">Fundamentos</a>
        <a href="./galeria.php">Galería</a>
        <a href="./contacto.php">Contactos</a>
        <a href="./nuestras-politicas.php">Nuestras Políticas</a>
        <a href="./servicios.php" class="activo" aria-current="page">Servicios</a>
        <a href="./novedades.php">Novedades</a>

        <?php if ($logueado): ?>
            <span class="nombre-usuario">
                Hola, <?= e($nombreEstudiante) ?>
            </span>
            <a href="./logout.php" class="boton-cerrar">Cerrar sesión</a>
        <?php else: ?>
            <a href="./login.php" class="boton-login">Iniciar sesión</a>
        <?php endif; ?>
    </div>
</nav>

<header class="encabezado-pagina">
    <div class="texto-encabezado">
        <h1>Servicios</h1>
        <p>
            Promovemos la educación ambiental, el cuidado de la huerta
            escolar y el uso responsable de los recursos naturales mediante
            actividades que fortalecen la conciencia ecológica de la
            comunidad educativa.
        </p>
    </div>

    <div class="semaforo-encabezado"
         role="img"
         aria-label="Semáforo ambiental con luces roja, amarilla y verde">
        <span class="luz-encabezado roja"></span>
        <span class="luz-encabezado amarilla"></span>
        <span class="luz-encabezado verde"></span>
    </div>
</header>

<main>
    <div class="contenedor">

        <section class="introduccion">
            <h2>¿Qué ofrece el proyecto?</h2>
            <p>
                El Semáforo Ambiental de la Huerta busca facilitar el
                aprendizaje de prácticas sostenibles a través de estrategias
                educativas relacionadas con el ambiente, el cultivo y la
                conservación de los recursos naturales.
            </p>
        </section>

        <?php if ($errorServicios): ?>
            <div class="mensaje">
                No fue posible cargar los servicios. Verifica la conexión
                con la base de datos y la estructura de la tabla
                <strong>tbl_servicios</strong>.
            </div>

        <?php elseif (empty($servicios)): ?>
            <div class="mensaje">
                Actualmente no hay servicios publicados. Próximamente
                encontrarás aquí información sobre nuestras actividades
                ambientales.
            </div>

        <?php else: ?>
            <section class="tarjetas-servicios" aria-label="Servicios ambientales">
                <?php foreach ($servicios as $servicio): ?>
                    <?php $rutaImagen = imagenServicioLocal($servicio['imagen'] ?? ''); ?>

                    <article class="tarjeta-servicio">
                        <?php if ($rutaImagen !== null): ?>
                            <img
                                src="<?= e($rutaImagen) ?>"
                                alt="<?= e($servicio['titulo']) ?>"
                                class="imagen-servicio"
                                loading="lazy">
                        <?php else: ?>
                            <div class="imagen-no-disponible">
                                Imagen no disponible
                            </div>
                        <?php endif; ?>

                        <div class="contenido-tarjeta">
                            <h3><?= e($servicio['titulo']) ?></h3>
                            <p><?= e($servicio['descripcion']) ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <section class="seccion-compromiso">
            <h2>Un compromiso con el ambiente</h2>
            <p>
                El proyecto Semáforo Ambiental de la Huerta promueve la
                reflexión y la participación de la comunidad educativa
                para mejorar las prácticas ambientales cotidianas. A través
                de la educación y el trabajo colaborativo, se busca
                fortalecer el cuidado de la huerta escolar y el uso
                responsable de los recursos naturales.
            </p>
        </section>

    </div>
</main>

<footer class="pie-pagina">
    <div class="logos-institucionales">
        <div class="logo-institucional">
            <img src="./img/republica-uruguay.png"
                 alt="Logo de la Institución Educativa República de Uruguay"
                 onerror="this.onerror=null;this.style.display='none'">
            <span>
                I.E. República de Uruguay<br>
                Medellín, Antioquia
            </span>
        </div>

        <div class="logo-institucional">
            <img src="./img/pascual-bravo.png"
                 alt="Logo de la Institución Universitaria Pascual Bravo"
                 onerror="this.onerror=null;this.style.display='none'">
            <span>
                Institución Universitaria<br>
                Pascual Bravo
            </span>
        </div>
    </div>

    <p>
        © <?= date('Y') ?> Semáforo Ambiental de la Huerta<br>
        I.E. República de Uruguay · Medellín, Antioquia<br>
        Educación ambiental · Uso responsable del agua
    </p>
</footer>

</body>
</html>
