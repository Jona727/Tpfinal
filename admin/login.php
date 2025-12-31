<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

iniciarSesion();

// Si ya está logueado, ir al dashboard
if (isset($_SESSION['usuario_id'])) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    
    if ($email && $password) {
        $db = getConnection();
        $stmt = $db->prepare("SELECT * FROM usuario WHERE email = ? AND activo = 1 LIMIT 1");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();
        
        if ($usuario && password_verify($password, $usuario['password_hash'])) {
            // Login correcto
            $_SESSION['usuario_id'] = $usuario['id_usuario'];
            $_SESSION['nombre'] = $usuario['nombre'];
            $_SESSION['email'] = $usuario['email'];
            $_SESSION['tipo'] = $usuario['tipo'];
            
            header('Location: ' . BASE_URL . ($usuario['tipo'] === 'CAMPO' ? '/admin/campo/index.php' : '/admin/dashboard.php'));
            exit();
        } else {
            $error = 'Credenciales incorrectas o usuario inactivo.';
        }
    } else {
        $error = 'Por favor complete todos los campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso - Solufeed</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2c5530;
            --primary-dark: #1e3a21;
            --secondary: #1e6091;
            --accent: #f4a261;
            --success: #28a745;
            --danger: #dc3545;
            --text: #1e293b;
            --text-muted: #64748b;
            --bg-glass: rgba(255, 255, 255, 0.9);
            --radius: 16px;
            --shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #2c5530 0%, #1e3a21 100%);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            color: var(--text);
        }

        /* Fondo decorativo */
        body::before {
            content: '';
            position: absolute;
            top: -10%;
            right: -10%;
            width: 400px;
            height: 400px;
            background: rgba(244, 162, 97, 0.1);
            border-radius: 50%;
            z-index: 0;
            filter: blur(80px);
        }

        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }

        .login-card {
            background: var(--bg-glass);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-align: center;
        }

        .logo-box {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 24px;
            font-size: 40px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        h1 {
            font-weight: 800;
            color: var(--primary);
            font-size: 1.8rem;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        p.subtitle {
            color: var(--text-muted);
            font-size: 0.95rem;
            margin-bottom: 32px;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text);
            padding-left: 4px;
        }

        .input-wrapper {
            position: relative;
        }

        input {
            width: 100%;
            padding: 14px 16px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s ease;
            outline: none;
        }

        input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(44, 85, 48, 0.1);
        }

        .error-box {
            background: #fee2e2;
            border-left: 4px solid var(--danger);
            color: #991b1b;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 0.85rem;
            font-weight: 500;
            text-align: left;
        }

        button {
            width: 100%;
            padding: 16px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
            box-shadow: 0 4px 6px -1px rgba(44, 85, 48, 0.2);
        }

        button:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 10px 15px -3px rgba(44, 85, 48, 0.3);
        }

        button:active {
            transform: translateY(0);
        }

        footer {
            margin-top: 24px;
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.6);
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo-box">🐮</div>
            <h1>Solufeed</h1>
            <p class="subtitle">Gestión Inteligente de Feedlots</p>
            
            <?php if ($error): ?>
                <div class="error-box">
                    <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <div class="input-wrapper">
                        <input type="email" id="email" name="email" placeholder="email@ejemplo.com" required autofocus>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" placeholder="••••••••" required>
                    </div>
                </div>
                
                <button type="submit">Iniciar Sesión</button>
            </form>
        </div>
        <footer>
            &copy; <?php echo date('Y'); ?> Solufeed. Todos los derechos reservados.
        </footer>
    </div>
</body>
</html>
