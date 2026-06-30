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

if (!$isAdmin) {
    header('Location: bounty.php');
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim((string)($_POST['title'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $xpReward = (int)($_POST['xp_reward'] ?? 0);
    $difficulty = trim((string)($_POST['difficulty'] ?? 'hard'));
    $expireDate = trim((string)($_POST['expire_date'] ?? ''));
    $rewardColor = trim((string)($_POST['reward_color'] ?? ''));
    if (!preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $rewardColor)) {
        $rewardColor = '#82C1FF';
    }

    if ($title !== '' && $description !== '') {
        try {
            $stmt = $pdo->prepare('INSERT INTO gdbb_bounties (title, description, difficulty, xp_reward, deadline, status, created_by_user_id, expire_date, reward_color) VALUES (?, ?, ?, ?, ?, "open", ?, ?, ?)');
            $stmt->execute([$title, $description, $difficulty, $xpReward, $expireDate !== '' ? $expireDate : null, $currentUserId > 0 ? $currentUserId : null, $expireDate !== '' ? $expireDate : null, $rewardColor]);
            $message = 'Bounty created successfully.';
        } catch (PDOException $e) {
            $message = 'Could not create bounty: ' . $e->getMessage();
        }
    } else {
        $message = 'Please fill in the title and description.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bounty Creator</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Hanuman:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>
<body class="bounty-creator-page" style="--app-outline-color: <?= htmlspecialchars(getUserOutlineColor($pdo, $_SESSION['user_id'] ?? null), ENT_QUOTES, 'UTF-8') ?>;">
    <header class="topbar">
        <a href="profile.php" class="brand-mark" aria-label="Go to profile">
            <img src="<?= htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Profile picture">
        </a>
        <nav class="topnav">
            <a href="leaderboard.php">leaderbord</a>
            <a href="bounty.php">bounty</a>
            <a href="bounty-checker.php">bounty checker</a>
            <a href="bounty-creator.php">bounty creator</a>
            <a href="guilds.php">guilds</a>
            <a href="logout.php">logout</a>
        </nav>
    </header>

    <main class="creator-shell">
        <section class="creator-card" aria-label="Create bounty form">
            <h1>CREATE BOUNTY</h1>

            <?php if ($message !== ''): ?>
                <p class="creator-message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>

            <form class="creator-form" method="post">
                <div class="creator-field">
                    <label for="title">Bounty Title</label>
                    <input id="title" name="title" type="text" value="">
                </div>

                <div class="creator-field">
                    <label for="description">bounty description</label>
                    <input id="description" name="description" type="text" value="">
                </div>

                <div class="creator-field creator-row">
                    <div class="creator-inline">
                        <label for="xp_reward">Bounty XP:</label>
                        <input id="xp_reward" name="xp_reward" type="number" value="">
                    </div>
                    <div class="creator-inline">
                        <label for="difficulty">Difficulty:</label>
                        <select id="difficulty" name="difficulty">
                            <option value="hard">hard</option>
                            <option value="medium">medium</option>
                            <option value="easy">easy</option>
                        </select>
                    </div>
                </div>

                <div class="creator-field creator-row">
                    <div class="creator-inline">
                        <label for="expire_date">expire date:</label>
                        <input id="expire_date" name="expire_date" type="text" value="">
                    </div>
                    <div class="creator-inline">
                        <label for="reward_color">reward color:</label>
                        <input id="reward_color" name="reward_color" type="color" value="#82C1FF">
                    </div>
                </div>

                <button class="creator-submit" type="submit">Create Bounty</button>
            </form>
        </section>
    </main>
</body>
</html>
