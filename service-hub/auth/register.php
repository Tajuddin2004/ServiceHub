<?php
session_start();
require_once dirname(__DIR__) . "/config/db.php";
require_once dirname(__DIR__) . "/config/mailer.php";

$error   = "";
$success = "";

// Fetch professions from services table
$professionsQuery  = "SELECT service_name FROM services ORDER BY service_name ASC";
$professionsResult = $conn->query($professionsQuery);
$professions       = [];
if ($professionsResult) {
    while ($row = $professionsResult->fetch_assoc()) {
        $professions[] = $row['service_name'];
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';
    $role     = $_POST['role']          ?? 'consumer';

    // ── Provider-specific field validation ──────────────────────────────
    if ($role === 'provider') {
        $age        = $_POST['age']        ?? '';
        $profession = $_POST['profession'] ?? '';
        $about_work = trim($_POST['about_work'] ?? '');
        $experience = $_POST['experience'] ?? '';
        $pincode    = trim($_POST['pincode'] ?? '');
        $phone      = trim($_POST['phone']  ?? '');

        if (empty($age) || empty($profession) || empty($about_work) ||
            empty($experience) || empty($pincode) || empty($phone)) {
            $error = "All fields are required for employee registration.";
        } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
            $error = "Please enter a valid 10-digit phone number.";
        } elseif (!preg_match('/^[0-9]{6}$/', $pincode)) {
            $error = "Please enter a valid 6-digit pincode.";
        }
    }

    // ── Common field validation ──────────────────────────────────────────
    if (empty($error) && ($name === '' || $email === '' || $password === '')) {
        $error = "All fields are required.";
    } elseif (empty($error) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (empty($error) && strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    }

    // ── Check for duplicate email ────────────────────────────────────────
    if (empty($error)) {
        $check = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $error = "Email already registered. Please sign in.";
        }
        $check->close();
    }

    // ── Generate OTP and store pending registration in session ───────────
    if (empty($error)) {
        $otp        = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        // Upsert OTP into email_otps table
        $stmt = $conn->prepare(
            "INSERT INTO email_otps (email, otp, expires_at)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE otp = VALUES(otp), expires_at = VALUES(expires_at), created_at = NOW()"
        );
        $stmt->bind_param("sss", $email, $otp, $otp_expiry);
        $stmt->execute();
        $stmt->close();

        // Store entire form data in session for use after OTP verification
        $_SESSION['pending_registration'] = [
            'name'       => $name,
            'email'      => $email,
            'password'   => password_hash($password, PASSWORD_DEFAULT),
            'role'       => $role,
            // Provider fields (null-safe)
            'age'        => $role === 'provider' ? ($_POST['age']        ?? null) : null,
            'profession' => $role === 'provider' ? ($_POST['profession'] ?? null) : null,
            'about_work' => $role === 'provider' ? ($_POST['about_work'] ?? null) : null,
            'experience' => $role === 'provider' ? ($_POST['experience'] ?? null) : null,
            'pincode'    => $role === 'provider' ? ($_POST['pincode']    ?? null) : null,
            'phone'      => $role === 'provider' ? ($_POST['phone']      ?? null) : null,
        ];

        // Send OTP email
        $sent = sendOtpEmail($email, $name, $otp);

        if ($sent) {
            header("Location: verify_otp.php");
            exit();
        } else {
            $error = "Failed to send verification email. Please check your mailer configuration.";
            // Clean up session if email failed
            unset($_SESSION['pending_registration']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Service-Hub | Sign Up</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Create your Service-Hub account to book trusted home services or register as a service provider.">
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
    top: 0; left: 0;
    width: 100%; height: 100%;
    z-index: -1;
    background: linear-gradient(135deg, #0a0a0f 0%, #1a1a2e 50%, #0a0a0f 100%);
}

.bg-gradient::before {
    content: '';
    position: absolute;
    top: -50%; left: -50%;
    width: 200%; height: 200%;
    background:
        radial-gradient(circle at 50% 50%, rgba(99,102,241,0.2) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(168,85,247,0.2) 0%, transparent 50%),
        radial-gradient(circle at 20% 80%, rgba(34,211,238,0.2) 0%, transparent 50%);
    animation: floatGradient 20s ease-in-out infinite;
}

@keyframes floatGradient {
    0%,100% { transform: translate(0,0) rotate(0deg); }
    33%      { transform: translate(30px,-30px) rotate(120deg); }
    66%      { transform: translate(-20px,20px) rotate(240deg); }
}

.orb { position: fixed; border-radius: 50%; filter: blur(80px); opacity: 0.3; animation: float 15s ease-in-out infinite; z-index: 0; }
.orb-1 { width:400px; height:400px; background:linear-gradient(135deg,#6366f1,#a855f7); top:-200px; right:-200px; animation-delay:0s; }
.orb-2 { width:350px; height:350px; background:linear-gradient(135deg,#22d3ee,#06b6d4); bottom:-150px; left:-150px; animation-delay:5s; }

@keyframes float {
    0%,100% { transform: translate(0,0); }
    50%      { transform: translate(50px,-50px); }
}

.auth-wrapper {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    position: relative;
    z-index: 1;
}

.register-card {
    width: 100%;
    max-width: 550px;
    background: rgba(255,255,255,0.03);
    backdrop-filter: blur(30px) saturate(180%);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 24px;
    padding: 3rem 2.5rem;
    box-shadow: 0 40px 80px rgba(0,0,0,0.4);
    animation: fadeUp 0.8s ease-out;
    position: relative;
}

.register-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 4px;
    background: linear-gradient(90deg, #6366f1, #a855f7, #22d3ee);
    border-radius: 24px 24px 0 0;
}

@keyframes fadeUp {
    from { opacity:0; transform:translateY(40px); }
    to   { opacity:1; transform:translateY(0); }
}

.register-header { text-align: center; margin-bottom: 2rem; }

.logo { display:flex; align-items:center; justify-content:center; gap:0.5rem; font-size:1.8rem; font-weight:700; margin-bottom:0.5rem; width:100%; }
.logo-text { background:linear-gradient(135deg,#22d3ee,#a855f7); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
.logo-icon { font-size:2rem; filter:drop-shadow(0 0 20px rgba(34,211,238,0.6)); background:none; -webkit-background-clip:unset; -webkit-text-fill-color:unset; background-clip:unset; }

.register-header h2 { color:#fff; font-size:1.5rem; font-weight:600; margin-bottom:0.5rem; }
.register-header p  { color:rgba(255,255,255,0.6); font-size:0.95rem; }

/* Role Toggle */
.role-toggle {
    display:flex; gap:0.8rem;
    margin-bottom:2rem;
    background:rgba(255,255,255,0.03);
    padding:0.4rem;
    border-radius:12px;
    border:1px solid rgba(255,255,255,0.08);
    position:relative;
}
.role-toggle::before {
    content:''; position:absolute;
    top:0.4rem; left:0.4rem;
    width:calc(50% - 0.4rem);
    height:calc(100% - 0.8rem);
    background:linear-gradient(135deg,#6366f1,#a855f7);
    border-radius:10px;
    transition:transform 0.3s cubic-bezier(0.4,0,0.2,1);
    box-shadow:0 4px 15px rgba(99,102,241,0.3);
    z-index:0;
}
.role-toggle.provider-active::before { transform:translateX(calc(100% + 0.8rem)); }

.role-btn {
    flex:1; padding:0.9rem 1.5rem;
    border:none; border-radius:10px;
    background:transparent; color:rgba(255,255,255,0.6);
    font-size:0.95rem; font-weight:600; cursor:pointer;
    transition:color 0.3s ease; font-family:inherit;
    position:relative; z-index:1;
}
.role-btn:hover  { color:rgba(255,255,255,0.9); }
.role-btn.active { color:#ffffff; }

/* Alerts */
.alert {
    padding:1rem 1.2rem; border-radius:12px;
    margin-bottom:1.5rem; font-size:0.9rem;
    display:flex; align-items:center; gap:0.5rem;
    animation:slideDown 0.3s ease;
}
@keyframes slideDown {
    from { opacity:0; transform:translateY(-10px); }
    to   { opacity:1; transform:translateY(0); }
}
.alert-error   { background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.3); color:#fca5a5; }
.alert-success { background:rgba(34,197,94,0.1);  border:1px solid rgba(34,197,94,0.3);  color:#86efac; }
.alert::before         { content:'⚠'; font-size:1.2rem; }
.alert-success::before { content:'✓'; }

/* Provider Fields */
#providerFields {
    overflow:hidden; max-height:0; opacity:0;
    transition:max-height 0.5s cubic-bezier(0.4,0,0.2,1), opacity 0.4s ease, margin-top 0.5s ease;
    margin-top:0;
}
#providerFields.show { max-height:2000px; opacity:1; }
#providerFields .form-group { transform:translateY(-10px); opacity:0; transition:transform 0.4s ease, opacity 0.4s ease; }
#providerFields.show .form-group { transform:translateY(0); opacity:1; }
#providerFields.show .form-group:nth-child(1) { transition-delay:0.10s; }
#providerFields.show .form-group:nth-child(2) { transition-delay:0.15s; }
#providerFields.show .form-group:nth-child(3) { transition-delay:0.20s; }
#providerFields.show .form-group:nth-child(4) { transition-delay:0.25s; }
#providerFields.show .form-group:nth-child(5) { transition-delay:0.30s; }
#providerFields.show .form-group:nth-child(6) { transition-delay:0.35s; }

/* Form */
.form-group  { margin-bottom:1.5rem; }
.form-label  { display:block; margin-bottom:0.5rem; color:rgba(255,255,255,0.8); font-size:0.9rem; font-weight:500; transition:color 0.3s ease; }

.form-input, select.form-input, textarea.form-input {
    width:100%; padding:1rem 1.2rem;
    background:rgba(255,255,255,0.05);
    border:1px solid rgba(255,255,255,0.1);
    border-radius:12px; color:#ffffff;
    font-size:1rem; transition:all 0.3s ease; font-family:inherit;
}
.form-input:focus, select.form-input:focus, textarea.form-input:focus {
    outline:none;
    background:rgba(255,255,255,0.08);
    border-color:rgba(99,102,241,0.5);
    box-shadow:0 0 0 4px rgba(99,102,241,0.1);
}
.form-input::placeholder, textarea.form-input::placeholder { color:rgba(255,255,255,0.4); }
select.form-input         { cursor:pointer; }
select.form-input option  { background:#1a1a2e; color:#fff; }
textarea.form-input       { resize:vertical; min-height:80px; }

/* Password wrapper */
.password-wrapper  { position:relative; }
.password-toggle   { position:absolute; right:1.2rem; top:50%; transform:translateY(-50%); background:none; border:none; color:rgba(255,255,255,0.5); cursor:pointer; font-size:1.2rem; transition:color 0.3s ease; padding:0; }
.password-toggle:hover { color:rgba(255,255,255,0.8); }

/* Phone input */
.phone-flex   { display:flex; gap:0.5rem; }
.phone-prefix { max-width:80px; background:rgba(255,255,255,0.03); cursor:not-allowed; }

/* Password strength */
.password-strength { margin-top:0.5rem; height:4px; background:rgba(255,255,255,0.1); border-radius:4px; overflow:hidden; opacity:0; transition:opacity 0.3s ease; }
.password-strength.active { opacity:1; }
.strength-bar { height:100%; width:0; transition:width 0.3s ease, background 0.3s ease; border-radius:4px; }
.strength-weak   { width:33%; background:#ef4444; }
.strength-medium { width:66%; background:#f59e0b; }
.strength-strong { width:100%; background:#22c55e; }
.strength-text { font-size:0.75rem; color:rgba(255,255,255,0.6); margin-top:0.3rem; }

/* Button */
.btn { width:100%; padding:1rem; border:none; border-radius:12px; font-size:1rem; font-weight:600; cursor:pointer; transition:all 0.3s ease; position:relative; overflow:hidden; }
.btn-primary { background:linear-gradient(135deg,#6366f1,#a855f7); color:#fff; box-shadow:0 10px 30px rgba(99,102,241,0.3); }
.btn-primary::before { content:''; position:absolute; top:0; left:-100%; width:100%; height:100%; background:linear-gradient(90deg,transparent,rgba(255,255,255,0.2),transparent); transition:left 0.5s ease; }
.btn-primary:hover::before { left:100%; }
.btn-primary:hover  { transform:translateY(-2px); box-shadow:0 15px 40px rgba(99,102,241,0.4); }
.btn-primary:active { transform:translateY(0); }

/* Links */
.auth-link { text-align:center; margin-top:1.5rem; color:rgba(255,255,255,0.6); font-size:0.95rem; }
.auth-link a { color:#22d3ee; text-decoration:none; font-weight:600; transition:color 0.3s ease; }
.auth-link a:hover { color:#a855f7; text-decoration:underline; }

/* Footer */
.auth-footer { text-align:center; padding:2rem 1rem; position:relative; z-index:1; }
.footer-links { display:flex; justify-content:center; gap:1.5rem; flex-wrap:wrap; margin-bottom:1rem; }
.footer-links a { color:rgba(255,255,255,0.5); text-decoration:none; font-size:0.85rem; transition:color 0.3s ease; }
.footer-links a:hover { color:#22d3ee; }
.footer-copy { color:rgba(255,255,255,0.3); font-size:0.8rem; }

@media (max-width:576px) {
    .register-card { padding:2.5rem 2rem; }
    .auth-wrapper  { padding:1.5rem; }
    .orb-1,.orb-2  { width:300px; height:300px; }
}
</style>
</head>

<body>
<div class="bg-gradient"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>

<div class="auth-wrapper">
    <div class="register-card">
        <div class="register-header">
            <div class="logo">
                <span class="logo-icon">🏠</span>
                <span class="logo-text">Service-Hub</span>
            </div>
            <h2>Create your account</h2>
            <p>Start booking trusted home services today</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" id="registerForm">
            <!-- Role Toggle -->
            <div class="role-toggle" id="roleToggle">
                <button type="button" class="role-btn active" data-role="consumer">👤 Consumer</button>
                <button type="button" class="role-btn"        data-role="provider">👷 Employee</button>
            </div>

            <input type="hidden" name="role" id="role" value="consumer">

            <!-- Common Fields -->
            <div class="form-group">
                <label class="form-label">Full name</label>
                <input type="text" name="name" class="form-input" placeholder="John Doe" required>
            </div>

            <div class="form-group">
                <label class="form-label">Email address</label>
                <input type="email" name="email" class="form-input" placeholder="you@example.com" required>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="password-wrapper">
                    <input type="password" name="password" class="form-input" id="password"
                           placeholder="At least 6 characters" required minlength="6">
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        <span id="toggleIcon">👁️</span>
                    </button>
                </div>
                <div class="password-strength" id="strengthBar">
                    <div class="strength-bar" id="strengthIndicator"></div>
                </div>
                <div class="strength-text" id="strengthText"></div>
            </div>

            <!-- Provider-Only Fields -->
            <div id="providerFields">
                <div class="form-group">
                    <label class="form-label">Age</label>
                    <input type="number" name="age" id="age" class="form-input" min="18" max="65" placeholder="25">
                </div>

                <div class="form-group">
                    <label class="form-label">Profession</label>
                    <select name="profession" id="profession" class="form-input">
                        <option value="">Select profession</option>
                        <?php foreach ($professions as $prof): ?>
                            <option value="<?= htmlspecialchars($prof) ?>"><?= htmlspecialchars($prof) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">About your work</label>
                    <textarea name="about_work" id="about_work" class="form-input" rows="3"
                              placeholder="Briefly describe your skills and work experience"></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Experience (years)</label>
                    <input type="number" name="experience" id="experience" class="form-input" min="0" max="50" placeholder="5">
                </div>

                <div class="form-group">
                    <label class="form-label">Area Pincode</label>
                    <input type="text" name="pincode" id="pincode" class="form-input"
                           maxlength="6" placeholder="e.g. 560001" pattern="[0-9]{6}">
                </div>

                <div class="form-group">
                    <label class="form-label">Phone number</label>
                    <div class="phone-flex">
                        <input type="text" value="+91" class="form-input phone-prefix" readonly>
                        <input type="text" name="phone" id="phone" class="form-input"
                               placeholder="9876543210" maxlength="10" pattern="[0-9]{10}">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" id="submitBtn">
                <span id="submitText">Send Verification Code →</span>
            </button>

            <div class="auth-link">
                Already have an account? <a href="login.php">Sign in</a>
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
    <div class="footer-copy">© 2025 Service-Hub. All rights reserved.</div>
</footer>

<script>
// ── Role Toggle ──────────────────────────────────────────────────────────────
const roleBtns      = document.querySelectorAll('.role-btn');
const roleInput     = document.getElementById('role');
const providerFields = document.getElementById('providerFields');
const roleToggle    = document.getElementById('roleToggle');

roleBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        roleBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        const role = btn.dataset.role;
        roleInput.value = role;

        if (role === 'provider') {
            roleToggle.classList.add('provider-active');
            providerFields.classList.add('show');
            document.getElementById('age').required        = true;
            document.getElementById('profession').required = true;
            document.getElementById('about_work').required = true;
            document.getElementById('experience').required = true;
            document.getElementById('pincode').required    = true;
            document.getElementById('phone').required      = true;
        } else {
            roleToggle.classList.remove('provider-active');
            providerFields.classList.remove('show');
            document.getElementById('age').required        = false;
            document.getElementById('profession').required = false;
            document.getElementById('about_work').required = false;
            document.getElementById('experience').required = false;
            document.getElementById('pincode').required    = false;
            document.getElementById('phone').required      = false;
        }
    });
});

// ── Password Toggle ──────────────────────────────────────────────────────────
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleIcon    = document.getElementById('toggleIcon');
    if (passwordInput.type === 'password') {
        passwordInput.type     = 'text';
        toggleIcon.textContent = '🙈';
    } else {
        passwordInput.type     = 'password';
        toggleIcon.textContent = '👁️';
    }
}

// ── Password Strength ────────────────────────────────────────────────────────
const passwordInput      = document.getElementById('password');
const strengthBar        = document.getElementById('strengthBar');
const strengthIndicator  = document.getElementById('strengthIndicator');
const strengthText       = document.getElementById('strengthText');

passwordInput.addEventListener('input', function () {
    const password = this.value;
    if (password.length === 0) {
        strengthBar.classList.remove('active');
        strengthText.textContent = '';
        return;
    }
    strengthBar.classList.add('active');

    let strength = 0;
    if (password.length >= 6)  strength++;
    if (password.length >= 10) strength++;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
    if (/\d/.test(password))  strength++;
    if (/[^a-zA-Z0-9]/.test(password)) strength++;

    strengthIndicator.className = 'strength-bar';
    if (strength <= 2) {
        strengthIndicator.classList.add('strength-weak');
        strengthText.textContent  = 'Weak password';
        strengthText.style.color  = '#ef4444';
    } else if (strength <= 4) {
        strengthIndicator.classList.add('strength-medium');
        strengthText.textContent  = 'Medium password';
        strengthText.style.color  = '#f59e0b';
    } else {
        strengthIndicator.classList.add('strength-strong');
        strengthText.textContent  = 'Strong password';
        strengthText.style.color  = '#22c55e';
    }
});

// ── Loading state on submit ──────────────────────────────────────────────────
document.getElementById('registerForm').addEventListener('submit', function () {
    const btn  = document.getElementById('submitBtn');
    const text = document.getElementById('submitText');
    btn.disabled      = true;
    text.textContent  = 'Sending code…';
    btn.style.opacity = '0.75';
});

// ── Floating card tilt ───────────────────────────────────────────────────────
const card = document.querySelector('.register-card');
document.addEventListener('mousemove', (e) => {
    const x = (e.clientX / window.innerWidth  - 0.5) * 10;
    const y = (e.clientY / window.innerHeight - 0.5) * 10;
    card.style.transform = `perspective(1000px) rotateY(${x}deg) rotateX(${-y}deg)`;
});
document.addEventListener('mouseleave', () => {
    card.style.transform = 'perspective(1000px) rotateY(0deg) rotateX(0deg)';
});
</script>
</body>
</html>