<?php
namespace App\Core\Rounds;

interface RoundInterface {
    /**
     * Start the round with specific configuration
     */
    public function start(array $config);

    /**
     * Handle a player action (buzz, answer, etc.)
     * Returns an array with the result of the action or null if invalid
     */
    public function handleAction(string $action, int $playerId, array $data): ?array;

    /**
     * Get the current public state of the round for the clients
     */
    public function getState(): array;

    /**
     * Check if the round should end or advance state (e.g. check timers)
     */
    public function checkConditions(): array;
}
