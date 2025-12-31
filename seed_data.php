<?php
/**
 * SOLUFEED - Script de Generación de Datos de Prueba (Seeding)
 * Genera 30 días de historial para validar reportes y estadísticas.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$db = getConnection();
echo "<h1>🌱 Sembrando Datos de Prueba (Solufeed)</h1>";

try {
    // 1. Obtener usuario administrador para los registros
    $stmt = $db->query("SELECT id_usuario FROM usuario WHERE tipo = 'ADMIN' LIMIT 1");
    $admin_id = $stmt->fetchColumn() ?: 1;

    // 2. Obtener lotes activos
    $lotes = $db->query("SELECT id_tropa, nombre, cantidad_inicial FROM tropa WHERE activo = 1")->fetchAll();

    if (empty($lotes)) {
        die("❌ No hay lotes activos para sembrar datos.");
    }

    // 3. Verificar si hay dietas disponibles
    $dieta_id = $db->query("SELECT id_dieta FROM dieta WHERE activo = 1 LIMIT 1")->fetchColumn();

    if (!$dieta_id) {
        // Crear una dieta de prueba si no existe
        $db->exec("INSERT INTO dieta (nombre, activo) VALUES ('Dieta de Engorde Automática', 1)");
        $dieta_id = $db->lastInsertId();
        
        // Buscar insumos para la dieta
        $insumos = $db->query("SELECT id_insumo FROM insumo LIMIT 3")->fetchAll();
        if (empty($insumos)) {
            // Crear insumos si no existen
            $db->exec("INSERT INTO insumo (nombre, tipo, porcentaje_ms) VALUES ('Maíz Grano', 'GRANO', 88), ('Núcleo Prot.', 'CONCENTRADO', 90), ('Silo de Maíz', 'FORRAJE', 35)");
            $insumos = $db->query("SELECT id_insumo FROM insumo ORDER BY id_insumo DESC LIMIT 3")->fetchAll();
        }
        
        // Agregar detalles a la dieta
        $porcentajes = [60, 10, 30];
        foreach ($insumos as $i => $ins) {
            $db->prepare("INSERT INTO dieta_detalle (id_dieta, id_insumo, porcentaje_teorico) VALUES (?, ?, ?)")
               ->execute([$dieta_id, $ins['id_insumo'], $porcentajes[$i]]);
        }
        echo "<p>✅ Dieta de prueba creada.</p>";
    }

    $hoy = new DateTime();
    $db->beginTransaction();

    foreach ($lotes as $lote) {
        $id_tropa = $lote['id_tropa'];
        $nombre_lote = $lote['nombre'];
        $animales = obtenerAnimalesPresentes($id_tropa);
        
        if ($animales <= 0) $animales = 50; // Fallback

        echo "<h3>🚛 Procesando Lote: $nombre_lote ($animales animales)</h3>";

        // Asegurar que el lote tenga la dieta asignada
        $tiene_dieta = $db->prepare("SELECT COUNT(*) FROM tropa_dieta_asignada WHERE id_tropa = ?");
        $tiene_dieta->execute([$id_tropa]);
        if ($tiene_dieta->fetchColumn() == 0) {
            $db->prepare("INSERT INTO tropa_dieta_asignada (id_tropa, id_dieta, fecha_desde) VALUES (?, ?, ?)")
               ->execute([$id_tropa, $dieta_id, $hoy->modify('-40 days')->format('Y-m-d')]);
            $hoy = new DateTime(); // Reset date
        }

        // 4. Crear Pesadas (Puntos de referencia para ADPV)
        $peso_inicial = rand(300, 350);
        $fecha_inicial = (clone $hoy)->modify('-30 days')->format('Y-m-d');
        
        // Eliminar pesadas previas para este rango si existen (opcional)
        $db->prepare("DELETE FROM pesada WHERE id_tropa = ? AND fecha >= ?")->execute([$id_tropa, $fecha_inicial]);

        // Pesada inicial
        $db->prepare("INSERT INTO pesada (id_tropa, id_usuario, fecha, peso_promedio, animales_esperados, animales_vistos, hay_diferencia, origen_registro, fecha_creacion) VALUES (?, ?, ?, ?, ?, ?, 0, 'ONLINE', NOW())")
           ->execute([$id_tropa, $admin_id, $fecha_inicial, $peso_inicial, $animales, $animales]);

        // Pesada final (hoy)
        $ganancia_total = rand(25, 45); // Entre 800g y 1.5kg por día aprox
        $peso_final = $peso_inicial + $ganancia_total;
        $db->prepare("INSERT INTO pesada (id_tropa, id_usuario, fecha, peso_promedio, animales_esperados, animales_vistos, hay_diferencia, origen_registro, fecha_creacion) VALUES (?, ?, ?, ?, ?, ?, 0, 'ONLINE', NOW())")
           ->execute([$id_tropa, $admin_id, $hoy->format('Y-m-d'), $peso_final, $animales, $animales]);

        echo "<p>⚖️ Pesadas generadas: " . $peso_inicial . "kg -> " . $peso_final . "kg</p>";

        // 5. Crear Alimentaciones por 30 días
        $db->prepare("DELETE FROM consumo_lote WHERE id_tropa = ? AND fecha >= ?")->execute([$id_tropa, $fecha_inicial]);
        
        // Obtener detalles de la dieta para los cálculos
        $stmt_dieta = $db->prepare("
            SELECT i.id_insumo, i.porcentaje_ms, dd.porcentaje_teorico 
            FROM dieta_detalle dd
            INNER JOIN insumo i ON dd.id_insumo = i.id_insumo
            WHERE dd.id_dieta = ?
        ");
        $stmt_dieta->execute([$dieta_id]);
        $componentes = $stmt_dieta->fetchAll();

        for ($i = 30; $i >= 0; $i--) {
            $fecha_log = (clone $hoy)->modify("-$i days")->format('Y-m-d');
            $cma_objetivo_kg_ms = rand(20, 26) / 10; // CMS entre 2.0% y 2.6% del PV aprox
            
            // Peso estimado ese día
            $peso_estimado = $peso_inicial + ($ganancia_total / 30 * (30 - $i));
            $ms_diaria_total = ($peso_estimado * ($cma_objetivo_kg_ms / 100)) * $animales;
            
            $sobrantes = ['SIN_SOBRAS', 'POCAS_SOBRAS', 'NORMAL'];
            $sobrante = $sobrantes[array_rand($sobrantes)];

            // Insertar consumo_lote
            $stmt_ins_cons = $db->prepare("
                INSERT INTO consumo_lote (id_tropa, id_usuario, fecha, hora, numero_alimentacion_dia, sobrante_nivel, kg_totales_tirados, animales_presentes, origen_registro, fecha_creacion) 
                VALUES (?, ?, ?, '08:30:00', 1, ?, ?, ?, 'ONLINE', NOW())
            ");
            
            // Calcularemos kg totales después de los detalles
            $stmt_ins_cons->execute([$id_tropa, $admin_id, $fecha_log, $sobrante, 0, $animales]);
            $id_consumo = $db->lastInsertId();

            $kg_totales_tal_cual = 0;
            $detalles = [];
            foreach ($componentes as $comp) {
                $ms_insumo = $ms_diaria_total * ($comp['porcentaje_teorico'] / 100);
                $tal_cual_insumo = $ms_insumo / ($comp['porcentaje_ms'] / 100);
                $tal_cual_insumo = round($tal_cual_insumo, 1);
                $ms_insumo = round($ms_insumo, 2);
                
                $detalles[] = [
                    'id_insumo' => $comp['id_insumo'],
                    'kg_ms' => $ms_insumo,
                    'kg_real' => $tal_cual_insumo,
                    'porcentaje' => $comp['porcentaje_teorico']
                ];
                
                $kg_totales_tal_cual += $tal_cual_insumo;
            }

            foreach ($detalles as $det) {
                $kg_sugerido = ($det['porcentaje'] * $kg_totales_tal_cual) / 100;
                $db->prepare("INSERT INTO consumo_lote_detalle (id_consumo, id_insumo, kg_sugeridos, kg_reales, porcentaje_real, kg_ms) VALUES (?, ?, ?, ?, ?, ?)")
                   ->execute([$id_consumo, $det['id_insumo'], $kg_sugerido, $det['kg_real'], $det['porcentaje'], $det['kg_ms']]);
            }

            // Actualizar kg totales en cabecera
            $db->prepare("UPDATE consumo_lote SET kg_totales_tirados = ? WHERE id_consumo = ?")
               ->execute([$kg_totales_tal_cual, $id_consumo]);
        }
        echo "<p>🌽 31 registros de alimentación creados.</p>";
    }

    $db->commit();
    echo "<h2>✅ ¡Proceso completado con éxito!</h2>";
    echo "<p><a href='admin/dashboard.php' style='padding: 10px 20px; background: #2c5530; color: white; border-radius: 5px; text-decoration: none;'>Ir al Dashboard</a></p>";

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo "<h2 style='color:red;'>❌ Error: " . $e->getMessage() . "</h2>";
}
