<?php
/**
 * SOLUFEED - Registrar Alimentación
 * Módulo principal del sistema - Registra consumo real y calcula MS automáticamente
 */

require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Verificar sesión y rol de campo
verificarCampo();

// Verificar si viene un lote pre-seleccionado
$lote_preseleccionado = isset($_GET['lote']) ? (int) $_GET['lote'] : null;

// Obtener lotes activos
$sql_join_usuario = "";
$sql_where_usuario = "";

if (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'CAMPO') {
    $sql_join_usuario = "INNER JOIN usuario_tropa ut ON t.id_tropa = ut.id_tropa";
    $sql_where_usuario = "AND ut.id_usuario = " . intval($_SESSION['usuario_id']);
}

// Obtener lotes activos
$query_lotes = "
    SELECT 
        t.id_tropa,
        t.nombre,
        c.nombre as campo_nombre
    FROM tropa t
    INNER JOIN campo c ON t.id_campo = c.id_campo
    $sql_join_usuario
    WHERE t.activo = 1
    $sql_where_usuario
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
    $kg_reales = $_POST['kg_real']; // Array de [id_insumo => kg]
    $origen_registro = isset($_POST['origen_registro']) ? limpiarDato($_POST['origen_registro']) : 'ONLINE';
    
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
    
    // Verificar que la suma de kg reales esté dentro de un margen razonable (+- 5%)
    $margen_permitido = $kg_totales * 0.05;
    if (abs($suma_kg_reales - $kg_totales) > $margen_permitido) { 
        $errores[] = "La diferencia entre el total (" . $kg_totales . " kg) y la suma de insumos (" . $suma_kg_reales . " kg) supera el 5% permitido (" . number_format($margen_permitido, 2) . " kg).";
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
            ($id_tropa, {$_SESSION['usuario_id']}, '$fecha', '$hora', $numero_alimentacion, '$sobrante_nivel', $kg_totales, $animales, '$origen_registro', NOW())
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
                    header("refresh:2;url=../campo/index.php");
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

<h1 style="font-weight: 800; color: var(--primary); margin-bottom: 2rem;">🍽️ Registrar Alimentación</h1>

<!-- Indicador de estado para PWA -->
<div id="connection-status" style="display:none; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: bold;"></div>

<div class="card">
    
    <?php if (isset($exito)): ?>
        <div class="card" style="background: #dcfce7; border-left: 5px solid var(--success); color: #166534; padding: 1rem; margin-bottom: 1.5rem;">
            <?php echo $exito; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($errores)): ?>
        <div class="card" style="background: #fee2e2; border-left: 5px solid var(--danger); color: #991b1b; padding: 1rem; margin-bottom: 1.5rem;">
            <strong style="display: block; margin-bottom: 0.5rem;">Se encontraron errores:</strong>
            <ul style="margin: 0; padding-left: 1.5rem; font-size: 0.9rem;">
                <?php foreach ($errores as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form method="POST" class="formulario" id="formAlimentacion">
        
        <!-- PASO 1: Seleccionar Lote -->
        <h3 class="card-title"><span>📍</span> Paso 1: Seleccionar Lote</h3>
        
        <div class="form-grupo">
            <label for="id_tropa">Lote a Alimentar *</label>
            <select id="id_tropa" name="id_tropa" required onchange="if(this.value) window.location.href='?lote=' + this.value;">
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
            <div class="card" style="background: var(--bg-main); border: 1px solid var(--border); margin-bottom: 2rem;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <div>
                        <small style="color: var(--text-muted); display: block; text-transform: uppercase; font-weight: 700; font-size: 0.7rem;">Lote</small>
                        <strong style="color: var(--primary);"><?php echo htmlspecialchars($lote_seleccionado['nombre']); ?></strong>
                    </div>
                    <div>
                        <small style="color: var(--text-muted); display: block; text-transform: uppercase; font-weight: 700; font-size: 0.7rem;">Animales</small>
                        <strong><?php echo $animales_presentes; ?> cab</strong>
                    </div>
                    <div>
                        <small style="color: var(--text-muted); display: block; text-transform: uppercase; font-weight: 700; font-size: 0.7rem;">Dieta Vigente</small>
                        <?php if ($dieta_vigente): ?>
                            <span style="color: var(--success); font-weight: 700;"><?php echo htmlspecialchars($dieta_vigente['dieta_nombre']); ?></span>
                        <?php else: ?>
                            <span style="color: var(--danger); font-weight: 700;">Sin dieta</span>
                        <?php endif; ?>
                    </div>
                </div>
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
                <h3 class="card-title"><span>📝</span> Paso 2: Datos de la Alimentación</h3>
                
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
                        <select id="sobrante_nivel" name="sobrante_nivel" required style="font-weight: 700;">
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
                <h3 class="card-title"><span>🌾</span> Paso 3: Mezcla Real Entregada</h3>
                
                <div class="card" style="background: #eef2ff; border: none; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 15px;">
                    <div style="font-size: 1.5rem;">ℹ️</div>
                    <div style="font-size: 0.9rem; color: var(--secondary); font-weight: 500;">
                        Ingresá los <strong>kg totales</strong> del mixer y luego los <strong>reales</strong> que salieron para cada insumo.
                    </div>
                </div>
                
                <div class="table-container" style="margin-bottom: 2rem;">
                    <table>
                        <thead>
                            <tr>
                                <th>Insumo</th>
                                <th>% MS</th>
                                <th style="width: 150px;">Kg Sugeridos</th>
                                <th style="width: 150px;">Kg Reales *</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($insumos_dieta as $insumo): ?>
                                <tr>
                                    <td>
                                        <strong style="display: block; color: var(--text-main);"><?php echo htmlspecialchars($insumo['nombre']); ?></strong>
                                        <small style="color: var(--text-muted);"><?php echo htmlspecialchars($insumo['tipo']); ?> | <?php echo formatearNumero($insumo['porcentaje_teorico'], 1); ?>% ración</small>
                                    </td>
                                    <td>
                                        <span style="background: var(--bg-main); padding: 4px 10px; border-radius: 50px; font-size: 0.8rem; font-weight: 600; border: 1px solid var(--border);">
                                            <?php echo formatearNumero($insumo['porcentaje_ms'], 1); ?>%
                                        </span>
                                    </td>
                                    <td>
                                        <input 
                                            type="text" 
                                            class="kg-sugerido" 
                                            data-porcentaje="<?php echo $insumo['porcentaje_teorico']; ?>"
                                            readonly 
                                            style="background: #f1f5f9; text-align: center; font-weight: 600; border-color: transparent;"
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
                                            inputmode="decimal"
                                            placeholder="0.0"
                                            style="text-align: center; font-weight: 800; border-bottom: 3px solid var(--border);"
                                            data-ms="<?php echo $insumo['porcentaje_ms']; ?>"
                                        >
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <!-- Fila de totales -->
                            <tr style="background: #f8fafc; font-weight: bold;">
                                <td colspan="3" style="text-align: right; padding: 1.5rem; font-size: 1.1rem; color: var(--primary);">
                                    TOTAL DEL MIXER (Kg):
                                </td>
                                <td style="padding: 1rem;">
                                    <input 
                                        type="number" 
                                        id="kg_totales" 
                                        name="kg_totales"
                                        step="0.1" 
                                        min="0"
                                        required
                                        inputmode="decimal"
                                        placeholder="TOTAL"
                                        style="width: 100%; padding: 1rem; text-align: center; font-size: 1.5rem; font-weight: 900; border: 3px solid var(--primary); color: var(--primary); background: white;"
                                    >
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="card" style="background: var(--bg-main); border: 2px dashed var(--border); padding: 1.5rem; margin-bottom: 2rem;">
                    <strong style="display: block; color: var(--text-muted); text-transform: uppercase; font-size: 0.75rem; margin-bottom: 1rem; letter-spacing: 1px;">📊 Resumen automático</strong>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1.5rem;">
                        <div style="text-align: center;">
                            <div id="suma-kg-reales" style="font-size: 1.75rem; font-weight: 800; color: var(--primary);">0.0 <small style="font-size: 50%;">kg</small></div>
                            <small style="font-weight: 600; color: var(--text-muted);">Suma Reales</small>
                        </div>
                        <div style="text-align: center;">
                            <div id="total-ms" style="font-size: 1.75rem; font-weight: 800; color: var(--secondary);">0.0 <small style="font-size: 50%;">kg MS</small></div>
                            <small style="font-weight: 600; color: var(--text-muted);">Materia Seca</small>
                        </div>
                        <div style="text-align: center;">
                            <div id="ms-por-animal" style="font-size: 1.75rem; font-weight: 800; color: var(--accent);">0.0 <small style="font-size: 50%;">kg/an</small></div>
                            <small style="font-weight: 600; color: var(--text-muted);">MS por Animal</small>
                        </div>
                    </div>
                </div>
                
                <!-- Botones -->
                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <input type="hidden" name="guardar_alimentacion" value="1">
                    <button type="submit" class="btn btn-primary" style="flex: 2; padding: 1.25rem;">💾 Guardar Alimentación</button>
                    <a href="../campo/index.php" class="btn btn-secondary" style="flex: 1; padding: 1.25rem;">❌ Cancelar</a>
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
    
    if (!inputKgTotales) return; // Evitar errores si no hay lote seleccionado todavía
    
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
        
        const margenJS = kgTotales * 0.05;
        if (diferencia > margenJS && kgTotales > 0) {
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formAlimentacion');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        // Si estamos offline, interceptamos el envío
        if (!navigator.onLine) {
            e.preventDefault();
            
            // Recolectar datos del formulario
            const formData = new FormData(form);
            const data = {};
            formData.forEach((value, key) => {
                data[key] = value;
            });
            
            // Añadir bandera para el servidor
            data['guardar_alimentacion'] = '1';
            data['origen_registro'] = 'OFFLINE';

            // Guardar en la cola local
            OfflineManager.saveToQueue(window.location.href, data, 'alimentacion');
            
            // Redirigir al hub después de un momento
            setTimeout(() => {
                window.location.href = '../campo/index.php';
            }, 3000);
        }
    });
});
</script>