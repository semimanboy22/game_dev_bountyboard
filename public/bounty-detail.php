<?php
require_once dirname(__DIR__) . '/src/bootstrap.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$avatarUrl = getUserAvatarUrl($pdo, $userId);
$message = '';

$bountyId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($bountyId <= 0) {
    header('Location: bounty.php');
    exit;
}

$bountyStmt = $pdo->prepare('SELECT id, title, description, xp_reward, difficulty, expire_date, status, reward_color FROM gdbb_bounties WHERE id = ? LIMIT 1');
$bountyStmt->execute([$bountyId]);
$bounty = $bountyStmt->fetch();

if (!$bounty) {
    header('Location: bounty.php');
    exit;
}

$existingSubmissionStmt = $pdo->prepare('SELECT id, status, approved FROM gdbb_bounty_submissions WHERE bounty_id = ? AND user_id = ? LIMIT 1');
$existingSubmissionStmt->execute([$bountyId, $userId]);
$existingSubmission = $existingSubmissionStmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_proof'])) {
    $proofDescription = trim((string)($_POST['proof_description'] ?? ''));
    $proofDescription = strip_tags($proofDescription);
    $proofDescription = preg_replace('/\s+/', ' ', $proofDescription);
    $proofDescription = mb_substr($proofDescription, 0, 1000);

    if ($proofDescription === '') {
        $message = 'Please enter a proof description.';
    } elseif ($existingSubmission) {
        $message = 'You already submitted proof for this bounty.';
    } else {
        $proofPhotoPath = null;
        if (!empty($_FILES['proof_photo']['name'])) {
            $uploadDir = dirname(__DIR__) . '/public/uploads/proof_images';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $extension = strtolower(pathinfo($_FILES['proof_photo']['name'], PATHINFO_EXTENSION));
            $proofFilename = 'proof-' . $userId . '-' . time() . '.' . $extension;
            $proofTargetPath = $uploadDir . '/' . $proofFilename;
            if (!in_array($extension, $allowedExtensions, true)) {
                $message = 'Only JPG, PNG, GIF, and WEBP images are allowed.';
            } elseif ($_FILES['proof_photo']['size'] > 2 * 1024 * 1024) {
                $message = 'Proof image must be 2MB or smaller.';
            } elseif (is_uploaded_file($_FILES['proof_photo']['tmp_name']) && move_uploaded_file($_FILES['proof_photo']['tmp_name'], $proofTargetPath)) {
                $proofPhotoPath = 'uploads/proof_images/' . $proofFilename;
            } else {
                $message = 'Could not upload proof image.';
            }
        }

        if ($message === '') {
            $insertSubmissionStmt = $pdo->prepare('INSERT INTO gdbb_bounty_submissions (bounty_id, user_id, proof_text, image_path, proof_photo, proof_description, status) VALUES (?, ?, ?, ?, ?, ?, "pending")');
            $insertSubmissionStmt->execute([$bountyId, $userId, $proofDescription, $proofPhotoPath, $proofPhotoPath, $proofDescription]);

            $updateBountyStatusStmt = $pdo->prepare('UPDATE gdbb_bounties SET status = "in_review" WHERE id = ?');
            $updateBountyStatusStmt->execute([$bountyId]);

            header('Location: bounty.php?message=' . rawurlencode('Proof submitted. An admin will review it soon.'));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bounty Detail</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>
<body class="bounty-detail-page">
    <header class="topbar">
        <a href="profile.php" class="brand-mark" aria-label="Go to profile">
            <img src="<?= htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Profile picture">
        </a>
        <nav class="topnav">
            <a href="leaderboard.php">leaderbord</a>
            <a href="bounty.php">bounty</a>
            <a href="guilds.php">guilds</a>
            <a href="logout.php">logout</a>
        </nav>
    </header>

    <main class="detail-shell">
        <h1>bounty</h1>
        <h2><?= htmlspecialchars((string)($bounty['title'] ?? 'Untitled bounty'), ENT_QUOTES, 'UTF-8') ?></h2>

        <section class="detail-content">
            <div class="detail-details">
                <div>Description: <?= htmlspecialchars((string)($bounty['description'] ?? 'No description provided.'), ENT_QUOTES, 'UTF-8') ?></div>
                <div>XP: <?= (int)($bounty['xp_reward'] ?? 0) ?></div>
                <div>DIFFICULTY: <?= htmlspecialchars(strtoupper((string)($bounty['difficulty'] ?? 'hard')), ENT_QUOTES, 'UTF-8') ?></div>
                <div>status: <?= htmlspecialchars((string)($bounty['status'] ?? 'open'), ENT_QUOTES, 'UTF-8') ?></div>
                <div>do date <?= htmlspecialchars((string)($bounty['expire_date'] ?? 'TBA'), ENT_QUOTES, 'UTF-8') ?></div>
            </div>

            <div class="detail-evidence">
                <div class="detail-evidence-label">Physical evidence</div>
                <div class="detail-image-placeholder" id="proof-preview-wrapper">
                    <img id="proof-preview-image" class="detail-placeholder-image" src="images/placeholder.png" alt="Placeholder image">
                </div>
                <button class="detail-upload-button" type="button" onclick="document.getElementById('proof_photo_input').click()">submit image</button>
                <form method="post" enctype="multipart/form-data" class="detail-input-area">
                    <label for="proof_description">imput text</label>
                    <textarea id="proof_description" name="proof_description" placeholder="Describe your proof here..."></textarea>
                    <input id="proof_photo_input" class="detail-file-input" type="file" name="proof_photo" accept="image/png,image/jpeg,image/gif,image/webp">
                    <button class="detail-submit-button" type="submit" name="submit_proof" value="1">submit proof</button>
                </form>
                <?php if ($message !== ''): ?>
                    <p class="detail-status"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <?php if ($existingSubmission): ?>
                    <p class="detail-status">Current status: <?= htmlspecialchars((string)($existingSubmission['status'] ?? 'pending'), ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
            </div>
        </section>
    </main>
    <script>
        const proofInput = document.getElementById('proof_photo_input');
        const previewImage = document.getElementById('proof-preview-image');
        if (proofInput && previewImage) {
            proofInput.addEventListener('change', function () {
                const file = this.files && this.files[0];
                if (!file) {
                    return;
                }
                const reader = new FileReader();
                reader.onload = function (event) {
                    previewImage.src = event.target.result;
                    previewImage.classList.remove('detail-placeholder-image');
                    previewImage.classList.add('detail-preview-image');
                };
                reader.readAsDataURL(file);
            });
        }
    </script>
</body>
</html>
