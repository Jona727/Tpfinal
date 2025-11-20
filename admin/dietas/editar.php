<?php
/**
 * SOLUFEED - Editar Dieta
 */

require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Simular sesión
$_SESSION['usuario_id'] = 1;
$_SESSION['nombre'] = 'Administrador';
$_SESSION['tipo'] = 'ADMIN';

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

<h1 class="tarjeta-titulo">✏️ Editar Dieta</h1>

<div class="tarjeta">
    
    <?php if (isset($exito)): ?>
        <div class="mensaje mensaje-exito"><?php echo $exito; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($errores)): ?>
        <div class="mensaje mensaje-error">
            <strong>Se encontraron los siguientes errores:</strong>
            <ul class="mensaje-lista">
                <?php foreach ($errores as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="mensaje mensaje-info">
        ℹ️ <strong>Atención:</strong> Si esta dieta está asignada a lotes activos, los cambios afectarán 
        los cálculos de las nuevas alimentaciones registradas a partir de ahora.
    </div>
    
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
                value="<?php echo htmlspecialchars($dieta['nombre']); ?>"
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
            ><?php echo htmlspecialchars($dieta['descripcion']); ?></textarea>
            <small>Describí el objetivo o características de esta dieta.</small>
        </div>
        
        <!-- Estado activo -->
        <div class="form-grupo">
            <label>
                <input 
                    type="checkbox" 
                    name="activo" 
                    value="1" 
                    <?php echo $dieta['activo'] ? 'checked' : ''; ?>
                >
                Dieta activa
            </label>
            <small>Las dietas inactivas no se pueden asignar a lotes nuevos.</small>
        </div>
        
        <!-- Información adicional -->
        <div class="form-grupo">
            <small class="texto-secundario">
                <strong>Creado:</strong> <?php echo formatearFecha($dieta['fecha_creacion']); ?>
                <?php if ($dieta['fecha_actualizacion']): ?>
                    | <strong>Última actualización:</strong> <?php echo formatearFecha($dieta['fecha_actualizacion']); ?>
                <?php endif; ?>
            </small>
        </div>

        <hr class="separador-horizontal">

        <!-- Selección de insumos -->
        <h3 class="seccion-titulo-formulario">🌾 Composición de la Dieta</h3>

        <div class="mensaje mensaje-info">
            ℹ️ Seleccioná los insumos y definí el porcentaje teórico de cada uno. 
            <strong>Los porcentajes deben sumar exactamente 100%.</strong>
        </div>
        
        <?php if (mysqli_num_rows($insumos_disponibles) > 0): ?>
            
            <div id="insumosContainer">
                <?php 
                $insumos_array = [];
                mysqli_data_seek($insumos_disponibles, 0); // Resetear el puntero
                while ($insumo = mysqli_fetch_assoc($insumos_disponibles)) {
                    $insumos_array[] = $insumo;
                }
                ?>

                <table class="tabla-insumos">
                    <thead>
                        <tr>
                            <th class="checkbox-col">Usar</th>
                            <th>Insumo</th>
                            <th>Tipo</th>
                            <th class="ms-col">% MS</th>
                            <th class="porcentaje-col">% en la Dieta</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($insumos_array as $insumo): ?>
                            <?php
                            // Verificar si este insumo está en la dieta actual
                            $esta_seleccionado = isset($insumos_actuales[$insumo['id_insumo']]);
                            $valor_porcentaje = $esta_seleccionado ? $insumos_actuales[$insumo['id_insumo']] : '';
                            ?>
                            <tr>
                                <td class="checkbox-cell">
                                    <input 
                                        type="checkbox" 
                                        name="insumos[]" 
                                        value="<?php echo $insumo['id_insumo']; ?>"
                                        class="insumo-checkbox"
                                        data-insumo-id="<?php echo $insumo['id_insumo']; ?>"
                                        <?php echo $esta_seleccionado ? 'checked' : ''; ?>
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
                                        value="<?php echo $valor_porcentaje; ?>"
                                        <?php echo $esta_seleccionado ? '' : 'disabled'; ?>
                                    >
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <!-- Fila de total -->
                        <tr class="fila-total">
                            <td colspan="4" class="total-label">TOTAL:</td>
                            <td class="total-display">
                                <div id="totalPorcentaje">0.00%</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Botones -->
            <div class="btn-grupo">
                <button type="submit" class="btn btn-primario">💾 Guardar Cambios</button>
                <a href="ver.php?id=<?php echo $id_dieta; ?>" class="btn btn-secundario">❌ Cancelar</a>
            </div>
            
        <?php else: ?>
            
            <div class="mensaje mensaje-error">
                ⚠️ No hay insumos activos disponibles. 
                <a href="../insumos/crear.php">Creá al menos un insumo</a> antes de editar dietas.
            </div>
            
        <?php endif; ?>
        
    </form>
    
</div>

<!-- Verificar en cuántos lotes se está usando esta dieta -->
<div class="tarjeta">
    <h2 class="tarjeta-titulo">📊 Lotes que usan esta Dieta</h2>
    
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

        <div class="mensaje mensaje-info">
            ⚠️ <strong>Importante:</strong> Esta dieta está siendo utilizada en
            <?php echo mysqli_num_rows($lotes_asignados); ?> lote(s) activo(s).
            Los cambios afectarán las nuevas alimentaciones registradas.
        </div>

        <table class="mt-1">
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
                        <td><strong><?php echo htmlspecialchars($lote['lote_nombre']); ?></strong></td>
                        <td><?php echo formatearFecha($lote['fecha_desde']); ?></td>
                        <td>
                            <?php if ($lote['lote_activo']): ?>
                                <span class="estado estado-activo">Activo</span>
                            <?php else: ?>
                                <span class="estado estado-inactivo">Inactivo</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

    <?php else: ?>

        <p class="sin-datos">Esta dieta no está asignada a ningún lote actualmente.</p>

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