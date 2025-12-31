<?php
require_once __DIR__ . '/../config/database.php';

class StatsModel {
    private $db;

    public function __construct() {
        $this->db = getConnection();
    }

    // Indicadores Principales
    public function getMainIndicators() {
        return [
            'lotes_activos' => $this->db->query("SELECT COUNT(*) FROM tropa WHERE activo = 1")->fetchColumn(),
            'total_animales' => $this->db->query("SELECT SUM(cantidad_inicial) FROM tropa WHERE activo = 1")->fetchColumn() ?? 0,
            'total_insumos' => $this->db->query("SELECT COUNT(*) FROM insumo WHERE activo = 1")->fetchColumn(),
            'total_dietas' => $this->db->query("SELECT COUNT(*) FROM dieta WHERE activo = 1")->fetchColumn()
        ];
    }

    public function getTodayStats() {
        $hoy = date('Y-m-d');
        $stmt = $this->db->prepare("SELECT COUNT(*) as alimentaciones, COALESCE(SUM(kg_totales_tirados), 0) as kg_totales FROM consumo_lote WHERE DATE(fecha) = ?");
        $stmt->execute([$hoy]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAdpvAvg() {
        $stmt = $this->db->query("
            SELECT AVG((p2.peso_promedio - p1.peso_promedio) / DATEDIFF(p2.fecha, p1.fecha)) as adpv
            FROM pesada p1
            INNER JOIN pesada p2 ON p1.id_tropa = p2.id_tropa 
                AND p2.fecha > p1.fecha
                AND p2.fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            WHERE NOT EXISTS (
                SELECT 1 FROM pesada p3 
                WHERE p3.id_tropa = p1.id_tropa AND p3.fecha > p1.fecha AND p3.fecha < p2.fecha
            )
        ");
        return $stmt->fetchColumn() ?? 0;
    }

    public function getCmsAvg() {
        $stmt = $this->db->query("
            SELECT AVG(
                (SELECT SUM(kg_ms) FROM consumo_lote_detalle cld WHERE cld.id_consumo = cl.id_consumo) / cl.animales_presentes
            ) as cms
            FROM consumo_lote cl
            WHERE cl.fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND cl.animales_presentes > 0
        ");
        return $stmt->fetchColumn() ?? 0;
    }

    // Alertas
    public function getAlerts() {
        $hoy = date('Y-m-d');
        
        $lotes_sin_dieta = $this->db->query("
            SELECT COUNT(*) FROM tropa t
            LEFT JOIN tropa_dieta_asignada tda ON t.id_tropa = tda.id_tropa AND tda.fecha_hasta IS NULL
            WHERE t.activo = 1 AND tda.id_tropa_dieta IS NULL
        ")->fetchColumn();

        $ajustes_pendientes = $this->db->query("SELECT COUNT(*) FROM ajuste_animales_pendiente WHERE estado = 'PENDIENTE'")->fetchColumn();

        $stmt = $this->db->prepare("
            SELECT COUNT(DISTINCT t.id_tropa) FROM tropa t
            LEFT JOIN consumo_lote cl ON t.id_tropa = cl.id_tropa AND DATE(cl.fecha) = ?
            WHERE t.activo = 1 AND cl.id_consumo IS NULL
        ");
        $stmt->execute([$hoy]);
        $lotes_sin_alimentar = $stmt->fetchColumn();

        return [
            'lotes_sin_dieta' => $lotes_sin_dieta,
            'ajustes_pendientes' => $ajustes_pendientes,
            'lotes_sin_alimentar' => $lotes_sin_alimentar
        ];
    }

    // Listados
    public function getTopActiveLotes($limit = 5) {
        $stmt = $this->db->prepare("
            SELECT 
                t.id_tropa, t.nombre, c.nombre as campo, t.cantidad_inicial as animales, d.nombre as dieta,
                (SELECT peso_promedio FROM pesada WHERE id_tropa = t.id_tropa ORDER BY fecha DESC LIMIT 1) as ultimo_peso,
                (SELECT DATE(fecha) FROM pesada WHERE id_tropa = t.id_tropa ORDER BY fecha DESC LIMIT 1) as fecha_peso,
                DATEDIFF(CURDATE(), t.fecha_inicio) as dias_feedlot
            FROM tropa t
            LEFT JOIN campo c ON t.id_campo = c.id_campo
            LEFT JOIN tropa_dieta_asignada tda ON t.id_tropa = tda.id_tropa AND tda.fecha_hasta IS NULL
            LEFT JOIN dieta d ON tda.id_dieta = d.id_dieta
            WHERE t.activo = 1
            ORDER BY t.fecha_inicio DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLastFeedings($limit = 5) {
        $stmt = $this->db->prepare("
            SELECT 
                cl.fecha, cl.hora, t.nombre as lote, cl.kg_totales_tirados, cl.animales_presentes, u.nombre as operario
            FROM consumo_lote cl
            INNER JOIN tropa t ON cl.id_tropa = t.id_tropa
            LEFT JOIN usuario u ON cl.id_usuario = u.id_usuario
            ORDER BY cl.fecha DESC, cl.hora DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Datos Gráficos
    public function getWeightEvolutionData() {
        return $this->db->query("
            SELECT DATE(p.fecha) as fecha, AVG(p.peso_promedio) as peso_promedio
            FROM pesada p
            INNER JOIN tropa t ON p.id_tropa = t.id_tropa
            WHERE t.activo = 1 AND p.fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY DATE(p.fecha)
            ORDER BY fecha ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMsConsumptionData() {
        return $this->db->query("
            SELECT DATE(cl.fecha) as fecha, 
            SUM((SELECT SUM(cld.kg_ms) FROM consumo_lote_detalle cld WHERE cld.id_consumo = cl.id_consumo)) as ms_total
            FROM consumo_lote cl
            WHERE cl.fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY DATE(cl.fecha)
            ORDER BY fecha ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
