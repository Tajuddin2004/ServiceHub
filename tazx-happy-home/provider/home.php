<?php
session_start();
require_once dirname(__DIR__) . "/config/db.php";

/* 🔐 Protect provider access */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'provider') {
    header("Location: ../auth/login.php");
    exit;
}

$userId = $_SESSION['user_id'];

/* 🔎 Check provider approval */
$providerStmt = $conn->prepare("
    SELECT provider_id, profession, is_approved
    FROM service_providers
    WHERE user_id = ?
");
$providerStmt->bind_param("i", $userId);
$providerStmt->execute();
$provider = $providerStmt->get_result()->fetch_assoc();

if (!$provider || $provider['is_approved'] != 1) {
    echo "<h2>⛔ Your account is not approved yet.</h2>";
    exit;
}

$providerId = $provider['provider_id'];

/* 📦 Fetch jobs */
$sql = "
SELECT
    b.booking_id,
    b.booking_date,
    b.address,
    b.pincode,
    b.problem_description,
    b.status,
    b.provider_note,
    s.service_name
FROM bookings b
JOIN services s ON s.service_id = b.service_id
WHERE
(
    b.provider_id IS NULL
    AND b.status = 'pending'
)
OR
(
    b.provider_id = ?
)
ORDER BY b.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $providerId);
$stmt->execute();
$result = $stmt->get_result();

/* 📊 Stats */
$totalJobs = 0;
$pendingCount = 0;
$acceptedCount = 0;
$completedCount = 0;
$cancelledCount = 0;

$bookingsArray = [];

while ($row = $result->fetch_assoc()) {
    $bookingsArray[] = $row;
    $totalJobs++;

    if ($row['status'] === 'pending') {
        $pendingCount++;
    } elseif ($row['status'] === 'accepted') {
        $acceptedCount++;
    } elseif ($row['status'] === 'completed') {
        $completedCount++;
    } elseif ($row['status'] === 'cancelled') {
        $cancelledCount++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Provider Dashboard | Tazx Happy-Home</title>
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

/* Navbar */
.navbar {
    position: fixed;
    top: 0;
    width: 100%;
    padding: 1.5rem 5%;
    background: rgba(10, 10, 15, 0.7);
    backdrop-filter: blur(20px) saturate(180%);
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    display: flex;
    justify-content: space-between;
    align-items: center;
    z-index: 1000;
    transition: all 0.3s ease;
}

.navbar.scrolled {
    background: rgba(10, 10, 15, 0.95);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
}

.logo {
    font-weight: 700;
    font-size: 1.3rem;
    background: linear-gradient(135deg, #22d3ee, #a855f7);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.nav-links {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.nav-btn {
    padding: 0.6rem 1.2rem;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 500;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.btn-earnings {
    background: rgba(34, 211, 238, 0.15);
    color: #22d3ee;
    border: 1px solid rgba(34, 211, 238, 0.3);
}

.btn-earnings:hover {
    background: rgba(34, 211, 238, 0.25);
    transform: translateY(-2px);
}

.btn-profile {
    background: rgba(34, 197, 94, 0.15);
    color: #86efac;
    border: 1px solid rgba(34, 197, 94, 0.3);
}

.btn-profile:hover {
    background: rgba(34, 197, 94, 0.25);
    transform: translateY(-2px);
}

.btn-logout {
    background: rgba(239, 68, 68, 0.15);
    color: #fca5a5;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.btn-logout:hover {
    background: rgba(239, 68, 68, 0.25);
    transform: translateY(-2px);
}

/* Container */
.container {
    padding: 8rem 5% 4rem;
    max-width: 1600px;
    margin: auto;
}

/* Welcome Section */
.welcome {
    margin-bottom: 2rem;
    animation: fadeUp 0.6s ease;
}

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

.welcome h1 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.welcome-subtitle {
    font-size: 1rem;
    color: rgba(255, 255, 255, 0.6);
    margin-bottom: 1rem;
}

.highlight-provider {
    background: linear-gradient(135deg, #6366f1, #a855f7);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    font-weight: 700;
}

.profession-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    background: rgba(99, 102, 241, 0.2);
    border: 1px solid rgba(99, 102, 241, 0.3);
    border-radius: 20px;
    color: #a5b4fc;
    font-size: 0.9rem;
    font-weight: 600;
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
    cursor: pointer;
}

.stat-card.active {
    border-color: rgba(99, 102, 241, 0.6);
    background: rgba(99, 102, 241, 0.12);
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

/* Search Section */
.search-section {
    margin-bottom: 2rem;
    animation: fadeUp 0.6s ease 0.2s backwards;
}

.search-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1rem;
}

.search-box {
    position: relative;
}

.search-input {
    width: 100%;
    padding: 1rem 1rem 1rem 3rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    color: #ffffff;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.search-input:focus {
    outline: none;
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(99, 102, 241, 0.5);
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
}

.search-input::placeholder {
    color: rgba(255, 255, 255, 0.4);
}

.search-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: 1.2rem;
    opacity: 0.5;
}

/* Jobs Section */
.jobs-header {
    margin-bottom: 1.5rem;
    animation: fadeUp 0.6s ease 0.3s backwards;
}

.jobs-header h2 {
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
    animation: fadeUp 0.6s ease 0.4s backwards;
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
    background: rgba(34, 197, 94, 0.2);
    color: #86efac;
    border: 1px solid rgba(34, 197, 94, 0.3);
}

.status-completed {
    background: rgba(59, 130, 246, 0.2);
    color: #93c5fd;
    border: 1px solid rgba(59, 130, 246, 0.3);
}

.status-cancelled {
    background: rgba(246, 59, 59, 0.2);
    color: #fd9393;
    border: 1px solid rgba(246, 87, 59, 0.3);
}

/* Action button */
.action-btn {
    padding: 0.6rem 1.2rem;
    background: linear-gradient(135deg, #6366f1, #a855f7);
    color: #ffffff;
    border-radius: 8px;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-block;
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);
}

/* Empty state */
.empty-state {
    text-align: center;
    padding: 3rem 2rem;
    color: rgba(255, 255, 255, 0.5);
}

/* Responsive */
@media (max-width: 768px) {
    .navbar {
        padding: 1.2rem 4%;
    }
    
    .nav-links {
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    
    .nav-btn {
        padding: 0.5rem 0.8rem;
        font-size: 0.85rem;
    }
    
    .container {
        padding: 6rem 4% 3rem;
    }
    
    .table-container {
        overflow-x: auto;
    }
    
    table {
        min-width: 800px;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>
</head>

<body>
<div class="bg-gradient"></div>

<!-- Navbar -->
<nav class="navbar" id="navbar">
    <div class="logo">
        🏠 Tazx Happy-Home
    </div>
    <div class="nav-links">
        <a href="my_earning.php" class="nav-btn btn-earnings">💰 My Earnings</a>
        <a href="profile.php" class="nav-btn btn-profile">👤 Profile</a>
        <a href="../auth/logout.php" class="nav-btn btn-logout">Logout</a>
    </div>
</nav>

<!-- Main Content -->
<div class="container">
    <!-- Welcome Section -->
    <div class="welcome">
        <h1>Welcome back, <?= htmlspecialchars($_SESSION['name']) ?>! 👋</h1>
        <p class="welcome-subtitle"><span class="highlight-provider">Provider</span> Dashboard</p>
        <span class="profession-badge">🔧 <?= htmlspecialchars($provider['profession']) ?> Provider</span>
    </div>

    <!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card filter-card" data-filter="all">
        <h3>Total Provider Jobs</h3>
        <div class="number"><?= $totalJobs ?></div>
    </div>

    <div class="stat-card filter-card" data-filter="pending">
        <h3>Pending</h3>
        <div class="number"><?= $pendingCount ?></div>
    </div>

    <div class="stat-card filter-card" data-filter="accepted">
        <h3>Accepted</h3>
        <div class="number"><?= $acceptedCount ?></div>
    </div>

    <div class="stat-card filter-card" data-filter="completed">
        <h3>Completed</h3>
        <div class="number"><?= $completedCount ?></div>
    </div>

    <div class="stat-card filter-card" data-filter="cancelled">
        <h3>Cancelled</h3>
        <div class="number"><?= $cancelledCount ?></div>
    </div>
</div>

    <!-- Search Section -->
    <div class="search-section">
        <div class="search-grid">
            <div class="search-box">
                <span class="search-icon">🔍</span>
                <input type="text" id="areaSearch" class="search-input" placeholder="Search by area...">
            </div>
            <div class="search-box">
                <span class="search-icon">🛠️</span>
                <input type="text" id="serviceSearch" class="search-input" placeholder="Search by service...">
            </div>
        </div>
    </div>

    <!-- Jobs Section -->
    <div class="jobs-header">
        <h2>Your Assigned Jobs</h2>
    </div>

    <!-- Jobs Table -->
    <div class="table-container">
        <table id="jobsTable">
        <thead>
            <tr>
                <th>Booking ID</th>
                <th>Service</th>
                <th>Date</th>
                <th>Address</th>
                <th>Problem</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
            <tbody>
                <?php if (count($bookingsArray) > 0): ?>
                    <?php foreach ($bookingsArray as $row): ?>
                    <tr class="job-row clickable-row"
                        data-href="my_job.php?id=<?= $row['booking_id'] ?>"
                        data-status="<?= $row['status'] ?>"
                        data-area="<?= strtolower($row['address'].' '.$row['pincode']) ?>"
                        data-service="<?= strtolower($row['service_name']) ?>">

                    <td><strong>#<?= $row['booking_id'] ?></strong></td>
                    <td><?= htmlspecialchars($row['service_name']) ?></td>
                    <td><?= htmlspecialchars($row['booking_date']) ?></td>

                    <td>
                        <?= htmlspecialchars($row['address']) ?> - <?= htmlspecialchars($row['pincode']) ?>
                    </td>

                    <td><?= htmlspecialchars($row['problem_description']) ?></td>

                    <td>
                        <?php if ($row['status'] === 'pending'): ?>
                            <span class="status-badge status-pending">Pending</span>

                        <?php elseif ($row['status'] === 'accepted'): ?>
                            <span class="status-badge status-accepted">Accepted</span>

                        <?php elseif ($row['status'] === 'completed'): ?>
                            <span class="status-badge status-completed">Completed</span>

                        <?php elseif ($row['status'] === 'cancelled'): ?>
                            <span class="status-badge status-cancelled">Cancelled</span>
                        <?php endif; ?>
                    </td>

                    <td>
                        <?php if ($row['status'] === 'pending'): ?>

                            <a class="action-btn stop-row-click"
                            href="accept_job.php?id=<?= $row['booking_id'] ?>">
                                Accept Job
                            </a>

                        <?php elseif (!empty($row['provider_note'])): ?>

                            <span style="
                                font-size: 0.85rem;
                                color: rgba(255,255,255,0.75);
                                font-style: italic;
                            ">
                                <?= htmlspecialchars($row['provider_note']) ?>
                            </span>

                        <?php else: ?>

                            <span style="opacity: 0.5;">—</span>

                        <?php endif; ?>
                    </td>

                </tr>

                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="empty-state">
                            No jobs assigned yet. Check back later!
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Navbar scroll effect
window.addEventListener("scroll", () => {
    const navbar = document.getElementById("navbar");
    navbar.classList.toggle("scrolled", window.scrollY > 50);
});

// Elements
const areaSearch = document.getElementById('areaSearch');
const serviceSearch = document.getElementById('serviceSearch');
const jobRows = document.querySelectorAll('.job-row');
const filterCards = document.querySelectorAll('.filter-card');

let activeStatusFilter = 'all';

// 🔹 MAIN FILTER FUNCTION
function filterJobs() {
    const areaQuery = areaSearch.value.toLowerCase().trim();
    const serviceQuery = serviceSearch.value.toLowerCase().trim();

    let visibleCount = 0;

    jobRows.forEach(row => {
        const area = row.dataset.area;
        const service = row.dataset.service;
        const status = row.dataset.status;

        const matchesArea = area.includes(areaQuery);
        const matchesService = service.includes(serviceQuery);
        const matchesStatus =
            activeStatusFilter === 'all' || status === activeStatusFilter;

        if (matchesArea && matchesService && matchesStatus) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    // Empty state
    const tbody = document.querySelector('#jobsTable tbody');
    let emptyRow = tbody.querySelector('.search-empty-state');

    if (visibleCount === 0 && jobRows.length > 0) {
        if (!emptyRow) {
            emptyRow = document.createElement('tr');
            emptyRow.className = 'search-empty-state';
            emptyRow.innerHTML =
                '<td colspan="7" class="empty-state">No jobs match this filter.</td>';
            tbody.appendChild(emptyRow);
        }
    } else if (emptyRow) {
        emptyRow.remove();
    }
}

// 🔹 SEARCH LISTENERS
areaSearch.addEventListener('input', filterJobs);
serviceSearch.addEventListener('input', filterJobs);

// 🔹 STATUS CARD FILTER
filterCards.forEach(card => {
    card.addEventListener('click', () => {
        // Active style
        filterCards.forEach(c => c.classList.remove('active'));
        card.classList.add('active');

        // Set filter
        activeStatusFilter = card.dataset.filter;

        filterJobs();
    });
});

// Make entire row clickable
document.querySelectorAll('.clickable-row').forEach(row => {
    row.addEventListener('click', () => {
        window.location.href = row.dataset.href;
    });
});

// Prevent Accept button from triggering row click
document.querySelectorAll('.stop-row-click').forEach(btn => {
    btn.addEventListener('click', e => {
        e.stopPropagation();
    });
});

// 🔹 DEFAULT LOAD = SHOW ALL
filterCards[0].classList.add('active');
filterJobs();
</script>

</body>
</html>