<?php
/**
 * SOLUFEED - Listar Insumos
 */

require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Simular sesión
$_SESSION['usuario_id'] = 1;
$_SESSION['nombre'] = 'Administrador';
$_SESSION['tipo'] = 'ADMIN';

include '../../includes/header.php';

// Obtener todos los insumos
$query = "
    SELECT 
        id_insumo,
        nombre,
        tipo,
        costo_kg,
        porcentaje_ms,
        activo,
        fecha_creacion
    FROM insumo
    ORDER BY activo DESC, nombre ASC
";

$resultado = ejecutarConsulta($query);
?>

<h1 class="tarjeta-titulo">🌾 Gestión de Insumos</h1>

<div class="tarjeta">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <p>Administrá los insumos que se utilizan en las dietas del feedlot.</p>
        <a href="crear.php" class="btn btn-primario">➕ Crear Nuevo Insumo</a>
    </div>
    
    <?php if (mysqli_num_rows($resultado) > 0): ?>
        
        <div class="tabla-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>% Materia Seca</th>
                        <th>Costo/Kg</th>
                        <th>Estado</th>
                        <th>Fecha Creación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($insumo = mysqli_fetch_assoc($resultado)): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($insumo['nombre']); ?></strong></td>
                            <td><?php echo htmlspecialchars($insumo['tipo']); ?></td>
                            <td>
                                <span style="background: #e3f2fd; padding: 0.3rem 0.8rem; border-radius: 20px; font-weight: 600;">
                                    <?php echo formatearNumero($insumo['porcentaje_ms'], 2); ?>%
                                </span>
                            </td>
                            <td>$<?php echo formatearNumero($insumo['costo_kg'], 2); ?></td>
                            <td>
                                <?php if ($insumo['activo']): ?>
                                    <span class="estado estado-activo">Activo</span>
                                <?php else: ?>
                                    <span class="estado estado-inactivo">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo formatearFecha($insumo['fecha_creacion']); ?></td>
                            <td>
                                <div class="tabla-acciones">
                                    <a href="editar.php?id=<?php echo $insumo['id_insumo']; ?>" 
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
            <p>No hay insumos registrados todavía.</p>
            <a href="crear.php" class="btn btn-primario">Crear Primer Insumo</a>
        </div>
        
    <?php endif; ?>
    
</div>

<?php include '../../includes/footer.php'; ?>