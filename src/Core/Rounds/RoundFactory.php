<?php
class RoundFactory
{
    public static function createRound($type, $config, $roomCode, $players, $db)
    {
        // Map type strings to Class Names
        $map = [
            'buzz_rapido' => 'App\Core\Rounds\BuzzRapidoRound',
            'todos_responden' => 'App\Core\Rounds\TodosRespondenRound',
            'bomba_musical' => 'App\Core\Rounds\BombaMusicalRound'
        ];

        // Manual requires because no autoloader
        require_once __DIR__ . '/RoundInterface.php';
        require_once __DIR__ . '/BaseRound.php';
        require_once __DIR__ . '/BuzzRapidoRound.php';
        require_once __DIR__ . '/TodosRespondenRound.php';
        require_once __DIR__ . '/BombaMusicalRound.php';

        if (!isset($map[$type])) {
            throw new Exception("Unknown round type: $type");
        }

        $className = $map[$type];

        // My Rounds take ($roomCode) in constructor.
        // And they expect start($config) to be called later.
        // So here we just instantiate.

        return new $className($roomCode, $players);
    }
}
