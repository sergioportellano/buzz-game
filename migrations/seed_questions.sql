-- Insert mock round types if they don't exist (assuming IDs 1, 2, 3 for simplicity or relying on auto-increment)
-- For safety, we will assume standard auto-increment.

-- Sample Questions for 'BuzzRapido' (Assuming type_id 1 is BuzzRapido)
-- We need to know the type_id. Let's assume we insert types first.

INSERT INTO tipos_ronda (nombre, codigo, descripcion, orden_sugerido) VALUES 
('Buzz Rápido', 'buzz_rapido', 'El primero que pulsa responde', 1),
('Todos Responden', 'todos_responden', 'Todos responden por puntos', 2),
('Bomba Musical', 'bomba_musical', 'Pasa la bomba acertando', 3);

-- Get IDs (Manual binding in real scenario, here we assume 1, 2, 3)

-- Q1: Queen
INSERT INTO preguntas_musica (pregunta, tipo_ronda_id, archivo_audio, duracion_audio, categoria, dificultad) VALUES 
('¿Qué banda interpreta esta canción?', 1, 'bohemian_rhapsody.mp3', 30, 'Rock', 'facil');

SET @q1_id = LAST_INSERT_ID();

INSERT INTO respuestas_musica (pregunta_id, respuesta, correcta) VALUES 
(@q1_id, 'Queen', TRUE),
(@q1_id, 'The Beatles', FALSE),
(@q1_id, 'Led Zeppelin', FALSE),
(@q1_id, 'Pink Floyd', FALSE);

-- Q2: Michael Jackson
INSERT INTO preguntas_musica (pregunta, tipo_ronda_id, archivo_audio, duracion_audio, categoria, dificultad) VALUES 
('¿Quién es el rey del pop?', 2, 'thriller.mp3', 30, 'Pop', 'facil');

SET @q2_id = LAST_INSERT_ID();

INSERT INTO respuestas_musica (pregunta_id, respuesta, correcta) VALUES 
(@q2_id, 'Michael Jackson', TRUE),
(@q2_id, 'Prince', FALSE),
(@q2_id, 'Madonna', FALSE),
(@q2_id, 'Elvis Presley', FALSE);

-- Q3: Bomba - Mozart
INSERT INTO preguntas_musica (pregunta, tipo_ronda_id, archivo_audio, duracion_audio, categoria, dificultad) VALUES 
('Compositor de esta pieza clásica', 3, 'eine_kleine_nachtmusik.mp3', 30, 'Clásica', 'medio');

SET @q3_id = LAST_INSERT_ID();

INSERT INTO respuestas_musica (pregunta_id, respuesta, correcta) VALUES 
(@q3_id, 'Mozart', TRUE),
(@q3_id, 'Beethoven', FALSE),
(@q3_id, 'Bach', FALSE),
(@q3_id, 'Vivaldi', FALSE);
