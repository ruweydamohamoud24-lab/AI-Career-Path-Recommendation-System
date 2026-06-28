<?php
require_once 'db_connect.php';
session_start();

$user_id = $_SESSION['user_id'] ?? 1;

echo "<h2>Checking History for User ID: $user_id</h2>";

// Check if table exists
$tables = $pdo->query("SHOW TABLES LIKE 'user_history'");
if ($tables->rowCount() == 0) {
    echo "❌ Table 'user_history' does NOT exist!<br>";
    echo "Run: CREATE TABLE user_history...<br>";
} else {
    echo "✅ Table 'user_history' exists<br>";
}

// Get history
$stmt = $pdo->prepare("SELECT * FROM user_history WHERE user_id = ? ORDER BY recommended_at DESC");
$stmt->execute([$user_id]);
$history = $stmt->fetchAll();

echo "<h3>History Records: " . count($history) . "</h3>";

if (count($history) > 0) {
    echo "<table border='1' cellpadding='8'>";
    echo "<tr><th>ID</th><th>Career</th><th>Score</th><th>Date</th></tr>";
    foreach ($history as $item) {
        echo "<tr>";
        echo "<td>{$item['id']}</td>";
        echo "<td>{$item['recommended_career']}</td>";
        echo "<td>{$item['match_score']}%</td>";
        echo "<td>{$item['recommended_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ No history found!<br>";
    echo "Try inserting test data or getting a new recommendation.<br>";
}

// Check users table for latest recommendation
$user = $pdo->prepare("SELECT latest_career, latest_match_score FROM users WHERE id = ?");
$user->execute([$user_id]);
$userData = $user->fetch();

echo "<h3>Latest Recommendation from Users Table:</h3>";
if ($userData && $userData['latest_career']) {
    echo "Career: " . $userData['latest_career'] . "<br>";
    echo "Score: " . $userData['latest_match_score'] . "%<br>";
} else {
    echo "❌ No latest recommendation found!<br>";
}
?>