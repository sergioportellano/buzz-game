<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../src/Core/Database.php';
require_once '../src/Core/AuthManager.php';
require_once '../src/Core/RoomManager.php';

$db = new Database();
$auth = new AuthManager($db);
$roomManager = new RoomManager($db);

// Redirigir si no está logueado
if (!$auth->estaLogueado()) {
    header("Location: login.php");
    exit();
}

// Ahora usamos el ID real del usuario - ESTO ES LO IMPORTANTE
$creatorId = $auth->obtenerUsuarioActual();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['room_name'])) {
    
    $roomCode = strtoupper(substr(md5(uniqid()), 0, 6));
    // $creatorId YA ESTÁ DEFINIDO ARRIBA - NO LO SOBREESCRIBAS
    $maxPlayers = $_POST['max_players'] ?? 4;
    $roomName = $_POST['room_name'] ?? 'Sala ' . $roomCode;
    
    error_log("=== CREAR SALA ===");
    error_log("Creator ID: " . $creatorId);  // Ahora será el ID real del usuario
    error_log("Usuario Nombre: " . ($_SESSION['usuario_nombre'] ?? 'Desconocido'));
    error_log("Room Name: " . $roomName);
    error_log("Max Players: " . $maxPlayers);
    
    // Crear la sala en la base de datos
    $result = $roomManager->createRoom($roomCode, $creatorId, $maxPlayers, $roomName);
    
    if ($result) {
        $_SESSION['current_room'] = $roomCode;
        // Usar el nombre real del usuario en lugar de 'Anfitrión'
        $_SESSION['player_name'] = $_SESSION['usuario_nombre'] ?? 'Anfitrión';
        
        error_log("Sala creada exitosamente: $roomCode");
        header("Location: sala.php?code=$roomCode");
        exit();
    } else {
        error_log("Error al crear sala");
        header("Location: crear-sala.php?error=create_failed");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Sala - Buzz Game</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        button {
            background-color: #007bff;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
        }
        button:hover {
            background-color: #0056b3;
        }
        h1 {
            color: #333;
            text-align: center;
        }
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            text-align: center;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .user-info {
            background: #e9f7ef;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Mostrar información del usuario -->
        <div class="user-info">
            👋 Hola, <strong><?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario'); ?></strong> | 
            <a href="logout.php">Cerrar sesión</a>
        </div>
        
        <h1>Crear Nueva Sala</h1>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="message error">
                Error al crear la sala. Inténtalo de nuevo.
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="room_name">Nombre de la Sala:</label>
                <input type="text" id="room_name" name="room_name" placeholder="Ej: Sala de los Genios" required value="<?php echo isset($_POST['room_name']) ? htmlspecialchars($_POST['room_name']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="max_players">Máximo de Jugadores:</label>
                <select id="max_players" name="max_players">
                    <?php for ($i = 2; $i <= 8; $i++): ?>
                        <option value="<?= $i ?>" <?= (isset($_POST['max_players']) && $_POST['max_players'] == $i) || (!isset($_POST['max_players']) && $i == 4) ? 'selected' : '' ?>>
                            <?= $i ?> Jugadores
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <button type="submit">Crear Sala</button>
        </form>
        
        <div style="margin-top: 20px; text-align: center;">
            <p>¿Tienes un código de sala? <a href="unirse-sala.php">Únete a una sala existente</a></p>
			<a href="salas-disponibles.php">Ver Salas Disponibles</a>
        </div>
        
        <!-- Debug info -->
        <div style="margin-top: 20px; padding: 10px; background: #f8f9fa; border-radius: 5px; font-size: 12px;">
            <strong>Debug:</strong><br>
            Usuario ID: <?php echo $creatorId; ?><br>
            Usuario Nombre: <?php echo $_SESSION['usuario_nombre'] ?? 'No definido'; ?><br>
            Método: <?php echo $_SERVER['REQUEST_METHOD']; ?><br>
            Sesión ID: <?php echo session_id(); ?><br>
            ¿Formulario enviado?: <?php echo ($_SERVER['REQUEST_METHOD'] === 'POST') ? 'Sí' : 'No'; ?><br>
        </div>
    </div>
</body>
</html>