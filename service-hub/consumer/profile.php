<?php

session_start();
require_once dirname(__DIR__) . "/config/db.php";

/* 🔐 Protect consumer access */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'consumer') {
    header("Location: ../auth/login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$success = $error = "";

/* 🔎 Fetch user data */
$stmt = $conn->prepare("
    SELECT user_id, name, email, phone, password, created_at
    FROM users
    WHERE user_id = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

/* 💾 Update profile */
if (isset($_POST['update_profile'])) {

    $name  = trim($_POST['name']);
    $phone = trim($_POST['phone']);

    if ($name === '') {
        $error = "Name is required.";
    } else {
        $update = $conn->prepare(
            "UPDATE users SET name = ?, phone = ? WHERE user_id = ?"
        );
        $update->bind_param("ssi", $name, $phone, $userId);
        $update->execute();

        $_SESSION['name'] = $name;
        $success = "Profile updated successfully.";
    }
}

/* 🔑 Change password */
if (isset($_POST['change_password'])) {

    $oldPassword     = $_POST['old_password'];
    $newPassword     = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if ($oldPassword === '' || $newPassword === '' || $confirmPassword === '') {
        $error = "All password fields are required.";
    }
    elseif (!password_verify($oldPassword, $user['password'])) {
        $error = "Old password is incorrect.";
    }
    elseif ($newPassword !== $confirmPassword) {
        $error = "New passwords do not match.";
    }
    elseif (strlen($newPassword) < 6) {
        $error = "Password must be at least 6 characters.";
    }
    else {
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);

        $p = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $p->bind_param("si", $hashed, $userId);
        $p->execute();

        $success = "Password changed successfully.";
    }
} */
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Edit Profile | Tazx Happy-Home</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: #0a0a0f;
    color: #ffffff;
    min-height: 100vh;
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
    background: radial-gradient(circle at 50% 50%, rgba(99, 102, 241, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(168, 85, 247, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 20% 80%, rgba(34, 211, 238, 0.15) 0%, transparent 50%);
    animation: floatGradient 20s ease-in-out infinite;
}

@keyframes floatGradient {
    0%, 100% { transform: translate(0, 0) rotate(0deg); }
    33% { transform: translate(30px, -30px) rotate(120deg); }
    66% { transform: translate(-20px, 20px) rotate(240deg); }
}

/* Container */
.container {
    padding: 3rem 5%;
    max-width: 900px;
    margin: auto;
    min-height: 100vh;
}

/* Header */
.page-header {
    margin-bottom: 2rem;
    animation: fadeUp 0.6s ease;
}

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    font-size: 0.9rem;
    margin-bottom: 1rem;
    transition: color 0.3s ease;
}

.back-link:hover {
    color: #22d3ee;
}

.page-header h1 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    background: linear-gradient(135deg, #ffffff 0%, #a0a0a0 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.page-header p {
    color: rgba(255, 255, 255, 0.6);
}

/* Alert */
.alert {
    padding: 1rem 1.2rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    gap: 0.8rem;
    animation: slideDown 0.4s ease;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.alert-success {
    background: rgba(34, 197, 94, 0.1);
    border: 1px solid rgba(34, 197, 94, 0.3);
    color: #86efac;
}

.alert-error {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #fca5a5;
}

.alert::before {
    font-size: 1.3rem;
}

.alert-success::before {
    content: '✓';
}

.alert-error::before {
    content: '⚠';
}

/* Form Cards */
.form-card {
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(30px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    padding: 2.5rem;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
    animation: fadeUp 0.6s ease 0.2s backwards;
}

.form-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #6366f1, #a855f7, #22d3ee);
    border-radius: 20px 20px 0 0;
}

.card-header {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    margin-bottom: 2rem;
}

.card-header h2 {
    font-size: 1.5rem;
    font-weight: 700;
}

.card-icon {
    font-size: 1.8rem;
}

/* Form Groups */
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

.form-input:read-only {
    background: rgba(255, 255, 255, 0.02);
    border-color: rgba(255, 255, 255, 0.05);
    cursor: not-allowed;
    color: rgba(255, 255, 255, 0.5);
}

.form-input::placeholder {
    color: rgba(255, 255, 255, 0.4);
}

/* Two Column Grid */
.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
}

/* Password Toggle */
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

/* Button */
.btn-primary {
    width: 100%;
    padding: 1.2rem;
    background: linear-gradient(135deg, #6366f1, #a855f7);
    border: none;
    border-radius: 12px;
    color: #ffffff;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
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

/* Password Card */
.password-card {
    animation: fadeUp 0.6s ease 0.3s backwards;
}

.password-card::before {
    background: linear-gradient(90deg, #ef4444, #f87171, #ef4444);
}

/* Responsive */
@media (max-width: 768px) {
    .container {
        padding: 2rem 4%;
    }
    
    .form-card {
        padding: 2rem 1.5rem;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
}
</style>
</head>

<body>
<div class="bg-gradient"></div>

<div class="container">
    <!-- Header -->
    <div class="page-header">
        <a href="account.php" class="back-link">← Back to My Account</a>
        <h1>Edit Profile</h1>
        <p>Update your personal information and security settings</p>
    </div>

    <!-- Success/Error Messages -->
    <?php if ($success): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Profile Information Card -->
    <div class="form-card">
        <div class="card-header">
            <span class="card-icon">👤</span>
            <h2>Profile Information</h2>
        </div>

        <form method="POST">
            <input type="hidden" name="update_profile">

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">User ID</label>
                    <input type="text" class="form-input" value="#<?= $user['user_id'] ?>" readonly>
                </div>

                <div class="form-group">
                    <label class="form-label">Member Since</label>
                    <input type="text" class="form-input" value="<?= date('M d, Y', strtotime($user['created_at'])) ?>" readonly>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-input" value="<?= htmlspecialchars($user['name']) ?>" required placeholder="Enter your full name">
            </div>

            <div class="form-group">
                <label class="form-label">Email Address (Cannot be changed)</label>
                <input type="email" class="form-input" value="<?= htmlspecialchars($user['email']) ?>" readonly>
            </div>

            <div class="form-group">
                <label class="form-label">Phone Number</label>
                <input type="tel" name="phone" class="form-input" value="<?= htmlspecialchars($user['phone']) ?>" placeholder="Enter your phone number" pattern="[0-9]{10}" maxlength="10">
            </div>

            <button type="submit" class="btn-primary">
                💾 Update Profile
            </button>
        </form>
    </div>

    <!-- Change Password Card -->
    <div class="form-card password-card">
        <div class="card-header">
            <span class="card-icon">🔒</span>
            <h2>Change Password</h2>
        </div>

        <form method="POST" id="passwordForm">
            <input type="hidden" name="change_password">

            <div class="form-group">
                <label class="form-label">Current Password</label>
                <div class="password-wrapper">
                    <input type="password" name="old_password" id="oldPassword" class="form-input" required placeholder="Enter current password">
                    <button type="button" class="password-toggle" onclick="togglePassword('oldPassword', this)">
                        <span>👁️</span>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">New Password</label>
                <div class="password-wrapper">
                    <input type="password" name="new_password" id="newPassword" class="form-input" required placeholder="Enter new password (min. 6 characters)" minlength="6">
                    <button type="button" class="password-toggle" onclick="togglePassword('newPassword', this)">
                        <span>👁️</span>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Confirm New Password</label>
                <div class="password-wrapper">
                    <input type="password" name="confirm_password" id="confirmPassword" class="form-input" required placeholder="Re-enter new password">
                    <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword', this)">
                        <span>👁️</span>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-primary">
                🔑 Change Password
            </button>
        </form>
    </div>
</div>

<script>
// Password visibility toggle
function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);
    const icon = button.querySelector('span');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.textContent = '🙈';
    } else {
        input.type = 'password';
        icon.textContent = '👁️';
    }
}

// Auto-hide alerts after 5 seconds
const alerts = document.querySelectorAll('.alert');
alerts.forEach(alert => {
    setTimeout(() => {
        alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-20px)';
        setTimeout(() => alert.remove(), 500);
    }, 5000);
});

// Phone number validation
const phoneInput = document.querySelector('input[name="phone"]');
if (phoneInput) {
    phoneInput.addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
        if (this.value.length > 10) {
            this.value = this.value.slice(0, 10);
        }
    });
}

// Password match validation
const passwordForm = document.getElementById('passwordForm');
passwordForm.addEventListener('submit', function(e) {
    const newPass = document.getElementById('newPassword').value;
    const confirmPass = document.getElementById('confirmPassword').value;
    
    if (newPass !== confirmPass) {
        e.preventDefault();
        alert('New passwords do not match!');
    }
});
</script>

</body>
</html>