<?php
require_once __DIR__ . '/../includes/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: /dashboard');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $recaptchaToken = $_POST['g-recaptcha-response'] ?? '';
    
    // Verify reCAPTCHA
    if (!verifyRecaptcha($recaptchaToken)) {
        $error = 'Please complete the reCAPTCHA verification';
    } elseif (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        // Find user
        $stmt = $pdo->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND status = 'active'");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['logged_in'] = true;
            
            // Update last login
            $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            // Redirect to dashboard instead of index
            header('Location: /dashboard');
            exit;
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - OLSHCO Student Announcement</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), 
                        url('https://scontent.fcrk1-4.fna.fbcdn.net/v/t1.6435-9/199599438_316035993343704_5216618319916563018_n.jpg?stp=dst-jpg_s960x960_tt6&_nc_cat=102&ccb=1-7&_nc_sid=7b2446&_nc_eui2=AeH8D2bwx1Hj00XJO53yWn4i0se-cZq6xxnSx75xmrrHGceLN1L7XlNtJg5oQBPRPdZBkG9tCnb3zB7PrqLjAXTb&_nc_ohc=U89sBdyR8egQ7kNvwE9Omrw&_nc_oc=AdqXtjLhd9kzcF3bLTDVwm35xEi5tWQjJHrU_XEDwfb9Z4iIyC9EL0zRQFSUt2Hu9_w&_nc_zt=23&_nc_ht=scontent.fcrk1-4.fna&_nc_gid=yzpoicsdof6zR83i-R9wRw&_nc_ss=7a3a8&oh=00_Af3KmyklmWmX_uhhin8nF7PYfZEqwKqg7ezAmafa0ffPsw&oe=69FDE314');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }
        
        .login-container {
            max-width: 480px;
            width: 100%;
            animation: fadeInUp 0.8s ease-out;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: none;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        .login-card:hover {
            transform: translateY(-5px);
        }
        
        .card-header {
            background: linear-gradient(135deg, #800000 0%, #a00000 100%);
            padding: 2rem 1.5rem 1.5rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .card-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 3s infinite;
        }
        
        .logo-container {
            position: relative;
            z-index: 1;
            margin-bottom: 15px;
        }
        
        .school-logo {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 50%;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            padding: 8px;
            border: 3px solid rgba(255, 255, 255, 0.3);
        }
        
        .school-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 50%;
        }
        
        .school-name {
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.95);
            margin-top: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            position: relative;
            z-index: 1;
            line-height: 1.4;
        }
        
        .school-name small {
            display: block;
            font-size: 0.8rem;
            opacity: 0.85;
            margin-top: 4px;
            font-weight: 400;
        }
        
        .card-header h3 {
            margin-bottom: 0.5rem;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
            position: relative;
            z-index: 1;
            font-size: 1.5rem;
        }
        
        .card-header p {
            opacity: 0.95;
            position: relative;
            z-index: 1;
        }
        
        .card-body {
            padding: 2.5rem 2rem;
        }
        
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .form-label i {
            color: #800000;
            width: 20px;
        }
        
        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }
        
        .form-control:focus {
            border-color: #800000;
            box-shadow: 0 0 0 0.2rem rgba(128, 0, 0, 0.15);
            background: white;
        }
        
        .btn-login {
            background: linear-gradient(135deg, #800000 0%, #a00000 100%);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 14px 20px;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-login:hover {
            background: linear-gradient(135deg, #600000 0%, #800000 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(128, 0, 0, 0.3);
            color: white;
        }
        
        .btn-home {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 14px 20px;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-home:hover {
            background: linear-gradient(135deg, #5a6268 0%, #4e555b 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(108, 117, 125, 0.3);
            color: white;
        }
        
        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem;
            margin-bottom: 1.5rem;
            animation: slideDown 0.4s ease-out;
        }
        
        .alert-danger {
            background-color: #fff5f5;
            color: #c53030;
            border-left: 4px solid #c53030;
        }
        
        .alert-success {
            background-color: #f0fff4;
            color: #2f855a;
            border-left: 4px solid #2f855a;
        }
        
        .register-link {
            color: #800000;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border-bottom: 2px solid transparent;
        }
        
        .register-link:hover {
            color: #a00000;
            border-bottom-color: #800000;
        }
        
        hr {
            margin: 2rem 0;
            opacity: 0.3;
        }
        
        .watermark {
            position: absolute;
            bottom: 20px;
            right: 20px;
            color: rgba(255, 255, 255, 0.3);
            font-size: 12px;
            z-index: 1000;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes pulse {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }
        
        .school-logo {
            animation: logoGlow 2s ease-in-out infinite alternate;
        }
        
        @keyframes logoGlow {
            from {
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2), 0 0 0 0 rgba(128, 0, 0, 0.1);
            }
            to {
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3), 0 0 20px 5px rgba(128, 0, 0, 0.2);
            }
        }
        
        @media (max-width: 576px) {
            .card-body {
                padding: 1.5rem 1.25rem;
            }
            
            .card-header {
                padding: 1.5rem 1rem;
            }
            
            .school-logo {
                width: 80px;
                height: 80px;
            }
            
            .school-name {
                font-size: 0.9rem;
            }
            
            .school-name small {
                font-size: 0.7rem;
            }
            
            .card-header h3 {
                font-size: 1.25rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="card-header">
                <div class="logo-container">
                    <div class="school-logo">
                        <img src="https://scontent.fcrk1-3.fna.fbcdn.net/v/t39.30808-6/304876520_452370496913886_1208855622441055407_n.png?_nc_cat=100&ccb=1-7&_nc_sid=1d70fc&_nc_eui2=AeFIQL6MOFjeGjUztCGOI-KdEXrOprJXfucRes6msld-5-RPuxZCuECAfdj6BSfZiXZAHT5iu6TnkDjz7RPuGicc&_nc_ohc=bTPoalUZm5MQ7kNvwGi1rNw&_nc_oc=AdrLiZj4kP9IyjhjYLrH4Q-X7Ma_f-PeHadjiTBQmaOK3tRmQrI_AA3UdExMGyFNCxE&_nc_zt=23&_nc_ht=scontent.fcrk1-3.fna&_nc_gid=K0bIMjgTOgo567Z1b0yhlg&_nc_ss=7a3a8&oh=00_Af1qphHPENAxrIpfg1kQkcjaKdmu9xXAphYMX6DXGMocRw&oe=69DC61CB" 
                             alt="OLSHCO Logo">
                    </div>
                    <div class="school-name">
                        OUR LADY OF THE SACRED HEART COLLEGE
                        <small>of Guimba, Inc. • Since 1947</small>
                    </div>
                </div>
                <h3 class="mb-0 text-white">
                    <i class="fas fa-bullhorn me-2"></i>Student Announcement
                </h3>
                <p class="mb-0 mt-2 text-white-50">Access your campus announcements and updates</p>
            </div>
            
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Error:</strong> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['registered'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>Success!</strong> Registration successful! Please login.
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-4">
                        <label for="username" class="form-label">
                            <i class="fas fa-user me-2"></i>Username or Email
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-end-0" style="border-radius: 12px 0 0 12px;">
                                <i class="fas fa-user text-muted"></i>
                            </span>
                            <input type="text" 
                                   class="form-control border-start-0" 
                                   id="username" 
                                   name="username" 
                                   placeholder="Enter your username or email"
                                   style="border-radius: 0 12px 12px 0;"
                                   required 
                                   autocomplete="username">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label">
                            <i class="fas fa-key me-2"></i>Password
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-end-0" style="border-radius: 12px 0 0 12px;">
                                <i class="fas fa-lock text-muted"></i>
                            </span>
                            <input type="password" 
                                   class="form-control border-start-0 border-end-0" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Enter your password"
                                   required 
                                   autocomplete="current-password">
                            <span class="input-group-text bg-transparent border-start-0" style="border-radius: 0 12px 12px 0; cursor: pointer;" onclick="togglePassword()">
                                <i class="fas fa-eye text-muted" id="togglePasswordIcon"></i>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Google reCAPTCHA - Centered -->
                    <div class="mb-4 text-center">
                        <div class="g-recaptcha d-inline-block" data-sitekey="<?= RECAPTCHA_SITE_KEY ?>"></div>
                    </div>
                    
                    <div class="d-grid gap-2 mb-3">
                        <button type="submit" class="btn btn-login">
                            <i class="fas fa-sign-in-alt me-2"></i>Login to Announcement
                        </button>
                    </div>
                    
                    <div class="text-end">
                        <a href="forgot-password.php" class="text-muted small" style="text-decoration: none;">
                            <i class="fas fa-question-circle me-1"></i>Forgot password?
                        </a>
                    </div>
                </form>
                
                <hr>
                
                <div class="text-center">
                    <p class="mb-2 text-muted">Don't have an account?</p>
                    <a href="/register" class="register-link">
                        <i class="fas fa-user-plus me-2"></i>Create New Account
                    </a>
                </div>
                
                <!-- Back to Home Button -->
                <div class="mt-4">
                    <a href="/" class="btn btn-home w-100">
                        <i class="fas fa-home me-2"></i>Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="watermark">
        <i class="fas fa-bullhorn me-1"></i>OLSHCO Student Announcement • Guimba, Nueva Ecija
    </div>
    
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('togglePasswordIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.form-control');
            inputs.forEach((input, index) => {
                input.style.opacity = '0';
                input.style.transform = 'translateX(-10px)';
                setTimeout(() => {
                    input.style.transition = 'all 0.5s ease';
                    input.style.opacity = '1';
                    input.style.transform = 'translateX(0)';
                }, 100 * index);
            });
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>




