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
    ORDER BY dd.porcentaje DESC
");
$stmt->execute([$id_dieta]);
$composicion = $stmt->fetchAll();

// Calcular MS total de la dieta
$ms_total = 0;
foreach ($composicion as $item) {
    $ms_total += ($item['porcentaje'] * $item['porcentaje_ms']) / 100;
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

<style>
.ver-dieta-container {
    max-width: 1200px;
    margin: 0 auto;
}

.breadcrumb {
    margin-bottom: 20px;
}

.breadcrumb a {
    color: #2c5530;
    text-decoration: none;
}

.breadcrumb a:hover {
    text-decoration: underline;
}

.dieta-header {
    background: linear-gradient(135deg, #2c5530 0%, #3d7043 100%);
    color: white;
    padding: 30px;
    border-radius: 10px;
    margin-bottom: 30px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.dieta-header h1 {
    margin: 0 0 10px 0;
    font-size: 2.2em;
}

.dieta-header p {
    margin: 0;
    font-size: 1.1em;
    opacity: 0.95;
}

.info-card {
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.info-card h3 {
    margin: 0 0 20px 0;
    color: #2c5530;
    font-size: 1.5em;
    border-bottom: 2px solid #2c5530;
    padding-bottom: 10px;
}

.composicion-tabla {
    width: 100%;
    border-collapse: collapse;
}

.composicion-tabla thead {
    background: #f8f9fa;
}

.composicion-tabla th {
    padding: 12px;
    text-align: left;
    font-weight: bold;
    border-bottom: 2px solid #dee2e6;
}

.composicion-tabla td {
    padding: 12px;
    border-bottom: 1px solid #dee2e6;
}

.composicion-tabla tbody tr:hover {
    background: #f8f9fa;
}

.tipo-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.85em;
    font-weight: bold;
}

.tipo-grano { background: #fff3cd; color: #856404; }
.tipo-forraje { background: #d4edda; color: #155724; }
.tipo-concentrado { background: #d1ecf1; color: #0c5460; }
.tipo-suplemento { background: #f8d7da; color: #721c24; }

.porcentaje-bar {
    height: 25px;
    background: #e9ecef;
    border-radius: 5px;
    overflow: hidden;
    position: relative;
}

.porcentaje-fill {
    height: 100%;
    background: linear-gradient(90deg, #2c5530 0%, #3d7043 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 0.85em;
}

.resumen-ms {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    margin-top: 20px;
    border-left: 4px solid #2c5530;
}

.resumen-ms h4 {
    margin: 0 0 15px 0;
    color: #2c5530;
}

.ms-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.ms-item {
    background: white;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
}

.ms-item .valor {
    font-size: 2em;
    font-weight: bold;
    color: #2c5530;
    margin: 0;
}

.ms-item .etiqueta {
    font-size: 0.9em;
    color: #666;
    margin: 5px 0 0 0;
}

.btn-group {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.btn {
    padding: 10px 20px;
    border-radius: 5px;
    text-decoration: none;
    font-weight: bold;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-volver {
    background: #6c757d;
    color: white;
}

.btn-volver:hover {
    background: #5a6268;
}

.btn-editar {
    background: #2c5530;
    color: white;
}

.btn-editar:hover {
    background: #3d7043;
}

.lotes-lista {
    list-style: none;
    padding: 0;
}

.lotes-lista li {
    background: #f8f9fa;
    padding: 15px;
    margin-bottom: 10px;
    border-radius: 5px;
    border-left: 4px solid #2c5530;
}

.lotes-lista li strong {
    color: #2c5530;
    font-size: 1.1em;
}
</style>

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
                            <td><strong><?php echo number_format($item['porcentaje'], 1); ?>%</strong></td>
                            <td>
                                <div class="porcentaje-bar">
                                    <div class="porcentaje-fill" style="width: <?php echo $item['porcentaje']; ?>%">
                                        <?php echo number_format($item['porcentaje'], 1); ?>%
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
                            $total_porcentaje = array_sum(array_column($composicion, 'porcentaje'));
                            echo number_format($total_porcentaje, 1); 
                            ?>%
                        </p>
                        <p class="etiqueta">Total Porcentajes</p>
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
    <div class="info-card">
        <h3>🐮 Lotes Usando Esta Dieta</h3>
        
        <?php if (count($lotes_usando) > 0): ?>
            <ul class="lotes-lista">
                <?php foreach ($lotes_usando as $lote): ?>
                    <li>
                        <strong><?php echo htmlspecialchars($lote['nombre_lote']); ?></strong>
                        <br>
                        <small style="color: #666;">
                            📍 <?php echo htmlspecialchars($lote['campo'] ?? 'Sin campo'); ?> • 
                            🐄 <?php echo $lote['cantidad_inicial']; ?> animales • 
                            📅 Desde: <?php echo date('d/m/Y', strtotime($lote['fecha_desde'])); ?>
                        </small>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div style="text-align: center; padding: 30px; color: #666;">
                <p>📊 Esta dieta no está siendo usada por ningún lote actualmente.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
