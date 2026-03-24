<?php
session_start();
require_once dirname(__DIR__) . "/config/db.php";
require_once dirname(__DIR__) . "/config/mailer.php";

$error   = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check user exists — also fetch role to block admin resets
        $stmt = $conn->prepare("SELECT user_id, name, role FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($user_id, $name, $role);
        $found = $stmt->fetch();
        $stmt->close();

        if (!$found || $role === 'admin') {
            // Generic message — prevents email enumeration AND silently blocks admin accounts
            $success = "If that email is registered, a reset code has been sent.";
        } else {
            // Generate OTP and store in email_otps
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

            // Store email in session for the reset page
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_name']  = $name;

            $sent = sendOtpEmail($email, $name, $otp);

            if ($sent) {
                header("Location: reset_password.php");
                exit();
            } else {
                $error = "Failed to send reset email. Please try again.";
                unset($_SESSION['reset_email'], $_SESSION['reset_name']);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Service-Hub | Forgot Password</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Reset your Service-Hub account password via email verification.">
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
@keyframes floatGradient {
    0%,100%{transform:translate(0,0) rotate(0deg);}
    33%    {transform:translate(30px,-30px) rotate(120deg);}
    66%    {transform:translate(-20px,20px) rotate(240deg);}
}
.orb {position:fixed;border-radius:50%;filter:blur(80px);opacity:0.3;animation:float 15s ease-in-out infinite;z-index:0;}
.orb-1{width:400px;height:400px;background:linear-gradient(135deg,#6366f1,#a855f7);top:-200px;right:-200px;}
.orb-2{width:350px;height:350px;background:linear-gradient(135deg,#22d3ee,#06b6d4);bottom:-150px;left:-150px;animation-delay:5s;}
@keyframes float{0%,100%{transform:translate(0,0);}50%{transform:translate(50px,-50px);}}

.auth-wrapper {
    flex:1; display:flex; align-items:center; justify-content:center;
    padding:2rem; position:relative; z-index:1;
}

.card {
    width:100%; max-width:460px;
    background:rgba(255,255,255,0.03);
    backdrop-filter:blur(30px) saturate(180%);
    border:1px solid rgba(255,255,255,0.1);
    border-radius:24px; padding:3rem 2.5rem;
    box-shadow:0 40px 80px rgba(0,0,0,0.4);
    animation:fadeUp 0.8s ease-out; position:relative; text-align:center;
}
.card::before {
    content:''; position:absolute; top:0; left:0; right:0; height:4px;
    background:linear-gradient(90deg,#6366f1,#a855f7,#22d3ee);
    border-radius:24px 24px 0 0;
}
@keyframes fadeUp{from{opacity:0;transform:translateY(40px);}to{opacity:1;transform:translateY(0);}}

.logo {
    display:inline-flex; align-items:center; gap:0.5rem;
    font-size:1.6rem; font-weight:700; margin-bottom:0.5rem;
    background:linear-gradient(135deg,#22d3ee,#a855f7);
    -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;
}
.logo-icon{font-size:1.8rem;filter:drop-shadow(0 0 20px rgba(34,211,238,0.6));}

.icon-wrap {
    width:76px; height:76px; border-radius:50%;
    background:linear-gradient(135deg,rgba(99,102,241,0.2),rgba(168,85,247,0.2));
    border:2px solid rgba(99,102,241,0.3);
    display:flex; align-items:center; justify-content:center;
    font-size:2rem; margin:1.5rem auto 1.2rem;
    animation:pulse 2.5s ease-in-out infinite;
}
@keyframes pulse{0%,100%{box-shadow:0 0 0 0 rgba(99,102,241,0.35);}50%{box-shadow:0 0 0 14px rgba(99,102,241,0);}}

.card h2 {color:#fff;font-size:1.4rem;font-weight:700;margin-bottom:0.4rem;}
.card .subtitle{color:rgba(255,255,255,0.55);font-size:0.9rem;line-height:1.65;margin-bottom:2rem;}

/* Alerts */
.alert{
    padding:0.9rem 1.1rem; border-radius:12px; margin-bottom:1.5rem;
    font-size:0.88rem; display:flex; align-items:center; gap:0.5rem;
    animation:slideDown 0.3s ease; text-align:left;
}
@keyframes slideDown{from{opacity:0;transform:translateY(-8px);}to{opacity:1;transform:translateY(0);}}
.alert-error  {background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.3); color:#fca5a5;}
.alert-success{background:rgba(34,197,94,0.1); border:1px solid rgba(34,197,94,0.3); color:#86efac;}
.alert-error::before  {content:'⚠';font-size:1.1rem;flex-shrink:0;}
.alert-success::before{content:'✓';font-size:1.1rem;flex-shrink:0;}

/* Form */
.form-group{margin-bottom:1.5rem;text-align:left;}
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
    border-color:rgba(99,102,241,0.5);
    box-shadow:0 0 0 4px rgba(99,102,241,0.1);
}
.form-input::placeholder{color:rgba(255,255,255,0.4);}

/* Button */
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
.btn-primary:disabled{opacity:0.65;cursor:not-allowed;transform:none;}

.back-link{
    display:inline-flex;align-items:center;gap:0.4rem;
    color:rgba(255,255,255,0.45);font-size:0.85rem;text-decoration:none;
    margin-top:1.5rem;transition:color 0.2s ease;
}
.back-link:hover{color:#22d3ee;}

/* Footer */
.auth-footer{text-align:center;padding:2rem 1rem;position:relative;z-index:1;}
.footer-links{display:flex;justify-content:center;gap:1.5rem;flex-wrap:wrap;margin-bottom:1rem;}
.footer-links a{color:rgba(255,255,255,0.5);text-decoration:none;font-size:0.85rem;transition:color 0.3s ease;}
.footer-links a:hover{color:#22d3ee;}
.footer-copy{color:rgba(255,255,255,0.3);font-size:0.8rem;}

@media(max-width:480px){.card{padding:2.5rem 1.5rem;}}
</style>
</head>
<body>
<div class="bg-gradient"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>

<div class="auth-wrapper">
    <div class="card">

        <div class="logo"><span class="logo-icon">🏠</span>Service-Hub</div>

        <div class="icon-wrap">🔐</div>
        <h2>Forgot your password?</h2>
        <p class="subtitle">Enter your registered email and we'll send you a 6-digit reset code.</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" id="forgotForm">
            <div class="form-group">
                <label class="form-label">Email address</label>
                <input type="email" name="email" id="emailInput" class="form-input"
                       placeholder="you@example.com" required
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <button type="submit" class="btn btn-primary" id="submitBtn">
                <span id="submitText">Send Reset Code →</span>
            </button>
        </form>

        <a href="login.php" class="back-link">← Back to sign in</a>
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
document.getElementById('forgotForm').addEventListener('submit', function () {
    const btn  = document.getElementById('submitBtn');
    const text = document.getElementById('submitText');
    btn.disabled     = true;
    text.textContent = 'Sending…';
    btn.style.opacity = '0.75';
});

// Floating card tilt
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
