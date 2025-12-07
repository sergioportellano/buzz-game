<?php
class ConfigManager {
    private $config;

    public function __construct($configFile) {
        $this->loadConfig($configFile);
    }

    private function loadConfig($configFile) {
        if (file_exists($configFile)) {
            $this->config = json_decode(file_get_contents($configFile), true);
        } else {
            throw new Exception("Configuration file not found: " . $configFile);
        }
    }

    public function get($key) {
        return $this->config[$key] ?? null;
    }

    public function set($key, $value) {
        $this->config[$key] = $value;
    }

    public function save($configFile) {
        file_put_contents($configFile, json_encode($this->config, JSON_PRETTY_PRINT));
    }

    public function getAll() {
        return $this->config;
    }
}
?>