<?php
/**
 * SOLUFEED - Crear Nueva Dieta
 */

require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Simular sesión
$_SESSION['usuario_id'] = 1;
$_SESSION['nombre'] = 'Administrador';
$_SESSION['tipo'] = 'ADMIN';

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

<h1 class="tarjeta-titulo">📋 Crear Nueva Dieta</h1>

<div class="tarjeta">
    
    <?php if (isset($exito)): ?>
        <div class="mensaje mensaje-exito"><?php echo $exito; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($errores)): ?>
        <div class="mensaje mensaje-error">
            <strong>Se encontraron los siguientes errores:</strong>
            <ul style="margin: 0.5rem 0 0 1.5rem;">
                <?php foreach ($errores as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form method="POST" class="formulario" id="formDieta">
        
        <!-- Nombre de la dieta -->
        <div class="form-grupo">
            <label for="nombre">Nombre de la Dieta *</label>
            <input 
                type="text" 
                id="nombre" 
                name="nombre" 
                required 
                placeholder="Ej: Engorde Intensivo, Crecimiento, Recría..."
                value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>"
            >
            <small>El nombre debe ser descriptivo y único.</small>
        </div>
        
        <!-- Descripción -->
        <div class="form-grupo">
            <label for="descripcion">Descripción (opcional)</label>
            <textarea 
                id="descripcion" 
                name="descripcion" 
                placeholder="Ej: Dieta alta en energía para terminación de novillos pesados"
            ><?php echo isset($_POST['descripcion']) ? htmlspecialchars($_POST['descripcion']) : ''; ?></textarea>
            <small>Describí el objetivo o características de esta dieta.</small>
        </div>
        
        <!-- Estado activo -->
        <div class="form-grupo">
            <label>
                <input 
                    type="checkbox" 
                    name="activo" 
                    value="1" 
                    <?php echo (!isset($_POST['activo']) || $_POST['activo']) ? 'checked' : ''; ?>
                >
                Dieta activa
            </label>
            <small>Las dietas inactivas no se pueden asignar a lotes nuevos.</small>
        </div>
        
        <hr style="margin: 2rem 0; border: none; border-top: 2px solid #e9ecef;">
        
        <!-- Selección de insumos -->
        <h3 style="color: #2c5530; margin-bottom: 1rem;">🌾 Composición de la Dieta</h3>
        
        <div class="mensaje mensaje-info" style="margin-bottom: 1.5rem;">
            ℹ️ Seleccioná los insumos y definí el porcentaje teórico de cada uno. 
            <strong>Los porcentajes deben sumar exactamente 100%.</strong>
        </div>
        
        <?php if (mysqli_num_rows($insumos_disponibles) > 0): ?>
            
            <div id="insumosContainer">
                <?php 
                $insumos_array = [];
                while ($insumo = mysqli_fetch_assoc($insumos_disponibles)) {
                    $insumos_array[] = $insumo;
                }
                ?>
                
                <table style="width: 100%;">
                    <thead>
                        <tr style="background: #f8f9fa;">
                            <th style="width: 50px; text-align: center;">Usar</th>
                            <th>Insumo</th>
                            <th>Tipo</th>
                            <th style="width: 100px;">% MS</th>
                            <th style="width: 150px;">% en la Dieta</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($insumos_array as $insumo): ?>
                            <?php 
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
                                        <?php echo $checked; ?>
                                    >
                                </td>
                                <td><strong><?php echo htmlspecialchars($insumo['nombre']); ?></strong></td>
                                <td><?php echo htmlspecialchars($insumo['tipo']); ?></td>
                                <td><?php echo formatearNumero($insumo['porcentaje_ms'], 2); ?>%</td>
                                <td>
                                    <input 
                                        type="number" 
                                        name="porcentajes[<?php echo $insumo['id_insumo']; ?>]"
                                        id="porcentaje_<?php echo $insumo['id_insumo']; ?>"
                                        step="0.01" 
                                        min="0" 
                                        max="100"
                                        placeholder="0.00"
                                        class="porcentaje-input"
                                        style="width: 100%; padding: 0.5rem;"
                                        value="<?php echo $valor_porcentaje; ?>"
                                        <?php echo $checked ? '' : 'disabled'; ?>
                                    >
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <!-- Fila de total -->
                        <tr style="background: #f8f9fa; font-weight: bold;">
                            <td colspan="4" style="text-align: right; padding: 1rem;">TOTAL:</td>
                            <td style="padding: 1rem;">
                                <div id="totalPorcentaje" style="font-size: 1.2rem; color: #2c5530;">0.00%</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Botones -->
            <div class="btn-grupo">
                <button type="submit" class="btn btn-primario">💾 Guardar Dieta</button>
                <a href="listar.php" class="btn btn-secundario">❌ Cancelar</a>
            </div>
            
        <?php else: ?>
            
            <div class="mensaje mensaje-error">
                ⚠️ No hay insumos activos disponibles. 
                <a href="../insumos/crear.php">Creá al menos un insumo</a> antes de crear dietas.
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