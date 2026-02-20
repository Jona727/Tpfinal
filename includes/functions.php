<?php
// Cargar configuración de entorno si no está cargada
if (!defined('BASE_URL')) {
    $envPath = __DIR__ . '/../config/env.php';
    if (file_exists($envPath)) {
        require_once $envPath;
    }
}

if (!defined('BASE_URL')) {
    define('BASE_URL', '/solufeed');
}


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
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit();
    }
}

/**
 * Verifica si el usuario es ADMIN
 */
function verificarAdmin() {
    iniciarSesion();
    
    // Si no hay sesión, al login
    if (!isset($_SESSION['tipo'])) {
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit();
    }
    
    // Si es de campo, al Hub de Campo
    if ($_SESSION['tipo'] === 'CAMPO') {
        header('Location: ' . BASE_URL . '/admin/campo/index.php');
        exit();
    }
    
    // Si no es ADMIN (y no es CAMPO, por descarte), al login
    if ($_SESSION['tipo'] !== 'ADMIN') {
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit();
    }
}

/**
 * Verifica si el usuario es de CAMPO (Operario)
 */
function verificarCampo() {
    iniciarSesion();
    
    if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'CAMPO') {
        // Si es Admin, mandarlo al dashboard, si es nada, al login
        if (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'ADMIN') {
            header('Location: ' . BASE_URL . '/admin/dashboard.php');
        } else {
            header('Location: ' . BASE_URL . '/admin/login.php');
        }
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
    try {
        $db = getConnection();
        
        // Obtener cantidad inicial
        $stmt = $db->prepare("SELECT cantidad_inicial FROM tropa WHERE id_tropa = ?");
        $stmt->execute([$id_tropa]);
        $cantidad = $stmt->fetchColumn() ?: 0;
        
        // Sumar/restar movimientos
        $stmt = $db->prepare("
            SELECT 
                SUM(CASE 
                    WHEN tipo_movimiento IN ('ENTRADA', 'AJUSTE_POSITIVO') THEN cantidad
                    WHEN tipo_movimiento IN ('SALIDA', 'BAJA', 'AJUSTE_NEGATIVO') THEN -cantidad
                    ELSE 0
                END) as ajuste_total
            FROM movimiento_animal
            WHERE id_tropa = ?
        ");
        $stmt->execute([$id_tropa]);
        $ajuste = $stmt->fetchColumn();
        
        if ($ajuste) {
            $cantidad += $ajuste;
        }
        
        return max(0, $cantidad);
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Obtiene la dieta vigente de un lote en una fecha específica
 * 
 * @param int $id_tropa - ID del lote
 * @param string $fecha - Fecha en formato Y-m-d (opcional, por defecto hoy)
 * @return array|null - Datos de la dieta o null si no hay
 */
function obtenerDietaVigente($id_tropa, $fecha = null) {
    try {
        $db = getConnection();
        
        if ($fecha === null) {
            $fecha = date('Y-m-d');
        }
        
        $stmt = $db->prepare("
            SELECT tda.*, d.nombre as dieta_nombre, d.id_dieta
            FROM tropa_dieta_asignada tda
            INNER JOIN dieta d ON tda.id_dieta = d.id_dieta
            WHERE tda.id_tropa = ?
            AND tda.fecha_desde <= ?
            AND (tda.fecha_hasta IS NULL OR tda.fecha_hasta >= ?)
            ORDER BY tda.fecha_desde DESC
            LIMIT 1
        ");
        
        $stmt->execute([$id_tropa, $fecha, $fecha]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $resultado ?: null;
        
    } catch (Exception $e) {
        return null;
    }
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
