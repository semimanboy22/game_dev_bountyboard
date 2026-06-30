<?php
require_once dirname(__DIR__) . '/src/bootstrap.php';

$avatarUrl = getUserAvatarUrl($pdo, $_SESSION['user_id'] ?? null);

$error = '';

$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif ($username === '' || $password === '') {
        $error = 'Please fill in all fields.';
    } else {
        try {
            $checkStmt = $pdo->prepare('SELECT 1 FROM gdbb_users WHERE username = ? LIMIT 1');
            $checkStmt->execute([$username]);

            if ($checkStmt->fetch()) {
                $error = 'That username is already taken. Please choose another one.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO gdbb_users (username, password_hash, role, is_admin, xp, level, is_blocked) VALUES (?, ?, ?, 0, 0, 1, 0)');
                $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), 'player']);
                header('Location: login.php');
                exit;
            }
        } catch (PDOException $e) {
            $error = 'Registration failed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>
<body class="bounty-page" style="--app-outline-color: <?= htmlspecialchars(getUserOutlineColor($pdo, $_SESSION['user_id'] ?? null), ENT_QUOTES, 'UTF-8') ?>;">
    <header class="topbar">
        <a href="register.php" class="brand-mark" aria-label="Go to register">
            <img src="<?= htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Profile picture">
        </a>
        <nav class="topnav">
            <a href="leaderboard.php">leaderbord</a>
            <a href="bounty.php">bounty</a>
            <a href="guilds.php">guilds</a>
            <a href="login.php">login</a>
        </nav>
    </header>

    <main class="register-page">
        <section class="register-card">
            <h1>Sign Up</h1>
            <?php if ($error): ?>
                <p class="register-link" style="color:#fff; margin-bottom:12px;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
            <form method="post" action="register.php">
                <div>
                    <label for="username">Username</label>
                    <input id="username" name="username" type="text" placeholder="Choose a username" required>
                </div>
                <div>
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" placeholder="Choose a password" required>
                </div>
                <div>
                    <label for="confirm_password">Confirm Password</label>
                    <input id="confirm_password" name="confirm_password" type="password" placeholder="Confirm your password" required>
                </div>
                <button class="register-button" type="submit">sign up</button>
            </form>
            <p class="register-link">Already have an account? <a href="login.php">Login</a></p>
        </section>
    </main>
</body>
</html>
