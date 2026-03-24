<?php
session_start();
require_once dirname(__DIR__) . "/config/db.php";

/* 🔐 Protect consumer */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'consumer') {
    header("Location: ../auth/login.php");
    exit;
}

$consumerId = $_SESSION['user_id'];

/* ===============================
   DEFAULT VARIABLES (VERY IMPORTANT)
================================ */
$message            = "";
$messageType        = "";
$pincode            = "";
$selectedPincode    = "";
$availableServices  = [];
$showBookingForm    = false;
$selectedServiceId  = null;
$serviceName        = "Service";

/* ===============================
   FUNCTION: FETCH SERVICES BY PINCODE
================================ */
function getServicesByPincode($conn, $pincode) {
    $stmt = $conn->prepare("
        SELECT 
            s.service_id,
            s.service_name,
            COUNT(sp.provider_id) AS provider_count
        FROM services s
        JOIN service_providers sp
            ON sp.profession = s.service_name
        WHERE sp.pincode = ?
          AND sp.is_approved = 1
          AND s.status = 1
        GROUP BY s.service_id
        ORDER BY s.service_name
    ");
    $stmt->bind_param("s", $pincode);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/* ===============================
   STEP 1: CHECK AVAILABILITY
================================ */
if (isset($_POST['check_availability'])) {

    $pincode = trim($_POST['pincode']);
    $selectedPincode = $pincode;

    if (!preg_match('/^[0-9]{6}$/', $pincode)) {
        $message = "Invalid pincode format.";
        $messageType = "error";
    } else {

        $availableServices = getServicesByPincode($conn, $pincode);

        if (empty($availableServices)) {
            $message = "No services available in your area.";
            $messageType = "error";
        } else {
            $message = "Services available in your area. Click on a service to book.";
            $messageType = "success";
            $showBookingForm = false; // Don't show form yet
        }
    }
}

/* ===============================
   STEP 2: SELECT SERVICE (NEW)
================================ */
if (isset($_POST['select_service'])) {
    $pincode = trim($_POST['pincode']);
    $selectedPincode = $pincode;
    $selectedServiceId = (int) $_POST['service_id'];
    
    $availableServices = getServicesByPincode($conn, $pincode);
    
    if (!empty($availableServices)) {
        $showBookingForm = true;
    }
}

/* ===============================
   STEP 3: CONFIRM BOOKING
================================ */
if (isset($_POST['submit_booking'])) {

    $pincode = trim($_POST['pincode']);
    $selectedPincode = $pincode;
    $selectedServiceId = (int) $_POST['service_id'];

    if (!preg_match('/^[0-9]{6}$/', $pincode)) {
        $message = "Invalid pincode.";
        $messageType = "error";
        $showBookingForm = true;
    } else {

        $availableServices = getServicesByPincode($conn, $pincode);
        $showBookingForm = true;

        if (empty($availableServices)) {
            $message = "No services available in your area.";
            $messageType = "error";
        } else {

            $serviceIds = array_column($availableServices, 'service_id');

            if (!in_array($selectedServiceId, $serviceIds)) {
                $message = "Selected service is not available in your area.";
                $messageType = "error";
            } else {

                $check = $conn->prepare("
                    SELECT provider_id
                    FROM service_providers
                    WHERE profession = (
                        SELECT service_name FROM services WHERE service_id = ?
                    )
                    AND pincode = ?
                    AND is_approved = 1
                    LIMIT 1
                ");
                $check->bind_param("is", $selectedServiceId, $pincode);
                $check->execute();

                if (!$check->get_result()->fetch_assoc()) {
                    $message = "No provider available for selected service.";
                    $messageType = "error";
                } else {

                    $contactPhone = '+91' . $_POST['contact_phone'];

                    $insert = $conn->prepare("
                        INSERT INTO bookings (
                            consumer_id,
                            service_id,
                            booking_date,
                            address,
                            pincode,
                            contact_phone,
                            problem_description,
                            payment_method,
                            status
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'cod', 'pending')
                    ");

                    $insert->bind_param(
                        "iisssss",
                        $consumerId,
                        $selectedServiceId,
                        $_POST['booking_date'],
                        $_POST['address'],
                        $pincode,
                        $contactPhone,                 // ✅ FIXED
                        $_POST['problem_description']
                    );


                    if ($insert->execute()) {
                        $message = "Booking successful! Provider will contact you soon.";
                        $messageType = "success";
                        $showBookingForm = false;
                        $availableServices = [];
                        $selectedPincode = "";
                    } else {
                        $message = "Booking failed. Please try again.";
                        $messageType = "error";
                    }
                }
            }
        }
    }
}

/* ===============================
   SELECTED SERVICE NAME (UI ONLY)
================================ */
if ($selectedServiceId) {
    $svc = $conn->prepare("SELECT service_name FROM services WHERE service_id = ?");
    $svc->bind_param("i", $selectedServiceId);
    $svc->execute();
    if ($row = $svc->get_result()->fetch_assoc()) {
        $serviceName = $row['service_name'];
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Book <?= htmlspecialchars($serviceName) ?> | Service-Hub</title>
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

.container {
    padding: 3rem 5%;
    max-width: 900px;
    margin: auto;
    min-height: 100vh;
}

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

.service-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    background: rgba(99, 102, 241, 0.2);
    border: 1px solid rgba(99, 102, 241, 0.3);
    border-radius: 20px;
    color: #a5b4fc;
    font-size: 0.9rem;
    font-weight: 600;
}

.alert {
    padding: 1rem 1.2rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    gap: 0.8rem;
    animation: slideDown 0.4s ease;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.alert-success {
    background: rgba(34, 197, 94, 0.1);
    border: 1px solid rgba(34, 197, 94, 0.3);
    color: #86efac;
}

.alert-error {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #fca5a5;
}

.alert::before {
    font-size: 1.3rem;
}

.alert-success::before {
    content: '✓';
}

.alert-error::before {
    content: '⚠';
}

.form-card {
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(30px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    padding: 2.5rem;
    animation: fadeUp 0.6s ease 0.2s backwards;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9rem;
    font-weight: 500;
}

.form-input, .form-textarea {
    width: 100%;
    padding: 1rem 1.2rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    color: #ffffff;
    font-size: 1rem;
    transition: all 0.3s ease;
    font-family: inherit;
}

.form-input:focus, .form-textarea:focus {
    outline: none;
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(99, 102, 241, 0.5);
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
}

.form-input::placeholder, .form-textarea::placeholder {
    color: rgba(255, 255, 255, 0.4);
}

.form-textarea {
    resize: vertical;
    min-height: 100px;
}

.pincode-group {
    display: flex;
    gap: 0.8rem;
}

.pincode-group .form-input {
    flex: 1;
}

.btn-search {
    padding: 1rem 1.5rem;
    background: rgba(34, 211, 238, 0.15);
    border: 1px solid rgba(34, 211, 238, 0.3);
    border-radius: 12px;
    color: #22d3ee;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    white-space: nowrap;
}

.btn-search:hover {
    background: rgba(34, 211, 238, 0.25);
    transform: translateY(-2px);
}

.payment-options {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
}

.payment-option {
    position: relative;
}

.payment-option input[type="radio"] {
    position: absolute;
    opacity: 0;
}

.payment-label {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    padding: 1.2rem;
    background: rgba(255, 255, 255, 0.05);
    border: 2px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.payment-option input[type="radio"]:checked + .payment-label {
    background: rgba(99, 102, 241, 0.15);
    border-color: rgba(99, 102, 241, 0.5);
}

.payment-label:hover {
    background: rgba(255, 255, 255, 0.08);
}

.payment-icon {
    font-size: 1.8rem;
}

.payment-info h4 {
    font-size: 0.95rem;
    margin-bottom: 0.2rem;
}

.payment-info p {
    font-size: 0.8rem;
    color: rgba(255, 255, 255, 0.6);
}

.services-section {
    margin: 2rem 0;
    animation: fadeUp 0.6s ease;
}

.services-section h3 {
    font-size: 1.3rem;
    margin-bottom: 1rem;
    color: #86efac;
}

.services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1rem;
}

.service-card {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 1.5rem;
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
}

.service-card:hover {
    background: rgba(255, 255, 255, 0.08);
    transform: translateY(-4px);
    border-color: rgba(99, 102, 241, 0.5);
    box-shadow: 0 8px 30px rgba(99, 102, 241, 0.3);
}

.service-card.selected-service {
    background: rgba(99, 102, 241, 0.15);
    border-color: rgba(99, 102, 241, 0.7);
    box-shadow: 0 8px 30px rgba(99, 102, 241, 0.4);
}

.service-card h4 {
    font-size: 1.1rem;
    margin-bottom: 0.5rem;
    color: #ffffff;
}

.service-card p {
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.6);
}

.service-card .provider-count {
    display: inline-block;
    margin-top: 0.5rem;
    padding: 0.3rem 0.8rem;
    background: rgba(34, 211, 238, 0.2);
    border-radius: 20px;
    font-size: 0.85rem;
    color: #22d3ee;
}

.service-card .select-indicator {
    position: absolute;
    top: 1rem;
    right: 1rem;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: rgba(34, 197, 94, 0.2);
    border: 2px solid rgba(34, 197, 94, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.service-card.selected-service .select-indicator {
    opacity: 1;
}

.btn-primary {
    width: 100%;
    padding: 1.2rem;
    background: linear-gradient(135deg, #6366f1, #a855f7);
    border: none;
    border-radius: 12px;
    color: #ffffff;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.btn-primary::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s ease;
}

.btn-primary:hover::before {
    left: 100%;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 15px 40px rgba(99, 102, 241, 0.4);
}

.btn-primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

.step-info {
    text-align: center;
    margin-bottom: 2rem;
    padding: 1rem;
    background: rgba(99, 102, 241, 0.1);
    border-radius: 12px;
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9rem;
}

.pincode-display {
    display: inline-block;
    padding: 0.5rem 1rem;
    background: rgba(34, 211, 238, 0.2);
    border: 1px solid rgba(34, 211, 238, 0.3);
    border-radius: 8px;
    color: #22d3ee;
    font-weight: 600;
    margin-left: 0.5rem;
}

.clickable-hint {
    text-align: center;
    margin-top: 1rem;
    padding: 0.8rem;
    background: rgba(168, 85, 247, 0.1);
    border: 1px solid rgba(168, 85, 247, 0.3);
    border-radius: 8px;
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .container {
        padding: 2rem 4%;
    }
    
    .form-card {
        padding: 2rem 1.5rem;
    }
    
    .pincode-group {
        flex-direction: column;
    }
    
    .payment-options {
        grid-template-columns: 1fr;
    }
    
    .services-grid {
        grid-template-columns: 1fr;
    }
}
</style>
</head>

<body>
<div class="bg-gradient"></div>

<div class="container">
    <div class="page-header">
        <a href="home.php" class="back-link">← Back to Services</a>
        <h1>Book Your Service</h1>
        <?php if ($showBookingForm): ?>
            <span class="service-badge">🔧 <?= htmlspecialchars($serviceName) ?></span>
        <?php endif; ?>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Step 1: Check Availability -->
    <?php if (!$showBookingForm && empty($availableServices)): ?>
        <div class="form-card">
            <div class="step-info">
                📍 Step 1: Check service availability in your area
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Enter Your Pincode</label>
                    <div class="pincode-group">
                        <input type="text" name="pincode" class="form-input" placeholder="Enter 6-digit pincode" required pattern="[0-9]{6}" maxlength="6" value="<?= htmlspecialchars($selectedPincode) ?>">
                        <button type="submit" name="check_availability" class="btn-search">
                            🔍 Check Availability
                        </button>
                    </div>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- Step 2: Show Clickable Services -->
    <?php if (!$showBookingForm && !empty($availableServices)): ?>
        <div class="services-section">
            <h3>✅ Available Services in Pincode <span class="pincode-display"><?= htmlspecialchars($selectedPincode) ?></span></h3>
            <div class="clickable-hint">
                👆 Click on any service below to book it
            </div>
            <div class="services-grid" style="margin-top: 1.5rem;">
                <?php foreach ($availableServices as $svc): ?>
                    <form method="POST" style="margin: 0;">
                        <input type="hidden" name="pincode" value="<?= htmlspecialchars($selectedPincode) ?>">
                        <input type="hidden" name="service_id" value="<?= $svc['service_id'] ?>">
                        <button type="submit" name="select_service" style="all: unset; width: 100%; cursor: pointer;">
                            <div class="service-card">
                                <h4><?= htmlspecialchars($svc['service_name'] ?? 'Unknown Service') ?></h4>
                                <p>Click to book this service</p>
                                <span class="provider-count"><?= ($svc['provider_count'] ?? 0) ?> provider(s)</span>
                            </div>
                        </button>
                    </form>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Step 3: Booking Form -->
    <?php if ($showBookingForm): ?>
        <div class="form-card">
            <div class="step-info">
                📝 Complete your booking details for <strong><?= htmlspecialchars($serviceName) ?></strong>
            </div>

            <form method="POST">
                <input type="hidden" name="service_id" value="<?= $selectedServiceId ?>">
                
                <div class="form-group">
                    <label class="form-label">Pincode</label>
                    <input type="text" name="pincode" class="form-input" value="<?= htmlspecialchars($selectedPincode) ?>" required pattern="[0-9]{6}" maxlength="6">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Booking Date</label>
                    <input type="date" name="booking_date" class="form-input" required min="<?= date('Y-m-d') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-textarea" placeholder="Enter your full address" required></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Contact Phone Number</label>
                    <div style="display:flex; gap:8px;">
                        <input type="text" value="+91" readonly style="width:70px; text-align:center; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color:#fff; font-weight:600;">
                        <input type="text" name="contact_phone" class="form-input" placeholder="Enter 10-digit mobile number" required pattern="[0-9]{10}" maxlength="10">
                    </div>
                    <small style="color: rgba(255,255,255,0.6); font-size: 0.85rem; display: block; margin-top: 0.5rem;">
                        📞 Provider will contact you on this number
                    </small>
                </div>

                <div class="form-group">
                    <label class="form-label">Problem Description</label>
                    <textarea name="problem_description" class="form-textarea" placeholder="Describe the issue you're facing" required></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Payment Method</label>
                    <div class="payment-options">
                        <div class="payment-option">
                            <input type="radio" name="payment_method" id="cod" value="cod" checked>
                            <label for="cod" class="payment-label">
                                <div class="payment-icon">💵</div>
                                <div class="payment-info">
                                    <h4>Cash on Delivery</h4>
                                    <p>Pay after service</p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <button type="submit" name="submit_booking" class="btn-primary">
                    Confirm Booking
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dateInput = document.querySelector('input[type="date"]');
    if (dateInput) {
        const today = new Date().toISOString().split('T')[0];
        dateInput.setAttribute('min', today);
    }
});

const alerts = document.querySelectorAll('.alert');
alerts.forEach(alert => {
    setTimeout(() => {
        alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-20px)';
        setTimeout(() => alert.remove(), 500);
    }, 5000);
});

const pincodeInputs = document.querySelectorAll('input[name="pincode"]');
pincodeInputs.forEach(input => {
    input.addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
        if (this.value.length > 6) {
            this.value = this.value.slice(0, 6);
        }
    });
});

const phoneInput = document.querySelector('input[name="contact_phone"]');
if (phoneInput) {
    phoneInput.addEventListener('input', function () {
        this.value = this.value.replace(/[^0-9]/g, '');
        if (this.value.length > 10) {
            this.value = this.value.slice(0, 10);
        }
    });
}
</script>

</body>
</html>