<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../src/Core/Database.php';
require_once '../src/Core/AuthManager.php';
require_once '../src/Core/RoomManager.php';

header('Content-Type: application/json');

$db = new Database();
$auth = new AuthManager($db);
$roomManager = new RoomManager($db);

if (!$auth->estaLogueado()) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit();
}

$roomCode = $_GET['code'] ?? '';
if (!$roomCode) {
    echo json_encode(['success' => false, 'error' => 'No room code']);
    exit();
}

$room = $roomManager->getRoom($roomCode);
if (!$room) {
    echo json_encode(['success' => false, 'error' => 'Sala no encontrada']);
    exit();
}

echo json_encode([
    'success' => true,
    'room' => [
        'players' => $room['players'],
        'state' => $room['state'],
        'current_round' => $room['current_round'],
        'max_players' => $room['max_players'],
        'creator_id' => $room['creator_id']
    ]
]);
?>