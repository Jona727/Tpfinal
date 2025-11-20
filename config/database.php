<?php
// config/database.php
// Configuración de conexión a la base de datos MySQL con PDO

/**
 * Función principal para obtener conexión PDO
 */
function getConnection() {
    $host = 'localhost';
    $dbname = 'solufeed_el_choli';
    $username = 'root';
    $password = '';
    
    try {
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        $db = new PDO($dsn, $username, $password, $options);
        return $db;
        
    } catch (PDOException $e) {
        die("Error al conectar con la base de datos: " . $e->getMessage());
    }
}

// ===========================================
// FUNCIONES COMPATIBILIDAD MYSQLI (TEMPORAL)
// Para que archivos viejos sigan funcionando
// ===========================================

// Conexión mysqli global
$conn = null;

function getMysqliConnection() {
    global $conn;
    if ($conn === null) {
        $conn = mysqli_connect('localhost', 'root', '', 'solufeed_el_choli');
        if (!$conn) {
            die("Error de conexión mysqli: " . mysqli_connect_error());
        }
        mysqli_set_charset($conn, "utf8mb4");
    }
    return $conn;
}

// Inicializar conexión
$conn = getMysqliConnection();

function ejecutarConsulta($query) {
    $conn = getMysqliConnection();
    $result = mysqli_query($conn, $query);
    if (!$result) {
        die("Error en la consulta: " . mysqli_error($conn));
    }
    return $result;
}

function limpiarDato($dato) {
    $conn = getMysqliConnection();
    return mysqli_real_escape_string($conn, trim($dato));
}

// ===========================================
// FUNCIONES AUXILIARES PDO
// ===========================================

function executeQuery($sql, $params = []) {
    $db = getConnection();
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function getOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetch();
}

function getAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchAll();
}
?>