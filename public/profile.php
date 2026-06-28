<?php
require_once dirname(__DIR__) . '/src/bootstrap.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT id, username, role, is_admin, xp, level, is_blocked, created_at FROM gdbb_users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$bountyCount = 0;
try {
    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM gdbb_bounty_submissions WHERE user_id = ?');
    $countStmt->execute([$userId]);
    $bountyCount = (int)$countStmt->fetchColumn();
} catch (PDOException $e) {
    $bountyCount = 0;
}

$leaderboardPlace = 1;
try {
    $rankStmt = $pdo->query('SELECT id FROM gdbb_users WHERE xp > ' . (int)$user['xp'] . ' ORDER BY xp DESC, id ASC');
    $leaderboardPlace = (int)$rankStmt->rowCount() + 1;
} catch (PDOException $e) {
    $leaderboardPlace = 1;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap&family=Hanuman:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>
<body class="profile-page">
    <header class="topbar">
        <a href="profile.php" class="brand-mark" aria-label="Go to profile"></a>
        <nav class="topnav">
            <a href="leaderboard.php">leaderbord</a>
            <a href="bounty.php">bounty</a>
            <a href="logout.php">logout</a>
        </nav>
    </header>

    <main class="profile-shell">
        <div class="profile-avatar"></div>

        <section class="profile-info-stack">
            <div class="profile-card">name: <?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></div>
            <div class="profile-card">bounties completed : <?= (int)$bountyCount ?></div>
            <div class="profile-card">xp collected : <?= (int)$user['xp'] ?></div>
            <div class="profile-card">chance outline profile</div>
        </section>

        <div class="leaderboard-row">place on leaderbord: <?= (int)$leaderboardPlace ?></div>

        <section class="profile-outlines-panel">
            <h2>profile outlines unlocked</h2>
            <div class="outline-badge"></div>
        </section>
    </main>
</body>
</html>
