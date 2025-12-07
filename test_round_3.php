<?php
require_once __DIR__ . '/src/Core/GameManager.php';
require_once __DIR__ . '/src/Core/RoomManager.php';
require_once __DIR__ . '/src/Core/Database.php';

$db = new Database();
$roomManager = new RoomManager($db);

// Mock Room
$roomCode = 'T_R3_' . rand(1000, 9999);
$created = $roomManager->createRoom($roomCode, 1, 4, 'TestR3');
if (!$created)
    die("Error creating room\n");

$gm = new GameManager($roomManager, $db);
// Init game (sets round -1 -> 0)
$gm->initializeGame($roomCode);

echo "R1: " . $gm->getGameState()['current_round'] . "\n";
$gm->startNextRound(); // R2
echo "R2: " . $gm->getGameState()['current_round'] . "\n";
$gm->startNextRound(); // R3
echo "R3: " . $gm->getGameState()['current_round'] . "\n";

$state = $gm->getCurrentRound()->getState();
// Check if options exist
$options = $state['question']['respuestas'] ?? [];
echo "R3 Options Count: " . count($options) . "\n";
print_r($options);
