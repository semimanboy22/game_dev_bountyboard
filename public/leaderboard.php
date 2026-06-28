<?php
require_once dirname(__DIR__) . '/src/bootstrap.php';

$stmt = $pdo->query('SELECT id, name, XP FROM bb_users ORDER BY XP DESC, id ASC LIMIT 10');
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <main class="page">
        <section class="card">
            <h1>Leaderboard</h1>
            <ol>
                <?php foreach ($users as $user): ?>
                    <li><?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?> — <?= (int)$user['XP'] ?> XP</li>
                <?php endforeach; ?>
            </ol>
            <p><a href="/public/index.php">Terug naar home</a></p>
        </section>
    </main>
</body>
</html>
