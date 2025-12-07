<?php
require_once __DIR__ . '/src/Core/Database.php';

$db = new Database();

try {
    // 1. Ensure Round Type Exists
    $db->query("SELECT id FROM tipos_ronda WHERE id = 1");
    if (!$db->fetch()) {
        $db->query("INSERT INTO tipos_ronda (id, nombre, codigo, descripcion, orden_sugerido) VALUES 
            (1, 'Buzz Rápido', 'buzz_rapido', 'El primero que pulsa responde', 1)");
        echo "Round Type 'Buzz Rápido' created.\n";
    }

    // 2. Insert Question
    $sql = "INSERT INTO preguntas_musica (pregunta, tipo_ronda_id, archivo_audio, duracion_audio, categoria, dificultad) 
            VALUES (:pregunta, :tipo, :audio, :duracion, :cat, :dif)";

    $file = '1.mp3';

    $db->query($sql, [
        ':pregunta' => '¿Quién interpreta esta canción misteriosa?',
        ':tipo' => 1,
        ':audio' => $file,
        ':duracion' => 30,
        ':cat' => 'Random',
        ':dif' => 'medio'
    ]);

    $qId = $db->lastInsertId();
    echo "Question inserted with ID: $qId\n";

    // 3. Insert Answers
    $answers = [
        ['text' => 'El Artista Correcto', 'correct' => 1],
        ['text' => 'Un Imitador', 'correct' => 0],
        ['text' => 'Banda Desconocida', 'correct' => 0],
        ['text' => 'Cantante de Ducha', 'correct' => 0]
    ];

    foreach ($answers as $ans) {
        $db->query("INSERT INTO respuestas_musica (pregunta_id, respuesta, correcta) VALUES (:qid, :text, :corr)", [
            ':qid' => $qId,
            ':text' => $ans['text'],
            ':corr' => $ans['correct']
        ]);
    }

    echo "Answers inserted.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
