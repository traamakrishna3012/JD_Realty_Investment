<?php
include('includes/config.php');

// Check if user or admin is logged in
$is_admin = isset($_SESSION['admin_role']) && $_SESSION['admin_role'] == 'admin';
$is_user = isset($_SESSION['user_id']);

if (!$is_admin && !$is_user) {
    header("Location: login.php");
    exit();
}

// Get the base path for file operations (root of website)
$base_path = dirname(__FILE__) . '/';
$upload_dir = $base_path . 'uploads/properties/';

// Get the base URL for displaying images
$site_base_url = defined('SITE_URL') ? SITE_URL . '/' : '/';

// Create uploads directory if it doesn't exist
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$error = '';
$success = '';
$property = null;

// Check if property ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $redirect = $is_admin ? 'admin/manage-properties.php' : 'user-dashboard.php';
    header("Location: $redirect?error=Invalid property ID");
    exit();
}

$property_id = intval($_GET['id']);

// Fetch property details - Admin can edit any, user can only edit their own
if ($is_admin) {
    $fetch_sql = "SELECT * FROM properties WHERE id=$property_id";
} else {
    $user_id = $_SESSION['user_id'];
    $fetch_sql = "SELECT * FROM properties WHERE id=$property_id AND created_by=$user_id";
}
$fetch_result = $conn->query($fetch_sql);

if (!$fetch_result || $fetch_result->num_rows === 0) {
    $redirect = $is_admin ? 'admin/manage-properties.php' : 'user-dashboard.php';
    header("Location: $redirect?error=Property not found");
    exit();
}

$property = $fetch_result->fetch_assoc();

// Fetch amenities
$amenities_sql = "SELECT * FROM amenities ORDER BY category, name";
$amenities_result = $conn->query($amenities_sql);
$amenities = [];

// Icon mapping for amenities (fallback for database encoding issues)
$amenity_icons = [
    // Basic Amenities
    'Power Backup' => '⚡',
    'Lift/Elevator' => '🛗',
    'Water Supply 24x7' => '💧',
    'Gas Pipeline' => '🔥',
    'Sewage Treatment' => '🚰',
    'Rain Water Harvesting' => '🌧️',
    'Waste Disposal' => '🗑️',
    'Internet/Wi-Fi' => '📶',
    // Safety Amenities
    'Security Guard' => '👮',
    'CCTV Surveillance' => '📹',
    'Gated Community' => '🚧',
    'Fire Safety' => '🧯',
    'Intercom' => '📞',
    'Video Door Phone' => '🚪',
    // Convenience Amenities
    'Car Parking' => '🚗',
    'Visitor Parking' => '🅿️',
    'Shopping Center' => '🛒',
    'ATM' => '🏧',
    'Laundry Service' => '🧺',
    'Maintenance Staff' => '🔧',
    'Pet Friendly' => '🐕',
    // Recreation Amenities
    'Swimming Pool' => '🏊',
    'Gym/Fitness Center' => '🏋️',
    'Children Play Area' => '🎠',
    'Clubhouse' => '🏛️',
    'Garden/Park' => '🌳',
    'Jogging Track' => '🏃',
    'Indoor Games' => '🎯',
    'Tennis Court' => '🎾',
    'Basketball Court' => '🏀',
    'Badminton Court' => '🏸',
    // Luxury Amenities
    'Spa/Sauna' => '🧖',
    'Jacuzzi' => '🛁',
    'Home Theater' => '🎬',
    'Concierge Service' => '🛎️',
    'Rooftop Garden' => '🌺',
    'Private Terrace' => '🏖️',
    'Wine Cellar' => '🍷',
    'Smart Home Features' => '🏠'
];

if ($amenities_result) {
    while ($row = $amenities_result->fetch_assoc()) {
        // Use PHP icon mapping as fallback if database icon is missing or corrupted
        $icon = isset($row['icon']) ? trim($row['icon']) : '';
        if (empty($icon) || $icon === '?' || strlen($icon) > 20) {
            $row['icon'] = isset($amenity_icons[$row['name']]) ? $amenity_icons[$row['name']] : '✓';
        }
        $amenities[$row['category']][] = $row;
    }
}

// Fetch selected amenities
$selected_amenities = [];
$selected_sql = "SELECT amenity_id FROM property_amenities WHERE property_id=$property_id";
$selected_result = $conn->query($selected_sql);
if ($selected_result) {
    while ($row = $selected_result->fetch_assoc()) {
        $selected_amenities[] = $row['amenity_id'];
    }
}

// Fetch existing property images
$existing_images = [];
// Check if image_category column exists
$column_check = $conn->query("SHOW COLUMNS FROM property_images LIKE 'image_category'");
if ($column_check && $column_check->num_rows > 0) {
    $images_sql = "SELECT * FROM property_images WHERE property_id=$property_id ORDER BY image_category, display_order";
} else {
    $images_sql = "SELECT * FROM property_images WHERE property_id=$property_id ORDER BY id";
}
$images_result = $conn->query($images_sql);
if ($images_result) {
    while ($row = $images_result->fetch_assoc()) {
        $category = $row['image_category'] ?? 'other';
        $existing_images[$category][] = $row;
    }
}

// Image categories for upload
$image_categories = [
    'exterior' => ['name' => 'Exterior/Building', 'icon' => '🏢'],
    'interior' => ['name' => 'Interior Overview', 'icon' => '🏠'],
    'bedroom' => ['name' => 'Bedroom', 'icon' => '🛏️'],
    'bathroom' => ['name' => 'Bathroom', 'icon' => '🚿'],
    'kitchen' => ['name' => 'Kitchen', 'icon' => '🍳'],
    'living_room' => ['name' => 'Living Room', 'icon' => '🛋️'],
    'balcony' => ['name' => 'Balcony/Terrace', 'icon' => '🌅'],
    'parking' => ['name' => 'Parking', 'icon' => '🚗'],
    'amenities' => ['name' => 'Amenities', 'icon' => '🏊'],
    'floor_plan' => ['name' => 'Floor Plan', 'icon' => '📐'],
    'other' => ['name' => 'Other', 'icon' => '📷']
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);
    $property_type = $conn->real_escape_string($_POST['property_type']);
    $category = !empty($_POST['category']) ? $conn->real_escape_string($_POST['category']) : NULL;
    $city = $conn->real_escape_string($_POST['city']);
    $address = !empty($_POST['address']) ? $conn->real_escape_string($_POST['address']) : NULL;
    $price = floatval($_POST['price']);
    $area_sqft = floatval($_POST['area_sqft']);
    $bedrooms = !empty($_POST['bedrooms']) ? intval($_POST['bedrooms']) : NULL;
    $bathrooms = !empty($_POST['bathrooms']) ? intval($_POST['bathrooms']) : NULL;
    $furnishing_status = $conn->real_escape_string($_POST['furnishing_status']);
    $possession_status = $conn->real_escape_string($_POST['possession_status']);
    $total_floors = !empty($_POST['total_floors']) ? intval($_POST['total_floors']) : NULL;
    $floor_number = !empty($_POST['floor_number']) ? intval($_POST['floor_number']) : NULL;
    $age_of_property = !empty($_POST['age_of_property']) ? $conn->real_escape_string($_POST['age_of_property']) : NULL;
    
    // New fields
    $pre_lease = !empty($_POST['pre_lease']) ? $conn->real_escape_string($_POST['pre_lease']) : 'no';
    $possession_date = !empty($_POST['possession_date']) ? $conn->real_escape_string($_POST['possession_date']) : NULL;
    $workstations = !empty($_POST['workstations']) ? intval($_POST['workstations']) : 0;
    $cabins = !empty($_POST['cabins']) ? intval($_POST['cabins']) : 0;
    $conference_rooms = !empty($_POST['conference_rooms']) ? intval($_POST['conference_rooms']) : 0;
    $meeting_rooms = !empty($_POST['meeting_rooms']) ? intval($_POST['meeting_rooms']) : 0;
    $pantry = !empty($_POST['pantry']) ? $conn->real_escape_string($_POST['pantry']) : 'no';
    
    // Location handling - auto-detect or manual
    $location_method = $_POST['location_method'] ?? 'manual';
    $latitude = 0;
    $longitude = 0;
    
    if ($location_method === 'auto' && !empty($_POST['detected_latitude']) && !empty($_POST['detected_longitude'])) {
        $latitude = floatval($_POST['detected_latitude']);
        $longitude = floatval($_POST['detected_longitude']);
    } elseif ($location_method === 'manual' && !empty($_POST['latitude']) && !empty($_POST['longitude'])) {
        $latitude = floatval($_POST['latitude']);
        $longitude = floatval($_POST['longitude']);
    }

    // Validation
    if (empty($title) || empty($description) || empty($property_type) || empty($city) || empty($price) || empty($area_sqft)) {
        $error = "All required fields must be filled!";
    } elseif ($price <= 0 || $area_sqft <= 0) {
        $error = "Price and area must be greater than 0!";
    } else {
        // If regular user edits an approved property, set it back to pending for admin re-approval
        $approval_change = "";
        if (!$is_admin && $property['approval_status'] == 'approved') {
            $approval_change = ", approval_status='pending'";
        }
        
        // Escape building name
        $building_name_escaped = $conn->real_escape_string($_POST['building_name'] ?? '');
        $building_name_value = $building_name_escaped ? "'$building_name_escaped'" : "NULL";
        
        // Update property with optimized query
        $sql = "UPDATE properties SET 
                title='$title', 
                building_name=$building_name_value,
                description='$description', 
                property_type='$property_type', 
                category=" . ($category ? "'$category'" : "NULL") . ", 
                city='$city', 
                address=" . ($address ? "'$address'" : "NULL") . ", 
                price=$price, 
                area_sqft=$area_sqft, 
                bedrooms=" . ($bedrooms ? $bedrooms : "NULL") . ", 
                bathrooms=" . ($bathrooms ? $bathrooms : "NULL") . ", 
                furnishing_status='$furnishing_status', 
                possession_status='$possession_status', 
                total_floors=" . ($total_floors ? $total_floors : "NULL") . ", 
                floor_number=" . ($floor_number ? $floor_number : "NULL") . ", 
                age_of_property=" . ($age_of_property ? "'$age_of_property'" : "NULL") . ", 
                latitude=$latitude, 
                longitude=$longitude,
                pre_lease='$pre_lease',
                possession_date=" . ($possession_date ? "'$possession_date'" : "NULL") . ",
                workstations=$workstations,
                cabins=$cabins,
                conference_rooms=$conference_rooms,
                meeting_rooms=$meeting_rooms,
                pantry='$pantry',
                updated_at=NOW()
                $approval_change 
                WHERE id=$property_id";

        if ($conn->query($sql) === TRUE) {
            // Update amenities
            $conn->query("DELETE FROM property_amenities WHERE property_id=$property_id");
            if (!empty($_POST['amenities'])) {
                foreach ($_POST['amenities'] as $amenity_id) {
                    $amenity_id = intval($amenity_id);
                    $conn->query("INSERT INTO property_amenities (property_id, amenity_id) VALUES ($property_id, $amenity_id)");
                }
            }

            // Handle file uploads if any
            if (!empty($_FILES['property_images']['name'][0])) {
                $files = $_FILES['property_images'];
                $file_count = count($files['name']);

                for ($i = 0; $i < $file_count; $i++) {
                    $file_name = $files['name'][$i];
                    $file_tmp = $files['tmp_name'][$i];
                    $file_type = $files['type'][$i];
                    $file_size = $files['size'][$i];

                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    if (in_array($file_type, $allowed_types) && $file_size <= 5 * 1024 * 1024) {
                        $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
                        $unique_name = 'prop_' . $property_id . '_' . time() . '_' . $i . '.' . $file_ext;
                        $full_upload_path = $upload_dir . $unique_name;
                        $db_path = 'uploads/properties/' . $unique_name;

                        if (move_uploaded_file($file_tmp, $full_upload_path)) {
                            $is_featured = 0;
                            $conn->query("INSERT INTO property_images (property_id, image_url, is_featured) VALUES ($property_id, '$db_path', $is_featured)");
                        }
                    }
                }
            }
            
            // Handle category-wise image uploads
            foreach ($image_categories as $cat_key => $cat_info) {
                $field_name = 'images_' . $cat_key;
                if (!empty($_FILES[$field_name]['name'][0])) {
                    $files = $_FILES[$field_name];
                    $file_count = count($files['name']);
                    
                    for ($i = 0; $i < $file_count && $i < 5; $i++) {
                        if ($files['error'][$i] === UPLOAD_ERR_OK) {
                            $file_name = $files['name'][$i];
                            $file_tmp = $files['tmp_name'][$i];
                            $file_type = $files['type'][$i];
                            $file_size = $files['size'][$i];
                            
                            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                            if (in_array($file_type, $allowed_types) && $file_size <= 5 * 1024 * 1024) {
                                $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
                                $unique_name = 'prop_' . $property_id . '_' . $cat_key . '_' . time() . '_' . $i . '.' . $file_ext;
                                $full_upload_path = $upload_dir . $unique_name;
                                $db_path = 'uploads/properties/' . $unique_name;
                                
                                if (move_uploaded_file($file_tmp, $full_upload_path)) {
                                    $escaped_path = $conn->real_escape_string($db_path);
                                    $conn->query("INSERT INTO property_images (property_id, image_url, image_category, display_order) VALUES ($property_id, '$escaped_path', '$cat_key', $i)");
                                }
                            }
                        }
                    }
                }
            }
            
            // Handle image deletions
            if (!empty($_POST['delete_images'])) {
                foreach ($_POST['delete_images'] as $image_id) {
                    $image_id = intval($image_id);
                    // Get image path
                    $img_result = $conn->query("SELECT image_url FROM property_images WHERE id=$image_id AND property_id=$property_id");
                    if ($img_result && $img_row = $img_result->fetch_assoc()) {
                        // Delete file using absolute path
                        $full_path = $base_path . $img_row['image_url'];
                        if (file_exists($full_path)) {
                            unlink($full_path);
                        }
                        // Delete from database
                        $conn->query("DELETE FROM property_images WHERE id=$image_id AND property_id=$property_id");
                    }
                }
            }
            
            // Update main image_url in properties table from first gallery image
            // This ensures the homepage shows the correct image
            $first_img = $conn->query("SELECT image_url FROM property_images WHERE property_id=$property_id ORDER BY id ASC LIMIT 1");
            if ($first_img && $first_img->num_rows > 0) {
                $first_img_row = $first_img->fetch_assoc();
                $new_main_image = $conn->real_escape_string($first_img_row['image_url']);
                $conn->query("UPDATE properties SET image_url = '$new_main_image' WHERE id = $property_id");
            } else {
                // No gallery images left, clear main image
                $conn->query("UPDATE properties SET image_url = NULL WHERE id = $property_id");
            }

            // Set success message based on whether re-approval is needed
            if (!$is_admin && $property['approval_status'] == 'approved') {
                $success = "Property updated successfully! Your property has been sent for admin re-approval.";
            } else {
                $success = "Property updated successfully!";
            }
            // Refresh property data
            $fetch_result = $conn->query($fetch_sql);
            $property = $fetch_result->fetch_assoc();
            
            // CRITICAL: Reload images after update
            $existing_images = [];
            $images_sql = "SELECT * FROM property_images WHERE property_id=$property_id ORDER BY image_category, display_order";
            $images_result = $conn->query($images_sql);
            if ($images_result) {
                while ($row = $images_result->fetch_assoc()) {
                    $category = $row['image_category'] ?? 'other';
                    $existing_images[$category][] = $row;
                }
            }
            
            $selected_result = $conn->query("SELECT amenity_id FROM property_amenities WHERE property_id=$property_id");
            $selected_amenities = [];
            while ($row = $selected_result->fetch_assoc()) {
                $selected_amenities[] = $row['amenity_id'];
            }
        } else {
            $error = "Error updating property: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Edit your property listing on JD Realty & Investment">
    <meta name="robots" content="noindex, follow">
    <title>Edit Property - JD Realty & Investment</title>
    <link rel="stylesheet" href="css/style.css?v=20251203c">
    <!-- Google Maps API for Location Selection -->
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAybBsnJ4hUUBVtOg1kfZ6FPBd9FahRGgo&libraries=places"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
            color: #1f2937;
            min-height: 100vh;
        }

        .navbar {
            background: linear-gradient(135deg, #374151 0%, #1f2937 50%, #111827 100%);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #d97706;
        }

        .logo span {
            color: #4f46e5;
        }

        .nav-links {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        .nav-links a {
            color: #ffffff;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .nav-links a:hover {
            color: #fbbf24;
        }

        .nav-breadcrumb {
            color: #6b7280;
            font-size: 14px;
        }

        .nav-breadcrumb a {
            color: #667eea;
            text-decoration: none;
            margin: 0 4px;
        }

        .logout-btn {
            background: linear-gradient(135deg, #f5576c 0%, #f093fb 100%);
            color: white !important;
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            box-shadow: 0 2px 10px rgba(245, 87, 108, 0.3);
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(245, 87, 108, 0.4);
            color: white !important;
        }

        .container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 32px;
            margin-bottom: 8px;
            color: #1f2937;
        }

        .page-header p {
            color: #6b7280;
            font-size: 16px;
        }

        .comparison-nav {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 30px;
        }

        .comparison-btn {
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            color: #1f2937;
            transition: all 0.3s ease;
            text-align: center;
        }

        .comparison-btn:hover {
            border-color: #667eea;
            color: #667eea;
            background: #f0f4ff;
        }

        .form-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .form-section {
            margin-bottom: 35px;
            padding-bottom: 25px;
            border-bottom: 2px solid #f0f0f0;
        }

        .form-section:last-child {
            border-bottom: none;
        }

        .form-section h3 {
            font-size: 18px;
            color: #667eea;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-icon {
            width: 24px;
            height: 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        .form-row.single {
            grid-template-columns: 1fr;
        }

        .form-row.three-col {
            grid-template-columns: repeat(3, 1fr);
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #1f2937;
            font-size: 14px;
        }

        .required {
            color: #dc2626;
        }

        input[type="text"],
        input[type="number"],
        input[type="file"],
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s ease;
            background: white;
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        input[type="file"]:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            background: #f9fbff;
        }

        textarea {
            resize: vertical;
            min-height: 120px;
        }

        /* Location Tabs */
        .location-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }

        .location-tab {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            background: white;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            color: #6b7280;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .location-tab:hover {
            border-color: #667eea;
            background: #f9fbff;
        }

        .location-tab.active {
            border-color: #667eea;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .location-tab .tab-icon {
            font-size: 16px;
        }

        .location-content {
            animation: fadeIn 0.3s ease;
        }

        .location-content.hidden {
            display: none;
        }

        .auto-detect-section {
            padding: 20px;
            background: #f9fbff;
            border-radius: 10px;
            border: 2px dashed #e5e7eb;
        }

        .location-preview {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .location-preview .preview-icon {
            font-size: 40px;
            opacity: 0.7;
        }

        .location-preview .preview-text p {
            margin: 0;
            color: #4b5563;
            font-size: 14px;
        }

        .location-preview .preview-text small {
            color: #9ca3af;
            font-size: 12px;
        }

        .auto-detect-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .auto-detect-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
        }

        .auto-detect-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .auto-detect-btn .btn-icon {
            font-size: 18px;
        }

        .location-status {
            margin-top: 12px;
            padding: 12px;
            border-radius: 8px;
            font-size: 13px;
            text-align: center;
        }

        .location-status:empty {
            display: none;
        }

        .location-status .status-success {
            color: #065f46;
        }

        .location-status.success {
            background: #d1fae5;
            color: #065f46;
        }

        .location-status.error {
            background: #fee2e2;
            color: #991b1b;
        }

        .manual-entry-section {
            padding: 20px;
            background: #f9fbff;
            border-radius: 10px;
            border: 2px solid #e5e7eb;
        }

        .manual-hint {
            margin: 0 0 15px 0;
            color: #4b5563;
            font-size: 14px;
        }

        .maps-link-btn {
            padding: 10px 14px;
            background: #f3f4f6;
            color: #374151;
            text-decoration: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }

        .maps-link-btn:hover {
            background: #e5e7eb;
            color: #1f2937;
        }

        .coordinates-hint {
            margin-top: 12px;
            padding: 10px;
            background: #fef3c7;
            border-radius: 6px;
        }

        .coordinates-hint small {
            color: #92400e;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .amenities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 12px;
        }

        .amenity-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }

        .amenity-item input[type="checkbox"] {
            width: auto;
            margin: 0;
            cursor: pointer;
        }

        .amenity-item:has(input[type="checkbox"]:checked) {
            background: #f0f4ff;
            border-color: #667eea;
        }

        .amenity-item label {
            margin: 0;
            cursor: pointer;
            font-weight: 500;
        }

        .image-upload-area {
            border: 3px dashed #667eea;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f9fbff;
        }

        .image-upload-area:hover {
            border-color: #764ba2;
            background: #f0f4ff;
        }

        .image-upload-area p {
            color: #6b7280;
            margin: 10px 0 0 0;
            font-size: 13px;
        }

        #image-preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .preview-img-container {
            position: relative;
        }

        .preview-img {
            width: 100%;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .remove-img-btn {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #dc2626;
            color: white;
            border: none;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .remove-img-btn:hover {
            background: #991b1b;
            transform: scale(1.1);
        }

        .form-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 35px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
        }

        .submit-btn,
        .cancel-btn {
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .submit-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .cancel-btn {
            background: #f3f4f6;
            color: #1f2937;
            border: 2px solid #e5e7eb;
        }

        .cancel-btn:hover {
            background: #e5e7eb;
        }

        .error-message {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid #dc2626;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .success-message {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid #06b6d4;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .message-icon {
            font-size: 20px;
            flex-shrink: 0;
        }

        .info-box {
            background: #dbeafe;
            border-left: 4px solid #3b82f6;
            padding: 12px;
            border-radius: 6px;
            color: #0c2d48;
            font-size: 13px;
            margin-top: 8px;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .form-row.three-col {
                grid-template-columns: 1fr;
            }

            .comparison-nav {
                grid-template-columns: 1fr;
            }

            .form-actions {
                grid-template-columns: 1fr;
            }

            .amenities-grid {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            }

            .container {
                margin: 20px auto;
            }

            .form-container {
                padding: 20px;
            }

            .location-methods {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <?php $back_url = $is_admin ? 'admin/manage-properties.php' : 'user-dashboard.php'; ?>
    <?php $back_text = $is_admin ? 'Manage Properties' : 'My Properties'; ?>
    <div class="navbar">
        <a href="index.php" class="logo" style="text-decoration: none; display: flex; align-items: center; gap: 12px;">
            <img src="images/jd-logo.svg?v=20251203" alt="JD Realty & Investment" style="height: 60px; width: 60px;">
            <span style="font-size: 24px; font-weight: bold; color: #d4a84b; text-shadow: 1px 1px 2px rgba(0,0,0,0.3);">JD Realty Investment</span>
        </a>
        <div class="nav-links">
            <span class="nav-breadcrumb">
                <a href="<?php echo $back_url; ?>"><?php echo $back_text; ?></a> / Edit Property
            </span>
            <a href="<?php echo $is_admin ? 'admin/dashboard.php?logout=true' : 'includes/logout.php'; ?>" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h1>Edit Your Property</h1>
            <p>Update your property details to attract more potential buyers</p>
        </div>

        <div class="comparison-nav">
            <a href="<?php echo $back_url; ?>" class="comparison-btn">← Back to <?php echo $back_text; ?></a>
            <?php if (!$is_admin): ?>
            <a href="list-property.php" class="comparison-btn">Compare with New Listing →</a>
            <?php endif; ?>
        </div>

        <div class="form-container">
            <?php if ($error): ?>
                <div class="error-message">
                    <span class="message-icon">⚠️</span>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message">
                    <span class="message-icon">✓</span>
                    <span><?php echo $success; ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <!-- Basic Details Section -->
                <div class="form-section">
                    <h3><span class="section-icon">1</span> Basic Details</h3>
                    
                    <div class="form-group">
                        <label>Property Title <span class="required">*</span></label>
                        <input type="text" name="title" required placeholder="e.g., Luxury 3BHK with Sea View"
                            value="<?php echo htmlspecialchars($property['title']); ?>">
                        <div class="info-box">Use a compelling title that highlights key features</div>
                    </div>

                    <div class="form-group">
                        <label>Building Name (Optional)</label>
                        <input type="text" name="building_name" placeholder="e.g., Mumbai Heights, Lodha Park"
                            value="<?php echo htmlspecialchars($property['building_name'] ?? ''); ?>">
                        <div class="info-box">Enter the building name for apartment complexes or societies</div>
                    </div>

                    <div class="form-group">
                        <label>Description <span class="required">*</span></label>
                        <textarea name="description" required placeholder="Describe your property in detail. Mention unique features, amenities, and highlights..."><?php echo htmlspecialchars($property['description']); ?></textarea>
                        <div class="info-box">A detailed description increases buyer interest (min 50 characters)</div>
                    </div>
                </div>

                <!-- Property Specifications Section -->
                <div class="form-section">
                    <h3><span class="section-icon">2</span> Property Specifications</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Property Type <span class="required">*</span></label>
                            <select name="property_type" id="property_type" required onchange="toggleCommercialSpecs()">
                                <option value="">Select Type</option>
                                <option value="residential" <?php echo $property['property_type'] === 'residential' ? 'selected' : ''; ?>>Residential</option>
                                <option value="commercial" <?php echo $property['property_type'] === 'commercial' ? 'selected' : ''; ?>>Commercial</option>
                                <option value="plot" <?php echo $property['property_type'] === 'plot' ? 'selected' : ''; ?>>Plot</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Category</label>
                            <select name="category">
                                <option value="">Select Category</option>
                                <option value="1bhk" <?php echo $property['category'] === '1bhk' ? 'selected' : ''; ?>>1 BHK</option>
                                <option value="2bhk" <?php echo $property['category'] === '2bhk' ? 'selected' : ''; ?>>2 BHK</option>
                                <option value="3bhk" <?php echo $property['category'] === '3bhk' ? 'selected' : ''; ?>>3 BHK</option>
                                <option value="4bhk" <?php echo $property['category'] === '4bhk' ? 'selected' : ''; ?>>4 BHK</option>
                                <option value="above4" <?php echo $property['category'] === 'above4' ? 'selected' : ''; ?>>Above 4 BHK</option>
                                <option value="shop" <?php echo $property['category'] === 'shop' ? 'selected' : ''; ?>>Shop</option>
                                <option value="office" <?php echo $property['category'] === 'office' ? 'selected' : ''; ?>>Office</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Furnishing Status</label>
                            <select name="furnishing_status">
                                <option value="unfurnished" <?php echo $property['furnishing_status'] === 'unfurnished' ? 'selected' : ''; ?>>Unfurnished</option>
                                <option value="semi-furnished" <?php echo $property['furnishing_status'] === 'semi-furnished' ? 'selected' : ''; ?>>Semi-Furnished</option>
                                <option value="fully-furnished" <?php echo $property['furnishing_status'] === 'fully-furnished' ? 'selected' : ''; ?>>Fully Furnished</option>
                                <option value="bareshell" <?php echo $property['furnishing_status'] === 'bareshell' ? 'selected' : ''; ?>>Bareshell</option>
                                <option value="warmshell" <?php echo $property['furnishing_status'] === 'warmshell' ? 'selected' : ''; ?>>Warmshell</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Possession Status</label>
                            <select name="possession_status">
                                <option value="ready_to_move" <?php echo $property['possession_status'] === 'ready_to_move' ? 'selected' : ''; ?>>Ready to Move</option>
                                <option value="under_construction" <?php echo $property['possession_status'] === 'under_construction' ? 'selected' : ''; ?>>Under Construction</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Pre-Lease</label>
                            <select name="pre_lease">
                                <option value="no" <?php echo ($property['pre_lease'] ?? 'no') === 'no' ? 'selected' : ''; ?>>No</option>
                                <option value="yes" <?php echo ($property['pre_lease'] ?? 'no') === 'yes' ? 'selected' : ''; ?>>Yes</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Possession Date</label>
                            <input type="date" name="possession_date" value="<?php echo $property['possession_date'] ?? ''; ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Bedrooms</label>
                            <input type="number" name="bedrooms" placeholder="e.g., 2" value="<?php echo $property['bedrooms'] ?? ''; ?>">
                        </div>

                        <div class="form-group">
                            <label>Bathrooms</label>
                            <input type="number" name="bathrooms" placeholder="e.g., 2" value="<?php echo $property['bathrooms'] ?? ''; ?>">
                        </div>
                    </div>
                    
                    <!-- Commercial Property Specs (for shop/office/commercial) -->
                    <div class="form-row commercial-specs" style="display: none;">
                        <div class="form-group">
                            <label>Workstations</label>
                            <input type="number" name="workstations" placeholder="e.g., 20" min="0" value="<?php echo $property['workstations'] ?? '0'; ?>">
                        </div>
                        <div class="form-group">
                            <label>Cabins</label>
                            <input type="number" name="cabins" placeholder="e.g., 5" min="0" value="<?php echo $property['cabins'] ?? '0'; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row commercial-specs" style="display: none;">
                        <div class="form-group">
                            <label>Conference Rooms</label>
                            <input type="number" name="conference_rooms" placeholder="e.g., 2" min="0" value="<?php echo $property['conference_rooms'] ?? '0'; ?>">
                        </div>
                        <div class="form-group">
                            <label>Meeting Rooms</label>
                            <input type="number" name="meeting_rooms" placeholder="e.g., 3" min="0" value="<?php echo $property['meeting_rooms'] ?? '0'; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row commercial-specs" style="display: none;">
                        <div class="form-group">
                            <label>Pantry</label>
                            <select name="pantry">
                                <option value="no" <?php echo ($property['pantry'] ?? 'no') === 'no' ? 'selected' : ''; ?>>No</option>
                                <option value="yes" <?php echo ($property['pantry'] ?? 'no') === 'yes' ? 'selected' : ''; ?>>Yes</option>
                            </select>
                        </div>
                        <div class="form-group"></div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Total Floors</label>
                            <input type="number" name="total_floors" placeholder="e.g., 20" value="<?php echo $property['total_floors'] ?? ''; ?>">
                        </div>

                        <div class="form-group">
                            <label>Floor Number</label>
                            <input type="number" name="floor_number" placeholder="e.g., 5" value="<?php echo $property['floor_number'] ?? ''; ?>">
                        </div>
                    </div>

                    <div class="form-row single">
                        <div class="form-group">
                            <label>Property Age</label>
                            <select name="age_of_property">
                                <option value="">Select Age</option>
                                <option value="New Construction" <?php echo $property['age_of_property'] === 'New Construction' ? 'selected' : ''; ?>>New Construction</option>
                                <option value="Less than 5 years" <?php echo $property['age_of_property'] === 'Less than 5 years' ? 'selected' : ''; ?>>Less than 5 years</option>
                                <option value="5 to 10 years" <?php echo $property['age_of_property'] === '5 to 10 years' ? 'selected' : ''; ?>>5 to 10 years</option>
                                <option value="More than 10 years" <?php echo $property['age_of_property'] === 'More than 10 years' ? 'selected' : ''; ?>>More than 10 years</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Location & Pricing Section -->
                <div class="form-section">
                    <h3><span class="section-icon">3</span> Location & Pricing</h3>

                    <div class="form-row single">
                        <div class="form-group">
                            <label>City <span class="required">*</span></label>
                            <input type="text" name="city" required placeholder="e.g., Thane" value="<?php echo htmlspecialchars($property['city']); ?>">
                        </div>
                    </div>

                    <div class="form-row single">
                        <div class="form-group">
                            <label>Full Address</label>
                            <textarea name="address" placeholder="Enter the complete address..." style="min-height: 80px;"><?php echo htmlspecialchars($property['address'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="form-group">
                        <label style="margin-bottom: 15px;">📍 Property Location on Map</label>
                        <input type="hidden" name="location_method" id="location_method" value="manual">
                        <input type="hidden" name="latitude" id="property_latitude" value="<?php echo $property['latitude'] ?? ''; ?>">
                        <input type="hidden" name="longitude" id="property_longitude" value="<?php echo $property['longitude'] ?? ''; ?>">
                        
                        <div class="map-selection-section" style="margin-top: 15px;">
                            <!-- Search by Address - Primary Method -->
                            <div style="margin-bottom: 15px; background: #f0fdf4; padding: 15px; border-radius: 10px; border: 2px solid #86efac;">
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #166534;">🔍 Search Property Location by Address</label>
                                <input type="text" id="addressSearch" placeholder="Type property address (e.g., Hiranandani Estate, Thane West)" style="width: 100%; padding: 14px; font-size: 15px; border: 2px solid #86efac; border-radius: 8px;">
                                <p style="font-size: 12px; color: #6b7280; margin-top: 8px;">Start typing and select from suggestions</p>
                            </div>
                            
                            <div class="location-status" id="locationStatus">
                                <?php if (!empty($property['latitude']) && $property['latitude'] != 0): ?>
                                    <span class="status-success">✓ Location saved: <?php echo $property['latitude']; ?>, <?php echo $property['longitude']; ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Interactive Map for Location Selection -->
                            <div id="locationMapContainer" style="margin-top: 15px;">
                                <div id="locationMap" style="width: 100%; height: 400px; border-radius: 10px; border: 2px solid #e5e7eb;"></div>
                                <p style="font-size: 13px; color: #6b7280; margin-top: 10px; text-align: center; background: #f0f9ff; padding: 10px; border-radius: 8px;">
                                    📍 <strong>Click on the map</strong> to set exact location or <strong>drag the marker</strong> to adjust
                                </p>
                            </div>
                            
                            <button type="button" class="auto-detect-btn" id="detectLocationBtn" style="margin-top: 10px; background: #6b7280; font-size: 13px; padding: 8px 15px;">
                                📍 Use My Current Location (only if you're at the property)
                            </button>
                        </div>
                        
                        <div class="info-box">
                            <span class="info-icon">ℹ️</span>
                            Adding accurate location helps buyers find your property on maps and shows nearby amenities
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Price (₹) <span class="required">*</span></label>
                            <input type="number" name="price" required placeholder="e.g., 7500000" value="<?php echo $property['price']; ?>">
                        </div>

                        <div class="form-group">
                            <label>Area (sq ft) <span class="required">*</span></label>
                            <input type="number" name="area_sqft" required placeholder="e.g., 950" value="<?php echo $property['area_sqft']; ?>">
                        </div>
                    </div>
                </div>

                <!-- Amenities Section -->
                <div class="form-section">
                    <h3><span class="section-icon">4</span> Amenities</h3>
                    <div class="amenities-grid">
                        <?php foreach ($amenities as $category => $items): ?>
                            <?php foreach ($items as $amenity): ?>
                                <div class="amenity-item">
                                    <input type="checkbox" name="amenities[]" value="<?php echo $amenity['id']; ?>" 
                                        id="amenity_<?php echo $amenity['id']; ?>"
                                        <?php echo in_array($amenity['id'], $selected_amenities) ? 'checked' : ''; ?>>
                                    <label for="amenity_<?php echo $amenity['id']; ?>">
                                        <?php echo htmlspecialchars($amenity['name']); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Gallery Section -->
                <div class="form-section">
                    <h3><span class="section-icon">5</span> Property Gallery</h3>
                    
                    <p style="color: #6b7280; margin-bottom: 20px; font-size: 14px;">
                        📸 Add high-quality images organized by category. Max 5 images per category.
                    </p>
                    
                    <!-- Existing Images -->
                    <?php if (!empty($existing_images)): ?>
                    <div class="existing-images-section" style="margin-bottom: 30px;">
                        <h4 style="font-size: 16px; color: #374151; margin-bottom: 15px;">📁 Existing Images</h4>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px;">
                            <?php foreach ($existing_images as $category => $images): ?>
                                <?php foreach ($images as $img): ?>
                                <div class="existing-img-item" id="img-container-<?php echo $img['id']; ?>" style="position: relative; border: 2px solid #e5e7eb; border-radius: 10px; overflow: hidden;">
                                    <img src="<?php echo $site_base_url . htmlspecialchars($img['image_url']); ?>" 
                                         style="width: 100%; height: 120px; object-fit: cover;"
                                         alt="Property image"
                                         onerror="this.src='<?php echo $site_base_url; ?>images/no-image.png';">
                                    <div style="padding: 8px; background: #f9fafb;">
                                        <small style="color: #6b7280; font-size: 11px;">
                                            <?php echo ucfirst(str_replace('_', ' ', $img['image_category'] ?? 'Uncategorized')); ?>
                                        </small>
                                    </div>
                                    <label class="delete-label" style="position: absolute; top: 8px; right: 8px; background: rgba(220, 38, 38, 0.9); color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; cursor: pointer; display: flex; align-items: center; gap: 4px;">
                                        <input type="checkbox" name="delete_images[]" value="<?php echo $img['id']; ?>" 
                                               onchange="this.parentElement.style.background = this.checked ? '#dc2626' : 'rgba(220, 38, 38, 0.9)'; 
                                                         document.getElementById('img-container-<?php echo $img['id']; ?>').style.opacity = this.checked ? '0.5' : '1';
                                                         document.getElementById('img-container-<?php echo $img['id']; ?>').style.border = this.checked ? '2px solid #dc2626' : '2px solid #e5e7eb';">
                                        🗑️ Delete
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </div>
                        <p style="color: #dc2626; font-size: 12px; margin-top: 10px;">⚠️ Check the boxes above to delete images when you save</p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Category Image Upload Tabs -->
                    <div class="image-tabs" style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 20px; border-bottom: 2px solid #e5e7eb; padding-bottom: 15px;">
                        <?php 
                        $first_cat = true;
                        foreach ($image_categories as $cat_key => $cat_info): 
                        ?>
                        <button type="button" class="image-tab <?php echo $first_cat ? 'active' : ''; ?>" data-category="<?php echo $cat_key; ?>"
                            style="padding: 10px 16px; border: 2px solid <?php echo $first_cat ? '#667eea' : '#e5e7eb'; ?>; 
                                   border-radius: 8px; background: <?php echo $first_cat ? 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' : 'white'; ?>; 
                                   color: <?php echo $first_cat ? 'white' : '#6b7280'; ?>; cursor: pointer; font-weight: 600; font-size: 13px;
                                   transition: all 0.3s ease;">
                            <?php echo $cat_info['icon']; ?> <?php echo $cat_info['name']; ?>
                        </button>
                        <?php 
                        $first_cat = false;
                        endforeach; 
                        ?>
                    </div>
                    
                    <!-- Category Upload Areas -->
                    <?php 
                    $first_cat = true;
                    foreach ($image_categories as $cat_key => $cat_info): 
                    ?>
                    <div class="category-upload-area <?php echo $first_cat ? '' : 'hidden'; ?>" id="upload_<?php echo $cat_key; ?>" 
                         style="<?php echo $first_cat ? '' : 'display: none;'; ?>">
                        <div class="image-upload-area" id="uploadArea_<?php echo $cat_key; ?>" 
                             style="border: 3px dashed #667eea; border-radius: 10px; padding: 30px; text-align: center; cursor: pointer; background: #f9fbff;">
                            <div style="font-size: 48px; opacity: 0.6;"><?php echo $cat_info['icon']; ?></div>
                            <p style="margin: 10px 0 5px; font-weight: 600; color: #374151;">Upload <?php echo $cat_info['name']; ?> Images</p>
                            <p style="color: #9ca3af; font-size: 12px;">Drag & drop or click to browse (Max 5 images, 5MB each)</p>
                            <input type="file" name="images_<?php echo $cat_key; ?>[]" multiple accept="image/*" 
                                   id="input_<?php echo $cat_key; ?>" style="display: none;">
                        </div>
                        <div class="preview-grid" id="preview_<?php echo $cat_key; ?>" 
                             style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 12px; margin-top: 15px;"></div>
                    </div>
                    <?php 
                    $first_cat = false;
                    endforeach; 
                    ?>
                    
                    <div class="info-box" style="margin-top: 15px;">
                        💡 <strong>Tip:</strong> High-quality images from different angles increase buyer interest by 60%
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <a href="<?php echo $back_url; ?>" class="cancel-btn">← Cancel</a>
                    <button type="submit" class="submit-btn">✓ Update Property</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Location Tab Switching
        const locationTabs = document.querySelectorAll('.location-tab');
        const autoContent = document.getElementById('autoLocationContent');
        const manualContent = document.getElementById('manualLocationContent');
        const locationMethodInput = document.getElementById('location_method');

        locationTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs
                locationTabs.forEach(t => t.classList.remove('active'));
                // Add active class to clicked tab
                this.classList.add('active');

                const tabType = this.dataset.tab;
                
                if (tabType === 'auto') {
                    autoContent.classList.remove('hidden');
                    manualContent.classList.add('hidden');
                    locationMethodInput.value = 'auto';
                } else {
                    autoContent.classList.add('hidden');
                    manualContent.classList.remove('hidden');
                    locationMethodInput.value = 'manual';
                }
            });
        });

        // Map variables
        let locationMap = null;
        let locationMarker = null;
        let autocomplete = null;
        
        // Default center (Mumbai) or existing property coordinates
        const defaultLat = <?php echo (!empty($property['latitude']) && $property['latitude'] != 0) ? $property['latitude'] : '19.0760'; ?>;
        const defaultLng = <?php echo (!empty($property['longitude']) && $property['longitude'] != 0) ? $property['longitude'] : '72.8777'; ?>;
        const hasExistingLocation = <?php echo (!empty($property['latitude']) && $property['latitude'] != 0) ? 'true' : 'false'; ?>;
        
        // Initialize the location selection map with Google Maps
        function initLocationMap() {
            // Check if map container exists
            const mapContainer = document.getElementById('locationMap');
            if (!mapContainer) {
                console.error('Map container not found');
                return;
            }
            
            try {
                // Create the Google Map
                locationMap = new google.maps.Map(mapContainer, {
                    center: { lat: defaultLat, lng: defaultLng },
                    zoom: hasExistingLocation ? 16 : 12,
                    mapTypeId: 'roadmap',
                    mapTypeControl: true,
                    streetViewControl: true,
                    fullscreenControl: true
                });
                
                // Add click handler to map
                locationMap.addListener('click', function(e) {
                    setMarkerPosition(e.latLng.lat(), e.latLng.lng());
                });
                
                // Initialize Places Autocomplete on the address search input
                const addressInput = document.getElementById('addressSearch');
                if (addressInput) {
                    autocomplete = new google.maps.places.Autocomplete(addressInput, {
                        componentRestrictions: { country: 'in' },
                        fields: ['geometry', 'formatted_address', 'name']
                    });
                    
                    autocomplete.addListener('place_changed', function() {
                        const place = autocomplete.getPlace();
                        if (place.geometry && place.geometry.location) {
                            const lat = place.geometry.location.lat();
                            const lng = place.geometry.location.lng();
                            setMarkerPosition(lat, lng);
                            
                            const statusDiv = document.getElementById('locationStatus');
                            statusDiv.innerHTML = '<span class="status-success">✅ <strong>Found:</strong> ' + place.formatted_address + '</span>';
                        }
                    });
                }
                
                // If property has existing coordinates, show marker
                if (hasExistingLocation) {
                    setMarkerPosition(defaultLat, defaultLng);
                }
                
                console.log('Google Maps initialized successfully');
            } catch (error) {
                console.error('Map initialization error:', error);
            }
        }
        
        // Set marker position on map
        function setMarkerPosition(lat, lng) {
            // Update hidden form fields
            document.getElementById('property_latitude').value = lat;
            document.getElementById('property_longitude').value = lng;
            
            // Update status
            const statusDiv = document.getElementById('locationStatus');
            statusDiv.innerHTML = '<span class="status-success">✅ <strong>Location set!</strong> Lat: ' + lat.toFixed(6) + ', Lng: ' + lng.toFixed(6) + '</span>';
            statusDiv.className = 'location-status success';
            
            // Remove existing marker if any
            if (locationMarker) {
                locationMarker.setMap(null);
            }
            
            // Add draggable marker
            locationMarker = new google.maps.Marker({
                position: { lat: lat, lng: lng },
                map: locationMap,
                draggable: true,
                animation: google.maps.Animation.DROP,
                icon: {
                    url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="50" viewBox="0 0 40 50">
                            <defs>
                                <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" style="stop-color:#667eea"/>
                                    <stop offset="100%" style="stop-color:#764ba2"/>
                                </linearGradient>
                            </defs>
                            <path d="M20 0C9 0 0 9 0 20c0 14 20 30 20 30s20-16 20-30C40 9 31 0 20 0z" fill="url(#grad)" stroke="white" stroke-width="2"/>
                            <circle cx="20" cy="18" r="8" fill="white"/>
                        </svg>
                    `),
                    scaledSize: new google.maps.Size(40, 50),
                    anchor: new google.maps.Point(20, 50)
                }
            });
            
            // Handle marker drag
            locationMarker.addListener('dragend', function() {
                const pos = locationMarker.getPosition();
                document.getElementById('property_latitude').value = pos.lat();
                document.getElementById('property_longitude').value = pos.lng();
                
                const statusDiv = document.getElementById('locationStatus');
                statusDiv.innerHTML = '<span class="status-success">✅ <strong>Location updated!</strong> Lat: ' + pos.lat().toFixed(6) + ', Lng: ' + pos.lng().toFixed(6) + '</span>';
            });
            
            // Center map on marker
            locationMap.setCenter({ lat: lat, lng: lng });
            locationMap.setZoom(16);
        }
        
        // Geocode address using Google Maps Geocoding
        async function geocodeAddress(address) {
            return new Promise((resolve, reject) => {
                const geocoder = new google.maps.Geocoder();
                geocoder.geocode({ address: address, region: 'in' }, function(results, status) {
                    if (status === 'OK' && results[0]) {
                        resolve({
                            lat: results[0].geometry.location.lat(),
                            lng: results[0].geometry.location.lng(),
                            display_name: results[0].formatted_address
                        });
                    } else {
                        resolve(null);
                    }
                });
            });
        }
        
        // Initialize map on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Wait for page to fully load before initializing map
            setTimeout(initLocationMap, 300);
        });
        
        // Also try to init when window loads (backup)
        window.addEventListener('load', function() {
            if (!locationMap) {
                setTimeout(initLocationMap, 500);
            }
        });
        
        // Detect current location button
        document.getElementById('detectLocationBtn').addEventListener('click', function(e) {
            e.preventDefault();
            const btn = this;
            const statusDiv = document.getElementById('locationStatus');
            
            btn.disabled = true;
            btn.innerHTML = '<span class="btn-icon">⏳</span> Detecting Location...';
            statusDiv.innerHTML = '🔄 Getting your current location...';
            statusDiv.className = 'location-status';
            statusDiv.style.background = '#fef3c7';
            statusDiv.style.color = '#92400e';
            
            // Function to show location error with instructions
            function showLocationError(message) {
                statusDiv.innerHTML = '❌ ' + message + '<br><br>' +
                    '<strong>Please use one of these options:</strong><br>' +
                    '• Use the address search box above to find the property location<br>' +
                    '• Or click directly on the map to place a marker';
                statusDiv.className = 'location-status error';
                statusDiv.style.textAlign = 'left';
                btn.disabled = false;
                btn.innerHTML = '<span class="btn-icon">📍</span> Detect My Current Location';
            }
            
            if (!navigator.geolocation) {
                showLocationError('Your browser does not support location detection.');
                return;
            }
            
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    
                    setMarkerPosition(lat, lng);
                    statusDiv.innerHTML = '✅ <strong>Device location detected!</strong> Lat: ' + lat.toFixed(6) + ', Lng: ' + lng.toFixed(6);
                    statusDiv.className = 'location-status success';
                    
                    btn.disabled = false;
                    btn.innerHTML = '<span class="btn-icon">📍</span> Detect My Current Location';
                },
                function(error) {
                    console.log('Geolocation error:', error.code, error.message);
                    let errorMsg = 'Could not detect your device location.';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMsg = 'Location permission denied. Please allow location access in your browser settings.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMsg = 'Location information is unavailable on this device.';
                            break;
                        case error.TIMEOUT:
                            errorMsg = 'Location request timed out. Please try again.';
                            break;
                    }
                    showLocationError(errorMsg);
                },
                { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
            );
        });
        
        // Category Image Tab Switching
        document.querySelectorAll('.image-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Update tab styles
                document.querySelectorAll('.image-tab').forEach(t => {
                    t.style.borderColor = '#e5e7eb';
                    t.style.background = 'white';
                    t.style.color = '#6b7280';
                    t.classList.remove('active');
                });
                this.style.borderColor = '#667eea';
                this.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                this.style.color = 'white';
                this.classList.add('active');
                
                // Show corresponding upload area
                const category = this.dataset.category;
                document.querySelectorAll('.category-upload-area').forEach(area => {
                    area.style.display = 'none';
                });
                document.getElementById('upload_' + category).style.display = 'block';
            });
        });
        
        // Initialize drag-and-drop for each category
        <?php foreach ($image_categories as $cat_key => $cat_info): ?>
        (function() {
            const catKey = '<?php echo $cat_key; ?>';
            const uploadArea = document.getElementById('uploadArea_' + catKey);
            const input = document.getElementById('input_' + catKey);
            const preview = document.getElementById('preview_' + catKey);
            
            if (!uploadArea || !input || !preview) return;
            
            uploadArea.addEventListener('click', () => input.click());
            
            uploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadArea.style.borderColor = '#764ba2';
                uploadArea.style.background = '#f0f4ff';
            });
            
            uploadArea.addEventListener('dragleave', () => {
                uploadArea.style.borderColor = '#667eea';
                uploadArea.style.background = '#f9fbff';
            });
            
            uploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadArea.style.borderColor = '#667eea';
                uploadArea.style.background = '#f9fbff';
                
                const dt = new DataTransfer();
                [...e.dataTransfer.files].slice(0, 5).forEach(file => {
                    if (file.type.startsWith('image/') && file.size <= 5 * 1024 * 1024) {
                        dt.items.add(file);
                    }
                });
                input.files = dt.files;
                handleCategoryPreview(catKey, input, preview);
            });
            
            input.addEventListener('change', () => handleCategoryPreview(catKey, input, preview));
        })();
        <?php endforeach; ?>
        
        function handleCategoryPreview(category, input, preview) {
            preview.innerHTML = '';
            const files = input.files;
            
            if (files.length > 5) {
                alert('Maximum 5 images allowed per category');
                return;
            }
            
            [...files].forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const container = document.createElement('div');
                    container.style.cssText = 'position: relative; border-radius: 8px; overflow: hidden;';
                    
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.cssText = 'width: 100%; height: 80px; object-fit: cover;';
                    
                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.textContent = '×';
                    removeBtn.style.cssText = 'position: absolute; top: 4px; right: 4px; background: #dc2626; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; font-weight: bold;';
                    removeBtn.addEventListener('click', () => {
                        container.remove();
                        // Remove from file input
                        const dt = new DataTransfer();
                        [...input.files].forEach((f, i) => {
                            if (i !== index) dt.items.add(f);
                        });
                        input.files = dt.files;
                    });
                    
                    container.appendChild(img);
                    container.appendChild(removeBtn);
                    preview.appendChild(container);
                };
                reader.readAsDataURL(file);
            });
        }

        // Image upload with drag and drop (legacy support)
        const uploadArea = document.getElementById('uploadArea');
        const imageInput = document.getElementById('image-input');
        const imagePreview = document.getElementById('image-preview');

        if (uploadArea && imageInput) {
            uploadArea.addEventListener('click', () => imageInput.click());

            uploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadArea.style.borderColor = '#667eea';
                uploadArea.style.background = '#f0f4ff';
            });

            uploadArea.addEventListener('dragleave', () => {
                uploadArea.style.borderColor = '#667eea';
                uploadArea.style.background = '#f9fbff';
            });

            uploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadArea.style.borderColor = '#667eea';
                uploadArea.style.background = '#f9fbff';
                imageInput.files = e.dataTransfer.files;
                handleImagePreview();
            });

            imageInput.addEventListener('change', handleImagePreview);
        }

        function handleImagePreview() {
            if (!imagePreview) return;
            imagePreview.innerHTML = '';
            const files = imageInput.files;

            if (files.length > 10) {
                alert('Maximum 10 images allowed');
                return;
            }

            [...files].forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const container = document.createElement('div');
                    container.className = 'preview-img-container';

                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'preview-img';

                    const removeBtn = document.createElement('button');
                    removeBtn.className = 'remove-img-btn';
                    removeBtn.type = 'button';
                    removeBtn.textContent = '×';
                    removeBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        container.remove();
                    });

                    container.appendChild(img);
                    container.appendChild(removeBtn);
                    imagePreview.appendChild(container);
                };
                reader.readAsDataURL(file);
            });
        }

        // Toggle commercial specs visibility based on property type or category
        function toggleCommercialSpecs() {
            const propertyType = document.getElementById('property_type').value;
            const category = document.querySelector('select[name="category"]').value;
            const commercialTypes = ['commercial'];
            const commercialCategories = ['shop', 'office'];
            const commercialSpecs = document.querySelectorAll('.commercial-specs');
            
            if (commercialTypes.includes(propertyType) || commercialCategories.includes(category)) {
                commercialSpecs.forEach(el => el.style.display = 'flex');
            } else {
                commercialSpecs.forEach(el => el.style.display = 'none');
            }
        }
        
        // Call on page load and when category changes
        document.addEventListener('DOMContentLoaded', function() {
            toggleCommercialSpecs();
            document.querySelector('select[name="category"]').addEventListener('change', toggleCommercialSpecs);
        });
    </script>
    
    <?php include('includes/footer.php'); ?>
</body>

</html>
