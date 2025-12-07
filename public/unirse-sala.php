<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../src/Core/Database.php';
require_once '../src/Core/AuthManager.php';
require_once '../src/Core/RoomManager.php';

$db = new Database();
$auth = new AuthManager($db);
$roomManager = new RoomManager($db);

$error = '';

// Verificar si el usuario está logueado
if (!$auth->estaLogueado()) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roomCode = strtoupper($_POST['room_code'] ?? '');
    $playerName = $_POST['player_name'] ?? '';
    
    error_log("=== UNIRSE SALA ===");
    error_log("Código: $roomCode, Nombre: $playerName");
    error_log("Usuario ID: " . $auth->obtenerUsuarioActual());
    error_log("Usuario Nombre: " . ($_SESSION['usuario_nombre'] ?? 'No definido'));
    
    if ($roomCode && $playerName) {
        $room = $roomManager->getRoom($roomCode);
        
        if ($room) {
            error_log("Sala encontrada: " . $room['name']);
            error_log("Estado: " . $room['state']);
            error_log("Jugadores actuales: " . count($room['players']));
            error_log("Máximo: " . $room['max_players']);
            error_log("Creador ID: " . $room['creator_id']);
            
            // Usar el ID REAL del usuario logueado
            $playerId = $auth->obtenerUsuarioActual();
            
            error_log("Player ID (usuario real): $playerId");
            
            $result = $roomManager->joinRoom($roomCode, $playerId, $playerName);
            
            if ($result) {
                $_SESSION['player_name'] = $playerName;
                $_SESSION['current_room'] = $roomCode;
                
                error_log("Unión exitosa, redirigiendo...");
                header("Location: sala.php?code=$roomCode");
                exit();
            } else {
                $error = "La sala está llena o no se pudo unir";
                error_log("Error al unirse: $error");
                
                // Debug adicional
                $roomAfter = $roomManager->getRoom($roomCode);
                error_log("Después de joinRoom - Jugadores: " . count($roomAfter['players']));
                
                // Verificar específicamente qué falló
                if (count($roomAfter['players']) >= $roomAfter['max_players']) {
                    $error = "La sala está llena (" . count($roomAfter['players']) . "/" . $roomAfter['max_players'] . " jugadores)";
                }
            }
        } else {
            $error = "Sala no encontrada";
            error_log("Sala no encontrada: $roomCode");
        }
    } else {
        $error = "Por favor completa todos los campos";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unirse a Sala</title>
    <style>
        body { 
            font-family: Arial; 
            max-width: 500px; 
            margin: 50px auto; 
            padding: 20px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container { 
            background: white; 
            padding: 30px; 
            border-radius: 10px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
            width: 100%;
        }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input { 
            width: 100%; 
            padding: 12px; 
            border: 2px solid #ddd; 
            border-radius: 5px; 
            box-sizing: border-box;
            font-size: 16px;
        }
        input:focus {
            border-color: #007bff;
            outline: none;
        }
        button { 
            background: #28a745; 
            color: white; 
            padding: 15px; 
            border: none; 
            border-radius: 5px; 
            width: 100%; 
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
        }
        button:hover {
            background: #218838;
        }
        .error { 
            background: #f8d7da; 
            color: #721c24; 
            padding: 12px; 
            border-radius: 5px; 
            margin-bottom: 20px; 
            border: 1px solid #f5c6cb;
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
            <a href="crear-sala.php">Crear Sala</a> | 
            <a href="logout.php">Cerrar sesión</a>
        </div>
        
        <h1>🎵 Unirse a Sala Existente</h1>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="room_code">Código de la Sala:</label>
                <input type="text" id="room_code" name="room_code" placeholder="Ej: 5D58D4" required value="<?php echo htmlspecialchars($_POST['room_code'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="player_name">Tu Nombre para esta partida:</label>
                <input type="text" id="player_name" name="player_name" placeholder="Ej: Juan" required value="<?php echo htmlspecialchars($_POST['player_name'] ?? $_SESSION['usuario_nombre'] ?? ''); ?>">
            </div>
            <button type="submit">🎮 Unirse a la Sala</button>
        </form>
        
        <p style="text-align: center; margin-top: 20px;">
            <a href="crear-sala.php">¿Quieres crear una nueva sala?</a>
			<a href="salas-disponibles.php">Ver Salas Disponibles</a>
        </p>
    </div>
</body>
</html>