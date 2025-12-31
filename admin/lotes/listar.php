<?php
/**
 * SOLUFEED - Listar Lotes/Tropas
 */

require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Verificar sesión
// Verificar permisos de administrador
verificarAdmin();

include '../../includes/header.php';

$db = getConnection();

// Obtener todos los lotes con información relacionada (Usando PDO)
$stmt = $db->query("
    SELECT 
        t.id_tropa,
        t.nombre,
        t.categoria,
        t.fecha_inicio,
        t.cantidad_inicial,
        t.activo,
        c.nombre as campo_nombre,
        d.nombre as dieta_nombre,
        (SELECT MAX(p.fecha) FROM pesada p WHERE p.id_tropa = t.id_tropa) as ultima_pesada,
        (SELECT MAX(cl.fecha) FROM consumo_lote cl WHERE cl.id_tropa = t.id_tropa) as ultima_alimentacion,
        (SELECT COUNT(*) FROM consumo_lote cl WHERE cl.id_tropa = t.id_tropa) as total_alimentaciones,
        (SELECT COUNT(*) FROM pesada p WHERE p.id_tropa = t.id_tropa) as total_pesadas
    FROM tropa t
    INNER JOIN campo c ON t.id_campo = c.id_campo
    LEFT JOIN tropa_dieta_asignada tda ON t.id_tropa = tda.id_tropa 
        AND tda.fecha_desde <= CURDATE() 
        AND (tda.fecha_hasta IS NULL OR tda.fecha_hasta >= CURDATE())
    LEFT JOIN dieta d ON tda.id_dieta = d.id_dieta
    ORDER BY t.activo DESC, t.fecha_inicio DESC
");

$lotes = $stmt->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; background: var(--surface); padding: 1.5rem; border-radius: var(--radius); box-shadow: var(--shadow-sm); border: 1px solid var(--border);">
    <div>
        <h1 style="font-weight: 800; color: var(--primary); margin: 0; letter-spacing: -1px;">🐮 Gestión de Lotes</h1>
        <p style="margin: 0.25rem 0 0 0; color: var(--text-muted); font-size: 0.95rem; font-weight: 500;">Administrá los lotes/tropas de animales en el feedlot</p>
    </div>
    <a href="crear.php" class="btn btn-primary" style="padding: 0.875rem 1.5rem;">
        <span>➕</span> Nuevo Lote
    </a>
</div>

<div class="card">
    <h3 class="card-title"><span>📋</span> Lista de Lotes</h3>
    
    <?php if (count($lotes) > 0): ?>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Lote</th>
                        <th>Campo</th>
                        <th>Animales</th>
                        <th>Dieta</th>
                        <th style="text-align: center;">Días</th>
                        <th style="text-align: center;">Estado</th>
                        <th style="text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lotes as $lote): ?>
                        <?php 
                        // Calcular animales presentes
                        $animales_presentes = obtenerAnimalesPresentes($lote['id_tropa']);
                        
                        // Calcular días desde el inicio
                        $fecha_inicio = new DateTime($lote['fecha_inicio']);
                        $fecha_hoy = new DateTime();
                        $dias_engorde = $fecha_inicio->diff($fecha_hoy)->days;
                        ?>
                        <tr>
                            <td>
                                <strong style="color: var(--primary); font-size: 1.05rem;"><?php echo htmlspecialchars($lote['nombre']); ?></strong>
                                <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo htmlspecialchars($lote['categoria']); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($lote['campo_nombre']); ?></td>
                            <td>
                                <span style="font-weight: 800; color: var(--primary); font-size: 1.1rem;"><?php echo $animales_presentes; ?></span>
                                <?php if ($animales_presentes != $lote['cantidad_inicial']): ?>
                                    <span style="font-size: 0.75rem; color: var(--text-muted); opacity: 0.7;">(de <?php echo $lote['cantidad_inicial']; ?>)</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($lote['dieta_nombre']): ?>
                                    <span style="color: var(--primary); font-weight: 600;">✓ <?php echo htmlspecialchars($lote['dieta_nombre']); ?></span>
                                <?php else: ?>
                                    <span style="color: var(--danger); font-weight: 700;">⚠ Sin dieta</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <span style="background: var(--bg-main); padding: 4px 10px; border-radius: 20px; font-weight: 700; font-size: 0.85rem;">
                                    <?php echo $dias_engorde; ?> d
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($lote['activo']): ?>
                                    <span class="badge" style="background: #dcfce7; color: #166534; border: 1px solid #bbf7d0;">Activo</span>
                                <?php else: ?>
                                    <span class="badge" style="background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;">Cerrado</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                    <a href="ver.php?id=<?php echo $lote['id_tropa']; ?>" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.4rem 0.8rem;">👁️ Ver</a>
                                    <a href="editar.php?id=<?php echo $lote['id_tropa']; ?>" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.4rem 0.8rem;">✏️</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
    <?php else: ?>
        
        <div style="text-align: center; padding: 4rem 2rem; border: 2px dashed var(--border); border-radius: var(--radius); opacity: 0.6;">
            <div style="font-size: 4rem; margin-bottom: 1.5rem;">🐮</div>
            <h2 style="color: var(--text-muted); font-weight: 800;">No hay lotes registrados</h2>
            <p style="color: var(--text-muted); margin-bottom: 2rem;">Creá el primer lote para comenzar a gestionar el engorde.</p>
            <a href="crear.php" class="btn btn-primary btn-lg">
                <span>➕</span> Crear Primer Lote
            </a>
        </div>
        
    <?php endif; ?>
    
</div>

<!-- Resumen estadístico -->
<?php if (count($lotes) > 0): ?>
    <?php
    // Calcular totales
    $total_animales = 0;
    $lotes_activos = 0;
    $lotes_sin_dieta = 0;
    
    foreach ($lotes as $lote) {
        if ($lote['activo']) {
            $lotes_activos++;
            $total_animales += obtenerAnimalesPresentes($lote['id_tropa']);
            if (empty($lote['dieta_nombre'])) {
                $lotes_sin_dieta++;
            }
        }
    }
    ?>
    
    <div class="card" style="background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%); color: white; border: none;">
        <h3 class="card-title" style="color: white; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 1rem;"><span>📊</span> Resumen General</h3>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem; padding: 1rem 0;">
            <div style="text-align: center;">
                <div style="font-size: 3rem; font-weight: 800; line-height: 1; margin-bottom: 0.5rem;"><?php echo $lotes_activos; ?></div>
                <div style="color: rgba(255,255,255,0.7); font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px;">Lotes Activos</div>
            </div>
            
            <div style="text-align: center;">
                <div style="font-size: 3rem; font-weight: 800; line-height: 1; margin-bottom: 0.5rem;"><?php echo number_format($total_animales); ?></div>
                <div style="color: rgba(255,255,255,0.7); font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px;">Cabezas en Engorde</div>
            </div>
            
            <?php if ($lotes_sin_dieta > 0): ?>
            <div style="text-align: center; background: rgba(239, 68, 68, 0.2); border-radius: var(--radius); padding: 1rem;">
                <div style="font-size: 3rem; font-weight: 800; line-height: 1; margin-bottom: 0.5rem; color: #fca5a5;"><?php echo $lotes_sin_dieta; ?></div>
                <div style="color: #fca5a5; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px;">⚠ Lotes Sin Dieta</div>
            </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>