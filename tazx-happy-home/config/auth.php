<?php
session_start();

function checkAuth($role = null) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /tazx-happy-home/auth/login.php");
        exit();
    }

    if ($role && $_SESSION['role'] !== $role) {
        die("Unauthorized access");
    }
}
?>
