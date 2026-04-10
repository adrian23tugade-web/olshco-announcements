<?php
/**
 * Authentication and user management functions
 */

/**
 * Register a new user
 */
function registerUser($pdo, $userData) {
    try {
        // Validate input
        $errors = [];
        
        if (empty($userData['username'])) {
            $errors[] = "Username is required";
        } elseif (strlen($userData['username']) < 3) {
            $errors[] = "Username must be at least 3 characters";
        }
        
        if (empty($userData['email'])) {
            $errors[] = "Email is required";
        } elseif (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }
        
        if (empty($userData['password'])) {
            $errors[] = "Password is required";
        } elseif (strlen($userData['password']) < 8) {
            $errors[] = "Password must be at least 8 characters";
        } elseif (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/", $userData['password'])) {
            $errors[] = "Password must contain at least one uppercase letter, one lowercase letter, and one number";
        }
        
        if ($userData['password'] !== $userData['confirm_password']) {
            $errors[] = "Passwords do not match";
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Check if username exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$userData['username']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'errors' => ['Username already taken']];
        }
        
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$userData['email']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'errors' => ['Email already registered']];
        }
        
        // Hash password
        $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
        
        // Insert user
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password, first_name, last_name, user_type) 
            VALUES (?, ?, ?, ?, ?, 'user')
        ");
        
        $stmt->execute([
            $userData['username'],
            $userData['email'],
            $hashedPassword,
            $userData['first_name'] ?? null,
            $userData['last_name'] ?? null
        ]);
        
        $userId = $pdo->lastInsertId();
        
        // Log activity
        logActivity($pdo, $userId, 'register', 'User registered successfully');
        
        return ['success' => true, 'user_id' => $userId];
        
    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        return ['success' => false, 'errors' => ['Registration failed. Please try again.']];
    }
}

/**
 * Login user
 */
function loginUser($pdo, $username, $password, $remember = false) {
    try {
        // Find user by username or email
        $stmt = $pdo->prepare("
            SELECT * FROM users 
            WHERE (username = ? OR email = ?) AND status = 'active'
        ");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'error' => 'Invalid username or password'];
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'error' => 'Invalid username or password'];
        }
        
        // Check if password needs rehash
        if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$newHash, $user['id']]);
        }
        
        // Update last login
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_type'] = $user['user_type'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        
        // Set remember me cookie if requested
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $expires = time() + (86400 * 30); // 30 days
            
            setcookie('remember_token', $token, $expires, '/', '', false, true);
            
            // Store token in database (you'd need a remember_tokens table)
            // For now, we'll skip this for brevity
        }
        
        // Log activity
        logActivity($pdo, $user['id'], 'login', 'User logged in');
        
        return ['success' => true, 'user' => $user];
        
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Login failed. Please try again.'];
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Get current user data
 */
function getCurrentUser($pdo) {
    if (!isLoggedIn()) {
        return null;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * Check if user has specific role
 */
function hasRole($role) {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === $role;
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return hasRole('admin');
}

/**
 * Check if user is staff or admin
 */
function isStaff() {
    return hasRole('staff') || isAdmin();
}

/**
 * Log user activity
 */
function logActivity($pdo, $userId, $action, $description = '') {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $action, $description, $ip, $userAgent]);
    } catch (Exception $e) {
        error_log("Activity logging error: " . $e->getMessage());
    }
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Create password reset token
 */
function createPasswordResetToken($pdo, $email) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND status = 'active'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return false;
    }
    
    // Generate token
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Save token
    $stmt = $pdo->prepare("
        INSERT INTO password_resets (user_id, token, expires_at) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$user['id'], $token, $expires]);
    
    return $token;
}

/**
 * Send password reset email (simplified - in production use PHPMailer)
 */
function sendPasswordResetEmail($email, $token) {
    $resetLink = APP_URL . "/reset-password.php?token=" . $token;
    
    $subject = "Password Reset Request - " . APP_NAME;
    $message = "Click this link to reset your password: " . $resetLink . "\n\n";
    $message .= "This link will expire in 1 hour.";
    
    // In production, use PHPMailer or a proper mail library
    mail($email, $subject, $message);
    
    return true;
}
?>