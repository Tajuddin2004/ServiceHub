<?php
session_start();
require_once dirname(__DIR__) . "/config/db.php";

/* 🔐 Protect provider */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'provider') {
    header("Location: ../auth/login.php");
    exit;
}

$userId = $_SESSION['user_id'];

/* 🔹 Validate booking ID */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid job.");
}
$bookingId = (int) $_GET['id'];

/* 🔹 Get provider_id */
$p = $conn->prepare("SELECT provider_id FROM service_providers WHERE user_id = ?");
$p->bind_param("i", $userId);
$p->execute();
$provider = $p->get_result()->fetch_assoc();

if (!$provider) {
    die("Provider not found.");
}
$providerId = (int) $provider['provider_id'];

/* 🔹 Fetch FULL job details */
$stmt = $conn->prepare("
    SELECT
        b.booking_id,
        b.booking_date,
        b.address,
        b.pincode,
        b.problem_description,
        b.status,
        b.contact_phone,
        b.service_charge,
        b.material_charge,
        b.gst_amount,
        b.total_amount,
        b.payment_method,
        b.payment_status,
        b.closed_at,
        b.provider_note,

        s.service_name,

        u.name AS customer_name,
        u.email,
        u.phone
    FROM bookings b
    JOIN services s ON s.service_id = b.service_id
    JOIN users u ON u.user_id = b.consumer_id
    WHERE b.booking_id = ?
      AND b.provider_id = ?
");
$stmt->bind_param("ii", $bookingId, $providerId);
$stmt->execute();
$job = $stmt->get_result()->fetch_assoc();

if (!$job) {
    die("Job not found or access denied.");
}

/* 🔹 Handle completion / cancellation */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $job['status'] === 'accepted') {

    if (empty($_POST['provider_note'])) {
        die("Provider note required.");
    }

    $note = substr(trim($_POST['provider_note']), 0, 30);

    /* ✅ COMPLETE JOB */
    if (isset($_POST['complete_work'])) {

        // 1️⃣ Update booking
        $u = $conn->prepare("
            UPDATE bookings
            SET 
                status = 'completed',
                payment_status = 'paid',
                provider_note = ?,
                closed_at = NOW()
            WHERE booking_id = ?
            AND provider_id = ?
        ");
        $u->bind_param("sii", $note, $bookingId, $providerId);
        $u->execute();

        // 2️⃣ Insert payment record
        $pay = $conn->prepare("
            INSERT INTO payments (
                booking_id,
                amount,
                method,
                status,
                created_at
            ) VALUES (?, ?, ?, 'paid', NOW())
        ");
        $pay->bind_param(
            "ids",
            $bookingId,
            $job['total_amount'],
            $job['payment_method']
        );
        $pay->execute();

        header("Location: home.php?success=job_completed");
        exit;
    }

    /* ❌ CANCEL JOB */
    if (isset($_POST['cancel_work'])) {

        $u = $conn->prepare("
            UPDATE bookings
            SET 
                status = 'cancelled',
                payment_status = 'cancelled',
                provider_note = ?,
                closed_at = NOW()
            WHERE booking_id = ?
            AND provider_id = ?
        ");
        $u->bind_param("sii", $note, $bookingId, $providerId);
        $u->execute();

        header("Location: home.php?success=job_cancelled");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Job Details | Service-Hub</title>
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
    max-width: 1200px;
    margin: auto;
    min-height: 100vh;
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

.page-header .subtitle {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.95rem;
}

/* Card Styles */
.card {
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(30px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    padding: 2.5rem;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
    animation: fadeUp 0.6s ease backwards;
}

.card:nth-child(2) { animation-delay: 0.1s; }
.card:nth-child(3) { animation-delay: 0.2s; }
.card:nth-child(4) { animation-delay: 0.3s; }
.card:nth-child(5) { animation-delay: 0.4s; }
.card:nth-child(6) { animation-delay: 0.5s; }

.card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #6366f1, #a855f7, #22d3ee);
    border-radius: 20px 20px 0 0;
}

.card-header {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    margin-bottom: 2rem;
}

.card-header h2 {
    font-size: 1.5rem;
    font-weight: 700;
}

.card-icon {
    font-size: 2rem;
}

/* Info Rows */
.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.9rem;
}

.info-value {
    color: #ffffff;
    font-weight: 500;
}

.status-badge {
    display: inline-block;
    padding: 0.4rem 1rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: capitalize;
}

.status-accepted {
    background: rgba(34, 197, 94, 0.2);
    color: #86efac;
    border: 1px solid rgba(34, 197, 94, 0.3);
}

.status-completed {
    background: rgba(34, 211, 238, 0.2);
    color: #22d3ee;
    border: 1px solid rgba(34, 211, 238, 0.3);
}

.status-cancelled {
    background: rgba(239, 68, 68, 0.2);
    color: #fca5a5;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.status-pending {
    background: rgba(245, 158, 11, 0.2);
    color: #fbbf24;
    border: 1px solid rgba(245, 158, 11, 0.3);
}

/* Payment Card Special Styling */
.payment-card::before {
    background: linear-gradient(90deg, #22c55e, #86efac);
}

.payment-amount {
    font-size: 2rem;
    font-weight: 700;
    background: linear-gradient(135deg, #86efac, #22c55e);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin: 1.5rem 0;
}

.payment-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.payment-item {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    padding: 1.2rem;
    text-align: center;
}

.payment-item-label {
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.6);
    margin-bottom: 0.5rem;
}

.payment-item-value {
    font-size: 1.3rem;
    font-weight: 600;
    color: #22d3ee;
}

/* Form Styles */
.form-group {
    margin-bottom: 1.5rem;
}

.form-input {
    width: 100%;
    padding: 1rem 1.2rem;
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
    border-color: rgba(99, 102, 241, 0.5);
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
}

.form-input::placeholder {
    color: rgba(255, 255, 255, 0.4);
}

.button-group {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
    flex-wrap: wrap;
}

.btn {
    padding: 1rem 2rem;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-complete {
    background: linear-gradient(135deg, #22c55e, #86efac);
    color: #ffffff;
    flex: 1;
}

.btn-complete:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(34, 197, 94, 0.3);
}

.btn-cancel {
    background: linear-gradient(135deg, #ef4444, #fca5a5);
    color: #ffffff;
    flex: 1;
}

.btn-cancel:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(239, 68, 68, 0.3);
}

/* Action Card */
.action-card::before {
    background: linear-gradient(90deg, #6366f1, #a855f7);
}

/* Responsive */
@media (max-width: 768px) {
    .container {
        padding: 2rem 4%;
    }
    
    .card {
        padding: 2rem 1.5rem;
    }
    
    .page-header h1 {
        font-size: 1.75rem;
    }
    
    .info-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .payment-grid {
        grid-template-columns: 1fr;
    }
    
    .button-group {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
}

/* Additional Animation */
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.8; }
}

.payment-amount {
    animation: pulse 2s ease-in-out infinite;
}
</style>
</head>

<body>
<div class="bg-gradient"></div>

<div class="container">
    <!-- Header -->
    <div class="page-header">
        <a href="home.php" class="back-link">← Back to Dashboard</a>
        <h1>🛠️ Job Details</h1>
        <p class="subtitle">View and manage job information</p>
    </div>

    <!-- Job Overview Card -->
    <div class="card">
        <div class="card-header">
            <span class="card-icon">📋</span>
            <h2><?= htmlspecialchars($job['service_name']) ?></h2>
        </div>
        
        <div class="info-row">
            <span class="info-label">Booking ID</span>
            <span class="info-value">#<?= $job['booking_id'] ?></span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Status</span>
            <span class="status-badge status-<?= strtolower($job['status']) ?>">
                <?= ucfirst($job['status']) ?>
            </span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Booking Date</span>
            <span class="info-value"><?= htmlspecialchars($job['booking_date']) ?></span>
        </div>
        
        <?php if (!empty($job['closed_at'])): ?>
        <div class="info-row">
            <span class="info-label">Closed At</span>
            <span class="info-value"><?= date('d M Y, h:i A', strtotime($job['closed_at'])) ?></span>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($job['provider_note'])): ?>
        <div class="info-row">
            <span class="info-label">Provider Note</span>
            <span class="info-value"><?= htmlspecialchars($job['provider_note']) ?></span>
        </div>
        <?php endif; ?>
    </div>

    <!-- Customer Details Card -->
    <div class="card">
        <div class="card-header">
            <span class="card-icon">👤</span>
            <h2>Customer Details</h2>
        </div>
        
        <div class="info-row">
            <span class="info-label">Name</span>
            <span class="info-value"><?= htmlspecialchars($job['customer_name']) ?></span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Email</span>
            <span class="info-value"><?= htmlspecialchars($job['email']) ?></span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Phone</span>
            <span class="info-value"><?= htmlspecialchars($job['contact_phone'] ?: '+91 '.$job['phone']) ?></span>
        </div>
    </div>

    <!-- Job Location Card -->
    <div class="card">
        <div class="card-header">
            <span class="card-icon">📍</span>
            <h2>Job Location & Details</h2>
        </div>
        
        <div class="info-row">
            <span class="info-label">Address</span>
            <span class="info-value"><?= htmlspecialchars($job['address']) ?></span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Pincode</span>
            <span class="info-value"><?= htmlspecialchars($job['pincode']) ?></span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Problem Description</span>
            <span class="info-value"><?= htmlspecialchars($job['problem_description']) ?></span>
        </div>
    </div>

    <!-- Payment Details Card -->
    <?php if (!empty($job['service_charge'])): ?>
    <div class="card payment-card">
        <div class="card-header">
            <span class="card-icon">💰</span>
            <h2>Payment Details</h2>
        </div>
        
        <div class="payment-grid">
            <div class="payment-item">
                <div class="payment-item-label">Service Charge</div>
                <div class="payment-item-value">₹<?= number_format($job['service_charge'], 2) ?></div>
            </div>
            
            <div class="payment-item">
                <div class="payment-item-label">Material Charge</div>
                <div class="payment-item-value">₹<?= number_format($job['material_charge'], 2) ?></div>
            </div>
            
            <div class="payment-item">
                <div class="payment-item-label">GST Amount</div>
                <div class="payment-item-value">₹<?= number_format($job['gst_amount'], 2) ?></div>
            </div>
        </div>
        
        <div class="payment-amount">
            💵 Total: ₹<?= number_format($job['total_amount'], 2) ?>
        </div>
        
        <div class="info-row">
            <span class="info-label">Payment Method</span>
            <span class="info-value"><?= strtoupper($job['payment_method']) ?></span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Payment Status</span>
            <span class="status-badge status-<?= strtolower($job['payment_status']) ?>">
                <?= ucfirst($job['payment_status']) ?>
            </span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Action Card -->
    <?php if ($job['status'] === 'accepted'): ?>
    <div class="card action-card">
        <div class="card-header">
            <span class="card-icon">🔁</span>
            <h2>Reconfirm Job</h2>
        </div>
        
        <form method="POST">
            <div class="form-group">
                <input
                    type="text"
                    name="provider_note"
                    class="form-input"
                    maxlength="30"
                    required
                    placeholder="Completion / cancellation note (max 30 characters)"
                >
            </div>
            
            <div class="button-group">
                <button type="submit" name="complete_work" class="btn btn-complete">
                    ✅ Complete Work
                </button>
                <button type="submit" name="cancel_work" class="btn btn-cancel">
                    ❌ Cancel Job
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>

</div>

</body>
</html>