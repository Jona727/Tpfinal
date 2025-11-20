<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Solufeed</title>
    
    <!-- Chart.js para gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #333;
        }

        /* SIDEBAR */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 260px;
            height: 100vh;
            background: #1a3a1f;
            color: white;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar-header {
            padding: 25px 20px;
            background: #2c5530;
            text-align: center;
            border-bottom: 2px solid rgba(255,255,255,0.1);
        }

        .sidebar-logo {
            font-size: 3em;
            margin-bottom: 10px;
        }

        .sidebar-title {
            font-size: 1.5em;
            font-weight: bold;
            margin: 0;
        }

        .sidebar-subtitle {
            font-size: 0.85em;
            opacity: 0.8;
            margin-top: 5px;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .sidebar-menu ul {
            list-style: none;
        }

        .sidebar-menu li a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
        }

        .sidebar-menu li a:hover {
            background: rgba(255,255,255,0.1);
            padding-left: 25px;
        }

        .menu-icono {
            font-size: 1.3em;
            margin-right: 12px;
            width: 30px;
            text-align: center;
        }

        .menu-texto {
            font-size: 0.95em;
        }

        .menu-separador {
            height: 1px;
            background: rgba(255,255,255,0.1);
            margin: 15px 20px;
        }

        .menu-titulo {
            padding: 10px 20px;
            font-size: 0.75em;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.6;
            font-weight: bold;
        }

        .sidebar-footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            padding: 20px;
            background: rgba(0,0,0,0.2);
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #2c5530;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2em;
        }

        .user-details {
            flex: 1;
        }

        .user-name {
            font-weight: bold;
            font-size: 0.9em;
        }

        .user-role {
            font-size: 0.75em;
            opacity: 0.7;
        }

        /* MAIN CONTENT */
        .main-wrapper {
            margin-left: 260px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .topbar {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .breadcrumb {
            color: #666;
            font-size: 0.9em;
        }

        .breadcrumb a {
            color: #2c5530;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .content {
            flex: 1;
            padding: 30px;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }

            .sidebar-title,
            .sidebar-subtitle,
            .menu-texto,
            .menu-titulo,
            .user-details {
                display: none;
            }

            .sidebar-menu li a {
                justify-content: center;
                padding: 15px 10px;
            }

            .sidebar-menu li a:hover {
                padding-left: 10px;
            }

            .menu-icono {
                margin-right: 0;
            }

            .main-wrapper {
                margin-left: 70px;
            }

            .sidebar-footer {
                padding: 10px;
            }

            .user-avatar {
                width: 35px;
                height: 35px;
                font-size: 1em;
            }
        }
    </style>
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
                <li>
                    <a href="/solufeed/admin/dashboard.php">
                        <span class="menu-icono">🏠</span>
                        <span class="menu-texto">Dashboard</span>
                    </a>
                </li>

                <li class="menu-separador"></li>
                <li class="menu-titulo">Configuración</li>

                <li>
                    <a href="/solufeed/admin/insumos/listar.php">
                        <span class="menu-icono">🌾</span>
                        <span class="menu-texto">Insumos</span>
                    </a>
                </li>

                <li>
                    <a href="/solufeed/admin/dietas/listar.php">
                        <span class="menu-icono">📋</span>
                        <span class="menu-texto">Dietas</span>
                    </a>
                </li>

                <li>
                    <a href="/solufeed/admin/lotes/listar.php">
                        <span class="menu-icono">🐮</span>
                        <span class="menu-texto">Lotes</span>
                    </a>
                </li>

                <li class="menu-separador"></li>
                <li class="menu-titulo">Gestión</li>

                <li>
                    <a href="/solufeed/admin/reportes/consumo.php">
                        <span class="menu-icono">📈</span>
                        <span class="menu-texto">Reportes</span>
                    </a>
                </li>
                
                <li class="menu-separador"></li>
                <li class="menu-titulo">Usuario de Campo</li>
                
                <li>
                    <a href="/solufeed/admin/campo/index.php">
                        <span class="menu-icono">👷</span>
                        <span class="menu-texto">Hub de Campo</span>
                    </a>
                </li>
            </ul>
        </nav>

        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">👤</div>
                <div class="user-details">
                    <div class="user-name">Administrador</div>
                    <div class="user-role">Admin</div>
                </div>
            </div>
        </div>
    </aside>

    <!-- MAIN WRAPPER -->
    <div class="main-wrapper">
        <header class="topbar">
            <div class="breadcrumb">
                <?php 
                if (isset($page_title)) {
                    echo '<a href="/solufeed/admin/dashboard.php">Inicio</a> / ' . htmlspecialchars($page_title);
                }
                ?>
            </div>
            <div>
                <?php echo date('d/m/Y'); ?>
            </div>
        </header>

        <main class="content">