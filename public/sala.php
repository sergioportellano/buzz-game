<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../src/Core/Database.php';
require_once '../src/Core/AuthManager.php';
require_once '../src/Core/RoomManager.php';
require_once '../src/Core/ConfigManager.php';

// Cargar configuraci贸n y crear instancias
$config = require '../config/game-settings.php';
$db = new Database();
$auth = new AuthManager($db);
$roomManager = new RoomManager($db);

// Verificar login
if (!$auth->estaLogueado()) {
    header("Location: login.php");
    exit();
}

// Check if room code is provided
if (isset($_GET['code'])) {
    $roomCode = $_GET['code'];
    $room = $roomManager->getRoom($roomCode);

    if ($room) {
        // Verificar la estructura de datos
        $players = isset($room['players']) ? $room['players'] : [];
        
        error_log("Sala encontrada: $roomCode - Jugadores: " . count($players));
    } else {
        error_log("Sala NO encontrada: $roomCode");
        header('Location: crear-sala.php?error=room_not_found');
        exit();
    }
} else {
    header('Location: crear-sala.php');
    exit();
}

// Verificar si el usuario actual es el creador - USANDO USUARIO REAL
$isCreator = $roomManager->isCreator($roomCode, $auth->obtenerUsuarioActual());
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sala: <?php echo htmlspecialchars($room['name']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .room-code {
            background: #007bff;
            color: white;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            margin: 20px 0;
        }
        .players-list {
            list-style: none;
            padding: 0;
        }
        .players-list li {
            padding: 10px;
            margin: 5px 0;
            background: #f8f9fa;
            border-radius: 5px;
            border-left: 4px solid #007bff;
        }
        .start-btn {
            background: #28a745;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            cursor: pointer;
            margin: 20px 0;
            transition: background 0.3s;
        }
        .start-btn:hover {
            background: #218838;
        }
        .start-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        .user-info {
            background: #e9f7ef;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .creator-badge {
            background: #ffc107;
            color: #856404;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            margin-left: 10px;
        }
		
				/* Agregar a los estilos existentes en sala.php */
		.players-list {
			transition: all 0.3s ease;
		}

		.player-joined {
			animation: highlight 2s ease;
		}

		@keyframes highlight {
			0% { background-color: #d4edda; }
			100% { background-color: #f8f9fa; }
		}
    </style>
</head>
<body>
    <div class="container">
        <!-- Informaci贸n del usuario -->
        <div class="user-info">
            馃憢 Hola, <strong><?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario'); ?></strong>
            <?php if ($isCreator): ?>
                <span class="creator-badge">Anfitri贸n</span>
            <?php endif; ?>
            | <a href="crear-sala.php">Crear Sala</a> | 
            <a href="logout.php">Cerrar sesi贸n</a>
        </div>
        
        <h1><?php echo htmlspecialchars($room['name']); ?></h1>
        
        <div class="room-code">
            馃幃 C贸digo: <?php echo htmlspecialchars($roomCode); ?>
        </div>
        
        <h2>Jugadores (<?php echo count($players); ?>/<?php echo $room['max_players']; ?>):</h2>
        <ul class="players-list">
            <?php if (empty($players)): ?>
                <li>No hay jugadores en la sala</li>
            <?php else: ?>
                <?php foreach ($players as $player): ?>
                    <li>
                        馃幆 <?php echo htmlspecialchars($player['name']); ?> - Puntos: <?php echo $player['score']; ?>
                        <?php if ($player['id'] == $auth->obtenerUsuarioActual()): ?>
                            <strong>(T煤)</strong>
                        <?php endif; ?>
                        <?php if ($player['id'] == $room['creator_id']): ?>
                            <span class="creator-badge">Anfitri贸n</span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>

        <h2>Configuraci贸n:</h2>
        <p><strong>Estado:</strong> <?php echo $room['state']; ?></p>
        <p><strong>Ronda actual:</strong> <?php echo $room['current_round']; ?></p>

        <?php if ($isCreator): ?>
            <button id="start-game" class="start-btn" onclick="window.location.href='juego.php?code=<?php echo $roomCode; ?>'">
                馃殌 Iniciar Juego
            </button>
            <?php if (count($players) < 2): ?>
                <p>鈴?Esperando al menos 2 jugadores para iniciar...</p>
            <?php endif; ?>
        <?php else: ?>
            <p>鈴?Esperando a que el anfitri贸n inicie el juego...</p>
        <?php endif; ?>
        
        <div style="margin-top: 20px;">
            <p><strong>馃摙 Comparte este c贸digo para que otros se unan:</strong> <?php echo $roomCode; ?></p>
			<a href="salas-disponibles.php">Ver Salas Disponibles</a>
        </div>
    </div>

    <!-- Reemplazar el script completo en sala.php -->
<script>
// Sistema de comunicación en tiempo real
let ws = null;
let pollingInterval = null;
let reconnectTimeout = null;
const roomCode = '<?php echo $roomCode; ?>';
const playerId = '<?php echo $auth->obtenerUsuarioActual(); ?>';
const playerName = '<?php echo $_SESSION['usuario_nombre'] ?? 'Usuario'; ?>';
let isPageClosing = false;
let gameStarting = false;

// Intentar WebSocket primero, luego fallback a polling
function initializeRealtime() {
    attemptWebSocket();
    startPolling(); // Siempre iniciar polling como respaldo
}

function attemptWebSocket() {
    try {
        if (ws) {
            ws.close(1000, 'Reconnecting');
        }

        const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        const wsUrl = `${protocol}//${window.location.hostname}:8080`;
        
        console.log('?? Intentando WebSocket...');
        ws = new WebSocket(wsUrl);
        
        ws.onopen = function(event) {
            console.log('? WebSocket conectado');
            clearTimeout(reconnectTimeout);
            
            ws.send(JSON.stringify({
                action: 'join_room',
                room_code: roomCode,
                player_id: playerId,
                player_name: playerName
            }));
        };
        
        ws.onmessage = function(event) {
            try {
                const data = JSON.parse(event.data);
                console.log('?? WebSocket:', data);
                handleRealtimeMessage(data);
            } catch (e) {
                console.error('Error procesando mensaje WebSocket:', e);
            }
        };
        
        ws.onclose = function(event) {
            console.log('?? WebSocket cerrado:', event.code, event.reason);
            if (!isPageClosing && !gameStarting) {
                console.log('?? Reintentando WebSocket en 5 segundos...');
                reconnectTimeout = setTimeout(attemptWebSocket, 5000);
            }
        };
        
        ws.onerror = function(error) {
            console.error('? Error WebSocket:', error);
        };
        
    } catch (error) {
        console.error('Error inicializando WebSocket:', error);
    }
}

// Polling como fallback robusto
function startPolling() {
    // Actualizar lista inmediatamente
    updateRoomState();
    
    // Polling cada 3 segundos
    pollingInterval = setInterval(updateRoomState, 3000);
}

function updateRoomState() {
    fetch(`get-room-info.php?code=${roomCode}&t=${Date.now()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateUI(data.room);
                
                // Si el juego empezó y no nos hemos redirigido
                if (data.room.state === 'jugando' && !gameStarting) {
                    handleGameStart(`juego.php?code=${roomCode}`);
                }
            }
        })
        .catch(error => console.error('Error en polling:', error));
}

function updateUI(room) {
    // Actualizar lista de jugadores
    updatePlayersList(room.players);
    
    // Actualizar estado de la sala
    updateRoomStatus(room.state, room.current_round);
    
    // Actualizar botón de inicio si es anfitrión
    updateStartButton(room);
}

function updatePlayersList(players) {
    const playersList = document.querySelector('.players-list');
    if (!playersList) return;
    
    playersList.innerHTML = '';
    
    if (players.length === 0) {
        playersList.innerHTML = '<li>No hay jugadores en la sala</li>';
        return;
    }
    
    players.forEach(player => {
        const li = document.createElement('li');
        let playerHtml = `?? ${escapeHtml(player.name)} - Puntos: ${player.score}`;
        
        if (player.id == playerId) {
            playerHtml += ' <strong>(Tú)</strong>';
        }
        if (player.id == '<?php echo $room['creator_id']; ?>') {
            playerHtml += ' <span class="creator-badge">Anfitrión</span>';
        }
        
        li.innerHTML = playerHtml;
        playersList.appendChild(li);
    });
}

function updateRoomStatus(state, round) {
    // Actualizar elementos de estado si existen
    const stateElement = document.querySelector('.room-state');
    const roundElement = document.querySelector('.room-round');
    
    if (stateElement) stateElement.textContent = state;
    if (roundElement) roundElement.textContent = round;
}

function updateStartButton(room) {
    const startBtn = document.getElementById('start-game');
    if (!startBtn) return;
    
    const minPlayers = 2;
    const hasEnoughPlayers = room.players.length >= minPlayers;
    const isWaiting = room.state === 'waiting';
    
    if (hasEnoughPlayers && isWaiting) {
        startBtn.disabled = false;
        startBtn.innerHTML = '?? Iniciar Juego';
    } else {
        startBtn.disabled = true;
        if (room.players.length < minPlayers) {
            startBtn.innerHTML = `? Esperando jugadores (${room.players.length}/${minPlayers})`;
        } else {
            startBtn.innerHTML = '?? Iniciar Juego';
        }
    }
}

function handleRealtimeMessage(data) {
    switch(data.action) {
        case 'game_starting':
            console.log('?? Juego iniciando via WebSocket...');
            handleGameStart(data.redirect_url);
            break;
            
        case 'player_joined':
            showNotification(`?? ${data.player_name} se unió a la sala`);
            updateRoomState(); // Actualizar via polling para consistencia
            break;
            
        case 'player_left':
            showNotification(`?? Un jugador abandonó la sala`);
            updateRoomState();
            break;
    }
}

function handleGameStart(redirectUrl) {
    if (gameStarting) return; // Prevenir múltiples ejecuciones
    
    gameStarting = true;
    isPageClosing = true;
    
    // Detener todas las conexiones y intervalos
    clearInterval(pollingInterval);
    clearTimeout(reconnectTimeout);
    
    if (ws) {
        ws.onclose = null;
        ws.close(1000, 'Game starting');
    }
    
    // Mostrar cuenta regresiva
    showCountdown(3, redirectUrl);
}

function showCountdown(seconds, redirectUrl) {
    let countdown = seconds;
    
    // Crear overlay de cuenta regresiva
    const overlay = document.createElement('div');
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.9);
        color: white;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        z-index: 10000;
        font-family: Arial, sans-serif;
    `;
    
    overlay.innerHTML = `
        <div style="text-align: center;">
            <div style="font-size: 3em; margin-bottom: 20px;">??</div>
            <div style="font-size: 2em; margin-bottom: 10px;">?El juego está empezando!</div>
            <div style="font-size: 4em; font-weight: bold; color: #4CAF50;" id="countdown-number">${countdown}</div>
            <div style="font-size: 1.2em; margin-top: 20px;">Redirigiendo a todos los jugadores...</div>
        </div>
    `;
    
    document.body.appendChild(overlay);
    
    const countdownInterval = setInterval(() => {
        countdown--;
        const numberElement = document.getElementById('countdown-number');
        if (numberElement) {
            numberElement.textContent = countdown;
            
            // Efecto visual
            numberElement.style.transform = 'scale(1.2)';
            setTimeout(() => {
                numberElement.style.transform = 'scale(1)';
            }, 200);
        }
        
        if (countdown <= 0) {
            clearInterval(countdownInterval);
            window.location.href = redirectUrl;
        }
    }, 1000);
}

function showNotification(message) {
    // Notificación toast simple
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #333;
        color: white;
        padding: 15px 20px;
        border-radius: 5px;
        z-index: 1000;
        font-size: 14px;
    `;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        document.body.removeChild(toast);
    }, 3000);
}

function escapeHtml(unsafe) {
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// Inicializar cuando la página carga
document.addEventListener('DOMContentLoaded', function() {
    initializeRealtime();
});

// Manejar inicio del juego (para anfitrión)
document.getElementById('start-game')?.addEventListener('click', function(e) {
    e.preventDefault();
    
    if (confirm('?Estás seguro de que quieres iniciar el juego? Todos los jugadores serán redirigidos.')) {
        startGame();
    }
});

function startGame() {
    const startBtn = document.getElementById('start-game');
    startBtn.disabled = true;
    startBtn.innerHTML = '?? Iniciando...';
    
    // Intentar via WebSocket primero
    if (ws && ws.readyState === WebSocket.OPEN) {
        ws.send(JSON.stringify({
            action: 'start_game',
            room_code: roomCode
        }));
        
        console.log('?? Comando de inicio enviado via WebSocket');
        
        // Timeout de seguridad - si no hay respuesta en 3 segundos, usar polling
        setTimeout(() => {
            if (!gameStarting) {
                console.log('? Timeout WebSocket, usando método alternativo...');
                startGameViaAPI();
            }
        }, 3000);
        
    } else {
        // WebSocket no disponible, usar API directamente
        startGameViaAPI();
    }
}

function startGameViaAPI() {
    // Llamar a un endpoint PHP que inicie el juego
    fetch('start-game.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `room_code=${roomCode}&player_id=${playerId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('? Juego iniciado via API');
            // El polling detectará el cambio de estado y redirigirá
        } else {
            alert('Error al iniciar el juego: ' + data.error);
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error iniciando juego:', error);
        alert('Error de conexión. Intentando redirigir...');
        window.location.href = 'juego.php?code=' + roomCode;
    });
}

// Manejar cierre de página
window.addEventListener('beforeunload', function() {
    isPageClosing = true;
    gameStarting = true;
    clearInterval(pollingInterval);
    clearTimeout(reconnectTimeout);
    if (ws) {
        ws.onclose = null;
        ws.close(1000, 'Page closing');
    }
});
</script>
	
	
</body>
</html>