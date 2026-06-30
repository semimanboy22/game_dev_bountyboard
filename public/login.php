<?php
require_once dirname(__DIR__) . '/src/bootstrap.php';

$avatarUrl = getUserAvatarUrl($pdo, $_SESSION['user_id'] ?? null);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT id, username, password_hash, role, xp, level FROM gdbb_users WHERE username = ? LIMIT 1');
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                header('Location: bounty.php');
                exit;
            }

            $error = 'Invalid username or password.';
        } catch (PDOException $e) {
            $error = 'Login failed. Please try again. ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
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

    <main class="login-page">
        <section class="login-card">
            <h1>Sign In</h1>
            <?php if ($error): ?>
                <p class="login-link" style="margin-bottom:12px; color:#fff;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
            <form method="post" action="login.php">
                <div>
                    <label for="username">Username</label>
                    <input id="username" name="username" type="text" placeholder="username" required>
                </div>
                <div>
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" placeholder="password" required>
                </div>
                <button class="login-button" type="submit">sign in</button>
            </form>
            <p class="login-link"><a href="register.php">registreer</a></p>
        </section>
    </main>
</body>
</html>
