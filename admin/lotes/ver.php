<?php
/**
 * SOLUFEED - Ver Detalle de Lote
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

$id_tropa = (int) $_GET['id'];

// Obtener datos del lote
$query_lote = "
    SELECT 
        t.*,
        c.nombre as campo_nombre,
        c.ubicacion as campo_ubicacion
    FROM tropa t
    INNER JOIN campo c ON t.id_campo = c.id_campo
    WHERE t.id_tropa = $id_tropa
";
$resultado_lote = ejecutarConsulta($query_lote);

if (mysqli_num_rows($resultado_lote) === 0) {
    header('Location: listar.php');
    exit();
}

$lote = mysqli_fetch_assoc($resultado_lote);

// Obtener dieta vigente
$dieta_vigente = obtenerDietaVigente($id_tropa);

// Calcular animales presentes
$animales_presentes = obtenerAnimalesPresentes($id_tropa);

// Calcular días de engorde
$fecha_inicio = new DateTime($lote['fecha_inicio']);
$fecha_hoy = new DateTime();
$dias_engorde = $fecha_inicio->diff($fecha_hoy)->days;

// Obtener pesadas del lote
$query_pesadas = "
    SELECT fecha, peso_promedio, animales_vistos
    FROM pesada
    WHERE id_tropa = $id_tropa
    ORDER BY fecha DESC
    LIMIT 10
";
$pesadas = ejecutarConsulta($query_pesadas);

// Calcular peso promedio inicial y actual
$peso_inicial = null;
$peso_actual = null;
$adpv = null;

$query_peso_inicial = "SELECT peso_promedio FROM pesada WHERE id_tropa = $id_tropa ORDER BY fecha ASC LIMIT 1";
$resultado_peso_inicial = ejecutarConsulta($query_peso_inicial);
if (mysqli_num_rows($resultado_peso_inicial) > 0) {
    $peso_inicial = mysqli_fetch_assoc($resultado_peso_inicial)['peso_promedio'];
}

$query_peso_actual = "SELECT peso_promedio, fecha FROM pesada WHERE id_tropa = $id_tropa ORDER BY fecha DESC LIMIT 1";
$resultado_peso_actual = ejecutarConsulta($query_peso_actual);
if (mysqli_num_rows($resultado_peso_actual) > 0) {
    $dato_peso_actual = mysqli_fetch_assoc($resultado_peso_actual);
    $peso_actual = $dato_peso_actual['peso_promedio'];
    
    // Calcular ADPV si hay peso inicial y actual
    if ($peso_inicial && $peso_actual && $dias_engorde > 0) {
        $adpv = ($peso_actual - $peso_inicial) / $dias_engorde;
    }
}

// Obtener últimas alimentaciones
$query_alimentaciones = "
    SELECT 
        cl.fecha,
        cl.hora,
        cl.kg_totales_tirados,
        cl.sobrante_nivel,
        cl.animales_presentes
    FROM consumo_lote cl
    WHERE cl.id_tropa = $id_tropa
    ORDER BY cl.fecha DESC, cl.hora DESC
    LIMIT 10
";
$alimentaciones = ejecutarConsulta($query_alimentaciones);

// Obtener movimientos de animales
$query_movimientos = "
    SELECT 
        fecha,
        tipo_movimiento,
        cantidad,
        motivo
    FROM movimiento_animal
    WHERE id_tropa = $id_tropa
    ORDER BY fecha DESC
    LIMIT 10
";
$movimientos = ejecutarConsulta($query_movimientos);

// Calcular consumo total de MS
$query_ms_total = "
    SELECT IFNULL(SUM(kg_ms), 0) as total_kg_ms
    FROM consumo_lote_detalle cld
    INNER JOIN consumo_lote cl ON cld.id_consumo = cl.id_consumo
    WHERE cl.id_tropa = $id_tropa
";
$resultado_ms = ejecutarConsulta($query_ms_total);
$total_kg_ms = mysqli_fetch_assoc($resultado_ms)['total_kg_ms'];

// Calcular CMS promedio
$cms_promedio = null;
if ($dias_engorde > 0 && $animales_presentes > 0) {
    $cms_promedio = $total_kg_ms / ($dias_engorde * $animales_presentes);
}

include '../../includes/header.php';
?>

<h1 class="tarjeta-titulo">🐮 Detalle del Lote</h1>

<!-- Información general del lote -->
<div class="tarjeta">
    <h2 style="color: #2c5530; margin-bottom: 1rem;">
        <?php echo htmlspecialchars($lote['nombre']); ?>
        <?php if ($lote['activo']): ?>
            <span class="estado estado-activo" style="font-size: 0.9rem; margin-left: 1rem;">Activo</span>
        <?php else: ?>
            <span class="estado estado-inactivo" style="font-size: 0.9rem; margin-left: 1rem;">Inactivo</span>
        <?php endif; ?>
    </h2>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
        
        <div>
            <small style="color: #666;">Campo:</small><br>
            <strong><?php echo htmlspecialchars($lote['campo_nombre']); ?></strong>
            <?php if (!empty($lote['campo_ubicacion'])): ?>
                <br><small style="color: #999;"><?php echo htmlspecialchars($lote['campo_ubicacion']); ?></small>
            <?php endif; ?>
        </div>
        
        <div>
            <small style="color: #666;">Categoría:</small><br>
            <strong><?php echo htmlspecialchars($lote['categoria']); ?></strong>
        </div>
        
        <div>
            <small style="color: #666;">Fecha de Inicio:</small><br>
            <strong><?php echo formatearFecha($lote['fecha_inicio']); ?></strong>
            <br><small style="color: #999;"><?php echo $dias_engorde; ?> días en feedlot</small>
        </div>
        
    </div>
    
    <div class="btn-grupo">
        <a href="editar.php?id=<?php echo $id_tropa; ?>" class="btn btn-primario">✏️ Editar Lote</a>
        <a href="../alimentaciones/registrar.php?lote=<?php echo $id_tropa; ?>" class="btn btn-secundario">🍽️ Registrar Alimentación</a>
        <a href="../pesadas/registrar.php?lote=<?php echo $id_tropa; ?>" class="btn btn-secundario">⚖️ Registrar Pesada</a>
        <a href="listar.php" class="btn btn-secundario">← Volver</a>
    </div>
</div>

<!-- Indicadores clave -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
    
    <div class="indicador">
        <div class="indicador-icono">🐄</div>
        <div class="indicador-valor"><?php echo $animales_presentes; ?></div>
        <div class="indicador-label">Animales Presentes</div>
        <?php if ($animales_presentes != $lote['cantidad_inicial']): ?>
            <small style="color: #999;">Inicial: <?php echo $lote['cantidad_inicial']; ?></small>
        <?php endif; ?>
    </div>
    
    <?php if ($peso_inicial): ?>
    <div class="indicador">
        <div class="indicador-icono">⚖️</div>
        <div class="indicador-valor"><?php echo formatearNumero($peso_inicial, 0); ?> kg</div>
        <div class="indicador-label">Peso Inicial Promedio</div>
    </div>
    <?php endif; ?>
    
    <?php if ($peso_actual): ?>
    <div class="indicador">
        <div class="indicador-icono">📊</div>
        <div class="indicador-valor"><?php echo formatearNumero($peso_actual, 0); ?> kg</div>
        <div class="indicador-label">Peso Actual Promedio</div>
    </div>
    <?php endif; ?>
    
    <?php if ($adpv): ?>
    <div class="indicador">
        <div class="indicador-icono">📈</div>
        <div class="indicador-valor"><?php echo formatearNumero($adpv, 2); ?></div>
        <div class="indicador-label">ADPV (kg/día)</div>
    </div>
    <?php endif; ?>
    
    <?php if ($cms_promedio): ?>
    <div class="indicador">
        <div class="indicador-icono">🌾</div>
        <div class="indicador-valor"><?php echo formatearNumero($cms_promedio, 2); ?></div>
        <div class="indicador-label">CMS Promedio (kg MS/día)</div>
    </div>
    <?php endif; ?>
    
    <div class="indicador">
        <div class="indicador-icono">📅</div>
        <div class="indicador-valor"><?php echo $dias_engorde; ?></div>
        <div class="indicador-label">Días de Engorde</div>
    </div>
    
</div>

<!-- Dieta vigente -->
<div class="tarjeta">
    <h2 class="tarjeta-titulo">📋 Dieta Vigente</h2>
    
    <?php if ($dieta_vigente): ?>
        
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="color: #2c5530; margin-bottom: 0.5rem;">
                    <?php echo htmlspecialchars($dieta_vigente['dieta_nombre']); ?>
                </h3>
                <small style="color: #666;">
                    Asignada desde: <?php echo formatearFecha($dieta_vigente['fecha_desde']); ?>
                </small>
            </div>
            <a href="../dietas/ver.php?id=<?php echo $dieta_vigente['id_dieta']; ?>" 
               class="btn btn-secundario btn-pequeno">Ver Composición</a>
        </div>
        
    <?php else: ?>
        
        <div class="sin-datos">
            <p>Este lote no tiene dieta asignada actualmente.</p>
            <a href="editar.php?id=<?php echo $id_tropa; ?>" class="btn btn-primario">Asignar Dieta</a>
        </div>
        
    <?php endif; ?>
</div>

<!-- Últimas Pesadas -->
<div class="tarjeta">
    <h2 class="tarjeta-titulo">⚖️ Últimas Pesadas</h2>
    
    <?php if (mysqli_num_rows($pesadas) > 0): ?>
        
        <div class="tabla-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Peso Promedio</th>
                        <th>Animales Vistos</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($pesada = mysqli_fetch_assoc($pesadas)): ?>
                        <tr>
                            <td><?php echo formatearFecha($pesada['fecha']); ?></td>
                            <td><strong><?php echo formatearNumero($pesada['peso_promedio'], 2); ?> kg</strong></td>
                            <td><?php echo $pesada['animales_vistos']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
    <?php else: ?>
        
        <div class="sin-datos">
            <p>No hay pesadas registradas para este lote todavía.</p>
            <a href="../pesadas/registrar.php?lote=<?php echo $id_tropa; ?>" class="btn btn-primario">Registrar Primera Pesada</a>
        </div>
        
    <?php endif; ?>
</div>

<!-- Últimas Alimentaciones -->
<div class="tarjeta">
    <h2 class="tarjeta-titulo">🍽️ Últimas Alimentaciones</h2>
    
    <?php if (mysqli_num_rows($alimentaciones) > 0): ?>
        
        <div class="tabla-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Hora</th>
                        <th>Kg Totales</th>
                        <th>Kg/Animal</th>
                        <th>Animales</th>
                        <th>Sobras</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($alim = mysqli_fetch_assoc($alimentaciones)): ?>
                        <?php 
                        $kg_por_animal = $alim['animales_presentes'] > 0 
                            ? $alim['kg_totales_tirados'] / $alim['animales_presentes'] 
                            : 0;
                        ?>
                        <tr>
                            <td><?php echo formatearFecha($alim['fecha']); ?></td>
                            <td><?php echo date('H:i', strtotime($alim['hora'])); ?></td>
                            <td><?php echo formatearNumero($alim['kg_totales_tirados'], 0); ?> kg</td>
                            <td><strong><?php echo formatearNumero($kg_por_animal, 2); ?> kg</strong></td>
                            <td><?php echo $alim['animales_presentes']; ?></td>
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
                                <span style="color: <?php echo $color_sobra; ?>; font-weight: 600;">
                                    <?php echo str_replace('_', ' ', $alim['sobrante_nivel']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
    <?php else: ?>
        
        <div class="sin-datos">
            <p>No hay alimentaciones registradas para este lote todavía.</p>
            <a href="../alimentaciones/registrar.php?lote=<?php echo $id_tropa; ?>" class="btn btn-primario">Registrar Primera Alimentación</a>
        </div>
        
    <?php endif; ?>
</div>

<!-- Movimientos de Animales -->
<?php if (mysqli_num_rows($movimientos) > 0): ?>
<div class="tarjeta">
    <h2 class="tarjeta-titulo">📝 Historial de Movimientos</h2>
    
    <div class="tabla-responsive">
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Cantidad</th>
                    <th>Motivo</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($mov = mysqli_fetch_assoc($movimientos)): ?>
                    <tr>
                        <td><?php echo formatearFecha($mov['fecha']); ?></td>
                        <td>
                            <?php
                            $color_mov = '';
                            switch($mov['tipo_movimiento']) {
                                case 'ENTRADA':
                                case 'AJUSTE_POSITIVO':
                                    $color_mov = '#28a745';
                                    break;
                                case 'SALIDA':
                                case 'BAJA':
                                case 'AJUSTE_NEGATIVO':
                                    $color_mov = '#dc3545';
                                    break;
                                default:
                                    $color_mov = '#17a2b8';
                            }
                            ?>
                            <span style="color: <?php echo $color_mov; ?>; font-weight: 600;">
                                <?php echo str_replace('_', ' ', $mov['tipo_movimiento']); ?>
                            </span>
                        </td>
                        <td><strong><?php echo $mov['cantidad']; ?></strong></td>
                        <td><?php echo htmlspecialchars($mov['motivo']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>