<?php
// admin/dietas/listar.php - Actualizado a PDO con CSS moderno
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Verificar permisos de administrador
verificarAdmin();

$page_title = "Gestión de Dietas";
$db = getConnection();

// Obtener todas las dietas activas con sus detalles
$stmt = $db->query("
    SELECT 
        d.id_dieta,
        d.nombre,
        d.descripcion,
        d.activo,
        d.fecha_creacion,
        (SELECT COUNT(*) FROM dieta_detalle WHERE id_dieta = d.id_dieta) as cantidad_insumos,
        (SELECT COUNT(*) FROM tropa_dieta_asignada tda 
         WHERE tda.id_dieta = d.id_dieta AND tda.fecha_hasta IS NULL) as lotes_usando
    FROM dieta d
    WHERE d.activo = 1
    ORDER BY d.nombre ASC
");
$dietas = $stmt->fetchAll();

require_once '../../includes/header.php';
?>



<div class="dietas-container">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; background: var(--surface); padding: 1.5rem; border-radius: var(--radius); box-shadow: var(--shadow-sm); border: 1px solid var(--border);">
        <div>
            <h1 style="font-weight: 800; color: var(--primary); margin: 0; letter-spacing: -1px;">📋 Gestión de Dietas</h1>
            <p style="margin: 0.25rem 0 0 0; color: var(--text-muted); font-size: 0.95rem; font-weight: 500;">Administrá las dietas teóricas asignadas a los lotes</p>
        </div>
        <a href="crear.php" class="btn btn-primary" style="padding: 0.875rem 1.5rem;">
            <span>➕</span> Crear Dieta
        </a>
    </div>

    <!-- Estadísticas -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <div class="card" style="text-align: center; padding: 1.5rem; margin-bottom: 0;">
            <div style="font-size: 2.5rem; font-weight: 800; color: var(--primary);"><?php echo count($dietas); ?></div>
            <div style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">Dietas Activas</div>
        </div>
        <div class="card" style="text-align: center; padding: 1.5rem; margin-bottom: 0;">
            <div style="font-size: 2.5rem; font-weight: 800; color: var(--secondary);">
                <?php 
                $total_insumos = array_sum(array_column($dietas, 'cantidad_insumos'));
                echo $total_insumos; 
                ?>
            </div>
            <div style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">Insumos en Uso</div>
        </div>
        <div class="card" style="text-align: center; padding: 1.5rem; margin-bottom: 0;">
            <div style="font-size: 2.5rem; font-weight: 800; color: var(--accent);">
                <?php 
                $lotes_usando = array_sum(array_column($dietas, 'lotes_usando'));
                echo $lotes_usando; 
                ?>
            </div>
            <div style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">Lotes Asociados</div>
        </div>
    </div>

    <!-- Grid de dietas -->
    <?php if (count($dietas) > 0): ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem;">
            <?php foreach ($dietas as $dieta): ?>
                <div class="card" style="display: flex; flex-direction: column; border-top: 5px solid var(--primary); padding: 1.75rem;">
                    <h3 style="font-size: 1.4rem; font-weight: 800; color: var(--primary); margin-bottom: 0.75rem; letter-spacing: -0.5px;">
                        <?php echo htmlspecialchars($dieta['nombre']); ?>
                    </h3>
                    
                    <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 1.5rem; line-height: 1.5; min-height: 3rem;">
                        <?php 
                        if ($dieta['descripcion']) {
                            echo htmlspecialchars($dieta['descripcion']);
                        } else {
                            echo '<em style="opacity: 0.5;">Sin descripción disponible</em>';
                        }
                        ?>
                    </p>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem; padding: 1rem; background: var(--bg-main); border-radius: 12px; text-align: center;">
                        <div>
                            <div style="font-size: 1.5rem; font-weight: 800; color: var(--primary);"><?php echo $dieta['cantidad_insumos']; ?></div>
                            <div style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Insumos</div>
                        </div>
                        <div>
                            <div style="font-size: 1.5rem; font-weight: 800; color: var(--primary);"><?php echo $dieta['lotes_usando']; ?></div>
                            <div style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Lotes</div>
                        </div>
                    </div>

                    <div style="margin-bottom: 1.5rem; display: flex; justify-content: center; gap: 0.5rem;">
                        <span class="badge" style="background: var(--bg-main); color: var(--success); font-weight: 700;">✓ Activa</span>
                        <?php if ($dieta['lotes_usando'] > 0): ?>
                            <span class="badge" style="background: #fef3c7; color: #92400e; font-weight: 700;">En Uso</span>
                        <?php endif; ?>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-top: auto;">
                        <a href="ver.php?id=<?php echo $dieta['id_dieta']; ?>" class="btn btn-secondary" style="font-size: 0.85rem;">
                            <span>👁️</span> Ver
                        </a>
                        <a href="editar.php?id=<?php echo $dieta['id_dieta']; ?>" class="btn btn-primary" style="font-size: 0.85rem;">
                            <span>✏️</span> Editar
                        </a>
                    </div>

                    <div style="margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--border); font-size: 0.8rem; color: var(--text-muted); text-align: center; font-weight: 500;">
                        📅 Creada: <?php echo date('d/m/Y', strtotime($dieta['fecha_creacion'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="card" style="text-align: center; padding: 4rem 2rem; border-style: dashed; background: transparent; opacity: 0.6;">
            <div style="font-size: 4rem; margin-bottom: 1.5rem;">📋</div>
            <h2 style="color: var(--text-muted); font-weight: 800;">No hay dietas registradas</h2>
            <p style="color: var(--text-muted); margin-bottom: 2rem;">Creá la primera dieta para comenzar a asignarlas a los lotes.</p>
            <a href="crear.php" class="btn btn-primary btn-lg">
                ➕ Crear Primera Dieta
            </a>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer.php'; ?>
