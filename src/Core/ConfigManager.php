<?php
class ConfigManager {
    private $config;

    public function __construct($config) { // CAMBIADO: acepta array directamente
        $this->config = $config; // CAMBIADO: asigna directamente el array
    }

    // ELIMINADO: loadConfig ya no es necesario

    public function get($key, $default = null) {
        return $this->config[$key] ?? $default;
    }

    public function set($key, $value) {
        $this->config[$key] = $value;
    }

    // ELIMINADO: save ya no es necesario para configuración en memoria

    public function getAll() {
        return $this->config;
    }

    // AÑADIDO: método para obtener configuración de rondas
    public function getRoundsConfig($roomConfig = null) {
        // Lógica para obtener configuración de rondas
        return []; // placeholder
    }
}
?>