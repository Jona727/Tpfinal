<?php
// admin/dietas/ver.php - Actualizado a PDO con CSS moderno
require_once '../../config/database.php';
require_once '../../includes/functions.php';

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
    <div class="breadcrumb">
        <a href="../dashboard.php">Dashboard</a> / 
        <a href="listar.php">Dietas</a> / 
        <strong><?php echo htmlspecialchars($dieta['nombre']); ?></strong>
    </div>

    <!-- Botones de acción -->
    <div class="btn-group">
        <a href="listar.php" class="btn btn-volver">
            ← Volver a Dietas
        </a>
        <a href="editar.php?id=<?php echo $id_dieta; ?>" class="btn btn-editar">
            ✏️ Editar Dieta
        </a>
    </div>

    <!-- Header de la dieta -->
    <div class="dieta-header">
        <h1>📋 <?php echo htmlspecialchars($dieta['nombre']); ?></h1>
        <?php if ($dieta['descripcion']): ?>
            <p><?php echo htmlspecialchars($dieta['descripcion']); ?></p>
        <?php endif; ?>
    </div>

    <!-- Composición de la dieta -->
    <div class="info-card">
        <h3>🌾 Composición de la Dieta</h3>
        
        <?php if (count($composicion) > 0): ?>
            <table class="composicion-tabla">
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
                            <td><strong><?php echo htmlspecialchars($item['nombre_insumo']); ?></strong></td>
                            <td>
                                <span class="tipo-badge tipo-<?php echo strtolower($item['tipo']); ?>">
                                    <?php echo htmlspecialchars($item['tipo']); ?>
                                </span>
                            </td>
                            <td><?php echo number_format($item['porcentaje_ms'], 1); ?>%</td>
                            <td><strong><?php echo number_format($item['porcentaje_teorico'], 1); ?>%</strong></td>
                            <td>
                                <div class="porcentaje-bar">
                                    <div class="porcentaje-fill" style="width: <?php echo $item['porcentaje_teorico']; ?>%">
                                        <?php echo number_format($item['porcentaje_teorico'], 1); ?>%
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Resumen de MS -->
            <div class="resumen-ms">
                <h4>📊 Resumen de Materia Seca</h4>
                <div class="ms-grid">
                    <div class="ms-item">
                        <p class="valor"><?php echo number_format($ms_total, 2); ?>%</p>
                        <p class="etiqueta">% MS Total de la Dieta</p>
                    </div>
                    <div class="ms-item">
                        <p class="valor"><?php echo count($composicion); ?></p>
                        <p class="etiqueta">Insumos en la Mezcla</p>
                    </div>
                    <div class="ms-item">
                        <p class="valor">
                            <?php
                            $total_porcentaje = array_sum(array_column($composicion, 'porcentaje_teorico'));
                            echo number_format($total_porcentaje, 1);
                            ?>%
                        </p>
                        <p class="etiqueta">Total Porcentajes</p>
                    </div>
                </div>

                <div class="nota-box">
                    <p>
                        <strong>💡 Nota:</strong> El % MS total indica la cantidad de materia seca que contiene esta dieta.
                        Para calcular el consumo de MS de los animales, se multiplican los kg totales entregados
                        por este porcentaje.
                    </p>
                </div>
            </div>
        <?php else: ?>
            <div class="sin-insumos">
                <div class="sin-insumos-icono">⚠️</div>
                <p><strong>Esta dieta no tiene insumos asignados</strong></p>
                <p>Editá la dieta para agregar insumos.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Lotes que usan esta dieta -->
    <div class="info-card">
        <h3>🐮 Lotes Usando Esta Dieta</h3>
        
        <?php if (count($lotes_usando) > 0): ?>
            <ul class="lotes-lista">
                <?php foreach ($lotes_usando as $lote): ?>
                    <li>
                        <strong><?php echo htmlspecialchars($lote['nombre_lote']); ?></strong>
                        <br>
                        <small>
                            📍 <?php echo htmlspecialchars($lote['campo'] ?? 'Sin campo'); ?> •
                            🐄 <?php echo $lote['cantidad_inicial']; ?> animales •
                            📅 Desde: <?php echo date('d/m/Y', strtotime($lote['fecha_desde'])); ?>
                        </small>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="sin-lotes">
                <p>📊 Esta dieta no está siendo usada por ningún lote actualmente.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
