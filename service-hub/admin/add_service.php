<?php
session_start();
require_once dirname(__DIR__) . "/config/db.php";

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

/* ===============================
   HANDLE ADD / UPDATE
================================ */
$message = "";
$messageType = "";

if (isset($_POST['save_service'])) {

    $service_id       = $_POST['service_id'] ?? null;
    $service_name     = trim($_POST['service_name']);
    $slug             = trim($_POST['slug']);
    $profession_key   = trim($_POST['profession_key']);
    $icon             = trim($_POST['icon']);
    $description      = trim($_POST['description']);
    
    // Convert empty strings to NULL for numeric fields
    $base_price       = !empty($_POST['base_price']) ? $_POST['base_price'] : null;
    $min_price        = !empty($_POST['min_price']) ? $_POST['min_price'] : null;
    $max_price        = !empty($_POST['max_price']) ? $_POST['max_price'] : null;
    $estimated_hours  = !empty($_POST['estimated_hours']) ? $_POST['estimated_hours'] : null;
    
    // Convert empty string to NULL for pricing_type
    $pricing_type     = !empty($_POST['pricing_type']) ? $_POST['pricing_type'] : null;
    
    $is_featured      = isset($_POST['is_featured']) ? 1 : 0;
    $status           = isset($_POST['status']) ? 1 : 0;

    if ($service_id) {
        /* UPDATE */
        $stmt = $conn->prepare("
            UPDATE services SET
                service_name = ?,
                slug = ?,
                profession_key = ?,
                icon = ?,
                description = ?,
                base_price = ?,
                min_price = ?,
                max_price = ?,
                pricing_type = ?,
                is_featured = ?,
                estimated_hours = ?,
                status = ?
            WHERE service_id = ?
        ");
        $stmt->bind_param(
            "sssssssssiiii",
            $service_name,
            $slug,
            $profession_key,
            $icon,
            $description,
            $base_price,
            $min_price,
            $max_price,
            $pricing_type,
            $is_featured,
            $estimated_hours,
            $status,
            $service_id
        );
        $stmt->execute();
        $message = "Service updated successfully!";
        $messageType = "success";

    } else {
        /* INSERT */
        $stmt = $conn->prepare("
            INSERT INTO services (
                service_name, slug, profession_key, icon, description,
                base_price, min_price, max_price,
                pricing_type, is_featured, estimated_hours, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "sssssssssiii",
            $service_name,
            $slug,
            $profession_key,
            $icon,
            $description,
            $base_price,
            $min_price,
            $max_price,
            $pricing_type,
            $is_featured,
            $estimated_hours,
            $status
        );
        $stmt->execute();
        $message = "New service added successfully!";
        $messageType = "success";
    }
}

/* ===============================
   HANDLE DELETE
================================ */
if (isset($_GET['delete'])) {
    $stmt = $conn->prepare("DELETE FROM services WHERE service_id = ?");
    $stmt->bind_param("i", $_GET['delete']);
    if ($stmt->execute()) {
        $message = "Service deleted successfully!";
        $messageType = "success";
    } else {
        $message = "Failed to delete service.";
        $messageType = "error";
    }
}

/* ===============================
   FETCH SERVICE FOR EDIT
================================ */
$editService = null;
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM services WHERE service_id = ?");
    $stmt->bind_param("i", $_GET['edit']);
    $stmt->execute();
    $editService = $stmt->get_result()->fetch_assoc();
}

/* ===============================
   FETCH ALL SERVICES
================================ */
$services = $conn->query("SELECT * FROM services ORDER BY service_id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Manage Services | Admin Panel</title>
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
    background: linear-gradient(135deg, #0a0a0f 0%, #1a1a2e 50%, #16213e 100%);
}

.bg-gradient::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle at 50% 50%, rgba(239, 68, 68, 0.12) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(245, 158, 11, 0.12) 0%, transparent 50%),
                radial-gradient(circle at 20% 80%, rgba(234, 88, 12, 0.12) 0%, transparent 50%);
    animation: floatGradient 20s ease-in-out infinite;
}

@keyframes floatGradient {
    0%, 100% { transform: translate(0, 0) rotate(0deg); }
    33% { transform: translate(30px, -30px) rotate(120deg); }
    66% { transform: translate(-20px, 20px) rotate(240deg); }
}

/* Grid pattern overlay */
.grid-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: -1;
    background-image: 
        linear-gradient(rgba(239, 68, 68, 0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(239, 68, 68, 0.03) 1px, transparent 1px);
    background-size: 50px 50px;
    opacity: 0.3;
}

/* Container */
.container {
    padding: 3rem 5%;
    max-width: 1600px;
    margin: auto;
    min-height: 100vh;
}

/* Page Header */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1.5rem;
    animation: fadeUp 0.6s ease;
}

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

.header-content h1 {
    font-size: 2.5rem;
    font-weight: 800;
    margin-bottom: 0.5rem;
    background: linear-gradient(135deg, #ffffff 0%, #ef4444 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.header-content p {
    color: rgba(255, 255, 255, 0.6);
    font-size: 1rem;
}

.back-link {
    padding: 1rem 2rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    border-radius: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
}

.back-link:hover {
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(239, 68, 68, 0.3);
    color: #ffffff;
    transform: translateX(-5px);
}

/* Alert Messages */
.alert {
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(30px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 1.2rem 1.5rem;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    animation: slideIn 0.5s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.alert-success {
    border-color: rgba(34, 197, 94, 0.3);
    background: rgba(34, 197, 94, 0.1);
}

.alert-success::before {
    content: '✅';
    font-size: 1.5rem;
}

.alert-error {
    border-color: rgba(239, 68, 68, 0.3);
    background: rgba(239, 68, 68, 0.1);
}

.alert-error::before {
    content: '⚠️';
    font-size: 1.5rem;
}

.alert-success {
    color: #86efac;
}

.alert-error {
    color: #fca5a5;
}

/* Form Card */
.form-card {
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(30px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    padding: 2.5rem;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
    animation: fadeUp 0.6s ease 0.1s backwards;
}

.form-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #ef4444, #f97316, #f59e0b);
    border-radius: 20px 20px 0 0;
}

.form-header {
    margin-bottom: 2rem;
}

.form-header h2 {
    font-size: 1.8rem;
    font-weight: 700;
    color: #ffffff;
    display: flex;
    align-items: center;
    gap: 0.8rem;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-label {
    font-size: 0.9rem;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.8);
    margin-bottom: 0.6rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-input,
.form-textarea,
.form-select {
    padding: 1rem 1.2rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    color: #ffffff;
    font-size: 0.95rem;
    font-family: inherit;
    transition: all 0.3s ease;
}

.form-input:focus,
.form-textarea:focus,
.form-select:focus {
    outline: none;
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(239, 68, 68, 0.5);
    box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
}

.form-input::placeholder,
.form-textarea::placeholder {
    color: rgba(255, 255, 255, 0.3);
}

.form-select {
    cursor: pointer;
}

.form-select option {
    background: #1a1a2e;
    color: #ffffff;
}

.form-textarea {
    resize: vertical;
    min-height: 120px;
}

.checkbox-group {
    display: flex;
    gap: 2rem;
    margin: 1.5rem 0;
    flex-wrap: wrap;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    cursor: pointer;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.8);
    padding: 0.8rem 1.2rem;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    transition: all 0.3s ease;
}

.checkbox-label:hover {
    background: rgba(255, 255, 255, 0.05);
    border-color: rgba(239, 68, 68, 0.3);
}

.checkbox-label input[type="checkbox"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
    accent-color: #ef4444;
}

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    flex-wrap: wrap;
}

.btn {
    padding: 1rem 2rem;
    border: none;
    border-radius: 12px;
    font-weight: 700;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
}

.btn-primary {
    background: linear-gradient(135deg, #ef4444, #f97316);
    color: #ffffff;
    box-shadow: 0 10px 30px rgba(239, 68, 68, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 15px 40px rgba(239, 68, 68, 0.4);
}

.btn-secondary {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: rgba(255, 255, 255, 0.8);
}

.btn-secondary:hover {
    background: rgba(255, 255, 255, 0.08);
    color: #ffffff;
}

/* Services Table Card */
.services-card {
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(30px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    padding: 2.5rem;
    position: relative;
    overflow: hidden;
    animation: fadeUp 0.6s ease 0.2s backwards;
}

.services-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #ef4444, #f97316, #f59e0b);
    border-radius: 20px 20px 0 0;
}

.table-header {
    margin-bottom: 2rem;
}

.table-header h2 {
    font-size: 1.8rem;
    font-weight: 700;
    color: #ffffff;
    display: flex;
    align-items: center;
    gap: 0.8rem;
}

.table-wrapper {
    overflow-x: auto;
    border-radius: 12px;
}

table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

thead {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(249, 115, 22, 0.2));
}

th {
    padding: 1.2rem 1rem;
    text-align: left;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.9);
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

tbody tr {
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    transition: all 0.3s ease;
}

tbody tr:hover {
    background: rgba(255, 255, 255, 0.03);
}

td {
    padding: 1.2rem 1rem;
    color: rgba(255, 255, 255, 0.8);
}

.service-name {
    display: flex;
    align-items: center;
    gap: 1rem;
    font-weight: 600;
    color: #ffffff;
}

.service-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #ef4444, #f97316);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.badge {
    padding: 0.4rem 0.9rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    display: inline-block;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-active {
    background: rgba(34, 197, 94, 0.2);
    color: #86efac;
    border: 1px solid rgba(34, 197, 94, 0.3);
}

.badge-inactive {
    background: rgba(239, 68, 68, 0.2);
    color: #fca5a5;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.badge-featured {
    background: rgba(245, 158, 11, 0.2);
    color: #fbbf24;
    border: 1px solid rgba(245, 158, 11, 0.3);
    margin-left: 0.5rem;
}

.pricing-info {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
}

.price-amount {
    font-weight: 700;
    color: #22d3ee;
    font-size: 1.05rem;
}

.price-type {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.5);
    text-transform: capitalize;
}

.action-buttons {
    display: flex;
    gap: 0.6rem;
}

.btn-icon {
    width: 38px;
    height: 38px;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.95rem;
}

.btn-edit {
    background: rgba(34, 211, 238, 0.15);
    color: #22d3ee;
    border: 1px solid rgba(34, 211, 238, 0.3);
}

.btn-edit:hover {
    background: rgba(34, 211, 238, 0.25);
    transform: translateY(-2px);
}

.btn-delete {
    background: rgba(239, 68, 68, 0.15);
    color: #fca5a5;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.btn-delete:hover {
    background: rgba(239, 68, 68, 0.25);
    transform: translateY(-2px);
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: rgba(255, 255, 255, 0.4);
}

.empty-state-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.3;
}

.empty-state p {
    font-size: 1.1rem;
}

/* Responsive */
@media (max-width: 768px) {
    .container {
        padding: 2rem 4%;
    }
    
    .page-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .header-content h1 {
        font-size: 2rem;
    }
    
    .back-link {
        width: 100%;
        justify-content: center;
    }
    
    .form-card,
    .services-card {
        padding: 2rem 1.5rem;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .checkbox-group {
        flex-direction: column;
        gap: 1rem;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
    
    .table-wrapper {
        overflow-x: scroll;
    }
    
    table {
        min-width: 800px;
    }
}

/* Smooth scrollbar */
.table-wrapper::-webkit-scrollbar {
    height: 8px;
}

.table-wrapper::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
}

.table-wrapper::-webkit-scrollbar-thumb {
    background: rgba(239, 68, 68, 0.3);
    border-radius: 10px;
}

.table-wrapper::-webkit-scrollbar-thumb:hover {
    background: rgba(239, 68, 68, 0.5);
}
</style>
</head>

<body>
<div class="bg-gradient"></div>
<div class="grid-overlay"></div>

<div class="container">

    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>🛠️ Service Management</h1>
            <p>Create and manage platform services</p>
        </div>
        <a href="home.php" class="back-link">
            ← Back to Dashboard
        </a>
    </div>

    <!-- Alert Messages -->
    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>">
            <span><?= htmlspecialchars($message) ?></span>
        </div>
    <?php endif; ?>

    <!-- Add/Edit Form -->
    <div class="form-card">
        <div class="form-header">
            <h2>
                <?= $editService ? '✏️ Edit Service' : '➕ Add New Service' ?>
            </h2>
        </div>

        <form method="POST" id="serviceForm">
            <input type="hidden" name="service_id" value="<?= $editService['service_id'] ?? '' ?>">

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">
                        🏷️ Service Name *
                    </label>
                    <input 
                        type="text" 
                        name="service_name" 
                        class="form-input" 
                        placeholder="e.g., House Cleaning" 
                        required 
                        value="<?= htmlspecialchars($editService['service_name'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label class="form-label">
                        🔗 Slug
                    </label>
                    <input 
                        type="text" 
                        name="slug" 
                        class="form-input" 
                        placeholder="e.g., house-cleaning" 
                        value="<?= htmlspecialchars($editService['slug'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label class="form-label">
                        💼 Profession Key
                    </label>
                    <input 
                        type="text" 
                        name="profession_key" 
                        class="form-input" 
                        placeholder="e.g., cleaner" 
                        value="<?= htmlspecialchars($editService['profession_key'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label class="form-label">
                        🎨 Icon (Emoji)
                    </label>
                    <input 
                        type="text" 
                        name="icon" 
                        class="form-input" 
                        placeholder="e.g., 🧹" 
                        value="<?= htmlspecialchars($editService['icon'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label class="form-label">
                        💰 Base Price
                    </label>
                    <input 
                        type="number" 
                        name="base_price" 
                        class="form-input" 
                        placeholder="0.00" 
                        step="0.01"
                        value="<?= htmlspecialchars($editService['base_price'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label class="form-label">
                        📉 Min Price
                    </label>
                    <input 
                        type="number" 
                        name="min_price" 
                        class="form-input" 
                        placeholder="0.00" 
                        step="0.01"
                        value="<?= htmlspecialchars($editService['min_price'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label class="form-label">
                        📈 Max Price
                    </label>
                    <input 
                        type="number" 
                        name="max_price" 
                        class="form-input" 
                        placeholder="0.00" 
                        step="0.01"
                        value="<?= htmlspecialchars($editService['max_price'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label class="form-label">
                        ⏱️ Estimated Hours
                    </label>
                    <input 
                        type="number" 
                        name="estimated_hours" 
                        class="form-input" 
                        placeholder="0.0" 
                        step="0.5"
                        value="<?= htmlspecialchars($editService['estimated_hours'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label class="form-label">
                        💵 Pricing Type
                    </label>
                    <select name="pricing_type" class="form-select">
                        <option value="" <?= empty($editService['pricing_type'] ?? '') ? 'selected' : '' ?>>
                            -- Select Pricing Type --
                        </option>
                        <option value="fixed" <?= ($editService['pricing_type'] ?? '') == 'fixed' ? 'selected' : '' ?>>
                            Fixed Price
                        </option>
                        <option value="hourly" <?= ($editService['pricing_type'] ?? '') == 'hourly' ? 'selected' : '' ?>>
                            Hourly Rate
                        </option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">
                    📝 Service Description
                </label>
                <textarea 
                    name="description" 
                    class="form-textarea" 
                    placeholder="Enter a detailed description of the service..."
                ><?= htmlspecialchars($editService['description'] ?? '') ?></textarea>
            </div>

            <div class="checkbox-group">
                <label class="checkbox-label">
                    <input 
                        type="checkbox" 
                        name="is_featured" 
                        <?= ($editService['is_featured'] ?? 0) ? 'checked' : '' ?>
                    >
                    ⭐ Featured Service
                </label>

                <label class="checkbox-label">
                    <input 
                        type="checkbox" 
                        name="status" 
                        <?= ($editService['status'] ?? 1) ? 'checked' : '' ?>
                    >
                    ✅ Active Status
                </label>
            </div>

            <div class="form-actions">
                <button type="submit" name="save_service" class="btn btn-primary">
                    💾 <?= $editService ? 'Update Service' : 'Add Service' ?>
                </button>
                
                <?php if ($editService): ?>
                    <a href="add_service.php" class="btn btn-secondary">
                        ✖️ Cancel Edit
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Services List -->
    <div class="services-card">
        <div class="table-header">
            <h2>
                📋 All Services
            </h2>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Service</th>
                        <th>Pricing</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($services->num_rows > 0): ?>
                        <?php while ($s = $services->fetch_assoc()): ?>
                            <tr>
                                <td>#<?= $s['service_id'] ?></td>
                                <td>
                                    <div class="service-name">
                                        <div class="service-icon">
                                            <?= htmlspecialchars($s['icon'] ?: '🛠️') ?>
                                        </div>
                                        <div>
                                            <?= htmlspecialchars($s['service_name']) ?>
                                            <?php if ($s['is_featured']): ?>
                                                <span class="badge badge-featured">
                                                    ⭐ Featured
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="pricing-info">
                                        <?php if ($s['base_price']): ?>
                                            <span class="price-amount">₹<?= number_format($s['base_price'], 2) ?></span>
                                        <?php elseif ($s['min_price'] && $s['max_price']): ?>
                                            <span class="price-amount">₹<?= number_format($s['min_price'], 2) ?> - ₹<?= number_format($s['max_price'], 2) ?></span>
                                        <?php else: ?>
                                            <span class="price-amount">-</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="price-type"><?= ucfirst($s['pricing_type']) ?></span>
                                </td>
                                <td>
                                    <span class="badge badge-<?= $s['status'] ? 'active' : 'inactive' ?>">
                                        <?= $s['status'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="add_service.php?edit=<?= $s['service_id'] ?>" class="btn-icon btn-edit" title="Edit">
                                            ✏️
                                        </a>
                                        <button 
                                            onclick="confirmDelete(<?= $s['service_id'] ?>, '<?= htmlspecialchars($s['service_name']) ?>')" 
                                            class="btn-icon btn-delete" 
                                            title="Delete"
                                        >
                                            🗑️
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <div class="empty-state-icon">📦</div>
                                    <p>No services found. Add your first service above!</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
// Auto-generate slug from service name
const serviceNameInput = document.querySelector('input[name="service_name"]');
const slugInput = document.querySelector('input[name="slug"]');

if (serviceNameInput && slugInput) {
    serviceNameInput.addEventListener('input', function() {
        if (!slugInput.value || slugInput.dataset.auto !== 'false') {
            slugInput.value = this.value
                .toLowerCase()
                .trim()
                .replace(/[^\w\s-]/g, '')
                .replace(/[\s_-]+/g, '-')
                .replace(/^-+|-+$/g, '');
            slugInput.dataset.auto = 'true';
        }
    });
    
    slugInput.addEventListener('input', function() {
        this.dataset.auto = 'false';
    });
}

// Confirm delete
function confirmDelete(id, name) {
    if (confirm(`Are you sure you want to delete "${name}"?\n\nThis action cannot be undone.`)) {
        window.location.href = `add_service.php?delete=${id}`;
    }
}

// Auto-hide alerts
const alerts = document.querySelectorAll('.alert');
alerts.forEach(alert => {
    setTimeout(() => {
        alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-20px)';
        setTimeout(() => alert.remove(), 500);
    }, 5000);
});

// Form validation
const form = document.getElementById('serviceForm');
form.addEventListener('submit', function(e) {
    const serviceName = document.querySelector('input[name="service_name"]').value.trim();
    
    if (!serviceName) {
        e.preventDefault();
        alert('Please enter a service name.');
        return false;
    }
});

// Smooth scroll to form when editing
if (window.location.search.includes('edit=')) {
    setTimeout(() => {
        document.querySelector('.form-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 100);
}
</script>

</body>
</html>