-- FixNow Cars refined database schema.
-- This version keeps the project simple while covering the core tables needed
-- for users, cars, services, service requests, receipts, and ratings.

CREATE DATABASE IF NOT EXISTS fixnow_cars
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE fixnow_cars;

-- Drop dependent tables first so the schema can be re-imported safely.
DROP TABLE IF EXISTS ratings;
DROP TABLE IF EXISTS receipts;
DROP TABLE IF EXISTS service_requests;
DROP TABLE IF EXISTS cars;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS users;

-- Stores all system accounts: normal users, technicians, and admins.
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'technician', 'admin') NOT NULL DEFAULT 'user',
    address VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Stores the cars owned by users.
CREATE TABLE cars (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    brand VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    year YEAR NOT NULL,
    plate_number VARCHAR(30) NOT NULL UNIQUE,
    color VARCHAR(30) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cars_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
);

-- Stores the list of available car services.
CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    base_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Stores each service request submitted by users.
CREATE TABLE service_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    car_id INT NOT NULL,
    service_id INT NOT NULL,
    technician_id INT DEFAULT NULL,
    problem_description TEXT NOT NULL,
    location VARCHAR(255) NOT NULL,
    request_date DATE NOT NULL,
    status ENUM('pending', 'accepted', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    estimated_price DECIMAL(10,2) DEFAULT NULL,
    final_price DECIMAL(10,2) DEFAULT NULL,
    admin_notes TEXT DEFAULT NULL,
    technician_notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_requests_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_requests_car
        FOREIGN KEY (car_id) REFERENCES cars(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_requests_service
        FOREIGN KEY (service_id) REFERENCES services(id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_requests_technician
        FOREIGN KEY (technician_id) REFERENCES users(id)
        ON DELETE SET NULL
);

-- Stores one receipt for each completed or billed service request.
CREATE TABLE receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL UNIQUE,
    amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('unpaid', 'paid') NOT NULL DEFAULT 'unpaid',
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_receipts_request
        FOREIGN KEY (request_id) REFERENCES service_requests(id)
        ON DELETE CASCADE
);

-- Stores user ratings for technician work after a request is completed.
CREATE TABLE ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL UNIQUE,
    user_id INT NOT NULL,
    technician_id INT NOT NULL,
    rating TINYINT NOT NULL,
    comment TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ratings_request
        FOREIGN KEY (request_id) REFERENCES service_requests(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_ratings_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_ratings_technician
        FOREIGN KEY (technician_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT chk_rating_value
        CHECK (rating BETWEEN 1 AND 5)
);

-- Default admin account for first login.
-- Email: admin@fixnow.com
-- Password: password
INSERT INTO users (name, email, phone, password, role, address) VALUES
('System Admin', 'admin@fixnow.com', '01000000000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Main Office');

-- Sample services for the initial service catalog.
INSERT INTO services (service_name, description, base_price) VALUES
('Oil Change', 'Engine oil and oil filter replacement.', 300.00),
('Brake Inspection', 'Basic brake system inspection and safety check.', 250.00),
('Battery Check', 'Battery testing and charging system check.', 150.00),
('Tire Replacement', 'Replacement of damaged or worn tires.', 600.00);
