<?php
require_once __DIR__ . '/../includes/config.php';

echo "<h1>Admin Account Setup</h1>";

// Delete existing admin if any
$pdo->exec("DELETE FROM users WHERE username = 'admin'");

// Create new admin
$username = 'admin';
$email = 'admin@olshco.edu.ph';
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

$sql = "INSERT INTO users (username, email, password, first_name, last_name, user_type, status, created_at) 
        VALUES (?, ?, ?, 'System', 'Administrator', 'admin', 'active', NOW())";

$stmt = $pdo->prepare($sql);
$stmt->execute([$username, $email, $hash]);

echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
echo "<h3 style='color: #155724;'>✅ Admin Account Created!</h3>";
echo "<p><strong>Username:</strong> admin</p>";
echo "<p><strong>Password:</strong> admin123</p>";
echo "</div>";

echo "<a href='/login' style='background: #800000; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login</a>";
?>




