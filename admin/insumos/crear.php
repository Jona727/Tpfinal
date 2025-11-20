<?php
// admin/insumos/crear.php - Actualizado a PDO
require_once '../../config/database.php';
require_once '../../includes/functions.php';

$page_title = "Crear Insumo";
$db = getConnection();

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $tipo = $_POST['tipo'];
    $porcentaje_ms = floatval($_POST['porcentaje_ms']);
    
    // Validaciones
    if (empty($nombre)) {
        $error = "El nombre es obligatorio";
    } elseif ($porcentaje_ms < 0 || $porcentaje_ms > 100) {
        $error = "El porcentaje de MS debe estar entre 0 y 100";
    } else {
        // Verificar que no exista otro insumo con el mismo nombre
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM insumo WHERE nombre = ?");
        $stmt->execute([$nombre]);
        $existe = $stmt->fetch()['total'];
        
        if ($existe > 0) {
            $error = "Ya existe un insumo con ese nombre";
        } else {
            // Insertar el nuevo insumo
            $stmt = $db->prepare("
                INSERT INTO insumo (nombre, tipo, porcentaje_ms, activo, fecha_creacion)
                VALUES (?, ?, ?, 1, NOW())
            ");
            
            if ($stmt->execute([$nombre, $tipo, $porcentaje_ms])) {
                $mensaje = "✅ Insumo creado exitosamente";
                // Limpiar formulario
                $_POST = [];
            } else {
                $error = "Error al crear el insumo";
            }
        }
    }
}

require_once '../../includes/header.php';
?>

<style>
.crear-container {
    max-width: 800px;
    margin: 0 auto;
}

.form-card {
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
    color: #333;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 12px;
    border: 2px solid #ddd;
    border-radius: 5px;
    font-size: 1em;
}

.form-group input:focus,
.form-group select:focus {
    border-color: #2c5530;
    outline: none;
}

.form-group small {
    display: block;
    margin-top: 5px;
    color: #666;
    font-size: 0.9em;
}

.btn-group {
    display: flex;
    gap: 10px;
    margin-top: 30px;
}

.btn {
    padding: 12px 25px;
    border: none;
    border-radius: 5px;
    font-size: 1em;
    font-weight: bold;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s;
}

.btn-primary {
    background: #2c5530;
    color: white;
}

.btn-primary:hover {
    background: #3d7043;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
}

.alert {
    padding: 15px 20px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.alert-success {
    background: #d4edda;
    border-left: 4px solid #28a745;
    color: #155724;
}

.alert-error {
    background: #f8d7da;
    border-left: 4px solid #dc3545;
    color: #721c24;
}

.info-box {
    background: #e7f3ff;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #0066cc;
    margin-bottom: 20px;
}

.info-box h4 {
    margin: 0 0 10px 0;
    color: #0066cc;
}

.info-box ul {
    margin: 10px 0 0 20px;
}
</style>

<div class="crear-container">
    <div style="margin-bottom: 20px;">
        <a href="listar.php" style="color: #2c5530; text-decoration: none;">
            ← Volver a Insumos
        </a>
    </div>

    <div class="form-card">
        <h1 style="margin: 0 0 10px 0;">🌾 Crear Nuevo Insumo</h1>
        <p style="color: #666; margin: 0 0 30px 0;">
            Ingresá los datos del nuevo insumo para el feedlot
        </p>

        <?php if ($mensaje): ?>
            <div class="alert alert-success">
                <?php echo $mensaje; ?>
                <br><br>
                <a href="listar.php" style="color: #155724; font-weight: bold;">
                    Ver lista de insumos →
                </a>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                ❌ <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="info-box">
            <h4>💡 Información sobre Materia Seca (MS)</h4>
            <p>El % de MS es la proporción de nutrientes sólidos del insumo (excluyendo el agua).</p>
            <ul>
                <li><strong>Granos secos:</strong> 85-90% MS</li>
                <li><strong>Forrajes conservados:</strong> 85-90% MS</li>
                <li><strong>Silajes:</strong> 30-40% MS</li>
                <li><strong>Forrajes verdes:</strong> 15-25% MS</li>
            </ul>
        </div>

        <form method="POST" action="">
            <div class="form-group">
                <label for="nombre">Nombre del Insumo *</label>
                <input type="text" 
                       id="nombre" 
                       name="nombre" 
                       required
                       placeholder="Ej: Maíz grano"
                       value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>">
                <small>Nombre descriptivo del insumo</small>
            </div>

            <div class="form-group">
                <label for="tipo">Tipo de Insumo *</label>
                <select id="tipo" name="tipo" required>
                    <option value="">-- Seleccionar tipo --</option>
                    <option value="GRANO" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'GRANO') ? 'selected' : ''; ?>>
                        Grano
                    </option>
                    <option value="FORRAJE" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'FORRAJE') ? 'selected' : ''; ?>>
                        Forraje
                    </option>
                    <option value="CONCENTRADO" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'CONCENTRADO') ? 'selected' : ''; ?>>
                        Concentrado
                    </option>
                    <option value="SUPLEMENTO" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'SUPLEMENTO') ? 'selected' : ''; ?>>
                        Suplemento
                    </option>
                </select>
                <small>Categoría del insumo</small>
            </div>

            <div class="form-group">
                <label for="porcentaje_ms">Porcentaje de Materia Seca (%) *</label>
                <input type="number" 
                       id="porcentaje_ms" 
                       name="porcentaje_ms" 
                       step="0.1" 
                       min="0" 
                       max="100" 
                       required
                       placeholder="Ej: 86.5"
                       value="<?php echo isset($_POST['porcentaje_ms']) ? $_POST['porcentaje_ms'] : ''; ?>">
                <small>Porcentaje de materia seca del insumo (0-100)</small>
            </div>

            <div class="btn-group">
                <button type="submit" class="btn btn-primary">
                    ✅ Crear Insumo
                </button>
                <a href="listar.php" class="btn btn-secondary">
                    ❌ Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
