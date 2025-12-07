<?php
require_once '../src/Core/GameEngine.php';
require_once '../src/Core/RoomManager.php';
require_once '../src/Rounds/RoundFactory.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? null;
$roomCode = $_GET['room_code'] ?? null;

if ($action === 'start_round' && $roomCode) {
    $roomManager = new RoomManager();
    $room = $roomManager->getRoomByCode($roomCode);

    if ($room) {
        $gameEngine = new GameEngine($room);
        $round = $gameEngine->startNextRound();
        echo json_encode(['status' => 'success', 'round' => $round]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Room not found.']);
    }
} elseif ($action === 'player_action' && $roomCode) {
    $playerId = $_POST['player_id'] ?? null;
    $actionType = $_POST['action_type'] ?? null;
    $data = $_POST['data'] ?? [];

    if ($playerId && $actionType) {
        $roomManager = new RoomManager();
        $room = $roomManager->getRoomByCode($roomCode);

        if ($room) {
            $gameEngine = new GameEngine($room);
            $result = $gameEngine->handlePlayerAction($playerId, $actionType, $data);
            echo json_encode(['status' => 'success', 'result' => $result]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Room not found.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid player action.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
}
?>