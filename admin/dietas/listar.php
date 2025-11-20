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

<style>
.dietas-container {
    max-width: 1400px;
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
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-crear:hover {
    background: #3d7043;
    transform: translateY(-2px);
}

.dietas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.dieta-card {
    background: white;
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: all 0.3s;
    border-left: 5px solid #2c5530;
}

.dieta-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.dieta-nombre {
    font-size: 1.4em;
    font-weight: bold;
    color: #2c5530;
    margin: 0 0 10px 0;
}

.dieta-descripcion {
    color: #666;
    font-size: 0.95em;
    margin: 0 0 15px 0;
    line-height: 1.5;
    min-height: 45px;
}

.dieta-info {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    margin: 15px 0;
    padding: 15px 0;
    border-top: 1px solid #eee;
    border-bottom: 1px solid #eee;
}

.info-item {
    text-align: center;
}

.info-numero {
    font-size: 1.8em;
    font-weight: bold;
    color: #2c5530;
    margin: 0;
}

.info-etiqueta {
    font-size: 0.85em;
    color: #666;
    margin: 5px 0 0 0;
}

.dieta-acciones {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    margin-top: 15px;
}

.btn-accion {
    padding: 8px 12px;
    text-decoration: none;
    border-radius: 5px;
    font-size: 0.9em;
    font-weight: bold;
    text-align: center;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
}

.btn-ver {
    background: #17a2b8;
    color: white;
}

.btn-ver:hover {
    background: #138496;
}

.btn-editar {
    background: #2c5530;
    color: white;
}

.btn-editar:hover {
    background: #3d7043;
}

.btn-eliminar {
    background: #dc3545;
    color: white;
}

.btn-eliminar:hover {
    background: #c82333;
}

.badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.85em;
    font-weight: bold;
}

.badge-activa {
    background: #d4edda;
    color: #155724;
}

.badge-en-uso {
    background: #fff3cd;
    color: #856404;
}

.mensaje-vacio {
    background: white;
    padding: 60px 20px;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.mensaje-vacio .icono {
    font-size: 5em;
    margin-bottom: 20px;
    opacity: 0.5;
}

.stats-dietas {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
}

.stat-card .numero {
    font-size: 2.5em;
    font-weight: bold;
    color: #2c5530;
}

.stat-card .etiqueta {
    color: #666;
    font-size: 0.9em;
}

@media (max-width: 768px) {
    .dietas-grid {
        grid-template-columns: 1fr;
    }
    
    .dieta-acciones {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="dietas-container">
    <!-- Header -->
    <div class="header-section">
        <div>
            <h1 style="margin: 0 0 5px 0;">📋 Gestión de Dietas</h1>
            <p style="margin: 0; color: #666;">
                Administrá las dietas teóricas que se asignan a los lotes
            </p>
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
                            echo '<em style="color: #999;">Sin descripción</em>';
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

                    <div style="margin: 15px 0; text-align: center;">
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

                    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee; font-size: 0.85em; color: #999; text-align: center;">
                        Creada: <?php echo date('d/m/Y', strtotime($dieta['fecha_creacion'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="mensaje-vacio">
            <div class="icono">📋</div>
            <h2 style="color: #666;">No hay dietas registradas</h2>
            <p style="color: #999;">Creá la primera dieta para comenzar a asignarlas a los lotes.</p>
            <a href="crear.php" class="btn-crear" style="margin-top: 20px;">
                ➕ Crear Primera Dieta
            </a>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer.php'; ?>
