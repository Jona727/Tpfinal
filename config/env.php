<?php
// config/env.php
// Variables de entorno y credenciales

// Determinar entorno
$is_local = ($_SERVER['REMOTE_ADDR'] == '127.0.0.1' || $_SERVER['REMOTE_ADDR'] == '::1');

if ($is_local) {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'solufeed_el_choli');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('BASE_URL', '/solufeed');
    define('APP_ENV', 'development');
    define('APP_DEBUG', true);
} else {
    // CREDENCIALES DE HOSTINGER
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'u903553666_solufeed');
    define('DB_USER', 'u903553666_solufeed');
    define('DB_PASS', 'Solufeed2026');
    define('BASE_URL', '');
    define('APP_ENV', 'production');
    define('APP_DEBUG', false);
}
?>