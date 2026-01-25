<?php
// Script de introspección para verificar estructura de tabla 'campo'
require_once __DIR__ . '/config/database.php';

try {
    $db = getConnection();
    $stmt = $db->query("SHOW COLUMNS FROM campo");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Estructura de tabla 'campo':\n";
    foreach ($columns as $col) {
        echo "- {$col['Field']} ({$col['Type']})\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
