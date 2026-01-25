<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Tazx Happy-Home | Professional Home Services</title>
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

/* NAVBAR */
.navbar {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    padding: 1.5rem 5%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    z-index: 1000;
    background: rgba(10, 10, 15, 0.7);
    backdrop-filter: blur(20px) saturate(180%);
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
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
    font-size: 1.5rem;
    filter: drop-shadow(0 0 10px rgba(34, 211, 238, 0.5));
}

.nav-links {
    display: flex;
    gap: 2rem;
    align-items: center;
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

/* HERO */
.hero {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 8rem 5% 4rem;
    position: relative;
}

.hero-content {
    max-width: 1000px;
    animation: fadeUp 1s ease-out;
}

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(40px); }
    to { opacity: 1; transform: translateY(0); }
}

.hero-badge {
    display: inline-block;
    padding: 0.5rem 1.2rem;
    background: rgba(99, 102, 241, 0.1);
    border: 1px solid rgba(99, 102, 241, 0.3);
    border-radius: 999px;
    font-size: 0.85rem;
    font-weight: 500;
    margin-bottom: 2rem;
    animation: fadeUp 1s ease-out 0.2s backwards;
}

.hero h1 {
    font-size: clamp(2.5rem, 6vw, 5rem);
    font-weight: 800;
    line-height: 1.1;
    background: linear-gradient(135deg, #ffffff 0%, #a0a0a0 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 1.5rem;
    animation: fadeUp 1s ease-out 0.3s backwards;
}

.hero-highlight {
    background: linear-gradient(135deg, #22d3ee, #a855f7);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.hero p {
    font-size: 1.25rem;
    line-height: 1.6;
    color: rgba(255, 255, 255, 0.7);
    max-width: 700px;
    margin: 0 auto 2.5rem;
    animation: fadeUp 1s ease-out 0.4s backwards;
}

.hero-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
    animation: fadeUp 1s ease-out 0.5s backwards;
}

/* BUTTONS */
.btn {
    padding: 1rem 2.5rem;
    border-radius: 12px;
    border: none;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
}

.btn-primary {
    background: linear-gradient(135deg, #6366f1, #a855f7);
    color: #fff;
    box-shadow: 0 10px 30px rgba(99, 102, 241, 0.3);
    position: relative;
    overflow: hidden;
}

.btn-primary::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s ease;
}

.btn-primary:hover::before {
    left: 100%;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 15px 40px rgba(99, 102, 241, 0.4);
}

.btn-outline {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: #fff;
    backdrop-filter: blur(10px);
}

.btn-outline:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.4);
    transform: translateY(-2px);
}

/* Floating elements */
.floating-card {
    position: absolute;
    padding: 1.5rem;
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    animation: float 6s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
}

.float-1 {
    top: 20%;
    left: 10%;
    animation-delay: 0s;
}

.float-2 {
    top: 60%;
    right: 10%;
    animation-delay: 2s;
}

/* SECTIONS */
.section {
    padding: 6rem 5%;
    position: relative;
}

.section h2 {
    font-size: clamp(2rem, 4vw, 3rem);
    margin-bottom: 1rem;
    font-weight: 700;
    text-align: center;
}

.section-subtitle {
    text-align: center;
    max-width: 700px;
    margin: 0 auto 4rem;
    font-size: 1.1rem;
    color: rgba(255, 255, 255, 0.6);
}

/* HOW IT WORKS */
.steps {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    max-width: 1200px;
    margin: 0 auto;
}

.step {
    padding: 2rem;
    border-radius: 20px;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.step::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, #6366f1, #a855f7);
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.step:hover::before {
    transform: scaleX(1);
}

.step:hover {
    background: rgba(255, 255, 255, 0.08);
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
}

.step-number {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6366f1, #a855f7);
    font-weight: 700;
    font-size: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 10px 30px rgba(99, 102, 241, 0.3);
}

.step h4 {
    font-size: 1.3rem;
    margin-bottom: 0.8rem;
    font-weight: 600;
}

.step p {
    color: rgba(255, 255, 255, 0.6);
    line-height: 1.6;
}

/* WHY US */
.features {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 2rem;
    max-width: 1200px;
    margin: 0 auto;
}

.feature-card {
    padding: 2.5rem 2rem;
    border-radius: 20px;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    position: relative;
    overflow: hidden;
}

.feature-card::before {
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

.feature-card:hover::before {
    width: 300px;
    height: 300px;
}

.feature-card:hover {
    transform: translateY(-10px) scale(1.02);
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(99, 102, 241, 0.3);
    box-shadow: 0 20px 50px rgba(99, 102, 241, 0.2);
}

.feature-icon {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    display: block;
}

.feature-card span {
    position: relative;
    z-index: 1;
    font-size: 1.1rem;
    font-weight: 600;
}

/* FOOTER */
.footer {
    padding: 3rem 5%;
    text-align: center;
    border-top: 1px solid rgba(255, 255, 255, 0.05);
    background: rgba(10, 10, 15, 0.5);
}

.footer-links {
    display: flex;
    justify-content: center;
    gap: 2rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.footer a {
    color: rgba(255, 255, 255, 0.6);
    text-decoration: none;
    font-size: 0.95rem;
    transition: color 0.3s ease;
}

.footer a:hover {
    color: #22d3ee;
}

.footer-copy {
    color: rgba(255, 255, 255, 0.4);
    font-size: 0.9rem;
}

/* Responsive */
@media (max-width: 768px) {
    .navbar {
        padding: 1.2rem 4%;
    }
    
    .hero {
        padding: 6rem 4% 3rem;
    }
    
    .floating-card {
        display: none;
    }
    
    .section {
        padding: 4rem 4%;
    }
}
</style>
</head>

<body>
<div class="bg-gradient"></div>

<!-- NAVBAR -->
<nav class="navbar" id="navbar">
    <div class="logo">
        <span class="logo-icon">🏠</span>
        Tazx Happy-Home
    </div>
    <div class="nav-links">
        <a href="auth/login.php">Sign in</a>
        <a href="auth/register.php" class="btn btn-primary" style="padding: 0.6rem 1.5rem; font-size: 0.9rem;">Get started</a>
    </div>
</nav>

<!-- Floating decoration cards -->
<div class="floating-card float-1" style="display: none;">
    ⚡ Fast Service
</div>
<div class="floating-card float-2" style="display: none;">
    ⭐ 5-Star Rated
</div>

<!-- HERO -->
<section class="hero">
    <div class="hero-content">
        <div class="hero-badge">✨ Trusted by 10,000+ happy customers</div>
        <h1>
            The <span class="hero-highlight">future</span> of home<br>services starts here
        </h1>
        <p>
            Book trusted professionals for plumbing, electrical work, carpentry, painting & more — fast, safe, and reliable.
        </p>

        <div class="hero-actions">
            <a href="auth/register.php" class="btn btn-primary">Get started free</a>
            <a href="auth/login.php" class="btn btn-outline">Sign in</a>
        </div>
    </div>
</section>

<!-- ABOUT -->
<section class="section">
    <h2>Our mission</h2>
    <p class="section-subtitle">
        We're eliminating the hassle of finding reliable home service professionals. Connect with verified experts for quality service, transparent pricing, and complete peace of mind.
    </p>
</section>

<!-- HOW IT WORKS -->
<section class="section">
    <h2>How it works</h2>
    <p class="section-subtitle">Get professional home services in four simple steps</p>

    <div class="steps">
        <div class="step">
            <div class="step-number">1</div>
            <h4>Choose a service</h4>
            <p>Select from our wide range of professional home services tailored to your needs.</p>
        </div>
        <div class="step">
            <div class="step-number">2</div>
            <h4>Book instantly</h4>
            <p>Pick your preferred date, time, and location with our easy booking system.</p>
        </div>
        <div class="step">
            <div class="step-number">3</div>
            <h4>Expert arrives</h4>
            <p>A verified, background-checked professional shows up and completes the job.</p>
        </div>
        <div class="step">
            <div class="step-number">4</div>
            <h4>Pay & review</h4>
            <p>Secure payment options available. Share your experience to help others.</p>
        </div>
    </div>
</section>

<!-- WHY US -->
<section class="section">
    <h2>Why choose us</h2>
    <p class="section-subtitle">Experience the difference with our premium service standards</p>

    <div class="features">
        <div class="feature-card">
            <span class="feature-icon">✓</span>
            <span>Verified professionals</span>
        </div>
        <div class="feature-card">
            <span class="feature-icon">💰</span>
            <span>Transparent pricing</span>
        </div>
        <div class="feature-card">
            <span class="feature-icon">🔒</span>
            <span>Secure payments</span>
        </div>
        <div class="feature-card">
            <span class="feature-icon">⭐</span>
            <span>Ratings & reviews</span>
        </div>
        <div class="feature-card">
            <span class="feature-icon">💬</span>
            <span>24/7 support</span>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer class="footer">
    <div class="footer-links">
        <a href="#">Terms of Service</a>
        <a href="#">Privacy Policy</a>
        <a href="#">Support</a>
        <a href="#">About Us</a>
    </div>
    <div class="footer-copy">
        © 2025 Tazx Happy-Home. All rights reserved.
    </div>
</footer>

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

// Intersection Observer for animation on scroll
const observerOptions = {
    threshold: 0.1,
    rootMargin: "0px 0px -100px 0px"
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = "1";
            entry.target.style.transform = "translateY(0)";
        }
    });
}, observerOptions);

document.querySelectorAll(".step, .feature-card").forEach(el => {
    el.style.opacity = "0";
    el.style.transform = "translateY(30px)";
    el.style.transition = "opacity 0.6s ease, transform 0.6s ease";
    observer.observe(el);
});
</script>

</body>
</html>