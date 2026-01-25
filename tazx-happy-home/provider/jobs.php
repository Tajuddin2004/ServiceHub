<?php
session_start();
require_once dirname(__DIR__) . "/config/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'provider') {
    exit;
}

$userId = $_SESSION['user_id'];

$sql = "
SELECT booking_id, status
FROM bookings
WHERE provider_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$accepted = 0;
$pending = 0;
$completed = 0;

while ($row = $result->fetch_assoc()) {
    if ($row['status'] === 'accepted') $accepted++;
    elseif ($row['status'] === 'pending') $pending++;
    elseif ($row['status'] === 'completed') $completed++;
}

echo json_encode([
    'accepted' => $accepted,
    'pending' => $pending,
    'completed' => $completed
]);
