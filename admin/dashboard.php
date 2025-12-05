<?php
include('../includes/config.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Fetch statistics - optimized with single query
$stats = $conn->query("SELECT 
    (SELECT COUNT(*) FROM users WHERE role='user') as total_users,
    (SELECT COUNT(*) FROM users WHERE role='admin') as total_admins,
    (SELECT COUNT(*) FROM properties) as total_properties,
    (SELECT COUNT(*) FROM inquiries) as total_inquiries
")->fetch_assoc();

$total_users = $stats['total_users'];
$total_admins = $stats['total_admins'];
$total_properties = $stats['total_properties'];
$total_inquiries = $stats['total_inquiries'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - JD Realty & Investment</title>
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
        
        .logo span {
            font-style: italic;
        }
        
        .nav-right {
            display: flex;
            align-items: center;
            gap: 30px;
        }
        
        .admin-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .admin-avatar {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .logout-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white !important;
            border: 1px solid white;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
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
        
        .welcome-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 40px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .welcome-section h1 {
            color: #1f2937;
            margin-bottom: 10px;
        }
        
        .welcome-section p {
            color: #6b7280;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-left: 5px solid #f5576c;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card.users {
            border-left-color: #667eea;
        }
        
        .stat-card.properties {
            border-left-color: #f093fb;
        }
        
        .stat-card.inquiries {
            border-left-color: #01baef;
        }
        
        .stat-card.admins {
            border-left-color: #f5576c;
        }
        
        .stat-label {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #1f2937;
        }
        
        .stat-icon {
            font-size: 30px;
            float: right;
            opacity: 0.3;
        }
        
        .action-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .action-section h2 {
            margin-bottom: 20px;
            color: #1f2937;
        }
        
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .action-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .action-btn.secondary {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .footer {
            text-align: center;
            color: #6b7280;
            font-size: 14px;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
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
        <div class="nav-right">
            <div class="admin-info">
                <div class="admin-avatar"><?php echo strtoupper(substr($_SESSION['admin_name'], 0, 1)); ?></div>
                <div>
                    <div style="font-size: 14px;"><?php echo $_SESSION['admin_name']; ?></div>
                    <div style="font-size: 12px; opacity: 0.8;">Administrator</div>
                </div>
            </div>
            <a href="?logout=true" class="logout-btn">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="welcome-section">
            <h1>Welcome back, <?php echo $_SESSION['admin_name']; ?>! 👋</h1>
            <p>Here's an overview of your property management system</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card users">
                <div class="stat-icon">👥</div>
                <div class="stat-label">Total Users</div>
                <div class="stat-number"><?php echo $total_users; ?></div>
            </div>
            
            <div class="stat-card properties">
                <div class="stat-icon">🏠</div>
                <div class="stat-label">Total Properties</div>
                <div class="stat-number"><?php echo $total_properties; ?></div>
            </div>
            
            <div class="stat-card inquiries">
                <div class="stat-icon">💬</div>
                <div class="stat-label">Total Inquiries</div>
                <div class="stat-number"><?php echo $total_inquiries; ?></div>
            </div>
            
            <div class="stat-card admins">
                <div class="stat-icon">⚙️</div>
                <div class="stat-label">Admin Accounts</div>
                <div class="stat-number"><?php echo $total_admins; ?></div>
            </div>
        </div>
        
        <div class="action-section">
            <h2>Quick Actions</h2>
            <div class="action-grid">
                <a href="manage-users.php" class="action-btn">👥 Manage Users</a>
                <a href="manage-properties.php" class="action-btn secondary">🏠 Manage Properties</a>
                <a href="manage-inquiries.php" class="action-btn">💬 View Inquiries</a>
                <a href="admin-settings.php" class="action-btn secondary">⚙️ Settings</a>
            </div>
        </div>
        
        <div class="action-section">
            <h2>📥 Export Data to Excel</h2>
            <div class="action-grid">
                <a href="export.php?type=users" class="action-btn" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">📊 Export Users</a>
                <a href="export.php?type=properties" class="action-btn" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">🏠 Export Properties</a>
                <a href="export.php?type=inquiries" class="action-btn" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">💬 Export Inquiries</a>
            </div>
        </div>
        
        <div class="footer">
            <p>&copy; 2025 JD Realty & Investment. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
