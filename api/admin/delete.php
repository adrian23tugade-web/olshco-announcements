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

if ($id) {
    // Optional: Check if announcement exists
    $stmt = $pdo->prepare("SELECT id FROM announcements WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
        $stmt->execute([$id]);
        
        $_SESSION['flash_message'] = 'Announcement deleted successfully!';
        $_SESSION['flash_type'] = 'success';
    }
}

header('Location: announcements.php?msg=Announcement+deleted+successfully');
exit;
?>






