<?php
require_once __DIR__ . '/../../includes/config.php';

// Require login and admin access
if (!isLoggedIn()) {
    header('Location: /login');
    exit;
}

if (!isAdmin()) {
    header('Location: /dashboard');
    exit;
}

$message = '';
$messageType = '';

// Handle Add User
if (isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $user_type = $_POST['user_type'];
    $status = $_POST['status'];
    
    // Check if username exists
    $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $check->execute([$username]);
    if ($check->rowCount() > 0) {
        $message = "Username already exists!";
        $messageType = "danger";
    } else {
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->rowCount() > 0) {
            $message = "Email already exists!";
            $messageType = "danger";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, first_name, last_name, user_type, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            if ($stmt->execute([$username, $email, $hashed, $first_name, $last_name, $user_type, $status])) {
                $message = "User added successfully!";
                $messageType = "success";
            }
        }
    }
}

// Handle Edit User
if (isset($_POST['edit_user'])) {
    $id = $_POST['user_id'];
    $email = trim($_POST['email']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $user_type = $_POST['user_type'];
    $status = $_POST['status'];
    
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $check->execute([$email, $id]);
    if ($check->rowCount() > 0) {
        $message = "Email already used!";
        $messageType = "danger";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET email = ?, first_name = ?, last_name = ?, user_type = ?, status = ? WHERE id = ?");
        if ($stmt->execute([$email, $first_name, $last_name, $user_type, $status, $id])) {
            $message = "User updated!";
            $messageType = "success";
        }
    }
}

// Handle Reset Password
if (isset($_POST['reset_password'])) {
    $id = $_POST['user_id'];
    $new_password = $_POST['new_password'];
    
    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    if ($stmt->execute([$hashed, $id])) {
        $message = "Password reset to: " . $new_password;
        $messageType = "success";
    }
}

// Handle Delete User
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    if ($id != $_SESSION['user_id']) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $message = "User deleted!";
        $messageType = "success";
    }
}

// Handle Toggle Status
if (isset($_GET['toggle'])) {
    $id = $_GET['toggle'];
    $current = $_GET['status'];
    $new = $current == 'active' ? 'inactive' : 'active';
    
    if ($id != $_SESSION['user_id']) {
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$new, $id]);
    }
}

// Filters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$user_type_filter = isset($_GET['user_type']) ? $_GET['user_type'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build query
$sql = "SELECT * FROM users WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
    $params = array_fill(0, 4, "%$search%");
}

if (!empty($user_type_filter)) {
    $sql .= " AND user_type = ?";
    $params[] = $user_type_filter;
}

if (!empty($status_filter)) {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Get counts
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$active_users = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
$admin_count = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'admin'")->fetchColumn();
$staff_count = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'staff'")->fetchColumn();

$currentUser = getCurrentUser($pdo);
$pageTitle = 'Manage Users';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - OLSHCO Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f8f9fa; overflow-x: hidden; }
        
        .admin-wrapper { display: flex; width: 100%; }
        
        .admin-sidebar {
            width: 280px;
            background: linear-gradient(135deg, #800000 0%, #660000 100%);
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h4 { color: white; margin: 0; font-weight: 700; }
        .sidebar-header p { color: rgba(255,255,255,0.7); margin: 5px 0 0; }
        .sidebar-nav { padding: 20px 0; }
        
        .sidebar-nav .nav-link {
            color: rgba(255,255,255,0.8) !important;
            padding: 12px 25px;
            margin: 3px 0;
            display: flex;
            align-items: center;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .sidebar-nav .nav-link i { width: 25px; margin-right: 10px; font-size: 1.1rem; }
        
        .sidebar-nav .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: white !important;
            border-left-color: #ffd700;
        }
        
        .sidebar-nav .nav-link.active {
            background: rgba(255,255,255,0.15);
            color: white !important;
            border-left-color: #ffd700;
        }
        
        .sidebar-footer {
            padding: 20px 25px;
            border-top: 1px solid rgba(255,255,255,0.1);
            position: absolute;
            bottom: 0;
            width: 100%;
        }
        
        .admin-main {
            margin-left: 280px;
            width: calc(100% - 280px);
            min-height: 100vh;
        }
        
        .admin-topbar {
            background: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 999;
        }
        
        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #800000;
            margin: 0;
        }
        
        .user-dropdown { display: flex; align-items: center; cursor: pointer; }
        
        .user-avatar {
            width: 45px;
            height: 45px;
            background: #800000;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
            margin-right: 10px;
        }
        
        .user-info { text-align: right; }
        .user-info strong { display: block; color: #333; }
        .user-info small { color: #666; }
        
        .admin-content { padding: 30px; }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            border-left: 4px solid #800000;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #800000;
        }
        
        .filter-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .card {
            border: none;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            border-radius: 15px;
            overflow: hidden;
        }
        
        .card-header {
            background: #800000;
            color: white;
            padding: 15px 20px;
            font-weight: 600;
            border: none;
        }
        
        .table { margin: 0; }
        
        .table th {
            background: #f8f9fa;
            color: #333;
            font-weight: 600;
            border-bottom: 2px solid #800000;
            padding: 12px;
        }
        
        .table td {
            padding: 12px;
            vertical-align: middle;
        }
        
        .user-avatar-small {
            width: 40px;
            height: 40px;
            background: #800000;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .btn-action {
            padding: 5px 10px;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .btn-action:hover { transform: scale(1.05); }
        
        @media (max-width: 768px) {
            .admin-sidebar { width: 80px; }
            .sidebar-header h4, .sidebar-header p, .sidebar-nav .nav-link span { display: none; }
            .sidebar-nav .nav-link i { margin: 0; font-size: 1.3rem; }
            .sidebar-nav .nav-link { justify-content: center; padding: 15px; }
            .admin-main { margin-left: 80px; width: calc(100% - 80px); }
            .sidebar-footer { display: none; }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <div class="admin-sidebar">
            <div class="sidebar-header">
                <h4><i class="fas fa-bullhorn me-2"></i>OLSHCO Admin</h4>
                <p>Announcement System</p>
            </div>
            
            <div class="sidebar-nav">
                <a href="/dashboard" class="nav-link">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="announcements.php" class="nav-link">
                    <i class="fas fa-bullhorn"></i>
                    <span>Announcements</span>
                </a>
                <a href="add.php" class="nav-link">
                    <i class="fas fa-plus-circle"></i>
                    <span>Add New</span>
                </a>
                <?php if (isAdmin()): ?>
                <a href="users.php" class="nav-link active">
                    <i class="fas fa-users"></i>
                    <span>Manage Users</span>
                </a>
                <?php endif; ?>
                <a href="/dashboard" class="nav-link">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Student View</span>
                </a>
                <a href="/" class="nav-link">
                    <i class="fas fa-globe"></i>
                    <span>Public Site</span>
                </a>
            </div>
            
            <div class="sidebar-footer">
                <a href="/logout" class="nav-link" style="padding: 12px 0;">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="admin-main">
            <!-- Top Bar -->
            <div class="admin-topbar">
                <h1 class="page-title">
                    <i class="fas fa-users me-2" style="color: #800000;"></i>
                    Manage Users
                </h1>
                
                <div class="dropdown">
                    <div class="user-dropdown" data-bs-toggle="dropdown">
                        <div class="user-avatar">
                            <?= strtoupper(substr($currentUser['first_name'] ?? $currentUser['username'], 0, 1)) ?>
                        </div>
                        <div class="user-info">
                            <strong><?= htmlspecialchars($currentUser['first_name'] ?? $currentUser['username']) ?></strong>
                            <small><?= ucfirst($currentUser['user_type']) ?></small>
                        </div>
                        <i class="fas fa-chevron-down ms-2" style="color: #666;"></i>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="/profile"><i class="fas fa-user me-2"></i>Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/logout"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
            
            <!-- Content -->
            <div class="admin-content">
                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                        <i class="fas fa-<?= $messageType == 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                        <?= $message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted">Total Users</small>
                                    <div class="stat-number"><?= $total_users ?></div>
                                </div>
                                <i class="fas fa-users fa-2x" style="color: #800000; opacity: 0.3;"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted">Active Users</small>
                                    <div class="stat-number text-success"><?= $active_users ?></div>
                                </div>
                                <i class="fas fa-user-check fa-2x" style="color: #28a745; opacity: 0.3;"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted">Administrators</small>
                                    <div class="stat-number text-danger"><?= $admin_count ?></div>
                                </div>
                                <i class="fas fa-user-shield fa-2x" style="color: #dc3545; opacity: 0.3;"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted">Staff</small>
                                    <div class="stat-number text-info"><?= $staff_count ?></div>
                                </div>
                                <i class="fas fa-user-tie fa-2x" style="color: #17a2b8; opacity: 0.3;"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filter Card -->
                <div class="filter-card">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" placeholder="Search users..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-3">
                            <select name="user_type" class="form-select">
                                <option value="">All Types</option>
                                <option value="admin" <?= $user_type_filter == 'admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="staff" <?= $user_type_filter == 'staff' ? 'selected' : '' ?>>Staff</option>
                                <option value="user" <?= $user_type_filter == 'user' ? 'selected' : '' ?>>User</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="active" <?= $status_filter == 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $status_filter == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-maroon w-100">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Actions Bar -->
                <div class="mb-3">
                    <button class="btn btn-maroon" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="fas fa-user-plus me-1"></i>Add New User
                    </button>
                    <a href="users.php" class="btn btn-outline-secondary">
                        <i class="fas fa-sync-alt me-1"></i>Refresh
                    </a>
                </div>
                
                <!-- Users Table -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-list me-2"></i>User List
                        <span class="badge bg-light text-dark ms-2"><?= count($users) ?> users</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Contact</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Last Login</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($users) > 0): ?>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td>#<?= $user['id'] ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="user-avatar-small me-2">
                                                            <?= strtoupper(substr($user['first_name'] ?? $user['username'], 0, 1)) ?>
                                                        </div>
                                                        <div>
                                                            <strong><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></strong>
                                                            <br>
                                                            <small class="text-muted">@<?= htmlspecialchars($user['username']) ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <i class="fas fa-envelope me-1"></i> <?= htmlspecialchars($user['email']) ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $typeClass = $user['user_type'] == 'admin' ? 'danger' : ($user['user_type'] == 'staff' ? 'info' : 'secondary');
                                                    ?>
                                                    <span class="badge bg-<?= $typeClass ?>"><?= ucfirst($user['user_type']) ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?= $user['status'] == 'active' ? 'success' : 'warning' ?>">
                                                        <?= ucfirst($user['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?= $user['last_login'] ? date('M d, Y', strtotime($user['last_login'])) : '<span class="text-muted">Never</span>' ?>
                                                </td>
                                                <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button class="btn btn-sm btn-warning btn-action" onclick='editUser(<?= json_encode($user) ?>)' title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-info btn-action" onclick='resetPassword(<?= $user['id'] ?>, "<?= htmlspecialchars($user['username']) ?>")' title="Reset Password">
                                                            <i class="fas fa-key"></i>
                                                        </button>
                                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                            <a href="?toggle=<?= $user['id'] ?>&status=<?= $user['status'] ?>" class="btn btn-sm btn-<?= $user['status'] == 'active' ? 'warning' : 'success' ?> btn-action" title="<?= $user['status'] == 'active' ? 'Deactivate' : 'Activate' ?>">
                                                                <i class="fas fa-<?= $user['status'] == 'active' ? 'pause' : 'play' ?>"></i>
                                                            </a>
                                                            <a href="?delete=<?= $user['id'] ?>" class="btn btn-sm btn-danger btn-action" title="Delete" onclick="return confirm('Delete user <?= htmlspecialchars($user['username']) ?>?')">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-5 text-muted">
                                                <i class="fas fa-users fa-3x mb-3"></i>
                                                <p>No users found</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background: #800000; color: white;">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add New User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" name="first_name" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" name="last_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="text" name="password" class="form-control" value="User@123" required>
                            <small class="text-muted">Default: User@123</small>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">User Type</label>
                                <select name="user_type" class="form-select">
                                    <option value="user">User</option>
                                    <option value="staff">Staff</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_user" class="btn btn-maroon">
                            <i class="fas fa-save me-1"></i>Save User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background: #800000; color: white;">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" name="first_name" id="edit_first_name" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" name="last_name" id="edit_last_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" id="edit_username" class="form-control" readonly disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="edit_email" class="form-control" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">User Type</label>
                                <select name="user_type" id="edit_user_type" class="form-select">
                                    <option value="user">User</option>
                                    <option value="staff">Staff</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" id="edit_status" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_user" class="btn btn-maroon">
                            <i class="fas fa-save me-1"></i>Update User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Reset Password Modal -->
    <div class="modal fade" id="resetPasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background: #800000; color: white;">
                    <h5 class="modal-title"><i class="fas fa-key me-2"></i>Reset Password</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="reset_user_id">
                        <p>Reset password for: <strong id="reset_username"></strong></p>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="text" name="new_password" class="form-control" value="User@123" required>
                            <small class="text-muted">Default: User@123</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="reset_password" class="btn btn-warning">
                            <i class="fas fa-key me-1"></i>Reset Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function editUser(user) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_first_name').value = user.first_name || '';
            document.getElementById('edit_last_name').value = user.last_name || '';
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_user_type').value = user.user_type;
            document.getElementById('edit_status').value = user.status;
            
            new bootstrap.Modal(document.getElementById('editUserModal')).show();
        }
        
        function resetPassword(id, username) {
            document.getElementById('reset_user_id').value = id;
            document.getElementById('reset_username').innerText = username;
            
            new bootstrap.Modal(document.getElementById('resetPasswordModal')).show();
        }
    </script>
</body>
</html>






