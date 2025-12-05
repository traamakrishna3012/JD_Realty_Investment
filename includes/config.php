<?php
// Start session FIRST before any output
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Error reporting - PRODUCTION MODE (errors hidden)
error_reporting(0);
ini_set('display_errors', 0);

// =============================================
// CACHE BUSTING VERSION - Update this when making changes
// =============================================
define('ASSET_VERSION', '20251203v2');

// Prevent browser caching during development
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// =============================================
// SITE CONFIGURATION
// =============================================
// Set to true for production (jdrealtyinvestment.com)
// Set to false for local development (localhost)
define('IS_PRODUCTION', true);

// Site URL Configuration
if (IS_PRODUCTION) {
    define('SITE_URL', 'https://jdrealtyinvestment.com');
    define('SITE_ROOT', '/');
} else {
    define('SITE_URL', 'http://localhost/jd-realty');
    define('SITE_ROOT', '/jd-realty/');
}

// =============================================
// DATABASE CONFIGURATION
// =============================================
// Load database credentials from environment variables
if (IS_PRODUCTION) {
    // Production Database - Load from environment variables
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    define('DB_USER', getenv('DB_USER'));
    define('DB_PASS', getenv('DB_PASS'));
    define('DB_NAME', getenv('DB_NAME'));
} else {
    // Local Development Database (XAMPP)
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'jd_realty');
}

// Create connection with optimization
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        error_log('Database Connection Error: ' . $conn->connect_error);
        die('<h2>Site Temporarily Unavailable</h2><p>Please try again later.</p>');
    }
    
    // Set charset to utf8mb4 for better Unicode support
    $conn->set_charset("utf8mb4");
    
    // Optimize MySQL connection
    $conn->query("SET SESSION sql_mode = ''");
    
} catch (Exception $e) {
    error_log('Database Exception: ' . $e->getMessage());
    die('<h2>Site Temporarily Unavailable</h2><p>Please try again later.</p>');
}

// =============================================
// HELPER FUNCTIONS
// =============================================
// Indian number format function (Lakhs, Crores) - centralized
function formatIndianPrice($number) {
    if ($number >= 10000000) {
        return '₹' . number_format($number / 10000000, 2) . ' Cr';
    } elseif ($number >= 100000) {
        return '₹' . number_format($number / 100000, 2) . ' Lac';
    }
    return '₹' . number_format($number, 0, '.', ',');
}

// Set cache headers for static content
function setCacheHeaders($days = 7) {
    $seconds = $days * 24 * 60 * 60;
    header("Cache-Control: public, max-age=$seconds");
    header("Expires: " . gmdate("D, d M Y H:i:s", time() + $seconds) . " GMT");
}