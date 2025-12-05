<?php
include('../includes/config.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Handle delete property
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $property_id = intval($_GET['delete']);
    $prop = $conn->query("SELECT image_url FROM properties WHERE id=$property_id LIMIT 1")->fetch_assoc();
    if ($prop && !empty($prop['image_url']) && file_exists($prop['image_url'])) {
        unlink($prop['image_url']);
    }
    $conn->query("DELETE FROM properties WHERE id=$property_id");
    header("Location: manage-properties.php?success=Property deleted successfully");
    exit();
}

// Handle copy/duplicate property
if (isset($_GET['copy']) && !empty($_GET['copy'])) {
    $original_id = intval($_GET['copy']);
    
    // Fetch original property
    $original = $conn->query("SELECT * FROM properties WHERE id=$original_id")->fetch_assoc();
    
    if ($original) {
        // Create copy with 'Copy of' prefix
        $new_title = "Copy of " . $original['title'];
        $new_title_escaped = $conn->real_escape_string($new_title);
        $desc_escaped = $conn->real_escape_string($original['description']);
        $property_type = $original['property_type'];
        $category = $original['category'];
        $city = $conn->real_escape_string($original['city']);
        $address = isset($original['address']) ? $conn->real_escape_string($original['address']) : '';
        
        $copy_sql = "INSERT INTO properties (title, building_name, description, property_type, category, city, address, latitude, longitude, price, area_sqft, bedrooms, bathrooms, furnishing_status, possession_status, total_floors, floor_number, age_of_property, image_url, created_by, status, approval_status, pre_lease, possession_date, workstations, cabins, conference_rooms, meeting_rooms, pantry)
                    VALUES ('$new_title_escaped', " . (isset($original['building_name']) && $original['building_name'] ? "'" . $conn->real_escape_string($original['building_name']) . "'" : "NULL") . ", '$desc_escaped', '$property_type', " . ($category ? "'$category'" : "NULL") . ", '$city', " . ($address ? "'$address'" : "NULL") . ", {$original['latitude']}, {$original['longitude']}, {$original['price']}, {$original['area_sqft']}, " . ($original['bedrooms'] ? $original['bedrooms'] : "NULL") . ", " . ($original['bathrooms'] ? $original['bathrooms'] : "NULL") . ", '{$original['furnishing_status']}', '{$original['possession_status']}', " . ($original['total_floors'] ? $original['total_floors'] : "NULL") . ", " . ($original['floor_number'] ? $original['floor_number'] : "NULL") . ", " . ($original['age_of_property'] ? "'{$original['age_of_property']}'" : "NULL") . ", NULL, {$original['created_by']}, 'available', 'pending', '{$original['pre_lease']}', " . ($original['possession_date'] ? "'{$original['possession_date']}'" : "NULL") . ", {$original['workstations']}, {$original['cabins']}, {$original['conference_rooms']}, {$original['meeting_rooms']}, '{$original['pantry']}')";
        
        if ($conn->query($copy_sql) === TRUE) {
            $new_property_id = $conn->insert_id;
            
            // Copy amenities
            $amenities_result = $conn->query("SELECT amenity_id FROM property_amenities WHERE property_id=$original_id");
            while ($amenity = $amenities_result->fetch_assoc()) {
                $conn->query("INSERT INTO property_amenities (property_id, amenity_id) VALUES ($new_property_id, {$amenity['amenity_id']})");
            }
            
            header("Location: manage-properties.php?success=Property copied successfully! New Property ID: $new_property_id");
        } else {
            header("Location: manage-properties.php?error=Failed to copy property");
        }
        exit();
    } else {
        header("Location: manage-properties.php?error=Property not found");
        exit();
    }
}

// Handle status update
if (isset($_GET['status_update']) && isset($_GET['property_id'])) {
    $property_id = intval($_GET['property_id']);
    $new_status = $conn->real_escape_string($_GET['status_update']);
    $allowed_statuses = ['available', 'sold', 'under_construction', 'under_discussion', 'rented'];
    if (in_array($new_status, $allowed_statuses)) {
        $conn->query("UPDATE properties SET status='$new_status' WHERE id=$property_id");
        header("Location: manage-properties.php?success=Property status updated to " . ucwords(str_replace('_', ' ', $new_status)));
        exit();
    }
}

// Handle status remarks update
if (isset($_POST['update_remarks']) && isset($_POST['property_id'])) {
    $property_id = intval($_POST['property_id']);
    $remarks = $conn->real_escape_string($_POST['status_remarks']);
    $conn->query("UPDATE properties SET status_remarks='$remarks' WHERE id=$property_id");
    header("Location: manage-properties.php?success=Property remarks updated");
    exit();
}

// Handle approval status update
if (isset($_GET['approval']) && isset($_GET['property_id'])) {
    $property_id = intval($_GET['property_id']);
    $approval = $conn->real_escape_string($_GET['approval']);
    $notes = isset($_GET['notes']) ? $conn->real_escape_string($_GET['notes']) : '';
    
    if (in_array($approval, ['approved', 'rejected', 'pending'])) {
        $conn->query("UPDATE properties SET approval_status='$approval', admin_notes='$notes' WHERE id=$property_id");
        $msg = $approval == 'approved' ? 'Property approved and now visible on site' : ($approval == 'rejected' ? 'Property rejected' : 'Property set to pending');
        header("Location: manage-properties.php?success=$msg");
        exit();
    }
}

// Handle deactivation with reason (POST)
if (isset($_POST['deactivate_property']) && isset($_POST['property_id'])) {
    $property_id = intval($_POST['property_id']);
    $deactivation_reason = $conn->real_escape_string($_POST['deactivation_reason']);
    $conn->query("UPDATE properties SET is_active=0, deactivated_at=NOW(), deactivation_reason='$deactivation_reason' WHERE id=$property_id");
    header("Location: manage-properties.php?success=Property deactivated successfully");
    exit();
}

// Handle deactivation toggle (admin can also deactivate/reactivate)
if (isset($_GET['toggle_active']) && isset($_GET['property_id'])) {
    $property_id = intval($_GET['property_id']);
    $prop_check = $conn->query("SELECT is_active FROM properties WHERE id=$property_id")->fetch_assoc();
    if ($prop_check) {
        if ($prop_check['is_active']) {
            $conn->query("UPDATE properties SET is_active=0, deactivated_at=NOW(), deactivation_reason='Deactivated by admin' WHERE id=$property_id");
            header("Location: manage-properties.php?success=Property deactivated");
        } else {
            $conn->query("UPDATE properties SET is_active=1, deactivated_at=NULL, deactivation_reason=NULL WHERE id=$property_id");
            header("Location: manage-properties.php?success=Property reactivated");
        }
        exit();
    }
}

// Filter by approval status
$filter = isset($_GET['filter']) ? $conn->real_escape_string($_GET['filter']) : 'all';
$active_filter = isset($_GET['active']) ? $conn->real_escape_string($_GET['active']) : 'all';

// Fetch all properties with owner info
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$where_parts = [];
if ($search) {
    $where_parts[] = "(properties.title LIKE '%$search%' OR properties.city LIKE '%$search%')";
}
if ($filter != 'all') {
    $where_parts[] = "properties.approval_status = '$filter'";
}
if ($active_filter == 'active') {
    $where_parts[] = "(properties.is_active = 1 OR properties.is_active IS NULL)";
} elseif ($active_filter == 'inactive') {
    $where_parts[] = "properties.is_active = 0";
}
$where = !empty($where_parts) ? "WHERE " . implode(' AND ', $where_parts) : '';

$sql = "SELECT properties.*, users.name as owner_name, users.email as owner_email FROM properties 
        LEFT JOIN users ON properties.created_by = users.id 
        $where ORDER BY 
        CASE WHEN properties.approval_status = 'pending' THEN 0 ELSE 1 END,
        properties.created_at DESC";
$result = $conn->query($sql);
$properties = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $properties[] = $row;
    }
}

// Get counts for each status
$pending_count = $conn->query("SELECT COUNT(*) as cnt FROM properties WHERE approval_status='pending'")->fetch_assoc()['cnt'] ?? 0;
$approved_count = $conn->query("SELECT COUNT(*) as cnt FROM properties WHERE approval_status='approved'")->fetch_assoc()['cnt'] ?? 0;
$rejected_count = $conn->query("SELECT COUNT(*) as cnt FROM properties WHERE approval_status='rejected'")->fetch_assoc()['cnt'] ?? 0;
$inactive_count = $conn->query("SELECT COUNT(*) as cnt FROM properties WHERE is_active = 0")->fetch_assoc()['cnt'] ?? 0;
$total_properties = count($properties);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Properties - JD Realty Admin</title>
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
        
        .badge-available {
            background: #dcfce7;
            color: #166534;
        }
        
        .badge-sold {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .badge-construction {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-approved {
            background: #dcfce7;
            color: #166534;
        }
        
        .badge-rejected {
            background: #fee2e2;
            color: #991b1b;
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
        
        .btn-view {
            background: #667eea;
            color: white;
        }
        
        .btn-view:hover {
            background: #5568d3;
        }
        
        .btn-edit {
            background: #01baef;
            color: white;
        }
        
        .btn-edit:hover {
            background: #009ec8;
        }
        
        .btn-delete {
            background: #f5576c;
            color: white;
        }
        
        .btn-delete:hover {
            background: #d63c47;
        }
        
        .btn-approve {
            background: #22c55e;
            color: white;
        }
        
        .btn-approve:hover {
            background: #16a34a;
        }
        
        .btn-reject {
            background: #ef4444;
            color: white;
        }
        
        .btn-reject:hover {
            background: #dc2626;
        }
        
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .filter-tab {
            padding: 10px 20px;
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            color: #1f2937;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .filter-tab:hover {
            border-color: #667eea;
            color: #667eea;
        }
        
        .filter-tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
        }
        
        .filter-tab .count {
            background: rgba(0,0,0,0.1);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
            margin-left: 5px;
        }
        
        .filter-tab.active .count {
            background: rgba(255,255,255,0.3);
        }
        
        .pending-highlight {
            background: #fef3c7 !important;
        }
        
        .stats-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            flex: 1;
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .stat-card h3 {
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .stat-card.pending h3 { color: #f59e0b; }
        .stat-card.approved h3 { color: #22c55e; }
        .stat-card.rejected h3 { color: #ef4444; }
        
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
        
        .stat-row {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
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
            <h1>🏠 Manage Properties</h1>
            <div style="display: flex; gap: 15px; align-items: center;">
                <a href="export.php?type=properties" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">📥 Export Excel</a>
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Search by title or city..." value="<?php echo htmlspecialchars($search); ?>">
                    <button onclick="document.location.href='manage-properties.php?search='+document.getElementById('searchInput').value+'&filter=<?php echo $filter; ?>'">Search</button>
                </div>
            </div>
        </div>
        
        <!-- Stats Row -->
        <div class="stats-row">
            <div class="stat-card pending">
                <h3><?php echo $pending_count; ?></h3>
                <p>⏳ Pending Approval</p>
            </div>
            <div class="stat-card approved">
                <h3><?php echo $approved_count; ?></h3>
                <p>✅ Approved</p>
            </div>
            <div class="stat-card rejected">
                <h3><?php echo $rejected_count; ?></h3>
                <p>❌ Rejected</p>
            </div>
        </div>
        
        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <a href="manage-properties.php?filter=all" class="filter-tab <?php echo $filter == 'all' && $active_filter == 'all' ? 'active' : ''; ?>">All <span class="count"><?php echo $pending_count + $approved_count + $rejected_count; ?></span></a>
            <a href="manage-properties.php?filter=pending" class="filter-tab <?php echo $filter == 'pending' ? 'active' : ''; ?>">⏳ Pending <span class="count"><?php echo $pending_count; ?></span></a>
            <a href="manage-properties.php?filter=approved" class="filter-tab <?php echo $filter == 'approved' ? 'active' : ''; ?>">✅ Approved <span class="count"><?php echo $approved_count; ?></span></a>
            <a href="manage-properties.php?filter=rejected" class="filter-tab <?php echo $filter == 'rejected' ? 'active' : ''; ?>">❌ Rejected <span class="count"><?php echo $rejected_count; ?></span></a>
            <a href="manage-properties.php?active=inactive" class="filter-tab <?php echo $active_filter == 'inactive' ? 'active' : ''; ?>" style="margin-left: 20px; background: #fee2e2; color: #991b1b;">⏸ Inactive <span class="count"><?php echo $inactive_count; ?></span></a>
        </div>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="success-message"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>
        
        <div class="table-container">
            <?php if (!empty($properties)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>City</th>
                            <th>Price</th>
                            <th>Owner</th>
                            <th>Status</th>
                            <th>Approval</th>
                            <th>Created</th>
                            <th>Active</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($properties as $prop): 
                            $approval_status = $prop['approval_status'] ?? 'pending';
                            $is_pending = $approval_status == 'pending';
                            $is_active = $prop['is_active'] ?? 1;
                        ?>
                            <tr class="<?php echo $is_pending ? 'pending-highlight' : ''; ?>" style="<?php echo !$is_active ? 'opacity: 0.6; background: #f3f4f6;' : ''; ?>">
                                <td><?php echo $prop['id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars(substr($prop['title'], 0, 30)); ?>
                                    <?php if (!$is_active): ?>
                                        <br><small style="color: #991b1b;">⚠️ Deactivated</small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo ucfirst($prop['property_type']); ?></td>
                                <td><?php echo htmlspecialchars($prop['city']); ?></td>
                                <td><?php echo formatIndianPrice($prop['price']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($prop['owner_name'] ?? 'N/A'); ?>
                                    <?php if (!empty($prop['owner_email'])): ?>
                                        <br><small style="color:#6b7280;"><?php echo htmlspecialchars($prop['owner_email']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <select onchange="location.href='manage-properties.php?status_update='+this.value+'&property_id=<?php echo $prop['id']; ?>'" style="padding: 5px 8px; border-radius: 4px; border: 1px solid #d1d5db; font-size: 12px; cursor: pointer; background: white;">
                                        <option value="available" <?php echo $prop['status'] == 'available' ? 'selected' : ''; ?>>🟢 Available</option>
                                        <option value="under_discussion" <?php echo $prop['status'] == 'under_discussion' ? 'selected' : ''; ?>>💬 Under Discussion</option>
                                        <option value="sold" <?php echo $prop['status'] == 'sold' ? 'selected' : ''; ?>>🔴 Sold</option>
                                        <option value="rented" <?php echo $prop['status'] == 'rented' ? 'selected' : ''; ?>>🏠 Rented</option>
                                        <option value="under_construction" <?php echo $prop['status'] == 'under_construction' ? 'selected' : ''; ?>>🔨 Under Construction</option>
                                    </select>
                                    <?php if (!empty($prop['status_remarks'])): ?>
                                        <div style="background: #fef3c7; padding: 8px; border-radius: 6px; border-left: 3px solid #f59e0b; margin-top: 8px; max-width: 200px; word-wrap: break-word;">
                                            <span style="color: #92400e; font-size: 12px;">📝 <?php echo htmlspecialchars($prop['status_remarks']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $approval_status; ?>">
                                        <?php 
                                        if ($approval_status == 'pending') echo '⏳ Pending';
                                        elseif ($approval_status == 'approved') echo '✅ Approved';
                                        else echo '❌ Rejected';
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($is_active): ?>
                                        <span class="badge badge-approved" style="background:#dcfce7;color:#166534;">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-rejected" style="background:#fee2e2;color:#991b1b;">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($prop['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="../property-details.php?id=<?php echo $prop['id']; ?>" class="btn-small btn-view" target="_blank">View</a>
                                        <a href="../edit-property.php?id=<?php echo $prop['id']; ?>&admin=1" class="btn-small btn-edit">Edit</a>
                                        <button class="btn-small" style="background:#fef3c7;color:#92400e;" onclick="showRemarksModal(<?php echo $prop['id']; ?>, '<?php echo addslashes($prop['status_remarks'] ?? ''); ?>')">📝 Remarks</button>
                                        <?php if ($approval_status != 'approved'): ?>
                                            <button class="btn-small btn-approve" onclick="if(confirm('Approve this property? It will be visible on the main site.')) location.href='manage-properties.php?approval=approved&property_id=<?php echo $prop['id']; ?>'">✓ Approve</button>
                                        <?php endif; ?>
                                        <?php if ($approval_status != 'rejected'): ?>
                                            <button class="btn-small btn-reject" onclick="var reason = prompt('Reason for rejection (optional):'); if(reason !== null) location.href='manage-properties.php?approval=rejected&property_id=<?php echo $prop['id']; ?>&notes='+encodeURIComponent(reason)">✗ Reject</button>
                                        <?php endif; ?>
                                        <?php if ($approval_status == 'rejected'): ?>
                                            <button class="btn-small btn-approve" onclick="location.href='manage-properties.php?approval=pending&property_id=<?php echo $prop['id']; ?>'">↻ Re-review</button>
                                        <?php endif; ?>
                                        <?php if ($is_active): ?>
                                        <button type="button" class="btn-small btn-reject" onclick="showDeactivateModal(<?php echo $prop['id']; ?>, '<?php echo addslashes($prop['title']); ?>')">
                                            ⏸ Deactivate
                                        </button>
                                        <?php else: ?>
                                        <a href="manage-properties.php?toggle_active=1&property_id=<?php echo $prop['id']; ?>" class="btn-small btn-approve" onclick="return confirm('Reactivate this property?')">
                                            ▶ Reactivate
                                        </a>
                                        <span style="display:block;font-size:10px;color:#991b1b;margin-top:3px;">Deactivated: <?php echo !empty($prop['deactivated_at']) ? date('M d, Y', strtotime($prop['deactivated_at'])) : 'N/A'; ?></span>
                                        <?php if (!empty($prop['deactivation_reason'])): ?>
                                        <span style="display:block;font-size:10px;color:#6b7280;" title="<?php echo htmlspecialchars($prop['deactivation_reason']); ?>">📝 <?php echo htmlspecialchars(substr($prop['deactivation_reason'], 0, 20)); ?>...</span>
                                        <?php endif; ?>
                                        <?php endif; ?>
                                        <a href="manage-properties.php?copy=<?php echo $prop['id']; ?>" class="btn-small" style="background:#d1d5db;color:#1f2937;" title="Duplicate this property for similar listings">📋 Copy</a>
                                        <button class="btn-small btn-delete" onclick="if(confirm('Delete this property permanently?')) location.href='manage-properties.php?delete=<?php echo $prop['id']; ?>'">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No properties found</h3>
                    <p>Try adjusting your search or filter criteria</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="stat-row">
            <p>Showing: <strong><?php echo $total_properties; ?></strong> properties</p>
        </div>
        
        <div class="footer">
            <p>&copy; 2025 JD Realty & Investment. All rights reserved.</p>
        </div>
    </div>

    <!-- Remarks Modal -->
    <div id="remarksModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
        <div style="background:white; border-radius:12px; max-width:500px; width:90%; padding:25px; box-shadow:0 10px 40px rgba(0,0,0,0.2);">
            <h3 style="margin:0 0 15px 0; color:#1f2937;">📝 Status Remarks</h3>
            <p style="color:#6b7280; font-size:14px; margin-bottom:15px;">Add notes about discussions, negotiations, or closing details.</p>
            <form method="POST" action="manage-properties.php">
                <input type="hidden" name="property_id" id="remarks_property_id">
                <input type="hidden" name="update_remarks" value="1">
                <textarea name="status_remarks" id="remarks_text" rows="4" placeholder="E.g., Client interested, negotiating price. Meeting scheduled for Dec 1st..." style="width:100%; padding:12px; border:2px solid #e5e7eb; border-radius:8px; font-size:14px; resize:vertical; box-sizing:border-box;"></textarea>
                <div style="display:flex; gap:10px; margin-top:15px; justify-content:flex-end;">
                    <button type="button" onclick="closeRemarksModal()" style="padding:10px 20px; border:1px solid #d1d5db; background:white; border-radius:6px; cursor:pointer;">Cancel</button>
                    <button type="submit" style="padding:10px 20px; background:linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600;">Save Remarks</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Deactivation Modal -->
    <div id="deactivateModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
        <div style="background:white; border-radius:12px; max-width:500px; width:90%; padding:25px; box-shadow:0 10px 40px rgba(0,0,0,0.2);">
            <h3 style="margin:0 0 15px 0; color:#991b1b;">⏸ Deactivate Property</h3>
            <p style="color:#6b7280; font-size:14px; margin-bottom:10px;">Property: <strong id="deactivate_property_title"></strong></p>
            <p style="color:#6b7280; font-size:14px; margin-bottom:15px;">Please provide a reason for deactivating this property.</p>
            <form method="POST" action="manage-properties.php">
                <input type="hidden" name="property_id" id="deactivate_property_id">
                <input type="hidden" name="deactivate_property" value="1">
                <div style="margin-bottom:15px;">
                    <label style="display:block; font-weight:600; color:#374151; margin-bottom:8px;">Deactivation Reason *</label>
                    <select name="deactivation_reason" id="deactivation_reason_select" required style="width:100%; padding:12px; border:2px solid #e5e7eb; border-radius:8px; font-size:14px; margin-bottom:10px;" onchange="toggleCustomReason()">
                        <option value="">Select a reason...</option>
                        <option value="Sold">Property Sold</option>
                        <option value="Rented Out">Rented Out</option>
                        <option value="Owner Request">Owner Request</option>
                        <option value="Listing Expired">Listing Expired</option>
                        <option value="Under Renovation">Under Renovation</option>
                        <option value="Price Revision Pending">Price Revision Pending</option>
                        <option value="Documents Pending">Documents Pending</option>
                        <option value="Other">Other (Specify below)</option>
                    </select>
                    <textarea name="custom_reason" id="custom_reason" rows="2" placeholder="Enter custom reason..." style="width:100%; padding:12px; border:2px solid #e5e7eb; border-radius:8px; font-size:14px; resize:vertical; box-sizing:border-box; display:none;"></textarea>
                </div>
                <div style="background:#fef2f2; padding:12px; border-radius:8px; margin-bottom:15px;">
                    <p style="color:#991b1b; font-size:13px; margin:0;"><strong>Note:</strong> Deactivation date will be recorded automatically. You can reactivate the property later from the "Inactive" tab.</p>
                </div>
                <div style="display:flex; gap:10px; justify-content:flex-end;">
                    <button type="button" onclick="closeDeactivateModal()" style="padding:10px 20px; border:1px solid #d1d5db; background:white; border-radius:6px; cursor:pointer;">Cancel</button>
                    <button type="submit" style="padding:10px 20px; background:linear-gradient(135deg, #dc2626 0%, #991b1b 100%); color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600;">Deactivate Property</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showRemarksModal(propertyId, currentRemarks) {
            document.getElementById('remarks_property_id').value = propertyId;
            document.getElementById('remarks_text').value = currentRemarks;
            document.getElementById('remarksModal').style.display = 'flex';
        }
        
        function closeRemarksModal() {
            document.getElementById('remarksModal').style.display = 'none';
        }
        
        function showDeactivateModal(propertyId, propertyTitle) {
            document.getElementById('deactivate_property_id').value = propertyId;
            document.getElementById('deactivate_property_title').textContent = propertyTitle;
            document.getElementById('deactivation_reason_select').value = '';
            document.getElementById('custom_reason').style.display = 'none';
            document.getElementById('custom_reason').value = '';
            document.getElementById('deactivateModal').style.display = 'flex';
        }
        
        function closeDeactivateModal() {
            document.getElementById('deactivateModal').style.display = 'none';
        }
        
        function toggleCustomReason() {
            var select = document.getElementById('deactivation_reason_select');
            var customInput = document.getElementById('custom_reason');
            if (select.value === 'Other') {
                customInput.style.display = 'block';
                customInput.required = true;
            } else {
                customInput.style.display = 'none';
                customInput.required = false;
            }
        }
        
        // Handle form submit to combine reason
        document.querySelector('#deactivateModal form').addEventListener('submit', function(e) {
            var select = document.getElementById('deactivation_reason_select');
            var customInput = document.getElementById('custom_reason');
            if (select.value === 'Other' && customInput.value.trim()) {
                select.value = customInput.value.trim();
            }
        });
        
        // Close modal on outside click
        document.getElementById('remarksModal').addEventListener('click', function(e) {
            if (e.target === this) closeRemarksModal();
        });
        
        document.getElementById('deactivateModal').addEventListener('click', function(e) {
            if (e.target === this) closeDeactivateModal();
        });
    </script>
</body>
</html>
