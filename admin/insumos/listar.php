<?php
// admin/insumos/listar.php - Actualizado a PDO
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Verificar permisos de administrador
verificarAdmin();

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



<div class="insumos-container">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; background: var(--surface); padding: 1.5rem; border-radius: var(--radius); box-shadow: var(--shadow-sm); border: 1px solid var(--border);">
        <div>
            <h1 style="font-weight: 800; color: var(--primary); margin: 0; letter-spacing: -1px;">🌾 Gestión de Insumos</h1>
            <p style="margin: 0.25rem 0 0 0; color: var(--text-muted); font-size: 0.95rem; font-weight: 500;">Administrá los insumos disponibles para las dietas</p>
        </div>
        <a href="crear.php" class="btn btn-primary" style="padding: 0.875rem 1.5rem;">
            <span>➕</span> Nuevo Insumo
        </a>
    </div>

    <!-- Estadísticas por tipo -->
    <?php if (count($conteo_tipos) > 0): ?>
    <!-- Estadísticas por tipo -->
    <?php if (count($conteo_tipos) > 0): ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
            <?php foreach ($conteo_tipos as $tipo): ?>
                <div class="card" style="text-align: center; padding: 1.5rem; margin-bottom: 0;">
                    <div style="font-size: 2.5rem; font-weight: 800; color: var(--primary);"><?php echo $tipo['total']; ?></div>
                    <div style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">
                        <?php echo htmlspecialchars($tipo['tipo']); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php endif; ?>

    <!-- Tabla de insumos -->
    <!-- Tabla de insumos -->
    <div class="card">
        <h3 class="card-title"><span>📋</span> Lista de Insumos</h3>
        
        <?php if (count($insumos) > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th style="text-align: center;">% Materia Seca</th>
                            <th style="text-align: right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($insumos as $insumo): ?>
                            <tr>
                                <td>
                                    <strong style="color: var(--primary); font-size: 1.05rem;"><?php echo htmlspecialchars($insumo['nombre']); ?></strong>
                                </td>
                                <td>
                                    <span class="badge" style="background: var(--bg-main); color: var(--text-main); border: 1px solid var(--border);">
                                        <?php echo htmlspecialchars($insumo['tipo']); ?>
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <span style="background: #e7f3e7; color: var(--primary); padding: 4px 10px; border-radius: 8px; font-weight: 800; font-size: 0.9rem;">
                                        <?php echo number_format($insumo['porcentaje_ms'], 1); ?>% MS
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <a href="editar.php?id=<?php echo $insumo['id_insumo']; ?>" class="btn btn-secondary" style="font-size: 0.85rem; padding: 0.5rem 1rem;">
                                        <span>✏️</span> Editar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 4rem 2rem; border: 2px dashed var(--border); border-radius: var(--radius); opacity: 0.6;">
                <div style="font-size: 4rem; margin-bottom: 1.5rem;">🌾</div>
                <h2 style="color: var(--text-muted); font-weight: 800;">No hay insumos registrados</h2>
                <p style="color: var(--text-muted); margin-bottom: 2rem;">Creá el primer insumo para comenzar.</p>
                <a href="crear.php" class="btn btn-primary btn-lg">
                    <span>➕</span> Crear Primer Insumo
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
