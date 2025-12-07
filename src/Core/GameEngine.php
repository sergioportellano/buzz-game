<?php
require_once __DIR__ . '/QuestionManager.php';

class GameEngine
{
    private $roomManager;
    private $configManager;
    private $currentRound;
    private $questionManager;

    public function __construct($roomManager, $configManager)
    {
        $this->roomManager = $roomManager;
        $this->configManager = $configManager;

        // Inicializar QuestionManager (Hack: New connection)
        $db = new \Database();
        $this->questionManager = new \QuestionManager($db);

        // Cargar clases de rondas (Manual require ya que no hay autoloader configurado para esto aun)
        require_once __DIR__ . '/Rounds/RoundInterface.php';
        require_once __DIR__ . '/Rounds/BaseRound.php';
        require_once __DIR__ . '/Rounds/BuzzRapidoRound.php';
        require_once __DIR__ . '/Rounds/TodosRespondenRound.php';
        require_once __DIR__ . '/Rounds/BombaMusicalRound.php';
    }

    public function startGame($roomCode)
    {
        $room = $this->roomManager->getRoom($roomCode);

        // DEBUG
        error_log("=== START GAME DEBUG ===");
        error_log("Room Code: " . $roomCode);

        if ($room && $this->isReadyToStart($room)) {
            // Actualizar estado en la base de datos
            $this->roomManager->updateRoomState($roomCode, 'jugando');
            $this->roomManager->startGame($roomCode);

            error_log("Juego iniciado exitosamente para sala: " . $roomCode);

            // Inicializar rondas
            $this->initializeRounds($room);
        } else {
            error_log("No se pudo iniciar el juego - Sala no lista");
        }
    }

    public function stopGame($roomCode)
    {
        $room = $this->roomManager->getRoom($roomCode);
        if ($room) {
            $this->roomManager->updateRoomState($roomCode, 'finalizada');
            error_log("Juego finalizado para sala: " . $roomCode);
        }
    }

    private function isReadyToStart($room)
    {
        // Verificar condiciones para iniciar el juego
        $minPlayers = 2; // Mínimo de jugadores requeridos
        $currentPlayers = count($room['players'] ?? []);
        $isWaiting = ($room['state'] ?? '') === 'waiting';

        $ready = ($currentPlayers >= $minPlayers && $isWaiting);
        return $ready;
    }

    private function initializeRounds($room)
    {
        error_log("Inicializando rondas para sala: " . $room['code']);

        // 1. Instanciar QuestionManager si no existe (o inyectarlo, por ahora lo creo aquí on-the-fly o lo añado al constructor)
        // Mejor añadirlo al constructor, pero para no romper firmas ahora, lo instanciamos con la db existente
        // Reflection: $this->roomManager->db? No tengo acceso.
        // Asumamos que GameEngine recibe QuestionManager o lo crea new QuestionManager(new Database()).
        // "Quick fix": crear nueva db connection localmente.
        // $db = new \Database(); // Asumiendo que está disponible por require previo en index.php, sino require_once.
        // En index.php ya se hace require Database.php.
        // $qm = new \QuestionManager($db); // Replaced by injected $this->questionManager

        // 2. Determinar tipo de ronda (Hardcoded BuzzRapido = ID 1 en mi seed? O buscar por código)
        // Asumamos ID 1 = Buzz Rapido.
        $roundTypeId = 1;

        $questions = $this->questionManager->getQuestionsForRound($roundTypeId, 1);

        if (empty($questions)) {
            error_log("ADVERTENCIA: No hay preguntas en BBDD para el tipo de ronda $roundTypeId. Usando mock.");
            // El mock está dentro de BuzzRapidoRound si no le paso nada? No, BuzzRapido ahora espera config?
            // Revisemos BuzzRapidoRound.php. Tiene un mock en start().
            // Pasaremos preguntas vacías y dejar que el mock actúe o, mejor, pasar lo que tengamos.
        }

        $config = [];
        if (!empty($questions)) {
            $config['questions'] = $questions;
            // BuzzRapidoRound espera 'question' (singular) o 'questions' (plural)?
            // Adaptemos BuzzRapidoRound para usar la config.
            // Por ahora paso 'question' = la primera.
            $config['question'] = $questions[0];
        }

        $this->currentRound = new \App\Core\Rounds\BuzzRapidoRound($room['code']);
        $this->currentRound->start($config);

        error_log("Ronda BuzzRapido iniciada con " . (empty($questions) ? "MOCK" : "DB") . " pregunta.");
    }

    public function handlePlayerAction($roomCode, $playerId, $action, $data)
    {
        $room = $this->roomManager->getRoom($roomCode);

        error_log("=== PLAYER ACTION ===");
        error_log("Room: $roomCode, Player: $playerId, Action: $action");

        if ($this->currentRound) {
            $result = $this->currentRound->handleAction($action, $playerId, $data);
            if ($result) {
                error_log("Resultado de acción de ronda: " . print_r($result, true));
                // Aquí podrías notificar a los clientes vía WebSocket o guardar en DB
            }
        } else {
            error_log("No hay ronda activa para manejar la acción.");
        }
    }

    public function getGameState($roomCode)
    {
        $room = $this->roomManager->getRoom($roomCode);
        return $room ? $room['state'] : null;
    }

    public function nextRound($roomCode)
    {
        // Lógica para avanzar a la siguiente ronda
        $room = $this->roomManager->getRoom($roomCode);
        if ($room) {
            $currentRound = $room['current_round'] ?? 1;
            $newRound = $currentRound + 1;

            // Actualizar ronda en la base de datos
            $this->roomManager->updateCurrentRound($roomCode, $newRound);
            error_log("Avanzando a ronda $newRound en sala $roomCode");
        }
    }
}
?>