
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$logueado = isset($_SESSION['id_usuario'])
    && (int) $_SESSION['id_usuario'] > 0;

$nombreEstudiante = '';

if ($logueado) {
    $nombre = trim((string) ($_SESSION['nombre'] ?? 'Usuario'));

    if ($nombre === '') {
        $nombre = 'Usuario';
    }

    $nombreEstudiante = htmlspecialchars(
        $nombre,
        ENT_QUOTES,
        'UTF-8'
    );
}

$rol = strtolower(trim((string) ($_SESSION['rol'] ?? '')));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Conoce los fundamentos de educación ambiental, agroecología y sostenibilidad del proyecto Semáforo Ambiental de la Huerta.">

    <title>Fundamentos | Semáforo Ambiental de la Huerta</title>

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

        /* NAVEGACIÓN */
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
            padding: 12px 13px;
            border-radius: 9px;
            font-size: 0.9rem;
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

        .nav-enlaces .boton-login:hover {
            background: #b5e8a5;
        }

        .nav-enlaces .saludo-usuario {
            background: #ffffff26;
            color: white;
            font-weight: bold;
            cursor: default;
            white-space: normal;
            overflow-wrap: anywhere;
        }

        .nav-enlaces .boton-salir {
            background: white;
            color: var(--verde-oscuro);
            font-weight: bold;
        }

        .nav-enlaces .boton-salir:hover {
            background: #e8f5e3;
        }

        /* ENCABEZADO */
        .encabezado-pagina {
            background: linear-gradient(
                110deg,
                #1a5214 0%,
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
            font-weight: bold;
        }

        .encabezado-pagina p {
            max-width: 850px;
            font-size: 1.08rem;
            line-height: 1.75;
        }

        /* SEMÁFORO HORIZONTAL */
        .semaforo-encabezado {
            flex: 0 0 auto;
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: center;
            gap: 15px;
            width: 180px;
            height: 71px;
            padding: 12px 17px;
            background: #143d18;
            border: none;
            border-radius: 50px;
            box-shadow: 0 5px 14px #0002;
        }

        .luz-encabezado {
            display: block;
            flex: 0 0 35px;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            border: none;
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

        /* CONTENIDO */
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
            margin: 0 auto 30px;
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

        /* FUNDAMENTOS TEÓRICOS */
        .burbuja {
            background: white;
            padding: 32px;
            border-radius: 16px;
            box-shadow: 0 4px 20px #0000000d;
            border-left: 6px solid var(--verde-claro);
            font-size: 1rem;
            line-height: 1.85;
            color: #444;
        }

        .burbuja p {
            margin-bottom: 18px;
        }

        .burbuja p:last-of-type {
            margin-bottom: 0;
        }

        .burbuja strong {
            color: var(--verde-oscuro);
        }

        .titulo-pilares {
            color: var(--verde-oscuro);
            font-family: Georgia, "Times New Roman", serif;
            font-size: 1.5rem;
            margin: 30px 0 18px;
        }

        /* TARJETAS DE PILARES */
        .pilares {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
            margin-top: 22px;
        }

        .pilar {
            background: #f8fcf6;
            padding: 22px;
            border: 1px solid #dcebd5;
            border-radius: 12px;
            border-top: 4px solid var(--verde-claro);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .pilar:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 14px #0000000d;
        }

        .pilar h3 {
            color: var(--verde-oscuro);
            font-size: 1.05rem;
            margin-bottom: 9px;
        }

        .pilar p {
            color: #555;
            font-size: 0.94rem;
            line-height: 1.7;
        }

        /* IMAGEN INFERIOR */
        .seccion-imagen {
            margin-top: 35px;
            text-align: center;
        }

        .seccion-imagen h2 {
            color: var(--verde-oscuro);
            font-family: Georgia, "Times New Roman", serif;
            font-size: 1.7rem;
            margin-bottom: 12px;
        }

        .seccion-imagen p {
            max-width: 750px;
            margin: 0 auto 20px;
            color: #555;
            line-height: 1.7;
        }

        .imagen-inferior {
            display: block;
            width: 100%;
            max-width: 760px;
            max-height: 360px;
            object-fit: cover;
            margin: 0 auto;
            border-radius: 14px;
            box-shadow: 0 4px 16px #00000020;
        }

        /* PIE DE PÁGINA */
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

        /* TABLET */
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

        /* MÓVILES */
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

            .nav-enlaces .saludo-usuario,
            .nav-enlaces .boton-salir {
                padding: 8px;
                font-size: 0.76rem;
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

            .burbuja {
                padding: 23px 19px;
            }

            .pilares {
                grid-template-columns: 1fr;
            }

            .imagen-inferior {
                max-height: 260px;
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
            <img
                src="./img/logo.jpeg"
                alt="Logo del proyecto"
                onerror="this.style.display='none'"
            >
            <span>Semáforo Ambiental de la Huerta</span>
        </a>

        <div class="nav-enlaces">

            <a href="./index.php">Inicio</a>

            <a href="./quienes-somos.php">Quiénes Somos</a>

            <a
                href="./fundamentos.php"
                class="activo"
                aria-current="page"
            >Fundamentos</a>

            <a href="./galeria.php">Galería</a>

            <a href="./contacto.php">Contactos</a>

            <a href="./nuestras-politicas.php">Nuestras Políticas</a>

            <a href="./servicios.php">Servicios</a>

            <a href="./novedades.php">Novedades</a>

            <?php if ($logueado): ?>

                <span class="saludo-usuario">
                    Hola, <?= $nombreEstudiante ?>
                </span>

                <a href="./logout.php" class="boton-salir">
                    Cerrar sesión
                </a>

            <?php else: ?>

                <a href="./login.php" class="boton-login">
                    Iniciar sesión
                </a>

            <?php endif; ?>

        </div>
    </nav>

    <header class="encabezado-pagina">

        <div class="texto-encabezado">
            <h1>Fundamentos</h1>

            <p>
                Cultivando conciencia para cuidar el planeta y promover
                prácticas sostenibles desde la huerta escolar.
            </p>
        </div>

        <div
            class="semaforo-encabezado"
            role="img"
            aria-label="Semáforo ambiental con luces roja, amarilla y verde"
        >
            <span class="luz-encabezado roja"></span>
            <span class="luz-encabezado amarilla"></span>
            <span class="luz-encabezado verde"></span>
        </div>

    </header>

    <main>
        <div class="contenedor">

            <section class="introduccion">
                <h2>¿En qué se fundamenta el proyecto?</h2>

                <p>
                    El proyecto Semáforo Ambiental de la Huerta promueve
                    la conciencia ecológica y el uso responsable de los
                    recursos naturales mediante el aprendizaje y la
                    participación de la comunidad educativa.
                </p>
            </section>

            <section class="burbuja" aria-label="Fundamentos teóricos">

                <p>
                    Este proyecto se basa en la
                    <strong>educación ambiental</strong>,
                    que busca generar conciencia ecológica y promover prácticas
                    sostenibles en la comunidad educativa.
                </p>

                <p>
                    A través de la <strong>agroecología</strong>, se integran
                    conocimientos ecológicos y culturales en la producción agrícola,
                    fomentando sistemas alimentarios sostenibles.
                </p>

                <p>
                    La <strong>soberanía alimentaria</strong> destaca el derecho
                    de las comunidades a definir sus propias políticas agrícolas
                    y alimentarias, priorizando la producción local y sostenible.
                </p>

                <p>
                    Finalmente, la <strong>ética ambiental</strong> orienta el
                    comportamiento humano hacia una relación respetuosa y
                    responsable con la naturaleza.
                </p>

                <h2 class="titulo-pilares">
                    Pilares fundamentales del proyecto
                </h2>

                <div class="pilares">

                    <article class="pilar">
                        <h3>Conocimiento</h3>
                        <p>
                            Adquisición de información sobre el medio ambiente,
                            los recursos naturales y las problemáticas ecológicas.
                        </p>
                    </article>

                    <article class="pilar">
                        <h3>Análisis y resolución de problemas</h3>
                        <p>
                            Desarrollo de habilidades críticas para comprender
                            y abordar los desafíos ambientales del entorno.
                        </p>
                    </article>

                    <article class="pilar">
                        <h3>Participación y acción</h3>
                        <p>
                            Involucramiento de estudiantes y docentes en la toma
                            de decisiones y en acciones para proteger el ambiente.
                        </p>
                    </article>

                    <article class="pilar">
                        <h3>Diversidad biológica</h3>
                        <p>
                            Promoción de la variedad de especies vegetales y
                            organismos que contribuyen al equilibrio del ecosistema.
                        </p>
                    </article>

                    <article class="pilar">
                        <h3>Ciclos naturales</h3>
                        <p>
                            Reconocimiento de los procesos naturales que ayudan
                            a conservar la salud del suelo y de la huerta.
                        </p>
                    </article>

                    <article class="pilar">
                        <h3>Justicia social</h3>
                        <p>
                            Promoción de la equidad, la cooperación y el acceso
                            responsable a los recursos y beneficios comunitarios.
                        </p>
                    </article>

                    <article class="pilar">
                        <h3>Eficiencia energética</h3>
                        <p>
                            Uso responsable de la energía y de los recursos
                            disponibles en las actividades agrícolas y educativas.
                        </p>
                    </article>

                </div>
            </section>

            <section class="seccion-imagen">

                <h2>Educación ambiental en acción</h2>

                <p>
                    La huerta escolar es un espacio de aprendizaje en el que
                    los estudiantes pueden relacionar los conocimientos
                    ambientales con el cuidado de las plantas y el uso
                    responsable del agua.
                </p>

                <img
                    src="https://proyectosagiles.org/wp-content/uploads/2018/12/innovacic3b3neducativa-fundamentos-ebook-bloggesvin.jpg"
                    class="imagen-inferior"
                    alt="Imagen ilustrativa sobre fundamentos educativos"
                    loading="lazy"
                    onerror="this.style.display='none'"
                >

            </section>

        </div>
    </main>

    <footer class="pie-pagina">

        <div class="logos-institucionales">

            <div class="logo-institucional">
                <img
                    src="./img/republica-uruguay.png"
                    alt="Logo de la Institución Educativa República de Uruguay"
                    onerror="this.style.display='none'"
                >

                <span>
                    I.E. República de Uruguay<br>
                    Medellín, Antioquia
                </span>
            </div>

            <div class="logo-institucional">
                <img
                    src="./img/pascual-bravo.png"
                    alt="Logo de la Institución Universitaria Pascual Bravo"
                    onerror="this.style.display='none'"
                >

                <span>
                    Institución Universitaria<br>
                    Pascual Bravo
                </span>
            </div>

        </div>

        <p>
            © 2026 Semáforo Ambiental de la Huerta<br>
            I.E. República de Uruguay · Medellín, Antioquia<br>
            Educación ambiental · Uso responsable del agua
        </p>

    </footer>

</body>
</html>
