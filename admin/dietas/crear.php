<?php
/**
 * SOLUFEED - Crear Nueva Dieta
 */

require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Verificar sesión
// Verificar permisos de administrador
verificarAdmin();

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
    
    // Si no hay errores, crear la dieta
    if (empty($errores)) {
        
        // Insertar dieta
        $query_dieta = "
            INSERT INTO dieta (nombre, descripcion, activo, fecha_creacion)
            VALUES ('$nombre', '$descripcion', $activo, NOW())
        ";
        
        if (ejecutarConsulta($query_dieta)) {
            
            $id_dieta_nueva = mysqli_insert_id($conn);
            
            // Insertar detalle de insumos
            $inserciones_exitosas = true;
            
            foreach ($insumos_seleccionados as $id_insumo) {
                $id_insumo = (int) $id_insumo;
                $porcentaje = (float) $porcentajes[$id_insumo];
                
                $query_detalle = "
                    INSERT INTO dieta_detalle (id_dieta, id_insumo, porcentaje_teorico)
                    VALUES ($id_dieta_nueva, $id_insumo, $porcentaje)
                ";
                
                if (!ejecutarConsulta($query_detalle)) {
                    $inserciones_exitosas = false;
                    break;
                }
            }
            
            if ($inserciones_exitosas) {
                $exito = "✓ Dieta creada exitosamente.";
                header("refresh:2;url=ver.php?id=$id_dieta_nueva");
            } else {
                $errores[] = "Error al guardar los insumos de la dieta.";
            }
            
        } else {
            $errores[] = "Error al crear la dieta.";
        }
    }
}

include '../../includes/header.php';
?>

<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem;">
    <h1 style="font-weight: 800; color: var(--primary); margin: 0; letter-spacing: -1px;">📋 Crear Nueva Dieta</h1>
    <a href="listar.php" class="btn btn-secondary"><span>←</span> Cancelar</a>
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
                    value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>"
                    style="width: 100%;"
                >
                <small style="color: var(--text-muted); font-size: 0.8rem; display: block; margin-top: 0.25rem;">El nombre debe ser descriptivo y único.</small>
            </div>
            
            <!-- Estado activo -->
            <div class="form-grupo" style="display: flex; align-items: center; gap: 0.75rem; margin-top: 1.5rem;">
                <input 
                    type="checkbox" 
                    id="activo"
                    name="activo" 
                    value="1" 
                    style="width: 20px; height: 20px; cursor: pointer;"
                    <?php echo (!isset($_POST['activo']) || $_POST['activo']) ? 'checked' : ''; ?>
                >
                <label for="activo" style="font-weight: 700; color: var(--text-main); cursor: pointer;">Dieta activa</label>
                <small style="color: var(--text-muted); display: block; margin-left: auto;">Inactiva = No asignable</small>
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
            ><?php echo isset($_POST['descripcion']) ? htmlspecialchars($_POST['descripcion']) : ''; ?></textarea>
        </div>
        
        <div style="margin: 2.5rem 0; height: 1px; background: var(--border);"></div>
        
        <!-- Selección de insumos -->
        <h3 style="font-weight: 800; color: var(--primary); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
            <span>🌾</span> Composición de la Dieta
        </h3>
        
        <div style="background: var(--bg-main); padding: 1.25rem; border-radius: var(--radius); border-left: 4px solid var(--secondary); margin-bottom: 2rem;">
            <p style="margin: 0; font-size: 0.95rem; color: var(--text-main); font-weight: 500;">
                ℹ️ Seleccioná los insumos y definí el porcentaje teórico. 
                <strong>La suma debe ser exactamente 100%.</strong>
            </p>
        </div>
        
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
                            $checked = isset($_POST['insumos']) && in_array($insumo['id_insumo'], $_POST['insumos']) ? 'checked' : '';
                            $valor_porcentaje = isset($_POST['porcentajes'][$insumo['id_insumo']]) ? $_POST['porcentajes'][$insumo['id_insumo']] : '';
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
                                        <?php echo $checked; ?>
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
                                            <?php echo $checked ? '' : 'disabled'; ?>
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
                    <span>💾</span> Guardar Nueva Dieta
                </button>
                <a href="listar.php" class="btn btn-secondary btn-lg" style="flex: 0.3; padding: 1rem;">Cancelar</a>
            </div>
            
        <?php else: ?>
            
            <div class="card" style="background: #fee2e2; color: #991b1b; text-align: center; border: none; padding: 2rem;">
                <p style="font-weight: 700; margin-bottom: 1rem; font-size: 1.1rem;">⚠️ No hay insumos registrados</p>
                <p style="margin-bottom: 1.5rem; font-size: 0.95rem;">Antes de crear una dieta, necesitás tener insumos activos en el sistema.</p>
                <a href="../insumos/crear.php" class="btn btn-primary">Crear Primer Insumo</a>
            </div>
            
        <?php endif; ?>
        
    </form>
    
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