<?php
require_once '../../src/Core/Database.php';
require_once '../../src/Core/QuestionManager.php';

session_start();

// Simple auth check
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php"); // Adjust path as needed
    exit;
}

$db = new Database();
$qm = new QuestionManager($db);
$types = $qm->getRoundTypes();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $qm->createQuestion($_POST, $_FILES['audio_file']);
    $message = $result['message'];
    $msgClass = $result['success'] ? 'success' : 'error';
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Agregar Pregunta</title>
    <style>
        body {
            font-family: sans-serif;
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }

        input,
        select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .answers {
            border: 1px solid #eee;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .answer-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 0.5rem;
            align-items: center;
        }

        .btn {
            background: #007bff;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn:hover {
            background: #0056b3;
        }

        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }

        .success {
            background: #d4edda;
            color: #155724;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>

<body>
    <h1>Agregar Nueva Pregunta</h1>
    <p><a href="/">Volver al Inicio</a></p>

    <?php if ($message): ?>
        <div class="alert <?= $msgClass ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Tipo de Ronda</label>
            <select name="tipo_ronda_id" required>
                <?php foreach ($types as $type): ?>
                    <option value="<?= $type['id'] ?>"><?= htmlspecialchars($type['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Pregunta</label>
            <input type="text" name="pregunta" required placeholder="Ej: ¿Quién canta esto?">
        </div>

        <div class="form-group">
            <label>Archivo de Audio (MP3)</label>
            <input type="file" name="audio_file" accept=".mp3,audio/mpeg" required>
        </div>

        <div class="form-group">
            <label>Categoría</label>
            <input type="text" name="categoria" placeholder="Rock, Pop, 80s..." required>
        </div>

        <div class="form-group">
            <label>Dificultad</label>
            <select name="dificultad">
                <option value="facil">Fácil</option>
                <option value="medio">Medio</option>
                <option value="dificil">Difícil</option>
            </select>
        </div>

        <h3>Respuestas</h3>
        <div class="answers">
            <p>Escribe 4 respuestas y marca la correcta.</p>
            <?php for ($i = 0; $i < 4; $i++): ?>
                <div class="answer-row">
                    <input type="radio" name="correcta" value="<?= $i ?>" <?= $i === 0 ? 'checked' : '' ?>>
                    <input type="text" name="respuestas[]" placeholder="Respuesta <?= $i + 1 ?>" required>
                </div>
            <?php endfor; ?>
        </div>

        <button type="submit" class="btn">Guardar Pregunta</button>
    </form>
</body>

</html>