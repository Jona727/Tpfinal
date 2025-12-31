<?php
require_once '../includes/functions.php';
iniciarSesion();
session_destroy();
header('Location: ' . BASE_URL . '/admin/login.php');
exit();
?>
