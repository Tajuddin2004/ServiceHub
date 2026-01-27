<?php
session_start();
require_once dirname(__DIR__) . "/config/db.php";

/* 🔐 Protect consumer */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'consumer') {
    header("Location: ../auth/login.php");
    exit;
}

$consumerId = $_SESSION['user_id'];

/* 🔎 Validate booking ID */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid booking.");
}

$bookingId = (int) $_GET['id'];

/* 📦 Fetch full booking details */
$stmt = $conn->prepare("
    SELECT
        b.booking_id,
        b.booking_date,
        b.closed_at,
        b.address,
        b.problem_description,
        b.provider_note,
        b.status AS booking_status,
        b.payment_method,
        b.payment_status,
        b.provider_id,

        -- 💰 Charges
        b.service_charge,
        b.material_charge,
        b.gst_amount,
        b.total_amount,

        s.service_name,

        u.name AS provider_name,
        sp.profession,
        sp.phone,
        sp.pincode

    FROM bookings b
    JOIN services s ON s.service_id = b.service_id
    LEFT JOIN service_providers sp ON sp.provider_id = b.provider_id
    LEFT JOIN users u ON u.user_id = sp.user_id
    WHERE b.booking_id = ?
      AND b.consumer_id = ?
");
$stmt->bind_param("ii", $bookingId, $consumerId);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    die("Booking not found.");
}

/* 🎨 Status color helper */
function statusColor($status) {
    return match ($status) {
        'pending'   => 'pending',
        'accepted'  => 'accepted',
        'completed' => 'completed',
        'cancelled' => 'cancelled',
        default     => 'default',
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Booking Details | Service-Hub</title>
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
    max-width: 1000px;
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

.booking-id {
    display: inline-block;
    padding: 0.5rem 1rem;
    background: rgba(99, 102, 241, 0.2);
    border: 1px solid rgba(99, 102, 241, 0.3);
    border-radius: 20px;
    color: #a5b4fc;
    font-size: 0.9rem;
    font-weight: 600;
}

/* Main Card */
.details-card {
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(30px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    padding: 2.5rem;
    animation: fadeUp 0.6s ease 0.2s backwards;
    position: relative;
    overflow: hidden;
}

.details-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #6366f1, #a855f7, #22d3ee);
    border-radius: 20px 20px 0 0;
}

/* Section */
.section {
    margin-bottom: 2.5rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.section:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.section-title {
    font-size: 1.3rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Info Grid */
.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.info-item {
    padding: 1.2rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    transition: all 0.3s ease;
}

.info-item:hover {
    background: rgba(255, 255, 255, 0.08);
    transform: translateY(-2px);
}

.info-label {
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.6);
    margin-bottom: 0.5rem;
}

.info-value {
    font-size: 1rem;
    font-weight: 500;
    color: #ffffff;
}

.not-closed {
    color: rgba(255, 255, 255, 0.4);
    font-style: italic;
}

/* Status Badge */
.status-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.9rem;
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
    background: rgba(239, 68, 68, 0.2);
    color: #fca5a5;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.status-default {
    background: rgba(156, 163, 175, 0.2);
    color: #d1d5db;
    border: 1px solid rgba(156, 163, 175, 0.3);
}

/* Provider Note */
.note-box {
    padding: 1.5rem;
    background: rgba(59, 130, 246, 0.1);
    border: 1px solid rgba(59, 130, 246, 0.3);
    border-left: 4px solid #3b82f6;
    border-radius: 12px;
    color: #93c5fd;
    line-height: 1.6;
}

/* Bill Details */
.bill-row {
    display: flex;
    justify-content: space-between;
    padding: 0.8rem 1.2rem;
    margin-bottom: 0.5rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
    transition: all 0.3s ease;
}

.bill-row:hover {
    background: rgba(255, 255, 255, 0.08);
}

.bill-label {
    color: rgba(255, 255, 255, 0.7);
    font-weight: 500;
}

.bill-value {
    color: #ffffff;
    font-weight: 600;
}

.bill-total {
    margin-top: 1rem;
    padding: 1.2rem;
    background: linear-gradient(135deg, rgba(34, 197, 94, 0.15), rgba(34, 197, 94, 0.05));
    border: 1px solid rgba(34, 197, 94, 0.3);
    border-radius: 12px;
}

.bill-total .bill-value {
    font-size: 1.5rem;
    color: #86efac;
}

/* Payment Method Badge */
.payment-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.6rem 1.2rem;
    background: rgba(99, 102, 241, 0.15);
    border: 1px solid rgba(99, 102, 241, 0.3);
    border-radius: 20px;
    color: #a5b4fc;
    font-weight: 600;
}

/* Responsive */
@media (max-width: 768px) {
    .container {
        padding: 2rem 4%;
    }
    
    .details-card {
        padding: 2rem 1.5rem;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
    
    .bill-row {
        flex-direction: column;
        gap: 0.5rem;
    }
}
</style>
</head>

<body>
<div class="bg-gradient"></div>

<div class="container">
    <!-- Header -->
    <div class="page-header">
        <a href="account.php" class="back-link">← Back to My Account</a>
        <h1>Booking Details</h1>
        <span class="booking-id">📄 Booking #<?= $booking['booking_id'] ?></span>
    </div>

    <!-- Main Details Card -->
    <div class="details-card">
        
        <!-- Basic Information -->
        <div class="section">
            <h2 class="section-title">📋 Basic Information</h2>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Service Type</div>
                    <div class="info-value"><?= htmlspecialchars($booking['service_name']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Booking Date</div>
                    <div class="info-value"><?= htmlspecialchars(date('M d, Y', strtotime($booking['booking_date']))) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Status</div>
                    <div class="info-value">
                        <span class="status-badge status-<?= statusColor($booking['booking_status']) ?>">
                            <?= ucfirst($booking['booking_status']) ?>
                        </span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Closing Date</div>
                    <div class="info-value <?= !$booking['closed_at'] ? 'not-closed' : '' ?>">
                        <?= $booking['closed_at'] ? htmlspecialchars(date('M d, Y', strtotime($booking['closed_at']))) : 'Not closed yet' ?>
                    </div>
                </div>
            </div>

            <div class="info-grid" style="margin-top: 1.5rem;">
                <div class="info-item" style="grid-column: 1 / -1;">
                    <div class="info-label">Address</div>
                    <div class="info-value"><?= htmlspecialchars($booking['address']) ?></div>
                </div>
                <div class="info-item" style="grid-column: 1 / -1;">
                    <div class="info-label">Problem Description</div>
                    <div class="info-value"><?= htmlspecialchars($booking['problem_description']) ?></div>
                </div>
            </div>
        </div>

        <!-- Provider Note -->
        <?php if (!empty($booking['provider_note'])): ?>
        <div class="section">
            <h2 class="section-title">📝 Provider Note</h2>
            <div class="note-box">
                <?= nl2br(htmlspecialchars($booking['provider_note'])) ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Provider Details -->
        <?php if (!empty($booking['provider_id'])): ?>
        <div class="section">
            <h2 class="section-title">👷 Provider Details</h2>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Provider Name</div>
                    <div class="info-value"><?= htmlspecialchars($booking['provider_name']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Profession</div>
                    <div class="info-value"><?= htmlspecialchars($booking['profession']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Phone</div>
                    <div class="info-value">+91 <?= htmlspecialchars($booking['phone']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Area</div>
                    <div class="info-value"><?= htmlspecialchars($booking['pincode']) ?></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Bill Details -->
        <?php if (
            !empty($booking['provider_id']) &&
            in_array($booking['booking_status'], ['accepted', 'completed'])
        ): ?>
        <div class="section">
            <h2 class="section-title">💰 Billing Details</h2>
            
            <div class="bill-row">
                <div class="bill-label">Service Charges</div>
                <div class="bill-value">₹<?= number_format($booking['service_charge'], 2) ?></div>
            </div>
            
            <div class="bill-row">
                <div class="bill-label">Material Charges</div>
                <div class="bill-value">₹<?= number_format($booking['material_charge'], 2) ?></div>
            </div>
            
            <div class="bill-row">
                <div class="bill-label">Subtotal</div>
                <div class="bill-value">₹<?= number_format($booking['service_charge'] + $booking['material_charge'], 2) ?></div>
            </div>
            
            <div class="bill-row">
                <div class="bill-label">GST (18%)</div>
                <div class="bill-value">₹<?= number_format($booking['gst_amount'], 2) ?></div>
            </div>
            
            <div class="bill-row bill-total">
                <div class="bill-label" style="font-size: 1.2rem;">Total Amount</div>
                <div class="bill-value">₹<?= number_format($booking['total_amount'], 2) ?></div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Payment Details -->
        <div class="section">
            <h2 class="section-title">💳 Payment Information</h2>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Payment Method</div>
                    <div class="info-value">
                        <span class="payment-badge">
                            <?= $booking['payment_method'] === 'cod' ? '💵' : '💳' ?>
                            <?= strtoupper($booking['payment_method']) ?>
                        </span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Payment Status</div>
                    <div class="info-value">
                        <span class="status-badge status-<?= statusColor($booking['payment_status']) ?>">
                            <?= ucfirst($booking['payment_status']) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

</body>
</html>