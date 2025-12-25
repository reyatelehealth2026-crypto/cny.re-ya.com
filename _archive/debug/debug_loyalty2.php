<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Step 1: Start<br>";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "Step 2: Session started<br>";

require_once 'config/config.php';
echo "Step 3: Config loaded<br>";

require_once 'config/database.php';
echo "Step 4: Database loaded<br>";

$db = Database::getInstance()->getConnection();
echo "Step 5: DB connected<br>";

require_once 'classes/LoyaltyPoints.php';
echo "Step 6: LoyaltyPoints loaded<br>";

$currentBotId = $_SESSION['current_bot_id'] ?? null;
echo "Step 7: Bot ID = " . ($currentBotId ?? 'null') . "<br>";

$loyalty = new LoyaltyPoints($db, $currentBotId);
echo "Step 8: LoyaltyPoints created<br>";

$settings = $loyalty->getSettings();
echo "Step 9: Settings loaded<br>";

$summary = $loyalty->getPointsSummary();
echo "Step 10: Summary loaded<br>";

$rewards = $loyalty->getRewards(false);
echo "Step 11: Rewards loaded (" . count($rewards) . ")<br>";

$redemptions = $loyalty->getAllRedemptions(null, 20);
echo "Step 12: Redemptions loaded (" . count($redemptions) . ")<br>";

echo "Step 13: About to load header.php<br>";

// This is where it likely fails
require_once 'includes/header.php';
echo "Step 14: Header loaded<br>";
