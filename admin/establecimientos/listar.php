<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

verificarAdmin();

$db = getConnection();

// Obtener estadísticas y lista de establecimientos
$stmt = $db->query("
    SELECT 
        c.*,
        COUNT(DISTINCT t.id_tropa) as total_lotes,
        COALESCE(SUM(t.cantidad_inicial), 0) as total_animales
    FROM campo c
    LEFT JOIN tropa t ON c.id_campo = t.id_campo AND t.activo = 1
    GROUP BY c.id_campo
    ORDER BY c.nombre ASC
");
$establecimientos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Establecimientos - Solufeed</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <style>
        .grid-campos {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .card-campo {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: transform 0.2s, box-shadow 0.2s;
            border: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
        }
        
        .card-campo:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary) 0%, #1e3a21 100%);
            color: white;
            padding: 1.5rem;
            position: relative;
        }
        
        .card-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .card-header .location {
            font-size: 0.9rem;
            opacity: 0.8;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .card-body {
            padding: 1.5rem;
            flex: 1;
        }
        
        .metric-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .metric {
            text-align: center;
            flex: 1;
        }
        
        .metric .value {
            display: block;
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
        }
        
        .metric .label {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 600;
        }
        
        .card-footer {
            padding: 1rem 1.5rem;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
        }
        
        .btn-icon-text {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.2s;
        }
        
        .btn-edit { background: #e0f2fe; color: #0369a1; }
        .btn-edit:hover { background: #bae6fd; }
        
        .btn-view { background: var(--primary); color: white; }
        .btn-view:hover { background: var(--primary-dark); }
        
        .badge-status {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(4px);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>

    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <div>
                <h1 style="font-weight: 800; color: var(--primary); margin: 0;">🏭 Establecimientos</h1>
                <p style="color: var(--text-muted); margin-top: 0.5rem;">Gestiona los campos y sus unidades de producción</p>
            </div>
            <a href="crear.php" class="btn-nuevo" style="background: var(--primary); color: white; padding: 0.75rem 1.5rem; border-radius: 8px; text-decoration: none; font-weight: 600;">
                ➕ Nuevo Campo
            </a>
        </div>

        <?php if (count($establecimientos) > 0): ?>
            <div class="grid-campos">
                <?php foreach ($establecimientos as $campo): ?>
                    <div class="card-campo">
                        <div class="card-header">
                            <span class="badge-status">
                                <?php echo $campo['activo'] ? 'Activo' : 'Inactivo'; ?>
                            </span>
                            <h2><?php echo htmlspecialchars($campo['nombre']); ?></h2>
                            <?php if ($campo['ubicacion']): ?>
                                <div class="location">
                                    📍 <?php echo htmlspecialchars($campo['ubicacion']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <div class="metric-row">
                                <div class="metric">
                                    <span class="value"><?php echo $campo['total_lotes']; ?></span>
                                    <span class="label">Lotes Activos</span>
                                </div>
                                <div class="metric">
                                    <span class="value"><?php echo number_format($campo['total_animales']); ?></span>
                                    <span class="label">Cabezas</span>
                                </div>
                            </div>
                            <p style="margin: 0; font-size: 0.9rem; color: var(--text-muted);">
                                <?php if (isset($campo['fecha_creacion'])): ?>
                                    Creado el <?php echo date('d/m/Y', strtotime($campo['fecha_creacion'])); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="card-footer">
                            <a href="gestionar_lotes.php?id=<?php echo $campo['id_campo']; ?>" class="btn-icon-text" style="background: #f0fdf4; color: #166534; margin-right: auto;">
                                🐮 Asignar Lotes
                            </a>
                            <a href="editar.php?id=<?php echo $campo['id_campo']; ?>" class="btn-icon-text btn-edit">
                                ✏️ Editar
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state" style="text-align: center; padding: 4rem; border: 2px dashed #e2e8f0; border-radius: 12px; margin-top: 2rem;">
                <div style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;">🏭</div>
                <h3>No hay establecimientos registrados</h3>
                <p>Comienza creando el primer campo para asignar tus lotes.</p>
                <a href="crear.php" style="margin-top: 1rem; display: inline-block; color: var(--primary); font-weight: 600;">Crear ahora →</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
