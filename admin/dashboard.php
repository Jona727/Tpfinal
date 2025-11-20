<?php
/**
 * SOLUFEED - Dashboard Principal
 * Vista general con indicadores clave
 */

// Incluir archivos necesarios
require_once '../config/database.php';
require_once '../includes/functions.php';

// Por ahora simulamos sesión de admin (cuando implementes login, esto vendrá de la sesión real)
$_SESSION['usuario_id'] = 1;
$_SESSION['nombre'] = 'Administrador';
$_SESSION['tipo'] = 'ADMIN';

// Incluir header
include '../includes/header.php';

// ========================================
// OBTENER DATOS PARA EL DASHBOARD
// ========================================

// 1. Total de lotes activos
$query_lotes = "SELECT COUNT(*) as total FROM tropa WHERE activo = 1";
$resultado_lotes = ejecutarConsulta($query_lotes);
$total_lotes = mysqli_fetch_assoc($resultado_lotes)['total'];

// 2. Total de animales en feedlot (suma de animales presentes de todos los lotes activos)
$total_animales = 0;
$query_tropas = "SELECT id_tropa FROM tropa WHERE activo = 1";
$resultado_tropas = ejecutarConsulta($query_tropas);
while ($tropa = mysqli_fetch_assoc($resultado_tropas)) {
    $total_animales += obtenerAnimalesPresentes($tropa['id_tropa']);
}

// 3. Total de insumos registrados
$query_insumos = "SELECT COUNT(*) as total FROM insumo WHERE activo = 1";
$resultado_insumos = ejecutarConsulta($query_insumos);
$total_insumos = mysqli_fetch_assoc($resultado_insumos)['total'];

// 4. Total de dietas creadas
$query_dietas = "SELECT COUNT(*) as total FROM dieta WHERE activo = 1";
$resultado_dietas = ejecutarConsulta($query_dietas);
$total_dietas = mysqli_fetch_assoc($resultado_dietas)['total'];

// 5. Alimentaciones registradas hoy
$query_alimentaciones_hoy = "SELECT COUNT(*) as total FROM consumo_lote WHERE fecha = CURDATE()";
$resultado_alimentaciones_hoy = ejecutarConsulta($query_alimentaciones_hoy);
$alimentaciones_hoy = mysqli_fetch_assoc($resultado_alimentaciones_hoy)['total'];

// 6. Total de kg entregados hoy
$query_kg_hoy = "SELECT IFNULL(SUM(kg_totales_tirados), 0) as total FROM consumo_lote WHERE fecha = CURDATE()";
$resultado_kg_hoy = ejecutarConsulta($query_kg_hoy);
$kg_totales_hoy = mysqli_fetch_assoc($resultado_kg_hoy)['total'];

// 7. Últimas alimentaciones (últimas 5)
$query_ultimas_alim = "
    SELECT 
        cl.id_consumo,
        cl.fecha,
        cl.hora,
        cl.kg_totales_tirados,
        cl.sobrante_nivel,
        t.nombre as lote_nombre,
        cl.animales_presentes
    FROM consumo_lote cl
    INNER JOIN tropa t ON cl.id_tropa = t.id_tropa
    ORDER BY cl.fecha DESC, cl.hora DESC
    LIMIT 5
";
$ultimas_alimentaciones = ejecutarConsulta($query_ultimas_alim);

// 8. Lotes activos con datos
$query_lotes_detalle = "
    SELECT 
        t.id_tropa,
        t.nombre,
        t.categoria,
        t.cantidad_inicial,
        c.nombre as campo_nombre,
        d.nombre as dieta_nombre,
        (SELECT MAX(p.fecha) FROM pesada p WHERE p.id_tropa = t.id_tropa) as ultima_pesada,
        (SELECT MAX(cl.fecha) FROM consumo_lote cl WHERE cl.id_tropa = t.id_tropa) as ultima_alimentacion
    FROM tropa t
    INNER JOIN campo c ON t.id_campo = c.id_campo
    LEFT JOIN tropa_dieta_asignada tda ON t.id_tropa = tda.id_tropa 
        AND tda.fecha_desde <= CURDATE() 
        AND (tda.fecha_hasta IS NULL OR tda.fecha_hasta >= CURDATE())
    LEFT JOIN dieta d ON tda.id_dieta = d.id_dieta
    WHERE t.activo = 1
    ORDER BY t.fecha_creacion DESC
";
$lotes_detalle = ejecutarConsulta($query_lotes_detalle);

?>

<!-- ========================================
     CONTENIDO DEL DASHBOARD
     ======================================== -->

<h1 class="tarjeta-titulo">📊 Dashboard - Panel de Control</h1>

<!-- Indicadores principales -->
<div class="indicadores-grid">
    
    <!-- Indicador 1: Lotes Activos -->
    <div class="indicador">
        <div class="indicador-icono">🐮</div>
        <div class="indicador-valor"><?php echo $total_lotes; ?></div>
        <div class="indicador-label">Lotes Activos</div>
    </div>
    
    <!-- Indicador 2: Total de Animales -->
    <div class="indicador">
        <div class="indicador-icono">🐄</div>
        <div class="indicador-valor"><?php echo $total_animales; ?></div>
        <div class="indicador-label">Animales en Feedlot</div>
    </div>
    
    <!-- Indicador 3: Insumos -->
    <div class="indicador">
        <div class="indicador-icono">🌾</div>
        <div class="indicador-valor"><?php echo $total_insumos; ?></div>
        <div class="indicador-label">Insumos Disponibles</div>
    </div>
    
    <!-- Indicador 4: Dietas -->
    <div class="indicador">
        <div class="indicador-icono">📋</div>
        <div class="indicador-valor"><?php echo $total_dietas; ?></div>
        <div class="indicador-label">Dietas Configuradas</div>
    </div>
    
    <!-- Indicador 5: Alimentaciones Hoy -->
    <div class="indicador">
        <div class="indicador-icono">🍽️</div>
        <div class="indicador-valor"><?php echo $alimentaciones_hoy; ?></div>
        <div class="indicador-label">Alimentaciones Hoy</div>
    </div>
    
    <!-- Indicador 6: Kg entregados hoy -->
    <div class="indicador">
        <div class="indicador-icono">⚖️</div>
        <div class="indicador-valor"><?php echo formatearNumero($kg_totales_hoy, 0); ?></div>
        <div class="indicador-label">Kg Entregados Hoy</div>
    </div>
    
</div>

<!-- ========================================
     LOTES ACTIVOS
     ======================================== -->
<div class="tarjeta">
    <h2 class="tarjeta-titulo">🐮 Lotes Activos</h2>
    
    <?php if (mysqli_num_rows($lotes_detalle) > 0): ?>
        
        <div class="tabla-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Lote</th>
                        <th>Campo</th>
                        <th>Categoría</th>
                        <th>Animales</th>
                        <th>Dieta Asignada</th>
                        <th>Última Pesada</th>
                        <th>Última Alimentación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($lote = mysqli_fetch_assoc($lotes_detalle)): ?>
                        <?php $animales_presentes = obtenerAnimalesPresentes($lote['id_tropa']); ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($lote['nombre']); ?></strong></td>
                            <td><?php echo htmlspecialchars($lote['campo_nombre']); ?></td>
                            <td><?php echo htmlspecialchars($lote['categoria']); ?></td>
                            <td><?php echo $animales_presentes; ?></td>
                            <td>
                                <?php if ($lote['dieta_nombre']): ?>
                                    <span style="color: #2c5530;">✓ <?php echo htmlspecialchars($lote['dieta_nombre']); ?></span>
                                <?php else: ?>
                                    <span style="color: #dc3545;">⚠ Sin dieta</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                if ($lote['ultima_pesada']) {
                                    echo formatearFecha($lote['ultima_pesada']);
                                } else {
                                    echo '<span style="color: #999;">Sin datos</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                if ($lote['ultima_alimentacion']) {
                                    echo formatearFecha($lote['ultima_alimentacion']);
                                } else {
                                    echo '<span style="color: #999;">Sin datos</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <div class="tabla-acciones">
                                    <a href="/solufeed/admin/alimentaciones/registrar.php?lote=<?php echo $lote['id_tropa']; ?>" 
                                       class="btn btn-primario btn-pequeno">🍽️ Alimentar</a>
                                    <a href="/solufeed/admin/pesadas/registrar.php?lote=<?php echo $lote['id_tropa']; ?>" 
                                       class="btn btn-secundario btn-pequeno">⚖️ Pesar</a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
    <?php else: ?>
        <div class="sin-datos">
            <p>No hay lotes activos registrados.</p>
            <a href="/solufeed/admin/lotes/crear.php" class="btn btn-primario">Crear Primer Lote</a>
        </div>
    <?php endif; ?>
    
</div>

<!-- ========================================
     ÚLTIMAS ALIMENTACIONES
     ======================================== -->
<div class="tarjeta">
    <h2 class="tarjeta-titulo">🍽️ Últimas Alimentaciones</h2>
    
    <?php if (mysqli_num_rows($ultimas_alimentaciones) > 0): ?>
        
        <div class="tabla-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Hora</th>
                        <th>Lote</th>
                        <th>Animales</th>
                        <th>Kg Totales</th>
                        <th>Kg/Animal</th>
                        <th>Sobras</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($alim = mysqli_fetch_assoc($ultimas_alimentaciones)): ?>
                        <?php 
                        $kg_por_animal = $alim['animales_presentes'] > 0 
                            ? $alim['kg_totales_tirados'] / $alim['animales_presentes'] 
                            : 0;
                        
                        // Color según nivel de sobras
                        $color_sobra = '';
                        switch($alim['sobrante_nivel']) {
                            case 'SIN_SOBRAS': $color_sobra = '#28a745'; break;
                            case 'POCAS_SOBRAS': $color_sobra = '#ffc107'; break;
                            case 'NORMAL': $color_sobra = '#17a2b8'; break;
                            case 'MUCHAS_SOBRAS': $color_sobra = '#dc3545'; break;
                        }
                        ?>
                        <tr>
                            <td><?php echo formatearFecha($alim['fecha']); ?></td>
                            <td><?php echo date('H:i', strtotime($alim['hora'])); ?></td>
                            <td><strong><?php echo htmlspecialchars($alim['lote_nombre']); ?></strong></td>
                            <td><?php echo $alim['animales_presentes']; ?></td>
                            <td><?php echo formatearNumero($alim['kg_totales_tirados'], 0); ?> kg</td>
                            <td><?php echo formatearNumero($kg_por_animal, 2); ?> kg</td>
                            <td>
                                <span style="color: <?php echo $color_sobra; ?>; font-weight: 600;">
                                    <?php echo str_replace('_', ' ', $alim['sobrante_nivel']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <div class="texto-centro mt-2">
            <a href="/solufeed/admin/reportes/consumo.php" class="btn btn-secundario">Ver Todos los Reportes</a>
        </div>
        
    <?php else: ?>
        <div class="sin-datos">
            <p>No hay alimentaciones registradas aún.</p>
            <a href="/solufeed/admin/alimentaciones/registrar.php" class="btn btn-primario">Registrar Primera Alimentación</a>
        </div>
    <?php endif; ?>
    
</div>

<!-- ========================================
     ACCESOS RÁPIDOS
     ======================================== -->
<div class="tarjeta">
    <h2 class="tarjeta-titulo">⚡ Accesos Rápidos</h2>
    
    <div class="btn-grupo">
        <a href="/solufeed/admin/insumos/crear.php" class="btn btn-primario">🌾 Nuevo Insumo</a>
        <a href="/solufeed/admin/dietas/crear.php" class="btn btn-primario">📋 Nueva Dieta</a>
        <a href="/solufeed/admin/lotes/crear.php" class="btn btn-primario">🐮 Nuevo Lote</a>
        <a href="/solufeed/admin/alimentaciones/registrar.php" class="btn btn-secundario">🍽️ Registrar Alimentación</a>
        <a href="/solufeed/admin/pesadas/registrar.php" class="btn btn-secundario">⚖️ Registrar Pesada</a>
    </div>
</div>

<?php
// Incluir footer
include '../includes/footer.php';
?>