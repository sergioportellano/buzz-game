<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../src/Core/Database.php';
require_once '../src/Core/AuthManager.php';
require_once '../src/Core/RoomManager.php';
require_once '../src/Core/GameEngine.php';
require_once '../src/Core/ConfigManager.php';

header('Content-Type: application/json');

$db = new Database();
$auth = new AuthManager($db);
$roomManager = new RoomManager($db);
$config = require '../config/game-settings.php';
$configManager = new ConfigManager($config);
$gameEngine = new GameEngine($roomManager, $configManager);

if (!$auth->estaLogueado()) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit();
}

$roomCode = $_POST['room_code'] ?? '';
$playerId = $_POST['player_id'] ?? '';

if (!$roomCode || !$playerId) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit();
}

// Verificar que el jugador es el anfitrión
if (!$roomManager->isCreator($roomCode, $playerId)) {
    echo json_encode(['success' => false, 'error' => 'Solo el anfitrión puede iniciar el juego']);
    exit();
}

// Iniciar el juego
$room = $roomManager->getRoom($roomCode);
if ($room && $room['state'] === 'waiting' && count($room['players']) >= 2) {
    $gameEngine->startGame($roomCode);
    echo json_encode(['success' => true, 'message' => 'Juego iniciado']);
} else {
    echo json_encode(['success' => false, 'error' => 'No se puede iniciar el juego']);
}
?>