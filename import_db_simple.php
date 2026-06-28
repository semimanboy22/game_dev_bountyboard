<?php
$root = 'C:\\xampp\\htdocs\\leerjaar-1\\main-projects\\Game-dev bounty board';
require $root . '/src/bootstrap.php';

$sqlFile = $root . '/databases/game-dev-bounty-board-laptopdb.sql';
$sql = file_get_contents($sqlFile);
if ($sql === false) {
    exit("SQL file not found\n");
}

$lines = preg_split('/\R/', $sql);
$buffer = '';
foreach ($lines as $line) {
    $trimmed = trim($line);
    if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '/*!') || str_starts_with($trimmed, 'SET ') || str_starts_with($trimmed, 'START TRANSACTION') || str_starts_with($trimmed, 'COMMIT;')) {
        continue;
    }
    if (str_contains($trimmed, 'CREATE DATABASE') || str_contains($trimmed, 'USE `')) {
        continue;
    }
    $buffer .= $line . "\n";
}

$statements = preg_split('/;\s*(?:\R|$)/', $buffer);
foreach ($statements as $statement) {
    $statement = trim($statement);
    if ($statement === '' || str_starts_with($statement, '--')) {
        continue;
    }
    try {
        $pdo->exec($statement);
    } catch (PDOException $e) {
        echo "Skipped/failed: " . $e->getMessage() . "\n";
    }
}

echo "Import cleanup completed.\n";