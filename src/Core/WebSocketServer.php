<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class GameWebSocketServer implements MessageComponentInterface
{
    protected $clients;
    protected $rooms;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->rooms = [];
        error_log("🎮 Servidor WebSocket de juego iniciado");
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        error_log("👋 Nueva conexión: {$conn->resourceId}");
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        try {
            $data = json_decode($msg, true);
            error_log("💬 Mensaje recibido: " . $msg);

            if (!$data || !isset($data['action'])) {
                return;
            }

            switch ($data['action']) {
                case 'join_room':
                    $this->handleJoinRoom($from, $data);
                    break;
                case 'start_game':
                    $this->handleStartGame($from, $data);
                    break;
                case 'player_joined':
                    $this->broadcastToRoom($data['room_code'], [
                        'action' => 'player_joined',
                        'player_name' => $data['player_name'],
                        'players_count' => $data['players_count']
                    ]);
                    break;
                case 'ping':
                    $from->send(json_encode(['action' => 'pong']));
                    break;
                // En WebSocketServer.php, agregar estos casos en onMessage:
                case 'join_game':
                    $this->handleJoinGame($from, $data);
                    break;
                case 'buzz':
                    $this->handleGameBuzz($from, $data);
                    break;
                case 'answer':
                    $this->handleGameAnswer($from, $data);
                    break;
                case 'pass_bomb':
                    $this->handlePassBomb($from, $data);
                    break;
                case 'next_round':
                    $this->handleNextRound($from, $data);
                    break;
                case 'get_timer':
                    $this->handleGetTimer($from, $data);
                    break;
            }
        } catch (\Exception $e) {
            error_log("❌ Error procesando mensaje: " . $e->getMessage());
        }
    }

    private function handleJoinRoom(ConnectionInterface $conn, $data)
    {
        $roomCode = $data['room_code'];
        $playerId = $data['player_id'];

        // Registrar conexión en la sala
        if (!isset($this->rooms[$roomCode])) {
            $this->rooms[$roomCode] = [];
        }

        $this->rooms[$roomCode][$playerId] = $conn;
        $conn->room = $roomCode;
        $conn->playerId = $playerId;

        error_log("🎯 Jugador $playerId unido a sala $roomCode via WebSocket");

        // Confirmar unión
        $conn->send(json_encode([
            'action' => 'joined_room',
            'room_code' => $roomCode,
            'status' => 'success'
        ]));
    }

    private function handleStartGame(ConnectionInterface $from, $data)
    {
        $roomCode = $data['room_code'];

        error_log("🚀 Iniciando juego en sala: $roomCode");

        // Broadcast a todos en la sala para redirigir
        $this->broadcastToRoom($roomCode, [
            'action' => 'game_starting',
            'redirect_url' => "juego.php?code=$roomCode",
            'timestamp' => time()
        ]);
    }

    private function broadcastToRoom($roomCode, $message)
    {
        if (!isset($this->rooms[$roomCode])) {
            return;
        }

        $jsonMessage = json_encode($message);
        $count = 0;

        foreach ($this->rooms[$roomCode] as $playerId => $client) {
            if ($client instanceof ConnectionInterface) {
                $client->send($jsonMessage);
                $count++;
            }
        }

        error_log("📢 Broadcast a $count jugadores en sala $roomCode: " . $jsonMessage);
    }

    protected $games = []; // RoomCode => GameManager instance

    // ... (existing methods)

    private function getGameManager($roomCode)
    {
        if (!isset($this->games[$roomCode])) {
            require_once __DIR__ . '/Database.php';
            require_once __DIR__ . '/RoomManager.php';
            require_once __DIR__ . '/GameManager.php';

            // Re-use connection? A long running script might need ping/reconnect features, 
            // but for now simplistic approach:
            $db = new \Database();
            $roomManager = new \RoomManager($db);
            $this->games[$roomCode] = new \GameManager($roomManager, $db);

            // Try to initialize/sync game state from DB basics?
            // initializeGame re-starts sequence. We might want to just load it.
            // For now, if it's new in memory, we might be resetting round state if process restarted.
            // This is a known limitation without full persistence.
            $this->games[$roomCode]->initializeGame($roomCode);
        }
        return $this->games[$roomCode];
    }

    private function handleJoinGame(ConnectionInterface $conn, $data)
    {
        $roomCode = $data['room_code'];
        $this->handleJoinRoom($conn, $data); // Ensure connection is registered

        $game = $this->getGameManager($roomCode);
        $gameState = $game->getGameState();

        $conn->send(json_encode([
            'action' => 'round_started',
            'current_round' => $gameState['current_round'],
            'round_type' => $gameState['round_type'],
            'round_data' => $gameState['round_data']
        ]));
    }

    private function handleGameBuzz(ConnectionInterface $conn, $data)
    {
        $roomCode = $data['room_code'];
        $playerId = $data['player_id'];

        $game = $this->getGameManager($roomCode);
        $result = $game->handlePlayerAction($playerId, 'buzz', []);

        if ($result && $result['success']) {
            $this->broadcastToRoom($roomCode, [
                'action' => 'player_buzzed',
                'playerId' => $result['player_id'],
                'position' => 1 // Simple logic for now
            ]);
        }
    }

    private function handleGameAnswer(ConnectionInterface $conn, $data)
    {
        $roomCode = $data['room_code'];
        $playerId = $data['player_id'];
        $answerIdx = $data['answer_index'] ?? null;

        // Map index to answer logic? 
        // TodosResponden passes 'answer' => string. 
        // Front sends index. We need to map or rounds need to handle index.
        // Let's assume frontend sends the actual text? No, it sends index inside handleAnswer(index).
        // Update Frontend or Backend? Updating Backend mapping is safer.

        // Actually, TodosRespondenRound expects $data['answer'] (string?).
        // Let's check TodosRespondenRound.
        // It compares $answer === $correct.
        // My previous code: $q['options'] = array_column($q['respuestas'], 'respuesta');
        // So index corresponds to options array.

        $game = $this->getGameManager($roomCode);
        $gameState = $game->getGameState();
        $roundData = $gameState['round_data'];

        // Safe mapping
        $answerText = '';
        if (isset($roundData['question']['options'][$answerIdx])) {
            $answerText = $roundData['question']['options'][$answerIdx];
        }

        // For BuzzRapido, it usually works with text too? Or binary?
        // BuzzRapidoRound logic: $answer === $this->currentQuestion['correct']

        $result = $game->handlePlayerAction($playerId, 'answer', ['answer' => $answerText]);

        // Broadcast result usually needed? Or just score update?
        // If "TodosResponden", we might want to ACK the answer or wait for round end.
        // For "BuzzRapido", knowing if correct/incorrect is immediate.

        if ($result) {
            if ($result['success']) {
                // Check if timeout occurred
                if (isset($result['timeout']) && $result['timeout']) {
                    $this->broadcastToRoom($roomCode, [
                        'action' => 'round_timeout',
                        'message' => 'Tiempo agotado'
                    ]);
                    return;
                }

                $this->broadcastToRoom($roomCode, [
                    'action' => 'answer_result',
                    'success' => true,
                    'correct' => $result['correct'] ?? false,
                    'round_over' => $result['round_over'] ?? false,
                    'playerId' => $playerId,
                    'answer_text' => $answerText, // Broadcast the answer text to highlight
                    'answer_index' => $answerIdx, // Broadcast the answer index for consistent highlighting
                    'scores' => $game->getGameState()['round_data']['scores'] ?? []
                ]);
            } else {
                $conn->send(json_encode([
                    'action' => 'round_update',
                    'event' => $result
                ]));
            }
        }
    }

    private function handleNextRound(ConnectionInterface $conn, $data)
    {
        $roomCode = $data['room_code'];
        $game = $this->getGameManager($roomCode);

        // Advance round
        $game->startNextRound();

        // Broadcast new round state to EVERYONE
        $gameState = $game->getGameState();

        $this->broadcastToRoom($roomCode, [
            'action' => 'round_started',
            'current_round' => $gameState['current_round'],
            'round_type' => $gameState['round_type'],
            'round_data' => $gameState['round_data']
        ]);
    }

    private function handleGetTimer(ConnectionInterface $conn, $data)
    {
        $roomCode = $data['room_code'];
        $game = $this->getGameManager($roomCode);
        $gameState = $game->getGameState();

        // Send timer info back to requesting client
        $conn->send(json_encode([
            'action' => 'timer_update',
            'remaining_time' => $gameState['round_data']['remaining_time'] ?? 40,
            'timer_paused' => $gameState['round_data']['timer_paused'] ?? false
        ]));
    }

    private function handlePassBomb(ConnectionInterface $conn, $data)
    {
        $roomCode = $data['room_code'];
        $playerId = $data['player_id'];

        $game = $this->getGameManager($roomCode);
        $result = $game->handlePlayerAction($playerId, 'pass_bomb', []);

        if ($result && $result['success']) {
            $this->broadcastToRoom($roomCode, [
                'action' => 'bomb_passed',
                'playerId' => $result['new_holder'],
                'remaining_time' => 30 // hardcoded or from state
            ]);
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        // ... (existing onClose)
        // Helper to avoid duplicate huge blocks
        if (isset($conn->room) && isset($conn->playerId)) {
            $roomCode = $conn->room;
            $playerId = $conn->playerId;

            if (isset($this->rooms[$roomCode][$playerId])) {
                unset($this->rooms[$roomCode][$playerId]);
                error_log("👋 Jugador $playerId desconectado de sala $roomCode");

                // Keep game alive even if player disconnects for reconnections
            }
            $this->clients->detach($conn);
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        error_log("⚠️ Error WebSocket: {$e->getMessage()}");
        $conn->close();
    }
}

?>