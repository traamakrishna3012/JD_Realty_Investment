<?php
/**
 * Add phone_verified and otp columns to users table
 * Run this once, then DELETE this file!
 */

require_once 'includes/config.php';

echo "<h2>Adding OTP Columns to Users Table</h2><pre>";

// Check if otp column exists
$check = $conn->query("SHOW COLUMNS FROM users LIKE 'otp'");
if ($check && $check->num_rows > 0) {
    echo "✅ Column 'otp' already exists.\n";
} else {
    $sql = "ALTER TABLE users ADD COLUMN otp VARCHAR(6) DEFAULT NULL";
    if ($conn->query($sql)) {
        echo "✅ Column 'otp' added successfully!\n";
    } else {
        echo "❌ Error adding otp column: " . $conn->error . "\n";
    }
}

// Check if otp_expiry column exists
$check = $conn->query("SHOW COLUMNS FROM users LIKE 'otp_expiry'");
if ($check && $check->num_rows > 0) {
    echo "✅ Column 'otp_expiry' already exists.\n";
} else {
    $sql = "ALTER TABLE users ADD COLUMN otp_expiry DATETIME DEFAULT NULL";
    if ($conn->query($sql)) {
        echo "✅ Column 'otp_expiry' added successfully!\n";
    } else {
        echo "❌ Error adding otp_expiry column: " . $conn->error . "\n";
    }
}

// Check if phone_verified column exists
$check = $conn->query("SHOW COLUMNS FROM users LIKE 'phone_verified'");
if ($check && $check->num_rows > 0) {
    echo "✅ Column 'phone_verified' already exists.\n";
} else {
    $sql = "ALTER TABLE users ADD COLUMN phone_verified TINYINT(1) DEFAULT 0";
    if ($conn->query($sql)) {
        echo "✅ Column 'phone_verified' added successfully!\n";
    } else {
        echo "❌ Error adding phone_verified column: " . $conn->error . "\n";
    }
}

echo "\n</pre>";
echo "<p style='color: green; font-weight: bold;'>✅ Database update complete!</p>";
echo "<p style='color: red;'>⚠️ DELETE this file after running!</p>";
echo "<p><a href='login.php'>Go to Login</a></p>";

$conn->close();
?>
