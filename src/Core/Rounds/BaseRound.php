<?php
namespace App\Core\Rounds;

abstract class BaseRound implements RoundInterface
{
    protected $state = 'waiting'; // waiting, active, finished
    protected $currentQuestion = null;
    protected $scores = [];
    protected $startTime = null;
    protected $config = [];
    protected $roomCode;
    protected $players = [];

    public function __construct(string $roomCode, array $players = [])
    {
        $this->roomCode = $roomCode;
        $this->players = $players;
    }

    public function start(array $config)
    {
        $this->config = $config;
        $this->state = 'active';
        $this->startTime = time();

        // Initialize scores from previous rounds if provided
        if (isset($config['initial_scores']) && is_array($config['initial_scores'])) {
            $this->scores = $config['initial_scores'];
        }
    }

    public function getState(): array
    {
        return [
            'state' => $this->state,
            'question' => $this->currentQuestion,
            'scores' => $this->scores,
            'time_elapsed' => time() - $this->startTime
        ];
    }

    public function checkConditions(): array
    {
        return [];
    }

    // Alias for frontend compatibility (juego.php calls getRoundData)
    public function getRoundData(): array
    {
        return $this->getState();
    }
}
