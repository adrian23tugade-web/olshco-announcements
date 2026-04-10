<?php
require_once 'includes/config.php';

echo "🔧 OLSHCO LOGIN FIX TOOL<br><br>";

// Check connection
try {
    echo "✅ Database connected successfully<br>";
} catch(Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
}

// Check if users table exists
$tables = $pdo->query("SHOW TABLES LIKE 'users'");
if($tables->rowCount() > 0) {
    echo "✅ Users table exists<br>";
    
    // Count users
    $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "📊 Total users: " . $count . "<br>";
    
    // Show all users
    $users = $pdo->query("SELECT id, username, email, user_type, status FROM users");
    echo "<br>📋 User List:<br>";
    while($user = $users->fetch()) {
        echo "→ {$user['username']} ({$user['email']}) - {$user['user_type']} - {$user['status']}<br>";
    }
    
    // Create admin if none exists
    $admin = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'admin'")->fetchColumn();
    if($admin == 0) {
        echo "<br>⚠️ No admin found! Creating default admin...<br>";
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, user_type, status) VALUES (?, ?, ?, 'admin', 'active')");
        if($stmt->execute(['admin', 'admin@olshco.edu.ph', $password])) {
            echo "✅ Default admin created!<br>";
            echo "Username: admin<br>";
            echo "Password: admin123<br>";
        }
    }
} else {
    echo "❌ Users table missing! Please import database.sql<br>";
}

echo "<br><br><a href='/login'>Go to Login Page</a>";
?>





