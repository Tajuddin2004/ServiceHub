<?php
session_start();
require_once dirname(__DIR__) . "/config/db.php";

/* 🔐 Provider protection */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'provider') {
    header("Location: ../auth/login.php");
    exit;
}

$userId = $_SESSION['user_id'];

/* 🔹 Get provider_id */
$stmt = $conn->prepare("SELECT provider_id FROM service_providers WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$provider = $stmt->get_result()->fetch_assoc();

if (!$provider) {
    die("Provider not found");
}

$providerId = $provider['provider_id'];

/* 📅 Month filter */
$selectedMonth = $_GET['month'] ?? date('Y-m');
$startDate = $selectedMonth . "-01";
$endDate = date("Y-m-t", strtotime($startDate));

/* ♾️ Lifetime earnings */
$lifetimeSql = "
    SELECT
        COALESCE(SUM(b.service_charge), 0) AS service_earning,
        COALESCE(SUM(b.material_charge), 0) AS material_earning
    FROM bookings b
    JOIN payments p ON p.booking_id = b.booking_id
    WHERE
        b.provider_id = ?
        AND p.status = 'paid'
";
$stmt = $conn->prepare($lifetimeSql);
$stmt->bind_param("i", $providerId);
$stmt->execute();
$lifetime = $stmt->get_result()->fetch_assoc();
$lifetimeTotal = $lifetime['service_earning'] + $lifetime['material_earning'];

/* 📆 Monthly earnings */
$monthlySql = "
    SELECT
        COALESCE(SUM(b.service_charge), 0) AS service_earning,
        COALESCE(SUM(b.material_charge), 0) AS material_earning
    FROM bookings b
    JOIN payments p ON p.booking_id = b.booking_id
    WHERE
        b.provider_id = ?
        AND p.status = 'paid'
        AND DATE(p.created_at) BETWEEN ? AND ?
";
$stmt = $conn->prepare($monthlySql);
$stmt->bind_param("iss", $providerId, $startDate, $endDate);
$stmt->execute();
$monthly = $stmt->get_result()->fetch_assoc();
$monthlyTotal = $monthly['service_earning'] + $monthly['material_earning'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>My Earnings | Service-Hub</title>
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

/* Container */
.container {
    padding: 3rem 5%;
    max-width: 1200px;
    margin: auto;
    min-height: 100vh;
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

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    font-size: 0.9rem;
    margin-bottom: 1rem;
    transition: color 0.3s ease;
}

.back-link:hover {
    color: #22d3ee;
}

.page-header h1 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    background: linear-gradient(135deg, #ffffff 0%, #a0a0a0 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.page-header p {
    color: rgba(255, 255, 255, 0.6);
}

/* Month Selector */
.month-selector {
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 16px;
    padding: 1.5rem 2rem;
    margin-bottom: 2rem;
    animation: fadeUp 0.6s ease 0.1s backwards;
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.month-selector label {
    color: rgba(255, 255, 255, 0.8);
    font-weight: 500;
}

.month-input {
    padding: 0.8rem 1.2rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    color: #ffffff;
    font-size: 0.95rem;
    font-family: inherit;
    transition: all 0.3s ease;
}

.month-input:focus {
    outline: none;
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(99, 102, 241, 0.5);
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
}

.btn-view {
    padding: 0.8rem 1.5rem;
    background: linear-gradient(135deg, #6366f1, #a855f7);
    border: none;
    border-radius: 10px;
    color: #ffffff;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-view:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);
}

/* Earnings Card */
.earnings-card {
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(30px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    padding: 2.5rem;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
    animation: fadeUp 0.6s ease 0.2s backwards;
}

.earnings-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #6366f1, #a855f7, #22d3ee);
    border-radius: 20px 20px 0 0;
}

.card-header {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    margin-bottom: 2rem;
}

.card-header h2 {
    font-size: 1.5rem;
    font-weight: 700;
}

.card-icon {
    font-size: 2rem;
}

/* Earnings Grid */
.earnings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1.5rem;
}

.earning-box {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 16px;
    padding: 1.8rem;
    text-align: center;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.earning-box::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #6366f1, #a855f7);
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.earning-box:hover::before {
    transform: scaleX(1);
}

.earning-box:hover {
    transform: translateY(-5px);
    background: rgba(255, 255, 255, 0.08);
}

.earning-label {
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.7);
    margin-bottom: 0.8rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.earning-amount {
    font-size: 1.8rem;
    font-weight: 700;
    background: linear-gradient(135deg, #22d3ee, #a855f7);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.earning-box.total {
    background: linear-gradient(135deg, rgba(34, 197, 94, 0.15), rgba(34, 197, 94, 0.05));
    border: 1px solid rgba(34, 197, 94, 0.3);
}

.earning-box.total::before {
    background: linear-gradient(90deg, #22c55e, #86efac);
}

.earning-box.total .earning-label {
    color: #86efac;
}

.earning-box.total .earning-amount {
    font-size: 2.2rem;
    background: linear-gradient(135deg, #86efac, #22c55e);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Lifetime Card Special Styling */
.lifetime-card {
    animation: fadeUp 0.6s ease 0.3s backwards;
}

.lifetime-card::before {
    background: linear-gradient(90deg, #f59e0b, #fbbf24, #f59e0b);
}

/* Responsive */
@media (max-width: 768px) {
    .container {
        padding: 2rem 4%;
    }
    
    .earnings-card {
        padding: 2rem 1.5rem;
    }
    
    .month-selector {
        padding: 1.2rem 1.5rem;
    }
    
    .earnings-grid {
        grid-template-columns: 1fr;
    }
}

/* Number Counter Animation */
@keyframes countUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.earning-amount {
    animation: countUp 0.6s ease;
}
</style>
</head>

<body>
<div class="bg-gradient"></div>

<div class="container">
    <!-- Header -->
    <div class="page-header">
        <a href="home.php" class="back-link">← Back to Dashboard</a>
        <h1>💰 My Earnings</h1>
        <p>Track your monthly and lifetime earnings</p>
    </div>

    <!-- Month Selector -->
    <form method="GET" class="month-selector">
        <label>📅 Select Month:</label>
        <input type="month" name="month" value="<?= htmlspecialchars($selectedMonth) ?>" class="month-input">
 
    </form>

    <!-- Monthly Earnings Card -->
    <div class="earnings-card">
        <div class="card-header">
            <span class="card-icon">📆</span>
            <h2>Earnings for <?= date("F Y", strtotime($selectedMonth)) ?></h2>
        </div>
        
        <div class="earnings-grid">
            <div class="earning-box">
                <div class="earning-label">
                    🔧 Service Earnings
                </div>
                <div class="earning-amount">
                    ₹<?= number_format($monthly['service_earning'], 2) ?>
                </div>
            </div>
            
            <div class="earning-box">
                <div class="earning-label">
                    🛠️ Material Sold
                </div>
                <div class="earning-amount">
                    ₹<?= number_format($monthly['material_earning'], 2) ?>
                </div>
            </div>
            
            <div class="earning-box total">
                <div class="earning-label">
                    💵 Monthly Total
                </div>
                <div class="earning-amount">
                    ₹<?= number_format($monthlyTotal, 2) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Lifetime Earnings Card -->
    <div class="earnings-card lifetime-card">
        <div class="card-header">
            <span class="card-icon">♾️</span>
            <h2>Lifetime Earnings</h2>
        </div>
        
        <div class="earnings-grid">
            <div class="earning-box">
                <div class="earning-label">
                    🔧 Service Earnings
                </div>
                <div class="earning-amount">
                    ₹<?= number_format($lifetime['service_earning'], 2) ?>
                </div>
            </div>
            
            <div class="earning-box">
                <div class="earning-label">
                    🛠️ Material Sold
                </div>
                <div class="earning-amount">
                    ₹<?= number_format($lifetime['material_earning'], 2) ?>
                </div>
            </div>
            
            <div class="earning-box total">
                <div class="earning-label">
                    💰 Total Earnings
                </div>
                <div class="earning-amount">
                    ₹<?= number_format($lifetimeTotal, 2) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-submit form when month changes
const monthInput = document.querySelector('.month-input');
monthInput.addEventListener('change', function() {
    this.form.submit();
});
</script>

</body>
</html>