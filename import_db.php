<?php
$root = 'C:\\xampp\\htdocs\\leerjaar-1\\main-projects\\Game-dev bounty board';
require $root . '/src/bootstrap.php';

$sqlFile = $root . '/databases/game-dev-bounty-board-laptopdb.sql';
if (!file_exists($sqlFile)) {
    fwrite(STDERR, "SQL file not found: $sqlFile\n");
    exit(1);
}

$sql = file_get_contents($sqlFile);
if ($sql === false) {
    fwrite(STDERR, "Could not read SQL file.\n");
    exit(1);
}

$statements = preg_split('/;\s*(?:\R|$)/', $sql);
foreach ($statements as $statement) {
    $statement = trim($statement);
    if ($statement === '' || preg_match('/^(--|\/\*|\*\/)/', $statement)) {
        continue;
    }
    try {
        $pdo->exec($statement);
    } catch (PDOException $e) {
        fwrite(STDERR, "Import failed: " . $e->getMessage() . "\n");
        fwrite(STDERR, "Statement: " . $statement . "\n");
        exit(1);
    }
}

echo "Database import completed.\n";