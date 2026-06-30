<?php
session_start();

$root = dirname(__DIR__);

$envPath = $root . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') === false || str_starts_with($line, '#')) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $_ENV[$key] = $value;
        putenv("{$key}={$value}");
    }
}

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_NAME') ?: 'game_dev_bounty_board';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';

function getUserAvatarUrl(PDO $pdo, ?int $userId = null): string {
    $defaultAvatar = 'images/placeholder.png';
    $userId = (int)($userId ?? ($_SESSION['user_id'] ?? 0));

    if ($userId <= 0) {
        return $defaultAvatar;
    }

    try {
        $stmt = $pdo->prepare('SELECT profile_picture FROM gdbb_users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        $avatarPath = trim((string)($user['profile_picture'] ?? ''));

        return $avatarPath !== '' ? $avatarPath : $defaultAvatar;
    } catch (PDOException $e) {
        return $defaultAvatar;
    }
}

function getUserOutlineColor(PDO $pdo, ?int $userId = null): string {
    $defaultColor = '#000000';
    $userId = (int)($userId ?? ($_SESSION['user_id'] ?? 0));

    if ($userId <= 0) {
        return $defaultColor;
    }

    try {
        $stmt = $pdo->prepare('SELECT profile_outline_color FROM gdbb_users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        $color = trim((string)($user['profile_outline_color'] ?? ''));

        return preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color) ? $color : $defaultColor;
    } catch (PDOException $e) {
        return $defaultColor;
    }
}

try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec("CREATE TABLE IF NOT EXISTS gdbb_users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('player','gm','admin') NOT NULL DEFAULT 'player',
        is_admin TINYINT(1) NOT NULL DEFAULT 0,
        xp INT UNSIGNED NOT NULL DEFAULT 0,
        level INT UNSIGNED NOT NULL DEFAULT 1,
        is_blocked TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS gdbb_bounties (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT DEFAULT NULL,
        xp_reward INT UNSIGNED NOT NULL DEFAULT 0,
        difficulty VARCHAR(20) NOT NULL DEFAULT 'hard',
        expire_date VARCHAR(50) DEFAULT NULL,
        reward_color VARCHAR(20) DEFAULT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'open',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS gdbb_bounty_submissions (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        bounty_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        proof_photo VARCHAR(255) DEFAULT NULL,
        proof_description TEXT DEFAULT NULL,
        approved TINYINT(1) NOT NULL DEFAULT 0,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        reviewed_by INT UNSIGNED DEFAULT NULL,
        reviewed_at TIMESTAMP NULL DEFAULT NULL,
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_bounty_submission (bounty_id, user_id)
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS gdbb_guilds (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        description TEXT DEFAULT NULL,
        leader_user_id INT UNSIGNED NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS gdbb_guild_members (
        guild_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        role ENUM('leader','member') NOT NULL DEFAULT 'member',
        joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (guild_id, user_id),
        UNIQUE KEY unique_user_guild_membership (user_id)
    ) ENGINE=InnoDB");

    $submissionColumnsStmt = $pdo->query('SHOW COLUMNS FROM gdbb_bounty_submissions');
    $submissionColumns = $submissionColumnsStmt->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('proof_photo', $submissionColumns, true)) {
        $pdo->exec('ALTER TABLE gdbb_bounty_submissions ADD COLUMN proof_photo VARCHAR(255) DEFAULT NULL');
    }
    if (!in_array('proof_description', $submissionColumns, true)) {
        $pdo->exec('ALTER TABLE gdbb_bounty_submissions ADD COLUMN proof_description TEXT DEFAULT NULL');
    }
    if (!in_array('approved', $submissionColumns, true)) {
        $pdo->exec('ALTER TABLE gdbb_bounty_submissions ADD COLUMN approved TINYINT(1) NOT NULL DEFAULT 0');
    }
    if (!in_array('status', $submissionColumns, true)) {
        $pdo->exec('ALTER TABLE gdbb_bounty_submissions ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT "pending"');
    }
    if (!in_array('reviewed_by', $submissionColumns, true)) {
        $pdo->exec('ALTER TABLE gdbb_bounty_submissions ADD COLUMN reviewed_by INT UNSIGNED DEFAULT NULL');
    }
    if (!in_array('reviewed_at', $submissionColumns, true)) {
        $pdo->exec('ALTER TABLE gdbb_bounty_submissions ADD COLUMN reviewed_at TIMESTAMP NULL DEFAULT NULL');
    }
    if (!in_array('submitted_at', $submissionColumns, true)) {
        $pdo->exec('ALTER TABLE gdbb_bounty_submissions ADD COLUMN submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS gdbb_unlocked_outline_colors (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        color_code VARCHAR(20) NOT NULL,
        unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_color (user_id, color_code)
    ) ENGINE=InnoDB");

    $columnsStmt = $pdo->query('SHOW COLUMNS FROM gdbb_users');
    $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('profile_picture', $columns, true)) {
        $pdo->exec('ALTER TABLE gdbb_users ADD COLUMN profile_picture VARCHAR(255) DEFAULT NULL');
    }
    if (!in_array('profile_outline_color', $columns, true)) {
        $pdo->exec('ALTER TABLE gdbb_users ADD COLUMN profile_outline_color VARCHAR(20) NOT NULL DEFAULT "#000000"');
    }

    $bountyColumnsStmt = $pdo->query('SHOW COLUMNS FROM gdbb_bounties');
    $bountyColumns = $bountyColumnsStmt->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('title', $bountyColumns, true)) {
        $pdo->exec('ALTER TABLE gdbb_bounties ADD COLUMN title VARCHAR(255) NOT NULL DEFAULT ""');
    }
    if (!in_array('description', $bountyColumns, true)) {
        $pdo->exec('ALTER TABLE gdbb_bounties ADD COLUMN description TEXT DEFAULT NULL');
    }
    if (!in_array('xp_reward', $bountyColumns, true)) {
        $pdo->exec('ALTER TABLE gdbb_bounties ADD COLUMN xp_reward INT UNSIGNED NOT NULL DEFAULT 0');
    }
    if (!in_array('difficulty', $bountyColumns, true)) {
        $pdo->exec('ALTER TABLE gdbb_bounties ADD COLUMN difficulty VARCHAR(20) NOT NULL DEFAULT "hard"');
    }
    if (!in_array('expire_date', $bountyColumns, true)) {
        $pdo->exec('ALTER TABLE gdbb_bounties ADD COLUMN expire_date VARCHAR(50) DEFAULT NULL');
    }
    if (!in_array('reward_color', $bountyColumns, true)) {
        $pdo->exec('ALTER TABLE gdbb_bounties ADD COLUMN reward_color VARCHAR(20) DEFAULT NULL');
    }
    if (!in_array('status', $bountyColumns, true)) {
        $pdo->exec('ALTER TABLE gdbb_bounties ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT "open"');
    }
    if (!in_array('deadline', $bountyColumns, true)) {
        $pdo->exec('ALTER TABLE gdbb_bounties ADD COLUMN deadline VARCHAR(50) DEFAULT NULL');
    } else {
        $pdo->exec('ALTER TABLE gdbb_bounties MODIFY deadline VARCHAR(50) NULL DEFAULT NULL');
    }
} catch (PDOException $e) {
    die('Database connection failed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
