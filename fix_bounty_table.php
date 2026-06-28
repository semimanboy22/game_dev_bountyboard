<?php
$root = 'C:\\xampp\\htdocs\\leerjaar-1\\main-projects\\Game-dev bounty board';
require $root . '/src/bootstrap.php';

$pdo->exec("CREATE TABLE IF NOT EXISTS bb_bounty (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bounty_name VARCHAR(45) NOT NULL,
    bounty_xp INT NOT NULL,
    bounty_description VARCHAR(255) DEFAULT NULL,
    cosmetic_id INT DEFAULT NULL,
    BB_users_id INT DEFAULT NULL,
    bounty_disabled TINYINT(1) NOT NULL DEFAULT 0
)");

echo "Bounty table ready.\n";