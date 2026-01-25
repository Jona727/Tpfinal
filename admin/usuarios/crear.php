<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

verificarAdmin();

$db = getConnection();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $tipo = $_POST['tipo'] ?? 'CAMPO';
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    // Validaciones
    if (empty($nombre) || empty($email) || empty($password)) {
        $error = 'Todos los campos son obligatorios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El email no es válido.';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($password !== $password_confirm) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        // Verificar si el email ya existe
        $stmt = $db->prepare("SELECT id_usuario FROM usuario WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $error = 'Ya existe un usuario con ese email.';
        } else {
            // Crear usuario
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            try {
                $stmt = $db->prepare("
                    INSERT INTO usuario (nombre, email, password_hash, tipo, activo, fecha_creacion)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                
                $stmt->execute([$nombre, $email, $password_hash, $tipo, $activo]);
                
                $success = 'Usuario creado exitosamente.';
                
                // Limpiar formulario
                $nombre = $email = '';
                $tipo = 'CAMPO';
                $activo = 0;
                
                // Redirigir después de 2 segundos
                header("refresh:2;url=listar.php");
            } catch (Exception $e) {
                $error = 'Error al crear el usuario: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Usuario - Solufeed</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <style>
        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .btn-back {
            padding: 0.75rem 1.5rem;
            background: #6b7280;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            background: #4b5563;
            transform: translateY(-1px);
        }

        .form-card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            max-width: 600px;
            margin: 0 auto;
        }

        .form-grid {
            display: grid;
            gap: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group label {
            font-weight: 600;
            color: var(--text);
            font-size: 0.9rem;
        }

        .form-group label .required {
            color: var(--danger);
        }

        .form-group input,
        .form-group select {
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-family: inherit;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(44, 85, 48, 0.1);
        }

        .form-group small {
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 8px;
        }

        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .checkbox-group label {
            margin: 0;
            cursor: pointer;
            font-weight: 500;
        }

        .tipo-selector {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .tipo-option {
            position: relative;
        }

        .tipo-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }

        .tipo-option label {
            display: block;
            padding: 1.5rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .tipo-option input[type="radio"]:checked + label {
            border-color: var(--primary);
            background: rgba(44, 85, 48, 0.05);
        }

        .tipo-option label .icon {
            font-size: 2rem;
            display: block;
            margin-bottom: 0.5rem;
        }

        .tipo-option label .title {
            font-weight: 600;
            display: block;
            margin-bottom: 0.25rem;
        }

        .tipo-option label .description {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .alert-error {
            background: #fee2e2;
            border-left: 4px solid var(--danger);
            color: #991b1b;
        }

        .alert-success {
            background: #d1fae5;
            border-left: 4px solid var(--success);
            color: #065f46;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .btn-submit {
            flex: 1;
            padding: 1rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(44, 85, 48, 0.3);
        }

        .btn-reset {
            padding: 1rem 2rem;
            background: #f1f5f9;
            color: var(--text);
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-reset:hover {
            background: #e2e8f0;
        }

        .password-strength {
            height: 4px;
            background: #e2e8f0;
            border-radius: 2px;
            overflow: hidden;
            margin-top: 0.5rem;
        }

        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s ease;
        }

        .password-strength-bar.weak {
            width: 33%;
            background: var(--danger);
        }

        .password-strength-bar.medium {
            width: 66%;
            background: var(--accent);
        }

        .password-strength-bar.strong {
            width: 100%;
            background: var(--success);
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>

    <div class="container">
        <div class="form-header">
            <div>
                <h1>➕ Crear Nuevo Usuario</h1>
                <p style="color: var(--text-muted); margin-top: 0.5rem;">
                    Completa los datos para crear un nuevo usuario
                </p>
            </div>
            <a href="listar.php" class="btn-back">
                <span>←</span>
                Volver
            </a>
        </div>

        <div class="form-card">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <strong>❌ Error:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <strong>✓ Éxito:</strong> <?php echo htmlspecialchars($success); ?>
                    <br><small>Redirigiendo al listado...</small>
                </div>
            <?php endif; ?>

            <form method="POST" id="formUsuario">
                <div class="form-grid">
                    <!-- Nombre -->
                    <div class="form-group">
                        <label>
                            Nombre Completo <span class="required">*</span>
                        </label>
                        <input type="text" name="nombre" required 
                               value="<?php echo htmlspecialchars($nombre ?? ''); ?>"
                               placeholder="Ej: Juan Pérez">
                    </div>

                    <!-- Email -->
                    <div class="form-group">
                        <label>
                            Correo Electrónico <span class="required">*</span>
                        </label>
                        <input type="email" name="email" required 
                               value="<?php echo htmlspecialchars($email ?? ''); ?>"
                               placeholder="usuario@ejemplo.com">
                        <small>Este será el usuario para iniciar sesión</small>
                    </div>

                    <!-- Tipo de Usuario -->
                    <div class="form-group">
                        <label>
                            Tipo de Usuario <span class="required">*</span>
                        </label>
                        <div class="tipo-selector">
                            <div class="tipo-option">
                                <input type="radio" name="tipo" value="ADMIN" id="tipo_admin"
                                       <?php echo ($tipo ?? 'CAMPO') === 'ADMIN' ? 'checked' : ''; ?>>
                                <label for="tipo_admin">
                                    <span class="icon">👔</span>
                                    <span class="title">Administrador</span>
                                    <span class="description">Acceso completo al sistema</span>
                                </label>
                            </div>
                            <div class="tipo-option">
                                <input type="radio" name="tipo" value="CAMPO" id="tipo_campo"
                                       <?php echo ($tipo ?? 'CAMPO') === 'CAMPO' ? 'checked' : ''; ?>>
                                <label for="tipo_campo">
                                    <span class="icon">🧑‍🌾</span>
                                    <span class="title">Personal de Campo</span>
                                    <span class="description">Operaciones de campo</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Contraseña -->
                    <div class="form-group">
                        <label>
                            Contraseña <span class="required">*</span>
                        </label>
                        <input type="password" name="password" id="password" required 
                               minlength="6" placeholder="Mínimo 6 caracteres">
                        <div class="password-strength">
                            <div class="password-strength-bar" id="strengthBar"></div>
                        </div>
                        <small id="strengthText">Ingresa una contraseña</small>
                    </div>

                    <!-- Confirmar Contraseña -->
                    <div class="form-group">
                        <label>
                            Confirmar Contraseña <span class="required">*</span>
                        </label>
                        <input type="password" name="password_confirm" id="password_confirm" 
                               required minlength="6" placeholder="Repite la contraseña">
                    </div>

                    <!-- Estado -->
                    <div class="checkbox-group">
                        <input type="checkbox" name="activo" id="activo" value="1"
                               <?php echo ($activo ?? 0) ? 'checked' : ''; ?>>
                        <label for="activo">
                            <strong>Usuario Activo</strong>
                            <br>
                            <small style="color: var(--text-muted);">
                                Si está desactivado, no podrá iniciar sesión
                            </small>
                        </label>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit">
                        ✓ Crear Usuario
                    </button>
                    <button type="reset" class="btn-reset">
                        🔄 Limpiar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Validación de contraseña en tiempo real
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('password_confirm');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');

        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            if (password.length >= 6) strength++;
            if (password.length >= 10) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;

            strengthBar.className = 'password-strength-bar';
            
            if (strength <= 2) {
                strengthBar.classList.add('weak');
                strengthText.textContent = 'Contraseña débil';
                strengthText.style.color = 'var(--danger)';
            } else if (strength <= 3) {
                strengthBar.classList.add('medium');
                strengthText.textContent = 'Contraseña media';
                strengthText.style.color = 'var(--accent)';
            } else {
                strengthBar.classList.add('strong');
                strengthText.textContent = 'Contraseña fuerte';
                strengthText.style.color = 'var(--success)';
            }
        });

        // Validar que las contraseñas coincidan
        document.getElementById('formUsuario').addEventListener('submit', function(e) {
            if (passwordInput.value !== confirmInput.value) {
                e.preventDefault();
                alert('❌ Las contraseñas no coinciden');
                confirmInput.focus();
            }
        });
    </script>
</body>
</html>
