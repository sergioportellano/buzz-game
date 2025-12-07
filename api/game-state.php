<?php
// This file provides an API endpoint for retrieving the current state of the game.

require_once '../src/Core/GameEngine.php';
require_once '../src/Core/RoomManager.php';

$roomManager = new RoomManager();
$gameEngine = new GameEngine();

header('Content-Type: application/json');

if (isset($_GET['room_code'])) {
    $roomCode = $_GET['room_code'];
    $room = $roomManager->getRoom($roomCode);

    if ($room) {
        $gameState = $gameEngine->getGameState($room);
        echo json_encode([
            'success' => true,
            'data' => $gameState
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Room not found.'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Room code is required.'
    ]);
}
?>