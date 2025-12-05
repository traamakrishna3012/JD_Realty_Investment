<?php
include('includes/config.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

// Create uploads directory if it doesn't exist
$upload_dir = 'uploads/properties/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

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

// Fetch amenities from database
$amenities = [];
$amenities_sql = "SELECT * FROM amenities ORDER BY category, name";
$amenities_result = $conn->query($amenities_sql);
if ($amenities_result && $amenities_result->num_rows > 0) {
    while ($row = $amenities_result->fetch_assoc()) {
        // Use PHP icon mapping as fallback if database icon is missing or corrupted
        $icon = isset($row['icon']) ? trim($row['icon']) : '';
        if (empty($icon) || $icon === '?' || strlen($icon) > 20) {
            $row['icon'] = isset($amenity_icons[$row['name']]) ? $amenity_icons[$row['name']] : '✓';
        }
        $amenities[$row['category']][] = $row;
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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);
    $property_type = $conn->real_escape_string($_POST['property_type']);
    $category = !empty($_POST['category']) ? $conn->real_escape_string($_POST['category']) : NULL;
    $city = $conn->real_escape_string($_POST['city']);
    $address = !empty($_POST['address']) ? $conn->real_escape_string($_POST['address']) : NULL;
    $landmark = !empty($_POST['landmark']) ? $conn->real_escape_string($_POST['landmark']) : NULL;
    $price = floatval($_POST['price']);
    $area_sqft = floatval($_POST['area_sqft']);
    $bedrooms = !empty($_POST['bedrooms']) ? intval($_POST['bedrooms']) : NULL;
    $bathrooms = !empty($_POST['bathrooms']) ? intval($_POST['bathrooms']) : NULL;
    $furnishing_status = !empty($_POST['furnishing_status']) ? $conn->real_escape_string($_POST['furnishing_status']) : 'unfurnished';
    $possession_status = !empty($_POST['possession_status']) ? $conn->real_escape_string($_POST['possession_status']) : 'ready_to_move';
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
    
    $user_id = $_SESSION['user_id'];
    $image_url = NULL;
    
    // Location handling
    $location_method = $_POST['location_method'] ?? 'manual';
    $latitude = 0;
    $longitude = 0;
    
    if ($location_method === 'auto' && !empty($_POST['detected_latitude']) && !empty($_POST['detected_longitude'])) {
        $latitude = floatval($_POST['detected_latitude']);
        $longitude = floatval($_POST['detected_longitude']);
    } elseif (!empty($_POST['latitude']) && !empty($_POST['longitude'])) {
        $latitude = floatval($_POST['latitude']);
        $longitude = floatval($_POST['longitude']);
    }

    // Validation
    if (empty($title) || empty($description) || empty($property_type) || empty($city) || empty($price) || empty($area_sqft)) {
        $error = "All required fields must be filled!";
    } elseif ($price <= 0 || $area_sqft <= 0) {
        $error = "Price and area must be greater than 0!";
    } else {
        // Handle featured image upload
        if (!empty($_FILES['featured_image']['name'])) {
            $file = $_FILES['featured_image'];
            $file_name = $file['name'];
            $file_size = $file['size'];
            $file_tmp = $file['tmp_name'];
            $file_type = $file['type'];

            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 5 * 1024 * 1024;

            if (!in_array($file_type, $allowed_types)) {
                $error = "Only image files (JPG, PNG, GIF, WebP) are allowed!";
            } elseif ($file_size > $max_size) {
                $error = "File size must be less than 5MB!";
            } else {
                $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
                $unique_name = 'property_' . time() . '_' . uniqid() . '.' . $file_ext;
                $upload_path = $upload_dir . $unique_name;

                if (move_uploaded_file($file_tmp, $upload_path)) {
                    $image_url = $upload_path;
                } else {
                    $error = "Failed to upload image. Please try again.";
                }
            }
        }

        if (empty($error)) {
            // Insert property with pending approval status
            $building_name = $conn->real_escape_string($_POST['building_name'] ?? '');
            $building_name_value = $building_name ? "'$building_name'" : "NULL";
            
            $sql = "INSERT INTO properties (title, building_name, description, property_type, category, city, address, landmark, latitude, longitude, price, area_sqft, bedrooms, bathrooms, furnishing_status, possession_status, total_floors, floor_number, age_of_property, image_url, created_by, status, approval_status, pre_lease, possession_date, workstations, cabins, conference_rooms, meeting_rooms, pantry) 
                    VALUES ('$title', $building_name_value, '$description', '$property_type', " . ($category ? "'$category'" : "NULL") . ", '$city', " . ($address ? "'$address'" : "NULL") . ", " . ($landmark ? "'$landmark'" : "NULL") . ", $latitude, $longitude, $price, $area_sqft, " . ($bedrooms ? $bedrooms : "NULL") . ", " . ($bathrooms ? $bathrooms : "NULL") . ", '$furnishing_status', '$possession_status', " . ($total_floors ? $total_floors : "NULL") . ", " . ($floor_number ? $floor_number : "NULL") . ", " . ($age_of_property ? "'$age_of_property'" : "NULL") . ", " . ($image_url ? "'$image_url'" : "NULL") . ", $user_id, 'available', 'pending', '$pre_lease', " . ($possession_date ? "'$possession_date'" : "NULL") . ", $workstations, $cabins, $conference_rooms, $meeting_rooms, '$pantry')";

            if ($conn->query($sql) === TRUE) {
                $property_id = $conn->insert_id;
                
                // Insert amenities
                if (!empty($_POST['amenities'])) {
                    foreach ($_POST['amenities'] as $amenity_id) {
                        $amenity_id = intval($amenity_id);
                        $conn->query("INSERT INTO property_amenities (property_id, amenity_id) VALUES ($property_id, $amenity_id)");
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
                                    $upload_path = $upload_dir . $unique_name;
                                    
                                    if (move_uploaded_file($file_tmp, $upload_path)) {
                                        $escaped_path = $conn->real_escape_string($upload_path);
                                        $conn->query("INSERT INTO property_images (property_id, image_url, image_category, display_order) VALUES ($property_id, '$escaped_path', '$cat_key', $i)");
                                    }
                                }
                            }
                        }
                    }
                }
                
                $success = "Property submitted successfully! Your property is pending admin approval and will be visible once approved.";
                $_POST = [];
            } else {
                $error = "Error listing property: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="List your property on JD Realty & Investment for free. Reach thousands of buyers and sellers. Easy property listing process.">
    <meta name="keywords" content="list property, sell property, property listing, real estate, post property">
    <meta name="robots" content="noindex, follow">
    <title>List Your Property - JD Realty & Investment</title>
    <link rel="stylesheet" href="css/style.css?v=20251203c">
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
        }        .logout-btn {
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
        
        .form-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        
        .form-title {
            font-size: 32px;
            margin-bottom: 10px;
            color: #1f2937;
        }
        
        .form-subtitle {
            color: #6b7280;
            margin-bottom: 30px;
            font-size: 16px;
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
            width: 28px;
            height: 28px;
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
            gap: 20px;
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
        input[type="email"],
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s ease;
        }
        
        input[type="text"]:focus,
        input[type="number"]:focus,
        input[type="email"]:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        
        textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .info-box {
            background: #dbeafe;
            border-left: 4px solid #3b82f6;
            padding: 10px 12px;
            border-radius: 6px;
            color: #0c2d48;
            font-size: 13px;
            margin-top: 8px;
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
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Amenities Section */
        .amenities-category {
            margin-bottom: 25px;
        }
        
        .amenities-category h4 {
            font-size: 15px;
            color: #374151;
            margin-bottom: 12px;
            text-transform: capitalize;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .amenities-category h4::before {
            content: '';
            width: 4px;
            height: 18px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 2px;
        }
        
        .amenities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 10px;
        }
        
        .amenity-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }
        
        .amenity-item:hover {
            border-color: #667eea;
            background: #f9fbff;
        }
        
        .amenity-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #667eea;
        }
        
        .amenity-item:has(input[type="checkbox"]:checked) {
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
            border-color: #667eea;
        }
        
        .amenity-item label {
            margin: 0;
            cursor: pointer;
            font-weight: 500;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .amenity-icon {
            font-size: 16px;
        }
        
        /* Image Upload Categories */
        .image-categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .image-category-card {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 15px;
            background: white;
            transition: all 0.3s ease;
        }
        
        .image-category-card:hover {
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
        }
        
        .image-category-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }
        
        .category-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        .category-info h4 {
            font-size: 14px;
            color: #1f2937;
            margin: 0;
        }
        
        .category-info small {
            font-size: 11px;
            color: #6b7280;
        }
        
        .category-upload-area {
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f9fafb;
        }
        
        .category-upload-area:hover {
            border-color: #667eea;
            background: #f0f4ff;
        }
        
        .category-upload-area p {
            margin: 0;
            font-size: 12px;
            color: #6b7280;
        }
        
        .category-upload-area .upload-icon {
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .category-preview {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        
        .category-preview img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
            border: 2px solid #e5e7eb;
        }
        
        /* Featured Image */
        .featured-upload {
            border: 3px dashed #667eea;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: linear-gradient(135deg, #667eea08 0%, #764ba208 100%);
        }
        
        .featured-upload:hover {
            border-color: #764ba2;
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
        }
        
        .featured-upload .upload-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        
        .featured-upload p {
            margin: 5px 0;
            color: #6b7280;
        }
        
        .featured-upload .primary-text {
            font-weight: 600;
            color: #1f2937;
            font-size: 16px;
        }
        
        #featured-preview {
            margin-top: 15px;
        }
        
        #featured-preview img {
            max-width: 200px;
            max-height: 150px;
            object-fit: cover;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .error-message {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #dc2626;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .success-message {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #06b6d4;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .submit-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        
        .back-link:hover {
            color: #4f46e5;
        }
        
        .footer {
            background: #1f2937;
            color: white;
            padding: 40px;
            text-align: center;
            margin-top: 60px;
        }
        
        @media (max-width: 768px) {
            .form-row, .form-row.three-col {
                grid-template-columns: 1fr;
            }
            
            .form-container {
                padding: 20px;
            }
            
            .image-categories-grid {
                grid-template-columns: 1fr;
            }
            
            .amenities-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="index.php" class="logo" style="text-decoration: none; display: flex; align-items: center; gap: 12px;">
            <img src="images/jd-logo.svg?v=20251203" alt="JD Realty & Investment" style="height: 60px; width: 60px;">
            <span style="font-size: 24px; font-weight: bold; color: #d4a84b; text-shadow: 1px 1px 2px rgba(0,0,0,0.3);">JD Realty Investment</span>
        </a>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="user-dashboard.php">My Properties</a>
            <a href="includes/logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="form-container">
            <h1 class="form-title">📝 List Your Property</h1>
            <p class="form-subtitle">Fill in the details below to get your property in front of thousands of potential buyers</p>
            
            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <span>⚠️</span> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="success-message">
                    <span>✓</span> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <!-- Section 1: Basic Details -->
                <div class="form-section">
                    <h3><span class="section-icon">1</span> Basic Details</h3>
                    
                    <div class="form-group">
                        <label for="title">Property Title <span class="required">*</span></label>
                        <input type="text" id="title" name="title" required placeholder="e.g., Premium 2BHK Apartment in Thane" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                        <div class="info-box">💡 Use a compelling title that highlights key features</div>
                    </div>

                    <div class="form-group">
                        <label for="building_name">Building Name (Optional)</label>
                        <input type="text" id="building_name" name="building_name" placeholder="e.g., Mumbai Heights, Lodha Park" value="<?php echo isset($_POST['building_name']) ? htmlspecialchars($_POST['building_name']) : ''; ?>">
                        <div class="info-box">💡 Enter the building name for apartment complexes or societies</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description <span class="required">*</span></label>
                        <textarea id="description" name="description" required placeholder="Describe your property in detail. Mention unique features, amenities, nearby places, connectivity..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    </div>
                </div>
                
                <!-- Section 2: Property Specifications -->
                <div class="form-section">
                    <h3><span class="section-icon">2</span> Property Specifications</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="property_type">Property Type <span class="required">*</span></label>
                            <select id="property_type" name="property_type" required>
                                <option value="">Select Type</option>
                                <option value="residential" <?php echo isset($_POST['property_type']) && $_POST['property_type'] == 'residential' ? 'selected' : ''; ?>>Residential</option>
                                <option value="commercial" <?php echo isset($_POST['property_type']) && $_POST['property_type'] == 'commercial' ? 'selected' : ''; ?>>Commercial</option>
                                <option value="plot" <?php echo isset($_POST['property_type']) && $_POST['property_type'] == 'plot' ? 'selected' : ''; ?>>Plot</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="category">Category</label>
                            <select id="category" name="category">
                                <option value="">Select Category</option>
                                <option value="1rk" <?php echo isset($_POST['category']) && $_POST['category'] == '1rk' ? 'selected' : ''; ?>>1 RK</option>
                                <option value="1bhk" <?php echo isset($_POST['category']) && $_POST['category'] == '1bhk' ? 'selected' : ''; ?>>1 BHK</option>
                                <option value="2bhk" <?php echo isset($_POST['category']) && $_POST['category'] == '2bhk' ? 'selected' : ''; ?>>2 BHK</option>
                                <option value="3bhk" <?php echo isset($_POST['category']) && $_POST['category'] == '3bhk' ? 'selected' : ''; ?>>3 BHK</option>
                                <option value="4bhk" <?php echo isset($_POST['category']) && $_POST['category'] == '4bhk' ? 'selected' : ''; ?>>4 BHK</option>
                                <option value="above4" <?php echo isset($_POST['category']) && $_POST['category'] == 'above4' ? 'selected' : ''; ?>>Above 4 BHK</option>
                                <option value="shop" <?php echo isset($_POST['category']) && $_POST['category'] == 'shop' ? 'selected' : ''; ?>>Shop</option>
                                <option value="office" <?php echo isset($_POST['category']) && $_POST['category'] == 'office' ? 'selected' : ''; ?>>Office</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="furnishing_status">Furnishing Status</label>
                            <select id="furnishing_status" name="furnishing_status">
                                <option value="unfurnished">Unfurnished</option>
                                <option value="semi-furnished">Semi-Furnished</option>
                                <option value="fully-furnished">Fully Furnished</option>
                                <option value="bareshell">Bareshell</option>
                                <option value="warmshell">Warmshell</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="possession_status">Possession Status</label>
                            <select id="possession_status" name="possession_status">
                                <option value="ready_to_move">Ready to Move</option>
                                <option value="under_construction">Under Construction</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="pre_lease">Pre-Lease</label>
                            <select id="pre_lease" name="pre_lease">
                                <option value="no">No</option>
                                <option value="yes">Yes</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="possession_date">Possession Date</label>
                            <input type="date" id="possession_date" name="possession_date" value="<?php echo isset($_POST['possession_date']) ? htmlspecialchars($_POST['possession_date']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="bedrooms">Bedrooms</label>
                            <input type="number" id="bedrooms" name="bedrooms" placeholder="e.g., 2" min="1" value="<?php echo isset($_POST['bedrooms']) ? htmlspecialchars($_POST['bedrooms']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="bathrooms">Bathrooms</label>
                            <input type="number" id="bathrooms" name="bathrooms" placeholder="e.g., 2" min="1" value="<?php echo isset($_POST['bathrooms']) ? htmlspecialchars($_POST['bathrooms']) : ''; ?>">
                        </div>
                    </div>
                    
                    <!-- Commercial Property Specs (for shop/office/commercial) -->
                    <div class="form-row commercial-specs" id="commercial-specs" style="display: none;">
                        <div class="form-group">
                            <label for="workstations">Workstations</label>
                            <input type="number" id="workstations" name="workstations" placeholder="e.g., 20" min="0" value="<?php echo isset($_POST['workstations']) ? htmlspecialchars($_POST['workstations']) : '0'; ?>">
                        </div>
                        <div class="form-group">
                            <label for="cabins">Cabins</label>
                            <input type="number" id="cabins" name="cabins" placeholder="e.g., 5" min="0" value="<?php echo isset($_POST['cabins']) ? htmlspecialchars($_POST['cabins']) : '0'; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row commercial-specs" id="commercial-specs-2" style="display: none;">
                        <div class="form-group">
                            <label for="conference_rooms">Conference Rooms</label>
                            <input type="number" id="conference_rooms" name="conference_rooms" placeholder="e.g., 2" min="0" value="<?php echo isset($_POST['conference_rooms']) ? htmlspecialchars($_POST['conference_rooms']) : '0'; ?>">
                        </div>
                        <div class="form-group">
                            <label for="meeting_rooms">Meeting Rooms</label>
                            <input type="number" id="meeting_rooms" name="meeting_rooms" placeholder="e.g., 3" min="0" value="<?php echo isset($_POST['meeting_rooms']) ? htmlspecialchars($_POST['meeting_rooms']) : '0'; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row commercial-specs" id="commercial-specs-3" style="display: none;">
                        <div class="form-group">
                            <label for="pantry">Pantry</label>
                            <select id="pantry" name="pantry">
                                <option value="no" <?php echo isset($_POST['pantry']) && $_POST['pantry'] == 'no' ? 'selected' : ''; ?>>No</option>
                                <option value="yes" <?php echo isset($_POST['pantry']) && $_POST['pantry'] == 'yes' ? 'selected' : ''; ?>>Yes</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <!-- Placeholder for alignment -->
                        </div>
                    </div>
                    
                    <div class="form-row three-col">
                        <div class="form-group">
                            <label for="total_floors">Total Floors</label>
                            <input type="number" id="total_floors" name="total_floors" placeholder="e.g., 20" min="1" value="<?php echo isset($_POST['total_floors']) ? htmlspecialchars($_POST['total_floors']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="floor_number">Floor Number</label>
                            <input type="number" id="floor_number" name="floor_number" placeholder="e.g., 5" min="0" value="<?php echo isset($_POST['floor_number']) ? htmlspecialchars($_POST['floor_number']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="age_of_property">Property Age</label>
                            <select id="age_of_property" name="age_of_property">
                                <option value="">Select Age</option>
                                <option value="New Construction">New Construction</option>
                                <option value="Less than 5 years">Less than 5 years</option>
                                <option value="5 to 10 years">5 to 10 years</option>
                                <option value="More than 10 years">More than 10 years</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Section 3: Location & Pricing -->
                <div class="form-section">
                    <h3><span class="section-icon">3</span> Location & Pricing</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="city">City <span class="required">*</span></label>
                            <input type="text" id="city" name="city" required placeholder="e.g., Thane" value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="landmark">Landmark</label>
                            <input type="text" id="landmark" name="landmark" placeholder="e.g., Near Viviana Mall" value="<?php echo isset($_POST['landmark']) ? htmlspecialchars($_POST['landmark']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Full Address</label>
                        <textarea id="address" name="address" placeholder="Enter complete address with building name, street, area..." style="min-height: 80px;"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                    </div>
                    
                    <!-- Location Selection on Map -->
                    <div class="form-group">
                        <label>📍 Property Location on Map</label>
                        <input type="hidden" name="location_method" id="location_method" value="manual">
                        <input type="hidden" name="latitude" id="property_latitude">
                        <input type="hidden" name="longitude" id="property_longitude">
                        
                        <div class="map-selection-section" style="margin-top: 15px;">
                            <!-- Search by Address - Primary Method -->
                            <div style="margin-bottom: 15px; background: #f0fdf4; padding: 15px; border-radius: 10px; border: 2px solid #86efac;">
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #166534;">🔍 Search Property Location by Address</label>
                                <input type="text" id="addressSearch" placeholder="Type property address (e.g., Hiranandani Estate, Thane West)" style="width: 100%; padding: 14px; font-size: 15px; border: 2px solid #86efac; border-radius: 8px;">
                                <p style="font-size: 12px; color: #6b7280; margin-top: 8px;">Start typing and select from suggestions, or enter full address</p>
                            </div>
                            
                            <div class="location-status" id="locationStatus"></div>
                            
                            <!-- Interactive Map for Location Selection -->
                            <div id="locationMapContainer" style="margin-top: 15px;">
                                <div id="locationMap" style="width: 100%; height: 400px; border-radius: 10px; border: 2px solid #e5e7eb;"></div>
                                <p style="font-size: 13px; color: #6b7280; margin-top: 10px; text-align: center; background: #f0f9ff; padding: 10px; border-radius: 8px;">
                                    📍 <strong>Click on the map</strong> to set exact property location or <strong>drag the marker</strong> to adjust
                                </p>
                            </div>
                            
                            <div class="info-box" style="margin-top: 15px;">
                                <strong>💡 How to set property location:</strong><br>
                                1. Type the property address in the search box above<br>
                                2. Select from the dropdown suggestions<br>
                                3. Fine-tune by clicking on map or dragging the marker<br>
                                <span style="color: #dc2626; font-size: 12px;">⚠️ "Detect Current Location" uses YOUR device location, not the property's</span>
                            </div>
                            
                            <button type="button" class="auto-detect-btn" id="detectLocationBtn" style="margin-top: 10px; background: #6b7280; font-size: 13px; padding: 8px 15px;">
                                📍 Use My Current Location (only if you're at the property)
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="price">Price (₹) <span class="required">*</span></label>
                            <input type="number" id="price" name="price" required placeholder="e.g., 7500000" step="1" value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="area_sqft">Area (sq ft) <span class="required">*</span></label>
                            <input type="number" id="area_sqft" name="area_sqft" required placeholder="e.g., 950" step="0.01" value="<?php echo isset($_POST['area_sqft']) ? htmlspecialchars($_POST['area_sqft']) : ''; ?>">
                        </div>
                    </div>
                </div>
                
                <!-- Section 4: Amenities -->
                <div class="form-section">
                    <h3><span class="section-icon">4</span> Amenities</h3>
                    
                    <?php if (!empty($amenities)): ?>
                        <?php 
                        $category_names = [
                            'basic' => '🏠 Basic Amenities',
                            'safety' => '🛡️ Safety & Security',
                            'convenience' => '🛎️ Convenience',
                            'recreation' => '🎯 Recreation & Sports',
                            'luxury' => '✨ Luxury Features'
                        ];
                        foreach ($amenities as $category => $items): 
                        ?>
                            <div class="amenities-category">
                                <h4><?php echo $category_names[$category] ?? ucfirst($category); ?></h4>
                                <div class="amenities-grid">
                                    <?php foreach ($items as $amenity): ?>
                                        <div class="amenity-item">
                                            <input type="checkbox" name="amenities[]" value="<?php echo $amenity['id']; ?>" 
                                                id="amenity_<?php echo $amenity['id']; ?>"
                                                <?php echo (isset($_POST['amenities']) && in_array($amenity['id'], $_POST['amenities'])) ? 'checked' : ''; ?>>
                                            <label for="amenity_<?php echo $amenity['id']; ?>">
                                                <span class="amenity-icon"><?php echo $amenity['icon'] ?? '✓'; ?></span>
                                                <?php echo htmlspecialchars($amenity['name']); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="info-box">
                            ℹ️ Amenities feature is being set up. Please run the database migration to add amenities.
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Section 5: Property Images -->
                <div class="form-section">
                    <h3><span class="section-icon">5</span> Property Images</h3>
                    
                    <!-- Featured Image -->
                    <div class="form-group">
                        <label>Featured Image (Main Photo)</label>
                        <div class="featured-upload" onclick="document.getElementById('featured_image').click()">
                            <div class="upload-icon">📸</div>
                            <p class="primary-text">Click to upload featured image</p>
                            <p>This will be shown as the main image in listings</p>
                            <p style="font-size: 11px;">JPG, PNG, WebP (Max 5MB)</p>
                            <input type="file" id="featured_image" name="featured_image" accept="image/*" style="display: none;">
                        </div>
                        <div id="featured-preview"></div>
                    </div>
                    
                    <!-- Category-wise Images -->
                    <div class="form-group">
                        <label>Category-wise Images (Optional)</label>
                        <p style="color: #6b7280; font-size: 13px; margin-bottom: 15px;">Upload images by category for better organization. Max 5 images per category.</p>
                        
                        <div class="image-categories-grid">
                            <?php foreach ($image_categories as $cat_key => $cat_info): ?>
                                <div class="image-category-card">
                                    <div class="image-category-header">
                                        <div class="category-icon"><?php echo $cat_info['icon']; ?></div>
                                        <div class="category-info">
                                            <h4><?php echo $cat_info['name']; ?></h4>
                                            <small>Max 5 images</small>
                                        </div>
                                    </div>
                                    <div class="category-upload-area" onclick="document.getElementById('images_<?php echo $cat_key; ?>').click()">
                                        <div class="upload-icon">➕</div>
                                        <p>Click to add images</p>
                                        <input type="file" id="images_<?php echo $cat_key; ?>" name="images_<?php echo $cat_key; ?>[]" accept="image/*" multiple style="display: none;" onchange="previewCategoryImages(this, '<?php echo $cat_key; ?>')">
                                    </div>
                                    <div class="category-preview" id="preview_<?php echo $cat_key; ?>"></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <button type="submit" class="submit-btn">
                    ✓ List Property
                </button>
                <a href="user-dashboard.php" class="back-link">← Back to My Properties</a>
            </form>
        </div>
    </div>
    
    <?php include('includes/footer.php'); ?>
    
    <!-- Google Maps API for Location Selection -->
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAybBsnJ4hUUBVtOg1kfZ6FPBd9FahRGgo&libraries=places"></script>
    
    <script>
        // Toggle commercial specs visibility based on property type or category
        function toggleCommercialSpecs() {
            const propertyType = document.getElementById('property_type').value;
            const category = document.getElementById('category').value;
            const commercialTypes = ['commercial'];
            const commercialCategories = ['shop', 'office'];
            const commercialSpecs = document.querySelectorAll('.commercial-specs');
            
            if (commercialTypes.includes(propertyType) || commercialCategories.includes(category)) {
                commercialSpecs.forEach(el => el.style.display = 'flex');
            } else {
                commercialSpecs.forEach(el => el.style.display = 'none');
            }
        }
        
        // Call on page load and when property type or category changes
        document.addEventListener('DOMContentLoaded', function() {
            toggleCommercialSpecs();
            document.getElementById('property_type').addEventListener('change', toggleCommercialSpecs);
            document.getElementById('category').addEventListener('change', toggleCommercialSpecs);
            
            // Initialize the location map with delay
            setTimeout(initLocationMap, 300);
        });
        
        // Also try to init when window loads (backup)
        window.addEventListener('load', function() {
            if (!locationMap) {
                setTimeout(initLocationMap, 500);
            }
        });
        
        // Map variables
        let locationMap = null;
        let locationMarker = null;
        let autocomplete = null;
        
        // Default center (Mumbai)
        const defaultLat = 19.0760;
        const defaultLng = 72.8777;
        
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
                    zoom: 12,
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
                autocomplete = new google.maps.places.Autocomplete(addressInput, {
                    componentRestrictions: { country: 'in' },
                    fields: ['geometry', 'formatted_address', 'name']
                });
                
                // When a place is selected from autocomplete
                autocomplete.addListener('place_changed', function() {
                    const place = autocomplete.getPlace();
                    if (place.geometry && place.geometry.location) {
                        const lat = place.geometry.location.lat();
                        const lng = place.geometry.location.lng();
                        setMarkerPosition(lat, lng);
                        
                        const statusDiv = document.getElementById('locationStatus');
                        statusDiv.innerHTML = '✅ <strong>Found:</strong> ' + place.formatted_address;
                        statusDiv.className = 'location-status success';
                    }
                });
                
                // Also add autocomplete to the main address textarea
                const mainAddressInput = document.getElementById('address');
                if (mainAddressInput) {
                    const mainAutocomplete = new google.maps.places.Autocomplete(mainAddressInput, {
                        componentRestrictions: { country: 'in' },
                        fields: ['geometry', 'formatted_address', 'name', 'address_components']
                    });
                    
                    mainAutocomplete.addListener('place_changed', function() {
                        const place = mainAutocomplete.getPlace();
                        if (place.geometry && place.geometry.location) {
                            const lat = place.geometry.location.lat();
                            const lng = place.geometry.location.lng();
                            setMarkerPosition(lat, lng);
                            
                            // Try to auto-fill city
                            if (place.address_components) {
                                for (const component of place.address_components) {
                                    if (component.types.includes('locality')) {
                                        const citySelect = document.getElementById('city');
                                        const cityName = component.long_name;
                                        // Try to match with existing options
                                        for (let i = 0; i < citySelect.options.length; i++) {
                                            if (citySelect.options[i].value.toLowerCase() === cityName.toLowerCase()) {
                                                citySelect.selectedIndex = i;
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    });
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
            statusDiv.innerHTML = '✅ <strong>Location set!</strong> Lat: ' + lat.toFixed(6) + ', Lng: ' + lng.toFixed(6);
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
                statusDiv.innerHTML = '✅ <strong>Location updated!</strong> Lat: ' + pos.lat().toFixed(6) + ', Lng: ' + pos.lng().toFixed(6);
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
        
        // Detect current location button
        document.getElementById('detectLocationBtn').addEventListener('click', function(e) {
            e.preventDefault();
            const btn = this;
            const statusDiv = document.getElementById('locationStatus');
            
            btn.disabled = true;
            btn.innerHTML = '⏳ Detecting Location...';
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
                btn.innerHTML = '📍 Detect My Current Location';
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
                    btn.innerHTML = '📍 Detect My Current Location';
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
        
        // Featured image preview
        document.getElementById('featured_image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const previewDiv = document.getElementById('featured-preview');
            previewDiv.innerHTML = '';
            
            if (file) {
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB!');
                    e.target.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(event) {
                    const img = document.createElement('img');
                    img.src = event.target.result;
                    previewDiv.appendChild(img);
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Category images preview
        function previewCategoryImages(input, category) {
            const previewDiv = document.getElementById('preview_' + category);
            previewDiv.innerHTML = '';
            
            const files = input.files;
            if (files.length > 5) {
                alert('Maximum 5 images allowed per category!');
                input.value = '';
                return;
            }
            
            [...files].forEach((file, index) => {
                if (file.size > 5 * 1024 * 1024) {
                    alert('Each file must be less than 5MB!');
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(event) {
                    const img = document.createElement('img');
                    img.src = event.target.result;
                    previewDiv.appendChild(img);
                };
                reader.readAsDataURL(file);
            });
        }
    </script>
</body>
</html>
