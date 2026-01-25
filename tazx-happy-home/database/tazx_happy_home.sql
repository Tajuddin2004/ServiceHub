CREATE DATABASE IF NOT EXISTS tazx_happy_home;
USE tazx_happy_home;

-- USERS (Consumer + Provider + Admin)
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(15),
    password VARCHAR(255) NOT NULL,
    role ENUM('consumer','provider','admin') NOT NULL,
    status TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- SERVICE CATEGORIES
CREATE TABLE services (
    service_id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(100),
    description TEXT,
    base_price DECIMAL(10,2),
    status TINYINT DEFAULT 1
);

-- PROVIDER DETAILS
CREATE TABLE service_providers (
    provider_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    specialization VARCHAR(100),
    experience INT,
    area VARCHAR(100),
    id_proof VARCHAR(255),
    is_approved TINYINT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- BOOKINGS
CREATE TABLE bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    consumer_id INT,
    provider_id INT,
    service_id INT,
    booking_date DATE,
    booking_time TIME,
    address TEXT,
    problem_description TEXT,
    status ENUM('Pending','Accepted','In Progress','Completed','Cancelled') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- PAYMENTS
CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT,
    amount DECIMAL(10,2),
    method ENUM('Online','COD'),
    status ENUM('Pending','Paid','Failed'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- REVIEWS
CREATE TABLE reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT,
    rating INT,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
