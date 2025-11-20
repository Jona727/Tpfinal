<?php
// admin/campo/index.php
// Hub principal para Usuario de Campo
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$page_title = "Usuario de Campo";
$db = getConnection();

// Obtener información del día actual
$hoy = date('Y-m-d');

// Contar alimentaciones registradas hoy por este usuario
$stmt = $db->prepare("
    SELECT COUNT(*) as total 
    FROM consumo_lote 
    WHERE DATE(fecha) = ? 
");
$stmt->execute([$hoy]);
$alimentaciones_hoy = $stmt->fetch()['total'];

// Contar pesadas registradas hoy
$stmt = $db->prepare("
    SELECT COUNT(*) as total 
    FROM pesada 
    WHERE DATE(fecha) = ?
");
$stmt->execute([$hoy]);
$pesadas_hoy = $stmt->fetch()['total'];

// Obtener lotes activos
$stmt = $db->query("
    SELECT COUNT(*) as total 
    FROM tropa 
    WHERE activo = 1
");
$lotes_activos = $stmt->fetch()['total'];

// Obtener lotes pendientes de alimentar hoy
$stmt = $db->prepare("
    SELECT COUNT(DISTINCT t.id_tropa) as total
    FROM tropa t
    LEFT JOIN consumo_lote cl ON t.id_tropa = cl.id_tropa 
        AND DATE(cl.fecha) = ?
    WHERE t.activo = 1 
    AND cl.id_consumo IS NULL
");
$stmt->execute([$hoy]);
$lotes_pendientes = $stmt->fetch()['total'];

require_once __DIR__ . '/../../includes/header.php';
?>

<style>
/* Estilos específicos para el Hub de Campo */
.campo-hub {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.campo-header {
    background: linear-gradient(135deg, #2c5530 0%, #3d7043 100%);
    color: white;
    padding: 30px;
    border-radius: 15px;
    margin-bottom: 30px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.campo-header h1 {
    margin: 0 0 10px 0;
    font-size: 2.5em;
}

.campo-header p {
    margin: 0;
    font-size: 1.2em;
    opacity: 0.9;
}

.stats-rapidas {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    border-left: 4px solid #2c5530;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stat-card .numero {
    font-size: 2.5em;
    font-weight: bold;
    color: #2c5530;
    margin: 0;
}

.stat-card .etiqueta {
    font-size: 0.9em;
    color: #666;
    margin: 5px 0 0 0;
}

.acciones-principales {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.accion-card {
    background: white;
    border-radius: 15px;
    padding: 30px;
    text-align: center;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    cursor: pointer;
    text-decoration: none;
    color: inherit;
    display: block;
    border: 2px solid transparent;
}

.accion-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.15);
}

.accion-card.primaria {
    background: linear-gradient(135deg, #2c5530 0%, #3d7043 100%);
    color: white;
    border-color: #2c5530;
}

.accion-card.primaria:hover {
    background: linear-gradient(135deg, #3d7043 0%, #4a8550 100%);
}

.accion-card.secundaria {
    background: linear-gradient(135deg, #1e6091 0%, #2574a9 100%);
    color: white;
    border-color: #1e6091;
}

.accion-card.secundaria:hover {
    background: linear-gradient(135deg, #2574a9 0%, #2e86c1 100%);
}

.accion-card.info {
    background: white;
    border: 2px solid #e0e0e0;
}

.accion-card.info:hover {
    border-color: #2c5530;
}

.accion-icono {
    font-size: 4em;
    margin-bottom: 15px;
}

.accion-titulo {
    font-size: 1.5em;
    font-weight: bold;
    margin: 0 0 10px 0;
}

.accion-descripcion {
    font-size: 0.95em;
    opacity: 0.9;
    margin: 0;
}

.alerta-pendientes {
    background: #fff3cd;
    border-left: 4px solid #ffc107;
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.alerta-pendientes .icono {
    font-size: 2em;
}

.alerta-pendientes .texto {
    flex: 1;
}

.alerta-pendientes strong {
    display: block;
    margin-bottom: 5px;
    color: #856404;
}

@media (max-width: 768px) {
    .campo-header h1 {
        font-size: 1.8em;
    }
    
    .acciones-principales {
        grid-template-columns: 1fr;
    }
    
    .stats-rapidas {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<div class="campo-hub">
    <!-- Header principal -->
    <div class="campo-header">
        <h1>👷 Usuario de Campo</h1>
        <p>Bienvenido al área operativa del sistema</p>
        <p style="font-size: 0.9em; margin-top: 5px;">
            📅 <?php echo strftime('%A, %d de %B de %Y'); ?>
        </p>
    </div>

    <!-- Estadísticas rápidas del día -->
    <div class="stats-rapidas">
        <div class="stat-card">
            <p class="numero"><?php echo $alimentaciones_hoy; ?></p>
            <p class="etiqueta">Alimentaciones registradas hoy</p>
        </div>
        <div class="stat-card">
            <p class="numero"><?php echo $pesadas_hoy; ?></p>
            <p class="etiqueta">Pesadas registradas hoy</p>
        </div>
        <div class="stat-card">
            <p class="numero"><?php echo $lotes_activos; ?></p>
            <p class="etiqueta">Lotes activos</p>
        </div>
        <div class="stat-card">
            <p class="numero"><?php echo $lotes_pendientes; ?></p>
            <p class="etiqueta">Lotes sin alimentar hoy</p>
        </div>
    </div>

    <!-- Alerta de lotes pendientes -->
    <?php if ($lotes_pendientes > 0): ?>
        <div class="alerta-pendientes">
            <div class="icono">⚠️</div>
            <div class="texto">
                <strong>Lotes pendientes de alimentar</strong>
                Hay <?php echo $lotes_pendientes; ?> lote(s) que aún no han sido alimentados hoy.
            </div>
        </div>
    <?php endif; ?>

    <!-- Acciones principales -->
    <div class="acciones-principales">
        <!-- Registrar Alimentación -->
        <a href="../alimentaciones/registrar.php" class="accion-card primaria">
            <div class="accion-icono">🍽️</div>
            <h2 class="accion-titulo">Registrar Alimentación</h2>
            <p class="accion-descripcion">
                Registrar el consumo diario de alimento para un lote
            </p>
        </a>

        <!-- Registrar Pesada -->
        <a href="../pesadas/registrar.php" class="accion-card secundaria">
            <div class="accion-icono">⚖️</div>
            <h2 class="accion-titulo">Registrar Pesada</h2>
            <p class="accion-descripcion">
                Registrar el peso promedio de los animales de un lote
            </p>
        </a>

        <!-- Ver Historial del Día -->
        <a href="historial_dia.php" class="accion-card info">
            <div class="accion-icono">📋</div>
            <h2 class="accion-titulo" style="color: #2c5530;">Ver Historial del Día</h2>
            <p class="accion-descripcion" style="color: #666;">
                Ver todas las operaciones registradas hoy
            </p>
        </a>

        <!-- Consultar Lotes Disponibles -->
        <a href="consultar_lotes.php" class="accion-card info">
            <div class="accion-icono">🐮</div>
            <h2 class="accion-titulo" style="color: #2c5530;">Consultar Lotes</h2>
            <p class="accion-descripcion" style="color: #666;">
                Ver información de los lotes disponibles
            </p>
        </a>

        <!-- Ver Dieta de un Lote -->
        <a href="ver_dieta.php" class="accion-card info">
            <div class="accion-icono">📊</div>
            <h2 class="accion-titulo" style="color: #2c5530;">Ver Dieta de Lote</h2>
            <p class="accion-descripcion" style="color: #666;">
                Consultar la dieta vigente de un lote específico
            </p>
        </a>
    </div>

    <!-- Información adicional -->
    <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <h3 style="margin-top: 0; color: #2c5530;">💡 Instrucciones rápidas</h3>
        <ul style="line-height: 1.8;">
            <li><strong>Alimentación:</strong> Registrá el consumo diario seleccionando el lote y cargando los kg de cada insumo.</li>
            <li><strong>Pesadas:</strong> Registrá el peso promedio cuando se realice una pesada grupal del lote.</li>
            <li><strong>Historial:</strong> Podés revisar todas las operaciones que registraste en el día.</li>
            <li><strong>Consultas:</strong> Consultá información de lotes y dietas en cualquier momento.</li>
        </ul>
        
        <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
            <p style="margin: 0; color: #666;">
                <strong>📞 ¿Necesitás ayuda?</strong><br>
                Contactá al administrador del sistema si tenés dudas o problemas.
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>