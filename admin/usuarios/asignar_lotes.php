<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

verificarAdmin();

$db = getConnection();
$mensaje = '';
$tipo_mensaje = '';

// Obtener ID del usuario
$id_usuario = $_GET['id'] ?? 0;

if (!$id_usuario) {
    header('Location: listar.php');
    exit();
}

// Obtener datos del usuario
$stmt = $db->prepare("SELECT * FROM usuario WHERE id_usuario = ?");
$stmt->execute([$id_usuario]);
$usuario = $stmt->fetch();

if (!$usuario) {
    header('Location: listar.php');
    exit();
}

// Verificar que sea tipo CAMPO (opcional, pero recomendado)
if ($usuario['tipo'] !== 'CAMPO') {
    // Si queremos permitir asignar a admin también para testing, quitamos esto.
    // Pero la lógica de negocio dice que los admin ven todo, así que esto es redundante para admins.
    // Dejaremos una advertencia visual en lugar de bloquear.
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 1. Borrar asignaciones existentes
        $stmt = $db->prepare("DELETE FROM usuario_tropa WHERE id_usuario = ?");
        $stmt->execute([$id_usuario]);

        // 2. Insertar nuevas asignaciones
        if (isset($_POST['lotes']) && is_array($_POST['lotes'])) {
            $stmt = $db->prepare("INSERT INTO usuario_tropa (id_usuario, id_tropa) VALUES (?, ?)");
            foreach ($_POST['lotes'] as $id_tropa) {
                $stmt->execute([$id_usuario, $id_tropa]);
            }
        }
        
        $mensaje = "Asignaciones actualizadas correctamente.";
        $tipo_mensaje = "success";
        
    } catch (Exception $e) {
        $mensaje = "Error al guardar: " . $e->getMessage();
        $tipo_mensaje = "error";
    }
}

// Obtener todos los lotes activos
$stmt = $db->query("
    SELECT t.id_tropa, t.nombre, c.nombre as nombre_campo 
    FROM tropa t 
    LEFT JOIN campo c ON t.id_campo = c.id_campo
    WHERE t.activo = 1 
    ORDER BY t.nombre ASC
");
$lotes_activos = $stmt->fetchAll();

// Obtener lotes ya asignados
$stmt = $db->prepare("SELECT id_tropa FROM usuario_tropa WHERE id_usuario = ?");
$stmt->execute([$id_usuario]);
$lotes_asignados = $stmt->fetchAll(PDO::FETCH_COLUMN);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignar Lotes - Solufeed</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <style>
        .assignment-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .user-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            border-left: 5px solid var(--primary);
        }
        
        .user-avatar {
            font-size: 3rem;
            background: #f0fdf4;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        
        .lotes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
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
            position: relative;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .lote-option:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        /* Checkbox oculto pero funcional */
        .lote-option input[type="checkbox"] {
            width: 20px;
            height: 20px;
            accent-color: var(--primary);
        }
        
        /* Estilos cuando está seleccionado */
        .lote-option.selected {
            background: #f0fdf4;
            border-color: var(--primary);
        }
        
        .lote-info h4 {
            margin: 0;
            color: var(--text);
            font-size: 1rem;
        }
        
        .lote-info small {
            color: var(--text-muted);
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
            box-shadow: 0 -4px 20px rgba(0,0,0,0.1);
            z-index: 100;
        }
        
        .btn-save {
            background: var(--primary);
            color: white;
            padding: 1rem 2rem;
            border-radius: 8px;
            border: none;
            font-weight: bold;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn-save:hover {
            background: var(--primary-dark);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        
        .alert-success { background: #dcfce7; color: #166534; }
        .alert-error { background: #fee2e2; color: #991b1b; }
        .alert-warning { background: #fef3c7; color: #92400e; }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>

    <div class="container assignment-container">
        
        <div style="margin-bottom: 1.5rem;">
            <a href="listar.php" style="text-decoration: none; color: var(--text-muted); font-weight: 500;">← Volver al listado</a>
        </div>

        <h1>📋 Asignación de Lotes</h1>
        <p style="color: var(--text-muted); margin-bottom: 2rem;">Selecciona los lotes que este operario podrá visualizar y gestionar.</p>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <div class="user-card">
            <div class="user-avatar">
                👤
            </div>
            <div>
                <h2 style="margin: 0; margin-bottom: 0.25rem;"><?php echo htmlspecialchars($usuario['nombre']); ?></h2>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <span style="background: #e2e8f0; padding: 2px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: 600;">
                        <?php echo $usuario['tipo']; ?>
                    </span>
                    <span style="color: var(--text-muted);"><?php echo htmlspecialchars($usuario['email']); ?></span>
                </div>
            </div>
        </div>

        <?php if ($usuario['tipo'] === 'ADMIN'): ?>
            <div class="alert alert-warning">
                ⚠️ <strong>Nota:</strong> Este usuario es ADMINISTRADOR. Por defecto tiene acceso a todos los lotes, la asignación aquí no restringirá su acceso, pero se guardará por si su rol cambia a futuro.
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="actions-bar" style="position: static; margin-bottom: 1.5rem; box-shadow: none; padding: 0; background: transparent;">
                <div style="font-weight: 600;">
                    <?php echo count($lotes_activos); ?> lotes disponibles
                </div>
                <div>
                    <button type="button" onclick="document.querySelectorAll('input[type=checkbox]').forEach(c => c.checked = true); updateVisuals();" style="border: none; background: none; color: var(--primary); cursor: pointer; font-weight: 600;">Seleccionar Todos</button>
                    <span style="color: #ccc;">|</span>
                    <button type="button" onclick="document.querySelectorAll('input[type=checkbox]').forEach(c => c.checked = false); updateVisuals();" style="border: none; background: none; color: var(--text-muted); cursor: pointer;">Desmarcar Todos</button>
                </div>
            </div>

            <div class="lotes-grid">
                <?php foreach ($lotes_activos as $lote): ?>
                    <?php $isChecked = in_array($lote['id_tropa'], $lotes_asignados); ?>
                    <label class="lote-option <?php echo $isChecked ? 'selected' : ''; ?>">
                        <input type="checkbox" name="lotes[]" value="<?php echo $lote['id_tropa']; ?>" 
                               <?php echo $isChecked ? 'checked' : ''; ?>
                               onchange="updateVisuals()">
                        <div class="lote-info">
                            <h4><?php echo htmlspecialchars($lote['nombre']); ?></h4>
                            <small>📍 <?php echo htmlspecialchars($lote['nombre_campo'] ?? 'Sin campo'); ?></small>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>

            <div class="actions-bar">
                <a href="listar.php" style="color: var(--text-muted); text-decoration: none; font-weight: 600;">Cancelar</a>
                <button type="submit" class="btn-save">💾 Guardar Asignación</button>
            </div>
        </form>
    </div>

    <script>
        function updateVisuals() {
            document.querySelectorAll('.lote-option input').forEach(input => {
                if (input.checked) {
                    input.closest('.lote-option').classList.add('selected');
                } else {
                    input.closest('.lote-option').classList.remove('selected');
                }
            });
        }
        // Inicializar
        updateVisuals();
    </script>
</body>
</html>
