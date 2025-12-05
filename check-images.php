<?php
/**
 * Diagnose Image Display Issues
 * DELETE this file after debugging!
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Image Display Diagnostic</h2>";

// Include config to get SITE_URL
require_once 'includes/config.php';

echo "<pre>";
echo "=== Configuration ===\n";
echo "SITE_URL: " . SITE_URL . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Script Path: " . __FILE__ . "\n";
echo "Base Path: " . dirname(__FILE__) . "\n";

// Connect to database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "\n=== Recent Properties with Images ===\n";
$result = $conn->query("SELECT id, title, image_url FROM properties ORDER BY id DESC LIMIT 5");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "\nProperty ID: {$row['id']} - {$row['title']}\n";
        echo "  DB image_url: '{$row['image_url']}'\n";
        
        if (!empty($row['image_url'])) {
            $full_path = dirname(__FILE__) . '/' . $row['image_url'];
            echo "  Full path: $full_path\n";
            echo "  File exists: " . (file_exists($full_path) ? "YES ✅" : "NO ❌") . "\n";
            echo "  Web URL: " . SITE_URL . "/" . $row['image_url'] . "\n";
        } else {
            echo "  (No image set)\n";
        }
    }
}

echo "\n=== Property Images Table ===\n";
$result = $conn->query("SELECT pi.id, pi.property_id, pi.image_url, pi.image_category, p.title 
                        FROM property_images pi 
                        LEFT JOIN properties p ON pi.property_id = p.id 
                        ORDER BY pi.property_id DESC, pi.id DESC LIMIT 10");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "\nImage ID: {$row['id']} (Property: {$row['property_id']} - {$row['title']})\n";
        echo "  DB path: '{$row['image_url']}'\n";
        echo "  Category: {$row['image_category']}\n";
        
        $full_path = dirname(__FILE__) . '/' . $row['image_url'];
        echo "  File exists: " . (file_exists($full_path) ? "YES ✅" : "NO ❌") . "\n";
    }
} else {
    echo "No images in property_images table\n";
}

echo "\n=== Upload Directory Contents ===\n";
$upload_dir = dirname(__FILE__) . '/uploads/properties/';
echo "Upload dir: $upload_dir\n";
echo "Exists: " . (is_dir($upload_dir) ? "YES" : "NO") . "\n";

if (is_dir($upload_dir)) {
    $files = glob($upload_dir . '*.*');
    echo "File count: " . count($files) . "\n\n";
    
    if (count($files) > 0) {
        echo "Recent files:\n";
        // Sort by modification time
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        $count = 0;
        foreach ($files as $file) {
            if ($count++ >= 10) break;
            $relative = 'uploads/properties/' . basename($file);
            echo "  $relative (modified: " . date('Y-m-d H:i:s', filemtime($file)) . ")\n";
        }
    }
}

echo "</pre>";

echo "<h3>Test Image Display</h3>";

// Show actual images
$result = $conn->query("SELECT id, title, image_url FROM properties WHERE image_url IS NOT NULL AND image_url != '' ORDER BY id DESC LIMIT 3");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Property</th><th>Image Path</th><th>Image</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        $img_url = SITE_URL . '/' . $row['image_url'];
        echo "<tr>";
        echo "<td>ID: {$row['id']}<br>{$row['title']}</td>";
        echo "<td>{$row['image_url']}<br><small>$img_url</small></td>";
        echo "<td><img src='{$row['image_url']}' style='max-width:200px;max-height:150px;' onerror=\"this.src='images/jd-logo.svg'; this.style.background='#fee';\"></td>";
        echo "</tr>";
    }
    echo "</table>";
}

$conn->close();

echo "<hr><p style='color:red;'><strong>⚠️ DELETE this file after debugging!</strong></p>";
?>
