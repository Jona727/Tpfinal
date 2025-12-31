<?php
require_once 'config/database.php';
$db = getConnection();
$stmt = $db->query("DESCRIBE usuario");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Estructura de tabla usuario:\n";
foreach ($columns as $col) {
    echo $col['Field'] . " - " . $col['Type'] . "\n";
}

echo "\n\nUsuarios existentes (primeros 3):\n";
$stmt = $db->query("SELECT * FROM usuario LIMIT 3");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($users);
?>
