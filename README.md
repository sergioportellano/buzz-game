# 🎵 Buzz Game - Juego de Preguntas Musicales

Juego multijugador de preguntas musicales en tiempo real con WebSocket, inspirado en el clásico Buzz.

## 🎮 Características

- **Multijugador en tiempo real** con WebSocket
- **3 tipos de rondas**:
  - 🔴 **Buzz Rápido**: El primero en pulsar responde
  - 👥 **Todos Responden**: Todos los jugadores responden simultáneamente
  - 💣 **Bomba Musical**: Pasa la bomba antes de que explote
- **Temporizador de 8 segundos** para responder después del buzz
- **Cuenta regresiva de 3 segundos** antes de iniciar el juego
- **Sincronización de audio** para todos los jugadores
- **Sistema de puntuación** en tiempo real

## 🛠️ Tecnologías

- **Backend**: PHP 8.x
- **Base de datos**: MySQL
- **WebSocket**: Ratchet (PHP)
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Servidor**: Apache (XAMPP)

## 📋 Requisitos

- PHP 8.0 o superior
- MySQL 5.7 o superior
- Composer
- XAMPP (o Apache + MySQL)

## 🚀 Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/sergioportellano/buzz-game.git
cd buzz-game
```

### 2. Instalar dependencias

```bash
composer install
```

### 3. Configurar la base de datos

1. Importa el schema:
```bash
mysql -u root -p buzz_game < migrations/schema.sql
```

2. Ejecuta los seeds:
```bash
php migrations/seed_round_types.php
php migrations/seed_questions_v2.php
```

### 4. Iniciar el servidor WebSocket

```bash
php src/Core/start-websocket-server.php
```

El servidor WebSocket se ejecutará en `ws://localhost:8080`

### 5. Acceder al juego

Abre tu navegador en: `http://localhost/buzz/public/`

## 🎯 Cómo Jugar

1. **Crear una sala**: El anfitrión crea una sala con un código único
2. **Unirse**: Otros jugadores se unen usando el código de sala
3. **Iniciar**: El anfitrión inicia el juego (cuenta regresiva de 3 segundos)
4. **Jugar**: Responde preguntas musicales según el tipo de ronda
5. **Ganar**: El jugador con más puntos al final gana

## 📁 Estructura del Proyecto

```
buzz-game/
├── public/              # Archivos públicos (HTML, CSS, JS)
│   ├── index.php       # Página principal
│   ├── juego.php       # Interfaz del juego
│   └── uploads/audio/  # Archivos de audio
├── src/
│   ├── Core/           # Lógica principal del juego
│   │   ├── GameManager.php
│   │   ├── WebSocketServer.php
│   │   └── Rounds/     # Tipos de rondas
│   ├── Models/         # Modelos de datos
│   └── Services/       # Servicios auxiliares
├── migrations/         # Scripts de base de datos
└── config/            # Configuración
```

## 🔧 Configuración

Edita `config/database.php` con tus credenciales:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'buzz_game');
define('DB_USER', 'root');
define('DB_PASS', '');
```

## 🐛 Solución de Problemas

### El WebSocket no conecta
- Verifica que el servidor WebSocket esté corriendo
- Comprueba que el puerto 8080 no esté en uso
- Revisa la consola del navegador para errores

### Los audios no se reproducen
- Asegúrate de que los archivos MP3 estén en `public/uploads/audio/`
- Verifica los permisos de la carpeta

## 📝 Licencia

Este proyecto es de código abierto para uso educativo.

## 👨‍💻 Autor

**Sergio Portellano**
- GitHub: [@sergioportellano](https://github.com/sergioportellano)

---

⭐ Si te gusta este proyecto, dale una estrella en GitHub!
