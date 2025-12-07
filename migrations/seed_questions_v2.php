<?php
require_once __DIR__ . '/../src/Core/Database.php';

$db = new Database();
$db = new Database();
$pdo = $db->getPdo();

echo "🔍 Escaneando archivos de audio...\n";
$files = glob(__DIR__ . '/../public/uploads/audio/*.{mp3,MP3}', GLOB_BRACE);

if (empty($files)) {
    die("❌ No se encontraron archivos MP3 en public/uploads/audio\n");
}

echo "✅ Encontrados " . count($files) . " archivos.\n";

// Round Types: 1=Buzz, 2=Todos, 3=Bomba
$roundTypes = [1, 2, 3];
$typeNames = [1 => 'Buzz Rápido', 2 => 'Todos Responden', 3 => 'Bomba Musical'];
$typeIndex = 0;

$artists = ['Queen', 'Michael Jackson', 'Madonna', 'The Beatles', 'AC/DC', 'Shakira', 'Bad Bunny', 'Dua Lipa'];
$songs = ['Bohemian Rhapsody', 'Thriller', 'Like a Virgin', 'Hey Jude', 'Thunderstruck', 'Hips Don\'t Lie', 'Tití me preguntó', 'Levitating'];

foreach ($files as $filePath) {
    $filename = basename($filePath);

    // Check if duplicate
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM preguntas_musica WHERE archivo_audio = ?");
    $stmt->execute([$filename]);
    if ($stmt->fetchColumn() > 0) {
        echo "⏭️  Saltando $filename (ya existe)\n";
        continue;
    }

    $typeId = $roundTypes[$typeIndex % 3];
    $typeIndex++;

    // Generate Random Question Data
    $correctArtist = $artists[array_rand($artists)];
    $correctSong = $songs[array_rand($songs)];

    $questionText = match ($typeId) {
        1 => "¿Quién es el artista de este éxito?",
        2 => "¿Cómo se llama esta canción?",
        3 => "¡Rápido! ¿Qué instrumento predomina al inicio?",
        default => "¿Qué canción es?"
    };

    $correctAnswer = match ($typeId) {
        1 => $correctArtist,
        2 => $correctSong,
        3 => "Guitarra Eléctrica", // Generic for Bomba
        default => "Opción A"
    };

    // Distractors
    $options = [];
    $options[] = ['text' => $correctAnswer, 'correct' => 1];

    // Fill distractors
    while (count($options) < 4) {
        $fake = ($typeId == 1) ? $artists[array_rand($artists)] : (($typeId == 2) ? $songs[array_rand($songs)] : "Batería");
        if ($fake !== $correctAnswer) {
            // Check uniqueness in options
            $unique = true;
            foreach ($options as $o)
                if ($o['text'] === $fake)
                    $unique = false;

            if ($unique)
                $options[] = ['text' => $fake, 'correct' => 0];
        }
    }

    shuffle($options);

    echo "➕ Insertando pregunta para [$filename] -> Tipo: " . $typeNames[$typeId] . "\n";

    // Insert Question
    $stmt = $pdo->prepare("INSERT INTO preguntas_musica (tipo_ronda_id, pregunta, archivo_audio, activa) VALUES (?, ?, ?, 1)");
    $stmt->execute([$typeId, $questionText, $filename]);
    $questionId = $pdo->lastInsertId();

    // Insert Options
    $stmtOpt = $pdo->prepare("INSERT INTO respuestas_musica (pregunta_id, respuesta, correcta) VALUES (?, ?, ?)");
    foreach ($options as $opt) {
        $stmtOpt->execute([$questionId, $opt['text'], $opt['correct']]);
    }
}

echo "✨ ¡Hecho! Base de datos poblada.\n";
