<?php
/**
 * SOLUFEED - Header
 * Incluye navegación y estilos comunes
 */

// Iniciar sesión
iniciarSesion();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solufeed - El Choli SA</title>
    <link rel="stylesheet" href="/solufeed/assets/css/style.css">
</head>
<body>
    
    <!-- Header principal -->
    <header class="header-principal">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <h1>🐄 Solufeed</h1>
                    <span class="empresa">El Choli SA</span>
                </div>
                
                <?php if (isset($_SESSION['usuario_id'])): ?>
                <div class="usuario-info">
                    <span class="bienvenida">👤 <?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
                    <a href="/solufeed/admin/logout.php" class="btn-salir">Salir</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </header>
    
    <?php if (isset($_SESSION['usuario_id'])): ?>
    <!-- Menú de navegación -->
    <nav class="menu-principal">
        <div class="container">
            <ul class="menu-items">
                <li><a href="/solufeed/admin/dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">📊 Dashboard</a></li>
                
                <li><a href="/solufeed/admin/insumos/listar.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'insumos') !== false ? 'active' : ''; ?>">🌾 Insumos</a></li>
                
                <li><a href="/solufeed/admin/dietas/listar.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'dietas') !== false ? 'active' : ''; ?>">📋 Dietas</a></li>
                
                <li><a href="/solufeed/admin/lotes/listar.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'lotes') !== false ? 'active' : ''; ?>">🐮 Lotes</a></li>
                
                <li><a href="/solufeed/admin/alimentaciones/registrar.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'alimentaciones') !== false ? 'active' : ''; ?>">🍽️ Alimentar</a></li>
                
                <li><a href="/solufeed/admin/pesadas/registrar.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'pesadas') !== false ? 'active' : ''; ?>">⚖️ Pesar</a></li>
                
                <li><a href="/solufeed/admin/reportes/consumo.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'reportes') !== false ? 'active' : ''; ?>">📈 Reportes</a></li>
            </ul>
        </div>
    </nav>
    <?php endif; ?>
    
    <!-- Contenedor principal -->
    <main class="contenido-principal">
        <div class="container">