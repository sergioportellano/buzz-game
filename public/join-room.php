<?php
require_once '../src/Core/Database.php';
require_once '../src/Core/RoomManager.php';

$config = require '../config/game-settings.php';
$db = new Database($config['database']);
$roomManager = new RoomManager($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roomCode = $_POST['room_code'] ?? '';
    $playerName = $_POST['player_name'] ?? '';
    
    if ($roomCode && $playerName) {
        $playerId = uniqid(); // Generar ID único para el jugador
        
        if ($roomManager->joinRoom($roomCode, $playerId, $playerName)) {
            // Guardar en sesión
            session_start();
            $_SESSION['player_id'] = $playerId;
            $_SESSION['player_name'] = $playerName;
            $_SESSION['current_room'] = $roomCode;
            
            // Redirigir a la sala
            header("Location: sala.php?code=$roomCode");
            exit();
        } else {
            // Error al unirse
            header("Location: sala.php?code=$roomCode&error=join_failed");
            exit();
        }
    }
}

// Si no es POST, redirigir a crear sala
header('Location: crear-sala.php');
exit();
?>