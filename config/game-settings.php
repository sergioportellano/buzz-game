<?php
return [
    // Configuración del juego
    'max_players' => 8,
    'rounds_total' => 10,
    'victory_points' => 100,
    'difficulty_mode' => 'medium',
    'allows_spectators' => true,
    'advanced_rules' => [
        'powerups_enabled' => false,
        'wildcards_allowed' => false,
        'response_bounce' => false,
        'team_mode' => false
    ],

    // Configuración de base de datos - CORREGIDO: claves compatibles con Database.php
    'database' => [
        'host' => 'localhost',
        'dbname' => 'buzz',    // CAMBIADO: 'name' → 'dbname'
        'username' => 'root',    // CAMBIADO: 'username' (era correcto)
        'password' => '',        // CAMBIADO: 'pass' → 'password'
        'charset' => 'utf8mb4'
    ]
];
?>