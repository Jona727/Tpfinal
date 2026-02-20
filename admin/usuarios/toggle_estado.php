<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

verificarAdmin();

$id_usuario = $_GET['id'] ?? 0;

if (!$id_usuario) {
    header('Location: listar.php');
    exit();
}

$db = getConnection();

// Obtener usuario
$stmt = $db->prepare("SELECT id_usuario, nombre, activo FROM usuario WHERE id_usuario = ?");
$stmt->execute([$id_usuario]);
$usuario = $stmt->fetch();

if (!$usuario) {
    header('Location: listar.php');
    exit();
}

// Prevenir que el admin se desactive a sí mismo
if ($usuario['id_usuario'] == $_SESSION['usuario_id']) {
    $_SESSION['mensaje_error'] = 'No puedes cambiar tu propio estado.';
    header('Location: listar.php');
    exit();
}

// Cambiar estado
$nuevo_estado = $usuario['activo'] ? 0 : 1;

try {
    $stmt = $db->prepare("UPDATE usuario SET activo = ? WHERE id_usuario = ?");
    $stmt->execute([$nuevo_estado, $id_usuario]);
    
    $accion = $nuevo_estado ? 'activado' : 'desactivado';
    $_SESSION['mensaje_exito'] = "Usuario '{$usuario['nombre']}' {$accion} exitosamente.";
    
} catch (Exception $e) {
    $_SESSION['mensaje_error'] = 'Error al cambiar el estado del usuario.';
}

header('Location: listar.php');
exit();
?>
