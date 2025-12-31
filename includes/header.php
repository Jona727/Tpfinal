<!DOCTYPE html> 
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Solufeed</title>
    <!-- Tipografía: Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    
    <!-- CSS Principal -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/main.css">
    
    <!-- PWA -->
    <link rel="manifest" href="<?php echo BASE_URL; ?>/manifest.json">
    <meta name="theme-color" content="#2c5530">
    <link rel="apple-touch-icon" href="https://cdn-icons-png.flaticon.com/512/2395/2395796.png">

    <!-- Chart.js para gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Service Worker Registration -->
    <script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('<?php echo BASE_URL; ?>/sw.js')
                .then(reg => console.log('✅ Service Worker registrado'))
                .catch(err => console.log('❌ Error al registrar SW:', err));
        });
    }
    </script>
</head>
<body>
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">🐮</div>
            <h1 class="sidebar-title">Solufeed</h1>
            <p class="sidebar-subtitle">Sistema de Gestión</p>
        </div>

        <nav class="sidebar-menu">
            <ul>
                <?php if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] === 'ADMIN'): ?>
                    <!-- MENÚ ADMINISTRADOR -->
                    <li>
                        <a href="<?php echo BASE_URL; ?>/admin/dashboard.php">
                            <span class="menu-icono">🏠</span>
                            <span class="menu-texto">Dashboard</span>
                        </a>
                    </li>

                    <li class="menu-separador"></li>
                    <li class="menu-titulo">Configuración</li>

                    <li>
                        <a href="<?php echo BASE_URL; ?>/admin/insumos/listar.php">
                            <span class="menu-icono">🌾</span>
                            <span class="menu-texto">Insumos</span>
                        </a>
                    </li>

                    <li>
                        <a href="<?php echo BASE_URL; ?>/admin/dietas/listar.php">
                            <span class="menu-icono">📋</span>
                            <span class="menu-texto">Dietas</span>
                        </a>
                    </li>

                    <li>
                        <a href="<?php echo BASE_URL; ?>/admin/lotes/listar.php">
                            <span class="menu-icono">🐮</span>
                            <span class="menu-texto">Lotes</span>
                        </a>
                    </li>

                    <li class="menu-separador"></li>
                    <li class="menu-titulo">Gestión</li>

                    <li>
                        <a href="<?php echo BASE_URL; ?>/admin/reportes/consumo.php">
                            <span class="menu-icono">📈</span>
                            <span class="menu-texto">Reportes</span>
                        </a>
                    </li>
                    
                <?php endif; ?>

                <!-- MENÚ ESPECÍFICO CAMPO -->
                <?php if (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'CAMPO'): ?>
                <li>
                    <a href="<?php echo BASE_URL; ?>/admin/campo/index.php">
                        <span class="menu-icono">👷</span>
                        <span class="menu-texto">Hub de Campo</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>

        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">👤</div>
                <div class="user-details">
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['nombre'] ?? 'Usuario'); ?></div>
                    <div class="user-role"><?php echo htmlspecialchars($_SESSION['tipo'] ?? 'Invitado'); ?></div>
                </div>
                <a href="<?php echo BASE_URL; ?>/admin/logout.php" title="Cerrar Sesión" style="color: #ff6b6b; text-decoration: none; font-size: 1.2em; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                    🚪
                </a>
            </div>
        </div>
    </aside>

    <!-- MAIN WRAPPER -->
    <div class="main-wrapper">
        <main class="content">
