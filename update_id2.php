<?php
require_once __DIR__ . '/src/Core/Database.php';
$db = new Database();
// Using direct PDo
$db->getPdo()->exec("UPDATE preguntas_musica SET archivo_audio = '2.mp3' WHERE id = 2");
echo "Updated ID 2.\n";
