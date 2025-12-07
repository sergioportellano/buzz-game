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

// Verificar login
if (!$auth->estaLogueado()) {
    header("Location: login.php");
    exit();
}

// Obtener todas las salas disponibles
$salas = $roomManager->getAllRooms();
$salasConInfo = [];

foreach ($salas as $sala) {
    $salaCompleta = $roomManager->getRoom($sala['codigo']);
    if ($salaCompleta && $salaCompleta['state'] === 'waiting') {
        $salasConInfo[] = $salaCompleta;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salas Disponibles - Buzz Game</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
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
        .user-info {
            background: #e9f7ef;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .room-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .room-info h3 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .room-stats {
            color: #666;
            font-size: 14px;
        }
        .join-btn {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .join-btn:hover {
            background: #0056b3;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        .refresh-btn {
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Información del usuario -->
        <div class="user-info">
            👋 Hola, <strong><?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario'); ?></strong> | 
            <a href="crear-sala.php">Crear Sala</a> | 
            <a href="logout.php">Cerrar sesión</a>
        </div>

        <h1>🎮 Salas Disponibles</h1>
        
        <button class="refresh-btn" onclick="window.location.reload()">🔄 Actualizar Lista</button>

        <?php if (empty($salasConInfo)): ?>
            <div class="empty-state">
                <h3>No hay salas disponibles</h3>
                <p>¡Sé el primero en crear una sala!</p>
                <a href="crear-sala.php" class="join-btn">Crear Nueva Sala</a>
            </div>
        <?php else: ?>
            <?php foreach ($salasConInfo as $sala): ?>
                <div class="room-card">
                    <div class="room-info">
                        <h3><?php echo htmlspecialchars($sala['name']); ?></h3>
                        <div class="room-stats">
                            👥 Jugadores: <?php echo count($sala['players']); ?>/<?php echo $sala['max_players']; ?> | 
                            🎯 Código: <?php echo $sala['code']; ?> |
                            🕐 Estado: <?php echo ucfirst($sala['state']); ?>
                        </div>
                    </div>
                    <a href="unirse-sala.php?code=<?php echo $sala['code']; ?>" class="join-btn">
                        Unirse
                    </a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        // Auto-refresh cada 10 segundos
        setInterval(() => {
            window.location.reload();
        }, 10000);
    </script>
</body>
</html>