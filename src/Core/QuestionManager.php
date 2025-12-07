<?php
class QuestionManager
{
    private $db;
    private $pdo;

    public function __construct($db)
    {
        $this->db = $db;
        // Access PDO instance via reflection or getter if available, 
        // but Database class wrapper doesn't expose it directly publicly usually.
        // Let's check Database.php. It has no getPdo(). 
        // We will add a getPdo() to Database.php or work with the wrapper functions.
        // The wrapper has query(), fetchAll(), lastInsertId(). That is enough for transaction?
        // Database.php has no beginTransaction wrapper.
        // I'll stick to standard queries for now or modify Database.php.
        // Best approach: Add getPdo() to Database.php or use raw queries carefully.
        // I will assume for now I can modify Database.php later if needed, but I'll try to work with what I have.
    }

    public function getRoundTypes()
    {
        $this->db->query("SELECT * FROM tipos_ronda ORDER BY orden_sugerido ASC");
        return $this->db->fetchAll();
    }

    public function createQuestion($data, $file)
    {
        try {
            // 1. Handle File Upload
            $targetDir = __DIR__ . '/../../public/uploads/audio/';
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            $fileName = uniqid() . '_' . basename($file['name']);
            $targetPath = $targetDir . $fileName;

            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                throw new Exception("Error al subir el archivo.");
            }

            // 2. Insert Question
            $sql = "INSERT INTO preguntas_musica (pregunta, tipo_ronda_id, archivo_audio, duracion_audio, categoria, dificultad) 
                    VALUES (:pregunta, :tipo_ronda_id, :archivo_audio, :duracion_audio, :categoria, :dificultad)";

            $this->db->query($sql, [
                ':pregunta' => $data['pregunta'],
                ':tipo_ronda_id' => $data['tipo_ronda_id'],
                ':archivo_audio' => $fileName, // Store only filename
                ':duracion_audio' => 30, // Default or calculate?
                ':categoria' => $data['categoria'],
                ':dificultad' => $data['dificultad']
            ]);

            $questionId = $this->db->lastInsertId();

            // 3. Insert Answers
            foreach ($data['respuestas'] as $index => $respuestaText) {
                $isCorrect = (intval($data['correcta']) === $index);

                $sqlAns = "INSERT INTO respuestas_musica (pregunta_id, respuesta, correcta) 
                           VALUES (:pregunta_id, :respuesta, :correcta)";

                $this->db->query($sqlAns, [
                    ':pregunta_id' => $questionId,
                    ':respuesta' => $respuestaText,
                    ':correcta' => $isCorrect ? 1 : 0
                ]);
            }

            return ['success' => true, 'message' => 'Pregunta creada correctamente'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getQuestionsForRound($roundTypeId, $limit = 1, $offset = 0)
    {
        // 1. Get questions deterministically (by ID) to support pagination
        $limit = (int) $limit;
        $offset = (int) $offset;

        $sql = "SELECT * FROM preguntas_musica 
                WHERE tipo_ronda_id = :type_id 
                AND activa = 1 
                ORDER BY id ASC 
                LIMIT $limit OFFSET $offset";

        // Fix: Database->query returns $this, allow fetchAll
        $this->db->query($sql, [
            ':type_id' => $roundTypeId
        ]);
        $questions = $this->db->fetchAll();

        foreach ($questions as &$q) {
            // 2. Get answers for each question
            $this->db->query("SELECT * FROM respuestas_musica WHERE pregunta_id = :qid ORDER BY id ASC", [':qid' => $q['id']]);
            $q['respuestas'] = $this->db->fetchAll();

            // Format for Round usage
            $q['text'] = $q['pregunta'];
            $q['options'] = array_column($q['respuestas'], 'respuesta');

            // Find correct answer text
            foreach ($q['respuestas'] as $ans) {
                if ($ans['correcta']) {
                    $q['correct'] = $ans['respuesta'];
                    break;
                }
            }
        }

        return $questions;
    }
}
