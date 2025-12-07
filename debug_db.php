<?php
require_once __DIR__ . '/src/Core/Database.php';
require_once __DIR__ . '/src/Core/QuestionManager.php';

$db = new Database();
$qm = new QuestionManager($db);

echo "--- Debugging Data ---\n";

// Test getQuestionsForRound(1, 1, 0) -> Offset 0
echo "Fetching Offset 0 (Round 1):\n";
$q = $qm->getQuestionsForRound(1, 1, 0);
print_r($q);

// Test getQuestionsForRound(1, 1, 1) -> Offset 1
echo "\nFetching Offset 1 (Round 2):\n";
$q2 = $qm->getQuestionsForRound(1, 1, 1);
print_r($q2);

// Check if file exists
if (!empty($q)) {
    $file = __DIR__ . '/public/uploads/audio/' . ($q[0]['archivo_audio'] ?? 'MISSING');
    echo "\nChecking file: $file\n";
    echo file_exists($file) ? "EXISTS\n" : "DOES NOT EXIST\n";
}
