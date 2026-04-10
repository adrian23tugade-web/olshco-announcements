<?php
require_once '../../includes/config.php';

// Require login and admin/staff access
if (!isLoggedIn()) {
    header('Location: /login');
    exit;
}

if (!isStaff()) {
    header('Location: /dashboard');
    exit;
}

// Get statistics
$stats = [];

// Total announcements
$stmt = $pdo->query("SELECT COUNT(*) FROM announcements");
$stats['total_announcements'] = $stmt->fetchColumn();

// Active announcements
$stmt = $pdo->query("SELECT COUNT(*) FROM announcements WHERE status = 'active' AND (expiration_date IS NULL OR expiration_date >= CURDATE())");
$stats['active_announcements'] = $stmt->fetchColumn();

// Expired announcements
$stmt = $pdo->query("SELECT COUNT(*) FROM announcements WHERE expiration_date IS NOT NULL AND expiration_date < CURDATE()");
$stats['expired_announcements'] = $stmt->fetchColumn();

// Total users
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
$stats['total_users'] = $stmt->fetchColumn();

// Active users
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'");
$stats['active_users'] = $stmt->fetchColumn();

// Admin count
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'admin'");
$stats['admin_count'] = $stmt->fetchColumn();

// Staff count
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'staff'");
$stats['staff_count'] = $stmt->fetchColumn();

// Recent announcements
$stmt = $pdo->query("
    SELECT a.*, u.username, u.first_name, u.last_name 
    FROM announcements a 
    LEFT JOIN users u ON a.created_by = u.id 
    ORDER BY a.created_at DESC 
    LIMIT 10
");
$recent = $stmt->fetchAll();

// Recent users
$stmt = $pdo->query("
    SELECT * FROM users 
    ORDER BY created_at DESC 
    LIMIT 5
");
$recentUsers = $stmt->fetchAll();

// Urgent announcements
$stmt = $pdo->query("
    SELECT * FROM announcements 
    WHERE priority = 'urgent' AND status = 'active' 
    AND (expiration_date IS NULL OR expiration_date >= CURDATE())
    ORDER BY created_at DESC 
    LIMIT 5
");
$urgentAnnouncements = $stmt->fetchAll();

$currentUser = getCurrentUser($pdo);
$pageTitle = 'Admin Dashboard';
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
        * {
            font-family: 'Inter', sans-serif;
        }
        
        body {
            background: #f8f9fa;
            overflow-x: hidden;
        }
        
        .admin-wrapper {
            display: flex;
            width: 100%;
        }
        
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
        
        .sidebar-header h4 {
            color: white;
            margin: 0;
            font-weight: 700;
        }
        
        .sidebar-header p {
            color: rgba(255,255,255,0.7);
            margin: 5px 0 0;
        }
        
        .sidebar-nav {
            padding: 20px 0;
        }
        
        .sidebar-nav .nav-link {
            color: rgba(255,255,255,0.8) !important;
            padding: 12px 25px;
            margin: 3px 0;
            display: flex;
            align-items: center;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .sidebar-nav .nav-link i {
            width: 25px;
            margin-right: 10px;
            font-size: 1.1rem;
        }
        
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
        
        .user-dropdown {
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        
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
        
        .user-info {
            text-align: right;
        }
        
        .user-info strong {
            display: block;
            color: #333;
        }
        
        .user-info small {
            color: #666;
        }
        
        .admin-content {
            padding: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s;
            height: 100%;
            border: 1px solid #eee;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            background: rgba(128,0,0,0.1);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }
        
        .stat-icon i {
            font-size: 28px;
            color: #800000;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #800000;
            line-height: 1;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
            margin-top: 5px;
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
        
        .table {
            margin: 0;
        }
        
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
        
        .btn-action:hover {
            transform: scale(1.05);
        }
        
        .quick-action-btn {
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            transition: all 0.3s;
            text-decoration: none;
            display: block;
        }
        
        .quick-action-btn:hover {
            background: #800000;
            color: white;
            transform: translateY(-3px);
        }
        
        .quick-action-btn i {
            font-size: 2rem;
            color: #800000;
            margin-bottom: 10px;
        }
        
        .quick-action-btn:hover i,
        .quick-action-btn:hover span {
            color: white;
        }
        
        .quick-action-btn span {
            display: block;
            color: #333;
            font-weight: 500;
        }
        
        @media (max-width: 768px) {
            .admin-sidebar {
                width: 80px;
            }
            
            .sidebar-header h4,
            .sidebar-header p,
            .sidebar-nav .nav-link span {
                display: none;
            }
            
            .sidebar-nav .nav-link i {
                margin: 0;
                font-size: 1.3rem;
            }
            
            .sidebar-nav .nav-link {
                justify-content: center;
                padding: 15px;
            }
            
            .admin-main {
                margin-left: 80px;
                width: calc(100% - 80px);
            }
            
            .sidebar-footer {
                display: none;
            }
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
                <a href="/dashboard" class="nav-link active">
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
                <a href="users.php" class="nav-link">
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
                    <i class="fas fa-tachometer-alt me-2" style="color: #800000;"></i>
                    Dashboard Overview
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
                <!-- Welcome Message -->
                <div class="alert" style="background: linear-gradient(135deg, #800000 0%, #a52a2a 100%); color: white; border: none; border-radius: 15px; padding: 20px;">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h4><i class="fas fa-hand-wave me-2"></i>Welcome back, <?= htmlspecialchars($currentUser['first_name'] ?? $currentUser['username']) ?>!</h4>
                            <p class="mb-0">You're logged in as <strong><?= ucfirst($currentUser['user_type']) ?></strong>. Today is <?= date('l, F d, Y') ?>.</p>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <span class="badge bg-light text-dark"><?= date('h:i A') ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="row mb-4">
                    <div class="col-md-2 col-6 mb-3">
                        <a href="add.php" class="quick-action-btn">
                            <i class="fas fa-plus-circle"></i>
                            <span>Add New</span>
                        </a>
                    </div>
                    <div class="col-md-2 col-6 mb-3">
                        <a href="announcements.php" class="quick-action-btn">
                            <i class="fas fa-list"></i>
                            <span>View All</span>
                        </a>
                    </div>
                    <?php if (isAdmin()): ?>
                    <div class="col-md-2 col-6 mb-3">
                        <a href="users.php" class="quick-action-btn">
                            <i class="fas fa-user-plus"></i>
                            <span>Add User</span>
                        </a>
                    </div>
                    <div class="col-md-2 col-6 mb-3">
                        <a href="users.php" class="quick-action-btn">
                            <i class="fas fa-users-cog"></i>
                            <span>Users</span>
                        </a>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-2 col-6 mb-3">
                        <a href="/dashboard" class="quick-action-btn">
                            <i class="fas fa-eye"></i>
                            <span>Preview</span>
                        </a>
                    </div>
                    <div class="col-md-2 col-6 mb-3">
                        <a href="/logout" class="quick-action-btn">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-bullhorn"></i>
                            </div>
                            <div class="stat-number"><?= $stats['total_announcements'] ?></div>
                            <div class="stat-label">Total Announcements</div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-number" style="color: #28a745;"><?= $stats['active_announcements'] ?></div>
                            <div class="stat-label">Active Announcements</div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-number" style="color: #ffc107;"><?= $stats['expired_announcements'] ?></div>
                            <div class="stat-label">Expired Announcements</div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-number" style="color: #17a2b8;"><?= $stats['total_users'] ?></div>
                            <div class="stat-label">Total Users</div>
                        </div>
                    </div>
                </div>
                
                <?php if (isAdmin()): ?>
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div class="stat-number" style="color: #28a745;"><?= $stats['active_users'] ?></div>
                            <div class="stat-label">Active Users</div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-user-shield"></i>
                            </div>
                            <div class="stat-number" style="color: #dc3545;"><?= $stats['admin_count'] ?></div>
                            <div class="stat-label">Administrators</div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div class="stat-number" style="color: #17a2b8;"><?= $stats['staff_count'] ?></div>
                            <div class="stat-label">Staff Members</div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div class="stat-number" style="color: #6c757d;"><?= $stats['total_users'] - $stats['admin_count'] - $stats['staff_count'] ?></div>
                            <div class="stat-label">Regular Users</div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="row">
                    <!-- Recent Announcements -->
                    <div class="col-lg-8 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-history me-2"></i>Recent Announcements
                                <a href="announcements.php" class="btn btn-sm btn-light float-end">View All</a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Title</th>
                                                <th>Category</th>
                                                <th>Priority</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($recent) > 0): ?>
                                                <?php foreach ($recent as $ann): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?= htmlspecialchars(substr($ann['title'], 0, 30)) ?><?= strlen($ann['title']) > 30 ? '...' : '' ?></strong>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-secondary"><?= $ann['category'] ?></span>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            $priorityClass = $ann['priority'] === 'urgent' ? 'danger' : 
                                                                           ($ann['priority'] === 'high' ? 'warning' : 
                                                                           ($ann['priority'] === 'normal' ? 'info' : 'secondary'));
                                                            ?>
                                                            <span class="badge bg-<?= $priorityClass ?>"><?= ucfirst($ann['priority']) ?></span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-<?= $ann['status'] === 'active' ? 'success' : 'warning' ?>">
                                                                <?= ucfirst($ann['status']) ?>
                                                            </span>
                                                        </td>
                                                        <td><?= date('M d, Y', strtotime($ann['created_at'])) ?></td>
                                                        <td>
                                                            <a href="edit.php?id=<?= $ann['id'] ?>" class="btn btn-sm btn-warning btn-action">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <a href="delete.php?id=<?= $ann['id'] ?>" class="btn btn-sm btn-danger btn-action" onclick="return confirm('Delete this announcement?')">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="6" class="text-center py-4 text-muted">
                                                        <i class="fas fa-bullhorn fa-2x mb-2"></i>
                                                        <p>No announcements yet</p>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right Column -->
                    <div class="col-lg-4">
                        <!-- Urgent Announcements -->
                        <div class="card mb-4">
                            <div class="card-header" style="background: #dc3545;">
                                <i class="fas fa-exclamation-triangle me-2"></i>Urgent Announcements
                            </div>
                            <div class="card-body">
                                <?php if (count($urgentAnnouncements) > 0): ?>
                                    <?php foreach ($urgentAnnouncements as $urgent): ?>
                                        <div class="alert alert-danger mb-3">
                                            <strong><?= htmlspecialchars($urgent['title']) ?></strong>
                                            <p class="small mb-0 mt-1"><?= htmlspecialchars(substr($urgent['content'], 0, 80)) ?>...</p>
                                            <small class="text-muted"><?= date('M d, Y', strtotime($urgent['created_at'])) ?></small>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted text-center py-3">No urgent announcements</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Recent Users -->
                        <?php if (isAdmin()): ?>
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-user-plus me-2"></i>Recent Users
                                <a href="users.php" class="btn btn-sm btn-light float-end">Manage</a>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                                    <?php foreach ($recentUsers as $user): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex align-items-center">
                                                <div class="user-avatar me-3" style="width: 40px; height: 40px; font-size: 1rem;">
                                                    <?= strtoupper(substr($user['first_name'] ?? $user['username'], 0, 1)) ?>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <strong><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></strong>
                                                    <br>
                                                    <small class="text-muted">@<?= htmlspecialchars($user['username']) ?></small>
                                                </div>
                                                <div>
                                                    <span class="badge bg-<?= $user['user_type'] === 'admin' ? 'danger' : ($user['user_type'] === 'staff' ? 'info' : 'secondary') ?>">
                                                        <?= ucfirst($user['user_type']) ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../../assets/js/script.js"></script>
</body>
</html>





