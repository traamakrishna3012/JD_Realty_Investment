<?php
include('../includes/config.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Handle delete user
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $user_id = intval($_GET['delete']);
    // Prevent admin from deleting themselves
    if ($user_id != $_SESSION['admin_id']) {
        $conn->query("DELETE FROM users WHERE id=$user_id");
        header("Location: manage-users.php?success=User deleted successfully");
        exit();
    }
}

// Fetch all users
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$where = $search ? "WHERE name LIKE '%$search%' OR email LIKE '%$search%'" : '';
$sql = "SELECT * FROM users $where ORDER BY created_at DESC";
$result = $conn->query($sql);
$users = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

$total_users = count($users);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - JD Realty Admin</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-header h1 {
            font-size: 28px;
            color: #1f2937;
        }
        
        .search-box {
            display: flex;
            gap: 10px;
        }
        
        .search-box input {
            padding: 10px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            font-size: 14px;
            width: 250px;
        }
        
        .search-box button {
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .search-box button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .success-message {
            background-color: #dcfce7;
            color: #166534;
            padding: 12px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #22c55e;
        }
        
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: #f9fafb;
            border-bottom: 2px solid #e5e7eb;
        }
        
        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #1f2937;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        tbody tr:hover {
            background: #f9fafb;
        }
        
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-admin {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .badge-user {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        .btn-view {
            padding: 6px 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-view:hover {
            background: #5568d3;
        }
        
        .btn-delete {
            padding: 6px 12px;
            background: #f5576c;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s ease;
        }
        
        .btn-delete:hover {
            background: #d63c47;
        }
        
        .btn-delete:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }
        
        .footer {
            text-align: center;
            color: #6b7280;
            margin-top: 40px;
            font-size: 14px;
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
            <h1>👥 Manage Users</h1>
            <div style="display: flex; gap: 15px; align-items: center;">
                <a href="export.php?type=users" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">📥 Export Excel</a>
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Search by name or email...">
                    <button onclick="document.location.href='manage-users.php?search='+document.getElementById('searchInput').value">Search</button>
                </div>
            </div>
        </div>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="success-message"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>
        
        <div class="table-container">
            <?php if (!empty($users)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge <?php echo $user['role'] == 'admin' ? 'badge-admin' : 'badge-user'; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="#" class="btn-view">View</a>
                                        <?php if ($user['id'] != $_SESSION['admin_id']): ?>
                                            <button class="btn-delete" onclick="if(confirm('Delete this user?')) location.href='manage-users.php?delete=<?php echo $user['id']; ?>'">Delete</button>
                                        <?php else: ?>
                                            <button class="btn-delete" disabled title="Cannot delete your own account">Delete</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No users found</h3>
                    <p>Try adjusting your search criteria</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div style="background: white; padding: 20px; border-radius: 10px; margin-top: 20px; text-align: center;">
            <p>Total Users: <strong><?php echo $total_users; ?></strong></p>
        </div>
        
        <div class="footer">
            <p>&copy; 2025 JD Realty & Investment. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
