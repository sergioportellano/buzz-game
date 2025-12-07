-- Núcleo del sistema
CREATE TABLE salas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(10) UNIQUE NOT NULL,
    creador_id INT,
    estado ENUM('configuracion', 'esperando', 'jugando', 'pausada', 'finalizada') DEFAULT 'configuracion',
    configuracion JSON,
    max_jugadores INT DEFAULT 8,
    ronda_actual INT DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_inicio TIMESTAMP NULL,
    fecha_fin TIMESTAMP NULL
);

CREATE TABLE jugadores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) UNIQUE,
    avatar VARCHAR(255),
    preferencias JSON,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE jugadores_sala (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sala_id INT,
    jugador_id INT,
    nombre_jugador VARCHAR(50),
    posicion INT,
    puntos INT DEFAULT 0,
    vidas INT DEFAULT 3,
    estado ENUM('activo', 'eliminado', 'desconectado') DEFAULT 'activo',
    datos_partida JSON,
    FOREIGN KEY (sala_id) REFERENCES salas(id),
    FOREIGN KEY (jugador_id) REFERENCES jugadores(id)
);

CREATE TABLE tipos_ronda (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    codigo VARCHAR(30) UNIQUE NOT NULL,
    descripcion TEXT,
    configuracion_default JSON,
    activo BOOLEAN DEFAULT TRUE,
    max_jugadores INT DEFAULT 8,
    min_jugadores INT DEFAULT 2,
    orden_sugerido INT
);

CREATE TABLE configuraciones_ronda (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_ronda_id INT,
    nombre VARCHAR(50),
    configuracion JSON,
    es_publica BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (tipo_ronda_id) REFERENCES tipos_ronda(id)
);

CREATE TABLE preguntas_musica (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pregunta TEXT NOT NULL,
    tipo_ronda_id INT,
    archivo_audio VARCHAR(255),
    duracion_audio INT,
    metadata JSON,
    categoria VARCHAR(50),
    dificultad ENUM('facil', 'medio', 'dificil') DEFAULT 'facil',
    activa BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tipo_ronda_id) REFERENCES tipos_ronda(id)
);

CREATE TABLE respuestas_musica (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pregunta_id INT,
    respuesta TEXT NOT NULL,
    correcta BOOLEAN DEFAULT FALSE,
    metadata JSON,
    FOREIGN KEY (pregunta_id) REFERENCES preguntas_musica(id)
);

CREATE TABLE partidas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sala_id INT,
    configuracion JSON,
    resultado JSON,
    duracion INT,
    fecha_inicio TIMESTAMP,
    fecha_fin TIMESTAMP,
    FOREIGN KEY (sala_id) REFERENCES salas(id)
);

CREATE TABLE rondas_ejecutadas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partida_id INT,
    tipo_ronda_id INT,
    numero_ronda INT,
    configuracion JSON,
    resultado JSON,
    duracion INT,
    FOREIGN KEY (partida_id) REFERENCES partidas(id),
    FOREIGN KEY (tipo_ronda_id) REFERENCES tipos_ronda(id)
);