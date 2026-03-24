<?php
session_start();
require_once dirname(__DIR__) . "/config/db.php";
require_once dirname(__DIR__) . "/config/mailer.php";

// If no pending registration exists, redirect back
if (empty($_SESSION['pending_registration'])) {
    header("Location: register.php");
    exit();
}

$pending = $_SESSION['pending_registration'];
$error   = "";
$success = "";

// ── Handle OTP Verification ──────────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'verify') {

    $entered_otp = trim($_POST['otp'] ?? '');

    if (!preg_match('/^\d{6}$/', $entered_otp)) {
        $error = "Please enter a valid 6-digit code.";
    } else {
        // Fetch OTP from DB
        $stmt = $conn->prepare(
            "SELECT otp, expires_at FROM email_otps WHERE email = ? ORDER BY created_at DESC LIMIT 1"
        );
        $stmt->bind_param("s", $pending['email']);
        $stmt->execute();
        $stmt->bind_result($db_otp, $expires_at);
        $found = $stmt->fetch();
        $stmt->close();

        if (!$found) {
            $error = "No verification code found. Please register again.";
        } elseif (new DateTime() > new DateTime($expires_at)) {
            $error = "Your code has expired. Please resend a new one.";
        } elseif ($entered_otp !== $db_otp) {
            $error = "Incorrect code. Please try again.";
        } else {
            // ── OTP is valid: create the account ────────────────────────
            $conn->begin_transaction();
            try {
                if ($pending['role'] === 'provider') {
                    // Insert user (inactive until admin approval)
                    $s = $conn->prepare(
                        "INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, 'provider', 0)"
                    );
                    $s->bind_param("sss", $pending['name'], $pending['email'], $pending['password']);
                    $s->execute();
                    $userId = $conn->insert_id;
                    $s->close();

                    // Insert provider details
                    $p = $conn->prepare(
                        "INSERT INTO service_providers
                         (user_id, age, profession, about_work, experience, pincode, phone, is_approved)
                         VALUES (?, ?, ?, ?, ?, ?, ?, 0)"
                    );
                    $p->bind_param(
                        "iississ",
                        $userId,
                        $pending['age'],
                        $pending['profession'],
                        $pending['about_work'],
                        $pending['experience'],
                        $pending['pincode'],
                        $pending['phone']
                    );
                    $p->execute();
                    $p->close();

                    $success = "Application submitted! Admin will review and approve your account.";

                } else {
                    // Consumer — active immediately
                    $s = $conn->prepare(
                        "INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, 'consumer', 1)"
                    );
                    $s->bind_param("sss", $pending['name'], $pending['email'], $pending['password']);
                    $s->execute();
                    $s->close();

                    $success = "Account verified and created! You can now sign in.";
                }

                // Delete used OTP
                $del = $conn->prepare("DELETE FROM email_otps WHERE email = ?");
                $del->bind_param("s", $pending['email']);
                $del->execute();
                $del->close();

                $conn->commit();

                // Clear session
                unset($_SESSION['pending_registration']);

            } catch (Exception $e) {
                $conn->rollback();
                error_log("Registration error: " . $e->getMessage());
                $error = "Something went wrong during registration. Please try again.";
            }
        }
    }
}

// ── Handle Resend OTP ────────────────────────────────────────────────────────
$resend_message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'resend') {
    $otp        = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    $stmt = $conn->prepare(
        "INSERT INTO email_otps (email, otp, expires_at)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE otp = VALUES(otp), expires_at = VALUES(expires_at), created_at = NOW()"
    );
    $stmt->bind_param("sss", $pending['email'], $otp, $otp_expiry);
    $stmt->execute();
    $stmt->close();

    $sent = sendOtpEmail($pending['email'], $pending['name'], $otp);
    $resend_message = $sent
        ? "A new code has been sent to your email."
        : "Failed to resend. Please try again.";
}

// Mask email for display: j***@example.com
$email_parts = explode('@', $pending['email']);
$masked_email = substr($email_parts[0], 0, 1)
              . str_repeat('*', max(1, strlen($email_parts[0]) - 1))
              . '@' . $email_parts[1];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Service-Hub | Verify Email</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Verify your email address to complete Service-Hub registration.">
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

/* Modern OTP card */
.otp-card {
    width: 100%;
    max-width: 520px;
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
.otp-card::before {
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
    0% { background-position: 0% center; }
    100% { background-position: 200% center; }
}

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(30px) scale(0.96); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

/* Logo section */
.logo-section {
    background: rgba(0, 0, 0, 0.2);
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 2.5rem 2rem 2rem;
    border-bottom: 1px solid rgba(99, 102, 241, 0.15);
}

.logo-icon {
    width: 90px;
    height: 90px;
    object-fit: cover;
    border-radius: 18px;
    display: block;
    box-shadow:
        0 0 0 1px rgba(99, 102, 241, 0.3),
        0 0 30px rgba(99, 102, 241, 0.3),
        0 0 50px rgba(168, 85, 247, 0.2),
        0 8px 30px rgba(0, 0, 0, 0.6);
    transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    border: 2px solid rgba(99, 102, 241, 0.2);
}

/* Content section */
.content-section {
    padding: 2rem 2.5rem 2.5rem;
    text-align: center;
}

/* Email icon with animation */
.email-icon-wrap {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(168, 85, 247, 0.15));
    border: 2px solid rgba(99, 102, 241, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin: 0 auto 1.5rem;
    animation: pulse 2.5s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { 
        box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.4);
        transform: scale(1);
    }
    50% { 
        box-shadow: 0 0 0 12px rgba(99, 102, 241, 0);
        transform: scale(1.02);
    }
}

/* Typography */
.content-section h2 {
    color: #ffffff;
    font-size: 1.6rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    letter-spacing: -0.02em;
}

.subtitle {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.95rem;
    line-height: 1.5;
    margin-bottom: 0.3rem;
}

.email-display {
    color: #22d3ee;
    font-weight: 600;
    font-size: 0.95rem;
    margin-bottom: 2rem;
}

/* Alerts */
.alert {
    padding: 1rem 1.25rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    animation: slideDown 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    text-align: left;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.alert-error {
    background: rgba(239, 68, 68, 0.12);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #fca5a5;
}

.alert-success {
    background: rgba(34, 197, 94, 0.12);
    border: 1px solid rgba(34, 197, 94, 0.3);
    color: #86efac;
}

.alert-info {
    background: rgba(34, 211, 238, 0.1);
    border: 1px solid rgba(34, 211, 238, 0.25);
    color: #67e8f9;
}

.alert-error::before { content: '⚠'; font-size: 1.1rem; flex-shrink: 0; }
.alert-success::before { content: '✓'; font-size: 1.1rem; flex-shrink: 0; }
.alert-info::before { content: 'ℹ'; font-size: 1.1rem; flex-shrink: 0; }

/* OTP digit inputs */
.otp-inputs {
    display: flex;
    gap: 0.75rem;
    justify-content: center;
    margin-bottom: 2rem;
}

.otp-digit {
    width: 56px;
    height: 64px;
    background: rgba(255, 255, 255, 0.05);
    border: 2px solid rgba(99, 102, 241, 0.2);
    border-radius: 14px;
    color: #ffffff;
    font-size: 1.75rem;
    font-weight: 700;
    text-align: center;
    font-family: inherit;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    caret-color: transparent;
}

.otp-digit:focus {
    outline: none;
    background: rgba(99, 102, 241, 0.12);
    border-color: #6366f1;
    box-shadow: 
        0 0 0 3px rgba(99, 102, 241, 0.15),
        0 4px 12px rgba(99, 102, 241, 0.2);
    transform: scale(1.05);
}

.otp-digit.filled {
    border-color: rgba(99, 102, 241, 0.5);
    background: rgba(99, 102, 241, 0.1);
}

.otp-digit.error-shake {
    border-color: #ef4444;
    animation: shake 0.4s ease;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    20% { transform: translateX(-6px); }
    40% { transform: translateX(6px); }
    60% { transform: translateX(-4px); }
    80% { transform: translateX(4px); }
}

/* Hidden real input */
#otpHidden {
    display: none;
}

/* Buttons */
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
    margin-bottom: 1.5rem;
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

.btn-primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

/* Resend section */
.resend-section {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(99, 102, 241, 0.15);
}

.resend-section p {
    color: rgba(255, 255, 255, 0.5);
    font-size: 0.9rem;
    margin-bottom: 0.75rem;
}

.resend-btn-wrap {
    display: flex;
    gap: 0.75rem;
    align-items: center;
    justify-content: center;
}

.btn-ghost {
    background: transparent;
    border: 1.5px solid rgba(99, 102, 241, 0.25);
    color: rgba(255, 255, 255, 0.7);
    padding: 0.7rem 1.5rem;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    font-family: inherit;
    transition: all 0.3s ease;
    width: auto;
}

.btn-ghost:hover {
    border-color: rgba(99, 102, 241, 0.5);
    color: #fff;
    background: rgba(99, 102, 241, 0.1);
    transform: translateY(-1px);
}

.btn-ghost:disabled {
    opacity: 0.4;
    cursor: not-allowed;
    transform: none;
}

/* Timer badge */
.timer-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(99, 102, 241, 0.2);
    border-radius: 20px;
    padding: 0.4rem 1rem;
    color: rgba(255, 255, 255, 0.5);
    font-size: 0.85rem;
}

#timerCount {
    color: #22d3ee;
    font-weight: 600;
}

/* Back link */
.back-link {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    color: rgba(255, 255, 255, 0.5);
    font-size: 0.875rem;
    text-decoration: none;
    margin-top: 1.5rem;
    transition: all 0.25s ease;
    font-weight: 500;
}

.back-link:hover {
    color: #22d3ee;
}

/* Success State */
.success-state {
    display: none;
}

.success-state.show {
    display: block;
    animation: fadeUp 0.6s ease-out;
    padding: 2rem 2.5rem 2.5rem;
    text-align: center;
}

.checkmark-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    margin: 1rem auto 1.5rem;
    box-shadow: 
        0 8px 30px rgba(34, 197, 94, 0.4),
        0 0 60px rgba(34, 197, 94, 0.2);
    animation: bounceIn 0.6s ease-out;
}

@keyframes bounceIn {
    0% { transform: scale(0); }
    60% { transform: scale(1.15); }
    100% { transform: scale(1); }
}

.success-state h2 {
    color: #fff;
    font-size: 1.6rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.success-state p {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.95rem;
    line-height: 1.65;
    margin-bottom: 2rem;
}

.btn-login {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: #fff;
    padding: 1rem 2rem;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.95rem;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 8px 25px rgba(34, 197, 94, 0.35);
}

.btn-login:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 35px rgba(34, 197, 94, 0.45);
}

/* Responsive */
@media (max-width: 576px) {
    .otp-card {
        max-width: 100%;
        border-radius: 20px;
    }
    
    .content-section {
        padding: 1.5rem 1.8rem 2rem;
    }
    
    .logo-section {
        padding: 2rem 1.5rem 1.5rem;
    }
    
    .logo-icon {
        width: 75px;
        height: 75px;
    }
    
    .otp-digit {
        width: 48px;
        height: 58px;
        font-size: 1.5rem;
    }
    
    .otp-inputs {
        gap: 0.5rem;
    }
    
    .auth-wrapper {
        padding: 1rem;
    }
    
    .orb-1, .orb-2 {
        width: 250px;
        height: 250px;
    }
    
    .success-state.show {
        padding: 1.5rem 1.8rem 2rem;
    }
}

/* Subtle 3D tilt effect */
.otp-card {
    transition: transform 0.1s ease-out;
}
</style>
</head>

<body>
<div class="bg-gradient"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>

<div class="auth-wrapper">
    <div class="otp-card">

        <!-- Success State -->
        <?php if ($success): ?>
        <div class="logo-section">
            <img src="../assets/images/icon.jpeg" class="logo-icon" alt="Service-Hub">
        </div>
        <div class="success-state show">
            <div class="checkmark-circle">✅</div>
            <h2>Email Verified!</h2>
            <p><?= htmlspecialchars($success) ?></p>
            <a href="login.php" class="btn-login">
                Sign in to your account →
            </a>
        </div>

        <?php else: ?>
        <!-- OTP Entry State -->
        <div class="logo-section">
            <img src="../assets/images/icon.jpeg" class="logo-icon" alt="Service-Hub">
        </div>

        <div class="content-section">
            <div class="email-icon-wrap">📧</div>
            <h2>Check your email</h2>
            <p class="subtitle">We sent a 6-digit verification code to</p>
            <p class="email-display"><?= htmlspecialchars($masked_email) ?></p>

            <!-- Alerts -->
            <?php if ($error): ?>
                <div class="alert alert-error" id="alertBox"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($resend_message): ?>
                <div class="alert <?= strpos($resend_message,'new code') !== false ? 'alert-info' : 'alert-error' ?>" id="resendAlert">
                    <?= htmlspecialchars($resend_message) ?>
                </div>
            <?php endif; ?>

            <!-- OTP Form -->
            <form method="POST" id="otpForm">
                <input type="hidden" name="action" value="verify">
                <input type="hidden" name="otp" id="otpHidden">

                <div class="otp-inputs" id="otpInputs">
                    <input class="otp-digit" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="off" id="d0">
                    <input class="otp-digit" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="off" id="d1">
                    <input class="otp-digit" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="off" id="d2">
                    <input class="otp-digit" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="off" id="d3">
                    <input class="otp-digit" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="off" id="d4">
                    <input class="otp-digit" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="off" id="d5">
                </div>

                <button type="submit" class="btn btn-primary" id="verifyBtn" disabled>
                    Verify & Create Account
                </button>
            </form>

            <!-- Resend Section -->
            <div class="resend-section">
                <p>Didn't receive the code?</p>
                <div class="resend-btn-wrap">
                    <form method="POST" id="resendForm" style="margin: 0;">
                        <input type="hidden" name="action" value="resend">
                        <button type="submit" class="btn-ghost" id="resendBtn" disabled>🔄 Resend Code</button>
                    </form>
                    <div class="timer-badge">⏱ <span id="timerCount">2:00</span></div>
                </div>
            </div>

            <a href="register.php" class="back-link">← Back to registration</a>
        </div>
        <?php endif; ?>

    </div>
</div>

<script>
// OTP digit input logic
const digits = Array.from(document.querySelectorAll('.otp-digit'));
const hidden = document.getElementById('otpHidden');
const verifyBtn = document.getElementById('verifyBtn');

function getOtp() {
    return digits.map(d => d.value).join('');
}

function syncHiddenAndBtn() {
    const otp = getOtp();
    hidden.value = otp;
    verifyBtn.disabled = otp.length !== 6;
    digits.forEach((d, i) => {
        d.classList.toggle('filled', d.value !== '');
    });
}

digits.forEach((digit, idx) => {
    digit.addEventListener('input', (e) => {
        const val = e.target.value.replace(/\D/g, '');
        e.target.value = val ? val[0] : '';
        syncHiddenAndBtn();
        if (val && idx < 5) digits[idx + 1].focus();
    });

    digit.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && !digit.value && idx > 0) {
            digits[idx - 1].value = '';
            digits[idx - 1].focus();
            syncHiddenAndBtn();
        }
    });

    digit.addEventListener('paste', (e) => {
        e.preventDefault();
        const pasted = (e.clipboardData || window.clipboardData)
            .getData('text').replace(/\D/g, '').slice(0, 6);
        pasted.split('').forEach((ch, i) => {
            if (digits[i]) digits[i].value = ch;
        });
        syncHiddenAndBtn();
        const nextEmpty = digits.find(d => !d.value);
        if (nextEmpty) nextEmpty.focus();
        else digits[5].focus();
    });
});

// Focus first field on load
if (digits[0]) digits[0].focus();

// Shake animation on error
<?php if ($error): ?>
digits.forEach(d => {
    d.classList.add('error-shake');
    d.addEventListener('animationend', () => d.classList.remove('error-shake'), { once: true });
});
<?php endif; ?>

// Countdown timer for resend
const resendBtn = document.getElementById('resendBtn');
const timerCount = document.getElementById('timerCount');

let seconds = 120; // 2 minutes

function updateTimer() {
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    timerCount.textContent = `${m}:${String(s).padStart(2, '0')}`;
}

updateTimer();

const countdown = setInterval(() => {
    seconds--;
    updateTimer();
    if (seconds <= 0) {
        clearInterval(countdown);
        resendBtn.disabled = false;
        timerCount.textContent = '0:00';
        timerCount.style.color = '#f59e0b';
    }
}, 1000);

// Loading state on verify submit
document.getElementById('otpForm').addEventListener('submit', function() {
    verifyBtn.disabled = true;
    verifyBtn.textContent = 'Verifying…';
});

// Subtle 3D tilt effect on mouse move
const card = document.querySelector('.otp-card');
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