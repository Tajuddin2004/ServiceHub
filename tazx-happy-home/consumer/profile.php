<?php
session_start();
require_once dirname(__DIR__) . "/config/db.php";

/* 🔐 Protect consumer access */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'consumer') {
    header("Location: ../auth/login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$success = $error = "";

/* 🔎 Fetch user data */
$stmt = $conn->prepare("
    SELECT user_id, name, email, phone, password, created_at
    FROM users
    WHERE user_id = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

/* 💾 Update profile */
if (isset($_POST['update_profile'])) {

    $name  = trim($_POST['name']);
    $phone = trim($_POST['phone']);

    if ($name === '') {
        $error = "Name is required.";
    } else {
        $update = $conn->prepare(
            "UPDATE users SET name = ?, phone = ? WHERE user_id = ?"
        );
        $update->bind_param("ssi", $name, $phone, $userId);
        $update->execute();

        $_SESSION['name'] = $name;
        $success = "Profile updated successfully.";
    }
}

/* 🔑 Change password */
if (isset($_POST['change_password'])) {

    $oldPassword     = $_POST['old_password'];
    $newPassword     = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if ($oldPassword === '' || $newPassword === '' || $confirmPassword === '') {
        $error = "All password fields are required.";
    }
    elseif (!password_verify($oldPassword, $user['password'])) {
        $error = "Old password is incorrect.";
    }
    elseif ($newPassword !== $confirmPassword) {
        $error = "New passwords do not match.";
    }
    elseif (strlen($newPassword) < 6) {
        $error = "Password must be at least 6 characters.";
    }
    else {
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);

        $p = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $p->bind_param("si", $hashed, $userId);
        $p->execute();

        $success = "Password changed successfully.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Profile</title>
    <style>
        body { font-family: Arial; background:#f4f6f8; }
        .box {
            max-width:520px;
            margin:40px auto;
            background:#fff;
            padding:20px;
            border-radius:6px;
        }
        input {
            width:100%;
            padding:10px;
            margin:8px 0;
        }
        input[readonly] {
            background:#f1f1f1;
            cursor:not-allowed;
        }
        button {
            padding:10px;
            background:#2563eb;
            color:#fff;
            border:none;
            cursor:pointer;
            width:100%;
        }
        .success { color:green; }
        .error { color:red; }
        hr { margin:20px 0; }
    </style>
</head>
<body>

<div class="box">
    <h2>My Profile</h2>

    <?php if ($success): ?><p class="success"><?= htmlspecialchars($success) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>

    <!-- 👤 PROFILE INFO -->
    <form method="POST">
        <input type="hidden" name="update_profile">

        <label>User ID</label>
        <input type="text" value="<?= $user['user_id'] ?>" readonly>

        <label>Account Created On</label>
        <input type="text" value="<?= date('d M Y, h:i A', strtotime($user['created_at'])) ?>" readonly>

        <label>Name</label>
        <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>

        <label>Email (cannot change)</label>
        <input type="email" value="<?= htmlspecialchars($user['email']) ?>" readonly>

        <label>Phone</label>
        <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>">

        <button type="submit">Update Profile</button>
    </form>

    <hr>

    <!-- 🔑 CHANGE PASSWORD -->
    <h3>Change Password</h3>
    <form method="POST">
        <input type="hidden" name="change_password">

        <label>Old Password</label>
        <input type="password" name="old_password" required>

        <label>New Password</label>
        <input type="password" name="new_password" required>

        <label>Confirm New Password</label>
        <input type="password" name="confirm_password" required>

        <button type="submit">Change Password</button>
    </form>

    <br>
    <a href="account.php">⬅ Back to My Account</a>
</div>

</body>
</html>
