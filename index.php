<?php
/**
 * SOLUFEED - Página de Inicio
 * Redirige al dashboard (por ahora sin login)
 */

// Por ahora redirigimos directo al dashboard
// Cuando implementes login, acá irá la lógica de verificación
header('Location: /solufeed/admin/dashboard.php');
exit();
?>