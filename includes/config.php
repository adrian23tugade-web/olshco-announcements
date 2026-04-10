<?php
session_start();

// Database configuration
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_name = getenv('DB_NAME') ?: 'olshco_announcements';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// reCAPTCHA Configuration
define('RECAPTCHA_SITE_KEY', getenv('RECAPTCHA_SITE_KEY') ?: '6LdVIK4sAAAAAI-W80-JPbeD1XXwMLDRR9atZIP1');
define('RECAPTCHA_SECRET_KEY', getenv('RECAPTCHA_SECRET_KEY') ?: '6LdVIK4sAAAAACWjxW2WfoFMhjWUr3JXf5dZuajL');

// Gemini AI API Configuration
define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: 'AIzaSyBe5WJ6wCd-_proZIyY4M6SZcRi5q5cigQ');

// Application URL
define('APP_URL', getenv('APP_URL') ?: 'http://localhost/olshco');
define('APP_NAME', 'OLSHCO Announcement System');

// User functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function getCurrentUser($pdo) {
    if (isLoggedIn()) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    }
    return null;
}

function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

function isStaff() {
    return isset($_SESSION['user_type']) && ($_SESSION['user_type'] === 'staff' || $_SESSION['user_type'] === 'admin');
}

// Function to verify reCAPTCHA
function verifyRecaptcha($token) {
    if (empty($token)) return false;
    
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $token
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    $response = json_decode($result, true);
    
    return $response['success'] ?? false;
}

// Function to get AI response from Gemini
function getAIReply($message, $userName = 'Student') {
    $apiKey = GEMINI_API_KEY;
    
    if (empty($apiKey)) {
        return "The AI assistant is being set up. Please ask the administrator to configure the Gemini API key.";
    }
    
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=" . $apiKey;
    
    $systemPrompt = "You are OLSHCO Assistant, a helpful AI chatbot for Our Lady of the Sacred Heart College of Guimba, Inc. 
    You help students with announcements, campus information, and general inquiries. 
    Keep responses friendly, educational, and concise. The user's name is " . $userName . ".";
    
    $data = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $systemPrompt . "\n\nUser: " . $message . "\n\nAssistant:"]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.7,
            'maxOutputTokens' => 500,
            'topP' => 0.8,
            'topK' => 40
        ]
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/json\r\n",
            'method' => 'POST',
            'content' => json_encode($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);
    
    if ($result === false) {
        return "I'm having trouble connecting right now. Please try again later.";
    }
    
    $response = json_decode($result, true);
    
    if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
        return $response['candidates'][0]['content']['parts'][0]['text'];
    }
    // where is the api?
    
    return "I'm here to help! Ask me anything about campus announcements or events.";
}

// Create default admin if not exists
function ensureAdminExists($pdo) {
    try {
        // Check if any admin exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE user_type = 'admin'");
        $stmt->execute();
        $adminCount = $stmt->fetchColumn();
        
        if ($adminCount == 0) {
            // Create default admin
            $username = 'admin';
            $email = 'admin@olshco.edu.ph';
            $password = password_hash('admin123', PASSWORD_DEFAULT);
            $firstName = 'System';
            $lastName = 'Administrator';
            
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password, first_name, last_name, user_type, status, created_at) 
                VALUES (?, ?, ?, ?, ?, 'admin', 'active', NOW())
            ");
            $stmt->execute([$username, $email, $password, $firstName, $lastName]);
            
            return true;
        }
    } catch (Exception $e) {
        error_log("Admin creation error: " . $e->getMessage());
    }
    return false;
}

// Auto-create admin if needed
ensureAdminExists($pdo);
?>