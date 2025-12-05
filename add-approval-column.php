<?php
/**
 * Add approval_status column to properties table
 * Run this once, then DELETE this file!
 */

require_once 'includes/config.php';

echo "<h2>Adding Approval Status Column</h2><pre>";

// Check if column already exists
$check = $conn->query("SHOW COLUMNS FROM properties LIKE 'approval_status'");
if ($check && $check->num_rows > 0) {
    echo "✅ Column 'approval_status' already exists.\n";
} else {
    // Add the column
    $sql = "ALTER TABLE properties ADD COLUMN approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' AFTER status";
    if ($conn->query($sql)) {
        echo "✅ Column 'approval_status' added successfully!\n";
    } else {
        echo "❌ Error adding column: " . $conn->error . "\n";
    }
}

// Add admin_notes column for rejection reasons
$check = $conn->query("SHOW COLUMNS FROM properties LIKE 'admin_notes'");
if ($check && $check->num_rows > 0) {
    echo "✅ Column 'admin_notes' already exists.\n";
} else {
    $sql = "ALTER TABLE properties ADD COLUMN admin_notes TEXT AFTER approval_status";
    if ($conn->query($sql)) {
        echo "✅ Column 'admin_notes' added successfully!\n";
    } else {
        echo "❌ Error adding column: " . $conn->error . "\n";
    }
}

// Set existing properties to approved (so they continue showing)
$sql = "UPDATE properties SET approval_status = 'approved' WHERE approval_status IS NULL OR approval_status = 'pending'";
if ($conn->query($sql)) {
    $affected = $conn->affected_rows;
    echo "✅ Updated $affected existing properties to 'approved' status.\n";
} else {
    echo "❌ Error updating: " . $conn->error . "\n";
}

echo "\n</pre>";
echo "<p style='color: green; font-weight: bold;'>✅ Database update complete!</p>";
echo "<p style='color: red;'>⚠️ DELETE this file after running!</p>";
echo "<p><a href='index.php'>Go to Home</a> | <a href='admin/manage-properties.php'>Go to Admin</a></p>";

$conn->close();
?>
