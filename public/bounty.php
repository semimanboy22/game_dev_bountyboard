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

$introCookieName = 'seen_intro_pages';
if (!empty($_GET['from_welcome'])) {
    setcookie($introCookieName, '1', time() + (60 * 60 * 24 * 365 * 1000), '/');
}

$message = isset($_GET['message']) ? trim((string)$_GET['message']) : '';

$dbError = null;

try {
    $stmt = $pdo->query('SELECT id, title, description, xp_reward, difficulty, expire_date, reward_color, status FROM gdbb_bounties WHERE status != "completed" ORDER BY id DESC');
    $bounties = $stmt->fetchAll();
} catch (PDOException $e) {
    $bounties = [];
    $dbError = $e->getMessage();
}

$userSubmissionStatus = [];
if ($currentUserId > 0) {
    try {
        $submissionStatusStmt = $pdo->prepare('SELECT bounty_id FROM gdbb_bounty_submissions WHERE user_id = ?');
        $submissionStatusStmt->execute([$currentUserId]);
        foreach ($submissionStatusStmt->fetchAll(PDO::FETCH_COLUMN) as $bountyId) {
            $userSubmissionStatus[(int)$bountyId] = true;
        }
    } catch (PDOException $e) {
        $userSubmissionStatus = [];
    }
}

$isLoggedIn = !empty($_SESSION['user_id']) || !empty($_SESSION['user']) || !empty($_SESSION['username']) || !empty($_SESSION['email']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bounties</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>
<body class="bounty-page" style="--app-outline-color: <?= htmlspecialchars(getUserOutlineColor($pdo, $_SESSION['user_id'] ?? null), ENT_QUOTES, 'UTF-8') ?>;">
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

    <main class="bounty-grid-wrap">
        <?php if ($message !== ''): ?>
            <p class="bounty-message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <section class="bounty-grid">
            <?php if (!empty($dbError)): ?>
                <div class="db-error">Database error: <?= htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <?php if (empty($bounties)): ?>
                <p class="bounty-empty">No active bounties yet.</p>
            <?php endif; ?>

            <?php foreach ($bounties as $bounty): ?>
                <article class="bounty-card">
                    <div class="card-label">bounty</div>
                    <h2><?= htmlspecialchars($bounty['title'] ?? 'Untitled bounty', ENT_QUOTES, 'UTF-8') ?></h2>
                    <div class="card-details">
                        <p><strong>Description:</strong><br><?= htmlspecialchars($bounty['description'] ?? 'No description provided.', ENT_QUOTES, 'UTF-8') ?></p>
                        <p><strong>XP:</strong> <?= (int)($bounty['xp_reward'] ?? 0) ?></p>
                        <p><strong>DIFFICULTY:</strong> <?= htmlspecialchars((string)($bounty['difficulty'] ?? 'hard'), ENT_QUOTES, 'UTF-8') ?></p>
                        <?php
                        $statusValue = (string)($bounty['status'] ?? 'open');
                        $statusLabel = $statusValue === 'in_review' ? 'being reviewed' : ($statusValue === 'completed' ? 'completed' : $statusValue);
                        $hasPendingSubmission = !empty($userSubmissionStatus[(int)$bounty['id']]);
                        ?>
                        <p class="status-line"><strong>status:</strong> <span class="status-value"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span><?php if ($hasPendingSubmission): ?><span class="submission-status-inline">proof submitted · waiting for admin approval</span><?php endif; ?></p>
                        <p><strong>expire date:</strong> <?= htmlspecialchars((string)($bounty['expire_date'] ?? 'TBA'), ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <?php if ($isLoggedIn): ?>
                        <a href="bounty-detail.php?id=<?= (int)$bounty['id'] ?>" class="submit-button">submit proof</a>
                    <?php else: ?>
                        <a href="login.php" class="submit-button">login to submit proof</a>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </section>
    </main>
</body>
</html>
