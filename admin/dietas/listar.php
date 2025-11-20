<?php
/**
 * SOLUFEED - Listar Dietas
 */

require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Simular sesión
$_SESSION['usuario_id'] = 1;
$_SESSION['nombre'] = 'Administrador';
$_SESSION['tipo'] = 'ADMIN';

include '../../includes/header.php';

// Obtener todas las dietas
$query = "
    SELECT 
        d.id_dieta,
        d.nombre,
        d.descripcion,
        d.activo,
        d.fecha_creacion,
        COUNT(dd.id_insumo) as cantidad_insumos
    FROM dieta d
    LEFT JOIN dieta_detalle dd ON d.id_dieta = dd.id_dieta
    GROUP BY d.id_dieta
    ORDER BY d.activo DESC, d.nombre ASC
";

$resultado = ejecutarConsulta($query);
?>

<h1 class="tarjeta-titulo">📋 Gestión de Dietas</h1>

<div class="tarjeta">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <p>Administrá las dietas teóricas que se asignan a los lotes.</p>
        <a href="crear.php" class="btn btn-primario">➕ Crear Nueva Dieta</a>
    </div>
    
    <?php if (mysqli_num_rows($resultado) > 0): ?>
        
        <div class="tabla-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Insumos</th>
                        <th>Estado</th>
                        <th>Fecha Creación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($dieta = mysqli_fetch_assoc($resultado)): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($dieta['nombre']); ?></strong></td>
                            <td><?php echo htmlspecialchars($dieta['descripcion']); ?></td>
                            <td>
                                <span style="background: #e3f2fd; padding: 0.3rem 0.8rem; border-radius: 20px; font-weight: 600;">
                                    <?php echo $dieta['cantidad_insumos']; ?> insumo(s)
                                </span>
                            </td>
                            <td>
                                <?php if ($dieta['activo']): ?>
                                    <span class="estado estado-activo">Activa</span>
                                <?php else: ?>
                                    <span class="estado estado-inactivo">Inactiva</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo formatearFecha($dieta['fecha_creacion']); ?></td>
                            <td>
                                <div class="tabla-acciones">
                                    <a href="ver.php?id=<?php echo $dieta['id_dieta']; ?>" 
                                       class="btn btn-secundario btn-pequeno">👁️ Ver</a>
                                    <a href="editar.php?id=<?php echo $dieta['id_dieta']; ?>" 
                                       class="btn btn-secundario btn-pequeno">✏️ Editar</a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
    <?php else: ?>
        
        <div class="sin-datos">
            <p>No hay dietas registradas todavía.</p>
            <a href="crear.php" class="btn btn-primario">Crear Primera Dieta</a>
        </div>
        
    <?php endif; ?>
    
</div>

<?php include '../../includes/footer.php'; ?>