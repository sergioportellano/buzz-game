<?php
require_once __DIR__ . '/src/Core/Database.php';
$db = new Database();
$pdo = $db->getPdo();
$stmt = $pdo->query("SELECT id, nombre, codigo FROM tipos_ronda");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
