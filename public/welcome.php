<?php
require_once dirname(__DIR__) . '/src/bootstrap.php';

$introCookieName = 'seen_intro_pages';
if (!empty($_COOKIE[$introCookieName])) {
    header('Location: bounty.php');
    exit;
}

if (isset($_GET['from_rules'])) {
    setcookie($introCookieName, '1', time() + (60 * 60 * 24 * 365 * 1000), '/');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanuman:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <main class="rules-page">
        <section class="rules-box">
            <h1>Welcome</h1>
            <p class="rules-text">
                Welcome to the Game Dev Bounty Board.<br>
                Here you can explore exciting challenges, earn rewards, and join a creative community built around teamwork, growth, and meaningful achievements.
            </p>
            <a href="bounty.php?from_welcome=1" class="next-button" aria-label="Next">
                Next <span aria-hidden="true">→</span>
            </a>
        </section>
    </main>
</body>
</html>
