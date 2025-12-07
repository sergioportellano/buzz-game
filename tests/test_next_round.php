<?php
require_once __DIR__ . '/../src/Core/GameManager.php';
require_once __DIR__ . '/../src/Core/RoomManager.php';
require_once __DIR__ . '/../src/Core/Database.php';

$db = new Database();
$roomManager = new RoomManager($db);

// Short code for compatibility
$roomCode = 'T' . rand(10000, 99999);
$created = $roomManager->createRoom($roomCode, 1, 4, 'TestRoom');
if (!$created)
    die("Error creating room (Maybe duplicate T code, try again)\n");

echo "Room Created: $roomCode\n";

// Player 1 (Creator/Host) is already in room per createRoom logic (ID 1).

$gm = new GameManager($roomManager, $db);
$gm->restoreGameState($roomCode);

echo "Ronda Inicial: " . $gm->getGameState()['current_round'] . "\n";
// Sometimes currentRound is not init if round is 0?
// gameManager->restoreGameState initializes it.
// Default round 1.

$q1 = $gm->getCurrentRound()->getState()['question'];
echo "Pregunta 1: " . ($q1['text'] ?? 'N/A') . " (Audio: " . ($q1['archivo_audio'] ?? 'N/A') . ")\n";

echo "------------------------------------------------\n";
echo "Avanzando Ronda...\n";
$gm->startNextRound();

echo "Ronda 2: " . $gm->getGameState()['current_round'] . "\n";
$q2 = $gm->getCurrentRound()->getState()['question'];
echo "Pregunta 2: " . ($q2['text'] ?? 'N/A') . " (Audio: " . ($q2['archivo_audio'] ?? 'N/A') . ")\n";

if (($q1['text'] ?? '') === ($q2['text'] ?? '')) {
    echo "❌ ERROR: La pregunta es la misma.\n";
} else {
    echo "✅ ÉXITO: La pregunta cambió.\n";
}
