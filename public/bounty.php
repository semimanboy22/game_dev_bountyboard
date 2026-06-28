<?php
require_once dirname(__DIR__) . '/src/bootstrap.php';

$dbError = null;

try {
    $stmt = $pdo->query('SELECT id, title, description, xp_reward, status FROM gdbb_bounties WHERE status = "open" ORDER BY id DESC');
    $bounties = $stmt->fetchAll();
} catch (PDOException $e) {
    $bounties = [];
    $dbError = $e->getMessage();
}

$displayBounties = array_slice($bounties, 0, 6);
$placeholderName = 'Upcoming bounty';
$placeholderDescription = 'A new bounty will be available soon.';
$isLoggedIn = !empty($_SESSION['user_id']) || !empty($_SESSION['user']) || !empty($_SESSION['username']) || !empty($_SESSION['email']);

while (count($displayBounties) < 6) {
    $displayBounties[] = [
        'id' => null,
        'title' => $placeholderName,
        'description' => $placeholderDescription,
        'xp_reward' => 250,
        'placeholder' => true,
    ];
}
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
<body class="bounty-page">
    <header class="topbar">
        <a href="<?= !empty($_SESSION['user_id']) ? 'profile.php' : 'register.php' ?>" class="brand-mark" aria-label="Go to profile"></a>
        <nav class="topnav">
            <a href="leaderboard.php">leaderbord</a>
            <a href="bounty.php">bounty</a>
            <a href="login.php">login</a>
        </nav>
    </header>

    <main class="bounty-grid-wrap">
        <section class="bounty-grid">
            <?php if (!empty($dbError)): ?>
                <div class="db-error">Database error: <?= htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <?php foreach ($displayBounties as $bounty): ?>
                <article class="bounty-card<?= !empty($bounty['placeholder']) ? ' empty-card' : '' ?>">
                    <div class="card-label">bounty</div>
                    <h2><?= htmlspecialchars($bounty['title'] ?? $bounty['bounty_name'] ?? 'Upcoming bounty', ENT_QUOTES, 'UTF-8') ?></h2>
                    <div class="card-details">
                        <p><strong>Description:</strong><br><?= htmlspecialchars($bounty['description'] ?? $bounty['bounty_description'] ?? 'No description provided.', ENT_QUOTES, 'UTF-8') ?></p>
                        <p><strong>XP:</strong> <?= (int)($bounty['xp_reward'] ?? $bounty['bounty_xp'] ?? 0) ?></p>
                        <p><strong>DIFFICULTY:</strong> <?= !empty($bounty['placeholder']) ? 'COMING SOON' : 'HARD' ?></p>
                        <p><strong>status:</strong> <?= !empty($bounty['placeholder']) ? 'opening soon' : ($bounty['status'] ?? 'not claimed') ?></p>
                        <p><strong>do date:</strong> <?= !empty($bounty['placeholder']) ? 'TBA' : '19-12-2069' ?></p>
                    </div>
                    <a href="<?= !empty($bounty['placeholder']) ? 'register.php' : ($isLoggedIn ? 'SubmitProof.php' : 'register.php') ?>" class="submit-button">create account to submit proof</a>
                </article>
            <?php endforeach; ?>
        </section>
    </main>
</body>
</html>
