
<?php
require_once __DIR__ . '/config/conexion.php';

$usuarios = [
    'elprofe@gmail.com' => 'ProfeDemo2026!',
    'estudiante@correo.com' => 'EstudianteDemo2026!',
    'admin@semaforo.com' => 'AdminDemo2026!'
];

$stmt = $conexion->prepare(
    "UPDATE tbl_usuarios SET `password` = ? WHERE correo = ?"
);

if (!$stmt) {
    die("Error al preparar la consulta: " . $conexion->error);
}

foreach ($usuarios as $correo => $clave) {
    $hash = password_hash($clave, PASSWORD_DEFAULT);

    $stmt->bind_param("ss", $hash, $correo);

    if ($stmt->execute()) {
        if ($stmt->affected_rows === 1) {
            echo "Contraseña actualizada para: "
                . htmlspecialchars($correo) . "<br>";
        } else {
            echo "No se actualizó el usuario: "
                . htmlspecialchars($correo)
                . ". Verifica que exista y que la contraseña no sea ya igual.<br>";
        }
    } else {
        echo "Error al actualizar "
            . htmlspecialchars($correo) . "<br>";
    }
}

$stmt->close();
$conexion->close();
?>
