
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$logueado = isset($_SESSION['id_usuario']) && (int) $_SESSION['id_usuario'] > 0;
$nombreUsuario = trim((string) ($_SESSION['nombre'] ?? ''));

if ($logueado && $nombreUsuario === '') {
    $nombreUsuario = 'Usuario';
}

function e($valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Conoce el proyecto Semáforo Ambiental de la Huerta y prueba su simulación de riego responsable.">
    <title>Quiénes Somos | Semáforo Ambiental de la Huerta</title>

    <style>
        :root {
            --verde-oscuro: #1a5214;
            --verde: #388e3c;
            --verde-claro: #9dde89;
            --fondo: #f4faf2;
            --texto: #2d2d2d;
            --rojo: #e53935;
            --amarillo: #ffb81c;
            --verde-luz: #32b44a;
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
            padding: 8px 2%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            background: var(--verde-oscuro);
            box-shadow: 0 3px 12px #0002;
        }

        .nav-marca {
            display: flex;
            align-items: center;
            gap: 10px;
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
            font-weight: bold;
            font-size: 0.95rem;
            max-width: 200px;
            line-height: 1.3;
        }

        .nav-enlaces {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 4px;
        }

        .nav-enlaces a {
            color: white;
            text-decoration: none;
            padding: 12px 11px;
            border-radius: 9px;
            font-size: 0.86rem;
            transition: background 0.2s;
            white-space: nowrap;
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
            background: #b8eda8;
        }

        .usuario-sesion {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 8px;
            padding: 4px;
        }

        .nombre-usuario {
            color: white;
            font-weight: bold;
            font-size: 0.86rem;
            overflow-wrap: anywhere;
        }

        .nav-enlaces .boton-cerrar {
            background: white;
            color: var(--verde-oscuro);
            font-weight: bold;
        }

        .nav-enlaces .boton-cerrar:hover {
            background: #e7eee4;
        }

        .encabezado-pagina {
            position: relative;
            width: 100%;
            min-height: 315px;
            padding: 55px 9%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 35px;
            color: white;
            background: linear-gradient(110deg, #1a5214 0%, #2f7d2c 55%, #4a9e3f 100%);
            overflow: hidden;
        }

        .texto-encabezado {
            max-width: 850px;
            position: relative;
            z-index: 1;
        }

        .encabezado-pagina h1 {
            font-family: Georgia, "Times New Roman", serif;
            font-size: clamp(2.8rem, 5vw, 4.5rem);
            font-weight: 900;
            line-height: 1.1;
            margin-bottom: 20px;
        }

        .encabezado-pagina p {
            max-width: 850px;
            font-size: 1.08rem;
            line-height: 1.8;
        }

        .semaforo-encabezado {
            flex-shrink: 0;
            width: 180px;
            height: 71px;
            padding: 0 23px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            background: #143d18;
            border-radius: 45px;
            box-shadow: 0 5px 14px #0002;
            position: relative;
            z-index: 1;
        }

        .luz-encabezado {
            display: block;
            flex-shrink: 0;
            width: 35px;
            height: 35px;
            border-radius: 50%;
        }

        .luz-encabezado.roja {
            background: var(--rojo);
        }

        .luz-encabezado.amarilla {
            background: var(--amarillo);
        }

        .luz-encabezado.verde {
            background: var(--verde-luz);
        }

        main {
            width: 100%;
            flex: 1;
            padding: 42px 20px;
        }

        .contenedor {
            max-width: 1150px;
            margin: 0 auto;
        }

        .tarjetas {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 20px;
        }

        .tarjeta {
            min-width: 0;
            background: white;
            padding: 25px 22px;
            border-radius: 14px;
            border-top: 5px solid var(--verde-claro);
            box-shadow: 0 4px 15px #0000000d;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .tarjeta:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px #00000014;
        }

        .tarjeta h2 {
            color: var(--verde-oscuro);
            font-size: 1.2rem;
            line-height: 1.4;
            margin-bottom: 12px;
        }

        .tarjeta p {
            color: #555;
            line-height: 1.7;
            font-size: 0.94rem;
        }

        .seccion-simulador {
            margin-top: 42px;
            padding: 30px;
            background: white;
            border-radius: 18px;
            box-shadow: 0 5px 20px #0000000d;
        }

        .titulo-seccion {
            color: var(--verde-oscuro);
            font-family: Georgia, "Times New Roman", serif;
            font-size: clamp(1.7rem, 4vw, 2.2rem);
            text-align: center;
            margin-bottom: 12px;
        }

        .descripcion-seccion {
            max-width: 760px;
            margin: 0 auto 25px;
            text-align: center;
            color: #555;
            line-height: 1.7;
        }

        .panel-datos {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 25px;
        }

        .dato {
            padding: 18px 10px;
            background: #f0f8ed;
            border-radius: 12px;
            text-align: center;
            border: 1px solid #dcebd5;
        }

        .dato span {
            display: block;
            color: #555;
            font-size: 0.9rem;
            margin-bottom: 8px;
        }

        .dato strong {
            font-size: 1.45rem;
            color: var(--verde-oscuro);
        }

        .contenedor-semaforo {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 14px;
            margin: 25px auto 30px;
            padding: 25px 18px;
            background: #f4faf2;
            border: 1px solid #dcebd5;
            border-radius: 15px;
        }

        .contenedor-semaforo h3 {
            color: var(--verde-oscuro);
            font-size: 1.35rem;
            text-align: center;
        }

        .semaforo {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: center;
            gap: 15px;
            width: 180px;
            min-height: 71px;
            padding: 12px 20px;
            background: #143d18;
            border: none;
            border-radius: 45px;
            box-shadow: 0 5px 12px #0002;
        }

        .luz-semaforo {
            display: block;
            flex-shrink: 0;
            width: 31px;
            height: 31px;
            border-radius: 50%;
            opacity: 0.4;
            transition: opacity 0.3s, box-shadow 0.3s, transform 0.3s;
        }

        .luz-roja {
            background: var(--rojo);
        }

        .luz-amarilla {
            background: var(--amarillo);
        }

        .luz-verde {
            background: var(--verde-luz);
        }

        .luz-semaforo.encendida {
            opacity: 1;
            transform: scale(1.06);
        }

        .luz-roja.encendida {
            box-shadow: 0 0 14px #f44336;
        }

        .luz-amarilla.encendida {
            box-shadow: 0 0 14px #ffca28;
        }

        .luz-verde.encendida {
            box-shadow: 0 0 14px #4caf50;
        }

        .estado-semaforo {
            font-size: 1.1rem;
            font-weight: bold;
            text-align: center;
            color: var(--verde-oscuro);
        }

        .descripcion-semaforo {
            max-width: 600px;
            color: #555;
            text-align: center;
            line-height: 1.6;
            font-size: 0.93rem;
        }

        .etiqueta-consumo {
            display: block;
            color: #333;
            line-height: 1.5;
        }

        .barra-consumo {
            width: 100%;
            height: 14px;
            background: #e4e4e4;
            border-radius: 20px;
            overflow: hidden;
            margin: 12px 0 8px;
        }

        .barra-consumo div {
            width: 0%;
            height: 100%;
            background: #38a34a;
            border-radius: inherit;
            transition: width 0.5s, background 0.3s;
        }

        .texto-consumo {
            text-align: right;
            font-size: 0.85rem;
            color: #555;
            margin-bottom: 22px;
        }

        .huerta {
            position: relative;
            overflow: hidden;
            min-height: 310px;
            border-radius: 16px;
            border: 3px solid #b4d69e;
            background: linear-gradient(
                to bottom,
                #bde7ff 0%,
                #e6f7ff 36%,
                #8ccf6b 36%,
                #8ccf6b 100%
            );
        }

        .sol {
            position: absolute;
            top: 15px;
            right: 28px;
            font-size: 2.5rem;
        }

        .nube {
            position: absolute;
            top: 25px;
            left: 14%;
            font-size: 2rem;
        }

        .cartel-huerta {
            position: absolute;
            top: 12px;
            left: 12px;
            padding: 7px 10px;
            border-radius: 7px;
            background: #ffffffdb;
            color: var(--verde-oscuro);
            font-weight: bold;
            font-size: 0.8rem;
        }

        .surco {
            position: absolute;
            left: 4%;
            right: 4%;
            height: 75px;
            bottom: 24px;
            border-radius: 12px;
            background: #95603b;
            border: 4px solid #78492d;
            box-shadow: inset 0 7px 0 #ad7548;
        }

        .plantas {
            position: absolute;
            z-index: 2;
            left: 7%;
            right: 7%;
            bottom: 45px;
            display: flex;
            justify-content: space-around;
            align-items: flex-end;
        }

        .planta {
            width: 19%;
            min-height: 105px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            gap: 4px;
            text-align: center;
            position: relative;
        }

        .planta .emoji-planta {
            font-size: clamp(2.2rem, 5vw, 3.5rem);
            filter: grayscale(0.8);
            transition: filter 0.4s, transform 0.4s;
        }

        .planta.regada .emoji-planta {
            filter: none;
            transform: scale(1.12);
        }

        .planta .nombre-planta {
            font-size: 0.72rem;
            font-weight: bold;
            background: white;
            padding: 4px 7px;
            border-radius: 7px;
            white-space: nowrap;
        }

        .estado-planta {
            font-size: 0.68rem;
            color: white;
            font-weight: bold;
            text-shadow: 0 1px 3px #000;
        }

        .regador {
            position: absolute;
            z-index: 5;
            left: 8%;
            bottom: 105px;
            width: 65px;
            text-align: center;
            transition: left 1s ease-in-out;
        }

        .regador .muñeco {
            font-size: 3.2rem;
            display: block;
            line-height: 1.2;
        }

        .regador .nombre-regador {
            display: inline-block;
            color: #173e12;
            background: white;
            border-radius: 6px;
            padding: 3px 5px;
            font-size: 0.68rem;
            font-weight: bold;
            white-space: nowrap;
        }

        .regador.regando .muñeco {
            animation: movimiento 0.4s infinite alternate;
        }

        .chorro {
            position: absolute;
            display: none;
            left: 42px;
            top: 36px;
            font-size: 1.6rem;
            transform: rotate(25deg);
        }

        .regador.regando .chorro {
            display: block;
            animation: gotas 0.25s infinite alternate;
        }

        @keyframes movimiento {
            from { transform: rotate(-5deg); }
            to { transform: rotate(5deg); }
        }

        @keyframes gotas {
            from { transform: translateY(0) rotate(25deg); }
            to { transform: translateY(8px) rotate(25deg); }
        }

        .alerta {
            display: none;
            padding: 16px;
            margin-top: 18px;
            border-radius: 10px;
            line-height: 1.6;
            font-weight: bold;
        }

        .alerta.visible {
            display: block;
        }

        .alerta.normal {
            background: #e8f7e8;
            color: #205b25;
            border: 1px solid #a8d9a8;
        }

        .alerta.advertencia {
            background: #fff4d7;
            color: #805600;
            border: 1px solid #edce79;
        }

        .alerta.exceso {
            background: #ffe8e6;
            color: #a32119;
            border: 1px solid #e5a39d;
        }

        .controles {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
            margin-top: 20px;
        }

        .boton {
            border: none;
            padding: 13px 20px;
            border-radius: 9px;
            font-size: 0.95rem;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s, opacity 0.2s;
        }

        .boton:hover {
            transform: translateY(-2px);
        }

        .boton:disabled {
            cursor: not-allowed;
            opacity: 0.55;
            transform: none;
        }

        .boton-principal {
            background: var(--verde-oscuro);
            color: white;
        }

        .boton-secundario {
            background: #e7eee4;
            color: #254e20;
        }

        .nota-simulacion {
            margin-top: 16px;
            color: #666;
            font-size: 0.82rem;
            line-height: 1.6;
            text-align: center;
        }

        .pie-pagina {
            margin-top: 35px;
            padding: 30px 20px 18px;
            background: var(--verde-oscuro);
            color: white;
            text-align: center;
        }

        .logos-institucionales {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 35px;
            flex-wrap: wrap;
            margin-bottom: 22px;
        }

        .logo-institucional {
            display: flex;
            align-items: center;
            gap: 10px;
            max-width: 270px;
            text-align: left;
        }

        .logo-institucional img {
            width: 62px;
            height: 62px;
            object-fit: contain;
            background: white;
            border-radius: 9px;
            padding: 4px;
        }

        .logo-institucional span {
            font-size: 0.85rem;
            line-height: 1.4;
            font-weight: bold;
        }

        .pie-pagina p {
            font-size: 0.8rem;
            opacity: 0.85;
            line-height: 1.6;
        }

        @media (max-width: 1150px) {
            .barra-navegacion {
                align-items: flex-start;
                flex-direction: column;
                gap: 8px;
            }

            .nav-enlaces {
                justify-content: flex-start;
                width: 100%;
            }

            .nav-enlaces a {
                padding: 9px 10px;
            }

            .tarjetas {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .encabezado-pagina {
                padding: 45px 6%;
            }
        }

        @media (max-width: 700px) {
            .barra-navegacion {
                padding: 10px 4%;
            }

            .nav-marca span {
                font-size: 0.88rem;
            }

            .nav-enlaces {
                gap: 3px;
            }

            .nav-enlaces a {
                padding: 8px;
                font-size: 0.78rem;
            }

            .usuario-sesion {
                width: 100%;
                justify-content: flex-start;
            }

            .encabezado-pagina {
                min-height: auto;
                flex-direction: column;
                align-items: flex-start;
                padding: 40px 6% 48px;
                gap: 25px;
            }

            .encabezado-pagina h1 {
                font-size: clamp(2.5rem, 10vw, 3.5rem);
            }

            .encabezado-pagina p {
                font-size: 1rem;
            }

            .semaforo-encabezado {
                width: 180px;
                height: 71px;
                align-self: flex-start;
            }

            main {
                padding: 25px 12px;
            }

            .tarjetas {
                grid-template-columns: 1fr;
            }

            .panel-datos {
                grid-template-columns: 1fr;
            }

            .seccion-simulador {
                padding: 22px 13px;
            }

            .huerta {
                min-height: 285px;
            }

            .planta .nombre-planta {
                font-size: 0.62rem;
                padding: 3px;
            }

            .planta .emoji-planta {
                font-size: 2.2rem;
            }

            .regador {
                width: 50px;
            }

            .regador .muñeco {
                font-size: 2.7rem;
            }

            .surco {
                left: 2%;
                right: 2%;
            }

            .semaforo {
                width: 165px;
                min-height: 65px;
                gap: 12px;
                padding: 10px 16px;
            }

            .luz-semaforo {
                width: 27px;
                height: 27px;
            }

            .logos-institucionales {
                gap: 20px;
            }
        }

        @media (max-width: 400px) {
            .nav-marca img {
                width: 40px;
                height: 40px;
            }

            .nav-marca span {
                max-width: 180px;
                font-size: 0.8rem;
            }

            .nav-enlaces a {
                font-size: 0.73rem;
                padding: 7px;
            }

            .seccion-simulador {
                padding: 18px 10px;
            }

            .planta .emoji-planta {
                font-size: 1.8rem;
            }

            .planta .nombre-planta {
                white-space: normal;
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
        <img src="./img/logo.jpeg" alt="Logo del proyecto">
        <span>Semáforo Ambiental de la Huerta</span>
    </a>

    <div class="nav-enlaces">
        <a href="./index.php">Inicio</a>
        <a href="./quienes-somos.php" class="activo" aria-current="page">Quiénes Somos</a>
        <a href="./fundamentos.php">Fundamentos</a>
        <a href="./galeria.php">Galería</a>
        <a href="./contacto.php">Contactos</a>
        <a href="./nuestras-politicas.php">Nuestras Políticas</a>
        <a href="./servicios.php">Servicios</a>
        <a href="./novedades.php">Novedades</a>

        <?php if ($logueado): ?>
            <div class="usuario-sesion">
                <span class="nombre-usuario">
                    Hola, <?= e($nombreUsuario) ?>
                </span>
                <a href="./logout.php" class="boton-cerrar">Cerrar sesión</a>
            </div>
        <?php else: ?>
            <a href="./login.php" class="boton-login">Iniciar sesión</a>
        <?php endif; ?>
    </div>
</nav>

<header class="encabezado-pagina">
    <div class="texto-encabezado">
        <h1>Quiénes Somos</h1>
        <p>
            Conoce nuestro compromiso con la huerta escolar, el cuidado del
            medio ambiente y el uso responsable de los recursos naturales.
        </p>
    </div>

    <div class="semaforo-encabezado" role="img" aria-label="Semáforo con luces roja, amarilla y verde">
        <span class="luz-encabezado roja"></span>
        <span class="luz-encabezado amarilla"></span>
        <span class="luz-encabezado verde"></span>
    </div>
</header>

<main>
    <div class="contenedor">

        <section class="tarjetas" aria-label="Información institucional">
            <article class="tarjeta">
                <h2>Antecedentes</h2>
                <p>
                    En nuestra institución se busca fortalecer la conciencia
                    ambiental, promover el reciclaje y fomentar el uso responsable
                    del agua. La huerta escolar ofrece un espacio práctico para
                    aprender, colaborar y cuidar el entorno.
                </p>
            </article>

            <article class="tarjeta">
                <h2>Misión</h2>
                <p>
                    Impulsar una educación ambiental integral mediante prácticas
                    agroecológicas en la huerta escolar, promoviendo el aprendizaje
                    activo, la participación comunitaria y el uso responsable de
                    los recursos naturales.
                </p>
            </article>

            <article class="tarjeta">
                <h2>Visión</h2>
                <p>
                    Ser un referente educativo en el uso de huertas escolares,
                    fomentando hábitos sostenibles e integrando la teoría y la
                    práctica para formar estudiantes conscientes y comprometidos
                    con el medio ambiente.
                </p>
            </article>

            <article class="tarjeta">
                <h2>Valores</h2>
                <p>
                    Promovemos la responsabilidad, el respeto por la naturaleza,
                    la colaboración, la creatividad y el compromiso ambiental,
                    involucrando a estudiantes, docentes y familias en el cuidado
                    de nuestro entorno.
                </p>
            </article>
        </section>

        <section class="seccion-simulador" aria-labelledby="titulo-simulador">
            <h2 class="titulo-seccion" id="titulo-simulador">
                Simulador de riego de la huerta
            </h2>

            <p class="descripcion-seccion">
                Observa cómo el jardinero riega las plantas, controla el consumo
                de agua y activa el semáforo ambiental según la cantidad de agua
                utilizada.
            </p>

            <div class="panel-datos">
                <div class="dato">
                    <span>Agua utilizada</span>
                    <strong id="agua-usada">0 L</strong>
                </div>

                <div class="dato">
                    <span>Plantas regadas</span>
                    <strong id="plantas-regadas">0 de 4</strong>
                </div>

                <div class="dato">
                    <span>Estado ambiental</span>
                    <strong id="estado-ambiental">Verde</strong>
                </div>
            </div>

            <section class="contenedor-semaforo" aria-labelledby="titulo-semaforo">
                <h3 id="titulo-semaforo">Semáforo ambiental</h3>

                <div class="semaforo" role="img" aria-label="Semáforo ambiental de consumo de agua">
                    <span id="luz-roja" class="luz-semaforo luz-roja"></span>
                    <span id="luz-amarilla" class="luz-semaforo luz-amarilla"></span>
                    <span id="luz-verde" class="luz-semaforo luz-verde encendida"></span>
                </div>

                <p class="estado-semaforo" id="nombre-semaforo">
                    VERDE: consumo responsable
                </p>

                <p class="descripcion-semaforo" id="descripcion-semaforo">
                    El consumo de agua está dentro del límite establecido.
                </p>
            </section>

            <label class="etiqueta-consumo" for="relleno-agua">
                <strong>Consumo de agua frente al límite de 18 litros</strong>
            </label>

            <div class="barra-consumo"
                 id="barra-agua"
                 role="progressbar"
                 aria-label="Consumo de agua"
                 aria-valuemin="0"
                 aria-valuemax="18"
                 aria-valuenow="0">
                <div id="relleno-agua"></div>
            </div>

            <p class="texto-consumo" id="texto-consumo">0 de 18 litros</p>

            <div class="huerta" role="img" aria-label="Escenario ilustrado de la huerta escolar">
                <span class="sol" aria-hidden="true">☀️</span>
                <span class="nube" aria-hidden="true">☁️</span>
                <span class="cartel-huerta">HUERTA ESCOLAR</span>

                <div class="surco"></div>

                <div class="plantas">
                    <div class="planta" id="planta-0">
                        <span class="emoji-planta" aria-hidden="true">🥬</span>
                        <span class="nombre-planta">Lechuga</span>
                        <span class="estado-planta">Necesita agua</span>
                    </div>

                    <div class="planta" id="planta-1">
                        <span class="emoji-planta" aria-hidden="true">🍅</span>
                        <span class="nombre-planta">Tomate</span>
                        <span class="estado-planta">Necesita agua</span>
                    </div>

                    <div class="planta" id="planta-2">
                        <span class="emoji-planta" aria-hidden="true">🥕</span>
                        <span class="nombre-planta">Zanahoria</span>
                        <span class="estado-planta">Necesita agua</span>
                    </div>

                    <div class="planta" id="planta-3">
                        <span class="emoji-planta" aria-hidden="true">🌿</span>
                        <span class="nombre-planta">Hierbas</span>
                        <span class="estado-planta">Necesita agua</span>
                    </div>
                </div>

                <div class="regador" id="regador" aria-label="Jardinero">
                    <span class="muñeco" aria-hidden="true">🧑‍🌾</span>
                    <span class="nombre-regador">Jardinero</span>
                    <span class="chorro" aria-hidden="true">💦</span>
                </div>
            </div>

            <div id="alerta" class="alerta visible normal" role="status" aria-live="polite">
                La huerta está lista. Puedes comenzar el riego.
            </div>

            <div class="controles">
                <button type="button" class="boton boton-principal" id="boton-regar">
                    Regar siguiente planta
                </button>

                <button type="button" class="boton boton-secundario" id="boton-reiniciar">
                    Reiniciar simulación
                </button>
            </div>

            <p class="nota-simulacion">
                Esta es una simulación educativa. Cada riego consume 6 litros
                ficticios y el límite de referencia es de 18 litros. Al regar
                la cuarta planta se supera el límite. Los valores no representan
                mediciones reales de la huerta.
            </p>
        </section>

    </div>
</main>

<footer class="pie-pagina">
    <div class="logos-institucionales">
        <div class="logo-institucional">
            <img src="./img/republica-uruguay.png"
                 alt="Logo de la Institución Educativa República de Uruguay">
            <span>
                I.E. República de Uruguay<br>
                Medellín, Antioquia
            </span>
        </div>

        <div class="logo-institucional">
            <img src="./img/pascual-bravo.png"
                 alt="Logo de la Institución Universitaria Pascual Bravo">
            <span>
                Institución Universitaria<br>
                Pascual Bravo
            </span>
        </div>
    </div>

    <p>
        © <?= date('Y') ?> Semáforo Ambiental de la Huerta<br>
        Educación ambiental · Uso responsable del agua
    </p>
</footer>

<script>
"use strict";

const LITROS_POR_RIEGO = 6;
const LIMITE_AGUA = 18;
const TOTAL_PLANTAS = 4;

let aguaUsada = 0;
let siguientePlanta = 0;
let regando = false;
let temporizadores = [];

const botonRegar = document.getElementById("boton-regar");
const botonReiniciar = document.getElementById("boton-reiniciar");
const regador = document.getElementById("regador");
const alerta = document.getElementById("alerta");

const aguaTexto = document.getElementById("agua-usada");
const plantasTexto = document.getElementById("plantas-regadas");
const estadoTexto = document.getElementById("estado-ambiental");

const rellenoAgua = document.getElementById("relleno-agua");
const textoConsumo = document.getElementById("texto-consumo");
const barraAgua = document.getElementById("barra-agua");

const luzRoja = document.getElementById("luz-roja");
const luzAmarilla = document.getElementById("luz-amarilla");
const luzVerde = document.getElementById("luz-verde");

const nombreSemaforo = document.getElementById("nombre-semaforo");
const descripcionSemaforo = document.getElementById("descripcion-semaforo");

function esperar(tiempo, funcion) {
    const temporizador = window.setTimeout(function () {
        temporizadores = temporizadores.filter(function (id) {
            return id !== temporizador;
        });

        funcion();
    }, tiempo);

    temporizadores.push(temporizador);
}

function mostrarAlerta(mensaje, tipo) {
    alerta.textContent = mensaje;
    alerta.className = "alerta visible " + tipo;
}

function actualizarSemaforo(estado) {
    luzRoja.classList.remove("encendida");
    luzAmarilla.classList.remove("encendida");
    luzVerde.classList.remove("encendida");

    if (estado === "rojo") {
        luzRoja.classList.add("encendida");
        nombreSemaforo.textContent = "ROJO: exceso de agua";
        nombreSemaforo.style.color = "#b3261e";
        descripcionSemaforo.textContent =
            "Se superó el límite de 18 litros. Es necesario reducir el consumo y planificar mejor el riego.";
    } else if (estado === "amarillo") {
        luzAmarilla.classList.add("encendida");
        nombreSemaforo.textContent = "AMARILLO: límite alcanzado";
        nombreSemaforo.style.color = "#805600";
        descripcionSemaforo.textContent =
            "Se alcanzaron los 18 litros permitidos. Se recomienda no utilizar más agua.";
    } else {
        luzVerde.classList.add("encendida");
        nombreSemaforo.textContent = "VERDE: consumo responsable";
        nombreSemaforo.style.color = "#1a5214";
        descripcionSemaforo.textContent =
            "El consumo de agua está dentro del límite establecido. Continúa utilizando el agua responsablemente.";
    }
}

function actualizarPanel() {
    aguaTexto.textContent = aguaUsada + " L";
    plantasTexto.textContent = siguientePlanta + " de " + TOTAL_PLANTAS;

    const porcentaje = Math.min((aguaUsada / LIMITE_AGUA) * 100, 100);

    rellenoAgua.style.width = porcentaje + "%";
    barraAgua.setAttribute("aria-valuenow", aguaUsada);
    textoConsumo.textContent = aguaUsada + " de " + LIMITE_AGUA + " litros";

    if (aguaUsada > LIMITE_AGUA) {
        rellenoAgua.style.background = "#d93025";
        estadoTexto.textContent = "Rojo";
        estadoTexto.style.color = "#b3261e";
        actualizarSemaforo("rojo");
    } else if (aguaUsada === LIMITE_AGUA) {
        rellenoAgua.style.background = "#e6a700";
        estadoTexto.textContent = "Amarillo";
        estadoTexto.style.color = "#805600";
        actualizarSemaforo("amarillo");
    } else {
        rellenoAgua.style.background = "#38a34a";
        estadoTexto.textContent = "Verde";
        estadoTexto.style.color = "#1a5214";
        actualizarSemaforo("verde");
    }

    botonRegar.disabled = regando || siguientePlanta >= TOTAL_PLANTAS;
}

function moverJardinero(indice) {
    const posiciones = [9, 32, 55, 78];
    regador.style.left = posiciones[indice] + "%";
}

function regarSiguientePlanta() {
    if (regando || siguientePlanta >= TOTAL_PLANTAS) {
        return;
    }

    regando = true;
    const indice = siguientePlanta;

    botonRegar.disabled = true;
    botonReiniciar.disabled = true;

    mostrarAlerta("El jardinero se está acercando a la planta...", "normal");
    moverJardinero(indice);

    esperar(850, function () {
        regador.classList.add("regando");

        mostrarAlerta(
            "Regando la planta. Se están utilizando " + LITROS_POR_RIEGO + " litros de agua.",
            "normal"
        );

        esperar(1400, function () {
            aguaUsada += LITROS_POR_RIEGO;

            const planta = document.getElementById("planta-" + indice);
            planta.classList.add("regada");
            planta.querySelector(".estado-planta").textContent = "Regada";

            siguientePlanta++;
            regador.classList.remove("regando");

            actualizarPanel();

            if (aguaUsada > LIMITE_AGUA) {
                mostrarAlerta(
                    "ALERTA DE EXCESO DE AGUA. Se han utilizado " + aguaUsada +
                    " litros y el límite era de " + LIMITE_AGUA +
                    " litros. Es necesario reducir el consumo.",
                    "exceso"
                );
            } else if (aguaUsada === LIMITE_AGUA) {
                mostrarAlerta(
                    "ADVERTENCIA. Has alcanzado el límite de " + LIMITE_AGUA +
                    " litros. Evita consumir más agua.",
                    "advertencia"
                );
            } else if (siguientePlanta === TOTAL_PLANTAS) {
                mostrarAlerta(
                    "Todas las plantas han sido regadas. El consumo total fue de " +
                    aguaUsada + " litros, dentro del límite establecido.",
                    "normal"
                );
            } else {
                mostrarAlerta(
                    "Planta regada. Consumo acumulado: " + aguaUsada +
                    " litros. Puedes continuar con la siguiente.",
                    "normal"
                );
            }

            regando = false;
            botonReiniciar.disabled = false;
            actualizarPanel();
        });
    });
}

function reiniciarSimulacion() {
    if (regando) {
        return;
    }

    temporizadores.forEach(function (temporizador) {
        window.clearTimeout(temporizador);
    });

    temporizadores = [];
    aguaUsada = 0;
    siguientePlanta = 0;

    for (let i = 0; i < TOTAL_PLANTAS; i++) {
        const planta = document.getElementById("planta-" + i);
        planta.classList.remove("regada");
        planta.querySelector(".estado-planta").textContent = "Necesita agua";
    }

    regador.style.left = "8%";
    regador.classList.remove("regando");

    botonReiniciar.disabled = false;

    mostrarAlerta(
        "Simulación reiniciada. La huerta está lista para comenzar.",
        "normal"
    );

    actualizarPanel();
}

botonRegar.addEventListener("click", regarSiguientePlanta);
botonReiniciar.addEventListener("click", reiniciarSimulacion);

actualizarPanel();
</script>

</body>
</html>
