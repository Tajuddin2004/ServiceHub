<?php
session_start();
require_once dirname(__DIR__) . "/config/db.php";

/* 🔐 Protect provider access */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'provider') {
    header("Location: ../auth/login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$message = "";

/* 🔎 Fetch provider profile */
$sql = "
SELECT 
    u.user_id,
    sp.provider_id,

    u.name, u.email,
    sp.age, sp.profession, sp.about_work,
    sp.experience, sp.area, sp.pincode, sp.phone,
    sp.is_approved
FROM users u
JOIN service_providers sp ON sp.user_id = u.user_id
WHERE u.user_id = ?
";


$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();

if (!$profile) {
    echo "Profile not found.";
    exit;
}

/* 💾 Update profile */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name       = trim($_POST['name']);
    $age        = (int)$_POST['age'];
    $profession = trim($_POST['profession']);
    $about      = trim($_POST['about_work']);
    $experience = (int)$_POST['experience'];
    $pincode    = trim($_POST['pincode']);
    $area = trim($_POST['area']);
    $phone      = trim($_POST['phone']);

    $conn->begin_transaction();

    try {
        // Update users table
        $u = $conn->prepare("UPDATE users SET name = ? WHERE user_id = ?");
        $u->bind_param("si", $name, $userId);
        $u->execute();

        // Update provider table
        $p = $conn->prepare("
            UPDATE service_providers 
            SET age=?, profession=?, about_work=?, experience=?, area=?, pincode=?, phone=?
            WHERE user_id=?

        ");
        $p->bind_param(
            "ississsi",
            $age, $profession, $about, $experience, $area, $pincode, $phone, $userId
        );

        $p->execute();

        $conn->commit();
        $_SESSION['name'] = $name;
        $message = "Profile updated successfully ✅";

    } catch (Exception $e) {
        $conn->rollback();
        $message = "Something went wrong ❌";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Provider Profile | Service-Hub</title>
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

/* Animated gradient background */
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

/* Navbar */
.navbar {
    position: fixed;
    top: 0;
    width: 100%;
    padding: 1.5rem 5%;
    background: rgba(10, 10, 15, 0.7);
    backdrop-filter: blur(20px) saturate(180%);
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    display: flex;
    justify-content: space-between;
    align-items: center;
    z-index: 1000;
    transition: all 0.3s ease;
}

.navbar.scrolled {
    background: rgba(10, 10, 15, 0.95);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
}

.logo {
    font-weight: 700;
    font-size: 1.3rem;
    background: linear-gradient(135deg, #22d3ee, #a855f7);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.nav-links {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.nav-btn {
    padding: 0.6rem 1.2rem;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 500;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.btn-dashboard {
    background: rgba(99, 102, 241, 0.15);
    color: #a5b4fc;
    border: 1px solid rgba(99, 102, 241, 0.3);
}

.btn-dashboard:hover {
    background: rgba(99, 102, 241, 0.25);
    transform: translateY(-2px);
}

.btn-logout {
    background: rgba(239, 68, 68, 0.15);
    color: #fca5a5;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.btn-logout:hover {
    background: rgba(239, 68, 68, 0.25);
    transform: translateY(-2px);
}

/* Container */
.container {
    padding: 8rem 5% 4rem;
    max-width: 900px;
    margin: auto;
}

/* Header */
.page-header {
    margin-bottom: 2rem;
    animation: fadeUp 0.6s ease;
}

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

.page-header h1 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.page-header p {
    color: rgba(255, 255, 255, 0.6);
    font-size: 1rem;
}

/* Status Badge */
.status-container {
    margin-bottom: 2rem;
    animation: fadeUp 0.6s ease 0.1s backwards;
}

.status-badge {
    display: inline-block;
    padding: 0.6rem 1.2rem;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 600;
}

.status-approved {
    background: rgba(34, 197, 94, 0.2);
    color: #86efac;
    border: 1px solid rgba(34, 197, 94, 0.3);
}

.status-pending {
    background: rgba(251, 146, 60, 0.2);
    color: #fdba74;
    border: 1px solid rgba(251, 146, 60, 0.3);
}

.status-rejected {
    background: rgba(239, 68, 68, 0.2);
    color: #fca5a5;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

/* Success Message */
.message {
    background: rgba(34, 197, 94, 0.15);
    border: 1px solid rgba(34, 197, 94, 0.3);
    color: #86efac;
    padding: 1rem 1.5rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    animation: fadeUp 0.6s ease 0.2s backwards;
}

/* Form Container */
.form-container {
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 16px;
    padding: 2rem;
    animation: fadeUp 0.6s ease 0.3s backwards;
}

/* Form Grid */
.form-grid {
    display: grid;
    gap: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

label {
    font-weight: 600;
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.9);
}

input, textarea, select {
    width: 100%;
    padding: 0.9rem 1rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    color: #ffffff;
    font-size: 0.95rem;
    font-family: 'Inter', sans-serif;
    transition: all 0.3s ease;
}

input:focus, textarea:focus, select:focus {
    outline: none;
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(99, 102, 241, 0.5);
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
}

input:read-only {
    background: rgba(255, 255, 255, 0.02);
    cursor: not-allowed;
    color: rgba(255, 255, 255, 0.5);
}

textarea {
    resize: vertical;
    min-height: 120px;
}

select {
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='rgba(255,255,255,0.6)' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.7rem center;
    background-size: 1.2rem;
    padding-right: 2.5rem;
}

select option {
    background: #1a1a2e;
    color: #ffffff;
    padding: 0.5rem;
}

input::placeholder, textarea::placeholder {
    color: rgba(255, 255, 255, 0.4);
}

/* Submit Button */
.submit-btn {
    margin-top: 1rem;
    padding: 1rem 2rem;
    background: linear-gradient(135deg, #6366f1, #a855f7);
    color: #ffffff;
    border: none;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    width: 100%;
}

.submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);
}

/* Responsive */
@media (max-width: 768px) {
    .navbar {
        padding: 1.2rem 4%;
    }
    
    .container {
        padding: 6rem 4% 3rem;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .form-container {
        padding: 1.5rem;
    }
}
</style>
</head>

<body>
<div class="bg-gradient"></div>

<!-- Navbar -->
<nav class="navbar" id="navbar">
    <div class="logo">
        Service-Hub
    </div>
    <div class="nav-links">
        <a href="home.php" class="nav-btn btn-dashboard">← Dashboard</a>
        <a href="../auth/logout.php" class="nav-btn btn-logout">Logout</a>
    </div>
</nav>

<!-- Main Content -->
<div class="container">
    <!-- Page Header -->
    <div class="page-header">
        <h1>My Profile</h1>
        <p>Manage your provider information and preferences</p>
    </div>

    <!-- Status Badge -->
    <div class="status-container">
        <strong>Account Status: </strong>
        <?php
        if ($profile['is_approved'] == 1) {
            echo '<span class="status-badge status-approved">✅ Approved</span>';
        } elseif ($profile['is_approved'] == -1) {
            echo '<span class="status-badge status-rejected">❌ Rejected</span>';
        } else {
            echo '<span class="status-badge status-pending">⏳ Pending Approval</span>';
        }
        ?>
    </div>

    <!-- Success Message -->
    <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- Form -->
    <div class="form-container">
        <form method="POST">
            <div class="form-grid">

                <!-- User ID & Provider ID -->
                <!-- User ID & Provider ID -->
                <div class="form-row">
                    <div class="form-group">
                        <label>User ID</label>
                        <input type="text" value="<?= htmlspecialchars($profile['user_id']) ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label>Provider ID</label>
                        <input type="text" value="<?= htmlspecialchars($profile['provider_id']) ?>" readonly>
                    </div>
                </div>


                <!-- Name & Email -->
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" required
                               value="<?= htmlspecialchars($profile['name']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Email (readonly)</label>
                        <input type="email" value="<?= htmlspecialchars($profile['email']) ?>" readonly>
                    </div>
                </div>

                <!-- Age & Profession -->
                <div class="form-row">
                    <div class="form-group">
                        <label>Age</label>
                        <input type="number" name="age" min="18" required
                               value="<?= htmlspecialchars($profile['age']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Profession</label>
                        <select name="profession" required>
                            <option value="">-- Select Profession --</option>

                            <?php
                            $professions = [
                                'Electrician',
                                'Plumber',
                                'Carpenter',
                                'Painter',
                                'AC Technician',
                                'Cleaning',
                                'Appliance Repair'
                            ];

                            foreach ($professions as $prof) {
                                $selected = ($profile['profession'] === $prof) ? 'selected' : '';
                                echo "<option value=\"$prof\" $selected>$prof</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <!-- Experience & Phone -->
                <div class="form-row">
                    <div class="form-group">
                        <label>Experience (years)</label>
                        <input type="number" name="experience" min="0"
                               value="<?= htmlspecialchars($profile['experience']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone"
                               value="<?= htmlspecialchars($profile['phone']) ?>">
                    </div>
                </div>

                <!-- Area & Pincode -->
                <div class="form-row">
                    <div class="form-group">
                        <label>Current Area / Address</label>
                        <input type="text" name="area" placeholder="e.g. Sector 12, Noida"
                               value="<?= htmlspecialchars($profile['area'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Area Pincode</label>
                        <input type="text" name="pincode"
                               value="<?= htmlspecialchars($profile['pincode']) ?>">
                    </div>
                </div>

                <!-- About Work -->
                <div class="form-group">
                    <label>About Your Work</label>
                    <textarea name="about_work" rows="4" placeholder="Describe your expertise and services..."><?= htmlspecialchars($profile['about_work']) ?></textarea>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="submit-btn">💾 Update Profile</button>
            </div>
        </form>
    </div>
</div>

<script>
// Navbar scroll effect
window.addEventListener("scroll", () => {
    const navbar = document.getElementById("navbar");
    if (window.scrollY > 50) {
        navbar.classList.add("scrolled");
    } else {
        navbar.classList.remove("scrolled");
    }
});
</script>

</body>
</html>