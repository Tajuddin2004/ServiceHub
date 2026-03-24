<?php
session_start();
require_once dirname(__DIR__) . "/config/db.php";

/* 🔐 Protect admin */
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$message = "";
$messageType = "";

/* ===============================
   HANDLE ACTIVATE / DEACTIVATE
================================ */
if (
    isset($_POST['toggle_status'], $_POST['user_id'], $_POST['new_status'])
) {
    $userId    = (int) $_POST['user_id'];
    $newStatus = (int) $_POST['new_status'];
    $adminId   = (int) ($_SESSION['user_id'] ?? 0);

    /* 🔍 Fetch user role */
    $check = $conn->prepare("
        SELECT role 
        FROM users 
        WHERE user_id = ?
        LIMIT 1
    ");
    $check->bind_param("i", $userId);
    $check->execute();
    $user = $check->get_result()->fetch_assoc();

    if (!$user) {
        $message = "User not found.";
        $messageType = "error";
    }
    /* 🚫 Block admin deactivation */
    elseif ($user['role'] === 'admin' && $newStatus === 0) {
        $message = "Admin accounts cannot be deactivated.";
        $messageType = "error";
    }
    /* 🚫 Block self-deactivation */
    elseif ($userId === $adminId && $newStatus === 0) {
        $message = "You cannot deactivate your own account.";
        $messageType = "error";
    }
    else {
        /* ✅ Safe update */
        $update = $conn->prepare("
            UPDATE users 
            SET is_active = ? 
            WHERE user_id = ?
        ");
        $update->bind_param("ii", $newStatus, $userId);

        if ($update->execute()) {
            $message = $newStatus
                ? "User reactivated successfully!"
                : "User deactivated successfully!";
            $messageType = "success";
        } else {
            $message = "Failed to update user status.";
            $messageType = "error";
        }
    }
}

/* ===============================
   FETCH USERS (VIEW PURPOSE)
================================ */
$result = $conn->query("
    SELECT 
        user_id,
        name,
        email,
        phone,
        role,
        is_active,
        created_at
    FROM users
    ORDER BY created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Manage Users | Admin Panel</title>
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
    max-width: 1600px;
    margin: auto;
    min-height: 100vh;
}

/* Page Header */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1.5rem;
    animation: fadeUp 0.6s ease;
}

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

.header-content h1 {
    font-size: 2.5rem;
    font-weight: 800;
    margin-bottom: 0.5rem;
    background: linear-gradient(135deg, #ffffff 0%, #ef4444 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.header-content p {
    color: rgba(255, 255, 255, 0.6);
    font-size: 1rem;
}

.back-link {
    padding: 1rem 2rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    border-radius: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
}

.back-link:hover {
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(239, 68, 68, 0.3);
    color: #ffffff;
    transform: translateX(-5px);
}

/* Alert Messages */
.alert {
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(30px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 1.2rem 1.5rem;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 1rem;
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

.alert-success {
    border-color: rgba(34, 197, 94, 0.3);
    background: rgba(34, 197, 94, 0.1);
    color: #86efac;
}

.alert-error {
    border-color: rgba(197, 34, 34, 0.3);
    background: rgba(197, 34, 34, 0.1);
    color: #ef8686;
}

.alert-success::before {
    content: '✅';
    font-size: 1.5rem;
}

.alert-error::before {
    content: '🚫';
    font-size: 1.5rem;
}

/* Search Bar */
.search-card {
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(30px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
    animation: fadeUp 0.6s ease 0.1s backwards;
}

.search-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #ef4444, #f97316, #f59e0b);
    border-radius: 20px 20px 0 0;
}

.search-wrapper {
    position: relative;
    max-width: 600px;
}

.search-icon {
    position: absolute;
    left: 1.2rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: 1.2rem;
    color: rgba(255, 255, 255, 0.4);
    pointer-events: none;
}

.search-input {
    width: 100%;
    padding: 1rem 1.2rem 1rem 3.5rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    color: #ffffff;
    font-size: 1rem;
    font-family: inherit;
    transition: all 0.3s ease;
}

.search-input:focus {
    outline: none;
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(239, 68, 68, 0.5);
    box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
}

.search-input::placeholder {
    color: rgba(255, 255, 255, 0.3);
}

.search-results {
    margin-top: 1rem;
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.6);
}

/* Users Table Card */
.users-card {
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(30px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    padding: 2.5rem;
    position: relative;
    overflow: hidden;
    animation: fadeUp 0.6s ease 0.2s backwards;
}

.users-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #ef4444, #f97316, #f59e0b);
    border-radius: 20px 20px 0 0;
}

.table-header {
    margin-bottom: 2rem;
}

.table-header h2 {
    font-size: 1.8rem;
    font-weight: 700;
    color: #ffffff;
}

.table-wrapper {
    overflow-x: auto;
    border-radius: 12px;
}

table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

thead {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(249, 115, 22, 0.2));
}

th {
    padding: 1.2rem 1rem;
    text-align: left;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.9);
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

tbody tr {
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    transition: all 0.3s ease;
}

tbody tr:hover {
    background: rgba(255, 255, 255, 0.03);
}

tbody tr.hidden {
    display: none;
}

td {
    padding: 1.2rem 1rem;
    color: rgba(255, 255, 255, 0.8);
}

.user-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.user-avatar {
    width: 45px;
    height: 45px;
    background: linear-gradient(135deg, #ef4444, #f97316);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    font-weight: 700;
    flex-shrink: 0;
}

.user-details {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
}

.user-name {
    font-weight: 600;
    color: #ffffff;
}

.user-email {
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.5);
}

.role-badge {
    padding: 0.4rem 0.9rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    display: inline-block;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.role-admin {
    background: rgba(239, 68, 68, 0.2);
    color: #fca5a5;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.role-provider {
    background: rgba(34, 211, 238, 0.2);
    color: #22d3ee;
    border: 1px solid rgba(34, 211, 238, 0.3);
}

.role-consumer {
    background: rgba(168, 85, 247, 0.2);
    color: #c084fc;
    border: 1px solid rgba(168, 85, 247, 0.3);
}

.status-badge {
    padding: 0.4rem 0.9rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    display: inline-block;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-active {
    background: rgba(34, 197, 94, 0.2);
    color: #86efac;
    border: 1px solid rgba(34, 197, 94, 0.3);
}

.status-inactive {
    background: rgba(239, 68, 68, 0.2);
    color: #fca5a5;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.btn {
    padding: 0.7rem 1.5rem;
    border: none;
    border-radius: 10px;
    font-weight: 700;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.btn-deactivate {
    background: rgba(239, 68, 68, 0.15);
    color: #fca5a5;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.btn-deactivate:hover {
    background: rgba(239, 68, 68, 0.25);
    transform: translateY(-2px);
}

.btn-activate {
    background: rgba(34, 197, 94, 0.15);
    color: #86efac;
    border: 1px solid rgba(34, 197, 94, 0.3);
}

.btn-activate:hover {
    background: rgba(34, 197, 94, 0.25);
    transform: translateY(-2px);
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: rgba(255, 255, 255, 0.4);
}

.empty-state-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.3;
}

.empty-state p {
    font-size: 1.1rem;
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
    
    .header-content h1 {
        font-size: 2rem;
    }
    
    .back-link {
        width: 100%;
        justify-content: center;
    }
    
    .users-card {
        padding: 2rem 1.5rem;
    }
    
    .table-wrapper {
        overflow-x: scroll;
    }
    
    table {
        min-width: 900px;
    }
    
    .search-card {
        padding: 1.5rem;
    }
}

/* Smooth scrollbar */
.table-wrapper::-webkit-scrollbar {
    height: 8px;
}

.table-wrapper::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
}

.table-wrapper::-webkit-scrollbar-thumb {
    background: rgba(239, 68, 68, 0.3);
    border-radius: 10px;
}

.table-wrapper::-webkit-scrollbar-thumb:hover {
    background: rgba(239, 68, 68, 0.5);
}
</style>
</head>

<body>



<div class="bg-gradient"></div>
<div class="grid-overlay"></div>

<div class="container">

    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>👥 User Management</h1>
            <p>View and manage platform users</p>
        </div>
        <a href="home.php" class="back-link">
            ← Back to Dashboard
        </a>
    </div>

    <!-- Alert Messages -->
    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>">
            <span><?= htmlspecialchars($message) ?></span>
        </div>
    <?php endif; ?>

    <!-- Search Bar -->
    <div class="search-card">
        <div class="search-wrapper">
            <span class="search-icon">🔍</span>
            <input 
                type="text" 
                id="searchInput" 
                class="search-input" 
                placeholder="Search by name, email, phone, or role..."
                autocomplete="off"
            >
        </div>
        <div class="search-results" id="searchResults"></div>
    </div>

    <!-- Users Table -->
    <div class="users-card">
        <div class="table-header">
            <h2>All Users</h2>
        </div>

        <div class="table-wrapper">
            <table id="usersTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($u = $result->fetch_assoc()): ?>
                            <tr class="user-row">
                                <td>#<?= $u['user_id'] ?></td>
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                        </div>
                                        <div class="user-details">
                                            <span class="user-name"><?= htmlspecialchars($u['name']) ?></span>
                                            <span class="user-email"><?= htmlspecialchars($u['email']) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($u['phone']) ?></td>
                                <td>
                                    <span class="role-badge role-<?= strtolower($u['role']) ?>">
                                        <?= ucfirst($u['role']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= $u['is_active'] ? 'active' : 'inactive' ?>">
                                        <?= $u['is_active'] ? 'Active' : 'Deactivated' ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                        <input type="hidden" name="new_status" value="<?= $u['is_active'] ? 0 : 1 ?>">

                                        <?php if ($u['is_active']): ?>
                                            <button
                                                type="submit"
                                                name="toggle_status"
                                                class="btn btn-deactivate"
                                                onclick="return confirm('Are you sure you want to deactivate this user?')">
                                                Deactivate
                                            </button>
                                        <?php else: ?>
                                            <button
                                                type="submit"
                                                name="toggle_status"
                                                class="btn btn-activate"
                                                onclick="return confirm('Are you sure you want to reactivate this user?')">
                                                Reactivate
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <div class="empty-state-icon">👤</div>
                                    <p>No users found.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
// Live Search Functionality
const searchInput = document.getElementById('searchInput');
const searchResults = document.getElementById('searchResults');
const userRows = document.querySelectorAll('.user-row');
const totalUsers = userRows.length;

searchInput.addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase().trim();
    let visibleCount = 0;
    
    userRows.forEach(row => {
        const text = row.textContent.toLowerCase();
        
        if (searchTerm === '' || text.includes(searchTerm)) {
            row.classList.remove('hidden');
            visibleCount++;
        } else {
            row.classList.add('hidden');
        }
    });
    
    // Update search results
    if (searchTerm === '') {
        searchResults.textContent = '';
    } else {
        searchResults.textContent = `Showing ${visibleCount} of ${totalUsers} users`;
    }
});

// Auto-hide alerts
const alerts = document.querySelectorAll('.alert');
alerts.forEach(alert => {
    setTimeout(() => {
        alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-20px)';
        setTimeout(() => alert.remove(), 500);
    }, 5000);
});

// Auto-focus search on page load
searchInput.focus();
</script>

</body>
</html>