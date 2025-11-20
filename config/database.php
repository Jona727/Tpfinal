<?php
/**
 * SOLUFEED - Configuración de Base de Datos
 * 
 * Este archivo contiene la configuración para conectarse a MySQL.
 * Cambiá los valores si tu configuración es diferente.
 */

// Configuración de la base de datos
define('DB_HOST', 'localhost');        // Servidor (normalmente localhost)
define('DB_USER', 'root');             // Usuario de MySQL (por defecto "root" en XAMPP)
define('DB_PASS', '');                 // Contraseña (vacía por defecto en XAMPP)
define('DB_NAME', 'solufeed_el_choli'); // Nombre de tu base de datos

// Crear conexión
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Verificar conexión
if (!$conn) {
    die("❌ Error de conexión: " . mysqli_connect_error());
}

// Configurar charset UTF-8 para caracteres especiales (tildes, ñ, etc.)
mysqli_set_charset($conn, "utf8mb4");

/**
 * Función auxiliar para ejecutar consultas de forma segura
 * 
 * @param string $query - La consulta SQL a ejecutar
 * @return mysqli_result|bool - Resultado de la consulta
 */
function ejecutarConsulta($query) {
    global $conn;
    $resultado = mysqli_query($conn, $query);
    
    if (!$resultado) {
        // En desarrollo mostramos el error, en producción lo ocultaríamos
        die("❌ Error en la consulta: " . mysqli_error($conn));
    }
    
    return $resultado;
}

/**
 * Función para escapar datos y prevenir SQL Injection
 * 
 * @param string $data - Dato a escapar
 * @return string - Dato escapado y seguro
 */
function limpiarDato($data) {
    global $conn;
    return mysqli_real_escape_string($conn, trim($data));
}

// Configuración de zona horaria (Argentina)
date_default_timezone_set('America/Argentina/Buenos_Aires');
?>