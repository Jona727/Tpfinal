<?php
// admin/dietas/listar.php - Actualizado a PDO con CSS moderno
require_once '../../config/database.php';
require_once '../../includes/functions.php';

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
    <div class="header-section">
        <div>
            <h1>📋 Gestión de Dietas</h1>
            <p>Administrá las dietas teóricas que se asignan a los lotes</p>
        </div>
        <a href="crear.php" class="btn-crear">
            ➕ Crear Nueva Dieta
        </a>
    </div>

    <!-- Estadísticas -->
    <div class="stats-dietas">
        <div class="stat-card">
            <div class="numero"><?php echo count($dietas); ?></div>
            <div class="etiqueta">Dietas Activas</div>
        </div>
        <div class="stat-card">
            <div class="numero">
                <?php 
                $total_insumos = array_sum(array_column($dietas, 'cantidad_insumos'));
                echo $total_insumos; 
                ?>
            </div>
            <div class="etiqueta">Insumos Totales en Uso</div>
        </div>
        <div class="stat-card">
            <div class="numero">
                <?php 
                $lotes_usando = array_sum(array_column($dietas, 'lotes_usando'));
                echo $lotes_usando; 
                ?>
            </div>
            <div class="etiqueta">Lotes Usando Dietas</div>
        </div>
    </div>

    <!-- Grid de dietas -->
    <?php if (count($dietas) > 0): ?>
        <div class="dietas-grid">
            <?php foreach ($dietas as $dieta): ?>
                <div class="dieta-card">
                    <h3 class="dieta-nombre">
                        <?php echo htmlspecialchars($dieta['nombre']); ?>
                    </h3>
                    
                    <p class="dieta-descripcion">
                        <?php
                        if ($dieta['descripcion']) {
                            echo htmlspecialchars($dieta['descripcion']);
                        } else {
                            echo '<em>Sin descripción</em>';
                        }
                        ?>
                    </p>

                    <div class="dieta-info">
                        <div class="info-item">
                            <p class="info-numero"><?php echo $dieta['cantidad_insumos']; ?></p>
                            <p class="info-etiqueta">Insumos</p>
                        </div>
                        <div class="info-item">
                            <p class="info-numero"><?php echo $dieta['lotes_usando']; ?></p>
                            <p class="info-etiqueta">Lotes Usando</p>
                        </div>
                    </div>

                    <div class="dieta-badges">
                        <span class="badge badge-activa">✓ Activa</span>
                        <?php if ($dieta['lotes_usando'] > 0): ?>
                            <span class="badge badge-en-uso">En Uso</span>
                        <?php endif; ?>
                    </div>

                    <div class="dieta-acciones">
                        <a href="ver.php?id=<?php echo $dieta['id_dieta']; ?>" 
                           class="btn-accion btn-ver"
                           title="Ver detalle">
                            👁️ Ver
                        </a>
                        <a href="editar.php?id=<?php echo $dieta['id_dieta']; ?>" 
                           class="btn-accion btn-editar"
                           title="Editar dieta">
                            ✏️ Editar
                        </a>
                        <a href="ver.php?id=<?php echo $dieta['id_dieta']; ?>" 
                           class="btn-accion btn-ver"
                           title="Ver composición">
                            📊 Detalles
                        </a>
                    </div>

                    <div class="dieta-footer">
                        Creada: <?php echo date('d/m/Y', strtotime($dieta['fecha_creacion'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="mensaje-vacio">
            <div class="icono">📋</div>
            <h2>No hay dietas registradas</h2>
            <p>Creá la primera dieta para comenzar a asignarlas a los lotes.</p>
            <a href="crear.php" class="btn-crear">
                ➕ Crear Primera Dieta
            </a>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer.php'; ?>
