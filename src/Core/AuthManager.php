<?php
class AuthManager {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function registrar($usuario, $email, $password) {
        try {
            // Verificar si el email ya existe
            $this->db->query("SELECT id FROM jugadores WHERE email = ?", [$email]);
            if ($this->db->fetch()) {
                error_log("Email ya existe: " . $email);
                return false;
            }

            // Verificar si el usuario ya existe
            $this->db->query("SELECT id FROM jugadores WHERE usuario = ?", [$usuario]);
            if ($this->db->fetch()) {
                error_log("Usuario ya existe: " . $usuario);
                return false;
            }

            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            error_log("Registrando: $usuario, $email, Hash: " . substr($password_hash, 0, 20) . "...");
            
            $this->db->query(
                "INSERT INTO jugadores (usuario, email, password_hash, fecha_registro) 
                 VALUES (?, ?, ?, NOW())",
                [$usuario, $email, $password_hash]
            );
            
            $id = $this->db->lastInsertId();
            error_log("Usuario registrado con ID: " . $id);
            
            return $id;
            
        } catch (PDOException $e) {
            error_log("Error en registro: " . $e->getMessage());
            return false;
        }
    }

    public function login($email, $password) {
        try {
            error_log("Intentando login para: " . $email);
            
            $this->db->query(
                "SELECT id, usuario, email, password_hash FROM jugadores 
                 WHERE email = ?",
                [$email]
            );
            
            $usuario = $this->db->fetch();
            
            if (!$usuario) {
                error_log("Usuario no encontrado: " . $email);
                return false;
            }
            
            error_log("Usuario encontrado: " . $usuario['usuario']);
            error_log("Hash en BD: " . $usuario['password_hash']);
            
            if (password_verify($password, $usuario['password_hash'])) {
                error_log("Contraseña VERIFICADA");
                
                // Actualizar último acceso
                $this->db->query(
                    "UPDATE jugadores SET ultimo_acceso = NOW() WHERE id = ?",
                    [$usuario['id']]
                );
                
                return $usuario;
            } else {
                error_log("Contraseña INCORRECTA");
                return false;
            }
            
        } catch (PDOException $e) {
            error_log("Error en login: " . $e->getMessage());
            return false;
        }
    }

    public function estaLogueado() {
        return isset($_SESSION['usuario_id']);
    }

    public function obtenerUsuarioActual() {
        return $_SESSION['usuario_id'] ?? null;
    }

    public function logout() {
        session_destroy();
        session_start();
    }
}
?>