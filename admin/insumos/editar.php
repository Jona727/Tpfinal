<?php
/**
 * SOLUFEED - Editar Insumo
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

$id_insumo = (int) $_GET['id'];

// Obtener datos del insumo
$query = "SELECT * FROM insumo WHERE id_insumo = $id_insumo";
$resultado = ejecutarConsulta($query);

if (mysqli_num_rows($resultado) === 0) {
    header('Location: listar.php');
    exit();
}

$insumo = mysqli_fetch_assoc($resultado);

// Procesar formulario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Recibir datos del formulario
    $nombre = limpiarDato($_POST['nombre']);
    $tipo = limpiarDato($_POST['tipo']);
    $costo_kg = (float) $_POST['costo_kg'];
    $porcentaje_ms = (float) $_POST['porcentaje_ms'];
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    // Validaciones básicas
    if (empty($nombre) || empty($tipo) || $porcentaje_ms <= 0 || $porcentaje_ms > 100) {
        $error = "Por favor, completá todos los campos correctamente. El % MS debe estar entre 0 y 100.";
    } else {
        // Actualizar en la base de datos
        $query_update = "
            UPDATE insumo SET
                nombre = '$nombre',
                tipo = '$tipo',
                costo_kg = $costo_kg,
                porcentaje_ms = $porcentaje_ms,
                activo = $activo,
                fecha_actualizacion = NOW()
            WHERE id_insumo = $id_insumo
        ";
        
        if (ejecutarConsulta($query_update)) {
            $exito = "✓ Insumo actualizado exitosamente.";
            // Redirigir después de 2 segundos
            header("refresh:2;url=listar.php");
            
            // Actualizar datos para mostrar en el formulario
            $insumo['nombre'] = $nombre;
            $insumo['tipo'] = $tipo;
            $insumo['costo_kg'] = $costo_kg;
            $insumo['porcentaje_ms'] = $porcentaje_ms;
            $insumo['activo'] = $activo;
        } else {
            $error = "Error al actualizar el insumo. Intentá nuevamente.";
        }
    }
}

include '../../includes/header.php';
?>

<h1 class="tarjeta-titulo">✏️ Editar Insumo</h1>

<div class="tarjeta">
    
    <?php if (isset($exito)): ?>
        <div class="mensaje mensaje-exito"><?php echo $exito; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="mensaje mensaje-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="mensaje mensaje-info" style="margin-bottom: 1.5rem;">
        ℹ️ <strong>Atención:</strong> Si este insumo está siendo usado en dietas activas, los cambios en el % MS 
        afectarán los cálculos de los nuevos consumos registrados a partir de ahora.
    </div>
    
    <form method="POST" class="formulario">
        
        <!-- Nombre del insumo -->
        <div class="form-grupo">
            <label for="nombre">Nombre del Insumo *</label>
            <input 
                type="text" 
                id="nombre" 
                name="nombre" 
                required 
                placeholder="Ej: Maíz, Silo, Balanceado..."
                value="<?php echo htmlspecialchars($insumo['nombre']); ?>"
            >
            <small>El nombre debe ser descriptivo y único.</small>
        </div>
        
        <!-- Tipo de insumo -->
        <div class="form-grupo">
            <label for="tipo">Tipo de Insumo *</label>
            <select id="tipo" name="tipo" required>
                <option value="">-- Seleccioná un tipo --</option>
                <option value="Grano" <?php echo ($insumo['tipo'] == 'Grano') ? 'selected' : ''; ?>>Grano</option>
                <option value="Forraje" <?php echo ($insumo['tipo'] == 'Forraje') ? 'selected' : ''; ?>>Forraje</option>
                <option value="Concentrado" <?php echo ($insumo['tipo'] == 'Concentrado') ? 'selected' : ''; ?>>Concentrado</option>
                <option value="Suplemento" <?php echo ($insumo['tipo'] == 'Suplemento') ? 'selected' : ''; ?>>Suplemento</option>
                <option value="Otro" <?php echo ($insumo['tipo'] == 'Otro') ? 'selected' : ''; ?>>Otro</option>
            </select>
            <small>Categorizá el insumo para mejor organización.</small>
        </div>
        
        <!-- Porcentaje de Materia Seca -->
        <div class="form-grupo">
            <label for="porcentaje_ms">Porcentaje de Materia Seca (% MS) *</label>
            <input 
                type="number" 
                id="porcentaje_ms" 
                name="porcentaje_ms" 
                step="0.01" 
                min="0" 
                max="100" 
                required 
                class="input-pequeno"
                placeholder="Ej: 86.00"
                value="<?php echo $insumo['porcentaje_ms']; ?>"
            >
            <small>
                <strong>Muy importante:</strong> El % MS se usa para calcular los indicadores (CMS, EC, etc.).
                <br>Ejemplos: Maíz 86%, Silo 35%, Balanceado 90%
            </small>
        </div>
        
        <!-- Costo por kg -->
        <div class="form-grupo">
            <label for="costo_kg">Costo por Kg (opcional)</label>
            <input 
                type="number" 
                id="costo_kg" 
                name="costo_kg" 
                step="0.01" 
                min="0" 
                class="input-mediano"
                placeholder="Ej: 150.00"
                value="<?php echo $insumo['costo_kg']; ?>"
            >
            <small>Precio en pesos por kilogramo (opcional, para análisis de costos).</small>
        </div>
        
        <!-- Estado activo -->
        <div class="form-grupo">
            <label>
                <input 
                    type="checkbox" 
                    name="activo" 
                    value="1" 
                    <?php echo $insumo['activo'] ? 'checked' : ''; ?>
                >
                Insumo activo
            </label>
            <small>Los insumos inactivos no aparecen al crear dietas.</small>
        </div>
        
        <!-- Información adicional -->
        <div class="form-grupo">
            <small style="color: #666;">
                <strong>Creado:</strong> <?php echo formatearFecha($insumo['fecha_creacion']); ?>
                <?php if ($insumo['fecha_actualizacion']): ?>
                    | <strong>Última actualización:</strong> <?php echo formatearFecha($insumo['fecha_actualizacion']); ?>
                <?php endif; ?>
            </small>
        </div>
        
        <!-- Botones -->
        <div class="btn-grupo">
            <button type="submit" class="btn btn-primario">💾 Guardar Cambios</button>
            <a href="listar.php" class="btn btn-secundario">❌ Cancelar</a>
        </div>
        
    </form>
    
</div>

<!-- Sección de uso del insumo (opcional pero útil) -->
<div class="tarjeta">
    <h2 class="tarjeta-titulo">📊 Uso de este Insumo</h2>
    
    <?php
    // Verificar en cuántas dietas se usa este insumo
    $query_uso = "
        SELECT COUNT(*) as total_dietas
        FROM dieta_detalle dd
        INNER JOIN dieta d ON dd.id_dieta = d.id_dieta
        WHERE dd.id_insumo = $id_insumo
        AND d.activo = 1
    ";
    $resultado_uso = ejecutarConsulta($query_uso);
    $uso = mysqli_fetch_assoc($resultado_uso);
    
    // Obtener las dietas que lo usan
    $query_dietas = "
        SELECT 
            d.nombre as dieta_nombre,
            dd.porcentaje_teorico
        FROM dieta_detalle dd
        INNER JOIN dieta d ON dd.id_dieta = d.id_dieta
        WHERE dd.id_insumo = $id_insumo
        AND d.activo = 1
        ORDER BY d.nombre
    ";
    $resultado_dietas = ejecutarConsulta($query_dietas);
    ?>
    
    <?php if ($uso['total_dietas'] > 0): ?>
        <p>Este insumo está siendo utilizado en <strong><?php echo $uso['total_dietas']; ?></strong> dieta(s) activa(s):</p>
        
        <table style="margin-top: 1rem;">
            <thead>
                <tr>
                    <th>Dieta</th>
                    <th>% Teórico en la Dieta</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($dieta = mysqli_fetch_assoc($resultado_dietas)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($dieta['dieta_nombre']); ?></td>
                        <td><?php echo formatearNumero($dieta['porcentaje_teorico'], 2); ?>%</td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
    <?php else: ?>
        <p style="color: #999;">Este insumo no está siendo utilizado en ninguna dieta activa todavía.</p>
    <?php endif; ?>
    
</div>

<?php include '../../includes/footer.php'; ?>