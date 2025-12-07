<?php
namespace App\Core\Rounds;

class TodosRespondenRound extends BaseRound
{
    private $answeredPlayers = [];
    private $maxTime = 30; // seconds

    public function start(array $config)
    {
        parent::start($config);
        $this->answeredPlayers = [];

        if (isset($config['question'])) {
            $this->currentQuestion = $config['question'];
        } else {
            $this->currentQuestion = [
                'text' => '¿Pregunta para todos? (MOCK)',
                'options' => ['A', 'B', 'C', 'D'],
                'correct' => 'A'
            ];
        }
    }

    public function handleAction(string $action, int $playerId, array $data): ?array
    {
        if ($action === 'answer') {
            if (in_array($playerId, $this->answeredPlayers)) {
                return ['success' => false, 'reason' => 'already_answered'];
            }

            $answer = $data['answer'];
            $correct = ($answer === ($this->currentQuestion['correct'] ?? ''));
            $timeTaken = time() - $this->startTime;

            $points = 0;
            if ($correct) {
                // More points for faster answers. Max 20, Min 5.
                $points = max(5, 20 - intval($timeTaken / 2));
            }

            $this->scores[$playerId] = ($this->scores[$playerId] ?? 0) + $points;
            $this->answeredPlayers[] = $playerId;

            return ['success' => true, 'correct' => $correct, 'points' => $points];
        }

        return null;
    }

    public function getState(): array
    {
        $state = parent::getState();
        $state['answered_count'] = count($this->answeredPlayers);
        return $state;
    }
}
