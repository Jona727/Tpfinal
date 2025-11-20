<?php
/**
 * SOLUFEED - Reporte de Consumo y Métricas
 * Muestra indicadores técnicos calculados automáticamente
 */

require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Simular sesión
$_SESSION['usuario_id'] = 1;
$_SESSION['nombre'] = 'Administrador';
$_SESSION['tipo'] = 'ADMIN';

// Obtener lotes activos para el filtro
$query_lotes = "
    SELECT id_tropa, nombre
    FROM tropa
    WHERE activo = 1
    ORDER BY nombre ASC
";
$lotes_disponibles = ejecutarConsulta($query_lotes);

// Filtros
$lote_filtro = isset($_GET['lote']) ? (int) $_GET['lote'] : 0;
$fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : date('Y-m-d', strtotime('-30 days'));
$fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : date('Y-m-d');

// Si no se seleccionó lote específico, tomar el primero
if ($lote_filtro == 0) {
    mysqli_data_seek($lotes_disponibles, 0);
    if ($primer_lote = mysqli_fetch_assoc($lotes_disponibles)) {
        $lote_filtro = $primer_lote['id_tropa'];
    }
}

// Obtener datos del lote seleccionado
$lote_seleccionado = null;
$animales_presentes = 0;

if ($lote_filtro > 0) {
    $query_lote = "
        SELECT t.*, c.nombre as campo_nombre
        FROM tropa t
        INNER JOIN campo c ON t.id_campo = c.id_campo
        WHERE t.id_tropa = $lote_filtro
    ";
    $resultado_lote = ejecutarConsulta($query_lote);
    
    if (mysqli_num_rows($resultado_lote) > 0) {
        $lote_seleccionado = mysqli_fetch_assoc($resultado_lote);
        $animales_presentes = obtenerAnimalesPresentes($lote_filtro);
    }
}

include '../../includes/header.php';
?>

<h1 class="tarjeta-titulo">📈 Reportes de Consumo y Métricas</h1>

<!-- Filtros -->
<div class="tarjeta">
    <h2 class="tarjeta-titulo">🔍 Filtros</h2>
    
    <form method="GET" class="formulario">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            
            <!-- Lote -->
            <div class="form-grupo">
                <label for="lote">Lote</label>
                <select id="lote" name="lote" onchange="this.form.submit()">
                    <?php 
                    mysqli_data_seek($lotes_disponibles, 0);
                    while ($lote = mysqli_fetch_assoc($lotes_disponibles)): 
                    ?>
                        <option value="<?php echo $lote['id_tropa']; ?>"
                            <?php echo ($lote_filtro == $lote['id_tropa']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($lote['nombre']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <!-- Fecha desde -->
            <div class="form-grupo">
                <label for="fecha_desde">Desde</label>
                <input type="date" id="fecha_desde" name="fecha_desde" value="<?php echo $fecha_desde; ?>">
            </div>
            
            <!-- Fecha hasta -->
            <div class="form-grupo">
                <label for="fecha_hasta">Hasta</label>
                <input type="date" id="fecha_hasta" name="fecha_hasta" value="<?php echo $fecha_hasta; ?>">
            </div>
            
            <!-- Botón filtrar -->
            <div class="form-grupo" style="display: flex; align-items: flex-end;">
                <button type="submit" class="btn btn-primario" style="width: 100%;">Filtrar</button>
            </div>
            
        </div>
    </form>
</div>

<?php if ($lote_seleccionado): ?>

<!-- Información del lote -->
<div class="tarjeta" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
    <h2 style="color: #2c5530; margin-bottom: 1rem;">
        🐮 <?php echo htmlspecialchars($lote_seleccionado['nombre']); ?>
    </h2>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
        <div>
            <small style="color: #666;">Campo:</small><br>
            <strong><?php echo htmlspecialchars($lote_seleccionado['campo_nombre']); ?></strong>
        </div>
        <div>
            <small style="color: #666;">Categoría:</small><br>
            <strong><?php echo htmlspecialchars($lote_seleccionado['categoria']); ?></strong>
        </div>
        <div>
            <small style="color: #666;">Animales actuales:</small><br>
            <strong style="font-size: 1.3rem; color: #2c5530;"><?php echo $animales_presentes; ?></strong>
        </div>
        <div>
            <small style="color: #666;">Fecha inicio:</small><br>
            <strong><?php echo formatearFecha($lote_seleccionado['fecha_inicio']); ?></strong>
        </div>
    </div>
</div>

<?php
// ========================================
// CÁLCULO DE INDICADORES PRINCIPALES
// ========================================

// 1. Obtener pesadas en el rango
$query_pesadas = "
    SELECT peso_promedio, fecha
    FROM pesada
    WHERE id_tropa = $lote_filtro
    AND fecha BETWEEN '$fecha_desde' AND '$fecha_hasta'
    ORDER BY fecha ASC
";
$resultado_pesadas = ejecutarConsulta($query_pesadas);

$peso_inicial = null;
$peso_final = null;
$peso_medio = null;
$dias_periodo = 0;

$pesadas_array = [];
while ($pesada = mysqli_fetch_assoc($resultado_pesadas)) {
    $pesadas_array[] = $pesada;
}

if (count($pesadas_array) > 0) {
    $peso_inicial = $pesadas_array[0]['peso_promedio'];
    $peso_final = $pesadas_array[count($pesadas_array) - 1]['peso_promedio'];
    $peso_medio = ($peso_inicial + $peso_final) / 2;
    
    $fecha_ini = new DateTime($pesadas_array[0]['fecha']);
    $fecha_fin = new DateTime($pesadas_array[count($pesadas_array) - 1]['fecha']);
    $dias_periodo = max(1, $fecha_ini->diff($fecha_fin)->days);
}

// 2. Obtener consumo total de MS en el período
$query_ms_total = "
    SELECT 
        IFNULL(SUM(cld.kg_ms), 0) as total_kg_ms,
        COUNT(DISTINCT cl.fecha) as dias_con_alimentacion
    FROM consumo_lote cl
    INNER JOIN consumo_lote_detalle cld ON cl.id_consumo = cld.id_consumo
    WHERE cl.id_tropa = $lote_filtro
    AND cl.fecha BETWEEN '$fecha_desde' AND '$fecha_hasta'
";
$resultado_ms = ejecutarConsulta($query_ms_total);
$datos_ms = mysqli_fetch_assoc($resultado_ms);
$total_kg_ms = $datos_ms['total_kg_ms'];
$dias_con_alimentacion = $datos_ms['dias_con_alimentacion'];

// 3. Calcular indicadores
$adpv = null;
$cms_diario = null;
$cms_porcentaje_pv = null;
$ec = null;
$kg_producidos = null;

if ($peso_inicial && $peso_final && $dias_periodo > 0) {
    // ADPV = (Peso Final - Peso Inicial) / Días
    $adpv = ($peso_final - $peso_inicial) / $dias_periodo;
    
    // Kg Producidos = (Peso Final - Peso Inicial) * Animales
    $kg_producidos = ($peso_final - $peso_inicial) * $animales_presentes;
}

if ($total_kg_ms > 0 && $dias_con_alimentacion > 0 && $animales_presentes > 0) {
    // CMS Diario = Total MS / (Días * Animales)
    $cms_diario = $total_kg_ms / ($dias_con_alimentacion * $animales_presentes);
    
    // CMS % PV = (CMS / Peso Medio) * 100
    if ($peso_medio) {
        $cms_porcentaje_pv = ($cms_diario / $peso_medio) * 100;
    }
    
    // EC = Total MS / Kg Producidos
    if ($kg_producidos && $kg_producidos > 0) {
        $ec = $total_kg_ms / $kg_producidos;
    }
}

?>

<!-- Indicadores Principales -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
    
    <?php if ($peso_inicial): ?>
    <div class="indicador">
        <div class="indicador-icono">⚖️</div>
        <div class="indicador-valor"><?php echo formatearNumero($peso_inicial, 0); ?> kg</div>
        <div class="indicador-label">Peso Inicial Promedio</div>
    </div>
    <?php endif; ?>
    
    <?php if ($peso_final): ?>
    <div class="indicador">
        <div class="indicador-icono">📊</div>
        <div class="indicador-valor"><?php echo formatearNumero($peso_final, 0); ?> kg</div>
        <div class="indicador-label">Peso Final Promedio</div>
    </div>
    <?php endif; ?>
    
    <?php if ($adpv !== null): ?>
    <div class="indicador">
        <div class="indicador-icono">📈</div>
        <div class="indicador-valor" style="color: <?php echo $adpv > 0 ? '#28a745' : '#dc3545'; ?>">
            <?php echo formatearNumero($adpv, 3); ?>
        </div>
        <div class="indicador-label">ADPV (kg/día)</div>
    </div>
    <?php endif; ?>
    
    <?php if ($cms_diario !== null): ?>
    <div class="indicador">
        <div class="indicador-icono">🌾</div>
        <div class="indicador-valor"><?php echo formatearNumero($cms_diario, 2); ?></div>
        <div class="indicador-label">CMS Diario (kg MS/día)</div>
    </div>
    <?php endif; ?>
    
    <?php if ($cms_porcentaje_pv !== null): ?>
    <div class="indicador">
        <div class="indicador-icono">📊</div>
        <div class="indicador-valor"><?php echo formatearNumero($cms_porcentaje_pv, 2); ?>%</div>
        <div class="indicador-label">CMS % PV</div>
    </div>
    <?php endif; ?>
    
    <?php if ($ec !== null): ?>
    <div class="indicador">
        <div class="indicador-icono">⚡</div>
        <div class="indicador-valor"><?php echo formatearNumero($ec, 2); ?></div>
        <div class="indicador-label">Efic. Conversión (EC)</div>
        <small style="font-size: 0.75rem; color: #999;">kg MS / kg producido</small>
    </div>
    <?php endif; ?>
    
    <?php if ($kg_producidos !== null): ?>
    <div class="indicador">
        <div class="indicador-icono">🥩</div>
        <div class="indicador-valor"><?php echo formatearNumero($kg_producidos, 0); ?> kg</div>
        <div class="indicador-label">Kilos Producidos</div>
    </div>
    <?php endif; ?>
    
    <div class="indicador">
        <div class="indicador-icono">📅</div>
        <div class="indicador-valor"><?php echo $dias_periodo; ?></div>
        <div class="indicador-label">Días del Período</div>
    </div>
    
</div>

<!-- Explicación de Indicadores -->
<div class="tarjeta">
    <h2 class="tarjeta-titulo">📖 Glosario de Indicadores</h2>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
        
        <div>
            <strong style="color: #2c5530;">ADPV (Aumento Diario de Peso Vivo)</strong>
            <p style="margin: 0.5rem 0; color: #666; font-size: 0.9rem;">
                Kg que aumenta cada animal por día. Indica la velocidad de engorde.
                <br><strong>Fórmula:</strong> (Peso Final - Peso Inicial) / Días
            </p>
        </div>
        
        <div>
            <strong style="color: #2c5530;">CMS (Consumo de Materia Seca)</strong>
            <p style="margin: 0.5rem 0; color: #666; font-size: 0.9rem;">
                Kg de materia seca que consume cada animal por día.
                <br><strong>Fórmula:</strong> Total MS / (Días × Animales)
            </p>
        </div>
        
        <div>
            <strong style="color: #2c5530;">CMS % PV (CMS como % del Peso Vivo)</strong>
            <p style="margin: 0.5rem 0; color: #666; font-size: 0.9rem;">
                Consumo de MS expresado como porcentaje del peso del animal.
                <br><strong>Fórmula:</strong> (CMS / Peso Medio) × 100
            </p>
        </div>
        
        <div>
            <strong style="color: #2c5530;">EC (Eficiencia de Conversión)</strong>
            <p style="margin: 0.5rem 0; color: #666; font-size: 0.9rem;">
                Kg de MS necesarios para producir 1 kg de carne. Menor es mejor.
                <br><strong>Fórmula:</strong> Total MS / Kg Producidos
            </p>
        </div>
        
    </div>
</div>

<!-- Evolución de Peso -->
<?php if (count($pesadas_array) > 0): ?>
<div class="tarjeta">
    <h2 class="tarjeta-titulo">📊 Evolución de Peso</h2>
    
    <div class="tabla-responsive">
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Peso Promedio (kg)</th>
                    <th>Variación desde anterior</th>
                    <th>ADPV desde anterior</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $peso_anterior_tabla = null;
                $fecha_anterior_tabla = null;
                
                foreach ($pesadas_array as $pesada): 
                    $variacion = null;
                    $adpv_parcial = null;
                    
                    if ($peso_anterior_tabla !== null && $fecha_anterior_tabla !== null) {
                        $variacion = $pesada['peso_promedio'] - $peso_anterior_tabla;
                        
                        $fecha_ant = new DateTime($fecha_anterior_tabla);
                        $fecha_act = new DateTime($pesada['fecha']);
                        $dias_dif = max(1, $fecha_ant->diff($fecha_act)->days);
                        
                        $adpv_parcial = $variacion / $dias_dif;
                    }
                ?>
                    <tr>
                        <td><?php echo formatearFecha($pesada['fecha']); ?></td>
                        <td><strong><?php echo formatearNumero($pesada['peso_promedio'], 2); ?> kg</strong></td>
                        <td>
                            <?php if ($variacion !== null): ?>
                                <span style="color: <?php echo $variacion >= 0 ? '#28a745' : '#dc3545'; ?>; font-weight: 600;">
                                    <?php echo $variacion >= 0 ? '+' : ''; ?><?php echo formatearNumero($variacion, 2); ?> kg
                                </span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($adpv_parcial !== null): ?>
                                <span style="color: <?php echo $adpv_parcial >= 0 ? '#28a745' : '#dc3545'; ?>; font-weight: 600;">
                                    <?php echo formatearNumero($adpv_parcial, 3); ?> kg/día
                                </span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php 
                    $peso_anterior_tabla = $pesada['peso_promedio'];
                    $fecha_anterior_tabla = $pesada['fecha'];
                endforeach; 
                ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Detalle de Alimentaciones -->
<?php
$query_alimentaciones = "
    SELECT 
        cl.fecha,
        cl.hora,
        cl.kg_totales_tirados,
        cl.animales_presentes,
        cl.sobrante_nivel,
        SUM(cld.kg_ms) as total_kg_ms
    FROM consumo_lote cl
    LEFT JOIN consumo_lote_detalle cld ON cl.id_consumo = cld.id_consumo
    WHERE cl.id_tropa = $lote_filtro
    AND cl.fecha BETWEEN '$fecha_desde' AND '$fecha_hasta'
    GROUP BY cl.id_consumo
    ORDER BY cl.fecha DESC, cl.hora DESC
";
$alimentaciones = ejecutarConsulta($query_alimentaciones);
?>

<?php if (mysqli_num_rows($alimentaciones) > 0): ?>
<div class="tarjeta">
    <h2 class="tarjeta-titulo">🍽️ Detalle de Alimentaciones</h2>
    
    <div class="tabla-responsive">
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Animales</th>
                    <th>Kg Totales</th>
                    <th>Kg/Animal</th>
                    <th>Kg MS Total</th>
                    <th>Kg MS/Animal</th>
                    <th>Sobras</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($alim = mysqli_fetch_assoc($alimentaciones)): ?>
                    <?php 
                    $kg_por_animal = $alim['animales_presentes'] > 0 
                        ? $alim['kg_totales_tirados'] / $alim['animales_presentes'] 
                        : 0;
                    $kg_ms_por_animal = $alim['animales_presentes'] > 0
                        ? $alim['total_kg_ms'] / $alim['animales_presentes']
                        : 0;
                    ?>
                    <tr>
                        <td><?php echo formatearFecha($alim['fecha']); ?></td>
                        <td><?php echo date('H:i', strtotime($alim['hora'])); ?></td>
                        <td><?php echo $alim['animales_presentes']; ?></td>
                        <td><?php echo formatearNumero($alim['kg_totales_tirados'], 1); ?> kg</td>
                        <td><strong><?php echo formatearNumero($kg_por_animal, 2); ?> kg</strong></td>
                        <td style="color: #2c5530; font-weight: 600;">
                            <?php echo formatearNumero($alim['total_kg_ms'], 1); ?> kg MS
                        </td>
                        <td style="color: #2c5530; font-weight: 600;">
                            <strong><?php echo formatearNumero($kg_ms_por_animal, 2); ?> kg</strong>
                        </td>
                        <td>
                            <?php
                            $color_sobra = '';
                            switch($alim['sobrante_nivel']) {
                                case 'SIN_SOBRAS': $color_sobra = '#28a745'; break;
                                case 'POCAS_SOBRAS': $color_sobra = '#ffc107'; break;
                                case 'NORMAL': $color_sobra = '#17a2b8'; break;
                                case 'MUCHAS_SOBRAS': $color_sobra = '#dc3545'; break;
                            }
                            ?>
                            <span style="color: <?php echo $color_sobra; ?>; font-weight: 600; font-size: 0.85rem;">
                                <?php echo str_replace('_', ' ', $alim['sobrante_nivel']); ?>
                            </span>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
<div class="tarjeta">
    <div class="sin-datos">
        <p>No hay alimentaciones registradas en el período seleccionado.</p>
        <a href="../alimentaciones/registrar.php?lote=<?php echo $lote_filtro; ?>" class="btn btn-primario">
            Registrar Primera Alimentación
        </a>
    </div>
</div>
<?php endif; ?>

<?php else: ?>

<div class="tarjeta">
    <div class="sin-datos">
        <p>No hay lotes disponibles para mostrar reportes.</p>
        <a href="../lotes/crear.php" class="btn btn-primario">Crear Primer Lote</a>
    </div>
</div>

<?php endif; ?>

<?php include '../../includes/footer.php'; ?>