<?php
include('../includes/config.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$admin_id = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0);
if (!$admin_id) {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'update_profile') {
        $name = $conn->real_escape_string($_POST['name']);
        $email = $conn->real_escape_string($_POST['email']);
        $phone = $conn->real_escape_string($_POST['phone']);
        $address = $conn->real_escape_string($_POST['address']);
        
        // Check if email is already taken by another user
        $check = $conn->query("SELECT id FROM users WHERE email='$email' AND id != $admin_id");
        if ($check->num_rows > 0) {
            $error = "Email is already in use by another account";
        } else {
            $result = $conn->query("UPDATE users SET name='$name', email='$email', phone='$phone', address='$address' WHERE id=$admin_id");
            if ($result) {
                $_SESSION['user_name'] = $name;
                $message = "Profile updated successfully!";
            } else {
                $error = "Failed to update profile";
            }
        }
    }
    
    // Handle password change
    if ($_POST['action'] == 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify current password
        $user = $conn->query("SELECT password FROM users WHERE id=$admin_id")->fetch_assoc();
        
        if (!password_verify($current_password, $user['password'])) {
            $error = "Current password is incorrect";
        } elseif (strlen($new_password) < 6) {
            $error = "New password must be at least 6 characters";
        } elseif ($new_password != $confirm_password) {
            $error = "Passwords do not match";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            $result = $conn->query("UPDATE users SET password='$hashed_password' WHERE id=$admin_id");
            if ($result) {
                $message = "Password changed successfully!";
            } else {
                $error = "Failed to change password";
            }
        }
    }
}

// Fetch admin data
if (!$admin_id) {
    header("Location: login.php");
    exit();
}

$query = "SELECT * FROM users WHERE id=" . intval($admin_id);
$result = $conn->query($query);
if (!$result) {
    $error = "Database error: " . $conn->error;
    $admin = [];
} else {
    $admin = $result->fetch_assoc();
    if (!$admin) {
        header("Location: login.php");
        exit();
    }
}

// Get system statistics
$total_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='user'")->fetch_assoc()['count'];
$total_properties = $conn->query("SELECT COUNT(*) as count FROM properties")->fetch_assoc()['count'];
$total_inquiries = $conn->query("SELECT COUNT(*) as count FROM inquiries")->fetch_assoc()['count'];
$available_properties = $conn->query("SELECT COUNT(*) as count FROM properties WHERE status='available'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings - JD Realty Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f3f4f6;
            color: #1f2937;
        }
        
        .navbar {
            background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
            color: white;
            padding: 20px 40px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .logo img {
            width: 55px;
            height: 55px;
        }
        
        .nav-links {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.3s;
        }
        
        .nav-links a:hover {
            opacity: 0.8;
        }
        
        .logout-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white !important;
            border: 1px solid white;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }
        
        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white !important;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .page-header {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .page-header h1 {
            font-size: 28px;
            color: #1f2937;
        }
        
        .success-message {
            background-color: #dcfce7;
            color: #166534;
            padding: 12px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #22c55e;
        }
        
        .error-message {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 12px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #ef4444;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .section h2 {
            font-size: 20px;
            margin-bottom: 20px;
            color: #1f2937;
            padding-bottom: 15px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
        }
        
        input, textarea {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.3s;
        }
        
        input:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }
        
        button {
            padding: 12px 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            border-radius: 8px;
            color: white;
            text-align: center;
        }
        
        .stat-card h3 {
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .stat-card p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .tab-btn {
            padding: 12px 20px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-weight: 600;
            color: #6b7280;
            transition: all 0.3s;
        }
        
        .tab-btn.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .info-label {
            font-weight: 600;
            color: #6b7280;
        }
        
        .info-value {
            color: #1f2937;
        }
        
        .footer {
            text-align: center;
            color: #6b7280;
            margin-top: 40px;
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .button-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="logo" style="display: flex; align-items: center; gap: 10px;">
            <img src="../images/jd-logo.svg?v=20251203" alt="JD Realty" style="width: 40px; height: 40px;">
            <span style="color: #d4a84b; font-weight: bold;">JD Realty Investment</span>
            <span style="background: #ef4444; color: white; padding: 2px 8px; border-radius: 4px; font-size: 12px;">Admin</span>
        </div>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="manage-users.php">Users</a>
            <a href="manage-properties.php">Properties</a>
            <a href="manage-inquiries.php">Inquiries</a>
            <a href="dashboard.php?logout=true" class="logout-btn">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="page-header">
            <h1>⚙️ Admin Settings & Profile</h1>
        </div>
        
        <?php if ($message): ?>
            <div class="success-message"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="content-grid">
            <div>
                <!-- Tabs for profile and password -->
                <div class="section">
                    <div class="tabs">
                        <button class="tab-btn active" onclick="switchTab('profile')">Profile</button>
                        <button class="tab-btn" onclick="switchTab('password')">Change Password</button>
                    </div>
                    
                    <!-- Profile Tab -->
                    <div id="profile" class="tab-content active">
                        <h2>👤 My Profile</h2>
                        <form method="POST">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="form-group">
                                <label>Full Name</label>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($admin['name']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Email Address</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="tel" name="phone" value="<?php echo htmlspecialchars($admin['phone'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Address</label>
                                <textarea name="address" rows="3"><?php echo htmlspecialchars($admin['address'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="button-group">
                                <button type="submit">Save Changes</button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Password Tab -->
                    <div id="password" class="tab-content">
                        <h2>🔐 Change Password</h2>
                        <form method="POST">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="form-group">
                                <label>Current Password</label>
                                <input type="password" name="current_password" required>
                            </div>
                            
                            <div class="form-group">
                                <label>New Password</label>
                                <input type="password" name="new_password" required minlength="6">
                            </div>
                            
                            <div class="form-group">
                                <label>Confirm New Password</label>
                                <input type="password" name="confirm_password" required minlength="6">
                            </div>
                            
                            <div class="button-group">
                                <button type="submit">Update Password</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- System Statistics Sidebar -->
            <div>
                <div class="section">
                    <h2>📊 System Overview</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <h3><?php echo $total_users; ?></h3>
                            <p>Total Users</p>
                        </div>
                        <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                            <h3><?php echo $total_properties; ?></h3>
                            <p>Properties Listed</p>
                        </div>
                        <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                            <h3><?php echo $available_properties; ?></h3>
                            <p>Available Properties</p>
                        </div>
                        <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                            <h3><?php echo $total_inquiries; ?></h3>
                            <p>Inquiries</p>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Info -->
                <div class="section" style="margin-top: 20px;">
                    <h2>👤 Account Info</h2>
                    <div style="padding: 10px 0;">
                        <div class="info-row">
                            <span class="info-label">Role:</span>
                            <span class="info-value">Administrator</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Email:</span>
                            <span class="info-value"><?php echo htmlspecialchars($admin['email']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Joined:</span>
                            <span class="info-value"><?php echo date('M d, Y', strtotime($admin['created_at'])); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Last Updated:</span>
                            <span class="info-value"><?php echo date('M d, Y H:i', strtotime($admin['updated_at'])); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p>&copy; 2025 JD Realty & Investment. All rights reserved.</p>
        </div>
    </div>
    
    <script>
        function switchTab(tabName) {
            // Hide all tabs
            const tabs = document.querySelectorAll('.tab-content');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Deactivate all buttons
            const buttons = document.querySelectorAll('.tab-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            
            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            
            // Activate corresponding button
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
