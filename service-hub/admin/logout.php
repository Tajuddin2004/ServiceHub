<?php
session_start();

/*
|--------------------------------------------------------------------------
| ADMIN LOGOUT
|--------------------------------------------------------------------------
| Destroys all session data and redirects to admin login
|
| Safe even if session is already expired
|--------------------------------------------------------------------------
*/

// Unset all session variables
$_SESSION = [];

// Destroy the session
if (session_id() !== '') {
    session_destroy();
}

// Prevent browser back-button cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Redirect to admin login
header("Location: login.php?logged_out=1");
exit;
