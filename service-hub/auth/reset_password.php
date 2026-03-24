<?php
session_start();
require_once dirname(__DIR__) . "/config/db.php";
require_once dirname(__DIR__) . "/config/mailer.php";

// Guard: must have come from forgot_password
if (empty($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit();
}

$email  = $_SESSION['reset_email'];
$name   = $_SESSION['reset_name'] ?? 'User';
$error  = "";
$success = "";
$otp_verified = false; // tracks if we're in the "set new password" phase

// ── STEP 1: Verify OTP ───────────────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST['action'] ?? '') === 'verify_otp') {

    $entered_otp = trim($_POST['otp'] ?? '');

    if (!preg_match('/^\d{6}$/', $entered_otp)) {
        $error = "Please enter a valid 6-digit code.";
    } else {
        $stmt = $conn->prepare(
            "SELECT otp, expires_at FROM email_otps WHERE email = ? ORDER BY created_at DESC LIMIT 1"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($db_otp, $expires_at);
        $found = $stmt->fetch();
        $stmt->close();

        if (!$found) {
            $error = "No reset code found. Please request a new one.";
        } elseif (new DateTime() > new DateTime($expires_at)) {
            $error = "Your code has expired. Please request a new one.";
        } elseif ($entered_otp !== $db_otp) {
            $error = "Incorrect code. Please try again.";
        } else {
            // OTP correct — mark as verified in session, show password form
            $_SESSION['reset_otp_verified'] = true;
            $otp_verified = true;
        }
    }
}

// ── STEP 2: Set New Password ─────────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST['action'] ?? '') === 'set_password') {

    // Extra guard: OTP must have been verified
    if (empty($_SESSION['reset_otp_verified'])) {
        header("Location: reset_password.php");
        exit();
    }

    $new_password = $_POST['new_password']     ?? '';
    $confirm_pw   = $_POST['confirm_password'] ?? '';

    if (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters.";
        $otp_verified = true;
    } elseif ($new_password !== $confirm_pw) {
        $error = "Passwords do not match.";
        $otp_verified = true;
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);

        $upd = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $upd->bind_param("ss", $hashed, $email);
        $upd->execute();
        $upd->close();

        // Clean up OTP row and session
        $del = $conn->prepare("DELETE FROM email_otps WHERE email = ?");
        $del->bind_param("s", $email);
        $del->execute();
        $del->close();

        unset($_SESSION['reset_email'], $_SESSION['reset_name'], $_SESSION['reset_otp_verified']);

        $success = "PASSWORD_RESET_DONE"; // signal for the success state
    }
}

// ── RESEND OTP ───────────────────────────────────────────────────────────────
$resend_message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST['action'] ?? '') === 'resend') {
    $otp        = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    $ins = $conn->prepare(
        "INSERT INTO email_otps (email, otp, expires_at)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE otp = VALUES(otp), expires_at = VALUES(expires_at), created_at = NOW()"
    );
    $ins->bind_param("sss", $email, $otp, $otp_expiry);
    $ins->execute();
    $ins->close();

    $sent = sendOtpEmail($email, $name, $otp);
    $resend_message = $sent ? "A new code has been sent." : "Failed to resend. Try again.";
}

// If already OTP-verified (e.g. page refresh), restore that state
if (!empty($_SESSION['reset_otp_verified']) && empty($error)) {
    $otp_verified = true;
}

// Mask email
$parts        = explode('@', $email);
$masked_email = substr($parts[0], 0, 1) . str_repeat('*', max(1, strlen($parts[0]) - 1)) . '@' . $parts[1];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Service-Hub | Reset Password</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Reset your Service-Hub account password.">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
* { margin:0; padding:0; box-sizing:border-box; }

body {
    font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif;
    min-height:100vh; background:#0a0a0f;
    display:flex; flex-direction:column; overflow-x:hidden;
}
.bg-gradient {
    position:fixed; top:0; left:0; width:100%; height:100%; z-index:-1;
    background:linear-gradient(135deg,#0a0a0f 0%,#1a1a2e 50%,#0a0a0f 100%);
}
.bg-gradient::before {
    content:''; position:absolute; top:-50%; left:-50%; width:200%; height:200%;
    background:
        radial-gradient(circle at 50% 50%,rgba(99,102,241,0.2) 0%,transparent 50%),
        radial-gradient(circle at 80% 20%,rgba(168,85,247,0.2) 0%,transparent 50%),
        radial-gradient(circle at 20% 80%,rgba(34,211,238,0.2) 0%,transparent 50%);
    animation:floatGradient 20s ease-in-out infinite;
}
@keyframes floatGradient{
    0%,100%{transform:translate(0,0) rotate(0deg);}
    33%{transform:translate(30px,-30px) rotate(120deg);}
    66%{transform:translate(-20px,20px) rotate(240deg);}
}
.orb{position:fixed;border-radius:50%;filter:blur(80px);opacity:0.3;animation:float 15s ease-in-out infinite;z-index:0;}
.orb-1{width:400px;height:400px;background:linear-gradient(135deg,#6366f1,#a855f7);top:-200px;right:-200px;}
.orb-2{width:350px;height:350px;background:linear-gradient(135deg,#22d3ee,#06b6d4);bottom:-150px;left:-150px;animation-delay:5s;}
@keyframes float{0%,100%{transform:translate(0,0);}50%{transform:translate(50px,-50px);}}

.auth-wrapper{
    flex:1; display:flex; align-items:center; justify-content:center;
    padding:2rem; position:relative; z-index:1;
}

.card{
    width:100%; max-width:460px;
    background:rgba(255,255,255,0.03);
    backdrop-filter:blur(30px) saturate(180%);
    border:1px solid rgba(255,255,255,0.1);
    border-radius:24px; padding:3rem 2.5rem;
    box-shadow:0 40px 80px rgba(0,0,0,0.4);
    animation:fadeUp 0.8s ease-out; position:relative; text-align:center;
}
.card::before{
    content:''; position:absolute; top:0; left:0; right:0; height:4px;
    background:linear-gradient(90deg,#6366f1,#a855f7,#22d3ee);
    border-radius:24px 24px 0 0;
}
@keyframes fadeUp{from{opacity:0;transform:translateY(40px);}to{opacity:1;transform:translateY(0);}}

.logo{
    display:inline-flex; align-items:center; gap:0.5rem;
    font-size:1.6rem; font-weight:700;
    background:linear-gradient(135deg,#22d3ee,#a855f7);
    -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;
    margin-bottom:0.5rem;
}
.logo-icon{width:8rem;height:8rem;object-fit:cover;border-radius:8px;display:block;flex-shrink:0;box-shadow:0 0 12px rgba(34,211,238,0.45);-webkit-background-clip:unset !important;background-clip:unset !important;-webkit-text-fill-color:unset !important;}

.icon-wrap{
    width:76px; height:76px; border-radius:50%;
    background:linear-gradient(135deg,rgba(99,102,241,0.2),rgba(168,85,247,0.2));
    border:2px solid rgba(99,102,241,0.3);
    display:flex; align-items:center; justify-content:center;
    font-size:2rem; margin:1.5rem auto 1.2rem;
    animation:pulse 2.5s ease-in-out infinite;
}
@keyframes pulse{0%,100%{box-shadow:0 0 0 0 rgba(99,102,241,0.3);}50%{box-shadow:0 0 0 14px rgba(99,102,241,0);}}

.card h2{color:#fff; font-size:1.4rem; font-weight:700; margin-bottom:0.4rem;}
.subtitle{color:rgba(255,255,255,0.55); font-size:0.9rem; line-height:1.65; margin-bottom:0.3rem;}
.email-display{color:#22d3ee; font-weight:600; font-size:0.92rem; margin-bottom:2rem;}

/* Alerts */
.alert{
    padding:0.9rem 1.1rem; border-radius:12px; margin-bottom:1.4rem;
    font-size:0.88rem; display:flex; align-items:center; gap:0.5rem;
    animation:slideDown 0.3s ease; text-align:left;
}
@keyframes slideDown{from{opacity:0;transform:translateY(-8px);}to{opacity:1;transform:translateY(0);}}
.alert-error  {background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.3); color:#fca5a5;}
.alert-success{background:rgba(34,197,94,0.1);  border:1px solid rgba(34,197,94,0.3);  color:#86efac;}
.alert-info   {background:rgba(34,211,238,0.08);border:1px solid rgba(34,211,238,0.25);color:#67e8f9;}
.alert-error::before  {content:'⚠';font-size:1.1rem;flex-shrink:0;}
.alert-success::before{content:'✓';font-size:1.1rem;flex-shrink:0;}
.alert-info::before   {content:'ℹ';font-size:1.1rem;flex-shrink:0;}

/* OTP digit inputs */
.otp-inputs{display:flex;gap:0.7rem;justify-content:center;margin-bottom:1.8rem;}
.otp-digit{
    width:52px; height:60px;
    background:rgba(255,255,255,0.05);
    border:2px solid rgba(255,255,255,0.12);
    border-radius:14px; color:#fff;
    font-size:1.6rem; font-weight:700; text-align:center;
    font-family:inherit; transition:all 0.2s ease; caret-color:transparent;
}
.otp-digit:focus{
    outline:none; background:rgba(99,102,241,0.12);
    border-color:#6366f1; box-shadow:0 0 0 4px rgba(99,102,241,0.15); transform:scale(1.05);
}
.otp-digit.filled{border-color:rgba(99,102,241,0.5);background:rgba(99,102,241,0.08);}
.otp-digit.error-shake{border-color:#ef4444;animation:shake 0.4s ease;}
@keyframes shake{0%,100%{transform:translateX(0);}20%{transform:translateX(-6px);}40%{transform:translateX(6px);}60%{transform:translateX(-4px);}80%{transform:translateX(4px);}}

/* Form */
.form-group{margin-bottom:1.3rem;text-align:left;}
.form-label{display:block;margin-bottom:0.5rem;color:rgba(255,255,255,0.8);font-size:0.9rem;font-weight:500;}
.form-input{
    width:100%; padding:1rem 1.2rem;
    background:rgba(255,255,255,0.05);
    border:1px solid rgba(255,255,255,0.1);
    border-radius:12px; color:#fff; font-size:1rem;
    transition:all 0.3s ease; font-family:inherit;
}
.form-input:focus{
    outline:none; background:rgba(255,255,255,0.08);
    border-color:rgba(99,102,241,0.5); box-shadow:0 0 0 4px rgba(99,102,241,0.1);
}
.form-input::placeholder{color:rgba(255,255,255,0.4);}

/* Password wrapper */
.pw-wrap{position:relative;}
.pw-toggle{
    position:absolute; right:1.2rem; top:50%; transform:translateY(-50%);
    background:none; border:none; color:rgba(255,255,255,0.5);
    cursor:pointer; font-size:1.2rem; transition:color 0.3s ease; padding:0;
}
.pw-toggle:hover{color:rgba(255,255,255,0.8);}

/* Password strength */
.strength-bar-wrap{height:4px;background:rgba(255,255,255,0.1);border-radius:4px;margin-top:0.5rem;overflow:hidden;opacity:0;transition:opacity 0.3s;}
.strength-bar-wrap.active{opacity:1;}
.strength-bar{height:100%;width:0;transition:width 0.3s,background 0.3s;border-radius:4px;}
.s-weak  {width:33%;background:#ef4444;}
.s-medium{width:66%;background:#f59e0b;}
.s-strong{width:100%;background:#22c55e;}
.strength-hint{font-size:0.75rem;color:rgba(255,255,255,0.5);margin-top:0.3rem;text-align:left;}

/* Buttons */
.btn{
    width:100%; padding:1rem; border:none; border-radius:12px;
    font-size:1rem; font-weight:600; cursor:pointer;
    transition:all 0.3s ease; position:relative; overflow:hidden; font-family:inherit;
}
.btn-primary{background:linear-gradient(135deg,#6366f1,#a855f7);color:#fff;box-shadow:0 10px 30px rgba(99,102,241,0.3);}
.btn-primary::before{content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.2),transparent);transition:left 0.5s ease;}
.btn-primary:hover::before{left:100%;}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 15px 40px rgba(99,102,241,0.4);}
.btn-primary:active{transform:translateY(0);}
.btn-primary:disabled{opacity:0.6;cursor:not-allowed;transform:none;}

/* Resend */
.resend-section{margin-top:1rem;}
.resend-section p{color:rgba(255,255,255,0.5);font-size:0.88rem;margin-bottom:0.6rem;}
.resend-row{display:flex;gap:0.8rem;align-items:center;justify-content:center;}
.btn-ghost{
    background:transparent; border:1px solid rgba(255,255,255,0.15);
    color:rgba(255,255,255,0.7); padding:0.65rem 1.4rem;
    border-radius:10px; font-size:0.88rem; font-weight:500;
    cursor:pointer; font-family:inherit; transition:all 0.25s ease; width:auto;
}
.btn-ghost:hover{border-color:rgba(99,102,241,0.5);color:#fff;background:rgba(99,102,241,0.08);}
.timer-badge{
    display:inline-flex; align-items:center; gap:0.3rem;
    background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1);
    border-radius:20px; padding:0.35rem 0.9rem;
    color:rgba(255,255,255,0.5); font-size:0.82rem;
}
#timerCount{color:#22d3ee;font-weight:600;}

.back-link{
    display:inline-flex; align-items:center; gap:0.4rem;
    color:rgba(255,255,255,0.45); font-size:0.85rem; text-decoration:none;
    margin-top:1.5rem; transition:color 0.2s ease;
}
.back-link:hover{color:#22d3ee;}

/* Success state */
.success-state{animation:fadeUp 0.6s ease-out;}
.checkmark{
    width:80px; height:80px; border-radius:50%;
    background:linear-gradient(135deg,#22c55e,#16a34a);
    display:flex; align-items:center; justify-content:center;
    font-size:2.2rem; margin:1.5rem auto 1.2rem;
    box-shadow:0 8px 30px rgba(34,197,94,0.35);
    animation:bounceIn 0.6s ease-out;
}
@keyframes bounceIn{0%{transform:scale(0);}60%{transform:scale(1.15);}100%{transform:scale(1);}}
.success-state h2{color:#fff;margin-bottom:0.5rem;}
.success-state p{color:rgba(255,255,255,0.6);font-size:0.93rem;line-height:1.65;margin-bottom:1.5rem;}
.btn-login{
    display:inline-flex; align-items:center; gap:0.5rem;
    background:linear-gradient(135deg,#22c55e,#16a34a);
    color:#fff; padding:0.9rem 2rem; border-radius:12px;
    text-decoration:none; font-weight:600; font-size:0.95rem;
    transition:all 0.3s ease; box-shadow:0 8px 25px rgba(34,197,94,0.3);
}
.btn-login:hover{transform:translateY(-2px);box-shadow:0 12px 35px rgba(34,197,94,0.4);}

/* Step indicator */
.step-indicator{
    display:flex; align-items:center; justify-content:center;
    gap:0.5rem; margin-bottom:2rem;
}
.step{
    display:flex; align-items:center; gap:0.5rem; font-size:0.82rem;
    color:rgba(255,255,255,0.35); font-weight:500;
}
.step-dot{
    width:28px; height:28px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:0.78rem; font-weight:700;
    background:rgba(255,255,255,0.07); border:1px solid rgba(255,255,255,0.12);
}
.step.active .step-dot{background:linear-gradient(135deg,#6366f1,#a855f7);border-color:transparent;color:#fff;}
.step.active{color:rgba(255,255,255,0.85);}
.step.done .step-dot{background:rgba(34,197,94,0.2);border-color:rgba(34,197,94,0.4);color:#22c55e;}
.step-line{flex:1;max-width:40px;height:1px;background:rgba(255,255,255,0.1);}

/* Footer */
.auth-footer{text-align:center;padding:2rem 1rem;position:relative;z-index:1;}
.footer-links{display:flex;justify-content:center;gap:1.5rem;flex-wrap:wrap;margin-bottom:1rem;}
.footer-links a{color:rgba(255,255,255,0.5);text-decoration:none;font-size:0.85rem;transition:color 0.3s ease;}
.footer-links a:hover{color:#22d3ee;}
.footer-copy{color:rgba(255,255,255,0.3);font-size:0.8rem;}

@media(max-width:480px){.card{padding:2.5rem 1.5rem;}.otp-digit{width:44px;height:54px;font-size:1.4rem;}.otp-inputs{gap:0.5rem;}}
</style>
</head>
<body>
<div class="bg-gradient"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>

<div class="auth-wrapper">
    <div class="card">

        <?php if ($success === 'PASSWORD_RESET_DONE'): ?>
        <!-- ── ✅ Success State ────────────────────────────────── -->
        <div class="success-state">
            <div class="logo"><img src="../assets/images/icon.jpeg" class="logo-icon" alt="Service-Hub"></div>
            <div class="checkmark">🔓</div>
            <h2>Password Reset!</h2>
            <p>Your password has been updated successfully. You can now sign in with your new password.</p>
            <a href="login.php" class="btn-login">Sign in now →</a>
        </div>

        <?php elseif ($otp_verified): ?>
        <!-- ── 🔑 Step 2: Set New Password ──────────────────────── -->
        <div class="logo"><img src="../assets/images/icon.jpeg" class="logo-icon" alt="Service-Hub">Service-Hub</div>

        <!-- Step indicator -->
        <div class="step-indicator" style="margin-top:1.2rem;">
            <div class="step done"><div class="step-dot">✓</div> Verify</div>
            <div class="step-line"></div>
            <div class="step active"><div class="step-dot">2</div> New Password</div>
        </div>

        <div class="icon-wrap">🔑</div>
        <h2>Set new password</h2>
        <p class="subtitle" style="margin-bottom:1.8rem;">Choose a strong password for your account.</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" id="pwForm">
            <input type="hidden" name="action" value="set_password">

            <div class="form-group">
                <label class="form-label">New password</label>
                <div class="pw-wrap">
                    <input type="password" name="new_password" class="form-input" id="newPw"
                           placeholder="At least 6 characters" required minlength="6">
                    <button type="button" class="pw-toggle" onclick="togglePw('newPw','icon1')">
                        <span id="icon1">👁️</span>
                    </button>
                </div>
                <div class="strength-bar-wrap" id="strengthWrap">
                    <div class="strength-bar" id="strengthBar"></div>
                </div>
                <div class="strength-hint" id="strengthHint"></div>
            </div>

            <div class="form-group">
                <label class="form-label">Confirm new password</label>
                <div class="pw-wrap">
                    <input type="password" name="confirm_password" class="form-input" id="confirmPw"
                           placeholder="Re-enter password" required>
                    <button type="button" class="pw-toggle" onclick="togglePw('confirmPw','icon2')">
                        <span id="icon2">👁️</span>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" id="pwBtn">
                <span id="pwBtnText">Update Password →</span>
            </button>
        </form>

        <?php else: ?>
        <!-- ── 📧 Step 1: Enter OTP ─────────────────────────────── -->
        <div class="logo"><img src="../assets/images/icon.jpeg" class="logo-icon" alt="Service-Hub">Service-Hub</div>

        <!-- Step indicator -->
        <div class="step-indicator" style="margin-top:1.2rem;">
            <div class="step active"><div class="step-dot">1</div> Verify</div>
            <div class="step-line"></div>
            <div class="step"><div class="step-dot">2</div> New Password</div>
        </div>

        <div class="icon-wrap">📧</div>
        <h2>Enter reset code</h2>
        <p class="subtitle">We sent a 6-digit code to</p>
        <p class="email-display"><?= htmlspecialchars($masked_email) ?></p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($resend_message): ?>
            <div class="alert <?= str_contains($resend_message,'new code') ? 'alert-info' : 'alert-error' ?>">
                <?= htmlspecialchars($resend_message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="otpForm">
            <input type="hidden" name="action" value="verify_otp">
            <input type="hidden" name="otp" id="otpHidden">

            <div class="otp-inputs" id="otpInputs">
                <input class="otp-digit" type="text" maxlength="1" inputmode="numeric" autocomplete="off" id="d0">
                <input class="otp-digit" type="text" maxlength="1" inputmode="numeric" autocomplete="off" id="d1">
                <input class="otp-digit" type="text" maxlength="1" inputmode="numeric" autocomplete="off" id="d2">
                <input class="otp-digit" type="text" maxlength="1" inputmode="numeric" autocomplete="off" id="d3">
                <input class="otp-digit" type="text" maxlength="1" inputmode="numeric" autocomplete="off" id="d4">
                <input class="otp-digit" type="text" maxlength="1" inputmode="numeric" autocomplete="off" id="d5">
            </div>

            <button type="submit" class="btn btn-primary" id="verifyBtn" disabled>Verify Code</button>
        </form>

        <div class="resend-section">
            <p>Didn't receive the code?</p>
            <div class="resend-row">
                <form method="POST" id="resendForm">
                    <input type="hidden" name="action" value="resend">
                    <button type="submit" class="btn-ghost" id="resendBtn" disabled>🔄 Resend Code</button>
                </form>
                <div class="timer-badge">⏱ <span id="timerCount">2:00</span></div>
            </div>
        </div>

        <a href="forgot_password.php" class="back-link">← Use different email</a>
        <?php endif; ?>

    </div>
</div>

<footer class="auth-footer">
    <div class="footer-links">
        <a href="#">Terms of Service</a>
        <a href="#">Privacy Policy</a>
        <a href="#">Support</a>
    </div>
    <div class="footer-copy">© 2025 Service-Hub. All rights reserved.</div>
</footer>

<script>
// ── OTP Digit Inputs ─────────────────────────────────────────────────────────
const digits    = Array.from(document.querySelectorAll('.otp-digit') || []);
const hidden    = document.getElementById('otpHidden');
const verifyBtn = document.getElementById('verifyBtn');

if (digits.length) {
    function syncOtp() {
        const otp = digits.map(d => d.value).join('');
        if (hidden) hidden.value = otp;
        if (verifyBtn) verifyBtn.disabled = otp.length !== 6;
        digits.forEach(d => d.classList.toggle('filled', d.value !== ''));
    }

    digits.forEach((digit, idx) => {
        digit.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/\D/g, '')[0] || '';
            syncOtp();
            if (e.target.value && idx < 5) digits[idx + 1].focus();
        });
        digit.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !digit.value && idx > 0) {
                digits[idx - 1].value = '';
                digits[idx - 1].focus();
                syncOtp();
            }
        });
        digit.addEventListener('paste', (e) => {
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData)
                           .getData('text').replace(/\D/g,'').slice(0,6);
            pasted.split('').forEach((ch, i) => { if (digits[i]) digits[i].value = ch; });
            syncOtp();
            const nxt = digits.find(d => !d.value);
            (nxt || digits[5]).focus();
        });
    });
    if (digits[0]) digits[0].focus();

    <?php if ($error && !$otp_verified): ?>
    digits.forEach(d => {
        d.classList.add('error-shake');
        d.addEventListener('animationend', () => d.classList.remove('error-shake'), {once:true});
    });
    <?php endif; ?>

    // Countdown timer
    const resendBtn  = document.getElementById('resendBtn');
    const timerCount = document.getElementById('timerCount');
    let seconds = 120;
    function tick() {
        const m = Math.floor(seconds/60), s = seconds%60;
        timerCount.textContent = `${m}:${String(s).padStart(2,'0')}`;
    }
    tick();
    const countdown = setInterval(() => {
        seconds--;
        tick();
        if (seconds <= 0) {
            clearInterval(countdown);
            if (resendBtn) resendBtn.disabled = false;
            timerCount.style.color = '#f59e0b';
        }
    }, 1000);

    document.getElementById('otpForm')?.addEventListener('submit', function() {
        if (verifyBtn) { verifyBtn.disabled = true; verifyBtn.textContent = 'Verifying…'; }
    });
}

// ── Password Toggle ──────────────────────────────────────────────────────────
function togglePw(inputId, iconId) {
    const inp  = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (!inp) return;
    if (inp.type === 'password') { inp.type = 'text'; icon.textContent = '🙈'; }
    else { inp.type = 'password'; icon.textContent = '👁️'; }
}

// ── Password Strength ────────────────────────────────────────────────────────
const newPw       = document.getElementById('newPw');
const strengthWrap = document.getElementById('strengthWrap');
const strengthBar  = document.getElementById('strengthBar');
const strengthHint = document.getElementById('strengthHint');

if (newPw) {
    newPw.addEventListener('input', function() {
        const pw = this.value;
        if (!pw.length) { strengthWrap.classList.remove('active'); strengthHint.textContent=''; return; }
        strengthWrap.classList.add('active');
        let s = 0;
        if (pw.length >= 6)  s++;
        if (pw.length >= 10) s++;
        if (/[a-z]/.test(pw) && /[A-Z]/.test(pw)) s++;
        if (/\d/.test(pw))  s++;
        if (/[^a-zA-Z0-9]/.test(pw)) s++;
        strengthBar.className = 'strength-bar';
        if (s <= 2) { strengthBar.classList.add('s-weak');   strengthHint.textContent='Weak';   strengthHint.style.color='#ef4444'; }
        else if (s <= 4) { strengthBar.classList.add('s-medium'); strengthHint.textContent='Medium'; strengthHint.style.color='#f59e0b'; }
        else { strengthBar.classList.add('s-strong'); strengthHint.textContent='Strong'; strengthHint.style.color='#22c55e'; }
    });
}

// Loading on pw form submit
document.getElementById('pwForm')?.addEventListener('submit', function() {
    const btn  = document.getElementById('pwBtn');
    const text = document.getElementById('pwBtnText');
    if (btn) { btn.disabled = true; text.textContent = 'Updating…'; btn.style.opacity = '0.75'; }
});

// ── Card Tilt ────────────────────────────────────────────────────────────────
const card = document.querySelector('.card');
document.addEventListener('mousemove', (e) => {
    const x = (e.clientX / window.innerWidth  - 0.5) * 8;
    const y = (e.clientY / window.innerHeight - 0.5) * 8;
    card.style.transform = `perspective(1000px) rotateY(${x}deg) rotateX(${-y}deg)`;
});
document.addEventListener('mouseleave', () => {
    card.style.transform = 'perspective(1000px) rotateY(0deg) rotateX(0deg)';
});
</script>
</body>
</html>
