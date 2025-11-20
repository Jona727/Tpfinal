<?php
/**
 * SOLUFEED - Registrar Alimentación
 * Módulo principal del sistema - Registra consumo real y calcula MS automáticamente
 */

require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Simular sesión
$_SESSION['usuario_id'] = 1;
$_SESSION['nombre'] = 'Administrador';
$_SESSION['tipo'] = 'ADMIN';

// Verificar si viene un lote pre-seleccionado
$lote_preseleccionado = isset($_GET['lote']) ? (int) $_GET['lote'] : null;

// Obtener lotes activos
$query_lotes = "
    SELECT 
        t.id_tropa,
        t.nombre,
        c.nombre as campo_nombre
    FROM tropa t
    INNER JOIN campo c ON t.id_campo = c.id_campo
    WHERE t.activo = 1
    ORDER BY t.nombre ASC
";
$lotes_disponibles = ejecutarConsulta($query_lotes);

// Variables para el formulario
$lote_seleccionado = null;
$dieta_vigente = null;
$insumos_dieta = [];
$animales_presentes = 0;

// Si se seleccionó un lote (por GET o POST)
$id_lote_actual = $lote_preseleccionado;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_tropa'])) {
    $id_lote_actual = (int) $_POST['id_tropa'];
}

if ($id_lote_actual) {
    
    // Obtener datos del lote
    $query_lote = "SELECT * FROM tropa WHERE id_tropa = $id_lote_actual";
    $resultado_lote = ejecutarConsulta($query_lote);
    
    if (mysqli_num_rows($resultado_lote) > 0) {
        $lote_seleccionado = mysqli_fetch_assoc($resultado_lote);
        $animales_presentes = obtenerAnimalesPresentes($id_lote_actual);
        
        // Obtener dieta vigente
        $dieta_vigente = obtenerDietaVigente($id_lote_actual);
        
        // Si tiene dieta, obtener sus insumos
        if ($dieta_vigente) {
            $query_insumos = "
                SELECT 
                    i.id_insumo,
                    i.nombre,
                    i.tipo,
                    i.porcentaje_ms,
                    dd.porcentaje_teorico
                FROM dieta_detalle dd
                INNER JOIN insumo i ON dd.id_insumo = i.id_insumo
                WHERE dd.id_dieta = " . $dieta_vigente['id_dieta'] . "
                ORDER BY dd.porcentaje_teorico DESC
            ";
            $resultado_insumos = ejecutarConsulta($query_insumos);
            
            while ($insumo = mysqli_fetch_assoc($resultado_insumos)) {
                $insumos_dieta[] = $insumo;
            }
        }
    }
}

// Procesar formulario si se envió el registro completo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_alimentacion'])) {
    
    // Recibir datos del formulario
    $id_tropa = (int) $_POST['id_tropa'];
    $fecha = limpiarDato($_POST['fecha']);
    $hora = limpiarDato($_POST['hora']);
    $sobrante_nivel = limpiarDato($_POST['sobrante_nivel']);
    $kg_totales = (float) $_POST['kg_totales'];
    
    // Recibir kg reales de cada insumo
    $kg_reales = isset($_POST['kg_real']) ? $_POST['kg_real'] : [];
    
    // Validaciones
    $errores = [];
    
    if ($id_tropa <= 0) {
        $errores[] = "Debés seleccionar un lote.";
    }
    
    if (empty($fecha) || empty($hora)) {
        $errores[] = "La fecha y hora son obligatorias.";
    }
    
    if ($kg_totales <= 0) {
        $errores[] = "Los kg totales deben ser mayores a 0.";
    }
    
    // Verificar que se hayan ingresado kg para al menos un insumo
    $hay_insumos = false;
    $suma_kg_reales = 0;
    foreach ($kg_reales as $kg) {
        if ($kg > 0) {
            $hay_insumos = true;
            $suma_kg_reales += $kg;
        }
    }
    
    if (!$hay_insumos) {
        $errores[] = "Debés ingresar kg reales para al menos un insumo.";
    }
    
    // Verificar que la suma de kg reales coincida aproximadamente con kg totales
    if (abs($suma_kg_reales - $kg_totales) > 1) { // Tolerancia de 1 kg
        $errores[] = "La suma de kg por insumo (" . formatearNumero($suma_kg_reales, 2) . " kg) no coincide con los kg totales (" . formatearNumero($kg_totales, 2) . " kg).";
    }
    
    // Obtener animales presentes
    $animales = obtenerAnimalesPresentes($id_tropa);
    
    // Calcular número de alimentación del día
    $query_num_alim = "
        SELECT IFNULL(MAX(numero_alimentacion_dia), 0) + 1 as siguiente
        FROM consumo_lote
        WHERE id_tropa = $id_tropa AND fecha = '$fecha'
    ";
    $resultado_num = ejecutarConsulta($query_num_alim);
    $numero_alimentacion = mysqli_fetch_assoc($resultado_num)['siguiente'];
    
    // Si no hay errores, guardar
    if (empty($errores)) {
        
        // Insertar cabezal de consumo
        $query_consumo = "
            INSERT INTO consumo_lote 
            (id_tropa, id_usuario, fecha, hora, numero_alimentacion_dia, sobrante_nivel, kg_totales_tirados, animales_presentes, origen_registro, fecha_creacion)
            VALUES 
            ($id_tropa, {$_SESSION['usuario_id']}, '$fecha', '$hora', $numero_alimentacion, '$sobrante_nivel', $kg_totales, $animales, 'ONLINE', NOW())
        ";
        
        if (ejecutarConsulta($query_consumo)) {
            
            $id_consumo = mysqli_insert_id($conn);
            
            // Obtener dieta vigente para calcular porcentajes
            $dieta = obtenerDietaVigente($id_tropa, $fecha);
            
            if ($dieta) {
                
                // Obtener insumos de la dieta
                $query_insumos_dieta = "
                    SELECT 
                        i.id_insumo,
                        i.porcentaje_ms,
                        dd.porcentaje_teorico
                    FROM dieta_detalle dd
                    INNER JOIN insumo i ON dd.id_insumo = i.id_insumo
                    WHERE dd.id_dieta = " . $dieta['id_dieta'];
                
                $resultado_insumos_dieta = ejecutarConsulta($query_insumos_dieta);
                
                $inserciones_exitosas = true;
                
                while ($insumo = mysqli_fetch_assoc($resultado_insumos_dieta)) {
                    
                    $id_insumo = $insumo['id_insumo'];
                    
                    // Solo insertar si hay kg reales para este insumo
                    if (isset($kg_reales[$id_insumo]) && $kg_reales[$id_insumo] > 0) {
                        
                        $kg_real = (float) $kg_reales[$id_insumo];
                        
                        // Calcular kg sugeridos según dieta teórica
                        $kg_sugeridos = ($insumo['porcentaje_teorico'] * $kg_totales) / 100;
                        
                        // Calcular porcentaje real
                        $porcentaje_real = ($kg_real / $kg_totales) * 100;
                        
                        // CÁLCULO CLAVE: kg de Materia Seca
                        $kg_ms = ($kg_real * $insumo['porcentaje_ms']) / 100;
                        
                        // Insertar detalle
                        $query_detalle = "
                            INSERT INTO consumo_lote_detalle 
                            (id_consumo, id_insumo, kg_sugeridos, kg_reales, porcentaje_real, kg_ms)
                            VALUES 
                            ($id_consumo, $id_insumo, $kg_sugeridos, $kg_real, $porcentaje_real, $kg_ms)
                        ";
                        
                        if (!ejecutarConsulta($query_detalle)) {
                            $inserciones_exitosas = false;
                            break;
                        }
                    }
                }
                
                if ($inserciones_exitosas) {
                    $exito = "✓ Alimentación registrada exitosamente. Los cálculos de MS se realizaron automáticamente.";
                    header("refresh:2;url=../lotes/ver.php?id=$id_tropa");
                } else {
                    $errores[] = "Error al guardar el detalle de insumos.";
                }
                
            } else {
                $errores[] = "Este lote no tiene una dieta asignada para la fecha seleccionada.";
            }
            
        } else {
            $errores[] = "Error al registrar la alimentación.";
        }
    }
}

include '../../includes/header.php';
?>

<h1 class="tarjeta-titulo">🍽️ Registrar Alimentación</h1>

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
    
    <form method="POST" class="formulario" id="formAlimentacion">
        
        <!-- PASO 1: Seleccionar Lote -->
        <h3 style="color: #2c5530; margin-bottom: 1rem;">📍 Paso 1: Seleccionar Lote</h3>
        
        <div class="form-grupo">
            <label for="id_tropa">Lote a Alimentar *</label>
            <select id="id_tropa" name="id_tropa" required onchange="this.form.submit()">
                <option value="">-- Seleccioná un lote --</option>
                <?php while ($lote = mysqli_fetch_assoc($lotes_disponibles)): ?>
                    <option value="<?php echo $lote['id_tropa']; ?>"
                        <?php echo ($id_lote_actual == $lote['id_tropa']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($lote['nombre']); ?> - <?php echo htmlspecialchars($lote['campo_nombre']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <?php if ($lote_seleccionado): ?>
            
            <!-- Información del lote seleccionado -->
            <div style="background: #e3f2fd; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem;">
                <strong>📊 Lote seleccionado:</strong> <?php echo htmlspecialchars($lote_seleccionado['nombre']); ?>
                <br>
                <strong>🐄 Animales presentes:</strong> <?php echo $animales_presentes; ?>
                <br>
                <strong>📋 Dieta vigente:</strong> 
                <?php if ($dieta_vigente): ?>
                    <span style="color: #28a745;">✓ <?php echo htmlspecialchars($dieta_vigente['dieta_nombre']); ?></span>
                    <a href="../dietas/ver.php?id=<?php echo $dieta_vigente['id_dieta']; ?>" target="_blank" style="margin-left: 0.5rem;">(Ver composición)</a>
                <?php else: ?>
                    <span style="color: #dc3545;">⚠️ Sin dieta asignada</span>
                <?php endif; ?>
            </div>
            
            <?php if (!$dieta_vigente): ?>
                
                <div class="mensaje mensaje-error">
                    ⚠️ Este lote no tiene una dieta asignada. 
                    <a href="../lotes/editar.php?id=<?php echo $id_lote_actual; ?>">Asigná una dieta</a> 
                    antes de registrar alimentaciones.
                </div>
                
            <?php else: ?>
                
                <hr style="margin: 2rem 0; border: none; border-top: 2px solid #e9ecef;">
                
                <!-- PASO 2: Datos de la Alimentación -->
                <h3 style="color: #2c5530; margin-bottom: 1rem;">📝 Paso 2: Datos de la Alimentación</h3>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    
                    <!-- Fecha -->
                    <div class="form-grupo">
                        <label for="fecha">Fecha *</label>
                        <input 
                            type="date" 
                            id="fecha" 
                            name="fecha" 
                            required 
                            value="<?php echo date('Y-m-d'); ?>"
                        >
                    </div>
                    
                    <!-- Hora -->
                    <div class="form-grupo">
                        <label for="hora">Hora *</label>
                        <input 
                            type="time" 
                            id="hora" 
                            name="hora" 
                            required 
                            value="<?php echo date('H:i'); ?>"
                        >
                    </div>
                    
                    <!-- Nivel de sobras -->
                    <div class="form-grupo">
                        <label for="sobrante_nivel">Estado del Comedero *</label>
                        <select id="sobrante_nivel" name="sobrante_nivel" required>
                            <option value="SIN_SOBRAS">🟢 Sin sobras</option>
                            <option value="POCAS_SOBRAS">🟡 Pocas sobras</option>
                            <option value="NORMAL" selected>🔵 Normal</option>
                            <option value="MUCHAS_SOBRAS">🔴 Muchas sobras</option>
                        </select>
                        <small>¿Cómo estaba el comedero ANTES de alimentar?</small>
                    </div>
                    
                </div>
                
                <hr style="margin: 2rem 0; border: none; border-top: 2px solid #e9ecef;">
                
                <!-- PASO 3: Mezcla Real por Insumo -->
                <h3 style="color: #2c5530; margin-bottom: 1rem;">🌾 Paso 3: Mezcla Real Entregada</h3>
                
                <div class="mensaje mensaje-info" style="margin-bottom: 1.5rem;">
                    ℹ️ Ingresá los <strong>kg reales</strong> que salieron del mixer para cada insumo.
                    El sistema calculará automáticamente la Materia Seca.
                </div>
                
                <div class="tabla-responsive">
                    <table>
                        <thead>
                            <tr style="background: #f8f9fa;">
                                <th>Insumo</th>
                                <th>Tipo</th>
                                <th>% MS</th>
                                <th style="width: 120px;">% Teórico</th>
                                <th style="width: 150px;">Kg Sugeridos</th>
                                <th style="width: 150px;">Kg Reales *</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($insumos_dieta as $insumo): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($insumo['nombre']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($insumo['tipo']); ?></td>
                                    <td>
                                        <span style="background: #e3f2fd; padding: 0.3rem 0.6rem; border-radius: 15px; font-size: 0.9rem;">
                                            <?php echo formatearNumero($insumo['porcentaje_ms'], 2); ?>%
                                        </span>
                                    </td>
                                    <td style="text-align: center;">
                                        <?php echo formatearNumero($insumo['porcentaje_teorico'], 2); ?>%
                                    </td>
                                    <td>
                                        <input 
                                            type="text" 
                                            class="kg-sugerido" 
                                            data-porcentaje="<?php echo $insumo['porcentaje_teorico']; ?>"
                                            readonly 
                                            style="background: #f8f9fa; text-align: center; width: 100%; padding: 0.5rem;"
                                            placeholder="0.0"
                                        >
                                    </td>
                                    <td>
                                        <input 
                                            type="number" 
                                            name="kg_real[<?php echo $insumo['id_insumo']; ?>]"
                                            class="kg-real"
                                            step="0.1" 
                                            min="0"
                                            placeholder="0.0"
                                            style="width: 100%; padding: 0.5rem; text-align: center;"
                                            data-ms="<?php echo $insumo['porcentaje_ms']; ?>"
                                        >
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <!-- Fila de totales -->
                            <tr style="background: #f8f9fa; font-weight: bold;">
                                <td colspan="5" style="text-align: right; padding: 1rem;">
                                    TOTAL DE LA MEZCLA:
                                </td>
                                <td style="padding: 1rem;">
                                    <input 
                                        type="number" 
                                        id="kg_totales" 
                                        name="kg_totales"
                                        step="0.1" 
                                        min="0"
                                        required
                                        placeholder="0.0"
                                        style="width: 100%; padding: 0.5rem; text-align: center; font-size: 1.1rem; font-weight: bold; border: 2px solid #2c5530;"
                                    >
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div style="margin-top: 1rem; padding: 1rem; background: #fff3cd; border-radius: 6px;">
                    <strong>📊 Resumen automático:</strong>
                    <div style="margin-top: 0.5rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <div>
                            <small>Suma de kg reales:</small><br>
                            <strong id="suma-kg-reales" style="font-size: 1.2rem; color: #2c5530;">0.0 kg</strong>
                        </div>
                        <div>
                            <small>Total de MS calculado:</small><br>
                            <strong id="total-ms" style="font-size: 1.2rem; color: #2c5530;">0.0 kg MS</strong>
                        </div>
                        <div>
                            <small>MS por animal:</small><br>
                            <strong id="ms-por-animal" style="font-size: 1.2rem; color: #2c5530;">0.0 kg</strong>
                        </div>
                    </div>
                </div>
                
                <!-- Botones -->
                <div class="btn-grupo">
                    <input type="hidden" name="guardar_alimentacion" value="1">
                    <button type="submit" class="btn btn-primario">💾 Guardar Alimentación</button>
                    <a href="../lotes/ver.php?id=<?php echo $id_lote_actual; ?>" class="btn btn-secundario">❌ Cancelar</a>
                </div>
                
            <?php endif; ?>
            
        <?php endif; ?>
        
    </form>
    
</div>

<!-- JavaScript para cálculos automáticos -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    const inputsKgReal = document.querySelectorAll('.kg-real');
    const inputKgTotales = document.getElementById('kg_totales');
    const inputsSugeridos = document.querySelectorAll('.kg-sugerido');
    const animalesPresentes = <?php echo $animales_presentes; ?>;
    
    // Función para calcular kg sugeridos
    function calcularSugeridos() {
        const kgTotales = parseFloat(inputKgTotales.value) || 0;
        
        inputsSugeridos.forEach(input => {
            const porcentaje = parseFloat(input.dataset.porcentaje) || 0;
            const kgSugerido = (kgTotales * porcentaje) / 100;
            input.value = kgSugerido.toFixed(1);
        });
    }
    
    // Función para calcular totales
    function calcularTotales() {
        let sumaKgReales = 0;
        let totalMS = 0;
        
        inputsKgReal.forEach(input => {
            const kgReal = parseFloat(input.value) || 0;
            const porcentajeMS = parseFloat(input.dataset.ms) || 0;
            
            sumaKgReales += kgReal;
            
            // Calcular MS: (kg_real * %MS) / 100
            const kgMS = (kgReal * porcentajeMS) / 100;
            totalMS += kgMS;
        });
        
        // Actualizar visualización
        document.getElementById('suma-kg-reales').textContent = sumaKgReales.toFixed(1) + ' kg';
        document.getElementById('total-ms').textContent = totalMS.toFixed(1) + ' kg MS';
        
        // Calcular MS por animal
        const msPorAnimal = animalesPresentes > 0 ? totalMS / animalesPresentes : 0;
        document.getElementById('ms-por-animal').textContent = msPorAnimal.toFixed(2) + ' kg';
        
        // Validar que la suma coincida con kg totales
        const kgTotales = parseFloat(inputKgTotales.value) || 0;
        const diferencia = Math.abs(sumaKgReales - kgTotales);
        
        if (diferencia > 1 && kgTotales > 0) {
            document.getElementById('suma-kg-reales').style.color = '#dc3545';
        } else {
            document.getElementById('suma-kg-reales').style.color = '#2c5530';
        }
    }
    
    // Event listeners
    inputKgTotales.addEventListener('input', function() {
        calcularSugeridos();
        calcularTotales();
    });
    
    inputsKgReal.forEach(input => {
        input.addEventListener('input', calcularTotales);
    });
    
    // Calcular inicial si hay valores
    calcularSugeridos();
    calcularTotales();
    
});
</script>

<?php include '../../includes/footer.php'; ?>