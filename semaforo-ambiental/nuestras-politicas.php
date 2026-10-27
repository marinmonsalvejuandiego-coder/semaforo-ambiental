
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$logueado = isset($_SESSION['id_usuario'])
    && (int) $_SESSION['id_usuario'] > 0;

$nombreEstudiante = $logueado
    ? htmlspecialchars(
        (string) ($_SESSION['nombre'] ?? 'Usuario'),
        ENT_QUOTES,
        'UTF-8'
    )
    : '';

$rol = strtolower(trim((string) ($_SESSION['rol'] ?? '')));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Nuestras Políticas | Semáforo Ambiental de la Huerta</title>

    <meta name="description" content="Conoce las políticas de seguridad escolar, responsabilidad ambiental e inclusión educativa del proyecto Semáforo Ambiental de la Huerta.">
    <meta name="theme-color" content="#1a5214">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">

    <style>
        :root {
            --verde-oscuro: #1a5214;
            --verde: #2d8020;
            --verde-claro: #9dde89;
            --fondo: #f4faf2;
            --blanco: #ffffff;
            --texto: #333333;
            --gris: #606060;
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
            font-family: 'Roboto', Arial, sans-serif;
            background: var(--fondo);
            color: var(--texto);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        a:focus-visible,
        button:focus-visible {
            outline: 3px solid #ffb719;
            outline-offset: 3px;
        }

        .barra-navegacion {
            position: relative;
            z-index: 1000;
            width: 100%;
            min-height: 67px;
            padding: 8px 2%;
            background: var(--verde-oscuro);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.15);
        }

        .nav-marca {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
            text-decoration: none;
        }

        .nav-marca img {
            width: 47px;
            height: 47px;
            object-fit: cover;
            border-radius: 50%;
            background: white;
        }

        .nav-marca span {
            color: white;
            font-size: 17px;
            font-weight: 700;
            line-height: 1.3;
            max-width: 230px;
        }

        .nav-enlaces {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 4px;
        }

        .nav-enlaces a {
            display: inline-block;
            padding: 12px 13px;
            border-radius: 10px;
            color: white;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            white-space: nowrap;
            transition: background 0.2s;
        }

        .nav-enlaces a:hover,
        .nav-enlaces a.activo {
            background: rgba(255, 255, 255, 0.17);
        }

        .nav-enlaces .btn-login {
            background: var(--verde-claro);
            color: var(--verde-oscuro);
            font-weight: 700;
            margin-left: 3px;
        }

        .nav-enlaces .btn-login:hover {
            background: #b5eca5;
        }

        .nav-enlaces .enlace-usuario {
            display: inline-block;
            max-width: 190px;
            padding: 10px 12px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.14);
            color: white;
            font-size: 13px;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .nav-enlaces .btn-cerrar {
            background: white;
            color: var(--verde-oscuro);
            font-weight: 700;
        }

        .nav-enlaces .btn-cerrar:hover {
            background: #e7f5e1;
        }

        .encabezado-pagina {
            width: 100%;
            min-height: 315px;
            padding: 60px 9%;
            background: linear-gradient(
                110deg,
                #1a5214 0%,
                #2d802b 55%,
                #4a9e3f 100%
            );
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 35px;
        }

        .encabezado-texto {
            flex: 1;
            min-width: 0;
        }

        .encabezado-pagina h1 {
            font-family: Georgia, "Times New Roman", serif;
            font-size: clamp(40px, 4.2vw, 68px);
            font-weight: 900;
            line-height: 1.15;
            margin-bottom: 20px;
        }

        .encabezado-pagina p {
            max-width: 850px;
            font-size: 20px;
            font-weight: 400;
            line-height: 1.85;
            color: white;
        }

        .semaforo {
            width: 180px;
            height: 72px;
            flex-shrink: 0;
            padding: 0 16px;
            margin: 0 0 0 20px;
            background: #103b19;
            border: none;
            border-radius: 45px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            box-shadow: 0 7px 16px rgba(0, 0, 0, 0.16);
        }

        .luz {
            display: block;
            width: 35px;
            height: 35px;
            min-width: 35px;
            border: none;
            border-radius: 50%;
        }

        .luz-roja {
            background: #ed3438;
        }

        .luz-amarilla {
            background: #ffb719;
        }

        .luz-verde {
            background: #2db34a;
        }

        .contenido-principal {
            width: 100%;
            max-width: 1150px;
            margin: 0 auto;
            padding: 45px 25px;
            flex: 1;
        }

        .introduccion {
            max-width: 850px;
            margin: 0 auto 35px;
            text-align: center;
        }

        .introduccion h2 {
            color: var(--verde-oscuro);
            font-size: 27px;
            margin-bottom: 14px;
        }

        .introduccion p {
            color: var(--gris);
            font-size: 16px;
            line-height: 1.8;
        }

        .cuadricula-politicas {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 24px;
            margin-bottom: 38px;
        }

        .tarjeta-politica {
            min-width: 0;
            background: white;
            border-radius: 14px;
            padding: 28px 24px;
            border-top: 5px solid var(--verde-claro);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.07);
            transition: transform 0.25s, box-shadow 0.25s;
        }

        .tarjeta-politica:hover {
            transform: translateY(-5px);
            box-shadow: 0 9px 24px rgba(0, 0, 0, 0.12);
        }

        .numero-politica {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            margin-bottom: 17px;
            border-radius: 50%;
            background: #e8f5e3;
            color: var(--verde-oscuro);
            font-size: 17px;
            font-weight: 900;
        }

        .tarjeta-politica h3 {
            color: var(--verde-oscuro);
            font-size: 18px;
            line-height: 1.45;
            margin-bottom: 15px;
            padding-bottom: 12px;
            border-bottom: 2px solid #e8f5e3;
        }

        .tarjeta-politica p {
            color: var(--gris);
            font-size: 14px;
            line-height: 1.85;
        }

        .tarjeta-institucion {
            display: flex;
            align-items: center;
            gap: 25px;
            padding: 30px;
            border-radius: 14px;
            background: linear-gradient(
                120deg,
                var(--verde-oscuro),
                var(--verde)
            );
            color: white;
            box-shadow: 0 5px 18px rgba(0, 0, 0, 0.1);
        }

        .tarjeta-institucion img {
            width: 85px;
            height: 85px;
            flex-shrink: 0;
            object-fit: contain;
            padding: 7px;
            background: white;
            border-radius: 50%;
        }

        .tarjeta-institucion h2 {
            font-size: 21px;
            margin-bottom: 10px;
        }

        .tarjeta-institucion p {
            font-size: 14px;
            line-height: 1.8;
        }

        .pie-pagina {
            margin-top: 20px;
            padding: 27px 20px 18px;
            text-align: center;
            background: var(--verde-oscuro);
            color: white;
        }

        .iconos-redes {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-bottom: 15px;
        }

        .iconos-redes a {
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.12);
            color: white;
            transition: background 0.2s, transform 0.2s;
        }

        .iconos-redes a:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-3px);
        }

        .iconos-redes svg {
            width: 21px;
            height: 21px;
            fill: currentColor;
        }

        .pie-pagina p {
            font-size: 12px;
            line-height: 1.7;
            opacity: 0.85;
        }

        @media (max-width: 1100px) {
            .barra-navegacion {
                align-items: flex-start;
                flex-direction: column;
                gap: 10px;
                padding: 12px 20px;
            }

            .nav-enlaces {
                width: 100%;
                justify-content: flex-start;
            }

            .cuadricula-politicas {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 650px) {
            .barra-navegacion {
                position: relative;
                padding: 12px 15px;
            }

            .nav-marca span {
                font-size: 15px;
            }

            .nav-enlaces {
                gap: 4px;
            }

            .nav-enlaces a {
                font-size: 12px;
                padding: 9px;
                white-space: normal;
            }

            .encabezado-pagina {
                min-height: auto;
                padding: 40px 24px;
                flex-direction: column;
                align-items: flex-start;
                gap: 25px;
            }

            .encabezado-pagina h1 {
                font-size: 40px;
                margin-bottom: 15px;
            }

            .encabezado-pagina p {
                font-size: 16px;
                line-height: 1.7;
            }

            .semaforo {
                width: 160px;
                height: 62px;
                margin: 0 auto;
                padding: 0 14px;
                gap: 12px;
            }

            .luz {
                width: 30px;
                height: 30px;
                min-width: 30px;
            }

            .contenido-principal {
                padding: 30px 16px;
            }

            .introduccion h2 {
                font-size: 23px;
            }

            .cuadricula-politicas {
                grid-template-columns: 1fr;
                gap: 18px;
            }

            .tarjeta-politica {
                padding: 24px 21px;
            }

            .tarjeta-institucion {
                flex-direction: column;
                align-items: flex-start;
                padding: 25px;
            }

            .tarjeta-institucion img {
                width: 75px;
                height: 75px;
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
                alt="Logo del Semáforo Ambiental de la Huerta"
                onerror="this.onerror=null; this.src='./img/logo.png';"
            >
            <span>Semáforo Ambiental de la Huerta</span>
        </a>

        <div class="nav-enlaces">
            <a href="./index.php">Inicio</a>
            <a href="./quienes-somos.php">Quiénes Somos</a>
            <a href="./fundamentos.php">Fundamentos</a>
            <a href="./galeria.php">Galería</a>
            <a href="./contacto.php">Contacto</a>

            <a
                href="./nuestras-politicas.php"
                class="activo"
                aria-current="page"
            >Nuestras Políticas</a>

            <a href="./servicios.php">Servicios</a>
            <a href="./novedades.php">Novedades</a>

            <?php if ($logueado): ?>
                <span class="enlace-usuario">
                    Hola, <?= $nombreEstudiante ?>
                </span>

                <a href="./logout.php" class="btn-cerrar">
                    Cerrar sesión
                </a>
            <?php else: ?>
                <a href="./login.php" class="btn-login">
                    Iniciar sesión
                </a>
            <?php endif; ?>
        </div>
    </nav>

    <header class="encabezado-pagina">
        <div class="encabezado-texto">
            <h1>Nuestras Políticas</h1>
            <p>
                Conoce nuestro compromiso con la seguridad escolar,
                el cuidado del medio ambiente y la inclusión educativa.
            </p>
        </div>

        <div
            class="semaforo"
            role="img"
            aria-label="Semáforo horizontal con luces roja, amarilla y verde"
        >
            <span class="luz luz-roja"></span>
            <span class="luz luz-amarilla"></span>
            <span class="luz luz-verde"></span>
        </div>
    </header>

    <main class="contenido-principal">

        <section class="introduccion">
            <h2>Compromiso con nuestra comunidad</h2>
            <p>
                El proyecto Semáforo Ambiental de la Huerta promueve el cuidado
                del entorno escolar mediante acciones que fortalecen la seguridad,
                la responsabilidad ambiental y el respeto por la diversidad.
            </p>
        </section>

        <section
            class="cuadricula-politicas"
            aria-label="Políticas institucionales"
        >

            <article class="tarjeta-politica">
                <span class="numero-politica">01</span>
                <h3>Política de Seguridad Escolar</h3>
                <p>
                    Se promueve un entorno escolar seguro mediante protocolos de
                    evacuación, señalización adecuada y formación en primeros
                    auxilios. También se fomenta la participación de los estudiantes
                    en brigadas escolares para fortalecer la prevención y el cuidado
                    de toda la comunidad educativa.
                </p>
            </article>

            <article class="tarjeta-politica">
                <span class="numero-politica">02</span>
                <h3>Política de Responsabilidad Ambiental</h3>
                <p>
                    Se fomenta el uso responsable de los recursos naturales,
                    el reciclaje y las prácticas sostenibles. A través de actividades
                    como el compostaje y el trabajo en la huerta escolar, se fortalece
                    la conciencia ambiental y el compromiso con la protección
                    del planeta.
                </p>
            </article>

            <article class="tarjeta-politica">
                <span class="numero-politica">03</span>
                <h3>Política de Inclusión Educativa</h3>
                <p>
                    Se reconoce y respeta la diversidad de los estudiantes,
                    promoviendo la igualdad de oportunidades y la participación
                    de todos. Se favorecen estrategias pedagógicas adaptadas
                    y ambientes de aprendizaje basados en el respeto, la empatía
                    y la convivencia.
                </p>
            </article>

        </section>

        <section class="tarjeta-institucion">
            <img
                src="./img/logo.jpeg"
                alt="Logo de la Institución Educativa República de Uruguay"
                onerror="this.onerror=null; this.src='./img/logo.png';"
            >

            <div>
                <h2>I.E. República de Uruguay</h2>
                <p>
                    Institución Educativa comprometida con la formación integral
                    de sus estudiantes y con el cuidado del medio ambiente.
                    El proyecto Semáforo Ambiental de la Huerta contribuye a
                    fortalecer las prácticas responsables y el aprendizaje
                    ambiental dentro de la comunidad educativa.
                </p>
            </div>
        </section>

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
