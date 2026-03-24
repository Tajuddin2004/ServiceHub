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
* { margin:0; padding:0; box-sizing:border-box; }

body {
    font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif;
    min-height:100vh;
    background:#0a0a0f;
    display:flex; flex-direction:column;
    overflow-x:hidden;
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
@keyframes floatGradient {
    0%,100%{ transform:translate(0,0) rotate(0deg); }
    33%    { transform:translate(30px,-30px) rotate(120deg); }
    66%    { transform:translate(-20px,20px) rotate(240deg); }
}
.orb { position:fixed; border-radius:50%; filter:blur(80px); opacity:0.3; animation:float 15s ease-in-out infinite; z-index:0; }
.orb-1 { width:400px; height:400px; background:linear-gradient(135deg,#6366f1,#a855f7); top:-200px; right:-200px; }
.orb-2 { width:350px; height:350px; background:linear-gradient(135deg,#22d3ee,#06b6d4); bottom:-150px; left:-150px; animation-delay:5s; }
@keyframes float { 0%,100%{transform:translate(0,0);} 50%{transform:translate(50px,-50px);} }

.auth-wrapper {
    flex:1; display:flex; align-items:center; justify-content:center;
    padding:2rem; position:relative; z-index:1;
}

/* OTP Card */
.otp-card {
    width:100%; max-width:460px;
    background:rgba(255,255,255,0.03);
    backdrop-filter:blur(30px) saturate(180%);
    border:1px solid rgba(255,255,255,0.1);
    border-radius:24px;
    padding:3rem 2.5rem;
    box-shadow:0 40px 80px rgba(0,0,0,0.4);
    animation:fadeUp 0.8s ease-out;
    position:relative;
    text-align:center;
}
.otp-card::before {
    content:''; position:absolute; top:0; left:0; right:0; height:4px;
    background:linear-gradient(90deg,#6366f1,#a855f7,#22d3ee);
    border-radius:24px 24px 0 0;
}
@keyframes fadeUp {
    from{opacity:0;transform:translateY(40px);}
    to  {opacity:1;transform:translateY(0);}
}

.logo { display:flex; align-items:center; justify-content:center; gap:0.5rem; font-size:1.6rem; font-weight:700; margin-bottom:0.5rem; }
.logo-text { background:linear-gradient(135deg,#22d3ee,#a855f7); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
.logo-icon { font-size:1.8rem; filter:drop-shadow(0 0 20px rgba(34,211,238,0.6)); }

/* Email icon animation */
.email-icon-wrap {
    width:80px; height:80px; border-radius:50%;
    background:linear-gradient(135deg,rgba(99,102,241,0.2),rgba(168,85,247,0.2));
    border:2px solid rgba(99,102,241,0.3);
    display:flex; align-items:center; justify-content:center;
    font-size:2.2rem; margin:1.5rem auto 1.2rem;
    animation:pulse 2.5s ease-in-out infinite;
}
@keyframes pulse {
    0%,100%{ box-shadow:0 0 0 0 rgba(99,102,241,0.3); }
    50%    { box-shadow:0 0 0 14px rgba(99,102,241,0); }
}

.otp-card h2 { color:#fff; font-size:1.4rem; font-weight:700; margin-bottom:0.4rem; }
.otp-card .subtitle { color:rgba(255,255,255,0.55); font-size:0.9rem; line-height:1.6; margin-bottom:0.3rem; }
.email-display { color:#22d3ee; font-weight:600; font-size:0.92rem; margin-bottom:2rem; }

/* Alerts */
.alert {
    padding:0.9rem 1.1rem; border-radius:12px; margin-bottom:1.4rem;
    font-size:0.88rem; display:flex; align-items:center; gap:0.5rem;
    animation:slideDown 0.3s ease; text-align:left;
}
@keyframes slideDown { from{opacity:0;transform:translateY(-8px);} to{opacity:1;transform:translateY(0);} }
.alert-error   { background:rgba(239,68,68,0.1);  border:1px solid rgba(239,68,68,0.3);  color:#fca5a5; }
.alert-success { background:rgba(34,197,94,0.1);  border:1px solid rgba(34,197,94,0.3);  color:#86efac; }
.alert-info    { background:rgba(34,211,238,0.08);border:1px solid rgba(34,211,238,0.25);color:#67e8f9; }
.alert-error::before   { content:'⚠'; font-size:1.1rem; flex-shrink:0; }
.alert-success::before { content:'✓'; font-size:1.1rem; flex-shrink:0; }
.alert-info::before    { content:'ℹ'; font-size:1.1rem; flex-shrink:0; }

/* OTP digit inputs */
.otp-inputs {
    display:flex; gap:0.7rem; justify-content:center;
    margin-bottom:1.8rem;
}
.otp-digit {
    width:52px; height:60px;
    background:rgba(255,255,255,0.05);
    border:2px solid rgba(255,255,255,0.12);
    border-radius:14px;
    color:#ffffff; font-size:1.6rem; font-weight:700;
    text-align:center; font-family:inherit;
    transition:all 0.2s ease;
    caret-color: transparent;
}
.otp-digit:focus {
    outline:none;
    background:rgba(99,102,241,0.12);
    border-color:#6366f1;
    box-shadow:0 0 0 4px rgba(99,102,241,0.15);
    transform:scale(1.05);
}
.otp-digit.filled {
    border-color:rgba(99,102,241,0.5);
    background:rgba(99,102,241,0.08);
}
.otp-digit.error-shake {
    border-color:#ef4444;
    animation:shake 0.4s ease;
}
@keyframes shake {
    0%,100%{ transform:translateX(0); }
    20%    { transform:translateX(-6px); }
    40%    { transform:translateX(6px); }
    60%    { transform:translateX(-4px); }
    80%    { transform:translateX(4px); }
}

/* Hidden real input */
#otpHidden { display:none; }

/* Buttons */
.btn {
    width:100%; padding:1rem; border:none; border-radius:12px;
    font-size:1rem; font-weight:600; cursor:pointer;
    transition:all 0.3s ease; position:relative; overflow:hidden; font-family:inherit;
}
.btn-primary {
    background:linear-gradient(135deg,#6366f1,#a855f7);
    color:#fff; box-shadow:0 10px 30px rgba(99,102,241,0.3);
    margin-bottom:1rem;
}
.btn-primary::before { content:''; position:absolute; top:0; left:-100%; width:100%; height:100%; background:linear-gradient(90deg,transparent,rgba(255,255,255,0.2),transparent); transition:left 0.5s ease; }
.btn-primary:hover::before { left:100%; }
.btn-primary:hover  { transform:translateY(-2px); box-shadow:0 15px 40px rgba(99,102,241,0.4); }
.btn-primary:active { transform:translateY(0); }
.btn-primary:disabled { opacity:0.6; cursor:not-allowed; transform:none; }

/* Resend section */
.resend-section { margin-top:1rem; }
.resend-section p { color:rgba(255,255,255,0.5); font-size:0.88rem; margin-bottom:0.6rem; }
.resend-btn-wrap { display:flex; gap:0.8rem; align-items:center; justify-content:center; }
.btn-ghost {
    background:transparent;
    border:1px solid rgba(255,255,255,0.15);
    color:rgba(255,255,255,0.7);
    padding:0.65rem 1.4rem;
    border-radius:10px; font-size:0.88rem; font-weight:500;
    cursor:pointer; font-family:inherit; transition:all 0.25s ease;
    width:auto;
}
.btn-ghost:hover { border-color:rgba(99,102,241,0.5); color:#fff; background:rgba(99,102,241,0.08); }

/* Timer badge */
.timer-badge {
    display:inline-flex; align-items:center; gap:0.3rem;
    background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1);
    border-radius:20px; padding:0.35rem 0.9rem;
    color:rgba(255,255,255,0.5); font-size:0.82rem;
}
#timerCount { color:#22d3ee; font-weight:600; }

/* Back link */
.back-link {
    display:inline-flex; align-items:center; gap:0.4rem;
    color:rgba(255,255,255,0.45); font-size:0.85rem; text-decoration:none;
    margin-top:1.5rem; transition:color 0.2s ease;
}
.back-link:hover { color:#22d3ee; }

/* ── Success State ──────────────────────────────────────────────────────── */
.success-state { display:none; }
.success-state.show { display:block; animation:fadeUp 0.6s ease-out; }
.checkmark-circle {
    width:80px; height:80px; border-radius:50%;
    background:linear-gradient(135deg,#22c55e,#16a34a);
    display:flex; align-items:center; justify-content:center;
    font-size:2.2rem; margin:1.5rem auto 1.2rem;
    box-shadow:0 8px 30px rgba(34,197,94,0.35);
    animation:bounceIn 0.6s ease-out;
}
@keyframes bounceIn {
    0%  { transform:scale(0); }
    60% { transform:scale(1.15); }
    100%{ transform:scale(1); }
}
.success-state h2 { color:#fff; font-size:1.4rem; margin-bottom:0.5rem; }
.success-state p  { color:rgba(255,255,255,0.6); font-size:0.93rem; line-height:1.65; margin-bottom:1.5rem; }
.btn-login {
    display:inline-flex; align-items:center; gap:0.5rem;
    background:linear-gradient(135deg,#22c55e,#16a34a);
    color:#fff; padding:0.9rem 2rem; border-radius:12px;
    text-decoration:none; font-weight:600; font-size:0.95rem;
    transition:all 0.3s ease; box-shadow:0 8px 25px rgba(34,197,94,0.3);
}
.btn-login:hover { transform:translateY(-2px); box-shadow:0 12px 35px rgba(34,197,94,0.4); }

@media(max-width:480px) {
    .otp-card { padding:2.5rem 1.5rem; }
    .otp-digit { width:44px; height:54px; font-size:1.4rem; }
    .otp-inputs { gap:0.5rem; }
}
</style>
</head>

<body>
<div class="bg-gradient"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>

<div class="auth-wrapper">
    <div class="otp-card">

        <!-- ── Success State ────────────────────────────────────────────── -->
        <?php if ($success): ?>
        <div class="success-state show">
            <div class="logo">
                <span class="logo-icon">🏠</span>
                <span class="logo-text">Service-Hub</span>
            </div>
            <div class="checkmark-circle">✅</div>
            <h2>Email Verified!</h2>
            <p><?= htmlspecialchars($success) ?></p>
            <a href="login.php" class="btn-login">
                Sign in to your account →
            </a>
        </div>

        <?php else: ?>
        <!-- ── OTP Entry State ───────────────────────────────────────────── -->
        <div class="logo">
            <span class="logo-icon">🏠</span>
            <span class="logo-text">Service-Hub</span>
        </div>

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

        <!-- Resend Form -->
        <div class="resend-section">
            <p>Didn't receive the code?</p>
            <div class="resend-btn-wrap">
                <form method="POST" id="resendForm">
                    <input type="hidden" name="action" value="resend">
                    <button type="submit" class="btn-ghost" id="resendBtn" disabled>🔄 Resend Code</button>
                </form>
                <div class="timer-badge">⏱ <span id="timerCount">2:00</span></div>
            </div>
        </div>

        <a href="register.php" class="back-link">← Back to registration</a>
        <?php endif; ?>

    </div>
</div>

<script>
// ── OTP digit input logic ────────────────────────────────────────────────────
const digits    = Array.from(document.querySelectorAll('.otp-digit'));
const hidden    = document.getElementById('otpHidden');
const verifyBtn = document.getElementById('verifyBtn');

function getOtp() { return digits.map(d => d.value).join(''); }

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
        const val = e.target.value.replace(/\D/g,'');
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
        // Allow paste handling
    });

    digit.addEventListener('paste', (e) => {
        e.preventDefault();
        const pasted = (e.clipboardData || window.clipboardData)
                        .getData('text').replace(/\D/g,'').slice(0, 6);
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

// ── Countdown timer for resend ───────────────────────────────────────────────
const resendBtn  = document.getElementById('resendBtn');
const timerCount = document.getElementById('timerCount');

let seconds = 120; // 2 minutes

function updateTimer() {
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    timerCount.textContent = `${m}:${String(s).padStart(2,'0')}`;
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

// ── Loading state on verify submit ───────────────────────────────────────────
document.getElementById('otpForm').addEventListener('submit', function() {
    verifyBtn.disabled = true;
    verifyBtn.textContent = 'Verifying…';
});
</script>
</body>
</html>
