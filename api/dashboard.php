<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/header.php';

// Require login - redirect to login if not logged in
if (!isLoggedIn()) {
    $_SESSION['flash_message'] = 'Please login to access your dashboard';
    $_SESSION['flash_type'] = 'warning';
    header('Location: /login');
    exit;
}

// Get current user
$user = getCurrentUser($pdo);
$userName = $user ? ($user['first_name'] ?: $user['username']) : 'Student';
$userType = $user ? $user['user_type'] : 'user';

// Get all active announcements
$sql = "SELECT a.*, u.username, u.first_name, u.last_name 
        FROM announcements a 
        LEFT JOIN users u ON a.created_by = u.id 
        WHERE a.status = 'active' 
        AND (a.expiration_date IS NULL OR a.expiration_date >= CURDATE())
        ORDER BY 
            CASE a.priority 
                WHEN 'urgent' THEN 1 
                WHEN 'high' THEN 2 
                WHEN 'normal' THEN 3 
                WHEN 'low' THEN 4 
            END, 
            a.created_at DESC
        LIMIT 10";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$announcements = $stmt->fetchAll();

// Get urgent/class suspension announcements
$suspensionStmt = $pdo->prepare("SELECT * FROM announcements 
        WHERE status = 'active' 
        AND (priority = 'urgent' OR title LIKE '%suspension%' OR title LIKE '%class cancel%' OR content LIKE '%suspension%')
        AND (expiration_date IS NULL OR expiration_date >= CURDATE())
        ORDER BY created_at DESC LIMIT 3");
$suspensionStmt->execute();
$suspensions = $suspensionStmt->fetchAll();

// Get upcoming events
$eventsStmt = $pdo->prepare("SELECT * FROM announcements 
        WHERE status = 'active' AND category = 'Event'
        AND (expiration_date IS NULL OR expiration_date >= CURDATE())
        ORDER BY created_at ASC LIMIT 5");
$eventsStmt->execute();
$upcomingEvents = $eventsStmt->fetchAll();

// Get today's date for class schedule
$today = date('l, F d, Y');
$currentTime = date('h:i A');

// Class schedule data
$classSchedule = [
    ['time' => '8:00 AM - 9:30 AM', 'subject' => 'Information Technology', 'room' => 'IT Lab 101', 'instructor' => 'Prof. Santos'],
    ['time' => '9:30 AM - 10:00 AM', 'subject' => 'Break', 'room' => '-', 'instructor' => '-'],
    ['time' => '10:00 AM - 11:30 AM', 'subject' => 'Web Development', 'room' => 'ComLab 202', 'instructor' => 'Prof. Reyes'],
    ['time' => '11:30 AM - 1:00 PM', 'subject' => 'Lunch Break', 'room' => '-', 'instructor' => '-'],
    ['time' => '1:00 PM - 2:30 PM', 'subject' => 'Database Management', 'room' => 'ComLab 203', 'instructor' => 'Prof. Cruz'],
    ['time' => '2:30 PM - 4:00 PM', 'subject' => 'Self Study / Consultation', 'room' => 'Library', 'instructor' => '-'],
];

// Check if there's any class suspension
$hasSuspension = count($suspensions) > 0;
$suspensionMessage = $hasSuspension ? $suspensions[0]['title'] . ' - ' . substr($suspensions[0]['content'], 0, 150) : '';

// Get counts
$totalAnnouncements = $pdo->query("SELECT COUNT(*) FROM announcements WHERE status = 'active'")->fetchColumn();
$totalEvents = $pdo->query("SELECT COUNT(*) FROM announcements WHERE category = 'Event' AND status = 'active'")->fetchColumn();
if (!$totalEvents) $totalEvents = 0;
?>

<div class="dashboard-student">
    <div class="container">
        
        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2><i class="fas fa-graduation-cap me-2"></i>Welcome back, <?= htmlspecialchars($userName) ?>!</h2>
                    <p class="mb-0">Today is <strong><?= $today ?></strong> • Current time: <strong><?= $currentTime ?></strong></p>
                    <p class="small mt-2 mb-0 opacity-75">Stay updated with the latest campus announcements and events</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <i class="fas fa-school" style="font-size: 3rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
        
        <!-- Class Suspension Alert -->
        <?php if ($hasSuspension): ?>
        <div class="suspension-alert">
            <div class="row align-items-center">
                <div class="col-auto">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="col">
                    <strong style="color: #dc3545;">⚠️ CLASS SUSPENSION ANNOUNCEMENT</strong>
                    <p class="mb-0 small"><?= htmlspecialchars($suspensionMessage) ?></p>
                </div>
                <div class="col-auto">
                    <span class="badge bg-danger">URGENT</span>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Statistics Row -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <div class="stat-number"><?= $totalAnnouncements ?></div>
                    <div class="stat-label">Total Announcements</div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-number"><?= $totalEvents ?></div>
                    <div class="stat-label">Upcoming Events</div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-number"><?= date('h:i A') ?></div>
                    <div class="stat-label">Current Time</div>
                </div>
            </div>
        </div>
        
        <!-- Main Content Row -->
        <div class="row">
            <!-- Left Column - Announcements -->
            <div class="col-lg-7">
                <!-- Latest Announcements -->
                <div class="section-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="section-title mb-0">
                            <i class="fas fa-newspaper me-2"></i>Latest Announcements
                        </h5>
                        <a href="announcements.php" class="btn-view-all">View All <i class="fas fa-arrow-right ms-1"></i></a>
                    </div>
                    
                    <div class="announcement-list">
                        <?php if (count($announcements) > 0): ?>
                            <?php 
                            $displayCount = min(5, count($announcements));
                            for ($i = 0; $i < $displayCount; $i++): 
                                $ann = $announcements[$i];
                            ?>
                                <div class="announcement-item" data-bs-toggle="modal" data-bs-target="#announcementModal<?= $ann['id'] ?>">
                                    <span class="announcement-badge <?= $ann['priority'] === 'urgent' ? 'badge-urgent' : ($ann['priority'] === 'high' ? 'badge-high' : 'badge-normal') ?>">
                                        <?= $ann['priority'] === 'urgent' ? 'URGENT' : strtoupper(htmlspecialchars($ann['category'])) ?>
                                    </span>
                                    <div class="announcement-title"><?= htmlspecialchars($ann['title']) ?></div>
                                    <p class="small text-muted mb-0"><?= htmlspecialchars(substr($ann['content'], 0, 100)) ?>...</p>
                                    <div class="announcement-meta">
                                        <span><i class="far fa-calendar-alt me-1"></i> <?= date('M d, Y', strtotime($ann['created_at'])) ?></span>
                                        <span><i class="far fa-user me-1"></i> <?= htmlspecialchars($ann['first_name'] ?? $ann['username'] ?? 'Admin') ?></span>
                                    </div>
                                </div>
                                
                                <!-- Announcement Modal -->
                                <div class="modal fade" id="announcementModal<?= $ann['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header-custom">
                                                <h5 class="modal-title"><?= htmlspecialchars($ann['title']) ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body p-4">
                                                <div class="mb-3">
                                                    <span class="badge bg-secondary"><?= htmlspecialchars($ann['category']) ?></span>
                                                    <?php if ($ann['priority'] === 'urgent'): ?>
                                                        <span class="badge bg-danger">URGENT</span>
                                                    <?php elseif ($ann['priority'] === 'high'): ?>
                                                        <span class="badge bg-warning text-dark">HIGH PRIORITY</span>
                                                    <?php endif; ?>
                                                    <small class="text-muted ms-2">
                                                        <i class="far fa-calendar-alt"></i> <?= date('F d, Y h:i A', strtotime($ann['created_at'])) ?>
                                                    </small>
                                                </div>
                                                <hr>
                                                <div><?= nl2br(htmlspecialchars($ann['content'])) ?></div>
                                                <hr>
                                                <small class="text-muted">
                                                    <i class="far fa-user"></i> Posted by: <?= htmlspecialchars($ann['first_name'] ?? $ann['username'] ?? 'Administrator') ?>
                                                </small>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        <?php else: ?>
                            <p class="text-muted text-center py-4">No announcements available</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Today's Schedule / Class Schedule -->
                <div class="section-card">
                    <h5 class="section-title">
                        <i class="fas fa-chalkboard-user me-2"></i>Today's Class Schedule
                    </h5>
                    
                    <div class="schedule-table">
                        <?php foreach ($classSchedule as $class): ?>
                            <div class="schedule-row">
                                <div class="schedule-time">
                                    <i class="far fa-clock me-1"></i> <?= $class['time'] ?>
                                </div>
                                <div class="schedule-details">
                                    <div class="schedule-subject"><?= htmlspecialchars($class['subject']) ?></div>
                                    <div class="schedule-info">
                                        <i class="fas fa-door-open me-1"></i> <?= htmlspecialchars($class['room']) ?>
                                        <?php if ($class['instructor'] != '-'): ?>
                                            • <i class="fas fa-user me-1"></i> <?= htmlspecialchars($class['instructor']) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="alert alert-info mt-3 mb-0 small">
                        <i class="fas fa-info-circle me-2"></i> Schedule may change. Please check with your instructor for updates.
                    </div>
                </div>
            </div>
            
            <!-- Right Column - Events and Quick Links -->
            <div class="col-lg-5">
                <!-- Upcoming Events -->
                <div class="section-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="section-title mb-0">
                            <i class="fas fa-calendar-alt me-2"></i>Upcoming Events
                        </h5>
                        <a href="events.php" class="btn-view-all">View Calendar <i class="fas fa-arrow-right ms-1"></i></a>
                    </div>
                    
                    <?php if (count($upcomingEvents) > 0): ?>
                        <?php foreach ($upcomingEvents as $event): ?>
                            <div class="event-item">
                                <div class="event-date-box">
                                    <div class="event-day"><?= date('d', strtotime($event['created_at'])) ?></div>
                                    <div class="event-month"><?= date('M', strtotime($event['created_at'])) ?></div>
                                </div>
                                <div class="event-info">
                                    <h4><?= htmlspecialchars($event['title']) ?></h4>
                                    <div class="event-location">
                                        <i class="fas fa-map-marker-alt me-1"></i> OLSHCO Campus
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">No upcoming events</p>
                    <?php endif; ?>
                </div>
                
                <!-- Quick Links / Student Resources -->
                <div class="section-card">
                    <h5 class="section-title">
                        <i class="fas fa-link me-2"></i>Student Resources
                    </h5>
                    
                    <div class="row">
                        <div class="col-6 mb-3">
                            <a href="#" class="quick-link">
                                <i class="fas fa-book"></i>
                                <span>Online Library</span>
                            </a>
                        </div>
                        <div class="col-6 mb-3">
                            <a href="#" class="quick-link">
                                <i class="fas fa-id-card"></i>
                                <span>Student Portal</span>
                            </a>
                        </div>
                        <div class="col-6 mb-3">
                            <a href="#" class="quick-link">
                                <i class="fas fa-envelope"></i>
                                <span>Student Email</span>
                            </a>
                        </div>
                        <div class="col-6 mb-3">
                            <a href="#" class="quick-link">
                                <i class="fas fa-question-circle"></i>
                                <span>Help Desk</span>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- School Information -->
                <div class="section-card">
                    <h5 class="section-title">
                        <i class="fas fa-info-circle me-2"></i>School Information
                    </h5>
                    
                    <div class="mb-3">
                        <strong><i class="fas fa-map-marker-alt me-2" style="color: #800000;"></i>Address</strong>
                        <p class="small mb-0 mt-1">Guimba, Nueva Ecija, Philippines</p>
                    </div>
                    
                    <div class="mb-3">
                        <strong><i class="fas fa-phone me-2" style="color: #800000;"></i>Contact Number</strong>
                        <p class="small mb-0 mt-1">(044) 123-4567</p>
                    </div>
                    
                    <div class="mb-3">
                        <strong><i class="fas fa-envelope me-2" style="color: #800000;"></i>Email</strong>
                        <p class="small mb-0 mt-1">info@olshco.edu.ph</p>
                    </div>
                    
                    <div>
                        <strong><i class="fas fa-clock me-2" style="color: #800000;"></i>Office Hours</strong>
                        <p class="small mb-0 mt-1">Monday - Friday: 8:00 AM - 5:00 PM</p>
                    </div>
                </div>
                
                <!-- Need Help? -->
                <div class="section-card text-center">
                    <i class="fas fa-headset" style="font-size: 2rem; color: #800000;"></i>
                    <h6 class="mt-2">Need Assistance?</h6>
                    <p class="small text-muted">Contact the Student Affairs Office</p>
                    <button class="btn-read-more" onclick="window.location.href='contact.php'">
                        Get Help <i class="fas fa-arrow-right ms-1"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/chatbot.php'; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>




