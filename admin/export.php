<?php
/**
 * Export Data to Excel - Admin Tool
 * Exports Users, Properties, or Inquiries to CSV/Excel format
 */

include('../includes/config.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$type = isset($_GET['type']) ? $_GET['type'] : '';

if (!in_array($type, ['users', 'properties', 'inquiries'])) {
    die("Invalid export type");
}

// Set headers for Excel download
$filename = 'jd_realty_' . $type . '_' . date('Y-m-d_H-i-s') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM for Excel UTF-8 compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

switch ($type) {
    case 'users':
        // Export Users
        fputcsv($output, ['ID', 'Name', 'Email', 'Phone', 'Role', 'Phone Verified', 'Created At', 'Updated At']);
        
        $result = $conn->query("SELECT id, name, email, phone, role, phone_verified, created_at, updated_at FROM users ORDER BY id DESC");
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['id'],
                $row['name'],
                $row['email'],
                $row['phone'] ?? '',
                ucfirst($row['role']),
                $row['phone_verified'] ? 'Yes' : 'No',
                $row['created_at'],
                $row['updated_at']
            ]);
        }
        break;
        
    case 'properties':
        // Export Properties
        fputcsv($output, [
            'ID', 'Title', 'Property Type', 'Category', 'City', 'Address', 
            'Price (₹)', 'Area (sq ft)', 'Bedrooms', 'Bathrooms', 
            'Furnishing', 'Possession', 'Status', 'Status Remarks', 'Approval Status',
            'Owner Name', 'Owner Email', 'Created At'
        ]);
        
        $sql = "SELECT p.*, u.name as owner_name, u.email as owner_email 
                FROM properties p 
                LEFT JOIN users u ON p.created_by = u.id 
                ORDER BY p.id DESC";
        $result = $conn->query($sql);
        
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['id'],
                $row['title'],
                ucfirst($row['property_type']),
                $row['category'] ?? '',
                $row['city'],
                $row['address'] ?? '',
                $row['price'],
                $row['area_sqft'],
                $row['bedrooms'] ?? '',
                $row['bathrooms'] ?? '',
                $row['furnishing_status'] ?? '',
                $row['possession_status'] ?? '',
                ucfirst(str_replace('_', ' ', $row['status'])),
                $row['status_remarks'] ?? '',
                ucfirst($row['approval_status'] ?? 'pending'),
                $row['owner_name'] ?? 'N/A',
                $row['owner_email'] ?? 'N/A',
                $row['created_at']
            ]);
        }
        break;
        
    case 'inquiries':
        // Export Inquiries
        fputcsv($output, [
            'ID', 'Customer Name', 'Customer Email', 'Customer Phone', 
            'Property ID', 'Property Title', 'Property City',
            'Message', 'Status', 'Remarks', 'Created At', 'Replied At'
        ]);
        
        $sql = "SELECT i.*, p.title as property_title, p.city as property_city 
                FROM inquiries i 
                LEFT JOIN properties p ON i.property_id = p.id 
                ORDER BY i.id DESC";
        $result = $conn->query($sql);
        
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['id'],
                $row['name'],
                $row['email'],
                $row['phone'] ?? '',
                $row['property_id'],
                $row['property_title'] ?? 'Deleted Property',
                $row['property_city'] ?? '',
                $row['message'],
                ucfirst($row['status']),
                $row['remarks'] ?? '',
                $row['created_at'],
                $row['replied_at'] ?? ''
            ]);
        }
        break;
}

fclose($output);
$conn->close();
exit();
?>
