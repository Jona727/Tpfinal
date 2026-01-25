<?php
// admin/insumos/listar.php - Actualizado a PDO
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Verificar permisos de administrador
verificarAdmin();

$page_title = "Gestión de Insumos";
$db = getConnection();

// Configuración de paginación
$registros_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;

$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Filtros
$mostrar_inactivos = isset($_GET['inactivos']) && $_GET['inactivos'] == 1;
$condicion_activo = $mostrar_inactivos ? 0 : 1;
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';

// Construir consulta base
$sql_base = "FROM insumo WHERE activo = :activo";
$params = [':activo' => $condicion_activo];

if (!empty($busqueda)) {
    // Usamos parámetros únicos para evitar problemas con PDO en algunos drivers
    $sql_base .= " AND (nombre LIKE :busqueda1 OR tipo LIKE :busqueda2)";
    $params[':busqueda1'] = "%$busqueda%";
    $params[':busqueda2'] = "%$busqueda%";
}

// Obtener total de registros para paginación
// Para el count, podemos usar el array params directamente en execute
$stmt_count = $db->prepare("SELECT COUNT(*) " . $sql_base);
$stmt_count->execute($params);
$total_registros = $stmt_count->fetchColumn();
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Obtener insumos paginados
$sql_final = "SELECT * " . $sql_base . " ORDER BY nombre ASC LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($sql_final);

// Vincular los parámetros de filtro
foreach ($params as $key => $val) {
    if (is_int($val)) {
        $stmt->bindValue($key, $val, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($key, $val, PDO::PARAM_STR);
    }
}

// Vincular límite y offset
$stmt->bindValue(':limit', $registros_por_pagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$insumos = $stmt->fetchAll();

// La consulta de conteo por tipo se ha eliminado por solicitud del usuario (ahorro de espacio)

require_once '../../includes/header.php';
?>

<div class="insumos-container">
    <!-- Header -->
    <div class="page-header">
        <div>
            <h1 style="font-weight: 800; color: var(--primary); margin: 0; letter-spacing: -1px;">
                <?php echo $mostrar_inactivos ? '🗑️ Insumos Inactivos' : '🌾 Gestión de Insumos'; ?>
            </h1>
            <p style="margin: 0.25rem 0 0 0; color: var(--text-muted); font-size: 0.95rem; font-weight: 500;">
                <?php echo $mostrar_inactivos ? 'Visualizando insumos eliminados/inactivos' : 'Administrá los insumos disponibles para las dietas'; ?>
            </p>
        </div>
        <div class="header-actions">
            <?php if ($mostrar_inactivos): ?>
                <a href="listar.php" class="btn btn-secondary" style="padding: 0.875rem 1.5rem;">
                    <span>👀</span> Ver Activos
                </a>
            <?php else: ?>
                <a href="listar.php?inactivos=1" class="btn btn-secondary" style="background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1;">
                    <span>🗑️</span> Ver Inactivos
                </a>
                <a href="crear.php" class="btn btn-primary" style="padding: 0.875rem 1.5rem;">
                    <span>➕</span> Nuevo Insumo
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Buscador -->
    <div class="card" style="padding: 1rem; margin-bottom: 2rem;">
        <form method="GET" action="listar.php" style="display: flex; gap: 1rem;">
            <?php if ($mostrar_inactivos): ?>
                <input type="hidden" name="inactivos" value="1">
            <?php endif; ?>
            <div style="flex: 1; position: relative;">
                <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); font-size: 1.2rem;">🔍</span>
                <input type="text" name="busqueda" value="<?php echo htmlspecialchars($busqueda); ?>" placeholder="Buscar insumo por nombre o tipo..." style="padding-left: 3rem; width: 100%;">
            </div>
            <button type="submit" class="btn btn-primary">Buscar</button>
            <?php if (!empty($busqueda)): ?>
                <a href="listar.php<?php echo $mostrar_inactivos ? '?inactivos=1' : ''; ?>" class="btn btn-secondary" title="Limpiar búsqueda">✕</a>
            <?php endif; ?>
        </form>
    </div>

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
                            <th>% MS</th>
                            <th class="th-actions">Acciones</th>
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
                                <td style="text-align: right; white-space: nowrap;">
                                    <a href="ver.php?id=<?php echo $insumo['id_insumo']; ?>" class="btn btn-secondary btn-action">
                                        <span>👁️</span> <span class="btn-text">Ver</span>
                                    </a>
                                    <a href="editar.php?id=<?php echo $insumo['id_insumo']; ?>" class="btn btn-secondary btn-action">
                                        <span>✏️</span> <span class="btn-text">Editar</span>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <?php if ($total_paginas > 1): ?>
                <div style="display: flex; justify-content: center; margin-top: 2rem; gap: 0.5rem;">
                    <?php 
                    $range = 2;
                    $initial_num = $pagina_actual - $range;
                    $condition_limit_num = ($pagina_actual + $range)  + 1;
                    ?>

                    <?php if ($pagina_actual > 1): ?>
                        <a href="?pagina=1<?php echo !empty($busqueda) ? '&busqueda=' . urlencode($busqueda) : ''; ?><?php echo $mostrar_inactivos ? '&inactivos=1' : ''; ?>" class="btn btn-secondary" style="padding: 0.5rem 1rem;">«</a>
                        <a href="?pagina=<?php echo $pagina_actual - 1; ?><?php echo !empty($busqueda) ? '&busqueda=' . urlencode($busqueda) : ''; ?><?php echo $mostrar_inactivos ? '&inactivos=1' : ''; ?>" class="btn btn-secondary" style="padding: 0.5rem 1rem;">‹</a>
                    <?php endif; ?>

                    <?php for ($x = $initial_num; $x < $condition_limit_num; $x++): ?>
                        <?php if (($x > 0) && ($x <= $total_paginas)): ?>
                            <?php if ($x == $pagina_actual): ?>
                                <span class="btn btn-primary" style="padding: 0.5rem 1rem; cursor: default;"><?php echo $x; ?></span>
                            <?php else: ?>
                                <a href="?pagina=<?php echo $x; ?><?php echo !empty($busqueda) ? '&busqueda=' . urlencode($busqueda) : ''; ?><?php echo $mostrar_inactivos ? '&inactivos=1' : ''; ?>" class="btn btn-secondary" style="padding: 0.5rem 1rem;"><?php echo $x; ?></a>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($pagina_actual < $total_paginas): ?>
                        <a href="?pagina=<?php echo $pagina_actual + 1; ?><?php echo !empty($busqueda) ? '&busqueda=' . urlencode($busqueda) : ''; ?><?php echo $mostrar_inactivos ? '&inactivos=1' : ''; ?>" class="btn btn-secondary" style="padding: 0.5rem 1rem;">›</a>
                        <a href="?pagina=<?php echo $total_paginas; ?><?php echo !empty($busqueda) ? '&busqueda=' . urlencode($busqueda) : ''; ?><?php echo $mostrar_inactivos ? '&inactivos=1' : ''; ?>" class="btn btn-secondary" style="padding: 0.5rem 1rem;">»</a>
                    <?php endif; ?>
                </div>
                <div style="text-align: center; margin-top: 1rem; color: var(--text-muted); font-size: 0.9rem;">
                    Mostrando <?php echo count($insumos); ?> de <?php echo $total_registros; ?> resultados
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 4rem 2rem; border: 2px dashed var(--border); border-radius: var(--radius); opacity: 0.6;">
                <div style="font-size: 4rem; margin-bottom: 1.5rem;">🌾</div>
                <h2 style="color: var(--text-muted); font-weight: 800;">No se encontraron resultados</h2>
                <p style="color: var(--text-muted); margin-bottom: 2rem;">
                    <?php if (!empty($busqueda)): ?>
                        No hay insumos que coincidan con "<strong><?php echo htmlspecialchars($busqueda); ?></strong>".
                        <br><a href="listar.php<?php echo $mostrar_inactivos ? '?inactivos=1' : ''; ?>" style="color: var(--primary); font-weight: 700;">Limpiar búsqueda</a>
                    <?php else: ?>
                        Creá el primer insumo para comenzar.
                    <?php endif; ?>
                </p>
                <?php if (empty($busqueda)): ?>
                    <a href="crear.php" class="btn btn-primary btn-lg">
                        <span>➕</span> Crear Primer Insumo
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
