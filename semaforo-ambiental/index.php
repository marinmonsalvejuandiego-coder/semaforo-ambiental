
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$logueado = isset($_SESSION['id_usuario'])
    && (int) $_SESSION['id_usuario'] > 0;

$nombreEstudiante = $logueado
    ? htmlspecialchars(
        (string) (
            $_SESSION['nombre']
            ?? $_SESSION['nombre_completo']
            ?? 'Usuario'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    : '';

$rol = strtolower(trim((string) ($_SESSION['rol'] ?? '')));

$perfil = '';

if ($logueado) {
    if ($rol === 'estudiante') {
        $perfil = './estudiante.php';
    } elseif (in_array($rol, ['docente', 'profesor'], true)) {
        $perfil = './docente.php';
    } elseif ($rol === 'administrador') {
        $perfil = './admin.php';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Proyecto educativo Semáforo Ambiental de la Huerta de la I.E. República de Uruguay, Medellín.">
    <meta name="theme-color" content="#1a5214">

    <title>Semáforo Ambiental de la Huerta</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background: #f4faf2;
            color: #2d2d2d;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        img {
            max-width: 100%;
        }

        a {
            -webkit-tap-highlight-color: transparent;
        }

        /* Navegación */

        .barra-navegacion {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            min-height: 60px;
            background: #1a5214;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 8px 25px;
            z-index: 1000;
            box-shadow: 0 3px 14px rgba(0, 0, 0, 0.25);
        }

        .nav-marca {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
            text-decoration: none;
        }

        .nav-marca img {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: contain;
            background: white;
            border: 2px solid rgba(255, 255, 255, 0.4);
        }

        .nav-marca span {
            max-width: 150px;
            color: white;
            font-size: 0.9em;
            font-weight: 700;
            line-height: 1.2;
        }

        .nav-enlaces {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 3px;
        }

        .nav-enlaces a {
            color: rgba(255, 255, 255, 0.92);
            text-decoration: none;
            padding: 7px 9px;
            border-radius: 6px;
            font-size: 0.8em;
            font-weight: 500;
            white-space: nowrap;
            transition: background 0.2s, color 0.2s;
        }

        .nav-enlaces a:hover {
            background: rgba(255, 255, 255, 0.18);
            color: white;
        }

        .nav-enlaces .enlace-login {
            background: #9dde89;
            color: #1a5214;
            font-weight: 700;
        }

        .nav-enlaces .enlace-login:hover {
            background: white;
            color: #1a5214;
        }

        .nav-enlaces .enlace-usuario {
            display: inline-flex;
            align-items: center;
            max-width: 190px;
            padding: 7px 10px;
            border-radius: 6px;
            color: white;
            background: rgba(255, 255, 255, 0.14);
            font-size: 0.8em;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .nav-enlaces .enlace-cerrar {
            background: white;
            color: #1a5214;
            font-weight: 700;
        }

        .nav-enlaces .enlace-cerrar:hover {
            background: #f1f8ed;
            color: #1a5214;
        }

        /* Presentación principal */

        .hero {
            margin-top: 60px;
            padding: 75px 70px 110px;
            background: linear-gradient(
                135deg,
                #1a5214 0%,
                #2d8020 55%,
                #6abf57 100%
            );
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 50px;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: "";
            position: absolute;
            top: -80px;
            right: -80px;
            width: 350px;
            height: 350px;
            background: rgba(255, 255, 255, 0.04);
            border-radius: 50%;
            pointer-events: none;
        }

        .hero::after {
            content: "";
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 65px;
            background: #f4faf2;
            clip-path: ellipse(55% 100% at 50% 100%);
            pointer-events: none;
        }

        .hero-texto {
            max-width: 560px;
            color: white;
            position: relative;
            z-index: 1;
        }

        .hero-etiqueta {
            display: inline-block;
            padding: 5px 16px;
            margin-bottom: 18px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 30px;
            background: rgba(255, 255, 255, 0.18);
            color: white;
            font-size: 0.78em;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .hero-titulo {
            margin-bottom: 14px;
            font-size: 2.9em;
            font-weight: 900;
            line-height: 1.1;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .hero-slogan {
            margin-bottom: 10px;
            color: #d4f7c5;
            font-size: 1.1em;
            font-style: italic;
            line-height: 1.5;
        }

        .hero-subtitulo {
            margin-bottom: 32px;
            font-size: 0.97em;
            line-height: 1.75;
            font-weight: 300;
            opacity: 0.92;
        }

        .hero-boton {
            display: inline-block;
            padding: 13px 32px;
            border-radius: 30px;
            background: white;
            color: #1a5214;
            text-decoration: none;
            font-size: 0.95em;
            font-weight: 700;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.22);
            transition: transform 0.25s, box-shadow 0.25s;
        }

        .hero-boton:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.28);
        }

        .hero-logo-contenedor {
            flex-shrink: 0;
            position: relative;
            z-index: 1;
        }

        .hero-logo {
            display: block;
            width: 300px;
            height: 300px;
            padding: 18px;
            border: 4px solid rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            object-fit: contain;
            background: white;
            box-shadow: 0 12px 45px rgba(0, 0, 0, 0.3);
        }

        /* Información del proyecto */

        .seccion-info {
            flex: 1;
            padding: 60px 50px 50px;
        }

        .seccion-encabezado {
            margin-bottom: 44px;
            text-align: center;
        }

        .seccion-encabezado h2 {
            margin-bottom: 8px;
            color: #1a5214;
            font-size: 1.75em;
            font-weight: 700;
        }

        .seccion-encabezado p {
            color: #666;
            font-size: 0.97em;
            line-height: 1.6;
        }

        .linea-decorativa {
            width: 55px;
            height: 4px;
            margin: 10px auto 0;
            border-radius: 2px;
            background: linear-gradient(to right, #9dde89, #1a5214);
        }

        .cuadricula-tarjetas {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            max-width: 860px;
            margin: 0 auto;
        }

        .tarjeta {
            padding: 32px 28px;
            border-top: 5px solid #9dde89;
            border-radius: 16px;
            background: white;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.07);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .tarjeta:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.13);
        }

        .tarjeta-barra {
            width: 38px;
            height: 5px;
            margin-bottom: 18px;
            border-radius: 3px;
            background: linear-gradient(to right, #9dde89, #2d8020);
        }

        .tarjeta h3 {
            margin-bottom: 12px;
            color: #1a5214;
            font-size: 1.12em;
            font-weight: 700;
        }

        .tarjeta p {
            color: #555;
            font-size: 0.93em;
            font-weight: 300;
            line-height: 1.75;
        }

        /* Semáforo ambiental */

        .panel-semaforo {
            width: calc(100% - 40px);
            max-width: 1100px;
            margin: 25px auto 55px;
            padding: 35px;
            border-radius: 22px;
            background: white;
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.12);
        }

        .panel-semaforo h2 {
            margin-bottom: 12px;
            color: #1a5214;
            font-size: 1.8em;
            text-align: center;
        }

        .semaforo-descripcion {
            max-width: 750px;
            margin: 0 auto 28px;
            color: #555;
            line-height: 1.7;
            text-align: center;
        }

        .semaforo-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 35px;
        }

        .semaforo-real {
            width: 130px;
            padding: 22px 18px;
            border-radius: 28px;
            background: #1b1b1b;
            box-shadow:
                inset 0 0 20px rgba(255, 255, 255, 0.08),
                0 10px 25px rgba(0, 0, 0, 0.35);
        }

        .luz {
            width: 82px;
            height: 82px;
            margin: 12px auto;
            border: 4px solid #444;
            border-radius: 50%;
            background: #333;
            box-shadow: inset 0 0 12px rgba(0, 0, 0, 0.7);
            transition: background 0.5s, box-shadow 0.5s;
        }

        .poste {
            width: 18px;
            height: 100px;
            margin: 0 auto;
            border-radius: 10px;
            background: #444;
        }

        .info-semaforo {
            width: 100%;
            max-width: 650px;
        }

        .badge-estado {
            display: inline-block;
            margin-bottom: 12px;
            padding: 7px 17px;
            border-radius: 999px;
            background: #23a455;
            color: white;
            font-weight: 700;
        }

        .estado {
            margin-bottom: 12px;
            color: #222;
            font-size: 2rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .explicacion {
            margin-bottom: 18px;
            color: #555;
            line-height: 1.7;
        }

        .recomendacion {
            padding: 18px;
            border-left: 6px solid #2d8020;
            border-radius: 14px;
            background: #eef8ef;
            line-height: 1.7;
        }

        /* Pie de página */

        .pie-pagina {
            width: 100%;
            margin-top: auto;
            padding: 30px 20px 20px;
            background: #1a5214;
            color: white;
            text-align: center;
        }

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
            color: white;
            text-decoration: none;
            transition: background 0.2s, transform 0.2s;
        }

        .iconos-redes a:hover {
            transform: scale(1.08);
            background: rgba(255, 255, 255, 0.28);
        }

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
            background: white;
            object-fit: contain;
        }

        .logo-institucion span {
            color: white;
            font-size: 0.85em;
            line-height: 1.5;
            font-weight: 500;
        }

        .copyright {
            margin-top: 20px;
            padding-top: 18px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            color: rgba(255, 255, 255, 0.85);
            font-size: 0.8em;
            line-height: 1.8;
        }

        .imagen-error {
            display: none;
            max-width: 260px;
            padding: 10px;
            border: 2px dashed #b00020;
            border-radius: 8px;
            color: #b00020;
            background: white;
            font-size: 0.85em;
            text-align: center;
        }

        /* Adaptación a pantallas medianas */

        @media screen and (max-width: 1100px) {
            .barra-navegacion {
                padding: 8px 15px;
            }

            .nav-enlaces a {
                padding: 6px;
                font-size: 0.75em;
            }

            .hero {
                padding-right: 40px;
                padding-left: 40px;
                gap: 30px;
            }

            .hero-titulo {
                font-size: 2.5em;
            }

            .hero-logo {
                width: 250px;
                height: 250px;
            }
        }

        /* Adaptación a tabletas y móviles */

        @media screen and (max-width: 820px) {
            .barra-navegacion {
                position: relative;
                flex-direction: column;
                gap: 12px;
                padding: 12px;
            }

            .nav-marca {
                justify-content: center;
            }

            .nav-marca span {
                max-width: 220px;
            }

            .nav-enlaces {
                justify-content: center;
                width: 100%;
            }

            .nav-enlaces a {
                padding: 8px;
                font-size: 0.8em;
            }

            .nav-enlaces .enlace-usuario {
                max-width: 100%;
                overflow-wrap: anywhere;
            }

            .hero {
                margin-top: 0;
                flex-direction: column-reverse;
                gap: 30px;
                padding: 40px 25px 90px;
                text-align: center;
            }

            .hero-texto {
                max-width: 100%;
            }

            .hero-titulo {
                font-size: 2.3em;
            }

            .hero-logo {
                width: 210px;
                height: 210px;
            }

            .seccion-info {
                padding: 40px 20px;
            }

            .panel-semaforo {
                padding: 25px 18px;
            }
        }

        @media screen and (max-width: 480px) {
            .hero-titulo {
                font-size: 1.9em;
            }

            .hero-slogan {
                font-size: 1em;
            }

            .hero-logo {
                width: 180px;
                height: 180px;
            }

            .seccion-encabezado h2,
            .panel-semaforo h2 {
                font-size: 1.5em;
            }

            .cuadricula-tarjetas {
                grid-template-columns: 1fr;
            }

            .tarjeta {
                padding: 25px 22px;
            }

            .semaforo-wrap {
                gap: 20px;
            }

            .estado {
                font-size: 1.6rem;
            }

            .logo-institucion img {
                width: 155px;
                height: 95px;
            }

            .logos-institucionales {
                gap: 25px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            html {
                scroll-behavior: auto;
            }

            *,
            *::before,
            *::after {
                transition-duration: 0.01ms !important;
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
                onerror="this.style.display='none';"
            >
            <span>Semáforo Ambiental de la Huerta</span>
        </a>

        <div class="nav-enlaces">
            <a href="./index.php" aria-current="page">Inicio</a>
            <a href="./quienes-somos.php">Quiénes Somos</a>
            <a href="./fundamentos.php">Fundamentos</a>
            <a href="./galeria.php">Galería</a>
            <a href="./contacto.php">Contactos</a>
            <a href="./nuestras-politicas.php">Nuestras Políticas</a>
            <a href="./servicios.php">Servicios</a>
            <a href="./novedades.php">Novedades</a>

            <?php if ($logueado): ?>
                <span class="enlace-usuario">
                    Hola, <?= $nombreEstudiante ?>
                </span>

                <?php if ($perfil !== ''): ?>
                    <a href="<?= htmlspecialchars($perfil, ENT_QUOTES, 'UTF-8') ?>">
                        Mi perfil
                    </a>
                <?php endif; ?>

                <a href="./logout.php" class="enlace-cerrar">
                    Cerrar sesión
                </a>
            <?php else: ?>
                <a href="./login.php" class="enlace-login">
                    Iniciar sesión
                </a>
            <?php endif; ?>
        </div>

    </nav>

    <header class="hero">

        <div class="hero-texto">
            <span class="hero-etiqueta">
                I.E. República de Uruguay
            </span>

            <h1 class="hero-titulo">
                Semáforo Ambiental de la Huerta
            </h1>

            <p class="hero-slogan">
                "Cultivando conciencia para cuidar el planeta."
            </p>

            <p class="hero-subtitulo">
                Un espacio para aprender y cuidar nuestro entorno de manera
                sencilla y efectiva. Descubre cómo la educación agroecológica
                puede transformar nuestra comunidad escolar.
            </p>

            <a href="./quienes-somos.php" class="hero-boton">
                Conócenos
            </a>
        </div>

        <div class="hero-logo-contenedor">
            <img
                src="./img/logo.jpeg"
                alt="Logo del proyecto Semáforo Ambiental de la Huerta"
                class="hero-logo"
                onerror="this.style.display='none'; document.getElementById('error-logo-principal').style.display='block';"
            >

            <p id="error-logo-principal" class="imagen-error">
                No se encontró ./img/logo.jpeg. Revisa el nombre y la ubicación del archivo.
            </p>
        </div>

    </header>

    <main class="seccion-info">

        <div class="seccion-encabezado">
            <h2>Sobre el Proyecto</h2>

            <p>
                Conoce nuestros compromisos y el alcance de nuestra iniciativa educativa.
            </p>

            <div class="linea-decorativa" aria-hidden="true"></div>
        </div>

        <div class="cuadricula-tarjetas">

            <article class="tarjeta">
                <div class="tarjeta-barra" aria-hidden="true"></div>

                <h3>Alcance</h3>

                <p>
                    El proyecto incluye actividades educativas en el huerto escolar,
                    talleres de sensibilización y la participación de la comunidad
                    en iniciativas ecológicas. También busca alianzas con entidades
                    externas para fortalecer su sostenibilidad e integra la huerta
                    al currículo escolar como espacio de aprendizaje práctico e
                    interdisciplinario.
                </p>
            </article>

            <article class="tarjeta">
                <div class="tarjeta-barra" aria-hidden="true"></div>

                <h3>Políticas de Calidad</h3>

                <p>
                    Nos comprometemos a ofrecer una formación educativa de excelencia,
                    integrando la teoría y la práctica en el huerto escolar. Buscamos
                    que cada actividad contribuya al desarrollo integral de los
                    estudiantes y al respeto por el medio ambiente, mediante procesos
                    de evaluación y mejora continua.
                </p>
            </article>

        </div>
    </main>

    <section class="panel-semaforo" aria-labelledby="titulo-semaforo">

        <h2 id="titulo-semaforo">
            Semáforo Ambiental Inteligente
        </h2>

        <p class="semaforo-descripcion">
            El semáforo representa diferentes estados de referencia para la huerta.
            La demostración cambia automáticamente entre verde, amarillo y rojo.
            Los estados son ilustrativos y no corresponden a mediciones reales
            de sensores.
        </p>

        <div class="semaforo-wrap">

            <div aria-label="Semáforo de tres luces">
                <div class="semaforo-real">
                    <div id="luz-roja" class="luz"></div>
                    <div id="luz-amarilla" class="luz"></div>
                    <div id="luz-verde" class="luz"></div>
                </div>

                <div class="poste"></div>
            </div>

            <div class="info-semaforo">

                <div id="badge-estado" class="badge-estado">
                    Verde
                </div>

                <h3 id="estado" class="estado">
                    Buen estado
                </h3>

                <p id="explicacion" class="explicacion" aria-live="polite">
                    La humedad, la temperatura y la iluminación deben mantenerse
                    dentro de los rangos adecuados para favorecer el desarrollo
                    de los cultivos.
                </p>

                <div class="recomendacion">
                    <strong>Recomendación:</strong>
                    <span id="recomendacion">
                        Mantener el riego programado y revisar periódicamente las
                        condiciones de la huerta.
                    </span>
                </div>

            </div>
        </div>
    </section>

    <footer class="pie-pagina">

        <div class="iconos-redes" aria-label="Redes sociales">

            <a
                href="https://www.facebook.com/share/1cmyzR78o5/"
                target="_blank"
                rel="noopener noreferrer"
                title="Facebook"
                aria-label="Facebook"
            >
                <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor" aria-hidden="true">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
            </a>

            <a
                href="https://www.instagram.com/i.edu.republicadeuruguay"
                target="_blank"
                rel="noopener noreferrer"
                title="Instagram"
                aria-label="Instagram"
            >
                <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor" aria-hidden="true">
                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/>
                </svg>
            </a>

            <a
                href="https://www.youtube.com/@canalieru4941"
                target="_blank"
                rel="noopener noreferrer"
                title="YouTube"
                aria-label="YouTube"
            >
                <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor" aria-hidden="true">
                    <path d="M23.495 6.205a3.007 3.007 0 0 0-2.088-2.088c-1.87-.501-9.396-.501-9.396-.501s-7.507-.01-9.396.501A3.007 3.007 0 0 0 .527 6.205a31.247 31.247 0 0 0-.522 5.805 31.247 31.247 0 0 0 .522 5.783 3.007 3.007 0 0 0 2.088 2.088c1.868.502 9.396.502 9.396.502s7.506 0 9.396-.502a3.007 3.007 0 0 0 2.088-2.088 31.247 31.247 0 0 0 .5-5.783 31.247 31.247 0 0 0-.5-5.805zM9.609 15.601V8.408l6.264 3.602z"/>
                </svg>
            </a>

        </div>

        <div class="logos-institucionales">

            <div class="logo-institucion">
                <img
                    src="./img/pascual-bravo.png"
                    alt="Logo de la Institución Universitaria Pascual Bravo"
                    loading="lazy"
                    onerror="this.style.display='none'; this.nextElementSibling.textContent='No se encontró ./img/pascual-bravo.png'; this.nextElementSibling.style.color='#ffd6d6';"
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
                    onerror="this.style.display='none'; this.nextElementSibling.textContent='No se encontró ./img/republica-uruguay.png'; this.nextElementSibling.style.color='#ffd6d6';"
                >

                <span>
                    Institución Educativa República de Uruguay
                </span>
            </div>

        </div>

        <p class="copyright">
            © 2026 Semáforo Ambiental de la Huerta
            <br>
            I.E. República de Uruguay · Medellín, Antioquia
        </p>

    </footer>

    <script>
        "use strict";

        const estadosSemaforo = [
            {
                luz: "verde",
                color: "#23a455",
                nombre: "Verde",
                estado: "Buen estado",
                explicacion:
                    "La humedad, la temperatura y la iluminación deben mantenerse dentro de los rangos adecuados para favorecer el desarrollo de los cultivos.",
                recomendacion:
                    "Mantener el riego programado, revisar los sensores periódicamente y registrar las condiciones de la huerta."
            },
            {
                luz: "amarilla",
                color: "#d9a900",
                nombre: "Amarillo",
                estado: "Estado de precaución",
                explicacion:
                    "Uno o varios factores pueden estar saliendo de los rangos recomendados y requieren atención antes de que afecten los cultivos.",
                recomendacion:
                    "Revisar la humedad del suelo, la exposición solar y las condiciones de riego. Volver a comprobar después de hacer los ajustes."
            },
            {
                luz: "roja",
                color: "#e53935",
                nombre: "Rojo",
                estado: "Estado crítico",
                explicacion:
                    "Las condiciones de la huerta podrían perjudicar el crecimiento de los cultivos y requieren una revisión prioritaria.",
                recomendacion:
                    "Comprobar el riego, el drenaje, la exposición solar y el funcionamiento de los sensores antes de tomar medidas correctivas."
            }
        ];

        const luces = {
            roja: document.getElementById("luz-roja"),
            amarilla: document.getElementById("luz-amarilla"),
            verde: document.getElementById("luz-verde")
        };

        const elementoBadge = document.getElementById("badge-estado");
        const elementoEstado = document.getElementById("estado");
        const elementoExplicacion = document.getElementById("explicacion");
        const elementoRecomendacion = document.getElementById("recomendacion");

        let indiceEstado = 0;

        function apagarLuces() {
            Object.values(luces).forEach(function (luz) {
                luz.style.background = "#333";
                luz.style.boxShadow = "inset 0 0 12px rgba(0,0,0,0.7)";
            });
        }

        function mostrarEstado() {
            apagarLuces();

            const estadoActual = estadosSemaforo[indiceEstado];
            const luzActiva = luces[estadoActual.luz];

            luzActiva.style.background = estadoActual.color;
            luzActiva.style.boxShadow =
                "0 0 20px " + estadoActual.color +
                ", 0 0 35px " + estadoActual.color;

            elementoBadge.textContent = estadoActual.nombre;
            elementoBadge.style.background = estadoActual.color;

            elementoEstado.textContent = estadoActual.estado;
            elementoExplicacion.textContent = estadoActual.explicacion;
            elementoRecomendacion.textContent = estadoActual.recomendacion;

            indiceEstado = (indiceEstado + 1) % estadosSemaforo.length;
        }

        mostrarEstado();

        window.setInterval(mostrarEstado, 4500);
    </script>

</body>
</html>
