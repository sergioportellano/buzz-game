<?php
require_once '../src/Core/Database.php';
require_once '../src/Core/RoomManager.php';

$config = require '../config/game-settings.php';
$db = new Database($config['database']);
$roomManager = new RoomManager($db);

if (isset($_GET['code'])) {
    $roomCode = $_GET['code'];
    $room = $roomManager->getRoom($roomCode);
    
    if ($room && isset($room['players'])) {
        $players = $room['players'];
        if (empty($players)) {
            echo '<li>No hay jugadores en la sala</li>';
        } else {
            foreach ($players as $player) {
                echo '<li>' . htmlspecialchars($player['name']) . ' - Puntos: ' . $player['score'] . '</li>';
            }
        }
    }
}
?>