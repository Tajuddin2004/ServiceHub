<?php
session_start();

// Unset all session variables
session_unset();

// Destroy the session
session_destroy();

// Redirect to login page with logout message
header("Location: login.php?logout=1");
exit;
