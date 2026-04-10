<?php
require_once '../includes/config.php';
// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: /');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    
    // Validation
    if (empty($first_name)) {
        $errors[] = "First name is required";
    }
    
    if (empty($last_name)) {
        $errors[] = "Last name is required";
    }
    
    if (strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters";
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    } elseif (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/", $password)) {
        $errors[] = "Password must contain at least one uppercase letter, one lowercase letter, and one number";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    // reCAPTCHA validation
    $recaptchaToken = $_POST['g-recaptcha-response'] ?? '';
    if (!verifyRecaptcha($recaptchaToken)) {
        $errors[] = "Please complete the reCAPTCHA verification";
    }
    
    // Check if username exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $errors[] = "Username already taken";
        }
    }
    
    // Check if email exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = "Email already registered";
        }
    }
    
    // Register user
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password, first_name, last_name, user_type) 
            VALUES (?, ?, ?, ?, ?, 'user')
        ");
        
        if ($stmt->execute([$username, $email, $hashedPassword, $first_name, $last_name])) {
            $_SESSION['flash_message'] = 'Registration successful! Please login.';
            $_SESSION['flash_type'] = 'success';
            header('Location: login.php?registered=1');
            exit;
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - OLSHCO Student Announcement</title>
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
        
        .register-container {
            max-width: 600px;
            width: 100%;
            animation: fadeInUp 0.8s ease-out;
        }
        
        .register-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: none;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        .register-card:hover {
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
            width: 90px;
            height: 90px;
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
            margin-top: 3px;
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
            padding: 2rem;
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
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }
        
        .form-control:focus {
            border-color: #800000;
            box-shadow: 0 0 0 0.2rem rgba(128, 0, 0, 0.15);
            background: white;
        }
        
        .form-text {
            font-size: 0.8rem;
            color: #666;
            margin-top: 5px;
        }
        
        .form-text i {
            color: #800000;
            font-size: 0.75rem;
        }
        
        .btn-register {
            background: linear-gradient(135deg, #800000 0%, #a00000 100%);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 14px 20px;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-register:hover {
            background: linear-gradient(135deg, #600000 0%, #800000 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(128, 0, 0, 0.3);
            color: white;
        }
        
        .btn-home {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            color: white;
            border: none;
            border-radius: 10px;
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
            border-radius: 10px;
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
        
        .alert-danger ul {
            margin-bottom: 0;
            padding-left: 1.2rem;
        }
        
        .alert-danger li {
            margin-bottom: 3px;
        }
        
        .login-link {
            color: #800000;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border-bottom: 2px solid transparent;
        }
        
        .login-link:hover {
            color: #a00000;
            border-bottom-color: #800000;
        }
        
        hr {
            margin: 1.5rem 0;
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
        
        .required-indicator {
            color: #dc3545;
            font-size: 0.85rem;
            margin-left: 2px;
        }
        
        /* Password strength indicator */
        .password-strength {
            margin-top: 5px;
            height: 5px;
            background: #e0e0e0;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease, background-color 0.3s ease;
        }
        
        .strength-weak { width: 33.33%; background: #dc3545; }
        .strength-medium { width: 66.66%; background: #ffc107; }
        .strength-strong { width: 100%; background: #28a745; }
        
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
        
        /* Logo animation */
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
        
        /* Responsive adjustments */
        @media (max-width: 576px) {
            .card-body {
                padding: 1.5rem 1.25rem;
            }
            
            .card-header {
                padding: 1.5rem 1rem;
            }
            
            .school-logo {
                width: 70px;
                height: 70px;
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
    <div class="register-container">
        <div class="register-card">
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
                    <i class="fas fa-user-plus me-2"></i>Create an Account
                </h3>
                <p class="mb-0 mt-2 text-white-50">Join the OLSHCO Student Announcement</p>
            </div>
            
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Please correct the following errors:</strong>
                        <ul class="mt-2">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="registerForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">
                                <i class="fas fa-user me-1"></i>First Name 
                                <span class="required-indicator">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="first_name" 
                                   name="first_name"
                                   placeholder="Enter first name"
                                   value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>"
                                   required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">
                                <i class="fas fa-user me-1"></i>Last Name 
                                <span class="required-indicator">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="last_name" 
                                   name="last_name"
                                   placeholder="Enter last name"
                                   value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>"
                                   required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">
                            <i class="fas fa-at me-1"></i>Username 
                            <span class="required-indicator">*</span>
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="username" 
                               name="username" 
                               placeholder="Choose a username"
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                               required
                               minlength="3">
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>At least 3 characters
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope me-1"></i>Email Address 
                            <span class="required-indicator">*</span>
                        </label>
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email" 
                               placeholder="Enter your email"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                               required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock me-1"></i>Password 
                            <span class="required-indicator">*</span>
                        </label>
                        <div class="input-group">
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Create a password"
                                   required
                                   minlength="8">
                            <span class="input-group-text bg-transparent" style="cursor: pointer; border-radius: 0 10px 10px 0;" onclick="togglePassword('password')">
                                <i class="fas fa-eye text-muted" id="passwordToggleIcon"></i>
                            </span>
                        </div>
                        <div class="form-text">
                            <i class="fas fa-shield-alt me-1"></i>At least 8 characters with 1 uppercase, 1 lowercase, and 1 number
                        </div>
                        <div class="password-strength">
                            <div class="password-strength-bar" id="passwordStrengthBar"></div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">
                            <i class="fas fa-check-circle me-1"></i>Confirm Password 
                            <span class="required-indicator">*</span>
                        </label>
                        <div class="input-group">
                            <input type="password" 
                                   class="form-control" 
                                   id="confirm_password" 
                                   name="confirm_password" 
                                   placeholder="Confirm your password"
                                   required>
                            <span class="input-group-text bg-transparent" style="cursor: pointer; border-radius: 0 10px 10px 0;" onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye text-muted" id="confirmToggleIcon"></i>
                            </span>
                        </div>
                        <div class="form-text" id="passwordMatchMessage"></div>
                    </div>
                    
                    <!-- Google reCAPTCHA - Centered -->
                    <div class="mb-4 text-center">
                        <div class="g-recaptcha d-inline-block" data-sitekey="<?= RECAPTCHA_SITE_KEY ?>"></div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-register">
                            <i class="fas fa-user-plus me-2"></i>Create Account
                        </button>
                    </div>
                </form>
                
                <hr>
                
                <div class="text-center">
                    <p class="mb-0 text-muted">Already have an account?</p>
                    <a href="/login" class="login-link">
                        <i class="fas fa-sign-in-alt me-2"></i>Login to your account
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
        function togglePassword(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const toggleIcon = fieldId === 'password' ? 
                document.getElementById('passwordToggleIcon') : 
                document.getElementById('confirmToggleIcon');
            
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
        
        // Password strength checker
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('passwordStrengthBar');
            
            strengthBar.className = 'password-strength-bar';
            
            if (password.length === 0) {
                strengthBar.style.width = '0%';
            } else if (password.length < 8) {
                strengthBar.classList.add('strength-weak');
            } else {
                const hasUpper = /[A-Z]/.test(password);
                const hasLower = /[a-z]/.test(password);
                const hasNumber = /\d/.test(password);
                const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
                
                const strength = [hasUpper, hasLower, hasNumber, hasSpecial].filter(Boolean).length;
                
                if (strength <= 2) {
                    strengthBar.classList.add('strength-weak');
                } else if (strength === 3) {
                    strengthBar.classList.add('strength-medium');
                } else {
                    strengthBar.classList.add('strength-strong');
                }
            }
        });
        
        // Password match checker
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const matchMessage = document.getElementById('passwordMatchMessage');
        
        function checkPasswordMatch() {
            if (confirmPassword.value.length > 0) {
                if (password.value === confirmPassword.value) {
                    matchMessage.innerHTML = '<i class="fas fa-check-circle text-success me-1"></i>Passwords match';
                    matchMessage.style.color = '#28a745';
                    confirmPassword.style.borderColor = '#28a745';
                } else {
                    matchMessage.innerHTML = '<i class="fas fa-times-circle text-danger me-1"></i>Passwords do not match';
                    matchMessage.style.color = '#dc3545';
                    confirmPassword.style.borderColor = '#dc3545';
                }
            } else {
                matchMessage.innerHTML = '';
                confirmPassword.style.borderColor = '#e0e0e0';
            }
        }
        
        password.addEventListener('input', checkPasswordMatch);
        confirmPassword.addEventListener('input', checkPasswordMatch);
        
        // Add subtle animation to form on load
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.form-control');
            inputs.forEach((input, index) => {
                input.style.opacity = '0';
                input.style.transform = 'translateY(10px)';
                setTimeout(() => {
                    input.style.transition = 'all 0.5s ease';
                    input.style.opacity = '1';
                    input.style.transform = 'translateY(0)';
                }, 50 * index);
            });
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>



