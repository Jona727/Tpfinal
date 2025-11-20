<?php
/**
 * SOLUFEED - Ver Detalle de Dieta
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

// Obtener insumos de la dieta con sus porcentajes
$query_insumos = "
    SELECT 
        i.id_insumo,
        i.nombre,
        i.tipo,
        i.porcentaje_ms,
        dd.porcentaje_teorico
    FROM dieta_detalle dd
    INNER JOIN insumo i ON dd.id_insumo = i.id_insumo
    WHERE dd.id_dieta = $id_dieta
    ORDER BY dd.porcentaje_teorico DESC
";
$insumos = ejecutarConsulta($query_insumos);

// Calcular % MS promedio de la dieta
$total_porcentaje = 0;
$ms_total = 0;

$insumos_array = [];
while ($insumo = mysqli_fetch_assoc($insumos)) {
    $insumos_array[] = $insumo;
    $total_porcentaje += $insumo['porcentaje_teorico'];
    // MS aportado = (% del insumo en la dieta * % MS del insumo) / 100
    $ms_total += ($insumo['porcentaje_teorico'] * $insumo['porcentaje_ms']) / 100;
}

// Verificar en cuántos lotes se está usando esta dieta
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

include '../../includes/header.php';
?>

<h1 class="tarjeta-titulo">📋 Detalle de Dieta</h1>

<!-- Información general de la dieta -->
<div class="tarjeta">
    <h2 style="color: #2c5530; margin-bottom: 1rem;">
        <?php echo htmlspecialchars($dieta['nombre']); ?>
        <?php if ($dieta['activo']): ?>
            <span class="estado estado-activo" style="font-size: 0.9rem; margin-left: 1rem;">Activa</span>
        <?php else: ?>
            <span class="estado estado-inactivo" style="font-size: 0.9rem; margin-left: 1rem;">Inactiva</span>
        <?php endif; ?>
    </h2>
    
    <?php if (!empty($dieta['descripcion'])): ?>
        <p style="color: #666; margin-bottom: 1rem;">
            <?php echo htmlspecialchars($dieta['descripcion']); ?>
        </p>
    <?php endif; ?>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1.5rem;">
        <div>
            <small style="color: #666;">Fecha de creación:</small><br>
            <strong><?php echo formatearFecha($dieta['fecha_creacion']); ?></strong>
        </div>
        <div>
            <small style="color: #666;">Total de insumos:</small><br>
            <strong><?php echo count($insumos_array); ?></strong>
        </div>
        <div>
            <small style="color: #666;">% MS promedio de la dieta:</small><br>
            <strong style="color: #2c5530; font-size: 1.2rem;"><?php echo formatearNumero($ms_total, 2); ?>%</strong>
        </div>
    </div>
    
    <div class="btn-grupo mt-2">
        <a href="editar.php?id=<?php echo $id_dieta; ?>" class="btn btn-primario">✏️ Editar Dieta</a>
        <a href="listar.php" class="btn btn-secundario">← Volver a Lista</a>
    </div>
</div>

<!-- Composición de la dieta -->
<div class="tarjeta">
    <h2 class="tarjeta-titulo">🌾 Composición Teórica</h2>
    
    <?php if (count($insumos_array) > 0): ?>
        
        <div class="tabla-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Insumo</th>
                        <th>Tipo</th>
                        <th>% MS del Insumo</th>
                        <th>% Teórico en la Dieta</th>
                        <th>MS Aportado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($insumos_array as $insumo): ?>
                        <?php 
                        // Calcular MS aportado por este insumo
                        $ms_aportado = ($insumo['porcentaje_teorico'] * $insumo['porcentaje_ms']) / 100;
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($insumo['nombre']); ?></strong></td>
                            <td><?php echo htmlspecialchars($insumo['tipo']); ?></td>
                            <td><?php echo formatearNumero($insumo['porcentaje_ms'], 2); ?>%</td>
                            <td>
                                <span style="background: #d4edda; padding: 0.3rem 0.8rem; border-radius: 20px; font-weight: 600; color: #155724;">
                                    <?php echo formatearNumero($insumo['porcentaje_teorico'], 2); ?>%
                                </span>
                            </td>
                            <td>
                                <strong style="color: #2c5530;">
                                    <?php echo formatearNumero($ms_aportado, 2); ?>%
                                </strong>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <!-- Fila de totales -->
                    <tr style="background: #f8f9fa; font-weight: bold;">
                        <td colspan="3" style="text-align: right;">TOTAL:</td>
                        <td>
                            <span style="background: #2c5530; color: white; padding: 0.3rem 0.8rem; border-radius: 20px;">
                                <?php echo formatearNumero($total_porcentaje, 2); ?>%
                            </span>
                        </td>
                        <td>
                            <strong style="color: #2c5530; font-size: 1.1rem;">
                                <?php echo formatearNumero($ms_total, 2); ?>%
                            </strong>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <?php if (abs($total_porcentaje - 100) > 0.01): ?>
            <div class="mensaje mensaje-error" style="margin-top: 1rem;">
                ⚠️ <strong>Advertencia:</strong> El total de porcentajes debe sumar 100%. 
                Actualmente suma <?php echo formatearNumero($total_porcentaje, 2); ?>%.
                Por favor, editá la dieta para corregir esto.
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        
        <div class="sin-datos">
            <p>Esta dieta no tiene insumos asignados todavía.</p>
            <a href="editar.php?id=<?php echo $id_dieta; ?>" class="btn btn-primario">Agregar Insumos</a>
        </div>
        
    <?php endif; ?>
</div>

<!-- Lotes que usan esta dieta -->
<div class="tarjeta">
    <h2 class="tarjeta-titulo">🐮 Lotes Asignados</h2>
    
    <?php if (mysqli_num_rows($lotes_asignados) > 0): ?>
        
        <p>Esta dieta está siendo utilizada en los siguientes lotes:</p>
        
        <div class="tabla-responsive" style="margin-top: 1rem;">
            <table>
                <thead>
                    <tr>
                        <th>Lote</th>
                        <th>Fecha Desde</th>
                        <th>Estado del Lote</th>
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
        </div>
        
    <?php else: ?>
        
        <p style="color: #999;">Esta dieta no está asignada a ningún lote actualmente.</p>
        
    <?php endif; ?>
</div>

<!-- Ejemplo de uso (kg para 50 animales) -->
<div class="tarjeta">
    <h2 class="tarjeta-titulo">📊 Ejemplo de Mezcla</h2>
    
    <p>Si querés entregar <strong>500 kg totales</strong> de esta dieta, la mezcla sería:</p>
    
    <?php if (count($insumos_array) > 0): ?>
        
        <div class="tabla-responsive" style="margin-top: 1rem;">
            <table>
                <thead>
                    <tr>
                        <th>Insumo</th>
                        <th>Kg Sugeridos</th>
                        <th>% MS</th>
                        <th>Kg MS Aportados</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $kg_totales = 500;
                    $total_kg_ms = 0;
                    foreach ($insumos_array as $insumo): 
                        $kg_insumo = ($insumo['porcentaje_teorico'] * $kg_totales) / 100;
                        $kg_ms_insumo = ($kg_insumo * $insumo['porcentaje_ms']) / 100;
                        $total_kg_ms += $kg_ms_insumo;
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($insumo['nombre']); ?></td>
                            <td><strong><?php echo formatearNumero($kg_insumo, 1); ?> kg</strong></td>
                            <td><?php echo formatearNumero($insumo['porcentaje_ms'], 2); ?>%</td>
                            <td style="color: #2c5530; font-weight: 600;">
                                <?php echo formatearNumero($kg_ms_insumo, 1); ?> kg MS
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <tr style="background: #f8f9fa; font-weight: bold;">
                        <td>TOTAL</td>
                        <td><?php echo formatearNumero($kg_totales, 0); ?> kg</td>
                        <td>-</td>
                        <td style="color: #2c5530; font-size: 1.1rem;">
                            <?php echo formatearNumero($total_kg_ms, 1); ?> kg MS
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <p style="margin-top: 1rem; color: #666;">
            <small>
                💡 Si se entregan 500 kg de esta dieta, se estarían aportando 
                <strong><?php echo formatearNumero($total_kg_ms, 1); ?> kg de Materia Seca</strong>.
            </small>
        </p>
        
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>