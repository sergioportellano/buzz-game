<?php
namespace App\Core\Rounds;

class BombaMusicalRound extends BaseRound
{
    private $bombHolderId = null;
    private $detonationTime = null;

    public function start(array $config)
    {
        parent::start($config);
        // Randomly assign bomb to a player from the config list
        $players = $config['players'] ?? [];
        if (!empty($players)) {
            $this->bombHolderId = $players[array_rand($players)];
        }
        $this->detonationTime = time() + ($config['duration'] ?? 60); // 60s default

        if (isset($config['question'])) {
            $this->currentQuestion = $config['question'];
        } else {
            // Mock question
            $this->currentQuestion = ['text' => 'Pregunta Bomba (MOCK)', 'correct' => 'A'];
        }
    }

    public function handleAction(string $action, int $playerId, array $data): ?array
    {
        if ($action === 'answer') {
            if ($playerId !== $this->bombHolderId) {
                return ['success' => false, 'reason' => 'not_bomb_holder'];
            }

            if (time() > $this->detonationTime) {
                return ['success' => false, 'reason' => 'boom'];
            }

            $answer = $data['answer'];
            $correct = ($answer === $this->currentQuestion['correct']);

            if ($correct) {
                // Pass bomb to next player
                $this->passBomb($playerId);
                return ['success' => true, 'event' => 'bomb_passed', 'new_holder' => $this->bombHolderId];
            } else {
                return ['success' => true, 'event' => 'wrong_answer_keep_bomb'];
            }
        }
        return null;
    }

    private function passBomb($currentHolderId)
    {
        $players = $this->config['players'] ?? [];
        // Simple logic: pass to next ID in list (circular)
        $currentIndex = array_search($currentHolderId, $players);
        $nextIndex = ($currentIndex + 1) % count($players);
        $this->bombHolderId = $players[$nextIndex];
    }

    public function checkConditions(): array
    {
        if (time() > $this->detonationTime) {
            $this->state = 'finished';
            return ['event' => 'boom', 'eliminated_player' => $this->bombHolderId];
        }
        return [];
    }

    public function getState(): array
    {
        $state = parent::getState();
        $state['bomb_holder'] = $this->bombHolderId;
        $state['time_left'] = max(0, $this->detonationTime - time());
        return $state;
    }
}
