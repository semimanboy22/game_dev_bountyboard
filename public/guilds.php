<?php
require_once dirname(__DIR__) . '/src/bootstrap.php';

$avatarUrl = getUserAvatarUrl($pdo, $_SESSION['user_id'] ?? null);
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$currentUserRole = '';
$currentUserXp = 0;
$currentUserGuildId = null;
if ($currentUserId > 0) {
    $userRoleStmt = $pdo->prepare('SELECT role, is_admin, xp FROM gdbb_users WHERE id = ? LIMIT 1');
    $userRoleStmt->execute([$currentUserId]);
    $currentUser = $userRoleStmt->fetch();
    $currentUserRole = (string)($currentUser['role'] ?? 'player');
    $currentUserXp = (int)($currentUser['xp'] ?? 0);

    $membershipStmt = $pdo->prepare('SELECT guild_id FROM gdbb_guild_members WHERE user_id = ? LIMIT 1');
    $membershipStmt->execute([$currentUserId]);
    $currentMembership = $membershipStmt->fetch();
    $currentUserGuildId = $currentMembership ? (int)$currentMembership['guild_id'] : null;
}
$isAdmin = $currentUserRole === 'admin' || (int)($currentUser['is_admin'] ?? 0) === 1;

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($currentUserId <= 0) {
        $message = 'You need to be logged in before using guild features.';
        $messageType = 'error';
    } elseif ($_POST['action'] === 'create') {
        if ($currentUserXp < 5000) {
            $message = 'You need at least 5000 XP to create a guild.';
            $messageType = 'error';
        } elseif ($currentUserGuildId !== null) {
            $message = 'You already belong to a guild.';
            $messageType = 'error';
        } else {
            $name = trim((string)($_POST['guild_name'] ?? ''));
            $description = trim((string)($_POST['guild_description'] ?? ''));
            if ($name === '') {
                $message = 'Please enter a guild name.';
                $messageType = 'error';
            } else {
                try {
                    $insertGuildStmt = $pdo->prepare('INSERT INTO gdbb_guilds (name, description, leader_user_id) VALUES (?, ?, ?)');
                    $insertGuildStmt->execute([$name, $description, $currentUserId]);
                    $guildId = (int)$pdo->lastInsertId();

                    $insertMemberStmt = $pdo->prepare('INSERT INTO gdbb_guild_members (guild_id, user_id, role) VALUES (?, ?, ?)');
                    $insertMemberStmt->execute([$guildId, $currentUserId, 'leader']);

                    $message = 'Guild created successfully.';
                    $messageType = 'success';
                    $currentUserGuildId = $guildId;
                } catch (PDOException $e) {
                    $message = 'That guild name is already taken.';
                    $messageType = 'error';
                }
            }
        }
    } elseif ($_POST['action'] === 'join') {
        $guildId = (int)($_POST['guild_id'] ?? 0);
        if ($guildId <= 0) {
            $message = 'A valid guild was not selected.';
            $messageType = 'error';
        } elseif ($currentUserGuildId !== null) {
            $message = 'You already belong to a guild.';
            $messageType = 'error';
        } else {
            try {
                $joinStmt = $pdo->prepare('INSERT INTO gdbb_guild_members (guild_id, user_id, role) VALUES (?, ?, ?)');
                $joinStmt->execute([$guildId, $currentUserId, 'member']);
                $message = 'You joined the guild.';
                $messageType = 'success';
                $currentUserGuildId = $guildId;
            } catch (PDOException $e) {
                $message = 'You could not join that guild.';
                $messageType = 'error';
            }
        }
    } elseif ($_POST['action'] === 'leave') {
        $guildId = (int)($_POST['guild_id'] ?? 0);
        if ($guildId <= 0) {
            $message = 'A valid guild was not selected.';
            $messageType = 'error';
        } elseif ($currentUserGuildId !== $guildId) {
            $message = 'You are not a member of that guild.';
            $messageType = 'error';
        } else {
            try {
                $leaveStmt = $pdo->prepare('DELETE FROM gdbb_guild_members WHERE guild_id = ? AND user_id = ? LIMIT 1');
                $leaveStmt->execute([$guildId, $currentUserId]);
                $message = 'You left the guild.';
                $messageType = 'success';
                $currentUserGuildId = null;
            } catch (PDOException $e) {
                $message = 'You could not leave that guild.';
                $messageType = 'error';
            }
        }
    }
}

$guildStmt = $pdo->query('SELECT g.id, g.name, g.description, g.leader_user_id, leader.username AS leader_name, COALESCE(member_stats.member_count, 0) AS member_count, COALESCE(member_xp.total_xp, 0) AS total_xp FROM gdbb_guilds g LEFT JOIN gdbb_users leader ON leader.id = g.leader_user_id LEFT JOIN (SELECT guild_id, COUNT(*) AS member_count FROM gdbb_guild_members GROUP BY guild_id) AS member_stats ON member_stats.guild_id = g.id LEFT JOIN (SELECT gm.guild_id, SUM(user_xp.xp) AS total_xp FROM gdbb_guild_members gm LEFT JOIN gdbb_users user_xp ON user_xp.id = gm.user_id GROUP BY gm.guild_id) AS member_xp ON member_xp.guild_id = g.id ORDER BY total_xp DESC, g.name ASC');
$guilds = $guildStmt->fetchAll();

$selectedGuildId = isset($_GET['guild_id']) ? (int)$_GET['guild_id'] : 0;
if ($selectedGuildId <= 0 && !empty($guilds)) {
    $selectedGuildId = (int)$guilds[0]['id'];
}

$selectedGuild = null;
$selectedMembers = [];
if ($selectedGuildId > 0) {
    $selectedGuildStmt = $pdo->prepare('SELECT g.id, g.name, g.description, g.leader_user_id, leader.username AS leader_name FROM gdbb_guilds g LEFT JOIN gdbb_users leader ON leader.id = g.leader_user_id WHERE g.id = ? LIMIT 1');
    $selectedGuildStmt->execute([$selectedGuildId]);
    $selectedGuild = $selectedGuildStmt->fetch();

    if ($selectedGuild) {
        $membersStmt = $pdo->prepare('SELECT gm.role, gm.joined_at, u.id, u.username, u.xp FROM gdbb_guild_members gm LEFT JOIN gdbb_users u ON u.id = gm.user_id WHERE gm.guild_id = ? ORDER BY CASE WHEN gm.role = "leader" THEN 0 ELSE 1 END, u.xp DESC, u.username ASC');
        $membersStmt->execute([$selectedGuildId]);
        $selectedMembers = $membersStmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guilds</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>
<body class="guilds-page" style="--app-outline-color: <?= htmlspecialchars(getUserOutlineColor($pdo, $_SESSION['user_id'] ?? null), ENT_QUOTES, 'UTF-8') ?>;">
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

    <main class="guilds-shell">
        <section class="guilds-card" aria-label="Guilds information">
            <div class="guilds-card-header">
                <div>
                    <h1>guilds</h1>
                    <p>Choose a guild to view its members and join the one that fits your style.</p>
                </div>
                <?php if ($currentUserId > 0): ?>
                    <div class="guilds-user-status">
                        <span>Your XP: <?= (int)$currentUserXp ?></span>
                        <?php if ($currentUserXp >= 5000): ?>
                            <span class="xp-ready">You can create a guild</span>
                        <?php else: ?>
                            <span class="xp-blocked">Need 5000 XP to create</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($message !== ''): ?>
                <div class="guilds-message <?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <div class="guilds-layout">
                <div class="guilds-list-panel">
                    <h2>guild leaderboard</h2>
                    <?php if (empty($guilds)): ?>
                        <p class="guilds-empty">No guilds have been created yet.</p>
                    <?php else: ?>
                        <div class="guild-list">
                            <?php foreach ($guilds as $guild): ?>
                                <a class="guild-list-item <?= ((int)$guild['id'] === $selectedGuildId) ? 'active' : '' ?>" href="guilds.php?guild_id=<?= (int)$guild['id'] ?>">
                                    <div class="guild-list-main">
                                        <strong><?= htmlspecialchars($guild['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    </div>
                                    <div class="guild-list-meta">
                                        <span><?= (int)$guild['total_xp'] ?> total XP</span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="guilds-detail-panel">
                    <?php if ($selectedGuild): ?>
                        <div class="guild-detail-card">
                            <div class="guild-detail-header">
                                <div>
                                    <h3><?= htmlspecialchars($selectedGuild['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                                    <p><?= htmlspecialchars($selectedGuild['description'] !== '' ? $selectedGuild['description'] : 'No description yet.', ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                                <div class="guild-summary">
                                    <span><?= count($selectedMembers) ?> members</span>
                                    <span><?= array_sum(array_column($selectedMembers, 'xp')) ?> total XP</span>
                                </div>
                            </div>

                            <div class="guild-members-list" aria-label="Guild member rankings">
                                <?php foreach ($selectedMembers as $index => $member): ?>
                                    <div class="guild-member-item">
                                        <div class="guild-member-info">
                                            <strong><?= htmlspecialchars($member['username'], ENT_QUOTES, 'UTF-8') ?></strong>
                                            <span><?= ($index === 0 && $member['role'] === 'leader') ? 'Owner' : ($member['role'] === 'leader' ? 'Leader' : 'Member') ?></span>
                                        </div>
                                        <span class="guild-member-xp"><?= (int)$member['xp'] ?> XP</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <?php if ($currentUserId > 0 && $currentUserGuildId === null): ?>
                                <form method="post" class="guild-action-form">
                                    <input type="hidden" name="action" value="join">
                                    <input type="hidden" name="guild_id" value="<?= (int)$selectedGuild['id'] ?>">
                                    <button type="submit">join guild</button>
                                </form>
                            <?php elseif ($currentUserId > 0 && $currentUserGuildId === (int)$selectedGuild['id']): ?>
                                <form method="post" class="guild-action-form">
                                    <input type="hidden" name="action" value="leave">
                                    <input type="hidden" name="guild_id" value="<?= (int)$selectedGuild['id'] ?>">
                                    <button type="submit">leave guild</button>
                                </form>
                            <?php elseif ($currentUserId <= 0): ?>
                                <p class="guilds-member-note">Log in to join this guild.</p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <p class="guilds-empty">Select a guild to see its details.</p>
                    <?php endif; ?>

                    <div class="guild-create-card">
                        <h3>create a guild</h3>
                        <?php if ($currentUserId <= 0): ?>
                            <p>Log in to create a guild.</p>
                        <?php elseif ($currentUserXp < 5000): ?>
                            <p class="guild-create-locked">You need 5000 XP to create a guild.</p>
                        <?php elseif ($currentUserGuildId !== null): ?>
                            <p class="guild-create-locked">You can only belong to one guild at a time.</p>
                        <?php else: ?>
                            <details class="guild-create-toggle">
                                <summary>create guild</summary>
                                <form method="post" class="guild-create-form">
                                    <input type="hidden" name="action" value="create">
                                    <label for="guild_name">Guild name</label>
                                    <input id="guild_name" name="guild_name" type="text" maxlength="100" required>
                                    <label for="guild_description">Description</label>
                                    <textarea id="guild_description" name="guild_description" rows="3" maxlength="500"></textarea>
                                    <button type="submit">create guild</button>
                                </form>
                            </details>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
