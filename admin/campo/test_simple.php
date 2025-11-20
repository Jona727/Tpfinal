<?php
// admin/campo/test_simple.php
// Prueba simple de carga de archivos

echo "<h2>TEST SIMPLE DE CARGA</h2>";

echo "<h3>1. Verificando __DIR__:</h3>";
echo "<p>__DIR__ = " . __DIR__ . "</p>";

echo "<h3>2. Ruta a database.php:</h3>";
$ruta_db = __DIR__ . '/../../config/database.php';
echo "<p>Ruta completa: " . $ruta_db . "</p>";
echo "<p>Ruta real: " . realpath($ruta_db) . "</p>";
echo "<p>¿Existe? " . (file_exists($ruta_db) ? 'SÍ' : 'NO') . "</p>";

if (file_exists($ruta_db)) {
    echo "<h3>3. Intentando cargar database.php:</h3>";
    
    try {
        require_once $ruta_db;
        echo "<p>✅ database.php cargado OK</p>";
        
        if (function_exists('getConnection')) {
            echo "<p>✅ Función getConnection() existe</p>";
            
            try {
                $db = getConnection();
                echo "<p>✅ Conexión exitosa: " . get_class($db) . "</p>";
            } catch (Exception $e) {
                echo "<p>❌ Error al conectar: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p>❌ Función getConnection() NO existe</p>";
            
            // Mostrar todas las funciones definidas
            echo "<h4>Funciones definidas en database.php:</h4>";
            $file_content = file_get_contents($ruta_db);
            preg_match_all('/function\s+(\w+)/', $file_content, $matches);
            echo "<pre>";
            print_r($matches[1]);
            echo "</pre>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Error al cargar: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>❌ El archivo NO existe en esa ruta</p>";
}

echo "<h3>4. Contenido de database.php (primeras líneas):</h3>";
if (file_exists($ruta_db)) {
    $content = file_get_contents($ruta_db);
    $lines = explode("\n", $content);
    echo "<pre>";
    for ($i = 0; $i < min(30, count($lines)); $i++) {
        echo htmlspecialchars($lines[$i]) . "\n";
    }
    echo "</pre>";
}
?>