<?php
/*
|--------------------------------------------------------------------------
| CONEXIÓN A LA BASE DE DATOS
| Proyecto: Semáforo Ambiental de la Huerta
|--------------------------------------------------------------------------
*/

$servidor = "localhost";
$usuario  = "root";
$clave    = "";
$base_datos = "semaforo_ambiental";

/*
|--------------------------------------------------------------------------
| Crear conexión
|--------------------------------------------------------------------------
*/

$conexion = new mysqli(
    $servidor,
    $usuario,
    $clave,
    $base_datos
);


/*
|--------------------------------------------------------------------------
| Comprobar conexión
|--------------------------------------------------------------------------
*/

if ($conexion->connect_error) {

    die(
        "Error de conexión con la base de datos: "
        . $conexion->connect_error
    );

}


/*
|--------------------------------------------------------------------------
| Configurar UTF-8
|--------------------------------------------------------------------------
*/

if (!$conexion->set_charset("utf8mb4")) {

    die(
        "Error al configurar UTF-8: "
        . $conexion->error
    );

}

?>
 
