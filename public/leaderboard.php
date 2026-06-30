<?php
require_once dirname(__DIR__) . '/src/bootstrap.php';

$avatarUrl = getUserAvatarUrl($pdo, $_SESSION['user_id'] ?? null);
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$currentUserRole = '';
if ($currentUserId > 0) {
    $userRoleStmt = $pdo->prepare('SELECT role, is_admin FROM gdbb_users WHERE id = ? LIMIT 1');
    $userRoleStmt->execute([$currentUserId]);
    $currentUser = $userRoleStmt->fetch();
    $currentUserRole = (string)($currentUser['role'] ?? 'player');
}
$isAdmin = $currentUserRole === 'admin' || (int)($currentUser['is_admin'] ?? 0) === 1;

$stmt = $pdo->query('SELECT id, username, xp, level FROM gdbb_users ORDER BY xp DESC, level DESC, id ASC');
$users = $stmt->fetchAll();

$rankedUsers = [];
$rank = 1;
foreach ($users as $user) {
    $rankedUsers[] = [
        'rank' => $rank++,
        'username' => $user['username'],
        'xp' => (int)($user['xp'] ?? 0),
        'level' => (int)($user['level'] ?? 1),
    ];
}

$medalImages = [
    1 => 'images/gold_tropy.png',
    2 => 'images/silver_medal.png',
    3 => 'images/bronze_medal.png',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanuman:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>
<body class="leaderboard-page" style="--app-outline-color: <?= htmlspecialchars(getUserOutlineColor($pdo, $_SESSION['user_id'] ?? null), ENT_QUOTES, 'UTF-8') ?>;">
    <header class="topbar">
        <a href="<?= !empty($_SESSION['user_id']) ? 'profile.php' : 'register.php' ?>" class="brand-mark" aria-label="Go to profile">
            <img src="<?= htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Profile picture">
        </a>
        <nav class="topnav">
            <a href="leaderboard.php">leaderbord</a>
            <a href="bounty.php">bounty</a>
            <?php if ($isAdmin): ?>
                <a href="bounty-checker.php">bounty checker</a>
                <a href="bounty-creator.php">bounty creator</a>
            <?php endif; ?>
            <a href="guilds.php">guilds</a>
            <a href="<?= !empty($_SESSION['user_id']) ? 'logout.php' : 'login.php' ?>"><?= !empty($_SESSION['user_id']) ? 'logout' : 'login' ?></a>
        </nav>
    </header>

    <main class="leaderboard-main">
        <section class="leaderboard-banner" aria-label="Rankings banner">
            <h1>RANKINGS</h1>
            <p>this is the rankings of the best players</p>
        </section>

        <section class="leaderboard" aria-label="Leaderboard list">
            <?php foreach ($rankedUsers as $entry): ?>
                <article class="leaderboard-card rank-<?= min($entry['rank'], 5) ?>">
                    <?php if (!empty($medalImages[$entry['rank']])): ?>
                        <img class="leaderboard-medal" src="<?= htmlspecialchars($medalImages[$entry['rank']], ENT_QUOTES, 'UTF-8') ?>" alt="rank <?= $entry['rank'] ?> medal">
                    <?php endif; ?>

                    <span class="leaderboard-name">
                        <?= $entry['rank'] <= 3 ? '' : $entry['rank'] . ': ' ?>name: <?= htmlspecialchars($entry['username'] ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <span class="leaderboard-xp">xp: <?= (int)$entry['xp'] ?></span>
                </article>
            <?php endforeach; ?>
        </section>
    </main>
</body>
</html>
