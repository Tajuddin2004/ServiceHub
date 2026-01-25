<?php
session_start();
require_once dirname(__DIR__) . "/config/db.php";

// Try both possible session variable names
$isAdmin = false;
$adminId = 0;

// Check method 1: user_id + role
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $isAdmin = true;
    $adminId = (int) $_SESSION['user_id'];
}

// Check method 2: admin_logged_in (your old method)
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    $isAdmin = true;
    $adminId = (int) ($_SESSION['admin_id'] ?? 0);
}

if (!$isAdmin) {
    header("Location: login.php");
    exit;
}

$message = "";
$messageType = "";

if (isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($newPassword !== $confirmPassword) {
        $message = "New passwords do not match.";
        $messageType = "error";
    } elseif (strlen($newPassword) < 6) {
        $message = "Password must be at least 6 characters long.";
        $messageType = "error";
    } else {
        $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ? AND role = 'admin' LIMIT 1");
        $stmt->bind_param("i", $adminId);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();

        if (!$admin || !password_verify($currentPassword, $admin['password'])) {
            $message = "Current password is incorrect.";
            $messageType = "error";
        } else {
            $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ? AND role = 'admin'");
            $update->bind_param("si", $hashed, $adminId);

            if ($update->execute()) {
                $message = "Password changed successfully!";
                $messageType = "success";
            } else {
                $message = "Something went wrong. Please try again.";
                $messageType = "error";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Change Password | Admin Panel</title>
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
    background: linear-gradient(135deg, #0a0a0f 0%, #1a1a2e 50%, #16213e 100%);
}

.bg-gradient::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle at 50% 50%, rgba(239, 68, 68, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(245, 158, 11, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 20% 80%, rgba(234, 88, 12, 0.15) 0%, transparent 50%);
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
    background: linear-gradient(135deg, #ef4444, #f97316);
    top: -200px;
    right: -200px;
    animation-delay: 0s;
}

.orb-2 {
    width: 350px;
    height: 350px;
    background: linear-gradient(135deg, #f59e0b, #fbbf24);
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

/* Password card */
.password-card {
    width: 100%;
    max-width: 520px;
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(30px) saturate(180%);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 24px;
    padding: 3rem 2.5rem;
    box-shadow: 0 40px 80px rgba(0, 0, 0, 0.4);
    animation: fadeUp 0.8s ease-out;
    position: relative;
}

.password-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #ef4444, #f97316, #f59e0b);
    border-radius: 24px 24px 0 0;
}

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(40px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Back link */
.back-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: rgba(255, 255, 255, 0.6);
    text-decoration: none;
    font-size: 0.9rem;
    margin-bottom: 2rem;
    transition: all 0.3s ease;
}

.back-link:hover {
    color: #ef4444;
    transform: translateX(-3px);
}

/* Header */
.card-header {
    text-align: center;
    margin-bottom: 2.5rem;
}

.lock-icon {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.8rem;
    font-weight: 700;
    background: linear-gradient(135deg, #ef4444, #f97316);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 0.5rem;
}

.lock-emoji {
    font-size: 2rem;
    filter: drop-shadow(0 0 20px rgba(239, 68, 68, 0.6));
}

.card-header h2 {
    color: #ffffff;
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.card-header p {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.95rem;
}

/* Alert */
.alert {
    padding: 1rem 1.2rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    font-size: 0.9rem;
    animation: slideIn 0.5s ease;
    display: flex;
    align-items: center;
    gap: 0.8rem;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.alert-success {
    background: rgba(34, 197, 94, 0.1);
    border: 1px solid rgba(34, 197, 94, 0.3);
    color: #86efac;
}

.alert-success::before {
    content: '✅';
    font-size: 1.3rem;
}

.alert-error {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #fca5a5;
}

.alert-error::before {
    content: '⚠️';
    font-size: 1.3rem;
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
}

.password-wrapper {
    position: relative;
}

.form-input {
    width: 100%;
    padding: 1rem 3rem 1rem 1.2rem;
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
    border-color: rgba(239, 68, 68, 0.5);
    box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
}

.form-input::placeholder {
    color: rgba(255, 255, 255, 0.4);
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

/* Button */
.btn-primary {
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
    background: linear-gradient(135deg, #ef4444, #f97316);
    color: #ffffff;
    box-shadow: 0 10px 30px rgba(239, 68, 68, 0.3);
    margin-top: 0.5rem;
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
    box-shadow: 0 15px 40px rgba(239, 68, 68, 0.4);
}

.btn-primary:active {
    transform: translateY(0);
}

/* Security badge */
.security-badge {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 1.5rem;
    padding: 0.8rem;
    background: rgba(34, 197, 94, 0.1);
    border: 1px solid rgba(34, 197, 94, 0.2);
    border-radius: 10px;
    font-size: 0.85rem;
    color: #86efac;
}

/* Footer */
.auth-footer {
    text-align: center;
    padding: 2rem 1rem;
    position: relative;
    z-index: 1;
}

.footer-copy {
    color: rgba(255, 255, 255, 0.3);
    font-size: 0.8rem;
}

/* Responsive */
@media (max-width: 576px) {
    .password-card {
        padding: 2.5rem 2rem;
    }
    
    .auth-wrapper {
        padding: 1.5rem;
    }
    
    .orb-1, .orb-2 {
        width: 300px;
        height: 300px;
    }

    .card-header h2 {
        font-size: 1.3rem;
    }

    .lock-icon {
        font-size: 1.6rem;
    }
}
</style>
</head>

<body>
<div class="bg-gradient"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>

<div class="auth-wrapper">
    <div class="password-card">
        <!-- Back Link -->
        <a href="home.php" class="back-link">
            ← Back to Dashboard
        </a>

        <!-- Header -->
        <div class="card-header">
            <div class="lock-icon">
                <span class="lock-emoji"></span>
              🔐 Change Password
            </div>
            <h2>Update Security</h2>
            <p>Keep your admin account secure</p>
        </div>

        <!-- Alerts -->
        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>">
                <span><?= htmlspecialchars($message) ?></span>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST" id="passwordForm">
            <div class="form-group">
                <label class="form-label">Current Password</label>
                <div class="password-wrapper">
                    <input 
                        type="password" 
                        name="current_password" 
                        class="form-input" 
                        id="currentPassword"
                        placeholder="Enter current password" 
                        required
                        autocomplete="current-password"
                    >
                    <button type="button" class="password-toggle" onclick="togglePassword('currentPassword', 'toggleIcon1')">
                        <span id="toggleIcon1">👁️</span>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">New Password</label>
                <div class="password-wrapper">
                    <input 
                        type="password" 
                        name="new_password" 
                        class="form-input" 
                        id="newPassword"
                        placeholder="Enter new password" 
                        required
                        autocomplete="new-password"
                    >
                    <button type="button" class="password-toggle" onclick="togglePassword('newPassword', 'toggleIcon2')">
                        <span id="toggleIcon2">👁️</span>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Confirm New Password</label>
                <div class="password-wrapper">
                    <input 
                        type="password" 
                        name="confirm_password" 
                        class="form-input" 
                        id="confirmPassword"
                        placeholder="Re-enter new password" 
                        required
                        autocomplete="new-password"
                    >
                    <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword', 'toggleIcon3')">
                        <span id="toggleIcon3">👁️</span>
                    </button>
                </div>
            </div>

            <button type="submit" name="change_password" class="btn-primary">
                Update Password
            </button>

            <div class="security-badge">
                <span>🔐</span>
                <span>Your password is encrypted and secure</span>
            </div>
        </form>
    </div>
</div>

<footer class="auth-footer">
    <div class="footer-copy">
        © 2025 Tazx Happy-Home Admin Panel. All rights reserved.
    </div>
</footer>

<script>
function togglePassword(inputId, iconId) {
    const passwordInput = document.getElementById(inputId);
    const toggleIcon = document.getElementById(iconId);
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.textContent = '🙈';
    } else {
        passwordInput.type = 'password';
        toggleIcon.textContent = '👁️';
    }
}

// Form validation
document.getElementById('passwordForm').addEventListener('submit', function(e) {
    const newPassword = document.getElementById('newPassword');
    const confirmPassword = document.getElementById('confirmPassword');
    
    if (newPassword.value !== confirmPassword.value) {
        e.preventDefault();
        alert('New passwords do not match!');
        confirmPassword.focus();
    } else if (newPassword.value.length < 6) {
        e.preventDefault();
        alert('Password must be at least 6 characters long!');
        newPassword.focus();
    }
});

// Auto-hide success alerts
const alerts = document.querySelectorAll('.alert-success');
alerts.forEach(alert => {
    setTimeout(() => {
        alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-20px)';
        setTimeout(() => alert.remove(), 500);
    }, 5000);
});

// Add floating animation to card on mouse move
const card = document.querySelector('.password-card');
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