<?php
session_start();
require_once dirname(__DIR__) . "/config/db.php";

$error = "";
$timeoutMessage = isset($_GET['timeout']);

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = "Please fill in all fields.";
    } else {

        $stmt = $conn->prepare("
            SELECT 
                u.user_id,
                u.name,
                u.email,
                u.password,
                u.role,
                u.is_active,
                sp.is_approved
            FROM users u
            LEFT JOIN service_providers sp ON sp.user_id = u.user_id
            WHERE u.email = ?
            LIMIT 1
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        /* ❌ User not found */
        if (!$user || !password_verify($password, $user['password'])) {
            $error = "Invalid email or password.";
        }

        /* 🚫 User deactivated by admin */
        elseif ($user['is_active'] == 0) {
            $error = "Your account has been deactivated by admin.";
        }

        /* 🚫 Provider approval check */
        elseif ($user['role'] === 'provider' && $user['is_approved'] != 1) {

            if ($user['is_approved'] == 0) {
                $error = "Your account is pending admin approval.";
            } else {
                $error = "Your account has been rejected by admin.";
            }

        } else {

            /* ✅ LOGIN SUCCESS */
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['role']    = $user['role'];
            $_SESSION['login_success'] = true;

            if ($user['role'] === 'admin') {
                header("Location: ../admin/home.php");
            } elseif ($user['role'] === 'provider') {
                header("Location: ../provider/home.php");
            } else {
                header("Location: ../consumer/home.php");
            }
            exit;
        }
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Service-Hub | Login</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    min-height: 100vh;
    background: #0a0a0f;
    display: flex;
    flex-direction: column;
    overflow-x: hidden;
}

/* Animated gradient background */
.bg-gradient {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: -1;
    background: linear-gradient(135deg, #0a0a0f 0%, #1a1a2e 50%, #0a0a0f 100%);
}

.bg-gradient::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle at 50% 50%, rgba(99, 102, 241, 0.2) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(168, 85, 247, 0.2) 0%, transparent 50%),
                radial-gradient(circle at 20% 80%, rgba(34, 211, 238, 0.2) 0%, transparent 50%);
    animation: floatGradient 20s ease-in-out infinite;
}

@keyframes floatGradient {
    0%, 100% { transform: translate(0, 0) rotate(0deg); }
    33% { transform: translate(30px, -30px) rotate(120deg); }
    66% { transform: translate(-20px, 20px) rotate(240deg); }
}

/* Floating orbs */
.orb {
    position: fixed;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.3;
    animation: float 15s ease-in-out infinite;
    z-index: 0;
}

.orb-1 {
    width: 400px;
    height: 400px;
    background: linear-gradient(135deg, #6366f1, #a855f7);
    top: -200px;
    right: -200px;
    animation-delay: 0s;
}

.orb-2 {
    width: 350px;
    height: 350px;
    background: linear-gradient(135deg, #22d3ee, #06b6d4);
    bottom: -150px;
    left: -150px;
    animation-delay: 5s;
}

@keyframes float {
    0%, 100% { transform: translate(0, 0); }
    50% { transform: translate(50px, -50px); }
}

/* Auth wrapper */
.auth-wrapper {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    position: relative;
    z-index: 1;
}

/* Login card */
.login-card {
    width: 100%;
    max-width: 480px;
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(30px) saturate(180%);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 24px;
    padding: 3rem 2.5rem;
    box-shadow: 0 40px 80px rgba(0, 0, 0, 0.4);
    animation: fadeUp 0.8s ease-out;
    position: relative;
}


.login-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #6366f1, #a855f7, #22d3ee);
    border-radius: 24px 24px 0 0;
}

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(40px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Logo section */
.login-header {
    text-align: center;
    margin-bottom: 2.5rem;
}

.logo {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.8rem;
    font-weight: 700;
    background: linear-gradient(135deg, #22d3ee, #a855f7);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 0.5rem;
}

.logo-icon {
    font-size: 2rem;
    filter: drop-shadow(0 0 20px rgba(34, 211, 238, 0.6));
}

.login-header h2 {
    color: #ffffff;
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.login-header p {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.95rem;
}

/* Alert */
.alert {
    padding: 1rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #fca5a5;
    font-size: 0.9rem;
    animation: shake 0.5s ease;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-10px); }
    75% { transform: translateX(10px); }
}

/* Form */
.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9rem;
    font-weight: 500;
    transition: color 0.3s ease;
}

.form-input {
    width: 100%;
    padding: 1rem 1.2rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    color: #ffffff;
    font-size: 1rem;
    transition: all 0.3s ease;
    font-family: inherit;
}

.form-input:focus {
    outline: none;
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(99, 102, 241, 0.5);
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
}

.form-input::placeholder {
    color: rgba(255, 255, 255, 0.4);
}

/* Button */
.btn {
    width: 100%;
    padding: 1rem;
    border: none;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.btn-primary {
    background: linear-gradient(135deg, #6366f1, #a855f7);
    color: #ffffff;
    box-shadow: 0 10px 30px rgba(99, 102, 241, 0.3);
}

.btn-primary::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s ease;
}

.btn-primary:hover::before {
    left: 100%;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 15px 40px rgba(99, 102, 241, 0.4);
}

.btn-primary:active {
    transform: translateY(0);
}

/* Divider */
.divider {
    text-align: center;
    margin: 1.5rem 0;
    color: rgba(255, 255, 255, 0.4);
    font-size: 0.9rem;
}

/* Links */
.auth-link {
    text-align: center;
    margin-top: 1.5rem;
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.95rem;
}

.auth-link a {
    color: #22d3ee;
    text-decoration: none;
    font-weight: 600;
    transition: color 0.3s ease;
}

.auth-link a:hover {
    color: #a855f7;
    text-decoration: underline;
}

/* Forgot password link */
.forgot-link {
    text-align: center;
    margin-top: 1rem;
}

.forgot-link a {
    color: rgba(255, 255, 255, 0.45);
    text-decoration: none;
    font-size: 0.88rem;
    font-weight: 500;
    transition: color 0.25s ease;
}

.forgot-link a:hover {
    color: #22d3ee;
}

/* Footer */
.auth-footer {
    text-align: center;
    padding: 2rem 1rem;
    position: relative;
    z-index: 1;
}

.footer-links {
    display: flex;
    justify-content: center;
    gap: 1.5rem;
    flex-wrap: wrap;
    margin-bottom: 1rem;
}

.footer-links a {
    color: rgba(255, 255, 255, 0.5);
    text-decoration: none;
    font-size: 0.85rem;
    transition: color 0.3s ease;
}

.footer-links a:hover {
    color: #22d3ee;
}

.footer-copy {
    color: rgba(255, 255, 255, 0.3);
    font-size: 0.8rem;
}

/* Password toggle */
.password-wrapper {
    position: relative;
}

.password-toggle {
    position: absolute;
    right: 1.2rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: rgba(255, 255, 255, 0.5);
    cursor: pointer;
    font-size: 1.2rem;
    transition: color 0.3s ease;
    padding: 0;
}

.password-toggle:hover {
    color: rgba(255, 255, 255, 0.8);
}

/* Responsive */
@media (max-width: 576px) {
    .login-card {
        padding: 2.5rem 2rem;
    }
    
    .auth-wrapper {
        padding: 1.5rem;
    }
    
    .orb-1, .orb-2 {
        width: 300px;
        height: 300px;
    }
}
</style>
</head>

<body>
<div class="bg-gradient"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>

<div class="auth-wrapper">
    <div class="login-card">

         <!-- ⏱️ AUTO LOGOUT MESSAGE -->
    <?php if ($timeoutMessage): ?>
        <div class="alert alert-error">
            You were logged out due to inactivity. Please sign in again.
        </div>
    <?php endif; ?>

    <!-- Existing error message -->
    <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Login header -->
    <div class="login-header">
        
    </div>

    <!-- Login form -->
    <form method="POST" action="">
        
    </form>

    
        <div class="login-header">
            <div class="logo">
                <!--<span class="logo-icon">🏠</span>-->
                🏠Service-Hub
            </div>
            <h2>Welcome back</h2>
            <p>Sign in to manage your bookings and services</p>
        </div>



        <form method="POST">
            <div class="form-group">
                <label class="form-label">Email address</label>
                <input type="email" name="email" class="form-input" placeholder="you@example.com" required>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="password-wrapper">
                    <input type="password" name="password" class="form-input" id="password" placeholder="Enter your password" required>
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        <span id="toggleIcon">👁️</span>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                Sign in
            </button>

            <div class="forgot-link">
                <a href="forgot_password.php">Forgot password?</a>
            </div>

            <div class="auth-link">
                New to Service-Hub? <a href="register.php">Create an account</a>
            </div>
        </form>
    </div>
</div>

<footer class="auth-footer">
    <div class="footer-links">
        <a href="#">Terms of Service</a>
        <a href="#">Privacy Policy</a>
        <a href="#">Support</a>
        <a href="#">About Us</a>
    </div>
    <div class="footer-copy">
        © 2025 Service-Hub. All rights reserved.
    </div>
</footer>

<script>
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.textContent = '🙈';
    } else {
        passwordInput.type = 'password';
        toggleIcon.textContent = '👁️';
    }
}

// Add floating animation to card on mouse move
const card = document.querySelector('.login-card');
document.addEventListener('mousemove', (e) => {
    const x = (e.clientX / window.innerWidth - 0.5) * 10;
    const y = (e.clientY / window.innerHeight - 0.5) * 10;
    card.style.transform = `perspective(1000px) rotateY(${x}deg) rotateX(${-y}deg)`;
});

// Reset transform when mouse leaves
document.addEventListener('mouseleave', () => {
    card.style.transform = 'perspective(1000px) rotateY(0deg) rotateX(0deg)';
});
</script>

</body>
</html>