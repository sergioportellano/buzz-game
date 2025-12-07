<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../src/Core/Database.php';
require_once '../src/Core/AuthManager.php';
require_once '../src/Core/RoomManager.php';
require_once '../src/Core/GameEngine.php';
require_once '../src/Core/ConfigManager.php';
require_once '../src/Core/GameManager.php';

$config = require '../config/game-settings.php';
$db = new Database();
$auth = new AuthManager($db);
$roomManager = new RoomManager($db);
$configManager = new ConfigManager($config);
$gameEngine = new GameEngine($roomManager, $configManager);
$gameManager = new GameManager($roomManager, $db);

// Verificar login y sala
if (!$auth->estaLogueado()) {
    header("Location: login.php");
    exit();
}

$roomCode = $_GET['code'] ?? '';
if ($roomCode) {
    // Restore Game State to handle page reloads (Active Record Pattern-ish)
    $gameManager->restoreGameState($roomCode);
}
if (!$roomCode) {
    header('Location: crear-sala.php?error=no_room_code');
    exit();
}

$room = $roomManager->getRoom($roomCode);
if (!$room) {
    header('Location: crear-sala.php?error=room_not_found');
    exit();
}

// Verificar que el jugador esté en la sala
$playerId = $auth->obtenerUsuarioActual();
$playerInRoom = false;

if ($playerId) {
    foreach ($room['players'] as $player) {
        if ((string) $player['id'] === (string) $playerId) {
            $playerInRoom = true;
            break;
        }
    }
}

if (!$playerInRoom) {
    header("Location: unirse-sala.php?code=$roomCode");
    exit();
}

// Inicializar juego si es la primera vez
if ($room['state'] === 'jugando' && $room['current_round'] === 1) {
    $gameManager->initializeGame($roomCode);
}

// Manejar acciones del jugador
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'start_game':
            if ($roomManager->isCreator($roomCode, $playerId)) {
                $roomManager->startGame($roomCode);
                // Initialize game sequence logic
                $gameManager->initializeGame($roomCode);
                // Redirect to avoid form resubmission/stay on page for refresh
                header("Location: juego.php?code=$roomCode");
                exit();
            }
            break;
        case 'buzz':
            $result = $gameManager->handlePlayerAction($playerId, 'buzz', []);
            break;
        case 'answer':
            $answer = $_POST['answer'] ?? '';
            $result = $gameManager->handlePlayerAction($playerId, 'answer', ['answer' => $answer]);
            break;
        case 'next_round':
            $gameManager->startNextRound();
            break;
        case 'pass_bomb':
            $gameManager->handlePlayerAction($playerId, 'pass_bomb', []);
            break;
    }

    // Recargar la sala después de la acción
    $room = $roomManager->getRoom($roomCode);
}

// Si el juego no ha empezado, mostrar pantalla de espera
if ($room['state'] === 'waiting') {
    ?>
    <!DOCTYPE html>
    <html lang="es">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Esperando para empezar - <?php echo htmlspecialchars($room['name']); ?></title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 20px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .container {
                background: rgba(255, 255, 255, 0.1);
                padding: 40px;
                border-radius: 15px;
                backdrop-filter: blur(10px);
                text-align: center;
                max-width: 600px;
                width: 100%;
            }

            h1 {
                margin-bottom: 30px;
                font-size: 2.5em;
            }

            .players-list {
                list-style: none;
                padding: 0;
                margin: 30px 0;
            }

            .players-list li {
                background: rgba(255, 255, 255, 0.2);
                padding: 15px;
                margin: 10px 0;
                border-radius: 8px;
                font-size: 1.2em;
            }

            .start-btn {
                background: #4CAF50;
                color: white;
                padding: 15px 30px;
                border: none;
                border-radius: 8px;
                font-size: 1.2em;
                cursor: pointer;
                margin: 20px 0;
                transition: background 0.3s;
            }

            .start-btn:hover {
                background: #45a049;
            }

            .start-btn:disabled {
                background: #666;
                cursor: not-allowed;
            }

            .room-code {
                background: rgba(255, 255, 255, 0.2);
                padding: 10px 20px;
                border-radius: 8px;
                font-size: 1.5em;
                font-weight: bold;
                margin: 20px 0;
            }

            .user-info {
                background: rgba(255, 255, 255, 0.2);
                padding: 10px;
                border-radius: 8px;
                margin-bottom: 20px;
            }

            /* Countdown overlay styles */
            .countdown-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.95);
                display: none;
                align-items: center;
                justify-content: center;
                z-index: 9999;
                flex-direction: column;
            }

            .countdown-number {
                font-size: 15em;
                font-weight: bold;
                color: #4CAF50;
                text-shadow: 0 0 30px rgba(76, 175, 80, 0.8);
                animation: pulse 1s ease-in-out;
            }

            .countdown-text {
                font-size: 2em;
                margin-top: 20px;
                color: white;
            }

            @keyframes pulse {
                0% {
                    transform: scale(0.5);
                    opacity: 0;
                }

                50% {
                    transform: scale(1.2);
                }

                100% {
                    transform: scale(1);
                    opacity: 1;
                }
            }
        </style>
    </head>

    <body>
        <div class="container" id="waiting-container">
            <!-- Información del usuario -->
            <div class="user-info">
                👋 Hola, <strong><?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario'); ?></strong>
                <?php if ($roomManager->isCreator($roomCode, $playerId)): ?>
                    <span
                        style="background: #ffc107; color: #856404; padding: 3px 8px; border-radius: 12px; font-size: 12px; margin-left: 10px;">Anfitrión</span>
                <?php endif; ?>
            </div>

            <h1>🕹️ <?php echo htmlspecialchars($room['name']); ?></h1>

            <div class="room-code">
                Código: <?php echo $roomCode; ?>
            </div>

            <h2>Jugadores conectados (<?php echo count($room['players']); ?>/<?php echo $room['max_players']; ?>):</h2>
            <ul class="players-list">
                <?php foreach ($room['players'] as $player): ?>
                    <li>
                        🎮 <?php echo htmlspecialchars($player['name']); ?>
                        <?php if ((string) $player['id'] === (string) $playerId): ?>
                            <strong>(Tú)</strong>
                        <?php endif; ?>
                        <?php if ($player['id'] == $room['creator_id']): ?>
                            <span
                                style="background: #ffc107; color: #856404; padding: 2px 6px; border-radius: 10px; font-size: 11px; margin-left: 8px;">Anfitrión</span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>

            <?php if ($roomManager->isCreator($roomCode, $playerId)): ?>
                <button type="button" class="start-btn" id="start-game-btn" onclick="startGameWithCountdown()" <?php echo (count($room['players']) < 2) ? 'disabled' : ''; ?>>
                    🚀 Iniciar Juego
                </button>
                <?php if (count($room['players']) < 2): ?>
                    <p>Esperando al menos 2 jugadores...</p>
                <?php endif; ?>
            <?php else: ?>
                <p>⏳ Esperando a que el anfitrión inicie el juego...</p>
            <?php endif; ?>

            <p>Comparte el código <strong><?php echo $roomCode; ?></strong> con otros jugadores</p>
        </div>

        <!-- Countdown Overlay -->
        <div class="countdown-overlay" id="countdown-overlay">
            <div class="countdown-number" id="countdown-number">3</div>
            <div class="countdown-text">El juego comienza en...</div>
        </div>

        <script>
            const roomCode = '<?php echo $roomCode; ?>';
            const playerId = '<?php echo $playerId; ?>';
            let ws = null;
            let reloadTimer = null;

            function connectWaitingWebSocket() {
                try {
                    const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
                    const wsUrl = `${protocol}//${window.location.hostname}:8080`;

                    ws = new WebSocket(wsUrl);

                    ws.onopen = function () {
                        console.log('✅ Conectado al WebSocket de espera');
                        ws.send(JSON.stringify({
                            action: 'join_room',
                            room_code: roomCode,
                            player_id: playerId
                        }));
                    };

                    ws.onmessage = function (event) {
                        const data = JSON.parse(event.data);
                        console.log('📨 Mensaje recibido:', data);

                        switch (data.action) {
                            case 'game_starting':
                                // Clear reload timer when game starts
                                if (reloadTimer) {
                                    clearTimeout(reloadTimer);
                                    reloadTimer = null;
                                }
                                showCountdown(data.redirect_url);
                                break;
                            case 'player_joined':
                                // Reload to show new player
                                window.location.reload();
                                break;
                        }
                    };

                    ws.onclose = function () {
                        console.log('🔌 Conexión WebSocket cerrada');
                        setTimeout(connectWaitingWebSocket, 3000);
                    };

                } catch (error) {
                    console.error('Error conectando WebSocket:', error);
                }
            }

            function startGameWithCountdown() {
                const btn = document.getElementById('start-game-btn');
                btn.disabled = true;
                btn.textContent = '⏳ Iniciando...';

                if (ws && ws.readyState === WebSocket.OPEN) {
                    ws.send(JSON.stringify({
                        action: 'start_game',
                        room_code: roomCode
                    }));
                } else {
                    alert('Error: No hay conexión WebSocket. Recargando...');
                    window.location.reload();
                }
            }

            function showCountdown(redirectUrl) {
                const overlay = document.getElementById('countdown-overlay');
                const numberEl = document.getElementById('countdown-number');
                overlay.style.display = 'flex';

                let count = 3;
                numberEl.textContent = count;

                const interval = setInterval(() => {
                    count--;
                    if (count > 0) {
                        numberEl.textContent = count;
                        // Re-trigger animation
                        numberEl.style.animation = 'none';
                        setTimeout(() => {
                            numberEl.style.animation = 'pulse 1s ease-in-out';
                        }, 10);
                    } else {
                        clearInterval(interval);
                        numberEl.textContent = '¡GO!';
                        numberEl.style.color = '#FF9800';
                        setTimeout(() => {
                            window.location.href = redirectUrl;
                        }, 500);
                    }
                }, 1000);
            }

            // Connect WebSocket on page load
            document.addEventListener('DOMContentLoaded', function () {
                connectWaitingWebSocket();

                // Set reload timer (will be cleared if game starts)
                reloadTimer = setTimeout(() => {
                    window.location.reload();
                }, 3000);
            });
        </script>
    </body>

    </html>
    <?php
    exit();
}

// Obtener estado actual del juego
$gameState = $gameManager->getGameState();
$currentRound = $gameManager->getCurrentRound();

// Funciones helper para renderizar contenido
function getRoundTypeName($roundType)
{
    $names = [
        'buzz_rapido' => 'Buzz Rápido',
        'todos_responden' => 'Todos Responden',
        'bomba_musical' => 'Bomba Musical'
    ];
    return $names[$roundType] ?? $roundType;
}

function getRoundDescription($roundType)
{
    $descriptions = [
        'buzz_rapido' => '¡El primero en buzz gana! Responde rápido para sumar puntos.',
        'todos_responden' => 'Todos pueden responder. ¡Cuidado con las respuestas incorrectas!',
        'bomba_musical' => '¡Pasa la bomba antes de que explote! El último en tenerla pierde.'
    ];
    return $descriptions[$roundType] ?? '';
}

function renderRoundContent($gameState, $currentRound)
{
    $roundType = $gameState['round_type'] ?? '';

    switch ($roundType) {
        case 'buzz_rapido':
            return renderBuzzRapidoContent($currentRound);
        case 'todos_responden':
            return renderTodosRespondenContent($currentRound);
        case 'bomba_musical':
            return renderBombaMusicalContent($currentRound);
        default:
            return '<div class="question-container"><p>🎮 Preparando ronda...</p></div>';
    }
}

function renderBuzzRapidoContent($currentRound)
{
    $roundData = $currentRound ? $currentRound->getRoundData() : [];
    $question = $roundData['question'] ?? []; // Extract question from round state

    // Debug helper (remove in prod)
    if (empty($question))
        error_log("BuzzContent: Question is empty!");

    // Options mapping (DB uses 'respuestas', Mock uses 'options')
    $options = $question['respuestas'] ?? ($question['options'] ?? []);

    $optionsHtml = '';
    foreach ($options as $index => $option) {
        // Handle both DB array format and Mock string format
        $text = is_array($option) ? ($option['respuesta'] ?? 'Opción') : $option;

        $optionsHtml .= '
            <button class="answer-btn" onclick="handleAnswer(' . $index . ')" disabled>
                ' . chr(65 + $index) . ') ' . htmlspecialchars($text) . '
            </button>
        ';
    }

    $audioFile = $question['archivo_audio'] ?? '';
    // Quick debug visual
    $debugInfo = empty($audioFile) ? '<small style="color:red">Audio no encontrado</small>' : '';

    return '
        <div class="question-container">
            <div class="question-text">
                🎼 ' . htmlspecialchars($question['pregunta'] ?? ($question['text'] ?? '¿De qué canción es este fragmento?')) . '
            </div>
            <div class="audio-player">
                <audio controls autoplay id="game-audio">
                    <source src="uploads/audio/' . htmlspecialchars($audioFile) . '" type="audio/mpeg">
                    Tu navegador no soporta audio.
                </audio>
                ' . $debugInfo . '
            </div>
            
            <!-- Buzz Button (Default View) -->
            <div id="buzz-container" class="buzz-container">
                <button type="button" class="buzz-btn" onclick="handleBuzz()">🚨 BUZZ!</button>
            </div>
            
            <div id="buzz-timer" style="display:none; color: #ff5722; font-weight: bold; font-size: 1.5em; margin: 15px 0;">
                ⏳ 15s
            </div>

            <!-- Answer Options (Visible but disabled until Buzz) -->
            <div id="answer-container" class="answers-grid">
                ' . $optionsHtml . '
            </div>

            <div id="buzz-order-container"></div>
        </div>
    ';
}

function renderTodosRespondenContent($currentRound)
{
    $roundData = $currentRound ? $currentRound->getRoundData() : [];
    $question = $roundData['question'] ?? [];
    $options = $question['respuestas'] ?? [];

    $optionsHtml = '';
    foreach ($options as $index => $option) {
        $optionsHtml .= '
            <button class="answer-btn" onclick="handleAnswer(' . $index . ')">
                ' . chr(65 + $index) . ') ' . ($option['texto'] ?? 'Opción ' . ($index + 1)) . '
            </button>
        ';
    }

    return '
        <div class="question-container">
            <div class="question-text">
                🎼 ' . ($question['pregunta'] ?? 'Escucha y selecciona la respuesta correcta') . '
            </div>
            <div class="audio-player">
                <audio controls id="game-audio">
                    <source src="uploads/audio/' . htmlspecialchars($question['archivo_audio'] ?? '#') . '" type="audio/mpeg">
                    Tu navegador no soporta audio.
                </audio>
            </div>
            <div class="answers-grid">
                ' . $optionsHtml . '
            </div>
        </div>
    ';
}

function renderBombaMusicalContent($currentRound)
{
    return '
        <div class="question-container">
            <div class="question-text">
                💣 ¡Bomba Musical!
            </div>
            <div class="audio-player">
                <audio controls id="game-audio">
                    <source src="#" type="audio/mpeg">
                    Música de fondo
                </audio>
            </div>
            <div class="buzz-container">
                <button type="button" class="buzz-btn" onclick="handlePassBomb()" style="background: #ff9800;">
                    💣 PASAR BOMBA
                </button>
            </div>
            <div id="bomb-timer-container"></div>
        </div>
    ';
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Juego - <?php echo htmlspecialchars($room['name']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #1a1a1a;
            color: white;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .game-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: #2d2d2d;
            border-radius: 10px;
        }

        .question-container {
            background: #2d2d2d;
            padding: 30px;
            border-radius: 15px;
            margin: 20px 0;
            text-align: center;
        }

        .question-text {
            font-size: 1.5em;
            margin-bottom: 20px;
        }

        .answers-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 30px 0;
        }

        .answer-btn {
            background: #404040;
            color: white;
            padding: 20px;
            border: none;
            border-radius: 10px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background 0.3s, opacity 0.3s;
        }

        .answer-btn:hover:not(:disabled) {
            background: #505050;
        }

        /* Disabled state only for initial state, not for answered buttons */
        .answer-btn:disabled:not([style*="background"]) {
            background: #2a2a2a;
            color: #666;
            cursor: not-allowed;
            opacity: 0.5;
        }

        .buzz-container {
            text-align: center;
            margin: 30px 0;
        }

        .buzz-btn {
            background: #ff4444;
            color: white;
            padding: 20px 40px;
            border: none;
            border-radius: 50px;
            font-size: 1.5em;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .buzz-btn:hover {
            transform: scale(1.05);
        }

        .buzz-btn:active {
            transform: scale(0.95);
        }

        .players-score {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin: 20px 0;
        }

        .player-card {
            background: #007bff;
            padding: 15px;
            border-radius: 8px;
            min-width: 120px;
            text-align: center;
        }

        .current-player {
            background: #28a745;
        }

        .user-info {
            background: #2d2d2d;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        .round-info {
            background: #333;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .audio-sync {
            background: #4CAF50;
            color: white;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            text-align: center;
        }

        .buzz-order {
            display: flex;
            gap: 10px;
            margin: 15px 0;
            flex-wrap: wrap;
            justify-content: center;
        }

        .buzz-player {
            background: #FF9800;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
        }

        .bomb-timer {
            background: #f44336;
            color: white;
            padding: 20px;
            border-radius: 50%;
            width: 100px;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 20px auto;
            font-size: 24px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Información del usuario -->
        <div class="user-info">
            👋 Hola, <strong><?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario'); ?></strong>
            <?php if ($roomManager->isCreator($roomCode, $playerId)): ?>
                <span
                    style="background: #ffc107; color: #856404; padding: 3px 8px; border-radius: 12px; font-size: 12px; margin-left: 10px;">Anfitrión</span>
            <?php endif; ?>
        </div>

        <div class="game-header">
            <h1>🎵 <?php echo htmlspecialchars($room['name']); ?></h1>
            <div class="room-info">
                <strong>Ronda:</strong> <?php echo $room['current_round']; ?> |
                <strong>Tipo:</strong> <?php echo getRoundTypeName($gameState['round_type'] ?? ''); ?>
            </div>
            <!-- Timer display for Buzz rounds -->
            <div id="round-timer" style="display: none; margin-top: 10px;">
                <div
                    style="background: #2196F3; color: white; padding: 10px 20px; border-radius: 20px; font-size: 1.2em; font-weight: bold; display: inline-block;">
                    ⏱️ Tiempo: <span id="timer-value">40</span>s
                </div>
            </div>
        </div>

        <!-- Información de la ronda -->
        <div class="round-info">
            <h3 id="round-title">🎮 Ronda <?php echo $room['current_round']; ?> -
                <?php echo getRoundTypeName($gameState['round_type'] ?? ''); ?>
            </h3>
            <div id="round-description"><?php echo getRoundDescription($gameState['round_type'] ?? ''); ?></div>
        </div>

        <!-- Contenido dinámico según el tipo de ronda -->
        <div id="round-content">
            <?php echo renderRoundContent($gameState, $currentRound); ?>
        </div>

        <!-- Lista de jugadores -->
        <div class="players-score">
            <?php foreach ($room['players'] as $player): ?>
                <div class="player-card <?php echo ((string) $player['id'] === (string) $playerId) ? 'current-player' : ''; ?>"
                    data-player-id="<?php echo $player['id']; ?>">
                    <strong><?php echo htmlspecialchars($player['name']); ?></strong><br>
                    <span class="score"><?php echo $player['score']; ?></span> puntos<br>
                    <?php echo $player['lives']; ?> ❤️
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- WebSocket para sincronización en tiempo real -->
    <script>
        const roomCode = '<?php echo $roomCode; ?>';
        const playerId = '<?php echo $playerId; ?>';
        let ws = null;

        function connectGameWebSocket() {
            try {
                const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
                const wsUrl = `${protocol}//${window.location.hostname}:8080`;

                ws = new WebSocket(wsUrl);

                ws.onopen = function () {
                    console.log('✅ Conectado al juego WebSocket');
                    ws.send(JSON.stringify({
                        action: 'join_game',
                        room_code: roomCode,
                        player_id: playerId
                    }));
                };

                ws.onmessage = function (event) {
                    const data = JSON.parse(event.data);
                    console.log('🎮 Mensaje de juego:', data);

                    switch (data.action) {
                        case 'audio_sync':
                            handleAudioSync(data.file, data.start_time);
                            break;
                        case 'player_buzzed':
                            updateBuzzOrder(data.playerId, data.position);
                            break;
                        case 'bomb_passed':
                            updateBombStatus(data.playerId, data.remaining_time);
                            break;
                        case 'bomb_exploded':
                            showBombExplosion(data.loser);
                            break;
                        case 'round_started':
                            updateRoundContent(data);
                            break;
                        case 'round_update':
                            handleRoundUpdate(data);
                            break;
                        case 'answer_result':
                            handleAnswerResult(data);
                            break;
                        case 'timer_update':
                            updateTimer(data.remaining_time, data.timer_paused);
                            break;
                        case 'round_timeout':
                            handleTimeout();
                            break;
                    }
                };

                ws.onclose = function () {
                    console.log('🔌 Conexión de juego cerrada');
                    setTimeout(connectGameWebSocket, 3000);
                };

            } catch (error) {
                console.error('Error conectando WebSocket de juego:', error);
            }
        }

        function handleAudioSync(audioFile, startTime) {
            const now = Math.floor(Date.now() / 1000);
            const delay = Math.max(0, startTime - now - 1); // 1 segundo de margen

            setTimeout(() => {
                const audio = new Audio(audioFile);
                audio.play().catch(e => console.log('Error reproduciendo audio:', e));
            }, delay * 1000);

            // Mostrar cuenta regresiva
            showAudioCountdown(delay);
        }

        function showAudioCountdown(seconds) {
            const countdownEl = document.createElement('div');
            countdownEl.className = 'audio-sync';
            countdownEl.innerHTML = `🎵 Reproduciendo en: <span id="audio-countdown">${seconds}</span>s`;
            document.getElementById('round-content').prepend(countdownEl);

            let count = seconds;
            const interval = setInterval(() => {
                count--;
                const span = document.getElementById('audio-countdown');
                if (span) span.textContent = count;

                if (count <= 0) {
                    clearInterval(interval);
                    setTimeout(() => countdownEl.remove(), 2000);
                }
            }, 1000);
        }

        let answerTimerInterval = null; // Global timer reference

        function updateBuzzOrder(pId, position) {
            let buzzOrderEl = document.getElementById('buzz-order');
            if (!buzzOrderEl) {
                buzzOrderEl = document.createElement('div');
                buzzOrderEl.id = 'buzz-order';
                buzzOrderEl.className = 'buzz-order';
                const container = document.getElementById('buzz-order-container');
                if (container) container.appendChild(buzzOrderEl);
            }

            const playerEl = document.createElement('div');
            playerEl.className = 'buzz-player';
            playerEl.textContent = `#${position} - Jugador ${pId}`;
            buzzOrderEl.appendChild(playerEl);

            // LOGIC FOR BUZZ RAPIDO:
            // 1. Hide Buzz Button for EVERYONE
            const buzzContainer = document.getElementById('buzz-container');
            if (buzzContainer) buzzContainer.style.display = 'none';

            // 2. Enable/Disable answer buttons based on who buzzed
            const answerContainer = document.getElementById('answer-container');
            if (answerContainer) {
                const buttons = answerContainer.querySelectorAll('button');
                buttons.forEach(btn => {
                    if (String(pId) !== String(playerId)) {
                        // Not me - keep disabled
                        btn.disabled = true;
                        btn.style.opacity = '0.6';
                        btn.style.cursor = 'not-allowed';
                    } else {
                        // It's me - enable buttons
                        btn.disabled = false;
                        btn.style.opacity = '1';
                        btn.style.cursor = 'pointer';
                    }
                });
            }

            // 3. Start 8-second timer if I'm the one who buzzed
            if (String(pId) === String(playerId)) {
                startAnswerTimer();
            }
        }

        function startAnswerTimer() {
            // Clear any existing timer
            if (answerTimerInterval) {
                clearInterval(answerTimerInterval);
            }

            let timeLeft = 8;

            // Create or update timer display
            let timerEl = document.getElementById('buzz-timer');
            if (!timerEl) {
                timerEl = document.createElement('div');
                timerEl.id = 'buzz-timer';
                timerEl.style.cssText = `
                    color: #ff5722;
                    font-weight: bold;
                    font-size: 1.8em;
                    margin: 15px 0;
                    text-align: center;
                    animation: pulse 1s ease-in-out infinite;
                `;
                const answerContainer = document.getElementById('answer-container');
                if (answerContainer) {
                    answerContainer.parentNode.insertBefore(timerEl, answerContainer);
                }
            }

            timerEl.style.display = 'block';
            timerEl.textContent = `⏱️ Tiempo para responder: ${timeLeft}s`;

            answerTimerInterval = setInterval(() => {
                timeLeft--;
                timerEl.textContent = `⏱️ Tiempo para responder: ${timeLeft}s`;

                if (timeLeft <= 3) {
                    timerEl.style.color = '#f44336';
                    timerEl.style.fontSize = '2.2em';
                }

                if (timeLeft <= 0) {
                    clearInterval(answerTimerInterval);
                    timerEl.textContent = '⏰ ¡Tiempo agotado!';

                    // Auto-submit incorrect answer (index -1 means timeout)
                    console.warn('⏰ Tiempo agotado, enviando respuesta incorrecta automática');

                    // Disable all answer buttons
                    const buttons = document.querySelectorAll('.answer-btn');
                    buttons.forEach(btn => btn.disabled = true);

                    // Send timeout to server (we'll send index 0 but server should handle timeout)
                    if (ws && ws.readyState === WebSocket.OPEN) {
                        ws.send(JSON.stringify({
                            action: 'answer',
                            room_code: roomCode,
                            player_id: playerId,
                            answer_index: -1, // Special index for timeout
                            timeout: true
                        }));
                    }

                    setTimeout(() => {
                        if (timerEl) timerEl.style.display = 'none';
                    }, 2000);
                }
            }, 1000);
        }

        function stopAnswerTimer() {
            if (answerTimerInterval) {
                clearInterval(answerTimerInterval);
                answerTimerInterval = null;
            }

            const timerEl = document.getElementById('buzz-timer');
            if (timerEl) {
                timerEl.style.display = 'none';
            }
        }

        function updateBombStatus(playerId, remainingTime) {
            let bombTimerEl = document.getElementById('bomb-timer');
            if (!bombTimerEl) {
                bombTimerEl = document.createElement('div');
                bombTimerEl.id = 'bomb-timer';
                bombTimerEl.className = 'bomb-timer';
                bombTimerEl.textContent = remainingTime;
                document.getElementById('round-content').appendChild(bombTimerEl);
            } else {
                bombTimerEl.textContent = remainingTime;
            }
        }

        function showBombExplosion(loserId) {
            const explosionEl = document.createElement('div');
            explosionEl.style.cssText = `
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: rgba(255,0,0,0.9);
                color: white;
                padding: 30px;
                border-radius: 15px;
                font-size: 2em;
                z-index: 10000;
                text-align: center;
            `;
            explosionEl.innerHTML = '💥 ¡BOMBA EXPLOTÓ!<br>Perdedor: ' + loserId;
            document.body.appendChild(explosionEl);

            setTimeout(() => explosionEl.remove(), 3000);
        }

        // Inicializar cuando la página carga
        document.addEventListener('DOMContentLoaded', function () {
            connectGameWebSocket();
        });

        // Manejar acciones del juego
        function handleBuzz() {
            if (ws && ws.readyState === WebSocket.OPEN) {
                ws.send(JSON.stringify({
                    action: 'buzz',
                    room_code: roomCode,
                    player_id: playerId
                }));
            }
        }

        function handleAnswer(answerIndex) {
            // Stop the answer timer
            stopAnswerTimer();

            if (ws && ws.readyState === WebSocket.OPEN) {
                ws.send(JSON.stringify({
                    action: 'answer',
                    room_code: roomCode,
                    player_id: playerId,
                    answer_index: answerIndex
                }));
            }
        }

        // Helper function to trigger the next round
        function triggerNextRound() {
            setTimeout(() => {
                if (<?php echo $roomManager->isCreator($roomCode, $playerId) ? 'true' : 'false'; ?>) {
                    if (ws && ws.readyState === WebSocket.OPEN) {
                        ws.send(JSON.stringify({
                            action: 'next_round',
                            room_code: roomCode
                        }));
                    }
                }
            }, 3000); // Wait 3s so everyone sees the colors
        }

        function handleAnswerResult(data) {
            console.log('📊 handleAnswerResult:', data);
            
            // Update Scores
            if (data.scores) {
                for (const [pId, score] of Object.entries(data.scores)) {
                    const card = document.querySelector(`.player-card[data-player-id="${pId}"] .score`);
                    if (card) {
                        card.textContent = score;
                    }
                }
            }

            // Visual Feedback - AGGRESSIVE COLOR APPLICATION
            if (data.answer_index !== undefined) {
                const buttons = document.querySelectorAll('.answer-btn');
                if (buttons[data.answer_index]) {
                    const selectedButton = buttons[data.answer_index];
                    const color = data.correct ? '#4CAF50' : '#f44336';
                    const bgName = data.correct ? 'VERDE (correcta)' : 'ROJO (incorrecta)';
                    
                    console.log(`🎨 Aplicando color ${bgName} al botón ${data.answer_index}`);
                    
                    // Remove all existing classes
                    selectedButton.className = 'answer-btn';
                    
                    // Method 1: cssText (most aggressive)
                    selectedButton.style.cssText = `
                        background: ${color} !important;
                        background-color: ${color} !important;
                        color: white !important;
                        border: 3px solid ${color} !important;
                        opacity: 1 !important;
                        cursor: default !important;
                        font-weight: bold !important;
                    `;
                    
                    // Method 2: Direct properties
                    selectedButton.style.setProperty('background', color, 'important');
                    selectedButton.style.setProperty('background-color', color, 'important');
                    selectedButton.style.setProperty('color', 'white', 'important');
                    
                    // Method 3: Disable button
                    selectedButton.disabled = true;
                    
                    // Force reflow
                    void selectedButton.offsetHeight;
                    
                    console.log(`✅ Color aplicado. Verificación:`, {
                        backgroundColor: selectedButton.style.backgroundColor,
                        computedBg: window.getComputedStyle(selectedButton).backgroundColor
                    });
                }
            }

            if (data.correct) {
                const buttons = document.querySelectorAll('.answer-btn');
                buttons.forEach(btn => btn.disabled = true);

                const buzzContainer = document.getElementById('buzz-container');
                if (buzzContainer) {
                    const playerName = String(data.playerId) === String(playerId) ? 'Tú' : `Jugador ${data.playerId}`;
                    buzzContainer.innerHTML = `<div style="background: #4CAF50; color: white; padding: 20px; border-radius: 10px; font-size: 1.2em; text-align: center;">
                        ✅ ¡${playerName} ha acertado!<br>
                        <span style="font-size: 0.8em;">Pasando a la siguiente ronda...</span>
                    </div>`;
                    buzzContainer.style.display = 'block';
                }
                triggerNextRound();
            } else {
                const buzzContainer = document.getElementById('buzz-container');

                if (data.round_over) {
                    const buttons = document.querySelectorAll('.answer-btn');
                    buttons.forEach(btn => btn.disabled = true);

                    if (buzzContainer) {
                        buzzContainer.innerHTML = '<div style="background: #FF5722; color: white; padding: 20px; border-radius: 10px; font-size: 1.2em;">❌ Nadie acertó. Pasando ronda...</div>';
                        buzzContainer.style.display = 'block';
                    }
                    triggerNextRound();
                    return;
                }

                if (String(data.playerId) === String(playerId)) {
                    // I failed - disable all my buttons
                    const buttons = document.querySelectorAll('.answer-btn');
                    buttons.forEach(btn => btn.disabled = true);

                    if (buzzContainer) {
                        buzzContainer.innerHTML = '<p style="color: #ff5722; font-weight: bold; font-size: 1.2em;">⛔ Has fallado. Espera a la siguiente ronda.</p>';
                        buzzContainer.style.display = 'block';
                    }
                } else {
                    // Another player failed - allow rebote
                    console.log('🔄 Otro jugador falló, permitiendo rebote');
                    
                    // Show notification that player failed
                    if (buzzContainer) {
                        buzzContainer.innerHTML = `<div style="background: #ff5722; color: white; padding: 15px; border-radius: 10px; font-size: 1.1em; text-align: center; animation: pulse 0.5s;">
                            ❌ Jugador ${data.playerId} ha fallado<br>
                            <span style="font-size: 0.9em;">¡Puedes hacer BUZZ para intentarlo!</span>
                        </div>`;
                        buzzContainer.style.display = 'block';
                        
                        // Hide notification after 3 seconds
                        setTimeout(() => {
                            if (buzzContainer) {
                                buzzContainer.innerHTML = '';
                            }
                        }, 3000);
                    }
                    
                    // Disable answer buttons (they'll be enabled when someone buzzes)
                    const buttons = document.querySelectorAll('.answer-btn');
                    buttons.forEach(btn => {
                        btn.disabled = true;
                        btn.style.opacity = '0.5';
                    });

                    // Re-enable buzz button for everyone
                    const buzzBtn = document.getElementById('buzz-container');
                    if (buzzBtn) {
                        buzzBtn.style.display = 'block';
                    }

                    // Clear buzz order
                    const list = document.getElementById('buzz-order');
                    if (list) list.innerHTML = '';
                }
            }
        }

        const currentRoundNum = <?php echo $gameState['current_round']; ?>;

        function updateRoundContent(data) {
            // Prevent infinite reload loop
            // Server sends 'current_round', not 'round_number'
            if (data.current_round && parseInt(data.current_round) === parseInt(currentRoundNum)) {
                console.log('🔄 updateRoundContent recibido para ronda actual (' + data.current_round + '). Ignorando.');
                return;
            }
            console.log('🔄 Ronda nueva detectada (' + (data.current_round || 'unknown') + '), recargando...');
            window.location.reload();
        }

        // Add handler for 'round_update' which was missing in switch but present in logs
        // Actually, I need to update the switch case in connectGameWebSocket as well
        // But since this tool only replaces a chunk, I'll add the function here first.
        function handleRoundUpdate(data) {
            if (data.event && data.event.success === false) {
                console.warn('Action failed:', data.event.reason);
                if (data.event.reason === 'not_your_turn') {
                    alert('⚠️ ¡No es tu turno! Tienes que hacer Buzz primero.');
                } else if (data.event.reason === 'locked') {
                    alert('⚠️ El sistema está bloqueado por otro jugador.');
                } else {
                    alert('⚠️ Error: ' + data.event.reason);
                }
            }
        }

        let timerInterval = null;
        let currentRemainingTime = 40;
        const roundType = '<?php echo $gameState['round_type'] ?? ''; ?>';

        function updateTimer(remainingTime, isPaused) {
            currentRemainingTime = remainingTime;
            const timerEl = document.getElementById('round-timer');
            const timerValue = document.getElementById('timer-value');

            if (timerValue) {
                timerValue.textContent = Math.ceil(remainingTime);

                // Color coding based on time remaining
                const timerContainer = timerValue.parentElement;
                if (remainingTime <= 10) {
                    timerContainer.style.background = '#f44336'; // Red
                } else if (remainingTime <= 20) {
                    timerContainer.style.background = '#ff9800'; // Orange
                } else {
                    timerContainer.style.background = '#2196F3'; // Blue
                }

                // Show paused indicator
                if (isPaused) {
                    timerValue.textContent += ' ⏸️';
                }
            }

            // Check for timeout
            if (remainingTime <= 0 && !isPaused) {
                handleTimeout();
            }
        }

        function handleTimeout() {
            const buzzContainer = document.getElementById('buzz-container');
            if (buzzContainer) {
                buzzContainer.innerHTML = '<div style="background: #FF5722; color: white; padding: 20px; border-radius: 10px; font-size: 1.2em;">⏰ ¡Tiempo agotado! Pasando ronda...</div>';
                buzzContainer.style.display = 'block';
            }

            // Host triggers next round
            setTimeout(() => {
                if (<?php echo $roomManager->isCreator($roomCode, $playerId) ? 'true' : 'false'; ?>) {
                    if (ws && ws.readyState === WebSocket.OPEN) {
                        ws.send(JSON.stringify({
                            action: 'next_round',
                            room_code: roomCode
                        }));
                    }
                }
            }, 2000);
        }

        function startTimerSync() {
            // Show timer only for Buzz rounds
            const timerEl = document.getElementById('round-timer');
            if (roundType === 'buzz_rapido' && timerEl) {
                timerEl.style.display = 'block';

                // Initial sync
                if (ws && ws.readyState === WebSocket.OPEN) {
                    ws.send(JSON.stringify({
                        action: 'get_timer',
                        room_code: roomCode
                    }));
                }

                // Periodic sync every 2 seconds
                if (timerInterval) clearInterval(timerInterval);
                timerInterval = setInterval(() => {
                    if (ws && ws.readyState === WebSocket.OPEN) {
                        ws.send(JSON.stringify({
                            action: 'get_timer',
                            room_code: roomCode
                        }));
                    }
                }, 2000);
            } else if (timerEl) {
                timerEl.style.display = 'none';
            }
        }

        // Start timer sync when page loads
        document.addEventListener('DOMContentLoaded', function () {
            startTimerSync();
        });

        function handlePassBomb() {
            if (ws && ws.readyState === WebSocket.OPEN) {
                ws.send(JSON.stringify({
                    action: 'pass_bomb',
                    room_code: roomCode,
                    player_id: playerId
                }));
            }
        }
    </script>
</body>

</html>