<?php
// Database Setup Script
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'jd_realty');

// Create connection without specifying database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Read and execute SQL file
$sql = file_get_contents('database.sql');
$queries = array_filter(array_map('trim', preg_split('/;[\r\n]+/', $sql)));

$errors = [];
$success = [];

foreach ($queries as $query) {
    if (!empty($query)) {
        if ($conn->multi_query($query)) {
            $success[] = "Query executed successfully";
            while ($conn->next_result());
        } else {
            $errors[] = "Error: " . $conn->error;
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Setup</title>
    <style>
        body { font-family: Arial; margin: 50px; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>Database Setup</h1>
    <?php if (!empty($success)): ?>
        <div class="success">
            <h2>✓ Database setup completed successfully!</h2>
            <p>The database tables have been created and sample data has been inserted.</p>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="error">
            <h2>✗ Errors occurred:</h2>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</body>
</html>
