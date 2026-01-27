<?php
session_start();
require_once dirname(__DIR__) . "/config/db.php";

/* 🔐 Protect admin access */
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

/* 🔍 Status filter */
$statusFilter = $_GET['status'] ?? 'all';

$where = "";
if ($statusFilter === 'pending') {
    $where = "AND sp.is_approved = 0";
} elseif ($statusFilter === 'approved') {
    $where = "AND sp.is_approved = 1";
} elseif ($statusFilter === 'rejected') {
    $where = "AND sp.is_approved = -1";
}

/* 📊 Fetch providers */
$sql = "
SELECT 
    u.user_id,
    u.name,
    u.email,
    sp.profession,
    sp.experience,
    sp.about_work,
    sp.pincode,
    sp.phone,
    sp.is_approved
FROM users u
JOIN service_providers sp ON sp.user_id = u.user_id
WHERE u.role = 'provider'
$where
ORDER BY u.user_id DESC
";

$result = $conn->query($sql);

/* Get success/error messages */
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Provider Applications | Service-Hub</title>
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

/* Back link at top */
.back-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 2rem;
    padding: 1rem 1.5rem;
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    font-size: 0.95rem;
    font-weight: 500;
    transition: all 0.3s ease;
    animation: fadeUp 0.6s ease;
}

.back-link:hover {
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(239, 68, 68, 0.3);
    color: #ffffff;
    transform: translateX(3px);
}

/* Page Header */
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

/* Search Bar */
.search-container {
    position: relative;
    width: 100%;
    max-width: 500px;
    animation: fadeUp 0.6s ease 0.1s backwards;
}

.search-bar {
    width: 100%;
    padding: 1rem 1.25rem 1rem 3.5rem;
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    color: #ffffff;
    font-size: 1rem;
    font-family: 'Inter', sans-serif;
    transition: all 0.3s ease;
}

.search-bar:focus {
    outline: none;
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(239, 68, 68, 0.5);
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.search-bar::placeholder {
    color: rgba(255, 255, 255, 0.4);
}

.search-icon {
    position: absolute;
    left: 1.25rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: 1.2rem;
    color: rgba(255, 255, 255, 0.4);
    pointer-events: none;
}

/* Alert Messages */
.alert {
    padding: 1rem 1.5rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
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
    background: rgba(34, 197, 94, 0.15);
    border: 1px solid rgba(34, 197, 94, 0.3);
    color: #86efac;
}

.alert-error {
    background: rgba(239, 68, 68, 0.15);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #fca5a5;
}

/* Filter Section */
.filter-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
    animation: fadeUp 0.6s ease 0.2s backwards;
}

.filter-btns {
    display: flex;
    gap: 0.8rem;
    flex-wrap: wrap;
}

.filter-btn {
    padding: 0.8rem 1.5rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.05);
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
}

.filter-btn:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(239, 68, 68, 0.5);
    transform: translateY(-2px);
}

.filter-btn.active {
    background: linear-gradient(135deg, #ef4444, #f97316);
    border-color: transparent;
    color: #ffffff;
    box-shadow: 0 5px 20px rgba(239, 68, 68, 0.3);
}

.results-count {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.95rem;
}

/* Table Container */
.table-container {
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(30px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    overflow: hidden;
    animation: fadeUp 0.6s ease 0.3s backwards;
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
    font-weight: 700;
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.8);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

td {
    padding: 1.2rem 1rem;
    font-size: 0.9rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

tbody tr {
    transition: all 0.2s ease;
}

tbody tr:hover {
    background: rgba(255, 255, 255, 0.05);
}

tbody tr.hidden {
    display: none;
}

/* Status badges */
.status-badge {
    display: inline-block;
    padding: 0.4rem 0.9rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.status-approved {
    background: rgba(34, 197, 94, 0.2);
    color: #86efac;
    border: 1px solid rgba(34, 197, 94, 0.3);
}

.status-pending {
    background: rgba(251, 146, 60, 0.2);
    color: #fdba74;
    border: 1px solid rgba(251, 146, 60, 0.3);
}

.status-rejected {
    background: rgba(239, 68, 68, 0.2);
    color: #fca5a5;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

/* Action buttons */
.action-btn {
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s ease;
    margin-right: 0.5rem;
}

.btn-approve {
    background: rgba(34, 197, 94, 0.15);
    color: #86efac;
    border: 1px solid rgba(34, 197, 94, 0.3);
}

.btn-approve:hover {
    background: rgba(34, 197, 94, 0.25);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(34, 197, 94, 0.2);
}

.btn-reject {
    background: rgba(239, 68, 68, 0.15);
    color: #fca5a5;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.btn-reject:hover {
    background: rgba(239, 68, 68, 0.25);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(239, 68, 68, 0.2);
}

/* Empty state */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: rgba(255, 255, 255, 0.5);
}

.empty-state::before {
    content: '🔍';
    display: block;
    font-size: 3rem;
    margin-bottom: 1rem;
}

/* 🎉 EASTER EGG STYLES */
.secret-mode {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 9999;
    opacity: 0;
    transition: opacity 0.5s ease;
}

.secret-mode.active {
    opacity: 1;
}

.secret-mode::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: radial-gradient(circle at var(--mouse-x, 50%) var(--mouse-y, 50%), 
                rgba(139, 92, 246, 0.3) 0%, 
                rgba(236, 72, 153, 0.2) 30%, 
                transparent 70%);
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 0.5; }
    50% { opacity: 1; }
}

.easter-egg-notification {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0);
    background: linear-gradient(135deg, rgba(139, 92, 246, 0.95), rgba(236, 72, 153, 0.95));
    backdrop-filter: blur(20px);
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 20px;
    padding: 3rem;
    text-align: center;
    z-index: 10000;
    box-shadow: 0 20px 60px rgba(139, 92, 246, 0.5);
    pointer-events: all;
    transition: transform 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.easter-egg-notification.show {
    transform: translate(-50%, -50%) scale(1);
}

.easter-egg-notification h2 {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
}

.easter-egg-notification p {
    font-size: 1.2rem;
    margin-bottom: 2rem;
    opacity: 0.9;
}

.easter-egg-notification button {
    padding: 1rem 2rem;
    background: rgba(255, 255, 255, 0.2);
    border: 2px solid rgba(255, 255, 255, 0.5);
    border-radius: 12px;
    color: white;
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.easter-egg-notification button:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
}

.confetti {
    position: fixed;
    width: 10px;
    height: 10px;
    background: #f0f;
    position: absolute;
    animation: confetti-fall 3s linear forwards;
    z-index: 9998;
}

@keyframes confetti-fall {
    to {
        transform: translateY(100vh) rotate(360deg);
        opacity: 0;
    }
}

/* Disco mode for table rows */
.disco-mode tbody tr {
    animation: rainbow-row 3s linear infinite;
}

.disco-mode tbody tr:nth-child(2n) {
    animation-delay: 0.5s;
}

.disco-mode tbody tr:nth-child(3n) {
    animation-delay: 1s;
}

@keyframes rainbow-row {
    0% { background: rgba(255, 0, 0, 0.1); }
    16% { background: rgba(255, 127, 0, 0.1); }
    33% { background: rgba(255, 255, 0, 0.1); }
    50% { background: rgba(0, 255, 0, 0.1); }
    66% { background: rgba(0, 0, 255, 0.1); }
    83% { background: rgba(139, 0, 255, 0.1); }
    100% { background: rgba(255, 0, 0, 0.1); }
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
    
    .search-container {
        max-width: 100%;
    }
    
    .filter-section {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .table-container {
        overflow-x: auto;
    }
    
    table {
        min-width: 900px;
    }
    
    .easter-egg-notification {
        width: 90%;
        padding: 2rem;
    }
    
    .easter-egg-notification h2 {
        font-size: 1.8rem;
    }
}
</style>
</head>

<body>
<div class="bg-gradient"></div>
<div class="grid-overlay"></div>

<!-- 🎉 Secret Mode Overlay -->
<div class="secret-mode" id="secretMode"></div>

<!-- 🎊 Easter Egg Notification -->
<div class="easter-egg-notification" id="easterEggNotification">
    <h2>🎉 DISCO MODE ACTIVATED! 🎉</h2>
    <p>You found the secret! Press ESC to return to normal.</p>
    <button onclick="deactivateDiscoMode()">Back to Reality</button>
</div>

<div class="container">
    <!-- Back to Dashboard Link -->
    <div style="display: flex; justify-content: flex-end;">
        <a href="home.php" class="back-link">
            <span>← Back to Dashboard</span>
            
        </a>
    </div>

    <!-- Page Header -->
    <div class="page-header">
        <div class="welcome-section">
            <h1>Provider Applications 🧑‍🔧</h1>
            <p>Review and manage service provider applications</p>
        </div>
        
        <div class="search-container">
            <span class="search-icon">🔍</span>
            <input type="text" id="searchInput" class="search-bar" placeholder="Search by name, email, profession...">
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if ($success === 'approved'): ?>
        <div class="alert alert-success">
            ✅ Provider has been approved successfully!
        </div>
    <?php elseif ($success === 'rejected'): ?>
        <div class="alert alert-success">
            ✅ Provider application has been rejected.
        </div>
    <?php elseif ($error === 'invalid_request'): ?>
        <div class="alert alert-error">
            ⚠️ Invalid request. Please try again.
        </div>
    <?php elseif ($error === 'approval_failed'): ?>
        <div class="alert alert-error">
            ⚠️ Failed to approve provider. Please try again.
        </div>
    <?php elseif ($error === 'rejection_failed'): ?>
        <div class="alert alert-error">
            ⚠️ Failed to reject provider. Please try again.
        </div>
    <?php endif; ?>

    <!-- Filter Section -->
    <div class="filter-section">
        <div class="filter-btns">
            <a href="providers.php?status=pending" class="filter-btn <?= $statusFilter === 'pending' ? 'active' : '' ?>">
                ⏳ Pending
            </a>
            <a href="providers.php?status=approved" class="filter-btn <?= $statusFilter === 'approved' ? 'active' : '' ?>">
                ✅ Approved
            </a>
            <a href="providers.php?status=rejected" class="filter-btn <?= $statusFilter === 'rejected' ? 'active' : '' ?>">
                ❌ Rejected
            </a>
            <a href="providers.php" class="filter-btn <?= $statusFilter === 'all' ? 'active' : '' ?>">
                📋 All
            </a>
        </div>
        <div class="results-count" id="resultsCount">
            Showing <?= $result->num_rows ?> provider(s)
        </div>
    </div>

    <!-- Table -->
    <div class="table-container" id="tableContainer">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Profession</th>
                    <th>Experience</th>
                    <th>About Work</th>
                    <th>Pincode</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr data-name="<?= htmlspecialchars(strtolower($row['name'])) ?>" 
                        data-email="<?= htmlspecialchars(strtolower($row['email'])) ?>" 
                        data-profession="<?= htmlspecialchars(strtolower($row['profession'])) ?>">
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= htmlspecialchars($row['profession']) ?></td>
                        <td><?= (int)$row['experience'] ?> yrs</td>
                        <td><?= htmlspecialchars(substr($row['about_work'], 0, 50)) ?>...</td>
                        <td><?= htmlspecialchars($row['pincode']) ?></td>
                        <td>+91 <?= htmlspecialchars($row['phone']) ?></td>
                        <td>
                            <?php
                            if ($row['is_approved'] == 1) {
                                echo '<span class="status-badge status-approved">Approved</span>';
                            } elseif ($row['is_approved'] == -1) {
                                echo '<span class="status-badge status-rejected">Rejected</span>';
                            } else {
                                echo '<span class="status-badge status-pending">Pending</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <?php if ($row['is_approved'] == 0): ?>
                                <a href="approve.php?id=<?= $row['user_id'] ?>&action=approve" 
                                   class="action-btn btn-approve"
                                   onclick="return confirm('Are you sure you want to approve this provider?')">
                                    Approve
                                </a>
                                <a href="approve.php?id=<?= $row['user_id'] ?>&action=reject" 
                                   class="action-btn btn-reject"
                                   onclick="return confirm('Are you sure you want to reject this provider?')">
                                    Reject
                                </a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr id="emptyRow">
                        <td colspan="9" class="empty-state">
                            No provider applications found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Auto-hide success/error alerts after 5 seconds
const alerts = document.querySelectorAll('.alert');
alerts.forEach(alert => {
    setTimeout(() => {
        alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-20px)';
        setTimeout(() => alert.remove(), 500);
    }, 5000);
});

// 🔍 LIVE SEARCH FUNCTIONALITY
const searchInput = document.getElementById('searchInput');
const tableBody = document.getElementById('tableBody');
const resultsCount = document.getElementById('resultsCount');
const emptyRow = document.getElementById('emptyRow');

searchInput.addEventListener('input', (e) => {
    const searchTerm = e.target.value.toLowerCase().trim();
    const rows = tableBody.querySelectorAll('tr:not(#emptyRow)');
    let visibleCount = 0;
    
    rows.forEach(row => {
        const name = row.dataset.name || '';
        const email = row.dataset.email || '';
        const profession = row.dataset.profession || '';
        
        const matches = name.includes(searchTerm) || 
                       email.includes(searchTerm) || 
                       profession.includes(searchTerm);
        
        if (matches) {
            row.classList.remove('hidden');
            visibleCount++;
        } else {
            row.classList.add('hidden');
        }
    });
    
    // Update results count
    resultsCount.textContent = `Showing ${visibleCount} provider(s)`;
    
    // Show/hide empty state
    if (emptyRow && visibleCount === 0 && searchTerm !== '') {
        emptyRow.classList.remove('hidden');
        emptyRow.querySelector('.empty-state').innerHTML = `
            No results found for "${e.target.value}"<br>
            <small style="font-size: 0.85rem; margin-top: 0.5rem; display: block; opacity: 0.7;">Try a different search term</small>
        `;
    } else if (emptyRow) {
        if (rows.length === 0) {
            emptyRow.classList.remove('hidden');
            emptyRow.querySelector('.empty-state').textContent = 'No provider applications found.';
        } else {
            emptyRow.classList.add('hidden');
        }
    }
});

// 🎉 EASTER EGG: Disco Mode Activated by typing "disco"
let typedSequence = '';
let discoModeActive = false;
const secretCode = 'disco';

document.addEventListener('keydown', (e) => {
    // Don't capture typing in search input
    if (e.target === searchInput) {
        return;
    }
    
    // ESC to exit disco mode
    if (e.key === 'Escape' && discoModeActive) {
        deactivateDiscoMode();
        return;
    }
    
    // Build the typed sequence
    typedSequence += e.key.toLowerCase();
    
    // Keep only last 5 characters
    if (typedSequence.length > secretCode.length) {
        typedSequence = typedSequence.slice(-secretCode.length);
    }
    
    // Check if secret code is typed
    if (typedSequence === secretCode && !discoModeActive) {
        activateDiscoMode();
    }
});

function activateDiscoMode() {
    discoModeActive = true;
    
    // Show notification
    const notification = document.getElementById('easterEggNotification');
    notification.classList.add('show');
    
    // Activate secret mode overlay
    const secretMode = document.getElementById('secretMode');
    secretMode.classList.add('active');
    
    // Add disco mode to table
    const tableContainer = document.getElementById('tableContainer');
    tableContainer.classList.add('disco-mode');
    
    // Create confetti
    createConfetti();
    
    // Track mouse for gradient effect
    document.addEventListener('mousemove', updateMousePosition);
}

function deactivateDiscoMode() {
    discoModeActive = false;
    typedSequence = '';
    
    // Hide notification
    const notification = document.getElementById('easterEggNotification');
    notification.classList.remove('show');
    
    // Deactivate secret mode overlay
    const secretMode = document.getElementById('secretMode');
    secretMode.classList.remove('active');
    
    // Remove disco mode from table
    const tableContainer = document.getElementById('tableContainer');
    tableContainer.classList.remove('disco-mode');
    
    // Remove confetti
    document.querySelectorAll('.confetti').forEach(c => c.remove());
    
    // Stop tracking mouse
    document.removeEventListener('mousemove', updateMousePosition);
}

function updateMousePosition(e) {
    const secretMode = document.getElementById('secretMode');
    const x = (e.clientX / window.innerWidth) * 100;
    const y = (e.clientY / window.innerHeight) * 100;
    secretMode.style.setProperty('--mouse-x', x + '%');
    secretMode.style.setProperty('--mouse-y', y + '%');
}

function createConfetti() {
    const colors = ['#ff0080', '#ff8c00', '#ffff00', '#00ff00', '#0080ff', '#8b00ff'];
    
    for (let i = 0; i < 50; i++) {
        setTimeout(() => {
            const confetti = document.createElement('div');
            confetti.className = 'confetti';
            confetti.style.left = Math.random() * 100 + '%';
            confetti.style.top = '-10px';
            confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
            confetti.style.animationDelay = Math.random() * 0.5 + 's';
            confetti.style.animationDuration = (Math.random() * 2 + 2) + 's';
            document.body.appendChild(confetti);
            
            setTimeout(() => confetti.remove(), 3000);
        }, i * 50);
    }
}
</script>

</body>
</html>