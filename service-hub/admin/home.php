<?php
session_start();
require_once dirname(__DIR__) . "/config/db.php";

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

// Stats
$totalUsers = $conn->query("SELECT COUNT(*) total FROM users")->fetch_assoc()['total'];

$pendingProviders = $conn->query("
    SELECT COUNT(*) total 
    FROM service_providers 
    WHERE is_approved = 0
")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Dashboard | Service-Hub</title>
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

/* Container */
.container {
    padding: 3rem 5%;
    max-width: 1400px;
    margin: auto;
    min-height: 100vh;
}

/* Header Section */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 3rem;
    flex-wrap: wrap;
    gap: 1.5rem;
    animation: fadeUp 0.6s ease;
}

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

.welcome-section h1 {
    font-size: 2.5rem;
    font-weight: 800;
    margin-bottom: 0.5rem;
    background: linear-gradient(135deg, #ffffff 0%, #ef4444 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.welcome-section p {
    color: rgba(255, 255, 255, 0.6);
    font-size: 1rem;
}

.add-service-btn {
    padding: 1rem 2rem;
    background: linear-gradient(135deg, #ef4444, #f97316);
    color: #ffffff;
    text-decoration: none;
    border-radius: 12px;
    font-weight: 700;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    box-shadow: 0 10px 30px rgba(239, 68, 68, 0.3);
    border: none;
    cursor: pointer;
}

.add-service-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 15px 40px rgba(239, 68, 68, 0.4);
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
    animation: fadeUp 0.6s ease 0.2s backwards;
}

.stat-card {
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(30px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    padding: 2rem;
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #ef4444, #f97316);
    border-radius: 20px 20px 0 0;
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.stat-card:hover::before {
    transform: scaleX(1);
}

.stat-card:hover {
    transform: translateY(-5px);
    background: rgba(255, 255, 255, 0.05);
    border-color: rgba(239, 68, 68, 0.3);
}

.stat-card.pending::before {
    background: linear-gradient(90deg, #f59e0b, #fbbf24);
}

.stat-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(249, 115, 22, 0.2));
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin-bottom: 1.5rem;
}

.stat-card.pending .stat-icon {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.2), rgba(251, 191, 36, 0.2));
}

.stat-label {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.9rem;
    font-weight: 500;
    margin-bottom: 0.8rem;
}

.stat-value {
    font-size: 2.5rem;
    font-weight: 800;
    background: linear-gradient(135deg, #ef4444, #f97316);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.stat-card.pending .stat-value {
    background: linear-gradient(135deg, #f59e0b, #fbbf24);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Quick Actions Section */
.quick-actions {
    animation: fadeUp 0.6s ease 0.3s backwards;
}

.section-header {
    margin-bottom: 2rem;
}

.section-header h2 {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: #ffffff;
}

.section-header p {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.95rem;
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.action-card {
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(30px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 2rem;
    text-decoration: none;
    color: #ffffff;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    display: block;
}

.action-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #ef4444, #f97316);
    border-radius: 16px 16px 0 0;
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.action-card:hover::before {
    transform: scaleX(1);
}

.action-card:hover {
    transform: translateY(-5px);
    background: rgba(255, 255, 255, 0.05);
    border-color: rgba(239, 68, 68, 0.3);
    box-shadow: 0 10px 30px rgba(239, 68, 68, 0.2);
}

.action-icon {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    display: block;
}

.action-title {
    font-size: 1.1rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: #ffffff;
}

.action-description {
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.6);
    line-height: 1.5;
}

/* Logout Card Special */
.action-card.logout {
    border-color: rgba(239, 68, 68, 0.3);
}

.action-card.logout::before {
    background: linear-gradient(90deg, #ef4444, #dc2626);
}

.action-card.logout:hover {
    border-color: rgba(239, 68, 68, 0.5);
}

/* Responsive */
@media (max-width: 768px) {
    .container {
        padding: 2rem 4%;
    }
    
    .page-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .welcome-section h1 {
        font-size: 2rem;
    }
    
    .add-service-btn {
        width: 100%;
        justify-content: center;
    }
    
    .stats-grid,
    .actions-grid {
        grid-template-columns: 1fr;
    }
}

/* Loading Animation */
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* Success Message (if needed) */
.success-message {
    background: rgba(34, 197, 94, 0.15);
    border: 1px solid rgba(34, 197, 94, 0.3);
    border-radius: 12px;
    padding: 1rem 1.5rem;
    margin-bottom: 2rem;
    color: #86efac;
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
</style>
</head>

<body>
<div class="bg-gradient"></div>
<div class="grid-overlay"></div>

<div class="container">
    <!-- Page Header -->
    <div class="page-header">
        <div class="welcome-section">
            <h1>Welcome back, <?= htmlspecialchars($_SESSION['admin_name']) ?> 👋</h1>
            <p>Manage your platform from the admin dashboard</p>
        </div>
        
        <a href="add_service.php" class="add-service-btn">
            <span>➕</span>
            <span>Add New Service</span>
        </a>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-label">Total Users</div>
            <div class="stat-value"><?= number_format($totalUsers) ?></div>
        </div>

        <div class="stat-card pending">
            <div class="stat-icon">⏳</div>
            <div class="stat-label">Pending Providers</div>
            <div class="stat-value"><?= number_format($pendingProviders) ?></div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <div class="section-header">
            <h2>Quick Actions</h2>
            <p>Manage platform components and settings</p>
        </div>

        <div class="actions-grid">
            <a href="providers.php" class="action-card">
                <span class="action-icon">🧑‍🔧</span>
                <h3 class="action-title">Manage Providers</h3>
                <p class="action-description">Review and approve service provider applications</p>
            </a>

            <a href="manage_users.php" class="action-card">
                <span class="action-icon">👥</span>
                <h3 class="action-title">Manage Users</h3>
                <p class="action-description">View and manage all platform users</p>
            </a>

            <a href="change_password.php" class="action-card">
                <span class="action-icon">🔐</span>
                <h3 class="action-title">Change Password</h3>
                <p class="action-description">Update your admin account security</p>
            </a>

            <a href="logout.php" class="action-card logout">
                <span class="action-icon">🚪</span>
                <h3 class="action-title">Logout</h3>
                <p class="action-description">Sign out from admin dashboard</p>
            </a>
        </div>
    </div>
</div>

</body>
</html>