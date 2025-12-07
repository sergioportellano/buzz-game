<?php
require_once __DIR__ . '/Rounds/RoundFactory.php';

class GameManager
{
    private $roomManager;
    private $db;
    private $currentRound;
    private $roomCode;
    private $roundSequence;
    private $currentRoundIndex;
    private $questionManager;
    private $cumulativeScores = []; // Track scores across all rounds

    public function __construct($roomManager, $db)
    {
        $this->roomManager = $roomManager;
        $this->db = $db;

        // Initialize QuestionManager
        require_once __DIR__ . '/QuestionManager.php';
        $this->questionManager = new QuestionManager($db);
    }

    public function initializeGame($roomCode)
    {
        $this->roomCode = $roomCode;
        $room = $this->roomManager->getRoom($roomCode);

        // Define tournament sequence: 5 Buzz, 5 Todos, 5 Bomba
        $this->roundSequence = [];

        // 5 Rounds of Buzz Rapido
        for ($i = 0; $i < 5; $i++)
            $this->roundSequence[] = 'buzz_rapido';

        // 5 Rounds of Todos Responden
        for ($i = 0; $i < 5; $i++)
            $this->roundSequence[] = 'todos_responden';

        // 5 Rounds of Bomba Musical
        for ($i = 0; $i < 5; $i++)
            $this->roundSequence[] = 'bomba_musical';

        $this->currentRoundIndex = -1;

        // Iniciar primera ronda
        return $this->startNextRound();
    }

    public function startNextRound()
    {
        // Save scores from previous round before advancing
        if ($this->currentRound !== null) {
            $previousState = $this->currentRound->getState();
            if (isset($previousState['scores'])) {
                // Merge scores from previous round into cumulative scores
                foreach ($previousState['scores'] as $playerId => $score) {
                    if (!isset($this->cumulativeScores[$playerId])) {
                        $this->cumulativeScores[$playerId] = 0;
                    }
                    $this->cumulativeScores[$playerId] = $score; // Use latest score (already cumulative)

                    // Persist to database
                    $this->roomManager->updatePlayerScore($this->roomCode, $playerId, $score);
                }
            }
        }

        $this->currentRoundIndex++;

        if ($this->currentRoundIndex >= count($this->roundSequence)) {
            $this->endGame();
            return null;
        }

        $roundType = $this->roundSequence[$this->currentRoundIndex];
        $room = $this->roomManager->getRoom($this->roomCode);

        // Configuración específica por tipo de ronda
        $config = $this->getRoundConfig($roundType);

        // Determine offset based on how many rounds of this type we have already played
        $offset = 0;
        for ($i = 0; $i < $this->currentRoundIndex; $i++) {
            if ($this->roundSequence[$i] === $roundType) {
                $offset++;
            }
        }

        $typeIdMap = ['buzz_rapido' => 1, 'todos_responden' => 2, 'bomba_musical' => 3];
        $typeId = $typeIdMap[$roundType] ?? 1;

        // Fetch deterministic question using offset
        $config['question'] = null;
        $questions = $this->questionManager->getQuestionsForRound($typeId, 1, $offset);

        if (!empty($questions)) {
            $config['question'] = $questions[0];
            $config['questions'] = $questions;
        }

        // Pass cumulative scores to new round
        $config['initial_scores'] = $this->cumulativeScores;

        // Crear ronda
        $this->currentRound = RoundFactory::createRound(
            $roundType,
            $config,
            $this->roomCode,
            $room['players'],
            $this->db
        );

        // Inicializar ronda
        $this->currentRound->start($config);
        $roundData = $this->currentRound->getState();

        // Actualizar estado en base de datos
        $this->roomManager->updateCurrentRound($this->roomCode, $this->currentRoundIndex + 1);

        return $roundData;
    }

    public function getCurrentRound()
    {
        return $this->currentRound;
    }

    public function handlePlayerAction($playerId, $action, $data)
    {
        if ($this->currentRound) {
            return $this->currentRound->handleAction($action, $playerId, $data);
        }
        return null;
    }

    public function endRound()
    {
        if ($this->currentRound) {
            return $this->currentRound->getState();
        }
        return null;
    }

    private function getRoundConfig($roundType)
    {
        $configs = [
            'buzz_rapido' => [
                'duracion_respuesta' => 10,
                'puntos_correcto' => 100,
                'puntos_incorrecto' => -50,
                'mostrar_pregunta' => true
            ],
            'todos_responden' => [
                'duracion_respuesta' => 15,
                'puntos_correcto' => 50,
                'puntos_incorrecto' => 0,
                'mostrar_opciones' => true
            ],
            'bomba_musical' => [
                'tiempo_min_explosion' => 10,
                'tiempo_max_explosion' => 30,
                'puntos_perdedor' => -100,
                'max_jugadores' => 8
            ]
        ];

        return $configs[$roundType] ?? [];
    }

    private function endGame()
    {
        $this->roomManager->updateRoomState($this->roomCode, 'finalizada');
    }

    public function restoreGameState($roomCode)
    {
        $this->roomCode = $roomCode;
        $room = $this->roomManager->getRoom($roomCode);

        // Re-construct the sequence logic
        $this->roundSequence = [];
        for ($i = 0; $i < 5; $i++)
            $this->roundSequence[] = 'buzz_rapido';
        for ($i = 0; $i < 5; $i++)
            $this->roundSequence[] = 'todos_responden';
        for ($i = 0; $i < 5; $i++)
            $this->roundSequence[] = 'bomba_musical';

        $dbIndex = (int) ($room['current_round'] ?? 1);
        $this->currentRoundIndex = $dbIndex - 1;

        if ($this->currentRoundIndex < 0)
            $this->currentRoundIndex = 0;
        if ($this->currentRoundIndex >= count($this->roundSequence)) {
            return;
        }

        $roundType = $this->roundSequence[$this->currentRoundIndex];

        // Calculate offset
        $offset = 0;
        for ($i = 0; $i < $this->currentRoundIndex; $i++) {
            if ($this->roundSequence[$i] === $roundType) {
                $offset++;
            }
        }

        // Fetch Question
        $config = $this->getRoundConfig($roundType);
        $typeIdMap = ['buzz_rapido' => 1, 'todos_responden' => 2, 'bomba_musical' => 3];
        $typeId = $typeIdMap[$roundType] ?? 1;

        $questions = $this->questionManager->getQuestionsForRound($typeId, 1, $offset);
        if (!empty($questions)) {
            $config['question'] = $questions[0];
            $config['questions'] = $questions;
        }

        // Restore cumulative scores from room player data
        $this->cumulativeScores = [];
        foreach ($room['players'] as $player) {
            $this->cumulativeScores[$player['id']] = $player['score'] ?? 0;
        }
        $config['initial_scores'] = $this->cumulativeScores;

        // Re-create Round
        $this->currentRound = RoundFactory::createRound(
            $roundType,
            $config,
            $this->roomCode,
            $room['players'],
            $this->db
        );

        $this->currentRound->start($config);
    }

    public function getGameState()
    {
        return [
            'current_round' => $this->currentRoundIndex + 1,
            'total_rounds' => count($this->roundSequence),
            'round_type' => $this->roundSequence[$this->currentRoundIndex] ?? null,
            'round_data' => $this->currentRound ? $this->currentRound->getState() : null
        ];
    }
}
?>