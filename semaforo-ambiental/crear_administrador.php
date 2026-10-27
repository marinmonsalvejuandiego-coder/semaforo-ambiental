
<?php
require_once __DIR__ . '/config/conexion.php';

$nombre = 'Administrador del Sistema';
$correo = 'admin@semaforo.com';
$clave = 'AdminSemaforo2026*';
$password = password_hash($clave, PASSWORD_DEFAULT);
$rol = 'administrador';

$sql = "SELECT id_usuario FROM tbl_usuarios WHERE correo = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $correo);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    echo "El correo del administrador ya está registrado. Usa otro correo.";
} else {
    $sql = "INSERT INTO tbl_usuarios
            (nombre_completo, correo, password, rol)
            VALUES (?, ?, ?, ?)";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ssss", $nombre, $correo, $password, $rol);

    if ($stmt->execute()) {
        echo "Administrador creado correctamente.";
    } else {
        echo "No fue posible crear el administrador.";
    }
}
?>
