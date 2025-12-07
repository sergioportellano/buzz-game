<?php
namespace App\Core\Rounds;

class BuzzRapidoRound extends BaseRound
{
    private $buzzedPlayer = null;
    private $canBuzz = true;
    private $attempts = [];

    // Timer properties
    private $roundStartTime = null;
    private $pausedTime = null;
    private $totalPausedDuration = 0;
    private $roundDuration = 40; // 40 seconds
    private $timerPaused = false;

    public function start(array $config)
    {
        parent::start($config);
        $this->canBuzz = true;
        $this->buzzedPlayer = null;
        $this->attempts = [];

        // Initialize timer
        $this->roundStartTime = microtime(true);
        $this->pausedTime = null;
        $this->totalPausedDuration = 0;
        $this->timerPaused = false;

        if (isset($config['question'])) {
            $this->currentQuestion = $config['question'];
        } else {
            // Mock fallback
            $this->currentQuestion = [
                'text' => '¿Cual es esta cancion? (MOCK)',
                'options' => ['A', 'B', 'C', 'D'],
                'correct' => 'A',
                'archivo_audio' => '1.mp3'
            ];
        }
    }

    public function handleAction(string $action, int $playerId, array $data): ?array
    {
        // Check for timeout first
        if ($this->isTimedOut()) {
            $this->state = 'finished';
            return ['success' => true, 'timeout' => true, 'round_over' => true];
        }

        if ($action === 'buzz') {
            // Check if player already failed this round
            if (in_array($playerId, $this->attempts)) {
                return ['success' => false, 'reason' => 'already_attempted'];
            }

            if ($this->canBuzz && $this->buzzedPlayer === null) {
                $this->buzzedPlayer = $playerId;
                $this->canBuzz = false;
                $this->state = 'buzzed';

                // Pause timer when someone buzzes
                $this->pauseTimer();

                return ['success' => true, 'event' => 'player_buzzed', 'player_id' => $playerId];
            }
            return ['success' => false, 'reason' => 'locked'];
        }

        if ($action === 'answer') {
            if ($playerId !== $this->buzzedPlayer) {
                file_put_contents(__DIR__ . '/../../../../debug_buzz.log', date('Y-m-d H:i:s') . " Mismatch: Player($playerId) vs Buzzed(" . var_export($this->buzzedPlayer, true) . ")\n", FILE_APPEND);
                return ['success' => false, 'reason' => 'not_your_turn'];
            }

            $answer = $data['answer'];
            $correct = ($answer === $this->currentQuestion['correct']);

            if ($correct) {
                $this->scores[$playerId] = ($this->scores[$playerId] ?? 0) + 10;
                $this->state = 'finished';
                return ['success' => true, 'correct' => true];
            } else {
                $this->scores[$playerId] = ($this->scores[$playerId] ?? 0) - 5;
                $this->buzzedPlayer = null;
                $this->canBuzz = true;
                $this->state = 'active';

                // Resume timer after incorrect answer
                $this->resumeTimer();

                // Track failed attempt
                if (!in_array($playerId, $this->attempts)) {
                    $this->attempts[] = $playerId;
                }

                // Check if ALL players have failed
                if (count($this->attempts) >= count($this->players)) {
                    $this->state = 'finished';
                    return ['success' => true, 'correct' => false, 'round_over' => true];
                }

                return ['success' => true, 'correct' => false];
            }
        }

        return null;
    }

    private function pauseTimer()
    {
        if (!$this->timerPaused && $this->roundStartTime !== null) {
            $this->pausedTime = microtime(true);
            $this->timerPaused = true;
        }
    }

    private function resumeTimer()
    {
        if ($this->timerPaused && $this->pausedTime !== null) {
            $this->totalPausedDuration += (microtime(true) - $this->pausedTime);
            $this->pausedTime = null;
            $this->timerPaused = false;
        }
    }

    public function getRemainingTime(): float
    {
        if ($this->roundStartTime === null) {
            return $this->roundDuration;
        }

        $elapsed = microtime(true) - $this->roundStartTime - $this->totalPausedDuration;

        // If currently paused, don't count the current pause period
        if ($this->timerPaused && $this->pausedTime !== null) {
            $elapsed -= (microtime(true) - $this->pausedTime);
        }

        $remaining = $this->roundDuration - $elapsed;
        return max(0, $remaining);
    }

    private function isTimedOut(): bool
    {
        return $this->getRemainingTime() <= 0 && $this->state !== 'finished';
    }

    public function getState(): array
    {
        $state = parent::getState();
        $state['buzzed_player'] = $this->buzzedPlayer;
        $state['question'] = $this->currentQuestion;
        $state['remaining_time'] = round($this->getRemainingTime(), 1);
        $state['timer_paused'] = $this->timerPaused;
        return $state;
    }
}
