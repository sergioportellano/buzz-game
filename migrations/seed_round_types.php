<?php
require_once __DIR__ . '/../src/Core/Database.php';

$db = new Database();
$pdo = $db->getPdo();

$types = [
    [
        'nombre' => 'Todos Responden',
        'codigo' => 'todos_responden',
        'descripcion' => 'Todos los jugadores responden simultáneamente.',
        'configuracion_default' => json_encode(['tiempo' => 15, 'puntos' => 50])
    ],
    [
        'nombre' => 'Bomba Musical',
        'codigo' => 'bomba_musical',
        'descripcion' => 'Pasa la bomba antes de que explote.',
        'configuracion_default' => json_encode(['tiempo_min' => 10, 'tiempo_max' => 60])
    ]
];

foreach ($types as $type) {
    try {
        $stmt = $pdo->prepare("INSERT INTO tipos_ronda (nombre, codigo, descripcion, configuracion_default) VALUES (?, ?, ?, ?)");
        $stmt->execute([$type['nombre'], $type['codigo'], $type['descripcion'], $type['configuracion_default']]);
        echo "✅ Insertado tipo: " . $type['nombre'] . "\n";
    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 1062) {
            echo "ℹ️ Ya existe tipo: " . $type['nombre'] . "\n";
        } else {
            echo "❌ Error: " . $e->getMessage() . "\n";
        }
    }
}
