<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$db = getConnection();

echo "Verificando usuarios...\n";

// Verificar si existe algún usuario
$stmt = $db->query("SELECT * FROM usuario LIMIT 1");
$user = $stmt->fetch();

$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);
$email = 'admin@solufeed.com';

if ($user) {
    // Actualizar usuario existente
    echo "Usuario encontrado: " . $user['email'] . "\n";
    $stmt = $db->prepare("UPDATE usuario SET password_hash = ?, email = ? WHERE id_usuario = ?");
    $stmt->execute([$hash, $email, $user['id_usuario']]);
    echo "Contraseña actualizada a: '$password' para el usuario: '$email'\n";
} else {
    // Crear nuevo admin
    echo "No hay usuarios. Creando admin por defecto...\n";
    $stmt = $db->prepare("INSERT INTO usuario (nombre, email, password_hash, tipo, activo, fecha_creacion) VALUES (?, ?, ?, 'ADMIN', 1, NOW())");
    $stmt->execute(['Administrador', $email, $hash]);
    echo "Usuario creado:\nEmail: $email\nPassword: $password\n";
}
?>
