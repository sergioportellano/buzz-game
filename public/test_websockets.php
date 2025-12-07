<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

echo "🎯 Iniciando prueba WebSocket...\n";

class ChatServer implements MessageComponentInterface {
    public function onOpen(ConnectionInterface $conn) {
        echo "👋 Nueva conexión: {$conn->resourceId}\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        echo "💬 Mensaje recibido: $msg\n";
    }

    public function onClose(ConnectionInterface $conn) {
        echo "❌ Conexión cerrada: {$conn->resourceId}\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "⚠️ Error: {$e->getMessage()}\n";
    }
}

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new ChatServer()
        )
    ),
    8080
);

echo "✅ Servidor WebSocket corriendo en puerto 8080...\n";
$server->run();
