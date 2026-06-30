<?php
require_once dirname(__DIR__) . '/src/bootstrap.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$avatarUrl = getUserAvatarUrl($pdo, $userId);

$userRoleStmt = $pdo->prepare('SELECT role, is_admin FROM gdbb_users WHERE id = ? LIMIT 1');
$userRoleStmt->execute([$userId]);
$currentUser = $userRoleStmt->fetch();
$isAdmin = (string)($currentUser['role'] ?? 'player') === 'admin' || (int)($currentUser['is_admin'] ?? 0) === 1;

if (!$isAdmin) {
    header('Location: bounty.php');
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submission_id'], $_POST['decision'])) {
    $submissionId = (int)$_POST['submission_id'];
    $decision = $_POST['decision'] === 'approve' ? 'approve' : 'reject';

    $submissionStmt = $pdo->prepare('SELECT bs.id, bs.bounty_id, bs.user_id, bs.approved, bs.status, b.title, b.xp_reward, b.reward_color, b.status AS bounty_status FROM gdbb_bounty_submissions bs LEFT JOIN gdbb_bounties b ON b.id = bs.bounty_id WHERE bs.id = ? LIMIT 1');
    $submissionStmt->execute([$submissionId]);
    $submission = $submissionStmt->fetch();

    if (!$submission) {
        $message = 'Submission not found.';
    } elseif ((string)($submission['bounty_status'] ?? '') === 'closed' || (string)($submission['status'] ?? '') !== 'pending') {
        $message = 'This submission was already reviewed.';
    } elseif ($decision === 'approve') {
        $pdo->beginTransaction();
        try {
            $approveStmt = $pdo->prepare('UPDATE gdbb_bounty_submissions SET approved = 1, status = "approved", reviewed_by = ?, reviewed_at = NOW() WHERE id = ?');
            $approveStmt->execute([$userId, $submissionId]);

            $completeBountyStmt = $pdo->prepare('UPDATE gdbb_bounties SET status = "completed" WHERE id = ? AND status != "completed"');
            $completeBountyStmt->execute([(int)$submission['bounty_id']]);

            $rewardUserStmt = $pdo->prepare('UPDATE gdbb_users SET xp = xp + ? WHERE id = ?');
            $rewardUserStmt->execute([(int)$submission['xp_reward'], (int)$submission['user_id']]);

            if (!empty($submission['reward_color'])) {
                $unlockColorStmt = $pdo->prepare('INSERT IGNORE INTO gdbb_unlocked_outline_colors (user_id, color_code) VALUES (?, ?)');
                $unlockColorStmt->execute([(int)$submission['user_id'], (string)$submission['reward_color']]);
            }

            $rejectOthersStmt = $pdo->prepare('UPDATE gdbb_bounty_submissions SET approved = 0, status = "rejected", reviewed_by = ?, reviewed_at = NOW() WHERE bounty_id = ? AND id != ?');
            $rejectOthersStmt->execute([$userId, (int)$submission['bounty_id'], $submissionId]);

            $pdo->commit();
            $message = 'Submission approved and the bounty reward was granted.';
        } catch (Throwable $e) {
            $pdo->rollBack();
            $message = 'Could not approve the submission: ' . $e->getMessage();
        }
    } else {
        $pdo->beginTransaction();
        try {
            $deleteStmt = $pdo->prepare('DELETE FROM gdbb_bounty_submissions WHERE id = ?');
            $deleteStmt->execute([$submissionId]);

            $remainingStmt = $pdo->prepare('SELECT COUNT(*) FROM gdbb_bounty_submissions WHERE bounty_id = ? AND status IN ("pending", "approved")');
            $remainingStmt->execute([(int)$submission['bounty_id']]);
            if ((int)$remainingStmt->fetchColumn() === 0) {
                $resetBountyStmt = $pdo->prepare('UPDATE gdbb_bounties SET status = "open" WHERE id = ?');
                $resetBountyStmt->execute([(int)$submission['bounty_id']]);
            }

            $pdo->commit();
            $message = 'Submission rejected and removed.';
        } catch (Throwable $e) {
            $pdo->rollBack();
            $message = 'Could not reject the submission: ' . $e->getMessage();
        }
    }
}

$submissionsStmt = $pdo->query('SELECT bs.id, bs.bounty_id, bs.user_id, bs.proof_description, bs.proof_photo, bs.status, bs.submitted_at, bs.approved, b.title, b.xp_reward, b.description, u.username FROM gdbb_bounty_submissions bs LEFT JOIN gdbb_bounties b ON b.id = bs.bounty_id LEFT JOIN gdbb_users u ON u.id = bs.user_id WHERE bs.status = "pending" ORDER BY bs.submitted_at ASC, bs.id ASC');
$submissions = $submissionsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bounty Checker</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Hanuman:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>
<body class="checker-page" style="--app-outline-color: <?= htmlspecialchars(getUserOutlineColor($pdo, $_SESSION['user_id'] ?? null), ENT_QUOTES, 'UTF-8') ?>;">
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

    <main class="checker-shell">
        <h1>bounty checker</h1>
        <?php if ($message !== ''): ?>
            <p class="checker-message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <?php if (empty($submissions)): ?>
            <p class="checker-message">No pending submissions right now.</p>
        <?php else: ?>
            <section class="checker-list">
                <?php foreach ($submissions as $submission): ?>
                    <article class="checker-card">
                        <div>
                            <span class="checker-status">pending review</span>
                            <h2><?= htmlspecialchars((string)($submission['title'] ?? 'Untitled bounty'), ENT_QUOTES, 'UTF-8') ?></h2>
                            <p><strong>submitted by:</strong> <?= htmlspecialchars((string)($submission['username'] ?? 'Unknown user'), ENT_QUOTES, 'UTF-8') ?></p>
                            <p><strong>xp reward:</strong> <?= (int)($submission['xp_reward'] ?? 0) ?></p>
                            <p><strong>description:</strong> <?= htmlspecialchars((string)($submission['description'] ?? 'No description provided.'), ENT_QUOTES, 'UTF-8') ?></p>
                            <p><strong>proof description:</strong> <?= htmlspecialchars((string)($submission['proof_description'] ?? 'No description provided.'), ENT_QUOTES, 'UTF-8') ?></p>
                            <div class="checker-actions">
                                <form method="post">
                                    <input type="hidden" name="submission_id" value="<?= (int)$submission['id'] ?>">
                                    <button class="checker-button approve" type="submit" name="decision" value="approve">approved</button>
                                </form>
                                <form method="post">
                                    <input type="hidden" name="submission_id" value="<?= (int)$submission['id'] ?>">
                                    <button class="checker-button reject" type="submit" name="decision" value="reject">not accepted</button>
                                </form>
                            </div>
                        </div>
                        <div class="checker-proof-box">
                            <?php if (!empty($submission['proof_photo'])): ?>
                                <img src="<?= htmlspecialchars((string)$submission['proof_photo'], ENT_QUOTES, 'UTF-8') ?>" alt="Proof image">
                            <?php else: ?>
                                <p>No proof image attached.</p>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
