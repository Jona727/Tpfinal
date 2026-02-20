<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

verificarAdmin();

$db = getConnection();
$mensaje = '';
$tipo_mensaje = '';

// Obtener ID del campo
$id_campo = $_GET['id'] ?? 0;

if (!$id_campo) {
    header('Location: listar.php');
    exit();
}

// Obtener datos del campo
$stmt = $db->prepare("SELECT * FROM campo WHERE id_campo = ?");
$stmt->execute([$id_campo]);
$campo = $stmt->fetch();

if (!$campo) {
    header('Location: listar.php');
    exit();
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();

        // 1. Desasignar los lotes que ESTABAN en este campo pero ahora NO están seleccionados (checkboxes)
        // Primero obtenemos los actuales
        $stmt = $db->prepare("SELECT id_tropa FROM tropa WHERE id_campo = ? AND activo = 1");
        $stmt->execute([$id_campo]);
        $lotes_actuales_db = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $lotes_seleccionados = $_POST['lotes'] ?? [];

        // Calcular lotes a desasignar (estaban en DB pero no en POST)
        $lotes_a_quitar = array_diff($lotes_actuales_db, $lotes_seleccionados);
        
        if (!empty($lotes_a_quitar)) {
            $ids_quitar = implode(',', array_map('intval', $lotes_a_quitar));
            $db->exec("UPDATE tropa SET id_campo = NULL WHERE id_tropa IN ($ids_quitar)");
        }

        // 2. Asignar los nuevos seleccionados
        // Esto automáticamente "roba" el lote si estaba en otro campo
        if (!empty($lotes_seleccionados)) {
            $ids_poner = implode(',', array_map('intval', $lotes_seleccionados));
            $db->exec("UPDATE tropa SET id_campo = $id_campo WHERE id_tropa IN ($ids_poner)");
        }

        $db->commit();
        $mensaje = "Lotes actualizados correctamente.";
        $tipo_mensaje = "success";
        
    } catch (Exception $e) {
        $db->rollBack();
        $mensaje = "Error al guardar: " . $e->getMessage();
        $tipo_mensaje = "error";
    }
}

// Obtener TODOS los lotes activos para mostrar en la lista
// Traemos también el nombre del campo actual para mostrar contexto
$stmt = $db->query("
    SELECT t.id_tropa, t.nombre, t.id_campo, c.nombre as nombre_campo_actual
    FROM tropa t 
    LEFT JOIN campo c ON t.id_campo = c.id_campo
    WHERE t.activo = 1 
    ORDER BY t.nombre ASC
");
$todos_lotes = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Lotes - Solufeed</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <style>
        .assignment-container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .header-card {
            background: linear-gradient(135deg, var(--primary) 0%, #1e3a21 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .lotes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .lote-option {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        
        .lote-option:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        .lote-option input[type="checkbox"] {
            width: 20px;
            height: 20px;
            accent-color: var(--primary);
            margin-top: 2px;
        }
        
        .lote-option.selected {
            background: #f0fdf4;
            border-color: var(--primary);
        }

        .lote-option.occupied {
            background: #fff7ed;
            border-color: #ffedd5;
        }
        
        .current-location-badge {
            display: inline-block;
            font-size: 0.75rem;
            padding: 2px 8px;
            border-radius: 4px;
            margin-top: 4px;
            background: #e2e8f0;
            color: #64748b;
        }

        .occupied .current-location-badge {
            background: #ffedd5;
            color: #9a3412;
        }

        .actions-bar {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            bottom: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            z-index: 100;
            border: 1px solid #e2e8f0;
        }
        
        .btn-save {
            background: var(--primary);
            color: white;
            padding: 1rem 2rem;
            border-radius: 8px;
            border: none;
            font-weight: bold;
            cursor: pointer;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        
        .alert-success { background: #dcfce7; color: #166534; }
        .alert-error { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>

    <div class="container assignment-container">
        
        <a href="listar.php" style="text-decoration: none; color: var(--text-muted); font-weight: 600; display: inline-block; margin-bottom: 1rem;">← Volver a Establecimientos</a>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <div class="header-card">
            <h1 style="margin: 0;">🏭 <?php echo htmlspecialchars($campo['nombre']); ?></h1>
            <p style="margin: 0.5rem 0 0 0; opacity: 0.9;">Selecciona los lotes que pertenecen a este establecimiento.</p>
        </div>

        <form method="POST">
            <div style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; color: var(--text-muted);">Lotes Disponibles</h3>
                <div style="font-size: 0.9rem;">
                    <span style="display:inline-block; width: 10px; height: 10px; background: #f0fdf4; border: 1px solid var(--primary); border-radius: 50%; margin-right: 5px;"></span> En este campo
                    <span style="display:inline-block; width: 10px; height: 10px; background: #fff7ed; border: 1px solid #fb923c; border-radius: 50%; margin-left: 10px; margin-right: 5px;"></span> En otro campo (se moverá)
                </div>
            </div>

            <div class="lotes-grid">
                <?php foreach ($todos_lotes as $lote): ?>
                    <?php 
                        $isInThisField = ($lote['id_campo'] == $id_campo);
                        $isOccupied = ($lote['id_campo'] && !$isInThisField);
                        
                        $class = $isInThisField ? 'selected' : ($isOccupied ? 'occupied' : '');
                    ?>
                    <label class="lote-option <?php echo $class; ?>">
                        <input type="checkbox" name="lotes[]" value="<?php echo $lote['id_tropa']; ?>" 
                               <?php echo $isInThisField ? 'checked' : ''; ?>
                               onchange="updateVisuals(this)">
                        <div>
                            <div style="font-weight: 700; color: var(--text);"><?php echo htmlspecialchars($lote['nombre']); ?></div>
                            
                            <?php if ($isOccupied): ?>
                                <div class="current-location-badge">📍 En: <?php echo htmlspecialchars($lote['nombre_campo_actual']); ?></div>
                            <?php elseif ($isInThisField): ?>
                                <div class="current-location-badge" style="background:var(--primary); color:white;">✓ Asignado aquí</div>
                            <?php else: ?>
                                <div class="current-location-badge">Sin asignar</div>
                            <?php endif; ?>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>

            <div class="actions-bar">
                <div style="font-size: 0.9rem; color: var(--text-muted);">
                    Selecciona los lotes y guarda para aplicar cambios.
                </div>
                <button type="submit" class="btn-save">💾 Actualizar Ubicación de Lotes</button>
            </div>
        </form>
    </div>

    <script>
        function updateVisuals(checkbox) {
            const label = checkbox.closest('.lote-option');
            if (checkbox.checked) {
                label.classList.add('selected');
                label.classList.remove('occupied'); // Remove warning color if selected
            } else {
                label.classList.remove('selected');
                // We don't restore 'occupied' class immediately purely by JS because we don't store initial state easily, 
                // but visually uncluttering is fine.
            }
        }
    </script>
</body>
</html>
