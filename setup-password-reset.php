<?php
/**
 * Setup Password Reset Table
 * Run this file once to create the password_resets table
 * DELETE this file after running on production server
 */

include('includes/config.php');

echo "<h2>Setting up Password Reset Table</h2>";
echo "<pre>";

// Create password_resets table
$sql = "CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    expiry DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token),
    INDEX idx_user_id (user_id),
    INDEX idx_expiry (expiry)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql)) {
    echo "✅ password_resets table created successfully!\n\n";
} else {
    echo "❌ Error creating table: " . $conn->error . "\n\n";
}

// Show table structure
$result = $conn->query("DESCRIBE password_resets");
if ($result) {
    echo "Table Structure:\n";
    echo "----------------\n";
    while ($row = $result->fetch_assoc()) {
        echo sprintf("%-20s %-20s %-6s %-10s\n", 
            $row['Field'], 
            $row['Type'], 
            $row['Null'], 
            $row['Key']
        );
    }
}

echo "\n</pre>";
echo "<hr>";
echo "<p style='color: red; font-weight: bold;'>⚠️ IMPORTANT: Delete this file after running!</p>";
echo "<p><a href='forgot-password.php'>Test Forgot Password Page</a></p>";
echo "<p><a href='login.php'>Back to Login</a></p>";

$conn->close();
?>
