
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/conexion.php';

$logueado = isset($_SESSION['id_usuario'])
    && (int) $_SESSION['id_usuario'] > 0;

$nombreUsuario = '';

if ($logueado) {
    $nombre = trim((string) ($_SESSION['nombre'] ?? 'Usuario'));

    if ($nombre === '') {
        $nombre = 'Usuario';
    }

    $nombreUsuario = htmlspecialchars(
        $nombre,
        ENT_QUOTES,
        'UTF-8'
    );
}

function e($texto): string
{
    return htmlspecialchars(
        (string) ($texto ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

$sql = "SELECT id_galeria, titulo, descripcion, imagen
        FROM tbl_galeria
        WHERE activo = 1
        ORDER BY id_galeria ASC";

$resultado = $conexion->query($sql);
$errorGaleria = !$resultado;

if ($errorGaleria) {
    error_log(
        'Error al consultar tbl_galeria: ' . $conexion->error
    );
}

$rutaImagenes = 'imagenes/';
$rutaFisicaImagenes = __DIR__ . '/imagenes/';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Galería | Semáforo Ambiental de la Huerta</title>

    <meta
        name="description"
        content="Galería de actividades del proyecto Semáforo Ambiental de la Huerta de la I.E. República de Uruguay, Medellín."
    >
    <meta name="theme-color" content="#1a5214">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=Nunito+Sans:wght@400;600;700;800&display=swap"
        rel="stylesheet"
    >

    <style>
        :root {
            --verde-oscuro: #1a5214;
            --verde: #388e3c;
            --verde-claro: #9dde89;
            --fondo: #f4faf2;
            --texto: #2d2d2d;
            --texto-suave: #555;
            --linea: #dcebd5;
            --blanco: #fff;
            --titulo: "Fraunces", Georgia, serif;
            --cuerpo: "Nunito Sans", Arial, sans-serif;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: var(--cuerpo);
            background: var(--fondo);
            color: var(--texto);
            line-height: 1.6;
        }

        img {
            max-width: 100%;
        }

        a {
            -webkit-tap-highlight-color: transparent;
        }

        a:focus-visible {
            outline: 3px solid #ffb719;
            outline-offset: 3px;
        }

        .contenedor {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* NAVEGACIÓN */

        .barra-navegacion {
            position: sticky;
            top: 0;
            z-index: 1000;
            width: 100%;
            min-height: 70px;
            padding: 12px 4%;
            background: var(--verde-oscuro);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, .12);
        }

        .nav-marca {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
            text-decoration: none;
        }

        .nav-marca img {
            width: 48px;
            height: 48px;
            object-fit: contain;
            background: var(--blanco);
            border-radius: 50%;
            padding: 3px;
        }

        .nav-marca span {
            max-width: 165px;
            color: var(--blanco);
            font-size: .9rem;
            font-weight: 800;
            line-height: 1.25;
        }

        .nav-enlaces {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            flex-wrap: wrap;
            gap: 4px;
        }

        .nav-enlaces a {
            padding: 9px 11px;
            border-radius: 7px;
            color: var(--blanco);
            text-decoration: none;
            font-size: .83rem;
            transition: background .2s, color .2s;
        }

        .nav-enlaces a:hover,
        .nav-enlaces a.activo {
            background: rgba(255, 255, 255, .15);
        }

        .nav-enlaces .boton-login {
            background: var(--verde-claro);
            color: var(--verde-oscuro);
            font-weight: 800;
        }

        .nav-enlaces .boton-login:hover {
            background: var(--blanco);
        }

        .nav-enlaces .enlace-usuario {
            max-width: 190px;
            padding: 8px 11px;
            border-radius: 7px;
            background: rgba(255, 255, 255, .14);
            color: var(--blanco);
            font-size: .83rem;
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .nav-enlaces .boton-cerrar {
            background: var(--blanco);
            color: var(--verde-oscuro);
            font-weight: 800;
        }

        .nav-enlaces .boton-cerrar:hover {
            background: #edf7e9;
        }

        /* ENCABEZADO */

        .encabezado {
            position: relative;
            overflow: hidden;
            padding: 45px 20px;
            background: linear-gradient(135deg, #1a5214, #388e3c);
            color: var(--blanco);
            border-bottom: 5px solid var(--verde-claro);
        }

        .encabezado .contenedor {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 25px;
            flex-wrap: wrap;
        }

        .texto-encabezado {
            flex: 1 1 300px;
        }

        .encabezado h1 {
            margin-bottom: 12px;
            font-family: var(--titulo);
            font-size: clamp(2.2rem, 5vw, 3.2rem);
            line-height: 1.15;
        }

        .encabezado p {
            max-width: 650px;
            color: #edf8e9;
            font-size: 1.05rem;
        }

        /* SEMÁFORO HORIZONTAL */

        .semaforo {
            width: 180px;
            height: 72px;
            padding: 0 16px;
            background: #103b19;
            border-radius: 45px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            flex-shrink: 0;
            box-shadow:
                inset 0 2px 5px rgba(0, 0, 0, .3),
                0 5px 12px rgba(0, 0, 0, .18);
        }

        .luz {
            display: block;
            width: 35px;
            height: 35px;
            min-width: 35px;
            border-radius: 50%;
            border: 2px solid rgba(0, 0, 0, .16);
            box-shadow:
                inset 0 2px 4px rgba(255, 255, 255, .28),
                0 2px 5px rgba(0, 0, 0, .2);
        }

        #luz-roja {
            background: #ed3438;
        }

        #luz-amarilla {
            background: #ffb719;
        }

        #luz-verde {
            background: #2db34a;
        }

        /* GALERÍA */

        main {
            flex: 1;
            width: 100%;
            padding: 45px 20px 70px;
        }

        .introduccion {
            margin-bottom: 30px;
        }

        .introduccion h2 {
            margin-bottom: 8px;
            color: var(--verde-oscuro);
            font-family: var(--titulo);
            font-size: clamp(1.5rem, 3.5vw, 2rem);
        }

        .introduccion p {
            color: var(--texto-suave);
        }

        .galeria {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 30px;
        }

        .tarjeta {
            min-width: 0;
            overflow: hidden;
            background: var(--blanco);
            border: 1px solid var(--linea);
            border-radius: 16px;
            box-shadow: 0 5px 18px rgba(26, 82, 20, .11);
            transition: transform .25s, box-shadow .25s;
        }

        .tarjeta:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(26, 82, 20, .17);
        }

        .tarjeta img {
            display: block;
            width: 100%;
            height: 320px;
            object-fit: cover;
            background: #e9efec;
        }

        .contenido {
            padding: 23px;
        }

        .contenido h3 {
            margin-bottom: 10px;
            color: var(--verde-oscuro);
            font-family: var(--titulo);
            font-size: 1.4rem;
            line-height: 1.3;
            overflow-wrap: anywhere;
        }

        .contenido p {
            color: var(--texto-suave);
            font-size: 1rem;
            line-height: 1.7;
            overflow-wrap: anywhere;
        }

        .imagen-error {
            width: 100%;
            height: 320px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 20px;
            background: #e9efec;
            color: #68756f;
            text-align: center;
            overflow-wrap: anywhere;
        }

        .imagen-error strong {
            color: var(--verde-oscuro);
        }

        .mensaje-galeria {
            grid-column: 1 / -1;
            padding: 45px 25px;
            background: var(--blanco);
            border: 1px solid var(--linea);
            border-radius: 16px;
            box-shadow: 0 5px 18px rgba(26, 82, 20, .11);
            text-align: center;
        }

        .mensaje-galeria h2 {
            margin-bottom: 10px;
            color: var(--verde-oscuro);
            font-family: var(--titulo);
        }

        .mensaje-galeria p {
            color: var(--texto-suave);
        }

        /* PIE DE PÁGINA */

        .pie-pagina {
            margin-top: auto;
            padding: 28px 20px;
            background: var(--verde-oscuro);
            color: var(--blanco);
            text-align: center;
            font-size: .9rem;
        }

        .pie-pagina p + p {
            margin-top: 5px;
            color: #dcebd5;
        }

        .redes {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 25px;
            margin-top: 15px;
        }

        .redes a {
            color: var(--blanco);
            text-decoration: none;
            font-weight: 800;
        }

        .redes a:hover {
            color: var(--verde-claro);
        }

        /* TABLET */

        @media (max-width: 1100px) {
            .barra-navegacion {
                flex-direction: column;
                align-items: flex-start;
            }

            .nav-enlaces {
                justify-content: flex-start;
                width: 100%;
            }
        }

        /* MÓVILES */

        @media (max-width: 768px) {
            .galeria {
                grid-template-columns: 1fr;
            }

            .encabezado {
                padding: 35px 18px;
            }

            .encabezado .contenedor {
                justify-content: center;
            }

            .texto-encabezado {
                flex-basis: 100%;
                text-align: center;
            }

            .semaforo {
                width: 180px;
                height: 72px;
            }

            .tarjeta img,
            .imagen-error {
                height: 280px;
            }
        }

        @media (max-width: 600px) {
            main {
                padding: 32px 16px 45px;
            }

            .barra-navegacion {
                padding: 12px 16px;
            }

            .nav-enlaces {
                gap: 5px;
            }

            .nav-enlaces a {
                padding: 8px 9px;
                font-size: .8rem;
            }

            .nav-enlaces .enlace-usuario {
                max-width: 100%;
            }

            .contenido {
                padding: 19px;
            }

            .tarjeta img,
            .imagen-error {
                height: 240px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            html {
                scroll-behavior: auto;
            }

            *,
            *::before,
            *::after {
                transition-duration: .01ms !important;
            }
        }
        
/* ===== PIE DE PÁGINA ===== */

.pie-pagina {
    width: 100%;
    margin-top: auto;
    padding: 30px 20px 20px;
    background: #1a5214;
    color: #ffffff;
    text-align: center;
    font-family: 'Roboto', sans-serif;
}

/* Redes sociales */

.iconos-redes {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 25px;
}

.iconos-redes a {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    border: 1px solid rgba(255, 255, 255, 0.18);
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.14);
    color: #ffffff;
    text-decoration: none;
    transition: background 0.2s, transform 0.2s;
}

.iconos-redes a:hover {
    transform: scale(1.08);
    background: rgba(255, 255, 255, 0.28);
}

/* Logos institucionales */

.logos-institucionales {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
    gap: 40px;
    margin: 10px auto 25px;
}

.logo-institucion {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
    gap: 12px;
    width: 210px;
    max-width: 100%;
}

.logo-institucion img {
    display: block;
    width: 180px;
    height: 110px;
    padding: 10px;
    border-radius: 12px;
    background: #ffffff;
    object-fit: contain;
}

.logo-institucion span {
    color: #ffffff;
    font-size: 0.85em;
    line-height: 1.5;
    font-weight: 500;
}

/* Copyright */

.copyright {
    margin-top: 20px;
    padding-top: 18px;
    border-top: 1px solid rgba(255, 255, 255, 0.2);
    color: rgba(255, 255, 255, 0.85);
    font-size: 0.8em;
    line-height: 1.8;
}

/* Adaptación a móviles */

@media screen and (max-width: 480px) {
    .pie-pagina {
        padding: 25px 15px 18px;
    }

    .logo-institucion img {
        width: 155px;
        height: 95px;
    }

    .logos-institucionales {
        gap: 25px;
    }
}

    </style>
</head>

<body>

    <nav class="barra-navegacion" aria-label="Menú principal">

        <a href="./index.php" class="nav-marca">
            <img
                src="./img/logo.jpeg"
                alt="Logo del Semáforo Ambiental"
                onerror="this.onerror=null; this.src='./img/logo.png';"
            >
            <span>Semáforo Ambiental de la Huerta</span>
        </a>

        <div class="nav-enlaces">

            <a href="./index.php">Inicio</a>
            <a href="./quienes-somos.php">Quiénes Somos</a>
            <a href="./fundamentos.php">Fundamentos</a>

            <a
                href="./galeria.php"
                class="activo"
                aria-current="page"
            >Galería</a>

            <a href="./contacto.php">Contactos</a>
            <a href="./nuestras-politicas.php">Nuestras Políticas</a>
            <a href="./servicios.php">Servicios</a>
            <a href="./novedades.php">Novedades</a>

            <?php if ($logueado): ?>

                <span class="enlace-usuario">
                    Hola, <?= $nombreUsuario ?>
                </span>

                <a href="./logout.php" class="boton-cerrar">
                    Cerrar sesión
                </a>

            <?php else: ?>

                <a href="./login.php" class="boton-login">
                    Iniciar sesión
                </a>

            <?php endif; ?>

        </div>
    </nav>

    <header class="encabezado">
        <div class="contenedor">

            <div class="texto-encabezado">
                <h1>Galería</h1>

                <p>
                    Conoce las actividades, los avances y las experiencias
                    del proyecto Semáforo Ambiental de la Huerta.
                </p>
            </div>

            <div
                class="semaforo"
                role="img"
                aria-label="Semáforo ambiental horizontal con luces roja, amarilla y verde"
            >
                <span class="luz" id="luz-roja"></span>
                <span class="luz" id="luz-amarilla"></span>
                <span class="luz" id="luz-verde"></span>
            </div>

        </div>
    </header>

    <main>
        <div class="contenedor">

            <section class="introduccion">
                <h2>Nuestras actividades</h2>

                <p>
                    Un espacio para compartir imágenes y experiencias
                    relacionadas con el cuidado de la huerta escolar
                    y el medio ambiente.
                </p>
            </section>

            <section class="galeria" aria-label="Imágenes de la galería">

                <?php if ($errorGaleria): ?>

                    <div class="mensaje-galeria">
                        <h2>No se pudo cargar la galería</h2>

                        <p>
                            Ocurrió un problema al consultar las imágenes.
                            Intenta nuevamente más tarde.
                        </p>
                    </div>

                <?php elseif ($resultado->num_rows > 0): ?>

                    <?php while ($fila = $resultado->fetch_assoc()): ?>

                        <?php
                        $nombreImagen = trim(
                            (string) ($fila['imagen'] ?? '')
                        );

                        $nombreSeguro = basename(
                            str_replace('\\', '/', $nombreImagen)
                        );

                        $rutaImagen = $rutaImagenes . $nombreSeguro;
                        $archivoImagen = $rutaFisicaImagenes . $nombreSeguro;

                        $imagenDisponible = (
                            $nombreSeguro !== ''
                            && $nombreSeguro !== '.'
                            && $nombreSeguro !== '..'
                            && is_file($archivoImagen)
                        );

                        $tituloImagen = trim(
                            (string) ($fila['titulo'] ?? '')
                        );

                        if ($tituloImagen === '') {
                            $tituloImagen = 'Imagen de la galería';
                        }

                        $descripcionImagen = trim(
                            (string) ($fila['descripcion'] ?? '')
                        );
                        ?>

                        <article class="tarjeta">

                            <?php if ($imagenDisponible): ?>

                                <img
                                    src="<?= e($rutaImagen) ?>"
                                    alt="<?= e($tituloImagen) ?>"
                                    loading="lazy"
                                    onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                >

                                <div
                                    class="imagen-error"
                                    style="display:none;"
                                    role="img"
                                    aria-label="Imagen no disponible"
                                >
                                    <strong>Imagen no disponible</strong>
                                    <span>
                                        No se pudo cargar el archivo de imagen.
                                    </span>
                                </div>

                            <?php else: ?>

                                <div
                                    class="imagen-error"
                                    role="img"
                                    aria-label="Imagen no disponible"
                                >
                                    <strong>Imagen no disponible</strong>

                                    <span>
                                        <?= e(
                                            $nombreSeguro !== ''
                                                ? $nombreSeguro
                                                : 'No se registró una imagen'
                                        ) ?>
                                    </span>
                                </div>

                            <?php endif; ?>

                            <div class="contenido">
                                <h3><?= e($tituloImagen) ?></h3>

                                <p><?= nl2br(e($descripcionImagen)) ?></p>
                            </div>

                        </article>

                    <?php endwhile; ?>

                <?php else: ?>

                    <div class="mensaje-galeria">
                        <h2>No hay imágenes disponibles</h2>

                        <p>
                            Actualmente no hay contenido publicado
                            en la galería. Vuelve a visitarnos pronto.
                        </p>
                    </div>

                <?php endif; ?>

            </section>

        </div>
    </main>

  
<footer class="pie-pagina">

    <!-- Redes sociales -->
    <div class="iconos-redes" aria-label="Redes sociales">

        <a href="https://www.facebook.com/share/1cmyzR78o5/"
           target="_blank" rel="noopener noreferrer"
           title="Facebook" aria-label="Facebook">
            <svg viewBox="0 0 24 24" width="22" height="22"
                 fill="currentColor" aria-hidden="true">
                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
            </svg>
        </a>

        <a href="https://www.instagram.com/i.edu.republicadeuruguay"
           target="_blank" rel="noopener noreferrer"
           title="Instagram" aria-label="Instagram">
            <svg viewBox="0 0 24 24" width="22" height="22"
                 fill="currentColor" aria-hidden="true">
                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/>
            </svg>
        </a>

        <a href="https://www.youtube.com/@canalieru4941"
           target="_blank" rel="noopener noreferrer"
           title="YouTube" aria-label="YouTube">
            <svg viewBox="0 0 24 24" width="22" height="22"
                 fill="currentColor" aria-hidden="true">
                <path d="M23.495 6.205a3.007 3.007 0 0 0-2.088-2.088c-1.87-.501-9.396-.501-9.396-.501s-7.507-.01-9.396.501A3.007 3.007 0 0 0 .527 6.205a31.247 31.247 0 0 0-.522 5.805 31.247 31.247 0 0 0 .522 5.783 3.007 3.007 0 0 0 2.088 2.088c1.868.502 9.396.502 9.396.502s7.506 0 9.396-.502a3.007 3.007 0 0 0 2.088-2.088 31.247 31.247 0 0 0 .5-5.783 31.247 31.247 0 0 0-.5-5.805zM9.609 15.601V8.408l6.264 3.602z"/>
            </svg>
        </a>

    </div>

    <!-- Logos institucionales -->
    <div class="logos-institucionales">

        <div class="logo-institucion">
            <img
                src="./img/pascual-bravo.png"
                alt="Logo de la Institución Universitaria Pascual Bravo"
                loading="lazy"
            >
            <span>
                Institución Universitaria Pascual Bravo
            </span>
        </div>

        <div class="logo-institucion">
            <img
                src="./img/republica-uruguay.png"
                alt="Logo de la Institución Educativa República de Uruguay"
                loading="lazy"
            >
            <span>
                Institución Educativa República de Uruguay
            </span>
        </div>

    </div>

    <!-- Derechos y ubicación -->
    <p class="copyright">
        © 2026 Semáforo Ambiental de la Huerta
        <br>
        I.E. República de Uruguay · Medellín, Antioquia
    </p>

</footer>


</body>
</html>
