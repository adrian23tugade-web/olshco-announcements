<?php
require_once '../includes/config.php';

// Require login
if (!isLoggedIn()) {
    header('Location: /login');
    exit;
}

$user = getCurrentUser($pdo);
$success = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format';
        } else {
            // Check if email exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user['id']]);
            
            if ($stmt->fetch()) {
                $error = 'Email already used by another account';
            } else {
                // Update profile
                $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?");
                $stmt->execute([$first_name, $last_name, $email, $user['id']]);
                
                $success = 'Profile updated successfully';
                $user = getCurrentUser($pdo);
            }
        }
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        
        if (!password_verify($current, $user['password'])) {
            $error = 'Current password is incorrect';
        } elseif (strlen($new) < 8) {
            $error = 'New password must be at least 8 characters';
        } elseif (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/", $new)) {
            $error = 'New password must contain uppercase, lowercase, and number';
        } elseif ($new !== $confirm) {
            $error = 'New passwords do not match';
        } else {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $user['id']]);
            
            $success = 'Password changed successfully';
        }
    }
}
?>
<?php require_once '../includes/header.php'; ?>

<div class="row">
    <div class="col-md-3">
        <!-- Profile Sidebar -->
        <div class="card mb-4">
            <div class="card-body text-center">
                <div class="mb-3">
                    <div style="width: 100px; height: 100px; border-radius: 50%; background-color: #800000; 
                                color: white; display: flex; align-items: center; justify-content: center; 
                                font-size: 40px; font-weight: bold; margin: 0 auto;">
                        <?= strtoupper(substr($user['first_name'] ?? $user['username'], 0, 1)) ?>
                    </div>
                </div>
                <h5><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h5>
                <p class="text-muted mb-2">@<?= htmlspecialchars($user['username']) ?></p>
                <span class="badge" style="background-color: #800000; color: white;">
                    <?= ucfirst($user['user_type']) ?>
                </span>
            </div>
            <div class="card-footer text-center text-muted">
                <small>Member since <?= date('F d, Y', strtotime($user['created_at'])) ?></small>
            </div>
        </div>
    </div>
    
    <div class="col-md-9">
        <!-- Profile Update Form -->
        <div class="card mb-4">
            <div class="card-header" style="background-color: #800000; color: white;">
                <i class="fas fa-user-edit me-2"></i>Edit Profile
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i><?= $success ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i><?= $error ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name"
                                   value="<?= htmlspecialchars($user['first_name'] ?? '') ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name"
                                   value="<?= htmlspecialchars($user['last_name'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" readonly disabled>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email"
                               value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn" style="background-color: #800000; color: white;">
                        <i class="fas fa-save me-2"></i>Update Profile
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Change Password Form -->
        <div class="card">
            <div class="card-header" style="background-color: #800000; color: white;">
                <i class="fas fa-key me-2"></i>Change Password
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                        <div class="form-text">At least 8 characters with 1 uppercase, 1 lowercase, and 1 number</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" name="change_password" class="btn" style="background-color: #ffc107; color: #000;">
                        <i class="fas fa-key me-2"></i>Change Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Add this right before require_once '../includes/footer.php' -->
<?php require_once '../includes/chatbot.php'; ?>
<?php require_once '../includes/footer.php'; ?>



