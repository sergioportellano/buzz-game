<?php
require_once __DIR__ . '/../../vendor/autoload.php';



use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

require_once __DIR__ . '/WebSocketServer.php';



$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new GameWebSocketServer()
        )
    ),
    8080
);

echo "🎮 Servidor WebSocket de Buzz Game iniciado en puerto 8080...\n";
echo "✅ Listo para conexiones...\n";

$server->run();