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

/* Animated gradient background - ORIGINAL */
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

/* Floating orbs - ORIGINAL */
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

/* Modern login card */
.login-card {
    width: 100%;
    max-width: 460px;
    background: rgba(13, 13, 20, 0.85);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(99, 102, 241, 0.3);
    border-radius: 24px;
    overflow: hidden;
    padding: 0;
    box-shadow:
        0 0 0 1px rgba(168, 85, 247, 0.15),
        0 20px 60px rgba(0, 0, 0, 0.5),
        0 0 100px rgba(99, 102, 241, 0.1),
        inset 0 1px 0 rgba(255, 255, 255, 0.08);
    animation: fadeUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
    position: relative;
}

/* Animated rainbow top border */
.login-card::before {
    content: '';
    position: absolute;
    top: 0; 
    left: 0; 
    right: 0;
    height: 2px;
    background: linear-gradient(90deg, #6366f1, #a855f7, #22d3ee, #a855f7, #6366f1);
    background-size: 200% auto;
    animation: shimmer 4s linear infinite;
    z-index: 2;
}

@keyframes shimmer {
    0%   { background-position: 0% center; }
    100% { background-position: 200% center; }
}

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(30px) scale(0.96); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}

/* Logo section */
.logo-section {
    background: rgba(0, 0, 0, 0.2);
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 3rem 2rem 2rem;
    border-bottom: 1px solid rgba(99, 102, 241, 0.15);
}

.logo-icon {
    width: 120px;
    height: 120px;
    object-fit: cover;
    border-radius: 20px;
    display: block;
    box-shadow:
        0 0 0 1px rgba(99, 102, 241, 0.3),
        0 0 40px rgba(99, 102, 241, 0.3),
        0 0 60px rgba(168, 85, 247, 0.2),
        0 10px 40px rgba(0, 0, 0, 0.6);
    transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    border: 2px solid rgba(99, 102, 241, 0.2);
}

.logo-icon:hover {
    transform: translateY(-4px) scale(1.03);
    box-shadow:
        0 0 0 1px rgba(99, 102, 241, 0.5),
        0 0 60px rgba(99, 102, 241, 0.5),
        0 0 80px rgba(168, 85, 247, 0.3),
        0 15px 50px rgba(0, 0, 0, 0.7);
    border-color: rgba(99, 102, 241, 0.4);
}

/* Form section */
.form-section {
    padding: 2rem 2.5rem 2.5rem;
}

/* Header */
.form-header {
    text-align: center;
    margin-bottom: 2rem;
}

.form-header h2 {
    color: #ffffff;
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    letter-spacing: -0.02em;
}

.form-header p {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.95rem;
}

/* Alert */
.alert {
    padding: 1rem 1.25rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    background: rgba(239, 68, 68, 0.12);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #fca5a5;
    font-size: 0.9rem;
    animation: shake 0.5s ease;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-8px); }
    75% { transform: translateX(8px); }
}

/* Form groups */
.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    margin-bottom: 0.6rem;
    color: rgba(255, 255, 255, 0.9);
    font-size: 0.9rem;
    font-weight: 600;
}

.form-input {
    width: 100%;
    padding: 0.95rem 1.2rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1.5px solid rgba(99, 102, 241, 0.2);
    border-radius: 12px;
    color: #ffffff;
    font-size: 0.95rem;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    font-family: inherit;
}

.form-input:focus {
    outline: none;
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(99, 102, 241, 0.6);
    box-shadow: 
        0 0 0 3px rgba(99, 102, 241, 0.15),
        0 4px 12px rgba(99, 102, 241, 0.2);
    transform: translateY(-1px);
}

.form-input::placeholder {
    color: rgba(255, 255, 255, 0.4);
}

/* Password wrapper */
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
    transition: all 0.2s ease;
    padding: 0.25rem;
}

.password-toggle:hover {
    color: rgba(255, 255, 255, 0.8);
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
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    position: relative;
    overflow: hidden;
    font-family: inherit;
}

.btn-primary {
    background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
    color: #ffffff;
    box-shadow: 
        0 0 0 1px rgba(99, 102, 241, 0.3),
        0 8px 24px rgba(99, 102, 241, 0.4),
        0 0 40px rgba(168, 85, 247, 0.2);
}

.btn-primary::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.25), transparent);
    transition: left 0.6s ease;
}

.btn-primary:hover::before {
    left: 100%;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 
        0 0 0 1px rgba(99, 102, 241, 0.4),
        0 12px 32px rgba(99, 102, 241, 0.5),
        0 0 60px rgba(168, 85, 247, 0.3);
}

.btn-primary:active {
    transform: translateY(0);
}

/* Links section */
.form-links {
    margin-top: 1.5rem;
    text-align: center;
}

.forgot-link {
    display: inline-block;
    color: rgba(255, 255, 255, 0.5);
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    transition: color 0.25s ease;
    margin-bottom: 1.25rem;
}

.forgot-link:hover {
    color: #22d3ee;
}

.divider {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin: 1.5rem 0;
}

.divider::before,
.divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: rgba(99, 102, 241, 0.2);
}

.divider span {
    color: rgba(255, 255, 255, 0.4);
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.1em;
}

.signup-link {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.95rem;
}

.signup-link a {
    color: #22d3ee;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    position: relative;
}

.signup-link a::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 0;
    height: 1px;
    background: linear-gradient(90deg, #22d3ee, #a855f7);
    transition: width 0.3s ease;
}

.signup-link a:hover {
    color: #a855f7;
}

.signup-link a:hover::after {
    width: 100%;
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
    color: rgba(255, 255, 255, 0.35);
    font-size: 0.8rem;
}

/* Responsive */
@media (max-width: 576px) {
    .login-card { 
        max-width: 100%; 
        border-radius: 20px;
    }
    .form-section { 
        padding: 1.5rem 1.8rem 2rem; 
    }
    .logo-section { 
        padding: 2.5rem 1.5rem 1.5rem; 
    }
    .logo-icon { 
        width: 100px; 
        height: 100px; 
    }
    .form-header h2 {
        font-size: 1.5rem;
    }
    .auth-wrapper { 
        padding: 1rem; 
    }
    .orb-1, .orb-2 { 
        width: 250px; 
        height: 250px; 
    }
}

/* Subtle 3D tilt effect */
.login-card {
    transition: transform 0.1s ease-out;
}
</style>
</head>

<body>
<div class="bg-gradient"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>

<div class="auth-wrapper">
    <div class="login-card">
        <!-- Logo section -->
        <div class="logo-section">
            <img src="../assets/images/icon.jpeg" class="logo-icon" alt="Service-Hub">
        </div>

        <!-- Form section -->
        <div class="form-section">
            <!-- Auto logout message -->
            <?php if (isset($timeoutMessage) && $timeoutMessage): ?>
                <div class="alert">
                    You were logged out due to inactivity. Please sign in again.
                </div>
            <?php endif; ?>

            <!-- Error message -->
            <?php if (isset($error) && !empty($error)): ?>
                <div class="alert">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Header -->
            <div class="form-header">
                <h2>Welcome back</h2>
                <p>Sign in to manage your bookings and services</p>
            </div>

            <!-- Form -->
            <form method="POST">
                <div class="form-group">
                    <label class="form-label" for="email">Email address</label>
                    <input 
                        type="email" 
                        id="email"
                        name="email" 
                        class="form-input" 
                        placeholder="you@example.com" 
                        required
                        autocomplete="email"
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="password-wrapper">
                        <input 
                            type="password" 
                            id="password"
                            name="password" 
                            class="form-input" 
                            placeholder="Enter your password" 
                            required
                            autocomplete="current-password"
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword()" aria-label="Toggle password visibility">
                            <span id="toggleIcon">👁️</span>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    Sign in
                </button>

                <div class="form-links">
                    <a href="forgot_password.php" class="forgot-link">Forgot password?</a>
                    
                    <div class="divider">
                        <span>or</span>
                    </div>
                    
                    <div class="signup-link">
                        New to Service-Hub? <a href="register.php">Create an account</a>
                    </div>
                </div>
            </form>
        </div>
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
// Password toggle
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

// Subtle 3D tilt effect on mouse move
const card = document.querySelector('.login-card');
let bounds;

function rotateCard(e) {
    bounds = card.getBoundingClientRect();
    const mouseX = e.clientX;
    const mouseY = e.clientY;
    const leftX = mouseX - bounds.left;
    const topY = mouseY - bounds.top;
    const center = {
        x: leftX - bounds.width / 2,
        y: topY - bounds.height / 2
    };
    const distance = Math.sqrt(center.x**2 + center.y**2);
    
    card.style.transform = `
        perspective(1000px)
        rotateY(${center.x / 25}deg)
        rotateX(${-center.y / 25}deg)
        scale3d(1.01, 1.01, 1.01)
    `;
}

function resetCard() {
    card.style.transform = `
        perspective(1000px)
        rotateY(0deg)
        rotateX(0deg)
        scale3d(1, 1, 1)
    `;
}

card.addEventListener('mouseenter', () => {
    bounds = card.getBoundingClientRect();
});

card.addEventListener('mousemove', rotateCard);
card.addEventListener('mouseleave', resetCard);
</script>

</body>
</html>