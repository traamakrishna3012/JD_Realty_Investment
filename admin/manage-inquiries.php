<?php
include('../includes/config.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Handle status update
if (isset($_GET['mark']) && isset($_GET['inquiry_id'])) {
    $inquiry_id = intval($_GET['inquiry_id']);
    $mark = $conn->real_escape_string($_GET['mark']);
    if ($mark == 'replied') {
        $conn->query("UPDATE inquiries SET status='replied', replied_at=NOW() WHERE id=$inquiry_id");
    } else if ($mark == 'under_discussion') {
        $conn->query("UPDATE inquiries SET status='under_discussion' WHERE id=$inquiry_id");
    } else if ($mark == 'closed') {
        $conn->query("UPDATE inquiries SET status='closed' WHERE id=$inquiry_id");
    }
    header("Location: manage-inquiries.php?success=Inquiry updated successfully");
    exit();
}

// Handle close with remarks
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['close_with_remarks'])) {
    $inquiry_id = intval($_POST['inquiry_id']);
    $remarks = $conn->real_escape_string($_POST['remarks']);
    $conn->query("UPDATE inquiries SET status='closed', remarks='$remarks' WHERE id=$inquiry_id");
    header("Location: manage-inquiries.php?success=Inquiry closed with remarks");
    exit();
}

// Handle delete inquiry
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $inquiry_id = intval($_GET['delete']);
    $conn->query("DELETE FROM inquiries WHERE id=$inquiry_id");
    header("Location: manage-inquiries.php?success=Inquiry deleted successfully");
    exit();
}

// Fetch all inquiries with details
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$where = $status_filter ? "WHERE inquiries.status='$status_filter'" : '';
$sql = "SELECT inquiries.*, properties.id as property_id, properties.title as property_title, 
        users.name as user_name, users.phone as user_phone 
        FROM inquiries 
        LEFT JOIN properties ON inquiries.property_id = properties.id 
        LEFT JOIN users ON inquiries.user_id = users.id 
        $where 
        ORDER BY inquiries.created_at DESC";
$result = $conn->query($sql);
$inquiries = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $inquiries[] = $row;
    }
}

// Get counts by status
$pending_count = $conn->query("SELECT COUNT(*) as count FROM inquiries WHERE status='pending'")->fetch_assoc()['count'];
$replied_count = $conn->query("SELECT COUNT(*) as count FROM inquiries WHERE status='replied'")->fetch_assoc()['count'];
$under_discussion_count = $conn->query("SELECT COUNT(*) as count FROM inquiries WHERE status='under_discussion'")->fetch_assoc()['count'];
$closed_count = $conn->query("SELECT COUNT(*) as count FROM inquiries WHERE status='closed'")->fetch_assoc()['count'];
$total_inquiries = $pending_count + $replied_count + $under_discussion_count + $closed_count;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Inquiries - JD Realty Admin</title>
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
            max-width: 1400px;
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
            margin-bottom: 20px;
        }
        
        .filter-tabs {
            display: flex;
            gap: 10px;
        }
        
        .tab-btn {
            padding: 10px 20px;
            background: #f3f4f6;
            border: 2px solid transparent;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            color: #6b7280;
        }
        
        .tab-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
        }
        
        .tab-btn:hover {
            border-color: #667eea;
            color: #667eea;
        }
        
        .success-message {
            background-color: #dcfce7;
            color: #166534;
            padding: 12px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #22c55e;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            text-align: center;
        }
        
        .stat-label {
            color: #6b7280;
            font-size: 12px;
            margin-bottom: 10px;
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: #1f2937;
        }
        
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow-x: auto;
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
        
        .badge-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-replied {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .badge-closed {
            background: #dcfce7;
            color: #166534;
        }
        
        .badge-under_discussion {
            background: #ede9fe;
            color: #6d28d9;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .btn-small {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-mark {
            background: #01baef;
            color: white;
        }
        
        .btn-mark:hover {
            background: #009ec8;
        }
        
        .btn-discussion {
            background: #8b5cf6;
            color: white;
        }
        
        .btn-discussion:hover {
            background: #7c3aed;
        }
        
        .btn-close {
            background: #10b981;
            color: white;
        }
        
        .btn-close:hover {
            background: #059669;
        }
        
        .btn-delete {
            background: #f5576c;
            color: white;
        }
        
        .btn-delete:hover {
            background: #d63c47;
        }
        
        /* Modal styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-overlay.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 400px;
        }
        
        .modal-content h3 {
            margin-bottom: 20px;
            color: #1f2937;
        }
        
        .modal-content textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            resize: vertical;
            min-height: 100px;
            font-family: inherit;
        }
        
        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }
        
        .modal-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .modal-btn-cancel {
            background: #f3f4f6;
            color: #1f2937;
        }
        
        .modal-btn-submit {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
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
            <div style="display: flex; align-items: center; gap: 20px;">
                <h1>💬 Manage Inquiries</h1>
                <a href="export.php?type=inquiries" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">📥 Export Excel</a>
            </div>
            <div class="filter-tabs">
                <a href="manage-inquiries.php" class="tab-btn <?php echo !$status_filter ? 'active' : ''; ?>">All (<?php echo $total_inquiries; ?>)</a>
                <a href="manage-inquiries.php?status=pending" class="tab-btn <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">Pending (<?php echo $pending_count; ?>)</a>
                <a href="manage-inquiries.php?status=replied" class="tab-btn <?php echo $status_filter == 'replied' ? 'active' : ''; ?>">Replied (<?php echo $replied_count; ?>)</a>
                <a href="manage-inquiries.php?status=under_discussion" class="tab-btn <?php echo $status_filter == 'under_discussion' ? 'active' : ''; ?>">Under Discussion (<?php echo $under_discussion_count; ?>)</a>
                <a href="manage-inquiries.php?status=closed" class="tab-btn <?php echo $status_filter == 'closed' ? 'active' : ''; ?>">Closed (<?php echo $closed_count; ?>)</a>
            </div>
        </div>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="success-message"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Inquiries</div>
                <div class="stat-number"><?php echo $total_inquiries; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Pending</div>
                <div class="stat-number" style="color: #f59e0b;"><?php echo $pending_count; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Replied</div>
                <div class="stat-number" style="color: #3b82f6;"><?php echo $replied_count; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Under Discussion</div>
                <div class="stat-number" style="color: #8b5cf6;"><?php echo $under_discussion_count; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Closed</div>
                <div class="stat-number" style="color: #10b981;"><?php echo $closed_count; ?></div>
            </div>
        </div>
        
        <div class="table-container">
            <?php if (!empty($inquiries)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>User Phone</th>
                            <th>Property (ID)</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Remarks</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inquiries as $inq): ?>
                            <tr>
                                <td><?php echo $inq['id']; ?></td>
                                <td><?php echo htmlspecialchars($inq['name']); ?></td>
                                <td><?php echo htmlspecialchars($inq['email']); ?></td>
                                <td><?php echo htmlspecialchars($inq['phone'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($inq['user_phone'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php if ($inq['property_id']): ?>
                                        <a href="../property-details.php?id=<?php echo $inq['property_id']; ?>" target="_blank" style="color: #667eea; text-decoration: none;">
                                            <?php echo htmlspecialchars(substr($inq['property_title'] ?? 'N/A', 0, 20)); ?>
                                            <br><small style="color: #6b7280;">#<?php echo $inq['property_id']; ?></small>
                                        </a>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td title="<?php echo htmlspecialchars($inq['message']); ?>"><?php echo htmlspecialchars(substr($inq['message'], 0, 30)) . '...'; ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $inq['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $inq['status'])); ?>
                                    </span>
                                </td>
                                <td style="max-width: 200px; word-wrap: break-word;">
                                    <?php if (!empty($inq['remarks'])): ?>
                                        <div style="background: #f0fdf4; padding: 8px; border-radius: 6px; border-left: 3px solid #22c55e;">
                                            <span style="color: #166534; font-size: 12px;"><?php echo htmlspecialchars($inq['remarks']); ?></span>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: #9ca3af; font-size: 12px;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($inq['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($inq['status'] != 'replied'): ?>
                                            <a href="manage-inquiries.php?mark=replied&inquiry_id=<?php echo $inq['id']; ?>" class="btn-small btn-mark">Mark Replied</a>
                                        <?php endif; ?>
                                        <?php if ($inq['status'] != 'under_discussion'): ?>
                                            <a href="manage-inquiries.php?mark=under_discussion&inquiry_id=<?php echo $inq['id']; ?>" class="btn-small btn-discussion">Under Discussion</a>
                                        <?php endif; ?>
                                        <?php if ($inq['status'] != 'closed'): ?>
                                            <button class="btn-small btn-close" onclick="openRemarksModal(<?php echo $inq['id']; ?>)">Close</button>
                                        <?php endif; ?>
                                        <button class="btn-small btn-delete" onclick="if(confirm('Delete this inquiry?')) location.href='manage-inquiries.php?delete=<?php echo $inq['id']; ?>'">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No inquiries found</h3>
                    <p>All inquiries for this status have been handled</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="footer">
            <p>&copy; 2025 JD Realty & Investment. All rights reserved.</p>
        </div>
    </div>
    
    <!-- Remarks Modal -->
    <div class="modal-overlay" id="remarksModal">
        <div class="modal-content">
            <h3>Close Inquiry with Remarks</h3>
            <form method="POST" action="manage-inquiries.php">
                <input type="hidden" name="close_with_remarks" value="1">
                <input type="hidden" name="inquiry_id" id="modal_inquiry_id">
                <textarea name="remarks" placeholder="Enter closing remarks (reason for closure, outcome, etc.)..." required></textarea>
                <div class="modal-buttons">
                    <button type="button" class="modal-btn modal-btn-cancel" onclick="closeRemarksModal()">Cancel</button>
                    <button type="submit" class="modal-btn modal-btn-submit">Close Inquiry</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openRemarksModal(inquiryId) {
            document.getElementById('modal_inquiry_id').value = inquiryId;
            document.getElementById('remarksModal').classList.add('active');
        }
        
        function closeRemarksModal() {
            document.getElementById('remarksModal').classList.remove('active');
        }
        
        // Close modal on outside click
        document.getElementById('remarksModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeRemarksModal();
            }
        });
    </script>
</body>
</html>
