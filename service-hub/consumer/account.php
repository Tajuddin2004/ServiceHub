<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'consumer') {
    header("Location: ../auth/login.php");
    exit;
}

$userId = $_SESSION['user_id'];

/* 👤 Profile info */
$userStmt = $conn->prepare("
    SELECT user_id, name, email, phone
    FROM users
    WHERE user_id = ?
");
$userStmt->bind_param("i", $userId);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();

/* 📦 Booking list */
$bookingStmt = $conn->prepare("
    SELECT 
        b.booking_id, 
        s.service_name, 
        b.booking_date, 
        b.status
    FROM bookings b
    JOIN services s ON s.service_id = b.service_id
    WHERE b.consumer_id = ?
    ORDER BY b.created_at DESC
");
$bookingStmt->bind_param("i", $userId);
$bookingStmt->execute();
$bookings = $bookingStmt->get_result();

// Count bookings by status
$pendingCount = 0;
$acceptedCount = 0;
$completedCount = 0;
$cancelledCount = 0;

$bookingsArray = [];
while ($row = $bookings->fetch_assoc()) {
    $bookingsArray[] = $row;
    if ($row['status'] === 'pending') $pendingCount++;
    elseif ($row['status'] === 'accepted') $acceptedCount++;
    elseif ($row['status'] === 'completed') $completedCount++;
    elseif ($row['status'] === 'cancelled') $cancelledCount++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>My Account | Service-Hub</title>
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
    max-width: 1400px;
    margin: auto;
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

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
    animation: fadeUp 0.6s ease 0.1s backwards;
}

.stat-card {
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 16px;
    padding: 1.5rem;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #6366f1, #a855f7);
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.stat-card:hover::before {
    transform: scaleX(1);
}

.stat-card:hover {
    transform: translateY(-5px);
    background: rgba(255, 255, 255, 0.06);
}

.stat-card h3 {
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.7);
    margin-bottom: 0.5rem;
}

.stat-card .number {
    font-size: 2rem;
    font-weight: 700;
    background: linear-gradient(135deg, #22d3ee, #a855f7);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Profile Card */
.profile-card {
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(30px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    padding: 2.5rem;
    margin-bottom: 2rem;
    animation: fadeUp 0.6s ease 0.2s backwards;
    position: relative;
    overflow: hidden;
}

.profile-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #6366f1, #a855f7, #22d3ee);
    border-radius: 20px 20px 0 0;
}

.profile-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.profile-header h2 {
    font-size: 1.5rem;
    font-weight: 700;
}

.profile-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.profile-item {
    padding: 1.2rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    transition: all 0.3s ease;
}

.profile-item:hover {
    background: rgba(255, 255, 255, 0.08);
}

.profile-item label {
    display: block;
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.6);
    margin-bottom: 0.5rem;
}

.profile-item .value {
    font-size: 1rem;
    font-weight: 500;
    color: #ffffff;
}

.profile-item .not-added {
    color: rgba(255, 255, 255, 0.4);
    font-style: italic;
}

/* Buttons */
.btn {
    padding: 0.8rem 1.5rem;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 500;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    display: inline-block;
    cursor: pointer;
    border: none;
}

.btn-primary {
    background: linear-gradient(135deg, #6366f1, #a855f7);
    color: #ffffff;
    box-shadow: 0 10px 30px rgba(99, 102, 241, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 15px 40px rgba(99, 102, 241, 0.4);
}

.btn-outline {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: #ffffff;
}

.btn-outline:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: translateY(-2px);
}

/* Bookings Section */
.bookings-section {
    animation: fadeUp 0.6s ease 0.3s backwards;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.section-header h2 {
    font-size: 1.5rem;
    font-weight: 700;
}

/* Table */
.table-container {
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 16px;
    overflow: hidden;
}

table {
    width: 100%;
    border-collapse: collapse;
}

thead {
    background: rgba(255, 255, 255, 0.05);
}

th {
    padding: 1.2rem 1rem;
    text-align: left;
    font-weight: 600;
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.8);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

td {
    padding: 1.2rem 1rem;
    font-size: 0.9rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

tbody tr {
    transition: background 0.2s ease;
}

tbody tr:hover {
    background: rgba(255, 255, 255, 0.05);
}

/* Status badges */
.status-badge {
    display: inline-block;
    padding: 0.4rem 0.9rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.status-pending {
    background: rgba(251, 146, 60, 0.2);
    color: #fdba74;
    border: 1px solid rgba(251, 146, 60, 0.3);
}

.status-accepted {
    background: rgba(59, 130, 246, 0.2);
    color: #93c5fd;
    border: 1px solid rgba(59, 130, 246, 0.3);
}

.status-completed {
    background: rgba(34, 197, 94, 0.2);
    color: #86efac;
    border: 1px solid rgba(34, 197, 94, 0.3);
}

.status-cancelled {
    background: rgba(239, 68, 68, 0.2);
    color: #fca5a5;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

/* Empty state */
.empty-state {
    text-align: center;
    padding: 3rem 2rem;
    color: rgba(255, 255, 255, 0.5);
}

.empty-state-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.3;
}

/* Responsive */
@media (max-width: 768px) {
    .container {
        padding: 2rem 4%;
    }
    
    .profile-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .table-container {
        overflow-x: auto;
    }
    
    table {
        min-width: 600px;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>
</head>

<body>
<div class="bg-gradient"></div>

<div class="container">
    <!-- Header -->
    <div class="page-header">
        <a href="home.php" class="back-link">← Back to Dashboard</a>
        <h1>My Account</h1>
        <p>Manage your profile and view booking history</p>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Bookings</h3>
            <div class="number"><?= count($bookingsArray) ?></div>
        </div>
        <div class="stat-card">
            <h3>Pending</h3>
            <div class="number"><?= $pendingCount ?></div>
        </div>
        <div class="stat-card">
            <h3>Accepted</h3>
            <div class="number"><?= $acceptedCount ?></div>
        </div>
        <div class="stat-card">
            <h3>Completed</h3>
            <div class="number"><?= $completedCount ?></div>
        </div>
    </div>

    <!-- Profile Card -->
    <div class="profile-card">
        <div class="profile-header">
            <h2>👤 Profile Details</h2>
            <a href="profile.php" class="btn btn-primary">Edit Profile</a>
        </div>

        <div class="profile-grid">
            <div class="profile-item">
                <label>User ID</label>
                <div class="value">#<?= htmlspecialchars($user['user_id']) ?></div>
            </div>
            <div class="profile-item">
                <label>Full Name</label>
                <div class="value"><?= htmlspecialchars($user['name']) ?></div>
            </div>
            <div class="profile-item">
                <label>Email Address</label>
                <div class="value"><?= htmlspecialchars($user['email']) ?></div>
            </div>
            <div class="profile-item">
                <label>Phone Number</label>
                <div class="value <?= !$user['phone'] ? 'not-added' : '' ?>">
                    <?= $user['phone'] ? htmlspecialchars($user['phone']) : 'Not added yet' ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bookings Section -->
    <div class="bookings-section">
        <div class="section-header">
            <h2>📦 My Bookings</h2>
            <a href="home.php" class="btn btn-outline">+ New Booking</a>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Booking Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($bookingsArray) > 0): ?>
                        <?php foreach ($bookingsArray as $b): ?>
                        <tr>
                            <td><?= htmlspecialchars($b['service_name']) ?></td>
                            <td><?= htmlspecialchars(date('M d, Y', strtotime($b['booking_date']))) ?></td>
                            <td>
                                <span class="status-badge status-<?= htmlspecialchars($b['status']) ?>">
                                    <?= ucfirst($b['status']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="my_booking.php?id=<?= $b['booking_id'] ?>" class="btn btn-outline" style="padding: 0.5rem 1rem; font-size: 0.85rem;">
                                    View Details
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="empty-state">
                                <div class="empty-state-icon">📭</div>
                                <p>No bookings yet. Start booking services now!</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Smooth scroll
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        document.querySelector(this.getAttribute('href')).scrollIntoView({
            behavior: 'smooth'
        });
    });
});
</script>

</body>
</html>