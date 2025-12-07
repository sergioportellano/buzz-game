<?php
require_once __DIR__ . '/src/Core/Database.php';
$db = new Database();
$db->query("SELECT * FROM respuestas_musica WHERE pregunta_id IN (1,2,3,4,6)");
$rows = $db->fetchAll();
foreach ($rows as $r) {
    echo "QID: " . $r['pregunta_id'] . " Ans: " . $r['respuesta'] . "\n";
}
