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
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    
    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">

    <meta name="theme-color" content="#2c5530">
    <link rel="apple-touch-icon" href="https://cdn-icons-png.flaticon.com/512/2395/2395796.png">

    <!-- Chart.js para gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Registro del Service Worker (PWA) -->
    <script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js')
                .then(registration => {
                    console.log('✅ [PWA] Service Worker registrado con éxito. Scope:', registration.scope);
                })
                .catch(err => {
                    console.error('❌ [PWA] Error al registrar el Service Worker:', err);
                });
        });
    }
    </script>

    <script>
    function showToast(message, type = 'success') {
        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <span class="toast-icon">${icons[type] || icons.info}</span>
            <span class="toast-message">${message}</span>
        `;

        container.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 10);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 400);
        }, 4000);
    }
    </script>
    <script src="<?php echo BASE_URL; ?>/assets/js/offline_manager.js"></script>

    <!-- CORE PWA v3.2 - SOLUCIÓN TANQUE -->
    <script>
    const CACHE_NAME = 'solufeed-cache-v3.2';
    const CRITICAL_PAGES = [
        '<?php echo BASE_URL; ?>/admin/campo/index.php',
        '<?php echo BASE_URL; ?>/admin/alimentaciones/registrar.php',
        '<?php echo BASE_URL; ?>/admin/pesadas/registrar.php',
        '<?php echo BASE_URL; ?>/admin/campo/consultar_lotes.php',
        '<?php echo BASE_URL; ?>/admin/campo/historial_dia.php',
        '<?php echo BASE_URL; ?>/offline.html',
        '<?php echo BASE_URL; ?>/manifest.json',
        '<?php echo BASE_URL; ?>/assets/css/main.css',
        '<?php echo BASE_URL; ?>/assets/js/offline_manager.js',
        '<?php echo BASE_URL; ?>/assets/js/scripts.js',
        'https://cdn.jsdelivr.net/npm/chart.js',
        'https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap'
    ];

    // 1. Registro del SW
    if ('serviceWorker' in navigator) {
        const swPath = '<?php echo BASE_URL === "" ? "/sw.js" : BASE_URL . "/sw.js"; ?>';
        navigator.serviceWorker.register(swPath).then(reg => {
            console.log('✅ [PWA] Sistema v3.2 blindado activo');
        });
    }

    // 2. FORZADO DE CACHE (Solo si hay internet y el usuario está logueado)
    async function forceOfflineCache() {
        if (!navigator.onLine) return;
        
        try {
            const cache = await caches.open(CACHE_NAME);
            
            // Generar lista dinámica de páginas incluyendo cada lote
            let pagesToCache = [...CRITICAL_PAGES];
            
            <?php 
            // Obtener lotes activos para pre-cargar sus vistas específicas
            if (isset($_SESSION['usuario_id'])) {
                $q_lotes = ejecutarConsulta("SELECT id_tropa FROM tropa WHERE activo = 1");
                $lotes_ids = [];
                while($l = mysqli_fetch_assoc($q_lotes)) $lotes_ids[] = $l['id_tropa'];
                echo "const loteIds = " . json_encode($lotes_ids) . ";";
            } else {
                echo "const loteIds = [];";
            }
            ?>

            loteIds.forEach(id => {
                pagesToCache.push('<?php echo BASE_URL; ?>/admin/alimentaciones/registrar.php?lote=' + id);
                pagesToCache.push('<?php echo BASE_URL; ?>/admin/pesadas/registrar.php?lote=' + id);
            });

            console.log('📡 [PWA] Iniciando blindaje inteligente (' + pagesToCache.length + ' recursos)...');
            
            // Descargamos de forma secuencial para no saturar
            for (const url of pagesToCache) {
                try {
                    const response = await fetch(url);
                    if (response.ok) {
                        await cache.put(url, response);
                    }
                } catch (e) {}
            }
            console.log('🚀 [PWA] Blindaje total completado.');
        } catch (err) {
            console.error('❌ [PWA] Error en blindaje:', err);
        }
    }

    document.addEventListener('DOMContentLoaded', async () => {
        <?php if (isset($_SESSION['usuario_id'])): ?>
            // A. Blindaje de Páginas
            forceOfflineCache();

            // B. Persistencia de Sesión (para Login Offline)
            if (window.OfflineManager) {
                const userData = {
                    id: <?php echo $_SESSION['usuario_id']; ?>,
                    nombre: '<?php echo addslashes($_SESSION['nombre']); ?>',
                    tipo: '<?php echo $_SESSION['tipo']; ?>',
                    email: '<?php echo $_SESSION['email']; ?>'
                };
                try {
                    await OfflineManager.initDB();
                    const transaction = OfflineManager.db.transaction(['offline_queue'], 'readwrite');
                    const store = transaction.objectStore('offline_queue');
                    store.put({ id: 'current_session', data: userData, timestamp: new Date().toISOString() });
                    console.log('🔐 [PWA] Sesión blindada localmente');
                } catch (e) {
                    console.error('❌ [PWA] Error en sesión:', e);
                }
            }
        <?php endif; ?>
    });
    </script>
</head>
<body>
    <!-- Mobile Menu Overlay -->
    <div class="menu-overlay" id="menuOverlay"></div>

    <!-- Mobile Top Bar (Visible only on mobile) -->
    <div class="mobile-top-bar">
        <button id="menuToggle" class="menu-toggle">
            <span class="material-icon">☰</span>
        </button>
        <div class="mobile-brand">Solufeed 🐮</div>
    </div>

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
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
                        <a href="<?php echo BASE_URL; ?>/admin/establecimientos/listar.php">
                            <span class="menu-icono">🏭</span>
                            <span class="menu-texto">Establecimientos</span>
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
                        <a href="<?php echo BASE_URL; ?>/admin/usuarios/listar.php">
                            <span class="menu-icono">👥</span>
                            <span class="menu-texto">Usuarios</span>
                        </a>
                    </li>

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
                <div class="user-details-group">
                    <div class="user-avatar">👤</div>
                    <div class="user-details">
                        <div class="user-name"><?php echo htmlspecialchars($_SESSION['nombre'] ?? 'Usuario'); ?></div>
                        <div class="user-role"><?php echo htmlspecialchars($_SESSION['tipo'] ?? 'Invitado'); ?></div>
                    </div>
                </div>
                <a href="<?php echo BASE_URL; ?>/admin/logout.php" class="btn-logout">
                    <span class="logout-icon">⏻</span> Cerrar Sesión
                </a>
            </div>
        </div>
    </aside>

    <!-- MAIN WRAPPER -->
    <div class="main-wrapper">
        <header class="top-bar">
            <div class="breadcrumb">
                <?php
                if (isset($page_title)) {
                    echo '<a href="' . BASE_URL . '/admin/dashboard.php">Inicio</a> / ' . htmlspecialchars($page_title);
                }
                ?>
            </div>
            <div class="top-bar-actions">
                <span><?php echo date('d/m/Y'); ?></span>
            </div>
        </header>

        <main class="content">
            <div class="container">
