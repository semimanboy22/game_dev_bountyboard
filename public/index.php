<?php
require_once dirname(__DIR__) . '/src/bootstrap.php';

$introCookieName = 'seen_intro_pages';
if (!empty($_COOKIE[$introCookieName])) {
    header('Location: bounty.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rules</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <main class="rules-page">
        <section class="rules-box">
            <h1>Rules</h1>
            <p class="rules-text">
                Do not submit false information.<br>
                Do not discriminate in your text, and do not use racist language or behaviour.<br>
                Do not post images that violate these rules or could easily offend others.
            </p>
            <a href="welcome.php?from_rules=1" class="next-button" aria-label="Next">
                Next <span aria-hidden="true">→</span>
            </a>
        </section>
    </main>
</body>
</html>
