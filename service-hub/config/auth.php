<?php
session_start();

function checkAuth($role = null) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /service-hub/auth/login.php");
        exit();
    }

    if ($role && $_SESSION['role'] !== $role) {
        die("Unauthorized access");
    }
}
?>
