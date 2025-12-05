<?php
/**
 * Database update script to add status_remarks column and update status ENUM
 * Run this once to update the database schema
 */
require_once 'includes/config.php';

echo "Updating database schema...\n";

// Add status_remarks column if not exists
$result1 = $conn->query("ALTER TABLE properties ADD COLUMN status_remarks TEXT AFTER admin_notes");
if ($result1) {
    echo "✅ Added status_remarks column\n";
} else {
    if (strpos($conn->error, 'Duplicate column') !== false) {
        echo "ℹ️ status_remarks column already exists\n";
    } else {
        echo "❌ Error adding column: " . $conn->error . "\n";
    }
}

// Modify status ENUM to include new options
$result2 = $conn->query("ALTER TABLE properties MODIFY COLUMN status ENUM('available', 'sold', 'under_construction', 'under_discussion', 'rented') DEFAULT 'available'");
if ($result2) {
    echo "✅ Updated status ENUM with 'under_discussion' and 'rented' options\n";
} else {
    echo "❌ Error modifying ENUM: " . $conn->error . "\n";
}

echo "\nDatabase update complete!\n";
echo "You can now delete this file.\n";
?>
