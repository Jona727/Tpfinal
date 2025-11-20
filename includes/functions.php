<?php
/**
 * SOLUFEED - Funciones Auxiliares
 * 
 * Funciones reutilizables para toda la aplicación
 */

/**
 * Inicia una sesión si no está iniciada
 */
function iniciarSesion() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Verifica si el usuario está logueado
 * Si no lo está, redirige al login
 */
function verificarSesion() {
    iniciarSesion();
    
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: /solufeed/admin/login.php');
        exit();
    }
}

/**
 * Verifica si el usuario es ADMIN
 */
function verificarAdmin() {
    iniciarSesion();
    
    if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'ADMIN') {
        header('Location: /solufeed/admin/login.php');
        exit();
    }
}

/**
 * Formatea una fecha en formato argentino
 * 
 * @param string $fecha - Fecha en formato Y-m-d
 * @return string - Fecha en formato d/m/Y
 */
function formatearFecha($fecha) {
    if (empty($fecha)) return '-';
    
    $timestamp = strtotime($fecha);
    return date('d/m/Y', $timestamp);
}

/**
 * Formatea un número decimal con separador de miles
 * 
 * @param float $numero - Número a formatear
 * @param int $decimales - Cantidad de decimales
 * @return string - Número formateado
 */
function formatearNumero($numero, $decimales = 2) {
    return number_format($numero, $decimales, ',', '.');
}

/**
 * Calcula el porcentaje de Materia Seca (MS) de un consumo
 * 
 * @param float $kg_insumo - Kg del insumo
 * @param float $porcentaje_ms_insumo - % MS del insumo
 * @return float - Kg de MS
 */
function calcularKgMS($kg_insumo, $porcentaje_ms_insumo) {
    return ($kg_insumo * $porcentaje_ms_insumo) / 100;
}

/**
 * Obtiene los animales presentes actuales de un lote
 * Basado en: cantidad_inicial + movimientos
 * 
 * @param int $id_tropa - ID del lote
 * @return int - Cantidad de animales presentes
 */
function obtenerAnimalesPresentes($id_tropa) {
    global $conn;
    
    // Obtener cantidad inicial
    $query = "SELECT cantidad_inicial FROM tropa WHERE id_tropa = " . (int)$id_tropa;
    $resultado = mysqli_query($conn, $query);
    $tropa = mysqli_fetch_assoc($resultado);
    
    $cantidad = $tropa['cantidad_inicial'];
    
    // Sumar/restar movimientos
    $query_mov = "
        SELECT 
            SUM(CASE 
                WHEN tipo_movimiento IN ('ENTRADA', 'AJUSTE_POSITIVO') THEN cantidad
                WHEN tipo_movimiento IN ('SALIDA', 'BAJA', 'AJUSTE_NEGATIVO') THEN -cantidad
                ELSE 0
            END) as ajuste_total
        FROM movimiento_animal
        WHERE id_tropa = " . (int)$id_tropa;
    
    $resultado_mov = mysqli_query($conn, $query_mov);
    $movimiento = mysqli_fetch_assoc($resultado_mov);
    
    if ($movimiento['ajuste_total']) {
        $cantidad += $movimiento['ajuste_total'];
    }
    
    return max(0, $cantidad); // No puede ser negativo
}

/**
 * Obtiene la dieta vigente de un lote en una fecha específica
 * 
 * @param int $id_tropa - ID del lote
 * @param string $fecha - Fecha en formato Y-m-d (opcional, por defecto hoy)
 * @return array|null - Datos de la dieta o null si no hay
 */
function obtenerDietaVigente($id_tropa, $fecha = null) {
    global $conn;
    
    if ($fecha === null) {
        $fecha = date('Y-m-d');
    }
    
    $query = "
        SELECT tda.*, d.nombre as dieta_nombre
        FROM tropa_dieta_asignada tda
        INNER JOIN dieta d ON tda.id_dieta = d.id_dieta
        WHERE tda.id_tropa = " . (int)$id_tropa . "
        AND tda.fecha_desde <= '" . limpiarDato($fecha) . "'
        AND (tda.fecha_hasta IS NULL OR tda.fecha_hasta >= '" . limpiarDato($fecha) . "')
        ORDER BY tda.fecha_desde DESC
        LIMIT 1
    ";
    
    $resultado = mysqli_query($conn, $query);
    
    if ($resultado && mysqli_num_rows($resultado) > 0) {
        return mysqli_fetch_assoc($resultado);
    }
    
    return null;
}

/**
 * Muestra un mensaje de éxito o error
 * 
 * @param string $mensaje - Texto del mensaje
 * @param string $tipo - Tipo: 'success' o 'error'
 */
function mostrarMensaje($mensaje, $tipo = 'success') {
    $clase = $tipo === 'success' ? 'mensaje-exito' : 'mensaje-error';
    $icono = $tipo === 'success' ? '✓' : '✕';
    
    echo "<div class='mensaje {$clase}'>{$icono} {$mensaje}</div>";
}

/**
 * Redirige a una página después de un tiempo
 * 
 * @param string $url - URL de destino
 * @param int $segundos - Segundos de espera
 */
function redirigir($url, $segundos = 2) {
    header("refresh:{$segundos};url={$url}");
}
?>