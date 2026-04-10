<?php
/**
 * Middleware functions for access control
 */

/**
 * Require user to be logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
}

/**
 * Require user to be admin
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . APP_URL . '/index.php?error=unauthorized');
        exit;
    }
}

/**
 * Require user to be staff or admin
 */
function requireStaff() {
    requireLogin();
    if (!isStaff()) {
        header('Location: ' . APP_URL . '/index.php?error=unauthorized');
        exit;
    }
}

/**
 * Redirect if already logged in
 */
function redirectIfAuthenticated($redirectTo = '/index.php') {
    if (isLoggedIn()) {
        header('Location: ' . APP_URL . $redirectTo);
        exit;
    }
}

/**
 * Check session timeout (30 minutes)
 */
function checkSessionTimeout() {
    $timeout = 1800; // 30 minutes
    
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > $timeout)) {
        session_destroy();
        header('Location: ' . APP_URL . '/login.php?timeout=1');
        exit;
    }
    
    $_SESSION['login_time'] = time();
}
?>