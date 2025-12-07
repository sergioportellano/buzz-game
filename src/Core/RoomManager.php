<?php
class RoomManager
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function createRoom($roomCode, $creatorId, $maxPlayers, $roomName = '')
    {
        try {
            // Verificar que el usuario existe
            $this->db->query("SELECT id FROM jugadores WHERE id = ?", [$creatorId]);
            $usuario = $this->db->fetch();

            if (!$usuario) {
                error_log("Usuario no encontrado: $creatorId");
                return false;
            }

            $config = json_encode(['room_name' => $roomName]);

            $this->db->query(
                "INSERT INTO salas (codigo, creador_id, estado, max_jugadores, configuracion) 
             VALUES (?, ?, 'waiting', ?, ?)",
                [$roomCode, $creatorId, $maxPlayers, $config]
            );

            $salaId = $this->db->lastInsertId();

            // Usar el nombre real del usuario como anfitrión
            $this->addPlayerToRoom($salaId, $creatorId, $_SESSION['usuario_nombre'] ?? 'Anfitrión');

            return $roomCode;
        } catch (PDOException $e) {
            error_log("Error creando sala: " . $e->getMessage());
            return false;
        }
    }



    public function joinRoom($roomCode, $playerId, $playerName)
    {
        try {
            // Obtener la sala
            $sala = $this->getRoom($roomCode);
            if (!$sala) {
                error_log("Sala no encontrada: $roomCode");
                return false;
            }

            // DEBUG: Verificar estructura de la sala
            error_log("=== JOIN ROOM DEBUG ===");
            error_log("Sala ID: " . $sala['id']);
            error_log("Player ID: " . $playerId);
            error_log("Player Name: " . $playerName);
            error_log("Jugadores actuales: " . count($sala['players']));
            error_log("Máximo permitido: " . $sala['max_players']);

            // Verificar si hay espacio
            $playerCount = count($sala['players']);
            if ($playerCount >= $sala['max_players']) {
                error_log("Sala llena: $playerCount/" . $sala['max_players']);
                return false;
            }

            // Verificar si el jugador ya está en la sala
            foreach ($sala['players'] as $player) {
                if ($player['id'] == $playerId) { // Usar == en lugar de === para comparar int vs string
                    error_log("Jugador ya está en la sala");
                    return true;
                }
            }

            // Agregar jugador a la sala
            $result = $this->addPlayerToRoom($sala['id'], $playerId, $playerName);
            error_log("Resultado de addPlayerToRoom: " . ($result ? 'éxito' : 'fallo'));

            return $result;

        } catch (PDOException $e) {
            error_log("Error en joinRoom: " . $e->getMessage());
            return false;
        }
    }

    private function addPlayerToRoom($salaId, $playerId, $playerName)
    {
        try {
            error_log("Agregando jugador - SalaID: $salaId, PlayerID: $playerId, Nombre: $playerName");

            $this->db->query(
                "INSERT INTO jugadores_sala (sala_id, jugador_id, nombre_jugador, puntos, vidas, estado) 
             VALUES (?, ?, ?, 0, 3, 'activo')",
                [$salaId, $playerId, $playerName]
            );

            error_log("Jugador agregado exitosamente");
            return true;
        } catch (PDOException $e) {
            error_log("Error en addPlayerToRoom: " . $e->getMessage());
            return false;
        }
    }

    // En RoomManager.php, agrega este método:
    public function updateCurrentRound($roomCode, $roundNumber)
    {
        try {
            $this->db->query(
                "UPDATE salas SET ronda_actual = ? WHERE codigo = ?",
                [$roundNumber, $roomCode]
            );
            return true;
        } catch (PDOException $e) {
            error_log("Error actualizando ronda: " . $e->getMessage());
            return false;
        }
    }

    public function updatePlayerScore($roomCode, $playerId, $score)
    {
        try {
            $this->db->query(
                "UPDATE jugadores_sala 
                 SET puntos = ? 
                 WHERE sala_id = (SELECT id FROM salas WHERE codigo = ?) 
                 AND jugador_id = ?",
                [$score, $roomCode, $playerId]
            );
            return true;
        } catch (PDOException $e) {
            error_log("Error actualizando puntos: " . $e->getMessage());
            return false;
        }
    }

    public function getRoom($roomCode)
    {
        try {
            error_log("Buscando sala: $roomCode");

            $this->db->query(
                "SELECT s.*, 
                    js.id as js_id, js.jugador_id, js.nombre_jugador, js.puntos, js.vidas, js.estado as player_status
             FROM salas s
             LEFT JOIN jugadores_sala js ON s.id = js.sala_id
             WHERE s.codigo = ?",
                [$roomCode]
            );

            $rows = $this->db->fetchAll();
            error_log("Filas encontradas: " . count($rows));

            if (empty($rows)) {
                error_log("No se encontró la sala: $roomCode");
                return null;
            }

            // Construir estructura de sala
            $config = [];
            if (!empty($rows[0]['configuracion'])) {
                $config = json_decode($rows[0]['configuracion'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log("Error decodificando JSON: " . json_last_error_msg());
                    $config = [];
                }
            }

            $room = [
                'id' => $rows[0]['id'],
                'code' => $rows[0]['codigo'],
                'name' => $config['room_name'] ?? 'Sala ' . $rows[0]['codigo'],
                'creator_id' => $rows[0]['creador_id'],
                'max_players' => $rows[0]['max_jugadores'] ?? 4,
                'state' => $rows[0]['estado'] ?? 'waiting',
                'current_round' => $rows[0]['ronda_actual'] ?? 1,
                'players' => []
            ];

            error_log("Sala base: " . print_r($room, true));

            // Agregar jugadores
            foreach ($rows as $row) {
                if ($row['js_id'] && $row['jugador_id']) {
                    $room['players'][] = [
                        'id' => $row['jugador_id'], // ← Este es el importante
                        'name' => $row['nombre_jugador'],
                        'score' => $row['puntos'] ?? 0,
                        'lives' => $row['vidas'] ?? 3,
                        'status' => $row['player_status'] ?? 'activo'
                    ];
                }
            }

            error_log("Jugadores encontrados: " . count($room['players']));
            return $room;

        } catch (PDOException $e) {
            error_log("Error en getRoom: " . $e->getMessage());
            return null;
        }
    }

    public function updateRoomState($roomCode, $state)
    {
        try {
            $this->db->query(
                "UPDATE salas SET estado = ? WHERE codigo = ?",
                [$state, $roomCode]
            );
            return true;
        } catch (PDOException $e) {
            error_log("Error actualizando estado: " . $e->getMessage());
            return false;
        }
    }

    public function startGame($roomCode)
    {
        try {
            $this->db->query(
                "UPDATE salas 
                 SET estado = 'jugando', fecha_inicio = NOW() 
                 WHERE codigo = ?",
                [$roomCode]
            );
            return true;
        } catch (PDOException $e) {
            error_log("Error iniciando juego: " . $e->getMessage());
            return false;
        }
    }

    public function isCreator($roomCode, $playerId)
    {
        try {
            $this->db->query(
                "SELECT creador_id FROM salas WHERE codigo = ?",
                [$roomCode]
            );
            $room = $this->db->fetch();

            if ($room && $room['creador_id']) {
                // Comparar como strings para evitar problemas de tipo
                $result = ((string) $room['creador_id'] === (string) $playerId);
                error_log("isCreator: CreadorBD=" . $room['creador_id'] . ", Player=" . $playerId . ", Result=" . ($result ? 'true' : 'false'));
                return $result;
            }

            return false;
        } catch (PDOException $e) {
            error_log("Error verificando creador: " . $e->getMessage());
            return false;
        }
    }

    public function getAllRooms()
    {
        try {
            $this->db->query("SELECT codigo FROM salas WHERE estado != 'finalizada'");
            return $this->db->fetchAll();
        } catch (PDOException $e) {
            error_log("Error obteniendo salas: " . $e->getMessage());
            return [];
        }
    }

    public function removePlayerFromRoom($roomCode, $playerId)
    {
        try {
            error_log("Eliminando jugador $playerId de sala $roomCode");

            $this->db->query(
                "DELETE FROM jugadores_sala 
				 WHERE sala_id = (SELECT id FROM salas WHERE codigo = ?) 
				 AND jugador_id = ?",
                [$roomCode, $playerId]
            );

            // Verificar si la sala queda vacía para eliminarla
            $room = $this->getRoom($roomCode);
            if ($room && empty($room['players'])) {
                $this->db->query(
                    "DELETE FROM salas WHERE codigo = ?",
                    [$roomCode]
                );
                error_log("Sala $roomCode eliminada por estar vacía");
            }

            return true;
        } catch (PDOException $e) {
            error_log("Error eliminando jugador: " . $e->getMessage());
            return false;
        }
    }

    public function cleanupEmptyRooms()
    {
        try {
            $this->db->query(
                "DELETE s FROM salas s 
				 LEFT JOIN jugadores_sala js ON s.id = js.sala_id 
				 WHERE js.id IS NULL"
            );
            error_log("Limpieza de salas vacías completada");
        } catch (PDOException $e) {
            error_log("Error en cleanup: " . $e->getMessage());
        }
    }
}
?>