<?php
session_start();
require_once dirname(__DIR__) . "/config/db.php";

/* 🔐 Protect admin */
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

/* Validate input */
if (!isset($_GET['id'], $_GET['action'])) {
    header("Location: providers.php?error=invalid_request");
    exit;
}

$userId = (int) $_GET['id'];
$action = trim($_GET['action']);

if ($userId <= 0) {
    header("Location: providers.php?error=invalid_request");
    exit;
}

/* Approve / Reject - Use user_id since that's the foreign key */
if ($action === 'approve') {

    $stmt = $conn->prepare(
        "UPDATE service_providers SET is_approved = 1 WHERE user_id = ?"
    );
    $stmt->bind_param("i", $userId);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        // Also update the user status to active
        $userStmt = $conn->prepare("UPDATE users SET status = 1 WHERE user_id = ?");
        $userStmt->bind_param("i", $userId);
        $userStmt->execute();
        
        header("Location: providers.php?success=approved");
    } else {
        header("Location: providers.php?error=approval_failed");
    }

} elseif ($action === 'reject') {

    $stmt = $conn->prepare(
        "UPDATE service_providers SET is_approved = -1 WHERE user_id = ?"
    );
    $stmt->bind_param("i", $userId);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        // Keep user inactive
        $userStmt = $conn->prepare("UPDATE users SET status = 0 WHERE user_id = ?");
        $userStmt->bind_param("i", $userId);
        $userStmt->execute();
        
        header("Location: providers.php?success=rejected");
    } else {
        header("Location: providers.php?error=rejection_failed");
    }

} else {
    header("Location: providers.php?error=invalid_request");
}

exit;
?>