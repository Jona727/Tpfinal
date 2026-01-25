<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

verificarAdmin();

$id = $_GET['id'] ?? 0;
$db = getConnection();
$error = '';

if (!$id) {
    header('Location: listar.php');
    exit();
}

// Obtener datos
$stmt = $db->prepare("SELECT * FROM campo WHERE id_campo = ?");
$stmt->execute([$id]);
$campo = $stmt->fetch();

if (!$campo) {
    header('Location: listar.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $ubicacion = trim($_POST['ubicacion'] ?? '');
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    if (empty($nombre)) {
        $error = "El nombre es obligatorio.";
    } else {
        try {
            $stmt = $db->prepare("UPDATE campo SET nombre = ?, ubicacion = ?, activo = ? WHERE id_campo = ?");
            $stmt->execute([$nombre, $ubicacion, $activo, $id]);
            
            header('Location: listar.php?msg=editado');
            exit();
        } catch (Exception $e) {
            $error = "Error al guardar: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Establecimiento - Solufeed</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <style>
        .form-container {
            max-width: 600px;
            margin: 2rem auto;
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
        .form-group input[type="text"] { width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; }
        .btn-submit { width: 100%; padding: 1rem; background: var(--primary); color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; }
        .btn-submit:hover { background: var(--primary-dark); }
        
        .status-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background: #f8fafc;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container">
        <a href="listar.php" style="text-decoration: none; color: var(--text-muted);">← Volver</a>
        
        <div class="form-container">
            <h1 style="margin-top: 0;">✏️ Editar Establecimiento</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error" style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Nombre del Campo *</label>
                    <input type="text" name="nombre" required value="<?php echo htmlspecialchars($campo['nombre']); ?>">
                </div>
                
                <div class="form-group">
                    <label>Ubicación / Referencia</label>
                    <input type="text" name="ubicacion" value="<?php echo htmlspecialchars($campo['ubicacion']); ?>">
                </div>
                
                <div class="form-group">
                    <label>Estado</label>
                    <div class="status-toggle">
                        <input type="checkbox" name="activo" id="activo" value="1" <?php echo $campo['activo'] ? 'checked' : ''; ?> style="width: 20px; height: 20px;">
                        <label for="activo" style="margin: 0; font-weight: normal;">Establecimiento Activo</label>
                    </div>
                    <small style="color: var(--text-muted);">Si desactivas el campo, no podrás asignar nuevos lotes a él.</small>
                </div>
                
                <button type="submit" class="btn-submit">Guardar Cambios</button>
            </form>
        </div>
    </div>
</body>
</html>
