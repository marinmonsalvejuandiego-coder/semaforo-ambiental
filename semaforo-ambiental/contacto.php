
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/conexion.php';

$mensaje_exito = "";
$mensaje_error = "";

$valores = [
    "nombre" => "",
    "correo" => "",
    "mensaje" => ""
];

$logueado = isset($_SESSION["id_usuario"])
    && (int) $_SESSION["id_usuario"] > 0;

$nombreUsuario = htmlspecialchars(
    (string) ($_SESSION["nombre"] ?? "Usuario"),
    ENT_QUOTES,
    "UTF-8"
);

$idUsuario = $logueado ? (int) $_SESSION["id_usuario"] : null;

function e($texto): string
{
    return htmlspecialchars(
        (string) ($texto ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    foreach ($valores as $campo => $vacio) {
        $valores[$campo] = trim((string) ($_POST[$campo] ?? ""));
    }

    $nombre = $valores["nombre"];
    $correo = $valores["correo"];
    $mensaje = $valores["mensaje"];

    if ($nombre === "" || $correo === "" || $mensaje === "") {
        $mensaje_error = "Por favor, completa todos los campos.";
    } elseif (strlen($nombre) > 100) {
        $mensaje_error = "El nombre no puede superar los 100 caracteres.";
    } elseif (strlen($correo) > 120) {
        $mensaje_error = "El correo no puede superar los 120 caracteres.";
    } elseif (strlen($mensaje) > 1000) {
        $mensaje_error = "El mensaje no puede superar los 1000 caracteres.";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje_error = "Ingresa un correo electrónico válido.";
    } else {
        $sql = "INSERT INTO tbl_contactos
                (id_usuario, nombre, correo, mensaje, fecha)
                VALUES (?, ?, ?, ?, NOW())";

        $stmt = $conexion->prepare($sql);

        if ($stmt) {
            $stmt->bind_param(
                "isss",
                $idUsuario,
                $nombre,
                $correo,
                $mensaje
            );

            if ($stmt->execute()) {
                $mensaje_exito = "Mensaje enviado correctamente. Gracias por contactarnos.";

                $valores = [
                    "nombre" => "",
                    "correo" => "",
                    "mensaje" => ""
                ];
            } else {
                error_log("Error al guardar contacto: " . $stmt->error);
                $mensaje_error = "No se pudo enviar el mensaje. Inténtalo de nuevo.";
            }

            $stmt->close();
        } else {
            error_log("Error al preparar contacto: " . $conexion->error);
            $mensaje_error = "No se pudo preparar el registro del mensaje.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Contacto | Semáforo Ambiental de la Huerta</title>

    <meta name="description" content="Contacta con el proyecto Semáforo Ambiental de la Huerta de la I.E. República de Uruguay, Medellín.">
    <meta name="theme-color" content="#1a5214">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=Nunito+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">

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
            --rojo: #ed3438;
            --amarillo: #ffb719;
            --verde-luz: #2db34a;
            --titulo: "Fraunces", Georgia, serif;
            --cuerpo: "Nunito Sans", Arial, sans-serif;
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

        a:focus-visible,
        button:focus-visible {
            outline: 3px solid var(--amarillo);
            outline-offset: 3px;
        }

        .contenedor {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }

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

        .encabezado-pagina {
            position: relative;
            overflow: hidden;
            padding: 45px 20px;
            background: linear-gradient(135deg, #1a5214, #388e3c);
            color: var(--blanco);
            border-bottom: 5px solid var(--verde-claro);
        }

        .encabezado-pagina .contenedor {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 25px;
            flex-wrap: wrap;
        }

        .texto-encabezado {
            flex: 1 1 300px;
        }

        .encabezado-pagina h1 {
            margin-bottom: 12px;
            font-family: var(--titulo);
            font-size: clamp(2.2rem, 5vw, 3.2rem);
            line-height: 1.15;
        }

        .encabezado-pagina p {
            max-width: 650px;
            color: #edf8e9;
            font-size: 1.05rem;
        }

        .mini-semaforo {
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
            box-shadow: inset 0 2px 5px rgba(0, 0, 0, .3),
                        0 5px 12px rgba(0, 0, 0, .18);
        }

        .mini-semaforo span {
            display: block;
            width: 35px;
            height: 35px;
            min-width: 35px;
            border-radius: 50%;
            border: 2px solid rgba(0, 0, 0, .16);
            box-shadow: inset 0 2px 4px rgba(255, 255, 255, .28),
                        0 2px 5px rgba(0, 0, 0, .2);
        }

        .mini-semaforo span:nth-child(1) {
            background: var(--rojo);
        }

        .mini-semaforo span:nth-child(2) {
            background: var(--amarillo);
        }

        .mini-semaforo span:nth-child(3) {
            background: var(--verde-luz);
        }

        main {
            flex: 1;
            padding: 50px 20px 60px;
        }

        .titulo-seccion {
            margin-bottom: 10px;
            color: var(--verde-oscuro);
            font-family: var(--titulo);
            font-size: clamp(1.5rem, 3.5vw, 2rem);
            line-height: 1.25;
        }

        .intro-seccion {
            margin-bottom: 24px;
            color: var(--texto-suave);
        }

        .grid-contacto {
            display: grid;
            grid-template-columns: minmax(0, 5fr) minmax(0, 7fr);
            align-items: start;
            gap: 36px;
        }

        .datos-contacto dl {
            margin-top: 6px;
        }

        .datos-contacto .fila {
            padding: 17px 0;
            border-top: 2px solid var(--verde-claro);
        }

        .datos-contacto dt {
            color: var(--verde-oscuro);
            font-family: var(--titulo);
            font-size: 1.08rem;
            font-weight: 700;
        }

        .datos-contacto dd {
            color: var(--texto-suave);
            font-size: .97rem;
            overflow-wrap: anywhere;
        }

        .datos-contacto dd a {
            color: var(--verde);
            font-weight: 700;
        }

        .datos-contacto dd a:hover {
            color: var(--verde-oscuro);
        }

        .formulario {
            min-width: 0;
            padding: 34px;
            background: var(--blanco);
            border-radius: 20px;
            border-top: 6px solid var(--verde);
            box-shadow: 0 8px 30px rgba(26, 82, 20, .11);
        }

        .campo {
            min-width: 0;
            margin-bottom: 20px;
        }

        .campo label {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-bottom: 7px;
            color: var(--verde-oscuro);
            font-size: .9rem;
            font-weight: 800;
        }

        .campo label small {
            color: var(--texto-suave);
            font-weight: 400;
            white-space: nowrap;
        }

        .campo input,
        .campo textarea {
            display: block;
            width: 100%;
            padding: 13px 15px;
            border: 2px solid var(--linea);
            border-radius: 12px;
            background: #f7faf3;
            color: var(--texto);
            font: 400 1rem var(--cuerpo);
            transition: border-color .2s, background .2s, box-shadow .2s;
        }

        .campo input::placeholder,
        .campo textarea::placeholder {
            color: #879187;
        }

        .campo input:hover,
        .campo textarea:hover {
            border-color: #b6cfa6;
        }

        .campo input:focus,
        .campo textarea:focus {
            outline: none;
            background: var(--blanco);
            border-color: var(--verde);
            box-shadow: 0 0 0 4px rgba(56, 142, 60, .12);
        }

        .campo textarea {
            min-height: 150px;
            resize: vertical;
        }

        .fila-doble {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 12px;
            background: var(--verde-oscuro);
            color: var(--blanco);
            font: 800 1.05rem var(--cuerpo);
            cursor: pointer;
            transition: transform .15s, background .2s;
        }

        .btn:hover {
            background: var(--verde);
            transform: translateY(-2px);
        }

        .btn:disabled {
            opacity: .65;
            cursor: wait;
            transform: none;
        }

        .privacidad {
            margin-top: 12px;
            color: var(--texto-suave);
            font-size: .8rem;
            text-align: center;
        }

        .aviso {
            margin-bottom: 22px;
            padding: 13px 16px;
            border-left: 5px solid;
            border-radius: 10px;
            font-size: .95rem;
            font-weight: 700;
        }

        .aviso.exito {
            background: #e6f5e2;
            color: #1f5a27;
            border-color: var(--verde-luz);
        }

        .aviso.error {
            background: #fde5e2;
            color: #9c1f17;
            border-color: var(--rojo);
        }

        .seccion-mapa {
            margin-top: 56px;
        }

        .seccion-mapa iframe {
            display: block;
            width: 100%;
            height: 360px;
            border: 0;
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(26, 82, 20, .11);
        }

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

        @media (max-width: 980px) {
            .barra-navegacion {
                flex-direction: column;
                align-items: flex-start;
            }

            .nav-enlaces {
                justify-content: flex-start;
                width: 100%;
            }

            .grid-contacto {
                grid-template-columns: 1fr;
                gap: 30px;
            }
        }

        @media (max-width: 768px) {
            .encabezado-pagina {
                padding: 35px 18px;
            }

            .encabezado-pagina .contenedor {
                justify-content: center;
            }

            .texto-encabezado {
                flex-basis: 100%;
                text-align: center;
            }

            .mini-semaforo {
                margin: 0 auto;
            }
        }

        @media (max-width: 600px) {
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

            main {
                padding: 35px 16px 45px;
            }

            .fila-doble {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .formulario {
                padding: 24px 18px;
            }

            .seccion-mapa iframe {
                height: 280px;
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
                alt="Logo del proyecto"
                onerror="this.onerror=null; this.src='./img/logo.png';"
            >
            <span>Semáforo Ambiental de la Huerta</span>
        </a>

        <div class="nav-enlaces">
            <a href="./index.php">Inicio</a>
            <a href="./quienes-somos.php">Quiénes Somos</a>
            <a href="./fundamentos.php">Fundamentos</a>
            <a href="./galeria.php">Galería</a>

            <a href="./contacto.php" class="activo" aria-current="page">
                Contacto
            </a>

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

    <header class="encabezado-pagina">
        <div class="contenedor">
            <div class="texto-encabezado">
                <h1>Contacto</h1>
                <p>
                    Escríbenos tus preguntas, ideas o comentarios sobre la huerta.
                    I.E. República de Uruguay, Medellín.
                </p>
            </div>

            <div class="mini-semaforo" aria-hidden="true">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </header>

    <main>
        <div class="contenedor">

            <section class="grid-contacto" aria-label="Información y formulario de contacto">

                <div class="datos-contacto">
                    <h2 class="titulo-seccion">Información de contacto</h2>

                    <p class="intro-seccion">
                        Nuestra plataforma permite monitorear las condiciones
                        ambientales de la huerta y ofrecer recomendaciones para
                        el cuidado de los cultivos.
                    </p>

                    <dl>
                        <div class="fila">
                            <dt>Institución</dt>
                            <dd>I.E. República de Uruguay</dd>
                        </div>

                        <div class="fila">
                            <dt>Dirección</dt>
                            <dd>
                                Calle 91a # 70 - 193<br>
                                Medellín, Antioquia, Colombia
                            </dd>
                        </div>

                        <div class="fila">
                            <dt>Sitio web</dt>
                            <dd>
                                <a
                                    href="https://ieru.edu.co/"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >ieru.edu.co</a>
                            </dd>
                        </div>

                        <div class="fila">
                            <dt>Proyecto</dt>
                            <dd>Semáforo Ambiental de la Huerta</dd>
                        </div>
                    </dl>
                </div>

                <div class="formulario">
                    <h2 class="titulo-seccion">Envíanos un mensaje</h2>

                    <p class="intro-seccion">
                        Completa los campos y envíanos tus preguntas o sugerencias.
                    </p>

                    <?php if ($mensaje_exito !== ""): ?>
                        <div class="aviso exito" role="status" aria-live="polite">
                            <?= e($mensaje_exito) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($mensaje_error !== ""): ?>
                        <div class="aviso error" role="alert">
                            <?= e($mensaje_error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="./contacto.php" id="form-contacto">

                        <div class="fila-doble">
                            <div class="campo">
                                <label for="nombre">Nombre completo</label>
                                <input
                                    type="text"
                                    id="nombre"
                                    name="nombre"
                                    placeholder="Tu nombre"
                                    maxlength="100"
                                    autocomplete="name"
                                    required
                                    value="<?= e($valores['nombre']) ?>"
                                >
                            </div>

                            <div class="campo">
                                <label for="correo">Correo electrónico</label>
                                <input
                                    type="email"
                                    id="correo"
                                    name="correo"
                                    placeholder="ejemplo@correo.com"
                                    maxlength="120"
                                    autocomplete="email"
                                    required
                                    value="<?= e($valores['correo']) ?>"
                                >
                            </div>
                        </div>

                        <div class="campo">
                            <label for="mensaje">
                                Mensaje
                                <small id="contador">0 / 1000</small>
                            </label>

                            <textarea
                                id="mensaje"
                                name="mensaje"
                                placeholder="Cuéntanos en qué podemos ayudarte..."
                                maxlength="1000"
                                required
                            ><?= e($valores['mensaje']) ?></textarea>
                        </div>

                        <button type="submit" class="btn" id="btn-enviar">
                            Enviar mensaje
                        </button>

                        <p class="privacidad">
                            Usaremos los datos proporcionados para gestionar tu mensaje.
                        </p>
                    </form>
                </div>

            </section>

            <section class="seccion-mapa" aria-labelledby="titulo-mapa">
                <h2 class="titulo-seccion" id="titulo-mapa">¿Dónde estamos?</h2>

                <p class="intro-seccion">
                    Encuéntranos en la I.E. República de Uruguay, en Medellín.
                </p>

                <iframe
                    title="Mapa de la I.E. República de Uruguay"
                    src="https://www.google.com/maps?q=I.E.+Republica+de+Uruguay+Medellin&output=embed"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    allowfullscreen>
                </iframe>
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


    <script>
        const campoMensaje = document.getElementById("mensaje");
        const contador = document.getElementById("contador");
        const formulario = document.getElementById("form-contacto");
        const botonEnviar = document.getElementById("btn-enviar");

        function actualizarContador() {
            contador.textContent = campoMensaje.value.length + " / 1000";
        }

        campoMensaje.addEventListener("input", actualizarContador);
        actualizarContador();

        formulario.addEventListener("submit", function () {
            if (formulario.checkValidity()) {
                botonEnviar.disabled = true;
                botonEnviar.textContent = "Enviando...";
            }
        });
    </script>

</body>
</html>
