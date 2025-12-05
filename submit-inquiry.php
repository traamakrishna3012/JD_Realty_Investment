<?php
include('includes/config.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$name = isset($_POST['name']) ? $conn->real_escape_string(trim($_POST['name'])) : '';
$phone = isset($_POST['phone']) ? $conn->real_escape_string(trim($_POST['phone'])) : '';
$email = isset($_POST['email']) ? $conn->real_escape_string(trim($_POST['email'])) : '';
$message = isset($_POST['message']) ? $conn->real_escape_string(trim($_POST['message'])) : '';
$property_id = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;

// Validation
if (empty($name) || empty($phone)) {
    echo json_encode(['success' => false, 'message' => 'Name and phone number are required']);
    exit;
}

if (!preg_match('/^[0-9]{10}$/', $phone)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid 10-digit phone number']);
    exit;
}

if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
    exit;
}

// Get property details
$property_title = '';
if ($property_id > 0) {
    $prop_result = $conn->query("SELECT title FROM properties WHERE id = $property_id");
    if ($prop_result && $prop_result->num_rows > 0) {
        $property_title = $prop_result->fetch_assoc()['title'];
    }
}

// Check if inquiries table exists, if not create it
$conn->query("CREATE TABLE IF NOT EXISTS inquiries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(255),
    message TEXT,
    status ENUM('pending', 'replied', 'under_discussion', 'closed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (property_id),
    INDEX (status)
)");

// Insert inquiry
$sql = "INSERT INTO inquiries (property_id, name, phone, email, message) VALUES ($property_id, '$name', '$phone', '$email', '$message')";

if ($conn->query($sql)) {
    echo json_encode(['success' => true, 'message' => 'Inquiry submitted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit inquiry. Please try again.']);
}

$conn->close();
?>
