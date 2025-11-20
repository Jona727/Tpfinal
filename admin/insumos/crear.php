<?php
/**
 * SOLUFEED - Crear Insumo
 */

require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Simular sesión
$_SESSION['usuario_id'] = 1;
$_SESSION['nombre'] = 'Administrador';
$_SESSION['tipo'] = 'ADMIN';

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
        // Insertar en la base de datos
        $query = "
            INSERT INTO insumo (nombre, tipo, costo_kg, porcentaje_ms, activo, fecha_creacion)
            VALUES (
                '$nombre',
                '$tipo',
                $costo_kg,
                $porcentaje_ms,
                $activo,
                NOW()
            )
        ";
        
        if (ejecutarConsulta($query)) {
            $exito = "✓ Insumo creado exitosamente.";
            // Redirigir después de 2 segundos
            header("refresh:2;url=listar.php");
        } else {
            $error = "Error al crear el insumo. Intentá nuevamente.";
        }
    }
}

include '../../includes/header.php';
?>

<h1 class="tarjeta-titulo">🌾 Crear Nuevo Insumo</h1>

<div class="tarjeta">
    
    <?php if (isset($exito)): ?>
        <div class="mensaje mensaje-exito"><?php echo $exito; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="mensaje mensaje-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
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
                value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>"
            >
            <small>El nombre debe ser descriptivo y único.</small>
        </div>
        
        <!-- Tipo de insumo -->
        <div class="form-grupo">
            <label for="tipo">Tipo de Insumo *</label>
            <select id="tipo" name="tipo" required>
                <option value="">-- Seleccioná un tipo --</option>
                <option value="Grano" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'Grano') ? 'selected' : ''; ?>>Grano</option>
                <option value="Forraje" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'Forraje') ? 'selected' : ''; ?>>Forraje</option>
                <option value="Concentrado" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'Concentrado') ? 'selected' : ''; ?>>Concentrado</option>
                <option value="Suplemento" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'Suplemento') ? 'selected' : ''; ?>>Suplemento</option>
                <option value="Otro" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'Otro') ? 'selected' : ''; ?>>Otro</option>
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
                value="<?php echo isset($_POST['porcentaje_ms']) ? $_POST['porcentaje_ms'] : ''; ?>"
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
                value="<?php echo isset($_POST['costo_kg']) ? $_POST['costo_kg'] : '0'; ?>"
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
                    <?php echo (!isset($_POST['activo']) || $_POST['activo']) ? 'checked' : ''; ?>
                >
                Insumo activo
            </label>
            <small>Los insumos inactivos no aparecen al crear dietas.</small>
        </div>
        
        <!-- Botones -->
        <div class="btn-grupo">
            <button type="submit" class="btn btn-primario">💾 Guardar Insumo</button>
            <a href="listar.php" class="btn btn-secundario">❌ Cancelar</a>
        </div>
        
    </form>
    
</div>

<?php include '../../includes/footer.php'; ?>