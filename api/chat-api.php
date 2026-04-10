<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Load config for Vercel
require_once '../includes/config.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Only allow logged-in users
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Please login to use the chatbot']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$message = trim($data['message'] ?? '');

if (empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Message is empty']);
    exit;
}

// Get user info
$user = getCurrentUser($pdo);
$userName = $user ? ($user['first_name'] ?: $user['username']) : 'Student';

// Get AI response
$reply = getAIReply($message, $userName);

echo json_encode(['success' => true, 'reply' => $reply]);
?>



