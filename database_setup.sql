-- VELA Rental Management Database Setup
-- Drop database if exists and create new one
DROP DATABASE IF EXISTS vela_rental;
CREATE DATABASE vela_rental;
USE vela_rental;

-- Users table
CREATE TABLE USERS (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('tenant', 'landlord') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE
);

-- Properties table
CREATE TABLE PROPERTIES (
    property_id INT PRIMARY KEY AUTO_INCREMENT,
    landlord_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    address VARCHAR(255) NOT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(50) NOT NULL,
    zip_code VARCHAR(10) NOT NULL,
    property_type ENUM('apartment', 'house', 'condo', 'studio') NOT NULL,
    bedrooms INT NOT NULL,
    bathrooms DECIMAL(2,1) NOT NULL,
    square_feet INT,
    rent_amount DECIMAL(10,2) NOT NULL,
    security_deposit DECIMAL(10,2) NOT NULL,
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (landlord_id) REFERENCES USERS(user_id)
);

-- Leases table
CREATE TABLE LEASES (
    lease_id INT PRIMARY KEY AUTO_INCREMENT,
    property_id INT NOT NULL,
    tenant_id INT NOT NULL,
    landlord_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    monthly_rent DECIMAL(10,2) NOT NULL,
    security_deposit DECIMAL(10,2) NOT NULL,
    status ENUM('active', 'expired', 'terminated') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES PROPERTIES(property_id),
    FOREIGN KEY (tenant_id) REFERENCES USERS(user_id),
    FOREIGN KEY (landlord_id) REFERENCES USERS(user_id)
);

-- Rent payments table
CREATE TABLE RENT_PAYMENTS (
    payment_id INT PRIMARY KEY AUTO_INCREMENT,
    lease_id INT NOT NULL,
    tenant_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    due_date DATE NOT NULL,
    payment_method ENUM('cash', 'check', 'bank_transfer', 'credit_card') NOT NULL,
    status ENUM('paid', 'pending', 'overdue') DEFAULT 'pending',
    late_fee DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lease_id) REFERENCES LEASES(lease_id),
    FOREIGN KEY (tenant_id) REFERENCES USERS(user_id)
);

-- Maintenance requests table
CREATE TABLE MAINTENANCE_REQUESTS (
    request_id INT PRIMARY KEY AUTO_INCREMENT,
    property_id INT NOT NULL,
    tenant_id INT NOT NULL,
    landlord_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    requested_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_date TIMESTAMP NULL,
    cost DECIMAL(10,2) DEFAULT 0.00,
    FOREIGN KEY (property_id) REFERENCES PROPERTIES(property_id),
    FOREIGN KEY (tenant_id) REFERENCES USERS(user_id),
    FOREIGN KEY (landlord_id) REFERENCES USERS(user_id)
);

-- Insert sample users (passwords are hashed for 'password123')
INSERT INTO USERS (first_name, last_name, email, password, phone, role, last_login) VALUES
('John', 'Smith', 'john.landlord@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-0101', 'landlord', '2024-01-15 10:30:00'),
('Sarah', 'Johnson', 'sarah.landlord@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-0102', 'landlord', '2024-01-14 14:20:00'),
('Mike', 'Davis', 'mike.tenant@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-0201', 'tenant', '2024-01-15 09:15:00'),
('Emily', 'Wilson', 'emily.tenant@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-0202', 'tenant', '2024-01-14 16:45:00'),
('David', 'Brown', 'david.tenant@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-0203', 'tenant', '2024-01-13 11:30:00'),
('Lisa', 'Garcia', 'lisa.tenant@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-0204', 'tenant', '2024-01-15 08:20:00'),
('Robert', 'Miller', 'robert.landlord@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-0103', 'landlord', '2024-01-12 13:10:00'),
('Jennifer', 'Taylor', 'jennifer.tenant@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-0205', 'tenant', '2024-01-14 17:30:00');

-- Insert sample properties
INSERT INTO PROPERTIES (landlord_id, title, description, address, city, state, zip_code, property_type, bedrooms, bathrooms, square_feet, rent_amount, security_deposit, is_available) VALUES
(1, 'Modern Downtown Apartment', 'Beautiful 2-bedroom apartment in the heart of downtown with city views', '123 Main St Apt 4B', 'New York', 'NY', '10001', 'apartment', 2, 2.0, 1200, 2500.00, 5000.00, FALSE),
(1, 'Cozy Studio Near Park', 'Charming studio apartment with park views and modern amenities', '456 Park Ave Unit 12', 'New York', 'NY', '10002', 'studio', 0, 1.0, 600, 1800.00, 3600.00, TRUE),
(2, 'Spacious Family House', 'Large 4-bedroom house with backyard, perfect for families', '789 Oak Street', 'Brooklyn', 'NY', '11201', 'house', 4, 3.0, 2400, 3200.00, 6400.00, FALSE),
(2, 'Luxury Condo with Amenities', 'High-end 3-bedroom condo with gym and pool access', '321 Luxury Blvd Unit 15A', 'Manhattan', 'NY', '10003', 'condo', 3, 2.5, 1800, 4500.00, 9000.00, TRUE),
(7, 'Charming 1-Bedroom Apartment', 'Quiet 1-bedroom in residential area with parking', '654 Elm Street Apt 2C', 'Queens', 'NY', '11101', 'apartment', 1, 1.0, 800, 1900.00, 3800.00, FALSE),
(7, 'Modern 2-Bedroom Condo', 'Recently renovated condo with stainless steel appliances', '987 Modern Way Unit 8B', 'Bronx', 'NY', '10451', 'condo', 2, 2.0, 1100, 2800.00, 5600.00, TRUE);

-- Insert sample leases
INSERT INTO LEASES (property_id, tenant_id, landlord_id, start_date, end_date, monthly_rent, security_deposit, status) VALUES
(1, 3, 1, '2023-06-01', '2024-05-31', 2500.00, 5000.00, 'active'),
(3, 4, 2, '2023-08-15', '2024-08-14', 3200.00, 6400.00, 'active'),
(5, 5, 7, '2023-09-01', '2024-08-31', 1900.00, 3800.00, 'active'),
(1, 6, 1, '2022-06-01', '2023-05-31', 2400.00, 4800.00, 'expired'),
(3, 8, 2, '2022-03-01', '2023-02-28', 3000.00, 6000.00, 'expired');

-- Insert sample rent payments
INSERT INTO RENT_PAYMENTS (lease_id, tenant_id, amount, payment_date, due_date, payment_method, status, late_fee) VALUES
-- Current lease payments
(1, 3, 2500.00, '2024-01-01', '2024-01-01', 'bank_transfer', 'paid', 0.00),
(1, 3, 2500.00, '2023-12-01', '2023-12-01', 'bank_transfer', 'paid', 0.00),
(1, 3, 2500.00, '2023-11-03', '2023-11-01', 'bank_transfer', 'paid', 50.00),
(2, 4, 3200.00, '2024-01-01', '2024-01-01', 'check', 'paid', 0.00),
(2, 4, 3200.00, '2023-12-01', '2023-12-01', 'check', 'paid', 0.00),
(3, 5, 1900.00, '2024-01-01', '2024-01-01', 'credit_card', 'paid', 0.00),
(3, 5, 1900.00, '2023-12-05', '2023-12-01', 'credit_card', 'paid', 75.00),
-- Upcoming payments
(1, 3, 2500.00, '2024-02-01', '2024-02-01', 'bank_transfer', 'pending', 0.00),
(2, 4, 3200.00, '2024-02-01', '2024-02-01', 'check', 'pending', 0.00),
(3, 5, 1900.00, '2024-02-01', '2024-02-01', 'credit_card', 'pending', 0.00);

-- Insert sample maintenance requests
INSERT INTO MAINTENANCE_REQUESTS (property_id, tenant_id, landlord_id, title, description, priority, status, requested_date, completed_date, cost) VALUES
(1, 3, 1, 'Leaky Kitchen Faucet', 'The kitchen faucet has been dripping constantly for the past week', 'medium', 'completed', '2024-01-10 09:30:00', '2024-01-12 14:20:00', 150.00),
(3, 4, 2, 'Broken Air Conditioning', 'AC unit in master bedroom is not cooling properly', 'high', 'in_progress', '2024-01-14 16:45:00', NULL, 0.00),
(5, 5, 7, 'Clogged Bathroom Drain', 'Bathroom sink drain is completely blocked', 'medium', 'pending', '2024-01-15 11:20:00', NULL, 0.00),
(1, 3, 1, 'Squeaky Door Hinges', 'Front door hinges are very squeaky and need lubrication', 'low', 'completed', '2024-01-05 08:15:00', '2024-01-06 10:30:00', 25.00),
(3, 4, 2, 'Broken Window Lock', 'Living room window lock is broken and window won\'t stay closed', 'high', 'completed', '2024-01-08 13:40:00', '2024-01-10 09:15:00', 85.00),
(5, 5, 7, 'Flickering Light Fixture', 'Dining room light fixture flickers intermittently', 'medium', 'pending', '2024-01-13 19:20:00', NULL, 0.00);

-- Create some useful views
CREATE VIEW active_leases AS
SELECT 
    l.lease_id,
    l.start_date,
    l.end_date,
    l.monthly_rent,
    CONCAT(t.first_name, ' ', t.last_name) as tenant_name,
    t.email as tenant_email,
    p.title as property_title,
    p.address as property_address,
    CONCAT(ll.first_name, ' ', ll.last_name) as landlord_name
FROM LEASES l
JOIN USERS t ON l.tenant_id = t.user_id
JOIN USERS ll ON l.landlord_id = ll.user_id
JOIN PROPERTIES p ON l.property_id = p.property_id
WHERE l.status = 'active';

CREATE VIEW payment_summary AS
SELECT 
    rp.payment_id,
    rp.amount,
    rp.payment_date,
    rp.due_date,
    rp.status,
    rp.late_fee,
    CONCAT(u.first_name, ' ', u.last_name) as tenant_name,
    p.title as property_title
FROM RENT_PAYMENTS rp
JOIN USERS u ON rp.tenant_id = u.user_id
JOIN LEASES l ON rp.lease_id = l.lease_id
JOIN PROPERTIES p ON l.property_id = p.property_id;

-- Display summary information
SELECT 'Database Setup Complete!' as Status;
SELECT COUNT(*) as Total_Users FROM USERS;
SELECT COUNT(*) as Total_Properties FROM PROPERTIES;
SELECT COUNT(*) as Total_Leases FROM LEASES;
SELECT COUNT(*) as Total_Payments FROM RENT_PAYMENTS;
SELECT COUNT(*) as Total_Maintenance_Requests FROM MAINTENANCE_REQUESTS;