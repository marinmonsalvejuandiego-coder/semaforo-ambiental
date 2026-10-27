<?php
session_start();

// IMPORTANTE: login.php está dentro de /html y conexion.php está en /config.
require_once __DIR__ . '/config/conexion.php';
$mensaje = '';
$tipoMensaje = 'error';
$panelActivo = 'login';

// Comprueba que el archivo de conexión haya creado una conexión MySQLi válida.
if (!isset($conexion) || !($conexion instanceof mysqli)) {
    http_response_code(500);
    exit('No se pudo conectar con la base de datos. Revisa config/conexion.php.');
}

mysqli_report(MYSQLI_REPORT_OFF);
$conexion->set_charset('utf8mb4');

function redirigirSegunRol($rol)
{
    $rol = strtolower(trim((string) $rol));

    if ($rol === 'administrador') {
        header('Location: admin.php');
        exit;
    }

    if (in_array($rol, ['profesor', 'docente', 'estudiante'], true)) {
        header('Location: index.php');
        exit;
    }

    // No dejar una sesión abierta si el rol no es reconocido.
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit;
}

if (isset($_SESSION['id_usuario'], $_SESSION['rol'])) {
    redirigirSegunRol($_SESSION['rol']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $panelActivo = ($accion === 'registro') ? 'registro' : 'login';

    try {
        if ($accion === 'login') {
            $correo = trim((string) ($_POST['correo'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');

            if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || $password === '') {
                $mensaje = 'Ingresa un correo válido y tu contraseña.';
            } else {
                // Los nombres coinciden con las columnas de tbl_usuarios.
                $sql = 'SELECT id_usuario, nombre_completo, correo, password, rol
                        FROM tbl_usuarios
                        WHERE correo = ?
                        LIMIT 1';
                $stmt = $conexion->prepare($sql);

                if (!$stmt) {
                    $mensaje = 'No fue posible consultar los usuarios. Revisa la conexión y la estructura de la tabla.';
                } else {
                    $stmt->bind_param('s', $correo);

                    if (!$stmt->execute()) {
                        $mensaje = 'Ocurrió un error al consultar la cuenta. Inténtalo nuevamente.';
                        $stmt->close();
                    } else {
                        $stmt->bind_result($idUsuario, $nombreCompleto, $correoBD, $hashBD, $rolBD);
                        $encontrado = $stmt->fetch();
                        $stmt->close();

                        if ($encontrado && is_string($hashBD) && password_verify($password, $hashBD)) {
                            $rol = strtolower(trim((string) $rolBD));

                            if (!in_array($rol, ['administrador', 'profesor', 'docente', 'estudiante'], true)) {
                                $mensaje = 'La cuenta no tiene un rol válido para ingresar.';
                            } else {
                                session_regenerate_id(true);
                                $_SESSION['id_usuario'] = (int) $idUsuario;
                                $_SESSION['nombre'] = (string) $nombreCompleto;
                                $_SESSION['correo'] = (string) $correoBD;
                                $_SESSION['rol'] = $rol;
                                redirigirSegunRol($rol);
                            }
                        } else {
                            $mensaje = 'Correo o contraseña incorrectos. Verifica tus datos.';
                        }
                    }
                }
            }
        } elseif ($accion === 'registro') {
            $nombre = trim((string) ($_POST['nombre'] ?? ''));
            $correo = trim((string) ($_POST['correo'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            $confirmacion = (string) ($_POST['confirmacion'] ?? '');

            if ($nombre === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
                $mensaje = 'Completa los datos y utiliza una contraseña de al menos 8 caracteres.';
            } elseif ($password !== $confirmacion) {
                $mensaje = 'Las contraseñas no coinciden.';
            } else {
                $consulta = $conexion->prepare(
                    'SELECT id_usuario FROM tbl_usuarios WHERE correo = ? LIMIT 1'
                );

                if (!$consulta) {
                    $mensaje = 'No fue posible consultar los usuarios. Revisa la conexión y la estructura de la tabla.';
                } else {
                    $consulta->bind_param('s', $correo);
                    $consulta->execute();
                    $consulta->bind_result($idExistente);
                    $existe = $consulta->fetch();
                    $consulta->close();

                    if ($existe) {
                        $mensaje = 'Este correo ya está registrado.';
                        $panelActivo = 'registro';
                    } else {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $conexion->prepare(
                            "INSERT INTO tbl_usuarios (nombre_completo, correo, password, rol)
                             VALUES (?, ?, ?, 'estudiante')"
                        );

                        if (!$stmt) {
                            $mensaje = 'No fue posible preparar el registro. Verifica las columnas de tbl_usuarios.';
                            $panelActivo = 'registro';
                        } else {
                            $stmt->bind_param('sss', $nombre, $correo, $hash);

                            if ($stmt->execute()) {
                                $mensaje = 'Cuenta creada correctamente. Ya puedes iniciar sesión.';
                                $tipoMensaje = 'success';
                                $panelActivo = 'login';
                            } else {
                                $mensaje = 'No fue posible crear la cuenta. Comprueba que el correo no esté repetido e inténtalo de nuevo.';
                                $panelActivo = 'registro';
                            }
                            $stmt->close();
                        }
                    }
                }
            }
        }
    } catch (Throwable $e) {
        // El detalle técnico se consulta en el registro de errores de PHP; no se muestra al usuario.
        error_log('Error en login.php: ' . $e->getMessage());
        $mensaje = 'Ocurrió un error al procesar la solicitud. Revisa la conexión y los nombres de las columnas.';
    }
}
?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">



    <title>Iniciar sesión | Semáforo Ambiental de la Huerta</title>



    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">



    <style>

        :root {

            --verde-oscuro: #1a5214;

            --verde-medio: #2d8020;

            --verde-claro: #4a9e3f;

            --verde-lima: #9dde89;

            --amarillo: #f5c842;

            --rojo: #e84040;

            --blanco: #fff;

            --crema: #f4faf2;

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

            overflow-x: hidden;

            font-family: 'Nunito', sans-serif;

            color: #263b24;

            background: #0d3b0a;

        }



        button,

        input {

            font: inherit;

        }



        button {

            cursor: pointer;

        }



        .fondo {

            position: fixed;

            inset: 0;

            z-index: 0;

            overflow: hidden;

            pointer-events: none;

            background: linear-gradient(

                135deg,

                #0d3b0a,

                #1a5214 40%,

                #2d8020 70%,

                #4a9e3f

            );

        }



        .fondo::before,

        .fondo::after {

            position: absolute;

            content: "";

            border-radius: 50%;

            pointer-events: none;

        }



        .fondo::before {

            width: 500px;

            height: 500px;

            top: -200px;

            right: -200px;

            background: radial-gradient(

                circle,

                rgba(157, 222, 137, .18),

                transparent 70%

            );

            animation: pulso 6s ease-in-out infinite alternate;

        }



        .fondo::after {

            width: 400px;

            height: 400px;

            bottom: -150px;

            left: -150px;

            background: radial-gradient(

                circle,

                rgba(245, 200, 66, .12),

                transparent 70%

            );

            animation: pulso 8s ease-in-out infinite alternate;

        }



        .hoja {

            position: absolute;

            font-size: 1.5rem;

            opacity: .12;

            animation: flotar linear infinite;

        }



        @keyframes pulso {

            from { transform: scale(1); }

            to { transform: scale(1.2); }

        }



        @keyframes flotar {

            0% {

                transform: translateY(110vh) rotate(0);

                opacity: 0;

            }

            10%, 90% { opacity: .12; }

            100% {

                transform: translateY(-10vh) rotate(360deg);

                opacity: 0;

            }

        }



        @keyframes bajar {

            from {

                opacity: 0;

                transform: translateY(-15px);

            }

            to {

                opacity: 1;

                transform: translateY(0);

            }

        }



        @keyframes subir {

            from {

                opacity: 0;

                transform: translateY(15px);

            }

            to {

                opacity: 1;

                transform: translateY(0);

            }

        }



        @keyframes parpadeo {

            0%, 100% {

                opacity: 1;

            }

            50% {

                opacity: .65;

            }

        }



        .contenedor {

            position: relative;

            z-index: 1;

            display: flex;

            flex-direction: column;

            align-items: center;

            gap: 15px;

            width: 100%;

            min-height: 100vh;

            padding: 22px 18px 0;

        }



        .cabecera {

            width: 100%;

            max-width: 420px;

            text-align: center;

            animation: bajar .6s ease both;

        }



        .semaforo-deco {

            display: flex;

            justify-content: center;

            gap: 7px;

            margin-bottom: 9px;

        }



        .luz {

            width: 10px;

            height: 10px;

            border-radius: 50%;

            box-shadow: 0 0 8px currentColor;

            animation: parpadeo 3s ease-in-out infinite;

        }



        .luz-roja {

            color: #ff4d4d;

            background: #ff4d4d;

        }



        .luz-amarilla {

            color: #ffd700;

            background: #ffd700;

            animation-delay: 1s;

        }



        .luz-verde {

            color: #4ade80;

            background: #4ade80;

            animation-delay: 2s;

        }



        .logo-wrap {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 11px;

            max-width: 100%;

            padding: 7px 15px 7px 8px;

            margin-bottom: 8px;

            border: 1px solid rgba(255, 255, 255, .25);

            border-radius: 50px;

            background: rgba(255, 255, 255, .10);

            backdrop-filter: blur(10px);

        }



        .logo-wrap img {

            display: block;

            width: 44px;

            height: 44px;

            flex-shrink: 0;

            border: 2px solid var(--verde-lima);

            border-radius: 50%;

            object-fit: cover;

        }



        .logo-wrap span {

            max-width: 240px;

            color: white;

            font-size: .92rem;

            font-weight: 900;

            line-height: 1.25;

            text-align: left;

            overflow-wrap: anywhere;

        }



        .subtitulo-logo {

            color: rgba(255, 255, 255, .82);

            font-size: .72rem;

            font-weight: 700;

            letter-spacing: 1px;

            text-transform: uppercase;

        }



        /* SEMÁFORO AMBIENTAL COMPACTO */



        .imagen-huerta {

            width: 100%;

            max-width: 420px;

            overflow: hidden;

            border: 1px solid rgba(255, 255, 255, .25);

            border-radius: 18px;

            background: rgba(255, 255, 255, .10);

            box-shadow: 0 8px 24px rgba(0, 0, 0, .15);

            animation: bajar .7s ease both;

        }



        .contenido-semaforo {

            display: flex;

            flex-direction: column;

            align-items: center;

            gap: 12px;

            padding: 16px 16px 14px;

            text-align: center;

        }



        .semaforo {

            display: flex;

            flex-direction: row;

            align-items: center;

            justify-content: center;

            gap: 10px;

            padding: 8px 13px;

            border: 3px solid #263626;

            border-radius: 30px;

            background: #263626;

            box-shadow: 0 5px 12px rgba(0, 0, 0, .2);

        }



        .luz-semaforo {

            display: block;

            width: 29px;

            height: 29px;

            flex-shrink: 0;

            border: 3px solid rgba(255, 255, 255, .35);

            border-radius: 50%;

        }



        .luz-semaforo.roja {

            background: #ff4d4d;

            box-shadow: 0 0 9px rgba(255, 77, 77, .35);

        }



        .luz-semaforo.amarilla {

            background: #ffd700;

            box-shadow: 0 0 9px rgba(255, 215, 0, .3);

        }



        .luz-semaforo.verde {

            background: #4ade80;

            box-shadow: 0 0 9px rgba(74, 222, 128, .35);

        }



        .informacion-semaforo {

            width: 100%;

            color: white;

        }



        .informacion-semaforo h2 {

            margin-bottom: 5px;

            font-size: 1.12rem;

            font-weight: 900;

        }



        .informacion-semaforo p {

            margin-bottom: 10px;

            font-size: .81rem;

            line-height: 1.45;

        }



        .estados-semaforo {

            display: flex;

            flex-wrap: wrap;

            align-items: center;

            justify-content: center;

            gap: 7px 12px;

        }



        .estados-semaforo span {

            display: flex;

            align-items: center;

            gap: 5px;

            font-size: .73rem;

            font-weight: 800;

        }



        .punto {

            display: inline-block;

            width: 8px;

            height: 8px;

            flex-shrink: 0;

            border-radius: 50%;

        }



        .punto.rojo {

            background: #ff8585;

        }



        .punto.amarillo {

            background: #ffe66d;

        }



        .punto.verde {

            background: #8cf5b0;

        }



        .imagen-huerta figcaption {

            padding: 8px 10px;

            border-top: 1px solid rgba(255, 255, 255, .15);

            color: rgba(255, 255, 255, .9);

            font-size: .72rem;

            font-weight: 700;

            line-height: 1.4;

            text-align: center;

            background: rgba(0, 0, 0, .10);

        }



        /* FORMULARIO */



        .tarjeta {

            width: 100%;

            max-width: 420px;

            padding: 25px 25px;

            border: 1px solid rgba(255, 255, 255, .65);

            border-radius: 22px;

            background: rgba(255, 255, 255, .97);

            box-shadow: 0 18px 50px rgba(0, 0, 0, .25);

            animation: subir .6s ease .1s both;

        }



        .pestanas {

            display: flex;

            gap: 5px;

            padding: 4px;

            margin-bottom: 20px;

            border-radius: 12px;

            background: #f0f7ee;

        }



        .pestana-btn {

            flex: 1;

            padding: 10px 5px;

            border: none;

            border-radius: 9px;

            color: #61845b;

            background: transparent;

            font-size: .84rem;

            font-weight: 800;

            transition: .2s;

        }



        .pestana-btn.active {

            color: white;

            background: linear-gradient(

                135deg,

                var(--verde-oscuro),

                var(--verde-claro)

            );

            box-shadow: 0 3px 10px rgba(26, 82, 20, .2);

        }



        .form-header {

            margin-bottom: 19px;

            text-align: center;

        }



        .form-header h1 {

            margin-bottom: 5px;

            color: var(--verde-oscuro);

            font-family: 'Playfair Display', serif;

            font-size: 1.65rem;

        }



        .form-header p {

            color: #709469;

            font-size: .85rem;

            font-weight: 700;

        }



        .campo {

            margin-bottom: 14px;

        }



        .campo label {

            display: block;

            margin-bottom: 6px;

            color: #456340;

            font-size: .82rem;

            font-weight: 800;

        }



        input[type="email"],

        input[type="password"],

        input[type="text"] {

            display: block;

            width: 100%;

            min-width: 0;

            padding: 11px 40px 11px 12px;

            border: 2px solid #d4e8cf;

            border-radius: 10px;

            outline: none;

            color: #2d2d2d;

            background: #fafffe;

            font-size: .88rem;

            transition: border-color .2s, box-shadow .2s;

        }



        input:focus {

            border-color: var(--verde-claro);

            box-shadow: 0 0 0 3px rgba(74, 158, 63, .13);

        }



        .input-rel {

            position: relative;

        }



        .btn-ojo {

            position: absolute;

            top: 50%;

            right: 10px;

            transform: translateY(-50%);

            padding: 3px;

            border: none;

            background: transparent;

            font-size: .95rem;

        }



        .error-msg {

            display: block;

            margin-top: 4px;

            color: #c0392b;

            font-size: .75rem;

            font-weight: 700;

        }



        /* ACCESO DE DEMOSTRACIÓN */



        .acceso-rapido {

            padding: 11px;

            margin-bottom: 16px;

            border: 1px solid #c8e8c0;

            border-radius: 12px;

            background: linear-gradient(135deg, #f0f7ee, #e8f5e3);

        }



        .acceso-rapido > p {

            margin-bottom: 9px;

            color: #456340;

            font-size: .78rem;

            font-weight: 800;

            text-align: center;

        }



        .btns-acceso {

            display: flex;

            gap: 8px;

        }



        .btn-acceso {

            flex: 1;

            min-width: 0;

            padding: 8px 7px;

            border: 1px solid #9dde89;

            border-radius: 9px;

            background: white;

            text-align: left;

            transition: .2s;

        }



        .btn-acceso:hover {

            border-color: var(--verde-claro);

            background: #f5fff1;

        }



        .btn-acceso strong,

        .btn-acceso small {

            display: block;

        }



        .btn-acceso strong {

            color: var(--verde-oscuro);

            font-size: .77rem;

        }



        .btn-acceso small {

            margin-top: 3px;

            color: #777;

            font-size: .67rem;

        }



        /* FORTALEZA DE CONTRASEÑA */



        #fortaleza {

            height: 5px;

            overflow: hidden;

            margin-top: 7px;

            border-radius: 5px;

            background: #e0e0e0;

        }



        #barraFuerza {

            width: 0;

            height: 100%;

            border-radius: 5px;

            transition: width .3s, background .3s;

        }



        #textoFuerza {

            display: block;

            min-height: 17px;

            margin-top: 4px;

            color: #555;

            font-size: .73rem;

            font-weight: 700;

        }



        /* BOTÓN PRINCIPAL */



        .btn-enviar {

            width: 100%;

            padding: 12px;

            margin-top: 6px;

            border: none;

            border-radius: 11px;

            color: white;

            background: linear-gradient(

                135deg,

                var(--verde-oscuro),

                var(--verde-claro)

            );

            box-shadow: 0 4px 12px rgba(26, 82, 20, .2);

            font-size: .92rem;

            font-weight: 900;

            transition: transform .2s, box-shadow .2s;

        }



        .btn-enviar:hover {

            transform: translateY(-1px);

            box-shadow: 0 6px 16px rgba(26, 82, 20, .25);

        }



        .btn-enviar:active {

            transform: scale(.98);

        }



        .alerta {

            display: none;

            padding: 10px 12px;

            margin-bottom: 13px;

            border-radius: 9px;

            font-size: .82rem;

            font-weight: 700;

        }



        .alerta.error {

            display: block;

            border: 1px solid #ffcdd2;

            color: #c0392b;

            background: #ffebee;

        }



        .alerta.success {

            display: block;

            border: 1px solid #c8e6c9;

            color: #1b5e20;

            background: #e8f5e9;

        }



        .cambiar-panel {

            margin-top: 14px;

            color: #777;

            font-size: .81rem;

            text-align: center;

        }



        .cambiar-panel a {

            color: var(--verde-oscuro);

            font-weight: 900;

            text-decoration: none;

        }



        .cambiar-panel a:hover {

            text-decoration: underline;

        }



        /* PIE DE PÁGINA */



        .footer {

            width: calc(100% + 36px);

            margin-top: 4px;

            padding: 22px 18px 18px;

            border-top: 1px solid rgba(255, 255, 255, .2);

            color: white;

            background: rgba(5, 35, 5, .4);

            text-align: center;

        }



        .footer-titulo {

            margin-bottom: 13px;

            font-size: .84rem;

            font-weight: 800;

        }



        .footer-logos {

            display: flex;

            flex-wrap: wrap;

            align-items: stretch;

            justify-content: center;

            gap: 12px;

            max-width: 420px;

            margin: 0 auto 15px;

        }



        .footer-institucion {

            display: flex;

            flex-direction: column;

            align-items: center;

            justify-content: center;

            gap: 7px;

            width: 165px;

            min-height: 112px;

            padding: 10px;

            border: 1px solid rgba(255, 255, 255, .4);

            border-radius: 13px;

            color: #264b22;

            background: rgba(255, 255, 255, .95);

        }



        .footer-institucion img {

            display: block;

            width: 60px;

            height: 60px;

            object-fit: contain;

        }



        .footer-institucion span {

            font-size: .73rem;

            font-weight: 900;

            text-align: center;

        }



        .footer-creditos {

            color: rgba(255, 255, 255, .78);

            font-size: .75rem;

            line-height: 1.7;

        }



        /* RESPONSIVE */



        @media (max-width: 480px) {

            .contenedor {

                gap: 13px;

                padding: 18px 12px 0;

            }



            .cabecera {

                max-width: 100%;

            }



            .logo-wrap span {

                max-width: 220px;

                font-size: .85rem;

            }



            .subtitulo-logo {

                font-size: .66rem;

                letter-spacing: .7px;

            }



            .imagen-huerta {

                max-width: 100%;

            }



            .contenido-semaforo {

                gap: 10px;

                padding: 13px 10px 12px;

            }



            .semaforo {

                gap: 9px;

                padding: 7px 12px;

            }



            .luz-semaforo {

                width: 26px;

                height: 26px;

            }



            .informacion-semaforo h2 {

                font-size: 1.02rem;

            }



            .informacion-semaforo p {

                font-size: .78rem;

            }



            .estados-semaforo {

                gap: 7px 10px;

            }



            .estados-semaforo span {

                font-size: .69rem;

            }



            .tarjeta {

                padding: 22px 17px;

                border-radius: 19px;

            }



            .form-header h1 {

                font-size: 1.5rem;

            }



            .footer {

                width: calc(100% + 24px);

                padding-right: 12px;

                padding-left: 12px;

            }



            .footer-logos {

                gap: 9px;

            }



            .footer-institucion {

                width: min(145px, calc(50% - 5px));

                min-height: 105px;

                padding: 9px;

            }



            .footer-institucion img {

                width: 53px;

                height: 53px;

            }

        }



        @media (max-width: 350px) {

            .tarjeta {

                padding: 19px 12px;

            }



            .btn-acceso strong {

                font-size: .7rem;

            }



            .btn-acceso small {

                font-size: .62rem;

            }



            .estados-semaforo {

                flex-direction: column;

                align-items: flex-start;

            }

        }



        @media (prefers-reduced-motion: reduce) {

            *,

            *::before,

            *::after {

                animation-duration: .01ms !important;

                animation-iteration-count: 1 !important;

                scroll-behavior: auto !important;

                transition-duration: .01ms !important;

            }

        }

    </style>

</head>



<body>



    <div class="fondo" aria-hidden="true">

        <span class="hoja" style="left:10%;animation-duration:12s">🍃</span>

        <span class="hoja" style="left:25%;animation-duration:15s;animation-delay:3s">🌿</span>

        <span class="hoja" style="left:50%;animation-duration:10s;animation-delay:1s">🍃</span>

        <span class="hoja" style="left:70%;animation-duration:14s;animation-delay:5s">🌿</span>

        <span class="hoja" style="left:85%;animation-duration:11s;animation-delay:2s">🍃</span>

    </div>



    <div class="contenedor">



        <header class="cabecera">



            <div class="semaforo-deco" aria-hidden="true">

                <span class="luz luz-roja"></span>

                <span class="luz luz-amarilla"></span>

                <span class="luz luz-verde"></span>

            </div>



            <div class="logo-wrap">

                <img

                    src="img/logo.jpeg"

                    alt="Logo del Semáforo Ambiental"

                    onerror="this.onerror=null;this.src='img/logo.jpg';"

                >

                <span>Semáforo Ambiental de la Huerta</span>

            </div>



            <p class="subtitulo-logo">

                I. E. República de Uruguay · Medellín

            </p>



        </header>



        <!-- SEMÁFORO AMBIENTAL PRINCIPAL -->



        <figure class="imagen-huerta">



            <div class="contenido-semaforo">



                <div class="semaforo" aria-label="Semáforo de tres luces">

                    <span class="luz-semaforo roja"></span>

                    <span class="luz-semaforo amarilla"></span>

                    <span class="luz-semaforo verde"></span>

                </div>



                <div class="informacion-semaforo">



                    <h2>Semáforo Ambiental</h2>





            </div>



            <figcaption>

                Cultivamos conciencia, cuidamos nuestro planeta.

            </figcaption>



        </figure>



        <!-- FORMULARIO PRINCIPAL -->



        <main class="tarjeta">



            <div class="pestanas" role="tablist" aria-label="Acceso y registro">



                <button

                    type="button"

                    class="pestana-btn <?= $panelActivo === 'login' ? 'active' : '' ?>"

                    id="tabLogin"

                    role="tab"

                    aria-selected="<?= $panelActivo === 'login' ? 'true' : 'false' ?>"

                    aria-controls="panelLogin"

                    onclick="mostrarTab('login')"

                >

                    🔑 Iniciar sesión

                </button>



                <button

                    type="button"

                    class="pestana-btn <?= $panelActivo === 'registro' ? 'active' : '' ?>"

                    id="tabRegister"

                    role="tab"

                    aria-selected="<?= $panelActivo === 'registro' ? 'true' : 'false' ?>"

                    aria-controls="panelRegistro"

                    onclick="mostrarTab('registro')"

                >

                    🌱 Registrarse

                </button>



            </div>



            <!-- INICIAR SESIÓN -->



            <section

                id="panelLogin"

                role="tabpanel"

                aria-labelledby="tabLogin"

                style="display:<?= $panelActivo === 'login' ? 'block' : 'none' ?>"

            >



                <div class="form-header">

                    <h1>¡Bienvenido!</h1>

                    <p>Ingresa tus datos para continuar</p>

                </div>



                <div class="acceso-rapido">

                    <p>Acceso rápido de demostración</p>



                    <div class="btns-acceso">



                        <button

                            type="button"

                            class="btn-acceso"

                            onclick="llenarCredenciales('elprofe@gmail.com','Admin123')"

                        >

                            <strong>👨‍🏫 Profe</strong>

                            <small>Cuenta de prueba</small>

                        </button>



                        <button

                            type="button"

                            class="btn-acceso"

                            onclick="llenarCredenciales('estudiante@correo.com','Pass456')"

                        >

                            <strong>🧑‍🎓 Estudiante</strong>

                            <small>Cuenta de prueba</small>

                        </button>



                    </div>

                </div>



                <div

                    class="alerta <?= $panelActivo === 'login' && $mensaje !== '' ? htmlspecialchars($tipoMensaje, ENT_QUOTES, 'UTF-8') : '' ?>"

                    id="loginAlert"

                    role="alert"

                ><?= $panelActivo === 'login' ? htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') : '' ?></div>



                <form id="loginForm" method="POST" action="login.php">



                    <input type="hidden" name="accion" value="login">



                    <div class="campo">

                        <label for="email">📧 Correo electrónico</label>



                        <input

                            type="email"

                            id="email"

                            name="correo"

                            placeholder="tucorreo@ejemplo.com"

                            autocomplete="email"

                            required

                        >



                        <span class="error-msg" id="emailError" role="alert"></span>

                    </div>



                    <div class="campo">

                        <label for="password">🔒 Contraseña</label>



                        <div class="input-rel">

                            <input

                                type="password"

                                id="password"

                                name="password"

                                placeholder="Tu contraseña"

                                autocomplete="current-password"

                                required

                            >



                            <button

                                type="button"

                                class="btn-ojo"

                                id="togglePass"

                                aria-label="Mostrar contraseña"

                            >👁️</button>

                        </div>



                        <span class="error-msg" id="passError" role="alert"></span>

                    </div>



                    <button type="submit" class="btn-enviar">

                        Entrar al sitio 🌿

                    </button>



                    <p class="cambiar-panel">

                        ¿No tienes cuenta?

                        <a href="#registro" onclick="mostrarTab('registro');return false;">

                            Regístrate aquí

                        </a>

                    </p>



                </form>



            </section>



            <!-- REGISTRO -->



            <section

                id="panelRegistro"

                role="tabpanel"

                aria-labelledby="tabRegister"

                style="display:<?= $panelActivo === 'registro' ? 'block' : 'none' ?>"

            >



                <div class="form-header">

                    <h1>Crear cuenta</h1>

                    <p>Únete a nuestra comunidad ambiental</p>

                </div>



                <div

                    class="alerta <?= $panelActivo === 'registro' && $mensaje !== '' ? htmlspecialchars($tipoMensaje, ENT_QUOTES, 'UTF-8') : '' ?>"

                    id="registerAlert"

                    role="alert"

                ><?= $panelActivo === 'registro' ? htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') : '' ?></div>



                <form id="registerForm" method="POST" action="login.php">



                    <input type="hidden" name="accion" value="registro">



                    <div class="campo">

                        <label for="regNombre">👤 Nombre completo</label>



                        <input

                            type="text"

                            id="regNombre"

                            name="nombre"

                            placeholder="Tu nombre completo"

                            autocomplete="name"

                            required

                        >



                        <span class="error-msg" id="nombreError" role="alert"></span>

                    </div>



                    <div class="campo">

                        <label for="regEmail">📧 Correo electrónico</label>



                        <input

                            type="email"

                            id="regEmail"

                            name="correo"

                            placeholder="tucorreo@ejemplo.com"

                            autocomplete="email"

                            required

                        >



                        <span class="error-msg" id="regEmailError" role="alert"></span>

                    </div>



                    <div class="campo">

                        <label for="regPassword">🔒 Contraseña</label>



                        <div class="input-rel">

                            <input

                                type="password"

                                id="regPassword"

                                name="password"

                                placeholder="Mínimo 8 caracteres"

                                autocomplete="new-password"

                                required

                            >



                            <button

                                type="button"

                                class="btn-ojo"

                                id="toggleRegPass"

                                aria-label="Mostrar contraseña"

                            >👁️</button>

                        </div>



                        <div id="fortaleza">

                            <div id="barraFuerza"></div>

                        </div>



                        <span id="textoFuerza" aria-live="polite"></span>

                        <span class="error-msg" id="regPassError" role="alert"></span>

                    </div>



                    <div class="campo">

                        <label for="regPasswordConf">🔒 Confirmar contraseña</label>



                        <div class="input-rel">

                            <input

                                type="password"

                                id="regPasswordConf"

                                name="confirmacion"

                                placeholder="Repite tu contraseña"

                                autocomplete="new-password"

                                required

                            >



                            <button

                                type="button"

                                class="btn-ojo"

                                id="toggleRegPassConf"

                                aria-label="Mostrar confirmación"

                            >👁️</button>

                        </div>



                        <span class="error-msg" id="regPassConfError" role="alert"></span>

                    </div>



                    <button type="submit" class="btn-enviar" id="btnRegistro">

                        Crear cuenta 🌿

                    </button>



                    <p class="cambiar-panel">

                        ¿Ya tienes cuenta?

                        <a href="#login" onclick="mostrarTab('login');return false;">

                            Inicia sesión

                        </a>

                    </p>



                </form>



            </section>



        </main>



        <!-- PIE DE PÁGINA INSTITUCIONAL -->



        <footer class="footer">



            <p class="footer-titulo">

                Proyecto educativo desarrollado en colaboración con:

            </p>



            <div class="footer-logos">



                <div class="footer-institucion">

                    <img

                        src="img/pascual-bravo.png"

                        alt="Logo de Pascual Bravo"

                        onerror="this.style.display='none';this.nextElementSibling.style.display='block';"

                    >



                    <span class="aviso-logo" style="display:none;color:#c0392b">

                        No se encontró el logo.

                    </span>



                    <span>Institución Universitaria Pascual Bravo</span>

                </div>



                <div class="footer-institucion">

                    <img

                        src="img/republica-uruguay.png"

                        alt="Logo de República de Uruguay"

                        onerror="this.style.display='none';this.nextElementSibling.style.display='block';"

                    >



                    <span class="aviso-logo" style="display:none;color:#c0392b">

                        No se encontró el logo.

                    </span>



                    <span>Institución Educativa República de Uruguay</span>

                </div>



            </div>



            <p class="footer-creditos">

                Semáforo Ambiental de la Huerta<br>

                Medellín, Antioquia, Colombia<br>

                © 2026 · Proyecto educativo ambiental

            </p>



        </footer>



    </div>



    <script>

        function mostrarTab(tab) {

            const login = document.getElementById('panelLogin');

            const registro = document.getElementById('panelRegistro');

            const tabLogin = document.getElementById('tabLogin');

            const tabRegister = document.getElementById('tabRegister');



            const esLogin = tab === 'login';



            login.style.display = esLogin ? 'block' : 'none';

            registro.style.display = esLogin ? 'none' : 'block';



            tabLogin.classList.toggle('active', esLogin);

            tabRegister.classList.toggle('active', !esLogin);



            tabLogin.setAttribute('aria-selected', String(esLogin));

            tabRegister.setAttribute('aria-selected', String(!esLogin));

        }



        function llenarCredenciales(email, password) {

            document.getElementById('email').value = email;

            document.getElementById('password').value = password;

        }



        mostrarTab('<?= $panelActivo === 'registro' ? 'registro' : 'login' ?>');



        [

            ['togglePass', 'password'],

            ['toggleRegPass', 'regPassword'],

            ['toggleRegPassConf', 'regPasswordConf']

        ].forEach(function(par) {

            const boton = document.getElementById(par[0]);

            const campo = document.getElementById(par[1]);



            if (boton && campo) {

                boton.addEventListener('click', function() {

                    const mostrar = campo.type === 'password';



                    campo.type = mostrar ? 'text' : 'password';



                    boton.setAttribute(

                        'aria-label',

                        mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña'

                    );

                });

            }

        });



        const regPassword = document.getElementById('regPassword');



        if (regPassword) {

            regPassword.addEventListener('input', function() {

                const valor = regPassword.value;

                const barra = document.getElementById('barraFuerza');

                const texto = document.getElementById('textoFuerza');



                let puntos = 0;



                if (valor.length >= 8) puntos++;

                if (/[A-Z]/.test(valor)) puntos++;

                if (/[a-z]/.test(valor)) puntos++;

                if (/[0-9]/.test(valor)) puntos++;

                if (/[^A-Za-z0-9]/.test(valor)) puntos++;



                const colores = [

                    '#e84040',

                    '#e84040',

                    '#f5c842',

                    '#83bd48',

                    '#4a9e3f',

                    '#1a5214'

                ];



                const etiquetas = [

                    'Sin contraseña',

                    'Muy débil',

                    'Débil',

                    'Aceptable',

                    'Fuerte',

                    'Muy fuerte'

                ];



                barra.style.width = (puntos * 20) + '%';

                barra.style.background = colores[puntos];

                texto.textContent = valor ? etiquetas[puntos] : '';

            });

        }



        const formularioRegistro = document.getElementById('registerForm');



        if (formularioRegistro) {

            formularioRegistro.addEventListener('submit', function(evento) {

                const password = document.getElementById('regPassword').value;

                const confirmacion = document.getElementById('regPasswordConf').value;

                const errorPassword = document.getElementById('regPassError');

                const errorConfirmacion = document.getElementById('regPassConfError');



                errorPassword.textContent = '';

                errorConfirmacion.textContent = '';



                if (password.length < 8) {

                    evento.preventDefault();

                    errorPassword.textContent = 'La contraseña debe tener al menos 8 caracteres.';

                    return;

                }



                if (password !== confirmacion) {

                    evento.preventDefault();

                    errorConfirmacion.textContent = 'Las contraseñas no coinciden.';

                }

            });

        }

    </script>



</body>

</html>
