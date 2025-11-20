<?php
// admin/insumos/listar.php - Actualizado a PDO
require_once '../../config/database.php';
require_once '../../includes/functions.php';

$page_title = "Gestión de Insumos";
$db = getConnection();

// Obtener todos los insumos activos
$stmt = $db->query("
    SELECT 
        id_insumo,
        nombre,
        tipo,
        porcentaje_ms,
        activo
    FROM insumo
    WHERE activo = 1
    ORDER BY nombre ASC
");
$insumos = $stmt->fetchAll();

// Contar insumos por tipo
$stmt = $db->query("
    SELECT tipo, COUNT(*) as total
    FROM insumo
    WHERE activo = 1
    GROUP BY tipo
");
$conteo_tipos = $stmt->fetchAll();

require_once '../../includes/header.php';
?>

<style>
.insumos-container {
    max-width: 1200px;
    margin: 0 auto;
}

.header-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.btn-crear {
    background: #2c5530;
    color: white;
    padding: 12px 25px;
    text-decoration: none;
    border-radius: 5px;
    font-weight: bold;
    transition: all 0.3s;
}

.btn-crear:hover {
    background: #3d7043;
    transform: translateY(-2px);
}

.stats-tipos {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 30px;
}

.stat-tipo {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
}

.stat-tipo .numero {
    font-size: 2em;
    font-weight: bold;
    color: #2c5530;
}

.tabla-card {
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

table {
    width: 100%;
    border-collapse: collapse;
}

table th {
    background: #f8f9fa;
    padding: 12px;
    text-align: left;
    font-weight: bold;
    border-bottom: 2px solid #dee2e6;
}

table td {
    padding: 12px;
    border-bottom: 1px solid #dee2e6;
}

table tr:hover {
    background: #f8f9fa;
}

.badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.85em;
    font-weight: bold;
}

.badge-grano { background: #fff3cd; color: #856404; }
.badge-forraje { background: #d4edda; color: #155724; }
.badge-concentrado { background: #d1ecf1; color: #0c5460; }
.badge-suplemento { background: #f8d7da; color: #721c24; }

.btn-accion {
    padding: 6px 12px;
    margin: 0 3px;
    text-decoration: none;
    border-radius: 4px;
    font-size: 0.9em;
    transition: all 0.3s;
}

.btn-editar {
    background: #2c5530;
    color: white;
}

.btn-editar:hover {
    background: #3d7043;
}

.ms-badge {
    background: #e7f3e7;
    color: #2c5530;
    padding: 5px 10px;
    border-radius: 8px;
    font-weight: bold;
}
</style>

<div class="insumos-container">
    <!-- Header -->
    <div class="header-section">
        <div>
            <h1 style="margin: 0 0 5px 0;">🌾 Gestión de Insumos</h1>
            <p style="margin: 0; color: #666;">
                Administrá los insumos disponibles para las dietas
            </p>
        </div>
        <a href="crear.php" class="btn-crear">
            ➕ Crear Nuevo Insumo
        </a>
    </div>

    <!-- Estadísticas por tipo -->
    <?php if (count($conteo_tipos) > 0): ?>
        <div class="stats-tipos">
            <?php foreach ($conteo_tipos as $tipo): ?>
                <div class="stat-tipo">
                    <div class="numero"><?php echo $tipo['total']; ?></div>
                    <div><?php echo htmlspecialchars($tipo['tipo']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Tabla de insumos -->
    <div class="tabla-card">
        <h3 style="margin: 0 0 20px 0; color: #2c5530;">📋 Lista de Insumos</h3>
        
        <?php if (count($insumos) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>% Materia Seca</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($insumos as $insumo): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($insumo['nombre']); ?></strong>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo strtolower($insumo['tipo']); ?>">
                                    <?php echo htmlspecialchars($insumo['tipo']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="ms-badge">
                                    <?php echo number_format($insumo['porcentaje_ms'], 1); ?>% MS
                                </span>
                            </td>
                            <td>
                                <a href="editar.php?id=<?php echo $insumo['id_insumo']; ?>" 
                                   class="btn-accion btn-editar">
                                    ✏️ Editar
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div style="text-align: center; padding: 40px; color: #666;">
                <div style="font-size: 3em; margin-bottom: 15px;">🌾</div>
                <p><strong>No hay insumos registrados</strong></p>
                <p>Creá el primer insumo para comenzar.</p>
                <a href="crear.php" class="btn-crear" style="display: inline-block; margin-top: 15px;">
                    ➕ Crear Primer Insumo
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
