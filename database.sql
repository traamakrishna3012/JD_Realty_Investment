-- JD Realty & Investment Database
-- Create Database
CREATE DATABASE IF NOT EXISTS a1764443_jd_realty;
USE a1764443_jd_realty;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
);

-- Properties Table
CREATE TABLE IF NOT EXISTS properties (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    property_type ENUM('residential', 'commercial', 'plot') NOT NULL,
    category VARCHAR(50),
    city VARCHAR(100) NOT NULL,
    address VARCHAR(500),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    price DECIMAL(15, 2),
    area_sqft DECIMAL(10, 2),
    bedrooms INT,
    bathrooms INT,
    furnishing ENUM('furnished', 'semi-furnished', 'unfurnished') DEFAULT 'unfurnished',
    parking INT DEFAULT 0,
    facing VARCHAR(50),
    floor_number INT,
    total_floors INT,
    age_of_property VARCHAR(50),
    amenities TEXT,
    image_url VARCHAR(255),
    status ENUM('available', 'sold', 'under_construction') DEFAULT 'available',
    approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_notes TEXT,
    listing_type ENUM('buy', 'rent') DEFAULT 'buy',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_city (city),
    INDEX idx_property_type (property_type),
    INDEX idx_status (status),
    INDEX idx_listing_type (listing_type)
);

-- Inquiries Table
CREATE TABLE IF NOT EXISTS inquiries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    property_id INT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    message TEXT,
    status ENUM('pending', 'replied', 'closed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    replied_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    INDEX idx_status (status)
);

-- Insert Sample Admin User
-- Email: admin@jdrealty.com
-- Password: Admin@123
INSERT INTO users (name, email, password, phone, role) VALUES
('Admin User', 'admin@jdrealty.com', '$2y$10$O4N0L1fN1x9L3K8R2Q5W5e3Q5R5T5V5V5V5V5V5V5V5V5V5V5V5V5', '9876543210', 'admin');

-- Insert Sample User
-- Email: user@example.com
-- Password: User@123
INSERT INTO users (name, email, password, phone, role) VALUES
('John Doe', 'user@example.com', '$2y$10$O4N0L1fN1x9L3K8R2Q5W5e3Q5R5T5V5V5V5V5V5V5V5V5V5V5V5', '9123456789', 'user');

-- Insert Sample Properties
INSERT INTO properties (title, description, property_type, category, city, price, area_sqft, bedrooms, bathrooms, status, created_by) VALUES
('Premium 2BHK Apartment', 'Luxurious 2 bedroom apartment in prime location', 'residential', '2bhk', 'Thane', 7500000, 950, 2, 2, 'available', 1),
('Commercial Shop Space', 'Ready to move commercial space in shopping complex', 'commercial', NULL, 'Thane', 5000000, 500, NULL, NULL, 'available', 1),
('Residential Plot', 'Spacious residential plot in upcoming area', 'plot', NULL, 'Mumbai', 8000000, 1500, NULL, NULL, 'available', 1);

-- Amenities Master Table
CREATE TABLE IF NOT EXISTS amenities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(50),
    category ENUM('basic', 'safety', 'convenience', 'recreation', 'luxury') DEFAULT 'basic',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category (category)
);

-- Property Amenities Junction Table
CREATE TABLE IF NOT EXISTS property_amenities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    property_id INT NOT NULL,
    amenity_id INT NOT NULL,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    FOREIGN KEY (amenity_id) REFERENCES amenities(id) ON DELETE CASCADE,
    UNIQUE KEY unique_property_amenity (property_id, amenity_id)
);

-- Property Images with Categories Table
CREATE TABLE IF NOT EXISTS property_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    property_id INT NOT NULL,
    image_url VARCHAR(500) NOT NULL,
    image_category ENUM('exterior', 'interior', 'bedroom', 'bathroom', 'kitchen', 'living_room', 'balcony', 'parking', 'amenities', 'floor_plan', 'other') DEFAULT 'other',
    caption VARCHAR(255),
    is_featured TINYINT(1) DEFAULT 0,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    INDEX idx_property (property_id),
    INDEX idx_category (image_category),
    INDEX idx_featured (is_featured)
);

-- Insert Default Amenities
INSERT INTO amenities (name, icon, category) VALUES
-- Basic Amenities
('Power Backup', '⚡', 'basic'),
('Lift/Elevator', '🛗', 'basic'),
('Water Supply 24x7', '💧', 'basic'),
('Gas Pipeline', '🔥', 'basic'),
('Sewage Treatment', '🚰', 'basic'),
('Rain Water Harvesting', '🌧️', 'basic'),
('Waste Disposal', '🗑️', 'basic'),
('Internet/Wi-Fi', '📶', 'basic'),

-- Safety Amenities
('Security Guard', '👮', 'safety'),
('CCTV Surveillance', '📹', 'safety'),
('Gated Community', '🚧', 'safety'),
('Fire Safety', '🧯', 'safety'),
('Intercom', '📞', 'safety'),
('Video Door Phone', '🚪', 'safety'),

-- Convenience Amenities
('Car Parking', '🚗', 'convenience'),
('Visitor Parking', '🅿️', 'convenience'),
('Shopping Center', '🛒', 'convenience'),
('ATM', '🏧', 'convenience'),
('Laundry Service', '🧺', 'convenience'),
('Maintenance Staff', '🔧', 'convenience'),
('Pet Friendly', '🐕', 'convenience'),

-- Recreation Amenities
('Swimming Pool', '🏊', 'recreation'),
('Gym/Fitness Center', '🏋️', 'recreation'),
('Children Play Area', '🎠', 'recreation'),
('Clubhouse', '🏛️', 'recreation'),
('Garden/Park', '🌳', 'recreation'),
('Jogging Track', '🏃', 'recreation'),
('Indoor Games', '🎯', 'recreation'),
('Tennis Court', '🎾', 'recreation'),
('Basketball Court', '🏀', 'recreation'),
('Badminton Court', '🏸', 'recreation'),

-- Luxury Amenities
('Spa/Sauna', '🧖', 'luxury'),
('Jacuzzi', '🛁', 'luxury'),
('Home Theater', '🎬', 'luxury'),
('Concierge Service', '🛎️', 'luxury'),
('Rooftop Garden', '🌺', 'luxury'),
('Private Terrace', '🏖️', 'luxury'),
('Wine Cellar', '🍷', 'luxury'),
('Smart Home Features', '🏠', 'luxury');

-- Add furnishing_status, possession_status, and landmark columns to properties if not exists
-- Note: Run these statements individually. If column already exists, MySQL will show an error which can be ignored.

-- Check and add furnishing_status column
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'properties' AND COLUMN_NAME = 'furnishing_status');
SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE properties ADD COLUMN furnishing_status ENUM(''unfurnished'', ''semi-furnished'', ''fully-furnished'') DEFAULT ''unfurnished''', 
    'SELECT ''Column furnishing_status already exists''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add possession_status column
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'properties' AND COLUMN_NAME = 'possession_status');
SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE properties ADD COLUMN possession_status ENUM(''ready_to_move'', ''under_construction'') DEFAULT ''ready_to_move''', 
    'SELECT ''Column possession_status already exists''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add landmark column
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'properties' AND COLUMN_NAME = 'landmark');
SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE properties ADD COLUMN landmark VARCHAR(255) AFTER address', 
    'SELECT ''Column landmark already exists''');
PREPARE stmt FROM @sql;
EXECUTE stmt;

-- Check and add status_remarks column for discussion notes
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'properties' AND COLUMN_NAME = 'status_remarks');
SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE properties ADD COLUMN status_remarks TEXT AFTER admin_notes', 
    'SELECT ''Column status_remarks already exists''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Modify status ENUM to include 'under_discussion' option
-- Note: This ALTER will update the column to include new status options
ALTER TABLE properties MODIFY COLUMN status ENUM('available', 'sold', 'under_construction', 'under_discussion', 'rented') DEFAULT 'available';
DEALLOCATE PREPARE stmt;
