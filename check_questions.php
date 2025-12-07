<?php
require_once __DIR__ . '/src/Core/Database.php';

try {
    $db = new Database();
    $db->query("SELECT id, tipo_ronda_id, archivo_audio FROM preguntas_musica ORDER BY id ASC LIMIT 5");
    $rows = $db->fetchAll();
    foreach ($rows as $r) {
        echo "ID: " . $r['id'] . " Type: " . $r['tipo_ronda_id'] . " Audio: " . $r['archivo_audio'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
