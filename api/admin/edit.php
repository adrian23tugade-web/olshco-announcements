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

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM announcements WHERE id = ?");
$stmt->execute([$id]);
$announcement = $stmt->fetch();

if (!$announcement) {
    header('Location: announcements.php');
    exit;
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $category = $_POST['category'];
    $priority = $_POST['priority'];
    $expiration = !empty($_POST['expiration_date']) ? $_POST['expiration_date'] : null;
    $status = $_POST['status'];

    if (empty($title) || empty($content)) {
        $message = 'Title and content are required!';
        $messageType = 'danger';
    } else {
        $stmt = $pdo->prepare("
            UPDATE announcements 
            SET title=?, content=?, category=?, priority=?, expiration_date=?, status=?, updated_at=NOW(), updated_by=?
            WHERE id=?
        ");
        
        if ($stmt->execute([$title, $content, $category, $priority, $expiration, $status, $_SESSION['user_id'], $id])) {
            $message = 'Announcement updated successfully!';
            $messageType = 'success';
            // Refresh announcement data
            $stmt = $pdo->prepare("SELECT * FROM announcements WHERE id = ?");
            $stmt->execute([$id]);
            $announcement = $stmt->fetch();
        } else {
            $message = 'Error updating announcement!';
            $messageType = 'danger';
        }
    }
}

$currentUser = getCurrentUser($pdo);
$pageTitle = 'Edit Announcement';
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
        
        .form-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            max-width: 800px;
            margin: 0 auto;
        }
        
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        
        .form-label i { color: #800000; width: 20px; }
        .required::after { content: " *"; color: #dc3545; }
        
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
                    <i class="fas fa-edit me-2" style="color: #800000;"></i>
                    Edit Announcement #<?= $announcement['id'] ?>
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
                
                <div class="form-card">
                    <form method="POST" action="">
                        <div class="mb-4">
                            <label for="title" class="form-label required">
                                <i class="fas fa-heading"></i>Title
                            </label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?= htmlspecialchars($announcement['title']) ?>" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="content" class="form-label required">
                                <i class="fas fa-align-left"></i>Content
                            </label>
                            <textarea class="form-control" id="content" name="content" rows="8" required><?= htmlspecialchars($announcement['content']) ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="category" class="form-label">
                                    <i class="fas fa-tag"></i>Category
                                </label>
                                <select class="form-select" id="category" name="category">
                                    <option value="Announcement" <?= $announcement['category'] == 'Announcement' ? 'selected' : '' ?>>Announcement</option>
                                    <option value="Event" <?= $announcement['category'] == 'Event' ? 'selected' : '' ?>>Event</option>
                                    <option value="Bulletin" <?= $announcement['category'] == 'Bulletin' ? 'selected' : '' ?>>Bulletin</option>
                                    <option value="News" <?= $announcement['category'] == 'News' ? 'selected' : '' ?>>News</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="priority" class="form-label">
                                    <i class="fas fa-flag"></i>Priority
                                </label>
                                <select class="form-select" id="priority" name="priority">
                                    <option value="normal" <?= $announcement['priority'] == 'normal' ? 'selected' : '' ?>>Normal</option>
                                    <option value="high" <?= $announcement['priority'] == 'high' ? 'selected' : '' ?>>High</option>
                                    <option value="urgent" <?= $announcement['priority'] == 'urgent' ? 'selected' : '' ?>>Urgent</option>
                                    <option value="low" <?= $announcement['priority'] == 'low' ? 'selected' : '' ?>>Low</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="expiration_date" class="form-label">
                                    <i class="fas fa-calendar-alt"></i>Expiration Date
                                </label>
                                <input type="date" class="form-control" id="expiration_date" name="expiration_date"
                                       value="<?= htmlspecialchars($announcement['expiration_date'] ?? '') ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">
                                    <i class="fas fa-circle"></i>Status
                                </label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active" <?= $announcement['status'] == 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="draft" <?= $announcement['status'] == 'draft' ? 'selected' : '' ?>>Draft</option>
                                    <option value="inactive" <?= $announcement['status'] == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Created:</strong> <?= date('F d, Y h:i A', strtotime($announcement['created_at'])) ?>
                            <?php if (!empty($announcement['updated_at'])): ?>
                                <br><strong>Last Updated:</strong> <?= date('F d, Y h:i A', strtotime($announcement['updated_at'])) ?>
                            <?php endif; ?>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-maroon btn-lg">
                                <i class="fas fa-save me-2"></i>Update Announcement
                            </button>
                            <a href="announcements.php" class="btn btn-outline-secondary btn-lg">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                            <a href="delete.php?id=<?= $announcement['id'] ?>" class="btn btn-outline-danger btn-lg ms-auto" onclick="return confirm('Are you sure you want to delete this announcement?')">
                                <i class="fas fa-trash me-2"></i>Delete
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../../assets/js/script.js"></script>
</body>
</html>






