<?php
/**
 * SOLUFEED - Editar Dieta
 */

require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Verificar sesión
// Verificar permisos de administrador
verificarAdmin();

// Verificar que se recibió el ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: listar.php');
    exit();
}

$id_dieta = (int) $_GET['id'];

// Obtener datos de la dieta
$query_dieta = "SELECT * FROM dieta WHERE id_dieta = $id_dieta";
$resultado_dieta = ejecutarConsulta($query_dieta);

if (mysqli_num_rows($resultado_dieta) === 0) {
    header('Location: listar.php');
    exit();
}

$dieta = mysqli_fetch_assoc($resultado_dieta);

// Obtener insumos actuales de la dieta
$query_insumos_dieta = "
    SELECT id_insumo, porcentaje_teorico
    FROM dieta_detalle
    WHERE id_dieta = $id_dieta
";
$resultado_insumos_dieta = ejecutarConsulta($query_insumos_dieta);

$insumos_actuales = [];
while ($insumo_dieta = mysqli_fetch_assoc($resultado_insumos_dieta)) {
    $insumos_actuales[$insumo_dieta['id_insumo']] = $insumo_dieta['porcentaje_teorico'];
}

// Obtener todos los insumos activos
$query_insumos = "
    SELECT id_insumo, nombre, tipo, porcentaje_ms
    FROM insumo
    WHERE activo = 1
    ORDER BY nombre ASC
";
$insumos_disponibles = ejecutarConsulta($query_insumos);

// Procesar formulario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Recibir datos básicos de la dieta
    $nombre = limpiarDato($_POST['nombre']);
    $descripcion = limpiarDato($_POST['descripcion']);
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    // Recibir insumos seleccionados y sus porcentajes
    $insumos_seleccionados = isset($_POST['insumos']) ? $_POST['insumos'] : [];
    $porcentajes = isset($_POST['porcentajes']) ? $_POST['porcentajes'] : [];
    
    // Validaciones
    $errores = [];
    
    if (empty($nombre)) {
        $errores[] = "El nombre de la dieta es obligatorio.";
    }
    
    if (empty($insumos_seleccionados)) {
        $errores[] = "Debés seleccionar al menos un insumo.";
    }
    
    // Validar que los porcentajes sumen 100%
    $total_porcentaje = 0;
    foreach ($insumos_seleccionados as $id_insumo) {
        if (isset($porcentajes[$id_insumo]) && is_numeric($porcentajes[$id_insumo])) {
            $total_porcentaje += (float) $porcentajes[$id_insumo];
        }
    }
    
    if (abs($total_porcentaje - 100) > 0.01) {
        $errores[] = "Los porcentajes deben sumar exactamente 100%. Actualmente suman " . formatearNumero($total_porcentaje, 2) . "%.";
    }
    
    // Si no hay errores, actualizar la dieta
    if (empty($errores)) {
        
        // Actualizar dieta
        $query_update_dieta = "
            UPDATE dieta SET
                nombre = '$nombre',
                descripcion = '$descripcion',
                activo = $activo,
                fecha_actualizacion = NOW()
            WHERE id_dieta = $id_dieta
        ";
        
        if (ejecutarConsulta($query_update_dieta)) {
            
            // Eliminar todos los insumos actuales de la dieta
            $query_delete = "DELETE FROM dieta_detalle WHERE id_dieta = $id_dieta";
            ejecutarConsulta($query_delete);
            
            // Insertar los nuevos insumos
            $inserciones_exitosas = true;
            
            foreach ($insumos_seleccionados as $id_insumo) {
                $id_insumo = (int) $id_insumo;
                $porcentaje = (float) $porcentajes[$id_insumo];
                
                $query_detalle = "
                    INSERT INTO dieta_detalle (id_dieta, id_insumo, porcentaje_teorico)
                    VALUES ($id_dieta, $id_insumo, $porcentaje)
                ";
                
                if (!ejecutarConsulta($query_detalle)) {
                    $inserciones_exitosas = false;
                    break;
                }
            }
            
            if ($inserciones_exitosas) {
                $exito = "✓ Dieta actualizada exitosamente.";
                header("refresh:2;url=ver.php?id=$id_dieta");
                
                // Actualizar datos para mostrar en el formulario
                $dieta['nombre'] = $nombre;
                $dieta['descripcion'] = $descripcion;
                $dieta['activo'] = $activo;
                
                // Actualizar insumos actuales
                $insumos_actuales = [];
                foreach ($insumos_seleccionados as $id_insumo) {
                    $insumos_actuales[$id_insumo] = $porcentajes[$id_insumo];
                }
            } else {
                $errores[] = "Error al actualizar los insumos de la dieta.";
            }
            
        } else {
            $errores[] = "Error al actualizar la dieta.";
        }
    }
}

include '../../includes/header.php';
?>

<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem;">
    <h1 style="font-weight: 800; color: var(--primary); margin: 0; letter-spacing: -1px;">✏️ Editar Dieta</h1>
    <a href="ver.php?id=<?php echo $id_dieta; ?>" class="btn btn-secondary"><span>←</span> Cancelar</a>
</div>

<div class="card">
    
    <?php if (isset($exito)): ?>
        <div class="card" style="background: #dcfce7; border-left: 5px solid var(--success); color: #166534; padding: 1rem; margin-bottom: 1.5rem;">
            <?php echo $exito; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($errores)): ?>
        <div class="card" style="background: #fee2e2; border-left: 5px solid var(--danger); color: #991b1b; padding: 1rem; margin-bottom: 1.5rem;">
            <strong style="display: block; margin-bottom: 0.5rem;">Se encontraron los siguientes errores:</strong>
            <ul style="margin: 0; padding-left: 1.5rem; font-size: 0.9rem;">
                <?php foreach ($errores as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <div style="background: var(--bg-main); padding: 1.25rem; border-radius: var(--radius); border-left: 4px solid var(--accent); margin-bottom: 2rem;">
        <p style="margin: 0; font-size: 0.95rem; color: var(--text-main); font-weight: 500;">
            ⚠️ <strong>Atención:</strong> Si esta dieta está asignada a lotes activos, los cambios afectarán 
            los cálculos de las nuevas alimentaciones registradas a partir de ahora.
        </p>
    </div>
    
    <form method="POST" class="formulario" id="formDieta">
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <!-- Nombre de la dieta -->
            <div class="form-grupo">
                <label for="nombre" style="font-weight: 700; color: var(--text-main); display: block; margin-bottom: 0.5rem;">Nombre de la Dieta *</label>
                <input 
                    type="text" 
                    id="nombre" 
                    name="nombre" 
                    required 
                    placeholder="Ej: Engorde Intensivo, Crecimiento, Recría..."
                    value="<?php echo htmlspecialchars($dieta['nombre']); ?>"
                    style="width: 100%;"
                >
            </div>
            
            <!-- Estado activo -->
            <div class="form-grupo" style="display: flex; align-items: center; gap: 0.75rem; margin-top: 1.5rem;">
                <input 
                    type="checkbox" 
                    id="activo"
                    name="activo" 
                    value="1" 
                    style="width: 20px; height: 20px; cursor: pointer;"
                    <?php echo $dieta['activo'] ? 'checked' : ''; ?>
                >
                <label for="activo" style="font-weight: 700; color: var(--text-main); cursor: pointer;">Dieta activa</label>
            </div>
        </div>
        
        <!-- Descripción -->
        <div class="form-grupo" style="margin-top: 1.5rem;">
            <label for="descripcion" style="font-weight: 700; color: var(--text-main); display: block; margin-bottom: 0.5rem;">Descripción (opcional)</label>
            <textarea 
                id="descripcion" 
                name="descripcion" 
                placeholder="Ej: Dieta alta en energía para terminación de novillos pesados"
                style="width: 100%; min-height: 80px;"
            ><?php echo htmlspecialchars($dieta['descripcion']); ?></textarea>
        </div>
        
        <div style="display: flex; justify-content: flex-end; margin-top: 1rem;">
            <small style="color: var(--text-muted); font-weight: 500;">
                📅 Creada: <?php echo date('d/m/Y', strtotime($dieta['fecha_creacion'])); ?>
                <?php if ($dieta['fecha_actualizacion']): ?>
                    | 🔄 Actualizada: <?php echo date('d/m/Y', strtotime($dieta['fecha_actualizacion'])); ?>
                <?php endif; ?>
            </small>
        </div>
        
        <div style="margin: 2.5rem 0; height: 1px; background: var(--border);"></div>
        
        <h3 style="font-weight: 800; color: var(--primary); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
            <span>🌾</span> Composición de la Dieta
        </h3>
        
        <?php if (mysqli_num_rows($insumos_disponibles) > 0): ?>
            
            <div class="table-container">
                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th style="width: 60px; text-align: center;">Usar</th>
                            <th>Insumo</th>
                            <th>Tipo</th>
                            <th style="width: 100px;">% MS</th>
                            <th style="width: 180px; text-align: center;">% en la Dieta</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        mysqli_data_seek($insumos_disponibles, 0);
                        while ($insumo = mysqli_fetch_assoc($insumos_disponibles)): 
                            $esta_seleccionado = isset($insumos_actuales[$insumo['id_insumo']]);
                            $valor_porcentaje = $esta_seleccionado ? $insumos_actuales[$insumo['id_insumo']] : '';
                        ?>
                            <tr>
                                <td style="text-align: center;">
                                    <input 
                                        type="checkbox" 
                                        name="insumos[]" 
                                        value="<?php echo $insumo['id_insumo']; ?>"
                                        class="insumo-checkbox"
                                        data-insumo-id="<?php echo $insumo['id_insumo']; ?>"
                                        style="width: 20px; height: 20px;"
                                        <?php echo $esta_seleccionado ? 'checked' : ''; ?>
                                    >
                                </td>
                                <td><strong style="color: var(--primary); font-size: 1.05rem;"><?php echo htmlspecialchars($insumo['nombre']); ?></strong></td>
                                <td>
                                    <span class="badge" style="background: var(--bg-main); color: var(--text-main);">
                                        <?php echo htmlspecialchars($insumo['tipo']); ?>
                                    </span>
                                </td>
                                <td style="font-weight: 600;"><?php echo formatearNumero($insumo['porcentaje_ms'], 2); ?>%</td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.5rem; justify-content: center;">
                                        <input 
                                            type="number" 
                                            name="porcentajes[<?php echo $insumo['id_insumo']; ?>]"
                                            id="porcentaje_<?php echo $insumo['id_insumo']; ?>"
                                            step="0.01" 
                                            min="0" 
                                            max="100"
                                            placeholder="0.00"
                                            class="porcentaje-input"
                                            style="width: 100px; text-align: right; padding: 0.5rem; font-weight: 800; border-radius: 8px;"
                                            value="<?php echo $valor_porcentaje; ?>"
                                            <?php echo $esta_seleccionado ? '' : 'disabled'; ?>
                                        >
                                        <span style="font-weight: 800; color: var(--text-muted);">%</span>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background: var(--bg-main); font-weight: 800; border-top: 2px solid var(--border);">
                            <td colspan="4" style="text-align: right; padding: 1.25rem;">TOTAL DE LA MEZCLA:</td>
                            <td style="padding: 1.25rem; text-align: center;">
                                <div id="totalPorcentaje" style="font-size: 1.5rem; line-height: 1;">0.00%</div>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 2.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border);">
                <button type="submit" class="btn btn-primary btn-lg" style="flex: 1; padding: 1rem;">
                    <span>💾</span> Guardar Cambios
                </button>
                <a href="ver.php?id=<?php echo $id_dieta; ?>" class="btn btn-secondary btn-lg" style="flex: 0.3; padding: 1rem;">Cancelar</a>
            </div>
            
        <?php else: ?>
            <div class="card" style="background: #fee2e2; color: #991b1b; text-align: center; border: none; padding: 2rem;">
                <p style="font-weight: 700; margin-bottom: 1rem; font-size: 1.1rem;">⚠️ No hay insumos registrados</p>
                <a href="../insumos/crear.php" class="btn btn-primary">Crear Primer Insumo</a>
            </div>
        <?php endif; ?>
    </form>
</div>

<!-- Verificar en cuántos lotes se está usando esta dieta -->
<div class="card">
    <h3 class="card-title"><span>📊</span> Lotes que usan esta Dieta</h3>
    
    <?php
    $query_lotes = "
        SELECT 
            t.nombre as lote_nombre,
            tda.fecha_desde,
            t.activo as lote_activo
        FROM tropa_dieta_asignada tda
        INNER JOIN tropa t ON tda.id_tropa = t.id_tropa
        WHERE tda.id_dieta = $id_dieta
        AND (tda.fecha_hasta IS NULL OR tda.fecha_hasta >= CURDATE())
        ORDER BY tda.fecha_desde DESC
    ";
    $lotes_asignados = ejecutarConsulta($query_lotes);
    ?>
    
    <?php if (mysqli_num_rows($lotes_asignados) > 0): ?>
        <div style="background: #fef3c7; color: #92400e; padding: 1rem; border-radius: var(--radius); margin-bottom: 1.5rem; font-weight: 500; font-size: 0.9rem;">
            ⚠️ <strong>Importante:</strong> Esta dieta está siendo utilizada en <?php echo mysqli_num_rows($lotes_asignados); ?> lote(s) activo(s). Los cambios afectarán los nuevos registros.
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Lote</th>
                        <th>Fecha Desde</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($lote = mysqli_fetch_assoc($lotes_asignados)): ?>
                        <tr>
                            <td><strong style="color: var(--primary);"><?php echo htmlspecialchars($lote['lote_nombre']); ?></strong></td>
                            <td><?php echo date('d/m/Y', strtotime($lote['fecha_desde'])); ?></td>
                            <td>
                                <?php if ($lote['lote_activo']): ?>
                                    <span class="badge" style="background: #dcfce7; color: #166534;">Activo</span>
                                <?php else: ?>
                                    <span class="badge" style="background: #f3f4f6; color: #6b7280;">Inactivo</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p style="text-align: center; color: var(--text-muted); padding: 1.5rem; font-weight: 500;">
            Esta dieta no está asignada a ningún lote actualmente.
        </p>
    <?php endif; ?>
</div>

<!-- JavaScript para calcular total automáticamente -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    const checkboxes = document.querySelectorAll('.insumo-checkbox');
    const inputs = document.querySelectorAll('.porcentaje-input');
    
    // Habilitar/deshabilitar inputs según checkbox
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const insumoId = this.dataset.insumoId;
            const input = document.getElementById('porcentaje_' + insumoId);
            
            if (this.checked) {
                input.disabled = false;
                input.focus();
            } else {
                input.disabled = true;
                input.value = '';
            }
            
            calcularTotal();
        });
    });
    
    // Recalcular al cambiar porcentajes
    inputs.forEach(input => {
        input.addEventListener('input', calcularTotal);
    });
    
    // Función para calcular el total
    function calcularTotal() {
        let total = 0;
        
        inputs.forEach(input => {
            if (!input.disabled && input.value) {
                total += parseFloat(input.value) || 0;
            }
        });
        
        const totalDiv = document.getElementById('totalPorcentaje');
        totalDiv.textContent = total.toFixed(2) + '%';
        
        // Cambiar color según si está cerca de 100
        if (Math.abs(total - 100) < 0.01) {
            totalDiv.style.color = '#28a745'; // Verde si es 100%
        } else if (total > 100) {
            totalDiv.style.color = '#dc3545'; // Rojo si pasa de 100%
        } else {
            totalDiv.style.color = '#2c5530'; // Color normal
        }
    }
    
    // Calcular total inicial
    calcularTotal();
    
});
</script>

<?php include '../../includes/footer.php'; ?>