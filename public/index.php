<?php
require_once '../src/Core/Database.php';
require_once '../src/Core/GameEngine.php';
require_once '../src/Core/RoomManager.php';
require_once '../src/Core/ConfigManager.php';
require_once '../src/Core/QuestionManager.php';

// Initialize the application
session_start();

// Load configuration settings
$config = require '../config/game-settings.php';

// Set up the database connection
$db = new Database($config['database']);

// Initialize core components
$roomManager = new RoomManager($db);
$configManager = new ConfigManager($config);
$gameEngine = new GameEngine($roomManager, $configManager);

// Guardar solo la configuración en sesión (no objetos)
$_SESSION['config'] = $config;

// Handle routing based on the request
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$basePath = '/public'; // Ajusta según tu estructura

// Remover el base path si existe
if ($basePath !== '/' && strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}

// Simple routing - CORREGIDO
switch ($requestUri) {
    case '/':
    case '/index.php':
    case '':
        // Mostrar crear-sala.php directamente en lugar de redirigir
        include 'crear-sala.php';
        break;
    case '/crear-sala.php':
        include 'crear-sala.php';
        break;
    case '/sala.php':
        include 'sala.php';
        break;
    case '/juego.php':
        include 'juego.php';
        break;
    default:
        // 404 Not Found
        http_response_code(404);
        echo '404 Not Found - Página no encontrada: ' . htmlspecialchars($requestUri);
        break;
}
?>