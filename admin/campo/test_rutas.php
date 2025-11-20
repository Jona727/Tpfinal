<?php
// admin/campo/test_rutas.php
// Archivo de prueba para verificar rutas

echo "<h2>TEST DE RUTAS desde admin/campo/</h2>";

echo "<h3>1. Verificando __DIR__:</h3>";
echo "<p><strong>__DIR__ = </strong>" . __DIR__ . "</p>";

echo "<h3>2. Rutas que debería encontrar:</h3>";

$ruta_config = __DIR__ . '/../../config/database.php';
$ruta_functions = __DIR__ . '/../../includes/functions.php';
$ruta_header = __DIR__ . '/../../includes/header.php';

echo "<p><strong>Config:</strong> " . $ruta_config . "</p>";
echo "<p>¿Existe? " . (file_exists($ruta_config) ? '✅ SÍ' : '❌ NO') . "</p>";

echo "<p><strong>Functions:</strong> " . $ruta_functions . "</p>";
echo "<p>¿Existe? " . (file_exists($ruta_functions) ? '✅ SÍ' : '❌ NO') . "</p>";

echo "<p><strong>Header:</strong> " . $ruta_header . "</p>";
echo "<p>¿Existe? " . (file_exists($ruta_header) ? '✅ SÍ' : '❌ NO') . "</p>";

echo "<h3>3. Intentando cargar database.php:</h3>";

if (file_exists($ruta_config)) {
    require_once $ruta_config;
    echo "<p>✅ database.php cargado correctamente</p>";
    
    // Verificar si existe la función getConnection
    if (function_exists('getConnection')) {
        echo "<p>✅ Función getConnection() existe</p>";
        
        try {
            $db = getConnection();
            echo "<p>✅ Conexión a base de datos exitosa</p>";
        } catch (Exception $e) {
            echo "<p>❌ Error al conectar: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p>❌ Función getConnection() NO existe</p>";
        echo "<p>Funciones disponibles en database.php:</p>";
        echo "<pre>";
        $file_content = file_get_contents($ruta_config);
        preg_match_all('/function\s+(\w+)/', $file_content, $matches);
        print_r($matches[1]);
        echo "</pre>";
    }
} else {
    echo "<p>❌ No se pudo cargar database.php</p>";
}

echo "<h3>4. Estructura de directorios:</h3>";
echo "<p>Desde: " . __DIR__ . "</p>";
echo "<p>Dos niveles arriba: " . realpath(__DIR__ . '/../..') . "</p>";

echo "<h3>5. Archivos en config/:</h3>";
$config_dir = __DIR__ . '/../../config';
if (is_dir($config_dir)) {
    $files = scandir($config_dir);
    echo "<ul>";
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "<li>" . $file . "</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p>❌ Carpeta config no encontrada</p>";
}
?>