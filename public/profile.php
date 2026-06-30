<?php
require_once dirname(__DIR__) . '/src/bootstrap.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$avatarUrl = getUserAvatarUrl($pdo, $userId);
$selectedOutlineColor = getUserOutlineColor($pdo, $userId);
$uploadMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_avatar'])) {
    if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
        $uploadMessage = 'Please choose a valid image.';
    } else {
        $file = $_FILES['profile_picture'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedExtensions, true)) {
            $uploadMessage = 'Only JPG, PNG, GIF, and WEBP images are allowed.';
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $uploadMessage = 'Image must be 2MB or smaller.';
        } else {
            $uploadDir = dirname(__DIR__) . '/public/uploads/profile_pictures';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $filename = 'user-' . $userId . '-' . time() . '.' . $extension;
            $targetPath = $uploadDir . '/' . $filename;

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $relativePath = 'uploads/profile_pictures/' . $filename;
                $updateStmt = $pdo->prepare('UPDATE gdbb_users SET profile_picture = ? WHERE id = ?');
                $updateStmt->execute([$relativePath, $userId]);
                $avatarUrl = $relativePath;
                $uploadMessage = 'Profile picture updated.';
            } else {
                $uploadMessage = 'Could not upload the profile picture.';
            }
        }
    }
}

$unlockedColors = [];
try {
    $unlockedColorsStmt = $pdo->prepare('SELECT DISTINCT color_code FROM gdbb_unlocked_outline_colors WHERE user_id = ? ORDER BY color_code');
    $unlockedColorsStmt->execute([$userId]);
    $unlockedColors = array_values(array_filter($unlockedColorsStmt->fetchAll(PDO::FETCH_COLUMN)));
} catch (PDOException $e) {
    $unlockedColors = [];
}
$allowedOutlineColors = array_values(array_unique(array_merge(['#000000'], $unlockedColors)));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['outline_color'])) {
    $outlineColor = $_POST['outline_color'] ?? '#000000';
    if (in_array($outlineColor, $allowedOutlineColors, true)) {
        $updateStmt = $pdo->prepare('UPDATE gdbb_users SET profile_outline_color = ? WHERE id = ?');
        $updateStmt->execute([$outlineColor, $userId]);
        $selectedOutlineColor = $outlineColor;
    }
}

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
    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM gdbb_bounty_submissions WHERE user_id = ? AND approved = 1');
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

$bountyHistory = [];
try {
    $historyStmt = $pdo->prepare('SELECT bs.id, bs.approved, bs.submitted_at, b.title, b.xp_reward, b.difficulty, b.status AS bounty_status FROM gdbb_bounty_submissions bs LEFT JOIN gdbb_bounties b ON b.id = bs.bounty_id WHERE bs.user_id = ? ORDER BY bs.submitted_at DESC LIMIT 8');
    $historyStmt->execute([$userId]);
    $bountyHistory = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $bountyHistory = [];
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
<body class="profile-page" style="--app-outline-color: <?= htmlspecialchars($selectedOutlineColor, ENT_QUOTES, 'UTF-8') ?>;">
    <header class="topbar">
        <a href="profile.php" class="brand-mark" aria-label="Go to profile">
            <img src="<?= htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Profile picture">
        </a>
        <nav class="topnav">
            <a href="leaderboard.php">leaderbord</a>
            <a href="bounty.php">bounty</a>
            <?php if ($user['role'] === 'admin' || (int)$user['is_admin'] === 1): ?>
                <a href="bounty-checker.php">bounty checker</a>
                <a href="bounty-creator.php">bounty creator</a>
            <?php endif; ?>
            <a href="guilds.php">guilds</a>
            <a href="logout.php">logout</a>
        </nav>
    </header>

    <main class="profile-shell">
        <div class="profile-avatar">
            <img src="<?= htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Profile picture">
        </div>

        <section class="profile-info-stack">
            <div class="profile-card">name: <?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></div>
            <div class="profile-card">bounties completed : <?= (int)$bountyCount ?></div>
            <div class="profile-card">xp collected : <?= (int)$user['xp'] ?></div>
            <div class="profile-card">
                <form class="avatar-form" method="post" enctype="multipart/form-data">
                    <label for="profile_picture">change profile picture</label>
                    <input id="profile_picture" name="profile_picture" type="file" accept="image/png,image/jpeg,image/gif,image/webp">
                    <button type="submit" name="change_avatar" value="1">upload</button>
                    <?php if ($uploadMessage): ?>
                        <p class="avatar-upload-message"><?= htmlspecialchars($uploadMessage, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                </form>
            </div>
        </section>

        <div class="leaderboard-row">place on leaderbord: <?= (int)$leaderboardPlace ?></div>

        <section class="profile-outlines-panel">
            <h2>profile outlines unlocked</h2>
            <form class="outline-color-form" method="post">
                <?php foreach ($allowedOutlineColors as $color): ?>
                    <button class="outline-option <?= $selectedOutlineColor === $color ? 'active' : '' ?>" type="submit" name="outline_color" value="<?= htmlspecialchars($color, ENT_QUOTES, 'UTF-8') ?>" style="background:<?= htmlspecialchars($color, ENT_QUOTES, 'UTF-8') ?>" aria-label="Select <?= htmlspecialchars($color, ENT_QUOTES, 'UTF-8') ?> outline"></button>
                <?php endforeach; ?>
            </form>
        </section>

        <section class="profile-history-panel">
            <h2>bounty history</h2>
            <?php if (empty($bountyHistory)): ?>
                <p class="history-empty">No bounty submissions yet.</p>
            <?php else: ?>
                <div class="history-list">
                    <?php foreach ($bountyHistory as $entry): ?>
                        <?php
                        $statusText = 'submitted';
                        if ($entry['approved'] === 1) {
                            $statusText = 'approved';
                        } elseif ((string)($entry['bounty_status'] ?? '') === 'closed') {
                            $statusText = 'completed';
                        } elseif ($entry['approved'] === 0) {
                            $statusText = 'rejected';
                        }
                        ?>
                        <article class="history-item">
                            <div>
                                <strong><?= htmlspecialchars((string)($entry['title'] ?? 'Untitled bounty'), ENT_QUOTES, 'UTF-8') ?></strong>
                                <span><?= htmlspecialchars((string)($entry['difficulty'] ?? 'unknown'), ENT_QUOTES, 'UTF-8') ?> · <?= (int)($entry['xp_reward'] ?? 0) ?> XP</span>
                            </div>
                            <span class="history-status <?= htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8') ?></span>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
