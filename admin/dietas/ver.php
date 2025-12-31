<?php
// admin/dietas/ver.php - Actualizado a PDO con CSS moderno
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Verificar permisos de administrador
verificarAdmin();

$page_title = "Detalle de Dieta";
$db = getConnection();

$id_dieta = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_dieta <= 0) {
    header('Location: listar.php');
    exit;
}

// Obtener información de la dieta
$stmt = $db->prepare("SELECT * FROM dieta WHERE id_dieta = ?");
$stmt->execute([$id_dieta]);
$dieta = $stmt->fetch();

if (!$dieta) {
    header('Location: listar.php');
    exit;
}

// Obtener composición de la dieta
$stmt = $db->prepare("
    SELECT 
        dd.*,
        i.nombre as nombre_insumo,
        i.tipo,
        i.porcentaje_ms
    FROM dieta_detalle dd
    INNER JOIN insumo i ON dd.id_insumo = i.id_insumo
    WHERE dd.id_dieta = ?
    ORDER BY dd.porcentaje_teorico DESC
");
$stmt->execute([$id_dieta]);
$composicion = $stmt->fetchAll();

// Calcular MS total de la dieta
$ms_total = 0;
foreach ($composicion as $item) {
    $ms_total += ($item['porcentaje_teorico'] * $item['porcentaje_ms']) / 100;
}

// Obtener lotes que usan esta dieta
$stmt = $db->prepare("
    SELECT 
        t.nombre as nombre_lote,
        t.cantidad_inicial,
        c.nombre as campo,
        tda.fecha_desde
    FROM tropa_dieta_asignada tda
    INNER JOIN tropa t ON tda.id_tropa = t.id_tropa
    LEFT JOIN campo c ON t.id_campo = c.id_campo
    WHERE tda.id_dieta = ? AND tda.fecha_hasta IS NULL AND t.activo = 1
    ORDER BY t.nombre
");
$stmt->execute([$id_dieta]);
$lotes_usando = $stmt->fetchAll();

require_once '../../includes/header.php';
?>



<div class="ver-dieta-container">
    <!-- Breadcrumb -->
    <div style="margin-bottom: 2rem; display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem; font-weight: 500;">
        <a href="../dashboard.php" style="color: var(--primary); text-decoration: none;">Inicio</a>
        <span style="opacity: 0.5;">/</span>
        <a href="listar.php" style="color: var(--primary); text-decoration: none;">Dietas</a>
        <span style="opacity: 0.5;">/</span>
        <span style="color: var(--text-muted);"><?php echo htmlspecialchars($dieta['nombre']); ?></span>
    </div>

    <!-- Botones de acción -->
    <div style="display: flex; gap: 1rem; margin-bottom: 2rem;">
        <a href="listar.php" class="btn btn-secondary">
            <span>←</span> Volver
        </a>
        <a href="editar.php?id=<?php echo $id_dieta; ?>" class="btn btn-primary">
            <span>✏️</span> Editar Dieta
        </a>
    </div>

    <!-- Header de la dieta -->
    <div class="card" style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%); color: white; border: none; padding: 2.5rem;">
        <h1 style="font-weight: 800; margin-bottom: 0.5rem; letter-spacing: -1px;">📋 <?php echo htmlspecialchars($dieta['nombre']); ?></h1>
        <?php if ($dieta['descripcion']): ?>
            <p style="opacity: 0.9; font-weight: 500; font-size: 1.1rem;"><?php echo htmlspecialchars($dieta['descripcion']); ?></p>
        <?php endif; ?>
    </div>

    <!-- Composición de la dieta -->
    <div class="card">
        <h3 class="card-title"><span>🌾</span> Composición de la Dieta</h3>
        
        <?php if (count($composicion) > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Insumo</th>
                            <th>Tipo</th>
                            <th>% MS Insumo</th>
                            <th>% en Dieta</th>
                            <th>Proporción Visual</th>
                        </tr>
                    </thead>
                <tbody>
                    <?php foreach ($composicion as $item): ?>
                        <tr>
                            <td><strong style="color: var(--primary);"><?php echo htmlspecialchars($item['nombre_insumo']); ?></strong></td>
                            <td>
                                <span class="badge" style="background: var(--bg-main); color: var(--text-main);">
                                    <?php echo htmlspecialchars($item['tipo']); ?>
                                </span>
                            </td>
                            <td><?php echo number_format($item['porcentaje_ms'], 1); ?>%</td>
                            <td><strong style="font-size: 1.1rem;"><?php echo number_format($item['porcentaje_teorico'], 1); ?>%</strong></td>
                            <td>
                                <div class="porcentaje-bar" style="height: 12px; background: var(--bg-main); border-radius: 50px; overflow: hidden; max-width: 150px;">
                                    <div class="porcentaje-fill" style="width: <?php echo $item['porcentaje_teorico']; ?>%; height: 100%; background: var(--primary); border-radius: 50px;"></div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Resumen de MS -->
            <div style="margin-top: 2rem; padding: 1.5rem; background: var(--bg-main); border-radius: var(--radius);">
                <h4 style="color: var(--primary); font-weight: 800; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                    <span>📊</span> Resumen de Materia Seca
                </h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1.5rem;">
                    <div style="background: white; padding: 1.25rem; border-radius: var(--radius); text-align: center; box-shadow: var(--shadow-sm);">
                        <div style="font-size: 2rem; font-weight: 800; color: var(--primary);"><?php echo number_format($ms_total, 2); ?>%</div>
                        <div style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; margin-top: 0.25rem;">% MS Total Dieta</div>
                    </div>
                    <div style="background: white; padding: 1.25rem; border-radius: var(--radius); text-align: center; box-shadow: var(--shadow-sm);">
                        <div style="font-size: 2rem; font-weight: 800; color: var(--secondary);"><?php echo count($composicion); ?></div>
                        <div style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; margin-top: 0.25rem;">Insumos</div>
                    </div>
                    <div style="background: white; padding: 1.25rem; border-radius: var(--radius); text-align: center; box-shadow: var(--shadow-sm);">
                        <div style="font-size: 2rem; font-weight: 800; color: <?php 
                            $total_porcentaje = array_sum(array_column($composicion, 'porcentaje_teorico'));
                            echo (abs($total_porcentaje - 100) < 0.1) ? 'var(--success)' : 'var(--danger)';
                        ?>;">
                            <?php echo number_format($total_porcentaje, 1); ?>%
                        </div>
                        <div style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; margin-top: 0.25rem;">Total %</div>
                    </div>
                </div>
                
                <div style="margin-top: 20px; padding: 15px; background: white; border-radius: 8px;">
                    <p style="margin: 0; color: #666; font-size: 0.95em;">
                        <strong>💡 Nota:</strong> El % MS total indica la cantidad de materia seca que contiene esta dieta. 
                        Para calcular el consumo de MS de los animales, se multiplican los kg totales entregados 
                        por este porcentaje.
                    </p>
                </div>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 40px; color: #666;">
                <div style="font-size: 3em; margin-bottom: 15px;">⚠️</div>
                <p><strong>Esta dieta no tiene insumos asignados</strong></p>
                <p>Editá la dieta para agregar insumos.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Lotes que usan esta dieta -->
    <div class="card">
        <h3 class="card-title"><span>🐮</span> Lotes Usando Esta Dieta</h3>
        
        <?php if (count($lotes_usando) > 0): ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
                <?php foreach ($lotes_usando as $lote): ?>
                    <div style="background: var(--bg-main); padding: 1.25rem; border-radius: var(--radius); border-left: 4px solid var(--primary);">
                        <strong style="color: var(--primary); font-size: 1.1rem; display: block; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($lote['nombre_lote']); ?></strong>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; font-size: 0.85rem; color: var(--text-muted); font-weight: 500;">
                            <span>📍 <?php echo htmlspecialchars($lote['campo'] ?? 'Sin campo'); ?></span>
                            <span>🐄 <?php echo $lote['cantidad_inicial']; ?> animales</span>
                            <span style="grid-column: span 2;">📅 Desde: <?php echo date('d/m/Y', strtotime($lote['fecha_desde'])); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 30px; color: #666;">
                <p>📊 Esta dieta no está siendo usada por ningún lote actualmente.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
