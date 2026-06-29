-- Hardware Store Database Schema
-- Run this in phpMyAdmin > SQL tab

CREATE DATABASE IF NOT EXISTS hardware_store
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE hardware_store;

-- --------------------------------------------------------
-- Users (single admin user for the dashboard)
-- --------------------------------------------------------
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,  -- store hashed password (bcrypt)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Run setup_user.php once after importing this SQL to create the admin user.

-- --------------------------------------------------------
-- Categories
-- --------------------------------------------------------
CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- --------------------------------------------------------
-- Products
-- --------------------------------------------------------
CREATE TABLE products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    unit VARCHAR(50) NOT NULL DEFAULT 'piece',  -- e.g. piece, meter, kg, liter, box
    image VARCHAR(255) DEFAULT NULL,            -- stores the image filename e.g. hammer.jpg
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
);

-- --------------------------------------------------------
-- Transactions (Receipt Log)
-- --------------------------------------------------------
CREATE TABLE transactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(100) DEFAULT 'Walk-in',
    total_amount DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- --------------------------------------------------------
-- Transaction Items (What was bought per transaction)
-- --------------------------------------------------------
CREATE TABLE transaction_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity DECIMAL(10, 3) NOT NULL,           -- supports decimals: 2.5 meters, 0.75 kg
    unit_price DECIMAL(10, 2) NOT NULL,         -- price snapshot at time of sale
    subtotal DECIMAL(10, 2) NOT NULL,           -- quantity * unit_price
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);

-- --------------------------------------------------------
-- Sample Data
-- --------------------------------------------------------
INSERT INTO categories (name) VALUES
    ('Plumbing'),
    ('Electrical'),
    ('Tools'),
    ('Paint & Supplies'),
    ('Hardware & Fasteners');

INSERT INTO products (category_id, name, price, unit) VALUES
    (1, 'PVC Pipe 1/2"', 45.00, 'meter'),
    (1, 'Gate Valve 1/2"', 120.00, 'piece'),
    (2, 'Electrical Wire 2.0mm', 18.00, 'meter'),
    (2, 'Outlet (Universal)', 85.00, 'piece'),
    (3, 'Claw Hammer', 250.00, 'piece'),
    (3, 'Measuring Tape 5m', 180.00, 'piece'),
    (4, 'Flat Latex Paint White', 320.00, 'liter'),
    (4, 'Paint Roller', 95.00, 'piece'),
    (5, 'Concrete Nails 4"', 75.00, 'kg'),
    (5, 'Wood Screw Assorted Box', 110.00, 'box');
