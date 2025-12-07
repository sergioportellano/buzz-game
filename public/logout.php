<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../src/Core/Database.php';
require_once '../src/Core/AuthManager.php';
require_once '../src/Core/RoomManager.php';

$db = new Database();
$auth = new AuthManager($db);
$roomManager = new RoomManager($db);

// Remover jugador de sala si está en una
if (isset($_SESSION['current_room']) && $auth->estaLogueado()) {
    $roomCode = $_SESSION['current_room'];
    $playerId = $auth->obtenerUsuarioActual();
    
    $roomManager->removePlayerFromRoom($roomCode, $playerId);
    error_log("Jugador $playerId removido de sala $roomCode al cerrar sesión");
    
    unset($_SESSION['current_room']);
    unset($_SESSION['player_name']);
}

// Destruir sesión completamente
session_destroy();

// Redirigir a login
header("Location: login.php");
exit();
?>