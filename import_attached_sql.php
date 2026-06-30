<?php
$root = __DIR__;
require $root . '/src/bootstrap.php';

$sqlFile = 'C:\\Users\\semva\\Downloads\\game_dev_bounty_board (5).sql';
if (!file_exists($sqlFile)) {
    fwrite(STDERR, "SQL file not found: {$sqlFile}\n");
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
    if ($statement === '' || preg_match('/^(--|\/\*|\*\/|SET|START TRANSACTION|COMMIT)/', $statement)) {
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

$stmt = $pdo->query('SELECT COUNT(*) AS count FROM gdbb_users');
$count = $stmt->fetchColumn();
echo "Imported users: {$count}\n";
