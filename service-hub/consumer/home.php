<?php
session_start();

// ⏱️ Auto logout after 4 minutes
$timeout_duration = 240;

if (isset($_SESSION['LAST_ACTIVITY'])) {
    if (time() - $_SESSION['LAST_ACTIVITY'] > $timeout_duration) {
        session_unset();
        session_destroy();
        header("Location: ../auth/login.php?timeout=1");
        exit;
    }
}

$_SESSION['LAST_ACTIVITY'] = time();

// Protect page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'consumer') {
    header("Location: ../auth/login.php");
    exit;
}

$userName = $_SESSION['name'];
$loginSuccess = false;
if (!empty($_SESSION['login_success'])) {
    $loginSuccess = true;
    unset($_SESSION['login_success']);
}

require_once "../config/db.php";

$servicesStmt = $conn->prepare("
    SELECT service_id, service_name, icon, description
    FROM services
    WHERE status = 1
    ORDER BY service_name ASC
");
$servicesStmt->execute();
$services = $servicesStmt->get_result();

$servicesForJs = [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Service-Hub | Home</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
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

.logo-icon {
    width: 5rem;
    height: 5rem;
    object-fit: cover;
    border-radius: 7px;
    display: block;
    flex-shrink: 0;
    box-shadow: 0 0 10px rgba(34, 211, 238, 0.4);
    -webkit-background-clip: unset !important;
    background-clip: unset !important;
    -webkit-text-fill-color: unset !important;
}

.nav-links {
    display: flex;
    align-items: center;
    gap: 2rem;
}

.user-greeting {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.95rem;
}

.nav-links a {
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    font-weight: 500;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    position: relative;
}

.nav-links a:hover {
    color: #22d3ee;
}

.nav-links a::after {
    content: '';
    position: absolute;
    bottom: -4px;
    left: 0;
    width: 0;
    height: 2px;
    background: linear-gradient(90deg, #22d3ee, #a855f7);
    transition: width 0.3s ease;
}

.nav-links a:hover::after {
    width: 100%;
}

/* Container */
.container {
    padding: 8rem 5% 4rem;
    max-width: 1400px;
    margin: auto;
}

/* Success Alert */
.alert-success {
    background: rgba(34, 197, 94, 0.1);
    border: 1px solid rgba(34, 197, 94, 0.3);
    color: #86efac;
    padding: 1.2rem 1.5rem;
    border-radius: 16px;
    margin-bottom: 2rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.8rem;
    animation: slideDown 0.5s ease;
}

.alert-success::before {
    content: '✓';
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    background: rgba(34, 197, 94, 0.2);
    border-radius: 50%;
    font-size: 1.2rem;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Search Bar */
.search-container {
    margin-bottom: 3rem;
    animation: fadeUp 0.8s ease 0.2s backwards;
}

.search-wrapper {
    position: relative;
    max-width: 600px;
    margin: 0 auto;
}

.search-input {
    width: 100%;
    padding: 1.2rem 3.5rem 1.2rem 1.5rem;
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    color: #ffffff;
    font-size: 1rem;
    font-family: inherit;
    transition: all 0.3s ease;
}

.search-input::placeholder {
    color: rgba(255, 255, 255, 0.4);
}

.search-input:focus {
    outline: none;
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(99, 102, 241, 0.5);
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
}

.search-icon {
    position: absolute;
    right: 1.5rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: 1.3rem;
    pointer-events: none;
    opacity: 0.5;
}

.search-results {
    position: absolute;
    top: calc(100% + 0.5rem);
    left: 0;
    right: 0;
    background: rgba(26, 26, 46, 0.95);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    max-height: 300px;
    overflow-y: auto;
    display: none;
    z-index: 100;
}

.search-results.active {
    display: block;
    animation: slideDown 0.3s ease;
}

.search-result-item {
    padding: 1rem 1.5rem;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.search-result-item:last-child {
    border-bottom: none;
}

.search-result-item:hover {
    background: rgba(255, 255, 255, 0.08);
}

.search-result-icon {
    font-size: 1.8rem;
}

.search-result-text h4 {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.2rem;
}

.search-result-text p {
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.6);
}

.no-results {
    padding: 2rem;
    text-align: center;
    color: rgba(255, 255, 255, 0.4);
}

/* Welcome Section */
.welcome {
    margin-bottom: 3rem;
    animation: fadeUp 0.8s ease;
}

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

.welcome h1 {
    font-size: clamp(2rem, 4vw, 2.8rem);
    font-weight: 700;
    margin-bottom: 0.8rem;
    background: linear-gradient(135deg, #ffffff 0%, #a0a0a0 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.welcome p {
    color: rgba(255, 255, 255, 0.6);
    font-size: 1.1rem;
}

/* Services Grid */
.services {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 2rem;
}

.service-card {
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 24px;
    padding: 2.5rem 2rem;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    cursor: pointer;
    position: relative;
    overflow: hidden;
    opacity: 0;
    transform: translateY(30px);
    animation: cardFadeIn 0.6s ease forwards;
}

@keyframes cardFadeIn {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.service-card:nth-child(1) { animation-delay: 0.1s; }
.service-card:nth-child(2) { animation-delay: 0.2s; }
.service-card:nth-child(3) { animation-delay: 0.3s; }
.service-card:nth-child(4) { animation-delay: 0.4s; }
.service-card:nth-child(5) { animation-delay: 0.5s; }
.service-card:nth-child(6) { animation-delay: 0.6s; }

.service-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #6366f1, #a855f7);
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.service-card:hover::before {
    transform: scaleX(1);
}

.service-card::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(99, 102, 241, 0.1) 0%, transparent 70%);
    transform: translate(-50%, -50%);
    transition: width 0.4s ease, height 0.4s ease;
}

.service-card:hover::after {
    width: 300px;
    height: 300px;
}

.service-card:hover {
    transform: translateY(-12px) scale(1.02);
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(99, 102, 241, 0.3);
    box-shadow: 0 20px 50px rgba(99, 102, 241, 0.2);
}

.service-icon {
    font-size: 3rem;
    margin-bottom: 1.2rem;
    display: block;
    transition: transform 0.3s ease;
    position: relative;
    z-index: 1;
}

.service-card:hover .service-icon {
    transform: scale(1.1) rotate(5deg);
}

.service-card h3 {
    font-size: 1.4rem;
    margin-bottom: 0.6rem;
    font-weight: 600;
    position: relative;
    z-index: 1;
}

.service-card p {
    font-size: 0.95rem;
    color: rgba(255, 255, 255, 0.6);
    line-height: 1.5;
    position: relative;
    z-index: 1;
}

/* Footer */
.footer {
    text-align: center;
    padding: 3rem 1rem;
    margin-top: 4rem;
    border-top: 1px solid rgba(255, 255, 255, 0.05);
}

.footer-content {
    color: rgba(255, 255, 255, 0.4);
    font-size: 0.9rem;
}

/* Mobile Menu Toggle */
.mobile-menu-toggle {
    display: none;
    background: none;
    border: none;
    color: #fff;
    font-size: 1.5rem;
    cursor: pointer;
}

/* Responsive */
@media (max-width: 768px) {
    .navbar {
        padding: 1.2rem 4%;
    }
    
    .container {
        padding: 6rem 4% 3rem;
    }
    
    .nav-links {
        gap: 1rem;
    }
    
    .user-greeting {
        display: none;
    }
    
    .services {
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1.5rem;
    }
    
    .service-card {
        padding: 2rem 1.5rem;
    }
}

@media (max-width: 480px) {
    .services {
        grid-template-columns: 1fr;
    }
    
    .nav-links {
        font-size: 0.85rem;
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
        <span class="user-greeting">Hi, <?= htmlspecialchars($userName) ?> 👋</span>
        <a href="account.php">My Account</a>
        <a href="../auth/logout.php">Logout</a>
    </div>
</nav>

<!-- Main Content -->
<div class="container">
    <?php if ($loginSuccess): ?>
    <div class="alert-success">
        Login successful! Welcome back, <?= htmlspecialchars($userName) ?> 🎉
    </div>
    <?php endif; ?>

    <div class="welcome">
        <h1>What service do you need today?</h1>
        <p>Choose from our trusted home service professionals</p>
    </div>

    <!-- Search Bar -->
    <div class="search-container">
        <div class="search-wrapper">
            <input 
                type="text" 
                class="search-input" 
                id="searchInput"
                placeholder="Search for services... (e.g., electrician, plumber)"
                autocomplete="off"
            >
            <span class="search-icon">🔍</span>
            <div class="search-results" id="searchResults"></div>
        </div>
    </div>

    <div class="services">
        <?php while ($s = $services->fetch_assoc()): 
            $servicesForJs[] = [
                'id' => $s['service_id'],
                'name' => $s['service_name'],
                'description' => $s['description'] ?: 'Professional service',
                'icon' => $s['icon'] ?: '🛠️'
            ];
        ?>
            <div class="service-card"
                onclick="window.location.href='book.php?service_id=<?= $s['service_id'] ?>'">

                <div class="service-icon">
                    <?= htmlspecialchars($s['icon'] ?? '🛠️') ?>
                </div>

                <h3><?= htmlspecialchars($s['service_name']) ?></h3>

                <p>
                    <?= htmlspecialchars($s['description'] ?: 'Trusted professional service') ?>
                </p>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<!-- Footer -->
<footer class="footer">
    <div class="footer-content">
        © 2025 Service-Hub · All rights reserved
    </div>
</footer>

<script>
// Services data
const services = <?= json_encode($servicesForJs, JSON_UNESCAPED_UNICODE); ?>;

// Search functionality
const searchInput = document.getElementById('searchInput');
const searchResults = document.getElementById('searchResults');
const serviceCards = document.querySelectorAll('.service-card');

searchInput.addEventListener('input', (e) => {
    const query = e.target.value.toLowerCase().trim();
    
    if (query === '') {
        searchResults.classList.remove('active');
        // Show all cards
        serviceCards.forEach(card => {
            card.style.display = 'block';
            card.style.animation = 'cardFadeIn 0.6s ease forwards';
        });
        return;
    }
    
    // Filter services - check if name OR description contains the query
    const filtered = services.filter(service => 
        service.name.toLowerCase().includes(query) || 
        service.description.toLowerCase().includes(query) ||
        service.name.toLowerCase().startsWith(query)
    );
    
    // Show results dropdown
    if (filtered.length > 0) {
        searchResults.innerHTML = filtered.map(service => `
            <div class="search-result-item" onclick="selectService('${service.id}')">
                <div class="search-result-icon">${service.icon}</div>
                <div class="search-result-text">
                    <h4>${service.name}</h4>
                    <p>${service.description}</p>
                </div>
            </div>
        `).join('');
        searchResults.classList.add('active');
    } else {
        searchResults.innerHTML = '<div class="no-results">No services found matching "' + query + '"</div>';
        searchResults.classList.add('active');
    }
    
    // Filter service cards in the grid
    let visibleCount = 0;
    serviceCards.forEach((card, index) => {
        const service = services[index];
        const matches = service.name.toLowerCase().includes(query) || 
                       service.description.toLowerCase().includes(query) ||
                       service.name.toLowerCase().startsWith(query);
        
        if (matches) {
            card.style.display = 'block';
            card.style.animation = 'cardFadeIn 0.6s ease forwards';
            card.style.animationDelay = (visibleCount * 0.1) + 's';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
});

// Close search results when clicking outside
document.addEventListener('click', (e) => {
    if (!e.target.closest('.search-wrapper')) {
        searchResults.classList.remove('active');
    }
});

// Select service from search
function selectService(serviceId) {
    window.location.href = `book.php?service_id=${serviceId}`;
}

// Clear search on escape key
searchInput.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        searchInput.value = '';
        searchResults.classList.remove('active');
        serviceCards.forEach(card => {
            card.style.display = 'block';
            card.style.animation = 'cardFadeIn 0.6s ease forwards';
        });
    }
});

// Navbar scroll effect
window.addEventListener("scroll", () => {
    const navbar = document.getElementById("navbar");
    if (window.scrollY > 50) {
        navbar.classList.add("scrolled");
    } else {
        navbar.classList.remove("scrolled");
    }
});

// Auto-hide success alert after 5 seconds
const successAlert = document.querySelector('.alert-success');
if (successAlert) {
    setTimeout(() => {
        successAlert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        successAlert.style.opacity = '0';
        successAlert.style.transform = 'translateY(-20px)';
        setTimeout(() => {
            successAlert.remove();
        }, 500);
    }, 5000);
}
</script>

</body>
</html>