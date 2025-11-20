<?php
/**
 * SOLUFEED - Registrar Pesada
 * Calcula ADPV automáticamente y detecta diferencias de animales
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
$animales_esperados = 0;
$peso_anterior = null;
$fecha_anterior = null;
$dias_desde_ultima = 0;
$adpv_estimado = null;

// Si se seleccionó un lote (por GET o POST)
$id_lote_actual = $lote_preseleccionado;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_tropa'])) {
    $id_lote_actual = (int) $_POST['id_tropa'];
}

if ($id_lote_actual) {
    
    // Obtener datos del lote
    $query_lote = "
        SELECT t.*, c.nombre as campo_nombre
        FROM tropa t
        INNER JOIN campo c ON t.id_campo = c.id_campo
        WHERE t.id_tropa = $id_lote_actual
    ";
    $resultado_lote = ejecutarConsulta($query_lote);
    
    if (mysqli_num_rows($resultado_lote) > 0) {
        $lote_seleccionado = mysqli_fetch_assoc($resultado_lote);
        $animales_esperados = obtenerAnimalesPresentes($id_lote_actual);
        
        // Obtener última pesada
        $query_ultima_pesada = "
            SELECT peso_promedio, fecha
            FROM pesada
            WHERE id_tropa = $id_lote_actual
            ORDER BY fecha DESC
            LIMIT 1
        ";
        $resultado_ultima = ejecutarConsulta($query_ultima_pesada);
        
        if (mysqli_num_rows($resultado_ultima) > 0) {
            $ultima_pesada = mysqli_fetch_assoc($resultado_ultima);
            $peso_anterior = $ultima_pesada['peso_promedio'];
            $fecha_anterior = $ultima_pesada['fecha'];
            
            // Calcular días desde última pesada
            $fecha_ant = new DateTime($fecha_anterior);
            $fecha_hoy = new DateTime();
            $dias_desde_ultima = $fecha_ant->diff($fecha_hoy)->days;
        }
    }
}

// Procesar formulario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_pesada'])) {
    
    // Recibir datos del formulario
    $id_tropa = (int) $_POST['id_tropa'];
    $fecha = limpiarDato($_POST['fecha']);
    $peso_promedio = (float) $_POST['peso_promedio'];
    $animales_esperados_form = (int) $_POST['animales_esperados'];
    $animales_vistos = (int) $_POST['animales_vistos'];
    $hay_diferencia = ($animales_vistos != $animales_esperados_form) ? 1 : 0;
    
    // Si hay diferencia, recibir datos adicionales
    $diferencia_animales = 0;
    $motivo_operario = '';
    
    if ($hay_diferencia) {
        $diferencia_animales = $animales_vistos - $animales_esperados_form;
        $motivo_operario = isset($_POST['motivo_operario']) ? limpiarDato($_POST['motivo_operario']) : '';
    }
    
    // Validaciones
    $errores = [];
    
    if ($id_tropa <= 0) {
        $errores[] = "Debés seleccionar un lote.";
    }
    
    if (empty($fecha)) {
        $errores[] = "La fecha es obligatoria.";
    }
    
    if ($peso_promedio <= 0) {
        $errores[] = "El peso promedio debe ser mayor a 0.";
    }
    
    if ($animales_vistos <= 0) {
        $errores[] = "La cantidad de animales vistos debe ser mayor a 0.";
    }
    
    // Si no hay errores, guardar
    if (empty($errores)) {
        
        // Insertar pesada
        $query_pesada = "
            INSERT INTO pesada 
            (id_tropa, id_usuario, fecha, peso_promedio, animales_esperados, animales_vistos, hay_diferencia, origen_registro, fecha_creacion)
            VALUES 
            ($id_tropa, {$_SESSION['usuario_id']}, '$fecha', $peso_promedio, $animales_esperados_form, $animales_vistos, $hay_diferencia, 'ONLINE', NOW())
        ";
        
        if (ejecutarConsulta($query_pesada)) {
            
            $id_pesada = mysqli_insert_id($conn);
            
            // Si hay diferencia, crear ajuste pendiente
            if ($hay_diferencia) {
                $query_ajuste = "
                    INSERT INTO ajuste_animales_pendiente 
                    (id_pesada, id_tropa, diferencia_animales, motivo_operario, estado, fecha_creacion)
                    VALUES 
                    ($id_pesada, $id_tropa, $diferencia_animales, '$motivo_operario', 'PENDIENTE', NOW())
                ";
                ejecutarConsulta($query_ajuste);
            }
            
            $exito = "✓ Pesada registrada exitosamente.";
            
            if ($hay_diferencia) {
                $exito .= " Se creó un ajuste pendiente para revisión del administrador.";
            }
            
            header("refresh:3;url=../lotes/ver.php?id=$id_tropa");
            
        } else {
            $errores[] = "Error al registrar la pesada.";
        }
    }
}

include '../../includes/header.php';
?>

<h1 class="tarjeta-titulo">⚖️ Registrar Pesada</h1>

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
    
    <form method="POST" class="formulario" id="formPesada">
        
        <!-- PASO 1: Seleccionar Lote -->
        <h3 style="color: #2c5530; margin-bottom: 1rem;">📍 Paso 1: Seleccionar Lote</h3>
        
        <div class="form-grupo">
            <label for="id_tropa">Lote a Pesar *</label>
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
                <strong>🐄 Animales esperados:</strong> <?php echo $animales_esperados; ?>
                <br>
                <strong>📅 Fecha de inicio:</strong> <?php echo formatearFecha($lote_seleccionado['fecha_inicio']); ?>
                <?php
                $fecha_inicio = new DateTime($lote_seleccionado['fecha_inicio']);
                $fecha_hoy = new DateTime();
                $dias_total = $fecha_inicio->diff($fecha_hoy)->days;
                ?>
                (<?php echo $dias_total; ?> días en feedlot)
            </div>
            
            <!-- Historial de pesadas -->
            <?php if ($peso_anterior): ?>
                <div style="background: #fff3cd; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem;">
                    <strong>📈 Última pesada:</strong><br>
                    <strong>Peso:</strong> <?php echo formatearNumero($peso_anterior, 2); ?> kg/animal
                    | <strong>Fecha:</strong> <?php echo formatearFecha($fecha_anterior); ?>
                    | <strong>Hace:</strong> <?php echo $dias_desde_ultima; ?> días
                </div>
            <?php else: ?>
                <div class="mensaje mensaje-info" style="margin-bottom: 1.5rem;">
                    ℹ️ Esta será la <strong>primera pesada</strong> de este lote. 
                    Servirá como peso inicial de referencia para calcular ADPV.
                </div>
            <?php endif; ?>
            
            <hr style="margin: 2rem 0; border: none; border-top: 2px solid #e9ecef;">
            
            <!-- PASO 2: Datos de la Pesada -->
            <h3 style="color: #2c5530; margin-bottom: 1rem;">📝 Paso 2: Datos de la Pesada</h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                
                <!-- Fecha -->
                <div class="form-grupo">
                    <label for="fecha">Fecha de la Pesada *</label>
                    <input 
                        type="date" 
                        id="fecha" 
                        name="fecha" 
                        required 
                        value="<?php echo date('Y-m-d'); ?>"
                    >
                </div>
                
                <!-- Peso promedio -->
                <div class="form-grupo">
                    <label for="peso_promedio">Peso Promedio (kg/animal) *</label>
                    <input 
                        type="number" 
                        id="peso_promedio" 
                        name="peso_promedio" 
                        step="0.01" 
                        min="0"
                        required 
                        placeholder="Ej: 410.50"
                        style="font-size: 1.2rem; font-weight: bold;"
                    >
                    <small>Peso promedio del lote o de los animales pesados.</small>
                </div>
                
            </div>
            
            <!-- Cálculo de ADPV automático -->
            <?php if ($peso_anterior && $dias_desde_ultima > 0): ?>
                <div id="calculo-adpv" style="background: #d4edda; padding: 1rem; border-radius: 6px; margin-top: 1rem; display: none;">
                    <strong>📊 ADPV Calculado Automáticamente:</strong><br>
                    <div style="font-size: 1.5rem; color: #155724; margin-top: 0.5rem;">
                        <strong id="valor-adpv">0.00</strong> kg/día
                    </div>
                    <small style="color: #155724;">
                        (Aumento desde la última pesada en <?php echo $dias_desde_ultima; ?> días)
                    </small>
                </div>
            <?php endif; ?>
            
            <hr style="margin: 2rem 0; border: none; border-top: 2px solid #e9ecef;">
            
            <!-- PASO 3: Verificación de Animales -->
            <h3 style="color: #2c5530; margin-bottom: 1rem;">🔍 Paso 3: Verificación de Animales</h3>
            
            <div class="mensaje mensaje-info" style="margin-bottom: 1.5rem;">
                ℹ️ Verificá si la cantidad de animales que viste coincide con la cantidad esperada.
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                
                <!-- Animales esperados (automático) -->
                <div class="form-grupo">
                    <label for="animales_esperados">Animales Esperados</label>
                    <input 
                        type="number" 
                        id="animales_esperados" 
                        name="animales_esperados" 
                        readonly 
                        value="<?php echo $animales_esperados; ?>"
                        style="background: #f8f9fa; font-weight: bold; font-size: 1.1rem;"
                    >
                    <small>Calculado automáticamente según movimientos.</small>
                </div>
                
                <!-- Animales vistos (ingreso manual) -->
                <div class="form-grupo">
                    <label for="animales_vistos">Animales Vistos en la Pesada *</label>
                    <input 
                        type="number" 
                        id="animales_vistos" 
                        name="animales_vistos" 
                        required 
                        min="0"
                        value="<?php echo $animales_esperados; ?>"
                        style="font-weight: bold; font-size: 1.1rem;"
                    >
                    <small>¿Cuántos animales contaste realmente?</small>
                </div>
                
            </div>
            
            <!-- Diferencia detectada -->
            <div id="diferencia-container" style="display: none; margin-top: 1rem; padding: 1rem; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 6px;">
                <strong>⚠️ Diferencia Detectada:</strong>
                <div style="font-size: 1.3rem; margin: 0.5rem 0;">
                    <span id="texto-diferencia"></span>
                </div>
                
                <div class="form-grupo" style="margin-top: 1rem;">
                    <label for="motivo_operario">Motivo de la Diferencia (opcional)</label>
                    <textarea 
                        id="motivo_operario" 
                        name="motivo_operario" 
                        rows="3"
                        placeholder="Ej: Se encontró un animal muerto, Se escaparon 2 animales, etc."
                    ></textarea>
                    <small>Este ajuste quedará pendiente de validación por el administrador.</small>
                </div>
            </div>
            
            <!-- Botones -->
            <div class="btn-grupo">
                <input type="hidden" name="guardar_pesada" value="1">
                <button type="submit" class="btn btn-primario">💾 Guardar Pesada</button>
                <a href="../lotes/ver.php?id=<?php echo $id_lote_actual; ?>" class="btn btn-secundario">❌ Cancelar</a>
            </div>
            
        <?php endif; ?>
        
    </form>
    
</div>

<!-- JavaScript para cálculos automáticos -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    const inputPeso = document.getElementById('peso_promedio');
    const inputAnimalesVistos = document.getElementById('animales_vistos');
    const animalesEsperados = parseInt(document.getElementById('animales_esperados').value) || 0;
    const diferenciaContainer = document.getElementById('diferencia-container');
    const textoDiferencia = document.getElementById('texto-diferencia');
    
    <?php if ($peso_anterior && $dias_desde_ultima > 0): ?>
    const calculoADPV = document.getElementById('calculo-adpv');
    const valorADPV = document.getElementById('valor-adpv');
    const pesoAnterior = <?php echo $peso_anterior; ?>;
    const diasDesdeUltima = <?php echo $dias_desde_ultima; ?>;
    <?php endif; ?>
    
    // Función para calcular ADPV
    function calcularADPV() {
        <?php if ($peso_anterior && $dias_desde_ultima > 0): ?>
        const pesoActual = parseFloat(inputPeso.value) || 0;
        
        if (pesoActual > 0) {
            const adpv = (pesoActual - pesoAnterior) / diasDesdeUltima;
            valorADPV.textContent = adpv.toFixed(3);
            calculoADPV.style.display = 'block';
            
            // Cambiar color según ADPV
            if (adpv < 0) {
                valorADPV.style.color = '#dc3545'; // Rojo si perdió peso
            } else if (adpv < 0.5) {
                valorADPV.style.color = '#ffc107'; // Amarillo si es bajo
            } else {
                valorADPV.style.color = '#155724'; // Verde si es bueno
            }
        } else {
            calculoADPV.style.display = 'none';
        }
        <?php endif; ?>
    }
    
    // Función para verificar diferencia de animales
    function verificarDiferencia() {
        const animalesVistos = parseInt(inputAnimalesVistos.value) || 0;
        const diferencia = animalesVistos - animalesEsperados;
        
        if (diferencia !== 0) {
            diferenciaContainer.style.display = 'block';
            
            if (diferencia > 0) {
                textoDiferencia.innerHTML = '<span style="color: #28a745;">+' + diferencia + ' animal(es) más de lo esperado</span>';
            } else {
                textoDiferencia.innerHTML = '<span style="color: #dc3545;">' + diferencia + ' animal(es) menos de lo esperado</span>';
            }
        } else {
            diferenciaContainer.style.display = 'none';
        }
    }
    
    // Event listeners
    inputPeso.addEventListener('input', calcularADPV);
    inputAnimalesVistos.addEventListener('input', verificarDiferencia);
    
    // Calcular inicial
    calcularADPV();
    verificarDiferencia();
    
});
</script>

<?php include '../../includes/footer.php'; ?>