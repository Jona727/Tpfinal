<?php
// admin/dashboard.php - VERSIÓN REFACTORIZADA
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/StatsModel.php';

// Verificar sesión y rol ADMIN
verificarAdmin();

$page_title = "Dashboard - Panel de Control";
$stats = new StatsModel();

// Obtener datos
$indicadores = $stats->getMainIndicators();
$hoyStats = $stats->getTodayStats();
$adpv = $stats->getAdpvAvg();
$cms = $stats->getCmsAvg();
$eficiencia = ($cms > 0) ? ($adpv / $cms) : 0; // kg carne / kg MS
$alertas = $stats->getAlerts();
$lotesActivos = $stats->getTopActiveLotes(5);
$ultimasAlimentaciones = $stats->getLastFeedings(5);
$datosPeso = $stats->getWeightEvolutionData();
$datosMs = $stats->getMsConsumptionData();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h1 style="font-weight: 800; color: var(--primary); letter-spacing: -1px;">📊 Panel de Control</h1>
        <div class="user-badge" style="background: white; padding: 0.5rem 1rem; border-radius: 50px; box-shadow: var(--shadow-sm); display: flex; align-items: center; gap: 10px; border: 1px solid var(--border);">
            <div style="width: 32px; height: 32px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                <?php echo substr($_SESSION['nombre'], 0, 1); ?>
            </div>
            <span style="font-weight: 600; font-size: 0.9rem;"><?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
        </div>
    </div>

    <!-- ALERTAS -->
    <?php if ($alertas['lotes_sin_dieta'] > 0 || $alertas['ajustes_pendientes'] > 0 || $alertas['lotes_sin_alimentar'] > 0): ?>
        <div class="alertas-container" style="margin-bottom: 2rem;">
            <?php if ($alertas['lotes_sin_alimentar'] > 0): ?>
                <div class="alerta" style="background: #fff3cd; border-left: 5px solid var(--warning); padding: 1.25rem; border-radius: var(--radius); margin-bottom: 1rem; display: flex; align-items: center; gap: 15px; box-shadow: var(--shadow-sm);">
                    <div class="icono" style="font-size: 1.5rem;">⚠️</div>
                    <div class="contenido">
                        <strong style="color: #856404;">Atención: <?php echo $alertas['lotes_sin_alimentar']; ?> lote(s) sin alimentar hoy</strong>
                        <span style="font-size: 0.9rem; color: #856404; opacity: 0.8;">Hay lotes que aún no han recibido su ración diaria.</span>
                    </div>
                    <a href="lotes/listar.php?filtro=sin_alimentar" class="btn btn-secondary" style="margin-left: auto; font-size: 0.8rem; padding: 0.5rem 1rem;">Gestionar</a>
                </div>
            <?php endif; ?>

            <?php if ($alertas['lotes_sin_dieta'] > 0): ?>
                <div class="alerta" style="background: #eef2ff; border-left: 5px solid var(--secondary); padding: 1.25rem; border-radius: var(--radius); margin-bottom: 1rem; display: flex; align-items: center; gap: 15px; box-shadow: var(--shadow-sm);">
                    <div class="icono" style="font-size: 1.5rem;">📋</div>
                    <div class="contenido">
                        <strong style="color: var(--secondary);"><?php echo $alertas['lotes_sin_dieta']; ?> lote(s) sin dieta asignada</strong>
                        <span style="font-size: 0.9rem; color: var(--secondary); opacity: 0.8;">Es necesario asignar una dieta para poder registrar consumos.</span>
                    </div>
                    <a href="lotes/listar.php?filtro=sin_dieta" class="btn btn-secondary" style="margin-left: auto; font-size: 0.8rem; padding: 0.5rem 1rem;">Asignar</a>
                </div>
            <?php endif; ?>

            <?php if ($alertas['ajustes_pendientes'] > 0): ?>
                <div class="alerta" style="background: #fef2f2; border-left: 5px solid var(--danger); padding: 1.25rem; border-radius: var(--radius); margin-bottom: 1rem; display: flex; align-items: center; gap: 15px; box-shadow: var(--shadow-sm);">
                    <div class="icono" style="font-size: 1.5rem;">🚨</div>
                    <div class="contenido">
                        <strong style="color: var(--danger);"><?php echo $alertas['ajustes_pendientes']; ?> ajuste(s) pendiente(s)</strong>
                        <span style="font-size: 0.9rem; color: var(--danger); opacity: 0.8;">Se detectaron diferencias de animales en las pesadas que deben ser validadas.</span>
                    </div>
                    <a href="campo/ajustes_pendientes.php" class="btn btn-primary" style="margin-left: auto; font-size: 0.8rem; padding: 0.5rem 1rem; background: var(--danger);">Revisar</a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- INDICADORES PRINCIPALES -->
    <div class="indicadores" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <div class="card" style="background: linear-gradient(135deg, white 0%, #f0fdf4 100%); border: none; padding: 1.5rem; position: relative; overflow: hidden;">
            <div style="position: absolute; right: -10px; bottom: -10px; font-size: 5rem; opacity: 0.05; transform: rotate(-15deg);">🐮</div>
            <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem; letter-spacing: 0.5px;">Lotes Activos</div>
            <div style="font-size: 2.2rem; font-weight: 800; color: var(--primary);"><?php echo $indicadores['lotes_activos']; ?></div>
            <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.5rem;">En engorde actual</div>
        </div>

        <div class="card" style="background: linear-gradient(135deg, white 0%, #eff6ff 100%); border: none; padding: 1.5rem; position: relative; overflow: hidden;">
            <div style="position: absolute; right: -10px; bottom: -10px; font-size: 5rem; opacity: 0.05; transform: rotate(-15deg);">🐄</div>
            <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem; letter-spacing: 0.5px;">Stock Total</div>
            <div style="font-size: 2.2rem; font-weight: 800; color: var(--secondary);"><?php echo number_format($indicadores['total_animales']); ?></div>
            <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.5rem;">Cabezas totales</div>
        </div>

        <div class="card" style="background: linear-gradient(135deg, white 0%, #fff7ed 100%); border: none; padding: 1.5rem; position: relative; overflow: hidden;">
            <div style="position: absolute; right: -10px; bottom: -10px; font-size: 5rem; opacity: 0.05; transform: rotate(-15deg);">🍽️</div>
            <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem; letter-spacing: 0.5px;">Alimentaciones Hoy</div>
            <div style="font-size: 2.2rem; font-weight: 800; color: #c2410c;"><?php echo $hoyStats['alimentaciones']; ?></div>
            <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.5rem;">Registros del día</div>
        </div>

        <div class="card" style="background: linear-gradient(135deg, white 0%, #fdf2f8 100%); border: none; padding: 1.5rem; position: relative; overflow: hidden;">
            <div style="position: absolute; right: -10px; bottom: -10px; font-size: 5rem; opacity: 0.05; transform: rotate(-15deg);">⚖️</div>
            <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem; letter-spacing: 0.5px;">Kg Entregados Hoy</div>
            <div style="font-size: 2.2rem; font-weight: 800; color: #be185d;"><?php echo number_format($hoyStats['kg_totales'], 0); ?></div>
            <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.5rem;">Total Bruto (kg)</div>
        </div>

        <div class="card" style="background: linear-gradient(135deg, white 0%, #fefce8 100%); border: none; padding: 1.5rem; position: relative; overflow: hidden;">
            <div style="position: absolute; right: -10px; bottom: -10px; font-size: 5rem; opacity: 0.05; transform: rotate(-15deg);">📈</div>
            <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem; letter-spacing: 0.5px;">ADPV Promedio</div>
            <div style="font-size: 2.2rem; font-weight: 800; color: #854d0e;"><?php echo number_format($adpv, 2); ?> <span style="font-size: 1rem;">kg</span></div>
            <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.5rem;">Dernieros 30 días</div>
        </div>

        <div class="card" style="background: linear-gradient(135deg, white 0%, #f0f9ff 100%); border: none; padding: 1.5rem; position: relative; overflow: hidden;">
            <div style="position: absolute; right: -10px; bottom: -10px; font-size: 5rem; opacity: 0.05; transform: rotate(-15deg);">🌾</div>
            <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem; letter-spacing: 0.5px;">CMS Promedio</div>
            <div style="font-size: 2.2rem; font-weight: 800; color: #075985;"><?php echo number_format($cms, 2); ?> <span style="font-size: 1rem;">kg</span></div>
            <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.5rem;">Consumo MS/cab/día</div>
        </div>
    </div>

        </div>
    </div>

    <!-- GRÁFICOS -->
    <div class="hub-grid" style="margin-bottom: 2rem;">
        <div class="card">
            <h3 class="card-title">📈 Evolución de Peso</h3>
            <div style="height: 300px;">
                <canvas id="graficoPeso"></canvas>
            </div>
        </div>

        <div class="card">
            <h3 class="card-title">🌾 Consumo de MS</h3>
            <div style="height: 300px;">
                <canvas id="graficoMS"></canvas>
            </div>
        </div>
    </div>

    <!-- TABLAS -->
    <div style="display: grid; grid-template-columns: 1fr; gap: 2rem;">
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 class="card-title" style="margin-bottom: 0;">🐮 Gestión de Lotes Activos</h3>
                <a href="lotes/listar.php" class="btn btn-secondary" style="font-size: 0.8rem;">Ver todos</a>
            </div>
            <?php if (count($lotesActivos) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Lote</th>
                                <th>Ubicación</th>
                                <th>Stock</th>
                                <th>Dieta</th>
                                <th>Último Peso</th>
                                <th>Días</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lotesActivos as $lote): ?>
                                <tr>
                                    <td><strong style="color: var(--primary);"><?php echo htmlspecialchars($lote['nombre']); ?></strong></td>
                                    <td><span style="font-size: 0.85rem; color: var(--text-muted);"><?php echo htmlspecialchars($lote['campo'] ?? 'Sin campo'); ?></span></td>
                                    <td><span style="font-weight: 700;"><?php echo $lote['animales']; ?></span></td>
                                    <td>
                                        <?php if ($lote['dieta']): ?>
                                            <span style="background: var(--primary); color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600;"><?php echo htmlspecialchars($lote['dieta']); ?></span>
                                        <?php else: ?>
                                            <span style="color: var(--danger); font-size: 0.75rem; font-weight: 600;">⚠️ Sin dieta</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($lote['ultimo_peso']): ?>
                                            <span style="font-weight: 600;"><?php echo number_format($lote['ultimo_peso'], 1); ?> kg</span>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted);">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span style="color: var(--text-muted); font-size: 0.85rem;"><?php echo $lote['dias_feedlot']; ?> d</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 3rem; color: var(--text-muted);">No hay lotes activos.</div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3 class="card-title">🍽️ Últimos Registros de Alimentación</h3>
            <?php if (count($ultimasAlimentaciones) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Fecha/Hora</th>
                                <th>Lote</th>
                                <th>Kg Brutos</th>
                                <th>Kg/Animal</th>
                                <th>Operario</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimasAlimentaciones as $alim): ?>
                                <tr>
                                    <td style="font-size: 0.85rem;">
                                        <strong><?php echo date('d/m', strtotime($alim['fecha'])); ?></strong> 
                                        <span style="color: var(--text-muted);"><?php echo date('H:i', strtotime($alim['hora'])); ?></span>
                                    </td>
                                    <td><strong style="color: var(--secondary);"><?php echo htmlspecialchars($alim['lote']); ?></strong></td>
                                    <td><span style="font-weight: 700;"><?php echo number_format($alim['kg_totales_tirados'], 0, ',', '.'); ?> kg</span></td>
                                    <td><span style="color: var(--primary); font-weight: 600;"><?php echo number_format($alim['kg_totales_tirados'] / $alim['animales_presentes'], 2); ?></span> <small>kg/cab</small></td>
                                    <td><span style="font-size: 0.85rem; color: var(--text-muted);"><?php echo htmlspecialchars($alim['operario'] ?? 'Mixer'); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 3rem; color: var(--text-muted);">No hay alimentaciones recientes.</div>
            <?php endif; ?>
        </div>
    </div>

    </div>
</div>

<script>
// Gráficos (Datos inyectados desde PHP)
const dataPeso = <?php echo json_encode($datosPeso); ?>;
const dataMs = <?php echo json_encode($datosMs); ?>;

const formatData = (data, keyLabel, keyValue) => {
    return {
        labels: data.map(d => new Date(d[keyLabel]).toLocaleDateString('es-ES', {day: '2-digit', month: '2-digit'})),
        values: data.map(d => d[keyValue])
    };
};

const processedPeso = formatData(dataPeso, 'fecha', 'peso_promedio');
const processedMs = formatData(dataMs, 'fecha', 'ms_total');

// Gráfico Peso
new Chart(document.getElementById('graficoPeso').getContext('2d'), {
    type: 'line',
    data: {
        labels: processedPeso.labels,
        datasets: [{
            label: 'Peso Promedio (kg)',
            data: processedPeso.values,
            borderColor: '#2c5530',
            backgroundColor: 'rgba(44, 85, 48, 0.1)',
            tension: 0.4,
            fill: true,
            pointRadius: 4,
            pointBackgroundColor: '#2c5530',
            borderWidth: 3
        }]
    },
    options: { 
        responsive: true, 
        maintainAspectRatio: false, 
        plugins: { legend: { display: false } },
        scales: {
            y: {
                grid: { display: true, color: 'rgba(0,0,0,0.05)' },
                ticks: { font: { family: 'Outfit' } }
            },
            x: {
                grid: { display: false },
                ticks: { font: { family: 'Outfit' } }
            }
        }
    }
});

// Gráfico MS
new Chart(document.getElementById('graficoMS').getContext('2d'), {
    type: 'bar',
    data: {
        labels: processedMs.labels,
        datasets: [{
            label: 'MS Total (kg)',
            data: processedMs.values,
            backgroundColor: '#1e6091',
            borderRadius: 8,
            maxBarThickness: 30
        }]
    },
    options: { 
        responsive: true, 
        maintainAspectRatio: false, 
        plugins: { legend: { display: false } }, 
        scales: { 
            y: { 
                beginAtZero: true, 
                grid: { color: 'rgba(0,0,0,0.05)' },
                ticks: { font: { family: 'Outfit' } }
            },
            x: {
                grid: { display: false },
                ticks: { font: { family: 'Outfit' } }
            }
        } 
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
