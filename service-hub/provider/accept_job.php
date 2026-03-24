<?php
session_start();
require_once dirname(__DIR__) . "/config/db.php";

/* 🔐 Protect provider */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'provider') {
    header("Location: ../auth/login.php");
    exit;
}

$userId = $_SESSION['user_id'];

/* 🔹 Get provider_id */
$p = $conn->prepare("SELECT provider_id FROM service_providers WHERE user_id = ?");
$p->bind_param("i", $userId);
$p->execute();
$provider = $p->get_result()->fetch_assoc();

if (!$provider) {
    die("Provider not found");
}

$providerId = (int) $provider['provider_id'];

/* 🔹 Validate booking ID */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid booking");
}
$bookingId = (int) $_GET['id'];

/* 🔹 Fetch booking (ONLY pending & unassigned) */
$b = $conn->prepare("
    SELECT 
        b.booking_id,
        b.booking_date,
        b.address,
        b.pincode,
        b.problem_description,
        s.service_name
    FROM bookings b
    JOIN services s ON s.service_id = b.service_id
    WHERE b.booking_id = ?
      AND b.status = 'pending'
      AND b.provider_id IS NULL
");
$b->bind_param("i", $bookingId);
$b->execute();
$booking = $b->get_result()->fetch_assoc();

if (!$booking) {
    die("Booking not available or already handled");
}

/* =====================================================
   ❌ CANCEL JOB (DIRECT CANCEL)
===================================================== */
if (isset($_POST['cancel_job'])) {

    $cancel = $conn->prepare("
        UPDATE bookings
        SET 
            status = 'cancelled',
            payment_status = 'cancelled',
            closed_at = NOW()
        WHERE booking_id = ?
          AND provider_id IS NULL
          AND status = 'pending'
    ");
    $cancel->bind_param("i", $bookingId);
    $cancel->execute();

    header("Location: home.php?success=job_cancelled");
    exit;
}

/* =====================================================
   ✅ ACCEPT JOB
===================================================== */
if (isset($_POST['accept_job'])) {

    $providerCharge = (float) ($_POST['provider_charge'] ?? 0);
    $materialCharge = (float) ($_POST['material_charge'] ?? 0);

    if ($providerCharge < 0 || $materialCharge < 0) {
        $error = "Charges cannot be negative";
    } elseif ($providerCharge == 0 && $materialCharge == 0) {
        $error = "Please enter charges to accept job";
    } else {

        $subtotal = $providerCharge + $materialCharge;
        $gst = round($subtotal * 0.18, 2);
        $total = round($subtotal + $gst, 2);

        $update = $conn->prepare("
            UPDATE bookings
            SET 
                provider_id = ?,
                service_charge = ?,
                material_charge = ?,
                gst_amount = ?,
                total_amount = ?,
                status = 'accepted'
            WHERE booking_id = ?
              AND provider_id IS NULL
              AND status = 'pending'
        ");

        $update->bind_param(
            "iddddi",
            $providerId,
            $providerCharge,
            $materialCharge,
            $gst,
            $total,
            $bookingId
        );

        if ($update->execute()) {
            header("Location: home.php?success=job_accepted");
            exit;
        } else {
            $error = "Failed to accept job";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Accept Job</title>
<style>
body { background:#0a0a0f; color:#fff; font-family:Inter, Arial, sans-serif; }
.card { max-width:600px; margin:40px auto; background:#111; padding:25px; border-radius:12px; }
label { display:block; margin-top:15px; }
input { width:100%; padding:10px; border-radius:8px; border:none; }
.total { font-size:1.2rem; font-weight:700; margin-top:15px; }
.breakup { margin-top:10px; font-size:0.95rem; color:#ccc; }
button { margin-top:15px; padding:12px; width:100%; border:none; font-size:1rem; border-radius:10px; cursor:pointer; }
.btn-accept { background:#6366f1; color:#fff; }
.btn-cancel { background:#ef4444; color:#fff; }
.error { color:#ff6b6b; margin-bottom:10px; }
</style>
</head>

<body>
<div class="card">
    <h2>🛠️ <?= htmlspecialchars($booking['service_name']) ?></h2>

    <p><strong>Date:</strong> <?= htmlspecialchars($booking['booking_date']) ?></p>
    <p><strong>Address:</strong> <?= htmlspecialchars($booking['address']) ?> - <?= htmlspecialchars($booking['pincode']) ?></p>
    <p><strong>Problem:</strong> <?= htmlspecialchars($booking['problem_description']) ?></p>

    <?php if (!empty($error)): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Provider Charges (₹)</label>
        <input type="number" step="0.01" id="provider_charge" name="provider_charge">

        <label>Materials / Appliances Charges (₹)</label>
        <input type="number" step="0.01" id="material_charge" name="material_charge">

        <div class="breakup">
            Subtotal: ₹ <span id="subtotal">0.00</span><br>
            GST (18%): ₹ <span id="gst">0.00</span>
        </div>

        <div class="total">
            Total Amount: ₹ <span id="total">0.00</span>
        </div>

        <button type="submit" name="accept_job" class="btn-accept">
            Accept Job
        </button>

        <button type="submit" name="cancel_job" class="btn-cancel">
            Cancel Job
        </button>
    </form>
</div>

<script>
const providerInput = document.getElementById('provider_charge');
const materialInput = document.getElementById('material_charge');
const subtotalSpan = document.getElementById('subtotal');
const gstSpan = document.getElementById('gst');
const totalSpan = document.getElementById('total');

function calculate() {
    const provider = parseFloat(providerInput.value) || 0;
    const material = parseFloat(materialInput.value) || 0;
    const subtotal = provider + material;
    const gst = subtotal * 0.18;
    const total = subtotal + gst;

    subtotalSpan.textContent = subtotal.toFixed(2);
    gstSpan.textContent = gst.toFixed(2);
    totalSpan.textContent = total.toFixed(2);
}

providerInput.addEventListener('input', calculate);
materialInput.addEventListener('input', calculate);
</script>

</body>
</html>
