<?php
require_once __DIR__ . '/../../includes/config.php';

// Require login and admin/staff access
if (!isLoggedIn()) {
    header('Location: /login');
    exit;
}

if (!isStaff()) {
    header('Location: /dashboard');
    exit;
}

// Handle status toggle
if (isset($_GET['toggle'])) {
    $id = $_GET['toggle'];
    $stmt = $pdo->prepare("UPDATE announcements SET status = IF(status='active', 'inactive', 'active') WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: announcements.php?msg=Status updated');
    exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: announcements.php?msg=Announcement deleted');
    exit;
}

// Filter parameters
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$priority_filter = isset($_GET['priority']) ? $_GET['priority'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$sql = "SELECT a.*, u.username, u.first_name, u.last_name 
        FROM announcements a 
        LEFT JOIN users u ON a.created_by = u.id 
        WHERE 1=1";
$params = [];

if (!empty($category_filter)) {
    $sql .= " AND a.category = ?";
    $params[] = $category_filter;
}

if (!empty($status_filter)) {
    $sql .= " AND a.status = ?";
    $params[] = $status_filter;
}

if (!empty($priority_filter)) {
    $sql .= " AND a.priority = ?";
    $params[] = $priority_filter;
}

if (!empty($search)) {
    $sql .= " AND (a.title LIKE ? OR a.content LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY 
            CASE a.priority 
                WHEN 'urgent' THEN 1 
                WHEN 'high' THEN 2 
                WHEN 'normal' THEN 3 
                WHEN 'low' THEN 4 
            END, 
            a.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$announcements = $stmt->fetchAll();

// Get counts for filters
$totalAll = $pdo->query("SELECT COUNT(*) FROM announcements")->fetchColumn();
$totalActive = $pdo->query("SELECT COUNT(*) FROM announcements WHERE status = 'active'")->fetchColumn();
$totalInactive = $pdo->query("SELECT COUNT(*) FROM announcements WHERE status = 'inactive'")->fetchColumn();
$totalUrgent = $pdo->query("SELECT COUNT(*) FROM announcements WHERE priority = 'urgent'")->fetchColumn();

$currentUser = getCurrentUser($pdo);
$pageTitle = 'Manage Announcements';
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
        
        .sidebar-header h4 {
            color: white;
            margin: 0;
            font-weight: 700;
        }
        
        .sidebar-header p {
            color: rgba(255,255,255,0.7);
            margin: 5px 0 0;
        }
        
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
        
        .user-info { text-align: right; }
        .user-info strong { display: block; color: #333; }
        .user-info small { color: #666; }
        
        .admin-content { padding: 30px; }
        
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
                <a href="announcements.php" class="nav-link active">
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
                    <i class="fas fa-bullhorn me-2" style="color: #800000;"></i>
                    Manage Announcements
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
                <?php if (isset($_GET['msg'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($_GET['msg']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Filter Card -->
                <div class="filter-card">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label"><i class="fas fa-search me-1"></i>Search</label>
                            <input type="text" name="search" class="form-control" placeholder="Search title or content..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label"><i class="fas fa-tag me-1"></i>Category</label>
                            <select name="category" class="form-select">
                                <option value="">All Categories</option>
                                <option value="Announcement" <?= $category_filter == 'Announcement' ? 'selected' : '' ?>>Announcement</option>
                                <option value="Event" <?= $category_filter == 'Event' ? 'selected' : '' ?>>Event</option>
                                <option value="Bulletin" <?= $category_filter == 'Bulletin' ? 'selected' : '' ?>>Bulletin</option>
                                <option value="News" <?= $category_filter == 'News' ? 'selected' : '' ?>>News</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label"><i class="fas fa-flag me-1"></i>Priority</label>
                            <select name="priority" class="form-select">
                                <option value="">All Priorities</option>
                                <option value="urgent" <?= $priority_filter == 'urgent' ? 'selected' : '' ?>>Urgent</option>
                                <option value="high" <?= $priority_filter == 'high' ? 'selected' : '' ?>>High</option>
                                <option value="normal" <?= $priority_filter == 'normal' ? 'selected' : '' ?>>Normal</option>
                                <option value="low" <?= $priority_filter == 'low' ? 'selected' : '' ?>>Low</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label"><i class="fas fa-circle me-1"></i>Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="active" <?= $status_filter == 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $status_filter == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                <option value="draft" <?= $status_filter == 'draft' ? 'selected' : '' ?>>Draft</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-maroon">
                                    <i class="fas fa-filter me-1"></i>Apply Filters
                                </button>
                                <a href="announcements.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i>Clear
                                </a>
                                <a href="add.php" class="btn btn-success float-end">
                                    <i class="fas fa-plus me-1"></i>Add New
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Stats Badges -->
                <div class="mb-3">
                    <span class="badge bg-secondary me-2">Total: <?= $totalAll ?></span>
                    <span class="badge bg-success me-2">Active: <?= $totalActive ?></span>
                    <span class="badge bg-warning me-2">Inactive: <?= $totalInactive ?></span>
                    <span class="badge bg-danger me-2">Urgent: <?= $totalUrgent ?></span>
                </div>
                
                <!-- Announcements Table -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-list me-2"></i>All Announcements
                        <span class="badge bg-light text-dark ms-2"><?= count($announcements) ?> results</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Expiration</th>
                                        <th>Posted By</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($announcements) > 0): ?>
                                        <?php foreach ($announcements as $ann): ?>
                                            <tr>
                                                <td>#<?= $ann['id'] ?></td>
                                                <td>
                                                    <strong><?= htmlspecialchars(substr($ann['title'], 0, 40)) ?><?= strlen($ann['title']) > 40 ? '...' : '' ?></strong>
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
                                                    <span class="badge bg-<?= $ann['status'] === 'active' ? 'success' : ($ann['status'] === 'draft' ? 'secondary' : 'warning') ?>">
                                                        <?= ucfirst($ann['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?= $ann['expiration_date'] ? date('M d, Y', strtotime($ann['expiration_date'])) : '<span class="text-muted">Never</span>' ?>
                                                </td>
                                                <td><?= htmlspecialchars($ann['first_name'] ?? $ann['username'] ?? 'Admin') ?></td>
                                                <td><?= date('M d, Y', strtotime($ann['created_at'])) ?></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="edit.php?id=<?= $ann['id'] ?>" class="btn btn-sm btn-warning btn-action" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="?toggle=<?= $ann['id'] ?>" class="btn btn-sm btn-info btn-action" title="<?= $ann['status'] === 'active' ? 'Deactivate' : 'Activate' ?>">
                                                            <i class="fas fa-<?= $ann['status'] === 'active' ? 'pause' : 'play' ?>"></i>
                                                        </a>
                                                        <a href="?delete=<?= $ann['id'] ?>" class="btn btn-sm btn-danger btn-action" title="Delete" onclick="return confirm('Are you sure you want to delete this announcement?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center py-5 text-muted">
                                                <i class="fas fa-bullhorn fa-3x mb-3"></i>
                                                <p>No announcements found</p>
                                                <a href="add.php" class="btn btn-maroon">
                                                    <i class="fas fa-plus me-1"></i>Create First Announcement
                                                </a>
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../../assets/js/script.js"></script>
</body>
</html>






