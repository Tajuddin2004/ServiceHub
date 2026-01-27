<?php
session_start();
require_once "../config/db.php";

$bookingId = (int)$_GET['id'];

$sql = "
SELECT 
    b.booking_date, b.address, b.problem_description, b.status,
    s.service_name,
    u.name AS provider_name,
    sp.phone AS provider_phone
FROM bookings b
JOIN services s ON s.service_id = b.service_id
LEFT JOIN service_providers sp ON sp.provider_id = b.provider_id
LEFT JOIN users u ON u.user_id = sp.user_id
WHERE b.booking_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
?>

<h2>Booking Details</h2>

<p><strong>Service:</strong> <?= $booking['service_name'] ?></p>
<p><strong>Date:</strong> <?= $booking['booking_date'] ?></p>
<p><strong>Address:</strong> <?= $booking['address'] ?></p>
<p><strong>Problem:</strong> <?= $booking['problem_description'] ?></p>
<p><strong>Status:</strong> <?= ucfirst($booking['status']) ?></p>

<hr>

<h3>Assigned Provider</h3>

<?php if ($booking['provider_name']): ?>
    <p><strong>Name:</strong> <?= $booking['provider_name'] ?></p>
    <p><strong>Phone:</strong> <?= $booking['provider_phone'] ?></p>
<?php else: ?>
    <p>Provider not assigned yet.</p>
<?php endif; ?>

<a href="account.php">← Back to My Account</a>
