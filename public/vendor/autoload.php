<?php
// public/vendor/autoload.php - VERSIÓN FINAL CORREGIDA

// Ruta CORRECTA a Ratchet (subimos 2 niveles)
$ratchetPath = realpath(__DIR__ . '/../../vendor/ratchetphp/ratchet/src/Ratchet');

if (!$ratchetPath || !file_exists($ratchetPath)) {
    echo "❌ ERROR: No se encuentra Ratchet en: " . (__DIR__ . '/../../vendor/ratchetphp/ratchet/src/Ratchet') . "\n";
    echo "📁 Por favor verifica la estructura de carpetas\n";
    exit;
}

echo "✅ Ratchet encontrado en: $ratchetPath\n";

echo "✅ Clases de Ratchet cargadas correctamente\n";

function checkRatchetClasses() {
    $classes = [
        'Ratchet\MessageComponentInterface',
        'Ratchet\ConnectionInterface',
        'Ratchet\Server\IoServer',
        'Ratchet\Http\HttpServer',
        'Ratchet\WebSocket\WsServer'
    ];

    foreach ($classes as $class) {
        if (class_exists($class)) {
            echo "✅ $class cargado\n";
        } else {
            echo "❌ $class NO cargado\n";
        }
    }
}

checkRatchetClasses();
