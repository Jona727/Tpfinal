<?php
// Test local default credentials
$host = 'localhost';
$user = 'root';
$pass = '';

$conn = @mysqli_connect($host, $user, $pass);
if ($conn) {
    echo "Conexión con root/vacio EXITOSA.\n";
    $res = mysqli_query($conn, "SHOW DATABASES LIKE 'u806346265_gr5'");
    if (mysqli_num_rows($res) > 0) {
        echo "Base de datos u806346265_gr5 ENCONTRADA.\n";
    } else {
        echo "Base de datos u806346265_gr5 NO ENCONTRADA. Bases disponibles:\n";
        $res = mysqli_query($conn, "SHOW DATABASES");
        while ($row = mysqli_fetch_row($res)) {
            echo "- " . $row[0] . "\n";
        }
    }
} else {
    echo "Conexión con root/vacio FALLIDA: " . mysqli_connect_error() . "\n";
}
?>
