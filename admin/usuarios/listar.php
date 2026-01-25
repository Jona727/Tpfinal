<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Solo ADMIN puede gestionar usuarios
verificarAdmin();

$db = getConnection();

// Filtros
$filtro_tipo = $_GET['tipo'] ?? '';
$filtro_estado = $_GET['estado'] ?? '';
$busqueda = $_GET['busqueda'] ?? '';

// Construir query
$sql = "SELECT id_usuario, nombre, email, tipo, activo, fecha_creacion 
        FROM usuario 
        WHERE 1=1";
$params = [];

if ($filtro_tipo) {
    $sql .= " AND tipo = ?";
    $params[] = $filtro_tipo;
}

if ($filtro_estado === 'activo') {
    $sql .= " AND activo = 1";
} elseif ($filtro_estado === 'inactivo') {
    $sql .= " AND activo = 0";
}

if ($busqueda) {
    $sql .= " AND (nombre LIKE ? OR email LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
}

$sql .= " ORDER BY fecha_creacion DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$usuarios = $stmt->fetchAll();

// Estadísticas
$stmt = $db->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN tipo = 'ADMIN' THEN 1 ELSE 0 END) as total_admin,
    SUM(CASE WHEN tipo = 'CAMPO' THEN 1 ELSE 0 END) as total_campo,
    SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) as total_activos
    FROM usuario");
$stats = $stmt->fetch();

// Mensajes de sesión
$mensaje_exito = $_SESSION['mensaje_exito'] ?? '';
$mensaje_error = $_SESSION['mensaje_error'] ?? '';
unset($_SESSION['mensaje_exito'], $_SESSION['mensaje_error']);

// AJAX HANDLER: Retorna solo la tabla o el estado vacío
if (isset($_GET['ajax'])) {
    if (count($usuarios) > 0) {
        ?>
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Tipo</th>
                    <th>Estado</th>
                    <th>Fecha Creación</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($usuario['nombre']); ?></strong></td>
                        <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo strtolower($usuario['tipo']); ?>">
                                <?php echo $usuario['tipo'] === 'ADMIN' ? '👔 Admin' : '🧑‍🌾 Campo'; ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $usuario['activo'] ? 'activo' : 'inactivo'; ?>">
                                <?php echo $usuario['activo'] ? '✓ Activo' : '✕ Inactivo'; ?>
                            </span>
                        </td>
                        <td><?php echo formatearFecha($usuario['fecha_creacion']); ?></td>
                        <td>
                            <div class="actions">
                                <a href="editar.php?id=<?php echo $usuario['id_usuario']; ?>" 
                                   class="btn-icon" title="Editar">✏️</a>
                                <a href="toggle_estado.php?id=<?php echo $usuario['id_usuario']; ?>" 
                                   class="btn-icon" 
                                   title="<?php echo $usuario['activo'] ? 'Desactivar' : 'Activar'; ?>"
                                   onclick="return confirm('¿Confirmar cambio de estado?')">
                                    <?php echo $usuario['activo'] ? '🔒' : '🔓'; ?>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    } else {
        ?>
        <div class="empty-state">
            <div class="empty-state-icon">👤</div>
            <h3>No se encontraron usuarios</h3>
            <p>Intenta ajustar los filtros o crea un nuevo usuario</p>
        </div>
        <?php
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Solufeed</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <style>
        .usuarios-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-left: 4px solid var(--primary);
        }

        .stat-card.admin {
            border-left-color: var(--secondary);
        }

        .stat-card.campo {
            border-left-color: var(--accent);
        }

        .stat-card.activos {
            border-left-color: var(--success);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text);
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.85rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .filters-section {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-group label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text);
        }

        .filter-group input,
        .filter-group select {
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.95rem;
        }

        .btn-filter {
            padding: 0.75rem 1.5rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-filter:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .btn-clear {
            background: #6b7280;
        }

        .btn-clear:hover {
            background: #4b5563;
        }

        .usuarios-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
        }

        th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid #f1f5f9;
        }

        tbody tr {
            transition: background 0.2s ease;
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        .badge {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-admin {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-campo {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-activo {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-inactivo {
            background: #fee2e2;
            color: #991b1b;
        }

        .actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-icon {
            padding: 0.5rem;
            border: none;
            background: none;
            cursor: pointer;
            font-size: 1.2rem;
            transition: transform 0.2s ease;
            border-radius: 6px;
        }

        .btn-icon:hover {
            transform: scale(1.1);
            background: #f1f5f9;
        }

        .btn-nuevo {
            padding: 0.75rem 1.5rem;
            background: var(--success);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-nuevo:hover {
            background: #22c55e;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text-muted);
        }

        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            font-weight: 500;
        }

        .alert-error {
            background: #fee2e2;
            border-left: 4px solid var(--danger);
            color: #991b1b;
        }

        .alert-success {
            background: #d1fae5;
            border-left: 4px solid var(--success);
            color: #065f46;
        }

        @media (max-width: 768px) {
            .usuarios-table {
                overflow-x: auto;
            }

            table {
                min-width: 600px;
            }

            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>

    <div class="container">
        <div class="usuarios-header">
            <div>
                <h1>👥 Gestión de Usuarios</h1>
                <p style="color: var(--text-muted); margin-top: 0.5rem;">
                    Administra los usuarios del sistema
                </p>
            </div>
            <a href="crear.php" class="btn-nuevo">
                <span>➕</span>
                Nuevo Usuario
            </a>
        </div>

        <?php if ($mensaje_exito): ?>
            <div class="alert alert-success" style="margin-bottom: 2rem;">
                <strong>✓ Éxito:</strong> <?php echo htmlspecialchars($mensaje_exito); ?>
            </div>
        <?php endif; ?>

        <?php if ($mensaje_error): ?>
            <div class="alert alert-error" style="margin-bottom: 2rem;">
                <strong>❌ Error:</strong> <?php echo htmlspecialchars($mensaje_error); ?>
            </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Usuarios</div>
            </div>
            <div class="stat-card admin">
                <div class="stat-value"><?php echo $stats['total_admin']; ?></div>
                <div class="stat-label">Administradores</div>
            </div>
            <div class="stat-card campo">
                <div class="stat-value"><?php echo $stats['total_campo']; ?></div>
                <div class="stat-label">Personal de Campo</div>
            </div>
            <div class="stat-card activos">
                <div class="stat-value"><?php echo $stats['total_activos']; ?></div>
                <div class="stat-label">Usuarios Activos</div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filters-section">
            <form method="GET" class="filters-grid">
                <div class="filter-group">
                    <label>Buscar</label>
                    <input type="text" name="busqueda" placeholder="Nombre o email..." 
                           value="<?php echo htmlspecialchars($busqueda); ?>">
                </div>
                <div class="filter-group">
                    <label>Tipo</label>
                    <select name="tipo">
                        <option value="">Todos</option>
                        <option value="ADMIN" <?php echo $filtro_tipo === 'ADMIN' ? 'selected' : ''; ?>>Administrador</option>
                        <option value="CAMPO" <?php echo $filtro_tipo === 'CAMPO' ? 'selected' : ''; ?>>Campo</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Estado</label>
                    <select name="estado">
                        <option value="">Todos</option>
                        <option value="activo" <?php echo $filtro_estado === 'activo' ? 'selected' : ''; ?>>Activos</option>
                        <option value="inactivo" <?php echo $filtro_estado === 'inactivo' ? 'selected' : ''; ?>>Inactivos</option>
                    </select>
                </div>
                <div class="filter-group" style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="btn-filter">🔍 Filtrar</button>
                    <a href="listar.php" class="btn-filter btn-clear" style="text-decoration: none; text-align: center;">🔄 Limpiar</a>
                </div>
            </form>
        </div>

        <!-- Tabla de Usuarios -->
        <div class="usuarios-table">
            <?php if (count($usuarios) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th>Fecha Creación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($usuario['nombre']); ?></strong></td>
                                <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo strtolower($usuario['tipo']); ?>">
                                        <?php echo $usuario['tipo'] === 'ADMIN' ? '👔 Admin' : '🧑‍🌾 Campo'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $usuario['activo'] ? 'activo' : 'inactivo'; ?>">
                                        <?php echo $usuario['activo'] ? '✓ Activo' : '✕ Inactivo'; ?>
                                    </span>
                                </td>
                                <td><?php echo formatearFecha($usuario['fecha_creacion']); ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="editar.php?id=<?php echo $usuario['id_usuario']; ?>" 
                                           class="btn-icon" title="Editar">✏️</a>
                                        
                                        <?php if ($usuario['tipo'] === 'CAMPO'): ?>
                                            <a href="asignar_lotes.php?id=<?php echo $usuario['id_usuario']; ?>" 
                                               class="btn-icon" title="Asignar Lotes" style="background: #e0f2fe; color: #0284c7;">
                                                🐮
                                            </a>
                                        <?php endif; ?>

                                        <a href="toggle_estado.php?id=<?php echo $usuario['id_usuario']; ?>" 
                                           class="btn-icon" 
                                           title="<?php echo $usuario['activo'] ? 'Desactivar' : 'Activar'; ?>"
                                           onclick="return confirm('¿Confirmar cambio de estado?')">
                                            <?php echo $usuario['activo'] ? '🔒' : '🔓'; ?>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">👤</div>
                    <h3>No se encontraron usuarios</h3>
                    <p>Intenta ajustar los filtros o crea un nuevo usuario</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.querySelector('input[name="busqueda"]');
            const typeSelect = document.querySelector('select[name="tipo"]');
            const statusSelect = document.querySelector('select[name="estado"]');
            const tableContainer = document.querySelector('.usuarios-table');
            const form = document.querySelector('.filters-section form');

            // Función de debounce para evitar muchas peticiones
            function debounce(func, wait) {
                let timeout;
                return function executedFunction(...args) {
                    const later = () => {
                        clearTimeout(timeout);
                        func(...args);
                    };
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);
                };
            }

            // Función principal de búsqueda
            async function performSearch() {
                const params = new URLSearchParams({
                    ajax: '1',
                    busqueda: searchInput.value,
                    tipo: typeSelect.value,
                    estado: statusSelect.value
                });

                // Efecto visual de carga
                tableContainer.style.opacity = '0.5';
                tableContainer.style.transition = 'opacity 0.2s';

                try {
                    const response = await fetch(`listar.php?${params.toString()}`);
                    if (!response.ok) throw new Error('Error en la petición');
                    
                    const html = await response.text();
                    tableContainer.innerHTML = html;
                    
                    // Actualizar URL sin recargar (opcional, para que al refrescar se mantenga)
                    const urlParams = new URLSearchParams(params);
                    urlParams.delete('ajax'); // No queremos ajax=1 en la URL visible
                    window.history.replaceState({}, '', `${window.location.pathname}?${urlParams.toString()}`);
                    
                } catch (error) {
                    console.error('Error:', error);
                    // Si falla, no hacemos nada crítico, solo log
                } finally {
                    tableContainer.style.opacity = '1';
                }
            }

            // Aplicar debounce a la búsqueda por texto (300ms)
            const debouncedSearch = debounce(() => {
                performSearch();
            }, 300);

            // Listeners
            if (searchInput) {
                searchInput.addEventListener('input', debouncedSearch);
                // Prevenir envío de formulario tradicional con Enter
                searchInput.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        debouncedSearch();
                    }
                });
            }

            if (typeSelect) {
                typeSelect.addEventListener('change', performSearch);
            }

            if (statusSelect) {
                statusSelect.addEventListener('change', performSearch);
            }

            // Interceptar el botón de "Filtrar" para que use AJAX también
            if (form) {
                form.addEventListener('submit', (e) => {
                    e.preventDefault();
                    performSearch();
                });
            }
            
            // Botón Limpiar
            const cleanBtn = document.querySelector('.btn-clear');
            if (cleanBtn) {
                cleanBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    searchInput.value = '';
                    typeSelect.value = '';
                    statusSelect.value = '';
                    performSearch();
                });
            }
        });
    </script>
</body>
</html>
