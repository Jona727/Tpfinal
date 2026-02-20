<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

verificarAdmin();

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $ubicacion = trim($_POST['ubicacion'] ?? '');
    
    if (empty($nombre)) {
        $error = "El nombre es obligatorio.";
    } else {
        try {
            $db = getConnection();
            $stmt = $db->prepare("INSERT INTO campo (nombre, ubicacion, activo, fecha_creacion) VALUES (?, ?, 1, NOW())");
            $stmt->execute([$nombre, $ubicacion]);
            
            header('Location: listar.php?msg=creado');
            exit();
        } catch (Exception $e) {
            $error = "Error al crear: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Establecimiento - Solufeed</title>
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
        .form-group input { width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; }
        .btn-submit { width: 100%; padding: 1rem; background: var(--primary); color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; }
        .btn-submit:hover { background: var(--primary-dark); }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container">
        <a href="listar.php" style="text-decoration: none; color: var(--text-muted);">← Volver</a>
        
        <div class="form-container">
            <h1 style="margin-top: 0;">🏭 Nuevo Establecimiento</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error" style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Nombre del Campo *</label>
                    <input type="text" name="nombre" required placeholder="Ej: Campo Norte - Sector A">
                </div>
                
                <div class="form-group">
                    <label>Ubicación / Referencia</label>
                    <input type="text" name="ubicacion" placeholder="Ej: Ruta 5 km 200">
                </div>
                
                <button type="submit" class="btn-submit">Guardar Establecimiento</button>
            </form>
        </div>
    </div>
</body>
</html>
