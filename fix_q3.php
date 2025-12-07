<?php
require_once __DIR__ . '/src/Core/Database.php';
$db = new Database();
$qid = 3;
$answers = [
    ['ans' => 'Respuesta A', 'correct' => 1],
    ['ans' => 'Respuesta B', 'correct' => 0],
    ['ans' => 'Respuesta C', 'correct' => 0],
    ['ans' => 'Respuesta D', 'correct' => 0]
];

foreach ($answers as $a) {
    // using direct values for simplicity in ad-hoc script
    $sql = "INSERT INTO respuestas_musica (pregunta_id, respuesta, correcta) VALUES ($qid, '{$a['ans']}', {$a['correct']})";
    $db->getPdo()->exec($sql);
}
echo "Inserted answers for QID $qid.\n";
