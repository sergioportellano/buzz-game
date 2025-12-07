<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../src/Core/Database.php';
require_once '../src/Core/AuthManager.php';

$db = new Database();
$auth = new AuthManager($db);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmar = $_POST['confirmar_password'] ?? '';
    
    if ($password !== $confirmar) {
        $error = "Las contraseñas no coinciden";
    } else {
        $usuarioId = $auth->registrar($usuario, $email, $password);
        
        if ($usuarioId) {
            // Auto-login después del registro
            $usuarioData = $auth->login($email, $password);
            if ($usuarioData) {
                $_SESSION['usuario_id'] = $usuarioData['id'];
                $_SESSION['usuario_nombre'] = $usuarioData['usuario'];
                header("Location: crear-sala.php");
                exit();
            }
        } else {
            $error = "Error al registrar usuario (¿email ya existe?)";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Buzz Game</title>
    <style>
        body { font-family: Arial; max-width: 500px; margin: 50px auto; padding: 20px; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        button { background: #007bff; color: white; padding: 12px; border: none; border-radius: 5px; width: 100%; cursor: pointer; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Registro de Usuario</h1>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="usuario">Nombre de Usuario:</label>
                <input type="text" id="usuario" name="usuario" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirmar_password">Confirmar Contraseña:</label>
                <input type="password" id="confirmar_password" name="confirmar_password" required>
            </div>
            <button type="submit">Registrarse</button>
        </form>
        
        <p style="text-align: center; margin-top: 20px;">
            ¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a>
        </p>
    </div>
</body>
</html>