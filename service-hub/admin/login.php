<?php
session_start();
require_once dirname(__DIR__) . "/config/db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare(
        "SELECT user_id, name, password 
         FROM users 
         WHERE email = ? AND role = 'admin' AND status = 1"
    );
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $admin['user_id'];
        $_SESSION['admin_name'] = $admin['name'];

        header("Location: home.php");
        exit;
    } else {
        $error = "Invalid admin credentials.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login | Service-Hub</title>
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
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        position: relative;
    }

    /* Animated gradient background - Admin theme */
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
        background: radial-gradient(circle at 50% 50%, rgba(239, 68, 68, 0.12) 0%, transparent 50%),
                    radial-gradient(circle at 80% 20%, rgba(245, 158, 11, 0.12) 0%, transparent 50%),
                    radial-gradient(circle at 20% 80%, rgba(234, 88, 12, 0.12) 0%, transparent 50%);
        animation: floatGradient 20s ease-in-out infinite;
    }

    @keyframes floatGradient {
        0%, 100% { transform: translate(0, 0) rotate(0deg); }
        33% { transform: translate(30px, -30px) rotate(120deg); }
        66% { transform: translate(-20px, 20px) rotate(240deg); }
    }

    /* Grid pattern overlay */
    .grid-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: -1;
        background-image: 
            linear-gradient(rgba(239, 68, 68, 0.03) 1px, transparent 1px),
            linear-gradient(90deg, rgba(239, 68, 68, 0.03) 1px, transparent 1px);
        background-size: 50px 50px;
        opacity: 0.3;
    }

    /* Login Container */
    .login-container {
        width: 100%;
        max-width: 480px;
        padding: 2rem;
        animation: fadeIn 0.8s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Logo/Brand Section */
    .brand-section {
        text-align: center;
        margin-bottom: 3rem;
        animation: fadeIn 0.8s ease 0.2s backwards;
    }

    .admin-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 1.5rem;
        background: linear-gradient(135deg, #ef4444, #f97316);
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        box-shadow: 0 10px 40px rgba(239, 68, 68, 0.3);
        position: relative;
        overflow: hidden;
    }

    .admin-icon::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
        animation: shine 3s infinite;
    }

    @keyframes shine {
        0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
        100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
    }

    .brand-title {
        font-size: 2rem;
        font-weight: 800;
        margin-bottom: 0.5rem;
        background: linear-gradient(135deg, #ffffff 0%, #ef4444 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .brand-subtitle {
        color: rgba(255, 255, 255, 0.5);
        font-size: 0.95rem;
        font-weight: 500;
    }

    /* Login Card */
    .login-card {
        background: rgba(255, 255, 255, 0.03);
        backdrop-filter: blur(30px);
        border: 1px solid rgba(239, 68, 68, 0.2);
        border-radius: 24px;
        padding: 3rem 2.5rem;
        position: relative;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        animation: fadeIn 0.8s ease 0.3s backwards;
    }

    .login-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #ef4444, #f97316, #f59e0b);
        border-radius: 24px 24px 0 0;
    }

    .card-header {
        margin-bottom: 2rem;
    }

    .card-header h2 {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: #ffffff;
    }

    .card-header p {
        color: rgba(255, 255, 255, 0.6);
        font-size: 0.9rem;
    }

    /* Error Alert */
    .error-alert {
        background: rgba(239, 68, 68, 0.15);
        border: 1px solid rgba(239, 68, 68, 0.3);
        border-radius: 12px;
        padding: 1rem 1.2rem;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.8rem;
        animation: shake 0.5s ease;
    }

    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-10px); }
        75% { transform: translateX(10px); }
    }

    .error-alert .icon {
        font-size: 1.3rem;
        flex-shrink: 0;
    }

    .error-alert .message {
        color: #fca5a5;
        font-size: 0.9rem;
        font-weight: 500;
    }

    /* Success Alert */
    .success-alert {
        background: rgba(34, 197, 94, 0.15);
        border: 1px solid rgba(34, 197, 94, 0.3);
        border-radius: 12px;
        padding: 1rem 1.2rem;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.8rem;
        animation: slideIn 0.5s ease;
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

    .success-alert .icon {
        font-size: 1.3rem;
        flex-shrink: 0;
    }

    .success-alert .message {
        color: #86efac;
        font-size: 0.9rem;
        font-weight: 500;
    }

    /* Form Styles */
    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-label {
        display: block;
        color: rgba(255, 255, 255, 0.8);
        font-size: 0.9rem;
        font-weight: 500;
        margin-bottom: 0.6rem;
    }

    .input-wrapper {
        position: relative;
    }

    .input-icon {
        position: absolute;
        left: 1.2rem;
        top: 50%;
        transform: translateY(-50%);
        font-size: 1.2rem;
        color: rgba(255, 255, 255, 0.4);
        pointer-events: none;
        transition: color 0.3s ease;
    }

    .form-input {
        width: 100%;
        padding: 1rem 1.2rem 1rem 3.2rem;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        color: #ffffff;
        font-size: 0.95rem;
        font-family: inherit;
        transition: all 0.3s ease;
    }

    .form-input:focus {
        outline: none;
        background: rgba(255, 255, 255, 0.08);
        border-color: rgba(239, 68, 68, 0.5);
        box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
    }

    .form-input:focus + .input-icon {
        color: #ef4444;
    }

    .form-input::placeholder {
        color: rgba(255, 255, 255, 0.3);
    }

    /* Submit Button */
    .btn-submit {
        width: 100%;
        padding: 1.1rem;
        background: linear-gradient(135deg, #ef4444, #f97316);
        border: none;
        border-radius: 12px;
        color: #ffffff;
        font-size: 1rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-top: 1rem;
        position: relative;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(239, 68, 68, 0.3);
    }

    .btn-submit::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.5s ease;
    }

    .btn-submit:hover::before {
        left: 100%;
    }

    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 15px 40px rgba(239, 68, 68, 0.4);
    }

    .btn-submit:active {
        transform: translateY(0);
    }

    /* Footer */
    .login-footer {
        text-align: center;
        margin-top: 2rem;
        color: rgba(255, 255, 255, 0.4);
        font-size: 0.85rem;
    }

    .login-footer a {
        color: #ef4444;
        text-decoration: none;
        font-weight: 500;
        transition: color 0.3s ease;
    }

    .login-footer a:hover {
        color: #f97316;
    }

    /* Security Badge */
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

    /* Responsive */
    @media (max-width: 768px) {
        .login-container {
            padding: 1.5rem;
        }
        
        .login-card {
            padding: 2rem 1.5rem;
        }
        
        .brand-title {
            font-size: 1.75rem;
        }
        
        .admin-icon {
            width: 70px;
            height: 70px;
            font-size: 2rem;
        }
    }

    /* Loading State */
    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    .btn-submit.loading {
        pointer-events: none;
        opacity: 0.7;
    }

    .btn-submit.loading::after {
        content: '';
        position: absolute;
        width: 16px;
        height: 16px;
        top: 50%;
        left: 50%;
        margin-left: -8px;
        margin-top: -8px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        border-top-color: white;
        animation: spin 0.6s linear infinite;
    }
    </style>
</head>

<body>
    <div class="bg-gradient"></div>
    <div class="grid-overlay"></div>

    <div class="login-container">
        <!-- Brand Section -->
        <div class="brand-section">
            <div class="admin-icon">
                🛡️
            </div>
            <h1 class="brand-title">Admin Portal</h1>
            <p class="brand-subtitle">Service-Hub Management</p>
        </div>

        <!-- Login Card -->
        <div class="login-card">
            <div class="card-header">
                <h2>Welcome Back</h2>
                <p>Sign in to access the admin dashboard</p>
            </div>

            <?php if ($error): ?>
            <div class="error-alert">
                <span class="icon">⚠️</span>
                <span class="message"><?= htmlspecialchars($error) ?></span>
            </div>
            <?php endif; ?>

            <?php if (isset($_GET['logged_out'])): ?>
            <div class="success-alert">
                <span class="icon">✅</span>
                <span class="message">You have been logged out successfully.</span>
            </div>
            <?php endif; ?>

            <form method="POST" id="loginForm">
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <div class="input-wrapper">
                        <input 
                            type="email" 
                            name="email" 
                            class="form-input" 
                            placeholder="admin@service-hub.com" 
                            required
                            autocomplete="email"
                        >
                        <span class="input-icon">📧</span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-wrapper">
                        <input 
                            type="password" 
                            name="password" 
                            class="form-input" 
                            placeholder="Enter your password" 
                            required
                            autocomplete="current-password"
                        >
                        <span class="input-icon">🔒</span>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    Sign In to Dashboard
                </button>

                <div class="security-badge">
                    <span>🔐</span>
                    <span>Secured with end-to-end encryption</span>
                </div>
            </form>

            <div class="login-footer">
                Protected admin access only
            </div>
        </div>
    </div>

    <script>
    // Add loading state to button on submit
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        const btn = this.querySelector('.btn-submit');
        btn.classList.add('loading');
        btn.textContent = '';
    });

    // Auto-focus first input
    document.querySelector('input[name="email"]').focus();
    </script>
</body>
</html>