<?php
// admin/campo/historial_dia.php
// Ver historial de operaciones del día
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$page_title = "Historial del Día";
$db = getConnection();

$hoy = date('Y-m-d');

// Obtener alimentaciones de hoy
$stmt = $db->prepare("
    SELECT 
        cl.*,
        t.nombre as nombre_lote,
        cl.animales_presentes,
        c.nombre as nombre_campo,
        u.nombre as operario
    FROM consumo_lote cl
    INNER JOIN tropa t ON cl.id_tropa = t.id_tropa
    LEFT JOIN campo c ON t.id_campo = c.id_campo
    LEFT JOIN usuario u ON cl.id_usuario = u.id_usuario
    WHERE DATE(cl.fecha) = ?
    ORDER BY cl.fecha DESC, cl.hora DESC
");
$stmt->execute([$hoy]);
$alimentaciones = $stmt->fetchAll();

// Obtener pesadas de hoy
$stmt = $db->prepare("
    SELECT 
        p.*,
        t.nombre as nombre_lote,
        c.nombre as nombre_campo,
        u.nombre as operario
    FROM pesada p
    INNER JOIN tropa t ON p.id_tropa = t.id_tropa
    LEFT JOIN campo c ON t.id_campo = c.id_campo
    LEFT JOIN usuario u ON p.id_usuario = u.id_usuario
    WHERE DATE(p.fecha) = ?
    ORDER BY p.fecha DESC
");
$stmt->execute([$hoy]);
$pesadas = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<style>
.historial-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.header-historial {
    background: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.seccion-historial {
    background: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.seccion-titulo {
    font-size: 1.5em;
    color: #2c5530;
    margin: 0 0 20px 0;
    padding-bottom: 10px;
    border-bottom: 2px solid #2c5530;
}

.registro-item {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 15px;
    border-left: 4px solid #2c5530;
}

.registro-item:last-child {
    margin-bottom: 0;
}

.registro-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.registro-lote {
    font-size: 1.2em;
    font-weight: bold;
    color: #2c5530;
}

.registro-hora {
    color: #666;
    font-size: 0.9em;
}

.registro-detalles {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
    margin-top: 10px;
}

.detalle-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.detalle-item .icono {
    color: #2c5530;
}

.mensaje-vacio {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

.mensaje-vacio .icono {
    font-size: 4em;
    margin-bottom: 15px;
    opacity: 0.5;
}

.btn-volver {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: #2c5530;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    transition: all 0.3s;
}

.btn-volver:hover {
    background: #3d7043;
    transform: translateX(-3px);
}

.resumen-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.stat-box {
    background: linear-gradient(135deg, #2c5530 0%, #3d7043 100%);
    color: white;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
}

.stat-box .numero {
    font-size: 2.5em;
    font-weight: bold;
    margin: 0;
}

.stat-box .etiqueta {
    font-size: 0.9em;
    opacity: 0.9;
    margin: 5px 0 0 0;
}
</style>

<div class="historial-container">
    <!-- Header -->
    <div class="header-historial">
        <div>
            <h1 style="margin: 0 0 5px 0;">📋 Historial del Día</h1>
            <p style="margin: 0; color: #666;">
                <?php echo strftime('%A, %d de %B de %Y'); ?>
            </p>
        </div>
        <a href="index.php" class="btn-volver">
            ← Volver al Hub
        </a>
    </div>

    <!-- Resumen estadístico -->
    <div class="resumen-stats">
        <div class="stat-box">
            <p class="numero"><?php echo count($alimentaciones); ?></p>
            <p class="etiqueta">Alimentaciones Registradas</p>
        </div>
        <div class="stat-box">
            <p class="numero"><?php echo count($pesadas); ?></p>
            <p class="etiqueta">Pesadas Registradas</p>
        </div>
        <div class="stat-box">
            <p class="numero">
                <?php 
                $kg_totales = array_sum(array_column($alimentaciones, 'kg_totales_tirados'));
                echo number_format($kg_totales, 0);
                ?>
            </p>
            <p class="etiqueta">Kg de Alimento Entregados</p>
        </div>
    </div>

    <!-- Alimentaciones -->
    <div class="seccion-historial">
        <h2 class="seccion-titulo">🍽️ Alimentaciones Registradas</h2>
        
        <?php if (count($alimentaciones) > 0): ?>
            <?php foreach ($alimentaciones as $alim): ?>
                <div class="registro-item">
                    <div class="registro-header">
                        <div class="registro-lote">
                            🐮 <?php echo htmlspecialchars($alim['nombre_lote']); ?>
                        </div>
                        <div class="registro-hora">
                            <?php echo date('H:i', strtotime($alim['hora'])); ?> hs
                        </div>
                    </div>
                    
                    <div class="registro-detalles">
                        <div class="detalle-item">
                            <span class="icono">📍</span>
                            <span><?php echo htmlspecialchars($alim['nombre_campo']); ?></span>
                        </div>
                        <div class="detalle-item">
                            <span class="icono">🐄</span>
                            <span><?php echo $alim['animales_presentes']; ?> animales</span>
                        </div>
                        <div class="detalle-item">
                            <span class="icono">⚖️</span>
                            <span><?php echo number_format($alim['kg_totales_tirados'], 0); ?> kg totales</span>
                        </div>
                        <div class="detalle-item">
                            <span class="icono">🍖</span>
                            <span><?php echo number_format($alim['kg_totales_tirados'] / $alim['animales_presentes'], 2); ?> kg/animal</span>
                        </div>
                        <div class="detalle-item">
                            <span class="icono">👤</span>
                            <span><?php echo htmlspecialchars($alim['operario']); ?></span>
                        </div>
                        <div class="detalle-item">
                            <span class="icono">📊</span>
                            <span>Nivel sobras: <?php echo htmlspecialchars($alim['sobrante_nivel']); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="mensaje-vacio">
                <div class="icono">🍽️</div>
                <p><strong>No hay alimentaciones registradas hoy</strong></p>
                <p>Aún no se han registrado alimentaciones en el día de hoy.</p>
                <a href="../alimentaciones/registrar.php" style="color: #2c5530; font-weight: bold;">
                    Registrar primera alimentación →
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pesadas -->
    <div class="seccion-historial">
        <h2 class="seccion-titulo">⚖️ Pesadas Registradas</h2>
        
        <?php if (count($pesadas) > 0): ?>
            <?php foreach ($pesadas as $pesada): ?>
                <?php
                // Calcular ADPV si existe
                $stmt = $db->prepare("
                    SELECT peso_promedio, fecha 
                    FROM pesada 
                    WHERE id_tropa = ? 
                    AND fecha < ? 
                    ORDER BY fecha DESC 
                    LIMIT 1
                ");
                $stmt->execute([$pesada['id_tropa'], $pesada['fecha']]);
                $pesada_anterior = $stmt->fetch();
                
                $adpv = null;
                if ($pesada_anterior) {
                    $dias = (strtotime($pesada['fecha']) - strtotime($pesada_anterior['fecha'])) / 86400;
                    $adpv = ($pesada['peso_promedio'] - $pesada_anterior['peso_promedio']) / $dias;
                }
                ?>
                
                <div class="registro-item" style="border-left-color: #1e6091;">
                    <div class="registro-header">
                        <div class="registro-lote">
                            🐮 <?php echo htmlspecialchars($pesada['nombre_lote']); ?>
                        </div>
                        <div class="registro-hora">
                            <?php echo date('H:i', strtotime($pesada['fecha'])); ?> hs
                        </div>
                    </div>
                    
                    <div class="registro-detalles">
                        <div class="detalle-item">
                            <span class="icono">📍</span>
                            <span><?php echo htmlspecialchars($pesada['nombre_campo']); ?></span>
                        </div>
                        <div class="detalle-item">
                            <span class="icono">⚖️</span>
                            <span><strong><?php echo number_format($pesada['peso_promedio'], 0); ?> kg</strong> promedio</span>
                        </div>
                        <?php if ($adpv !== null): ?>
                            <div class="detalle-item">
                                <span class="icono">📈</span>
                                <span>ADPV: <strong><?php echo number_format($adpv, 3); ?> kg/día</strong></span>
                            </div>
                        <?php endif; ?>
                        <div class="detalle-item">
                            <span class="icono">🐄</span>
                            <span><?php echo $pesada['animales_esperados']; ?> esperados / <?php echo $pesada['animales_vistos']; ?> vistos</span>
                        </div>
                        <div class="detalle-item">
                            <span class="icono">👤</span>
                            <span><?php echo htmlspecialchars($pesada['operario']); ?></span>
                        </div>
                        <?php if ($pesada['diferencia_detectada']): ?>
                            <div class="detalle-item" style="color: #dc3545;">
                                <span class="icono">⚠️</span>
                                <span>Diferencia detectada</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="mensaje-vacio">
                <div class="icono">⚖️</div>
                <p><strong>No hay pesadas registradas hoy</strong></p>
                <p>Aún no se han registrado pesadas en el día de hoy.</p>
                <a href="../pesadas/registrar.php" style="color: #1e6091; font-weight: bold;">
                    Registrar primera pesada →
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Botón para volver -->
    <div style="text-align: center; margin-top: 30px;">
        <a href="index.php" class="btn-volver" style="font-size: 1.1em;">
            ← Volver al Hub de Campo
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>