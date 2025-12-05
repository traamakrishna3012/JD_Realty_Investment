<?php
include('includes/config.php');

// Get property ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$property_id = intval($_GET['id']);

// Get property details - optimized query
$sql = "SELECT p.*, u.name as owner_name, u.phone as owner_phone, u.email as owner_email 
        FROM properties p 
        LEFT JOIN users u ON p.created_by = u.id 
        WHERE p.id=$property_id LIMIT 1";
$result = $conn->query($sql);

if (!$result || $result->num_rows == 0) {
    header("Location: index.php");
    exit();
}

$property = $result->fetch_assoc();

// Fetch amenities for this property
$amenities = [];
$amenities_query = "SELECT a.id, a.name, a.icon, a.category FROM property_amenities pa 
                   JOIN amenities a ON pa.amenity_id = a.id 
                   WHERE pa.property_id = $property_id 
                   ORDER BY a.category, a.name";
$amenities_result = $conn->query($amenities_query);
if ($amenities_result) {
    while ($row = $amenities_result->fetch_assoc()) {
        if (!isset($amenities[$row['category']])) {
            $amenities[$row['category']] = [];
        }
        $amenities[$row['category']][] = $row;
    }
}

// Fetch all property images - optimized
$images_result = $conn->query("SELECT image_url, image_category, display_order FROM property_images WHERE property_id = $property_id ORDER BY image_category, display_order ASC");
$property_images = $images_result ? $images_result->fetch_all(MYSQLI_ASSOC) : [];

// Image category names for display
$image_category_names = [
    'exterior' => 'Exterior/Building',
    'interior' => 'Interior Overview',
    'bedroom' => 'Bedroom',
    'bathroom' => 'Bathroom',
    'kitchen' => 'Kitchen',
    'living_room' => 'Living Room',
    'balcony' => 'Balcony/Terrace',
    'parking' => 'Parking',
    'amenities' => 'Amenities',
    'floor_plan' => 'Floor Plan',
    'other' => 'Other'
];

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);

// Handle like/favorite toggle (AJAX will use this)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_favorite'])) {
    header('Content-Type: application/json');
    if (!$is_logged_in) {
        echo json_encode(['success' => false, 'message' => 'Please login to like properties']);
        exit();
    }
    
    $user_id = $_SESSION['user_id'];
    
    // Check if already liked
    $check_sql = "SELECT id FROM property_favorites WHERE user_id=$user_id AND property_id=$property_id";
    $check_result = $conn->query($check_sql);
    
    if ($check_result && $check_result->num_rows > 0) {
        // Remove like
        $conn->query("DELETE FROM property_favorites WHERE user_id=$user_id AND property_id=$property_id");
        echo json_encode(['success' => true, 'liked' => false, 'message' => 'Removed from favorites']);
    } else {
        // Add like
        $conn->query("INSERT INTO property_favorites (user_id, property_id) VALUES ($user_id, $property_id)");
        echo json_encode(['success' => true, 'liked' => true, 'message' => 'Added to favorites']);
    }
    exit();
}

// Check if current user has liked this property
$is_liked = false;
$like_count = 0;
if ($is_logged_in) {
    $like_check = $conn->query("SELECT id FROM property_favorites WHERE user_id={$_SESSION['user_id']} AND property_id=$property_id");
    $is_liked = ($like_check && $like_check->num_rows > 0);
}
$like_count_result = $conn->query("SELECT COUNT(*) as count FROM property_favorites WHERE property_id=$property_id");
if ($like_count_result) {
    $like_count = $like_count_result->fetch_assoc()['count'];
}

// Handle inquiry submission
$inquiry_success = '';
$inquiry_error = '';

// Include email configuration
require_once 'includes/email-config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_inquiry'])) {
    // Allow both logged-in and non-logged-in users
    if ($is_logged_in) {
        $user_id = $_SESSION['user_id'];
        $user_name = $_SESSION['user_name'];
        $user_email = $_SESSION['user_email'];
        $user_phone = isset($_POST['phone']) ? $conn->real_escape_string($_POST['phone']) : '';
    } else {
        $user_id = 'NULL'; // Guest user - use NULL for foreign key
        $user_name = isset($_POST['guest_name']) ? $conn->real_escape_string($_POST['guest_name']) : '';
        $user_email = isset($_POST['guest_email']) ? $conn->real_escape_string($_POST['guest_email']) : '';
        $user_phone = isset($_POST['phone']) ? $conn->real_escape_string($_POST['phone']) : '';
        
        // Validate guest details
        if (empty($user_name) || empty($user_email)) {
            $inquiry_error = "Please enter your name and email.";
        } elseif (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
            $inquiry_error = "Please enter a valid email address.";
        }
    }
    
    if (empty($inquiry_error)) {
        $message = $conn->real_escape_string($_POST['message']);
        
        if (empty($message)) {
            $inquiry_error = "Please enter your message.";
        } else {
            // Save inquiry to database
            $inquiry_sql = "INSERT INTO inquiries (user_id, property_id, name, email, phone, message) 
                           VALUES ($user_id, $property_id, 
                           '$user_name', 
                           '$user_email', 
                           '$user_phone', '$message')";
            
            if ($conn->query($inquiry_sql) === TRUE) {
                // Send email notification to JD Realty
                $subject = "New Property Inquiry - " . $property['title'];
                
                $email_body = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                        .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
                        .property-box { background: white; padding: 15px; margin: 15px 0; border-radius: 8px; border-left: 4px solid #667eea; }
                        .label { font-weight: bold; color: #555; }
                        .footer { background: #333; color: white; padding: 15px; text-align: center; font-size: 12px; border-radius: 0 0 10px 10px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>🏠 New Property Inquiry</h2>
                        </div>
                        <div class='content'>
                            <h3>Customer Details:</h3>
                            <p><span class='label'>Name:</span> $user_name</p>
                            <p><span class='label'>Email:</span> <a href='mailto:$user_email'>$user_email</a></p>
                            <p><span class='label'>Phone:</span> " . ($user_phone ? $user_phone : 'Not provided') . "</p>
                            
                            <div class='property-box'>
                                <h3>Property Details:</h3>
                                <p><span class='label'>Title:</span> {$property['title']}</p>
                                <p><span class='label'>Price:</span> " . formatIndianPrice($property['price']) . "</p>
                                <p><span class='label'>Location:</span> {$property['city']}</p>
                                <p><span class='label'>Type:</span> " . ucfirst($property['property_type']) . "</p>
                                <p><span class='label'>Property ID:</span> {$property['id']}</p>
                                <p><a href='" . SITE_URL . "/property-details.php?id={$property['id']}'>View Property</a></p>
                            </div>
                            
                            <h3>Customer Message:</h3>
                            <p style='background: white; padding: 15px; border-radius: 8px;'>" . nl2br(htmlspecialchars(stripslashes($message))) . "</p>
                        </div>
                        <div class='footer'>
                            <p>This inquiry was sent from JD Realty & Investment website</p>
                            <p>" . SITE_URL . "</p>
                        </div>
                    </div>
                </body>
                </html>
                ";
                
                // Send email using configured method
                sendEmail(ADMIN_EMAIL, $subject, $email_body, $user_email);
                
                $inquiry_success = "Your inquiry has been sent successfully! We will contact you soon.";
                $_POST = [];
            } else {
                $inquiry_error = "Error sending inquiry. Please try again.";
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
    <meta name="description" content="<?php echo htmlspecialchars(substr($property['description'], 0, 155)); ?> - View detailed property information on JD Realty & Investment.">
    <meta name="keywords" content="<?php echo htmlspecialchars($property['city'] . ', ' . $property['property_type'] . ', property, real estate'); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($property['title']); ?>">
    <meta property="og:description" content="<?php echo formatIndianPrice($property['price']); ?> - <?php echo htmlspecialchars($property['city']); ?>">
    <meta property="og:type" content="website">
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <meta name="author" content="JD Realty & Investment">
    <meta property="og:url" content="<?php echo SITE_URL; ?>/property-details.php?id=<?php echo $property['id']; ?>">
    <meta property="og:image" content="<?php echo !empty($property['image_url']) ? SITE_URL . '/' . htmlspecialchars($property['image_url']) : SITE_URL . '/images/jd-logo.svg'; ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($property['title']); ?>">
    <meta name="twitter:description" content="<?php echo formatIndianPrice($property['price']); ?> - <?php echo htmlspecialchars($property['city']); ?>">
    <link rel="canonical" href="<?php echo SITE_URL; ?>/property-details.php?id=<?php echo $property['id']; ?>">
    <link rel="icon" type="image/x-icon" href="images/jd-logo.svg">
    <title><?php echo htmlspecialchars($property['title']); ?> - <?php echo formatIndianPrice($property['price']); ?> | JD Realty & Investment</title>
    <link rel="stylesheet" href="css/style.css?v=20251203c">
    
    <!-- Google Maps API -->
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAybBsnJ4hUUBVtOg1kfZ6FPBd9FahRGgo&libraries=places"></script>
    
    <!-- Structured Data - Real Estate Listing -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "RealEstateListing",
        "name": "<?php echo htmlspecialchars($property['title']); ?>",
        "description": "<?php echo htmlspecialchars(substr($property['description'], 0, 500)); ?>",
        "url": "<?php echo SITE_URL; ?>/property-details.php?id=<?php echo $property['id']; ?>",
        <?php if (!empty($property['image_url'])): ?>
        "image": "<?php echo SITE_URL . '/' . htmlspecialchars($property['image_url']); ?>",
        <?php endif; ?>
        "offers": {
            "@type": "Offer",
            "price": "<?php echo $property['price']; ?>",
            "priceCurrency": "INR",
            "availability": "https://schema.org/InStock"
        },
        "address": {
            "@type": "PostalAddress",
            "addressLocality": "<?php echo htmlspecialchars($property['city']); ?>",
            "addressRegion": "Maharashtra",
            "addressCountry": "IN"
        },
        "floorSize": {
            "@type": "QuantitativeValue",
            "value": "<?php echo $property['area_sqft']; ?>",
            "unitCode": "SQF"
        }
        <?php if (!empty($property['bedrooms'])): ?>,
        "numberOfRooms": "<?php echo $property['bedrooms']; ?>"
        <?php endif; ?>
        <?php if (!empty($property['bathrooms'])): ?>,
        "numberOfBathroomsTotal": "<?php echo $property['bathrooms']; ?>"
        <?php endif; ?>
    }
    </script>
    
    <!-- Breadcrumb Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            {
                "@type": "ListItem",
                "position": 1,
                "name": "Home",
                "item": "http://localhost/jd-realty/"
            },
            {
                "@type": "ListItem",
                "position": 2,
                "name": "Search",
                "item": "http://localhost/jd-realty/search.php"
            },
            {
                "@type": "ListItem",
                "position": 3,
                "name": "<?php echo htmlspecialchars($property['title']); ?>",
                "item": "http://localhost/jd-realty/property-details.php?id=<?php echo $property['id']; ?>"
            }
        ]
    }
    </script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f9fafb;
            color: #1f2937;
        }
        
        .navbar {
            background: linear-gradient(135deg, #374151 0%, #1f2937 50%, #111827 100%);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
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

        .nav-btn {
            background: linear-gradient(135deg, #374151 0%, #4b5563 100%);
            color: white !important;
            border: 2px solid #6b7280;
            padding: 8px 18px;
            border-radius: 25px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            box-shadow: 0 2px 10px rgba(75, 85, 99, 0.3);
            transition: all 0.3s ease;
        }
        
        .nav-btn:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: transparent;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .login-btn {
            background: linear-gradient(135deg, #f5576c 0%, #f093fb 100%);
            color: white !important;
            border: none;
            padding: 8px 18px;
            border-radius: 25px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            box-shadow: 0 2px 10px rgba(245, 87, 108, 0.3);
            transition: all 0.3s ease;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(245, 87, 108, 0.4);
        }
        
        /* Mobile Navbar Responsive */
        @media (max-width: 768px) {
            .navbar {
                padding: 10px 15px;
                flex-wrap: nowrap;
                gap: 8px;
            }
            
            .logo {
                flex-shrink: 0;
            }
            
            .logo img {
                height: 40px !important;
                width: 40px !important;
            }
            
            .logo span {
                font-size: 14px !important;
            }
            
            .nav-links {
                gap: 6px;
                flex-shrink: 0;
            }
            
            .nav-btn {
                padding: 6px 10px;
                font-size: 11px;
                white-space: nowrap;
            }
            
            .login-btn {
                padding: 6px 10px;
                font-size: 11px;
                white-space: nowrap;
            }
            
            .logout-btn {
                padding: 6px 10px;
                font-size: 11px;
                white-space: nowrap;
            }
            
            .nav-links a {
                font-size: 11px;
            }
        }
        
        @media (max-width: 480px) {
            .navbar {
                padding: 8px 10px;
            }
            
            .logo img {
                height: 35px !important;
                width: 35px !important;
            }
            
            .logo span {
                font-size: 12px !important;
            }
            
            .nav-btn {
                padding: 5px 8px;
                font-size: 10px;
            }
            
            .login-btn {
                padding: 5px 8px;
                font-size: 10px;
            }
            
            .logout-btn {
                padding: 5px 8px;
                font-size: 10px;
            }
            
            .nav-links a {
                font-size: 10px;
            }
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 30px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        
        .back-link:hover {
            color: #4f46e5;
        }
        
        .property-detail-container {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
            max-width: 1200px;
        }
        
        .property-detail-container > div:first-child {
            min-width: 0;
            max-width: 100%;
        }
        
        .property-image {
            width: 100%;
            height: 450px;
            max-height: 450px;
            min-height: 450px;
            max-width: 100%;
            background: #f3f4f6;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 64px;
            position: relative;
            overflow: hidden;
            box-sizing: border-box;
        }
        
        .property-image img {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            transition: none;
        }

        /* Image Navigation Arrows */
        .image-nav-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 45px;
            height: 45px;
            background: rgba(255,255,255,0.9);
            border: none;
            border-radius: 50%;
            cursor: pointer;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }
        
        .image-nav-btn:hover {
            background: #667eea;
            color: white;
            transform: translateY(-50%) scale(1.1);
        }
        
        .image-nav-btn.prev {
            left: 15px;
        }
        
        .image-nav-btn.next {
            right: 15px;
        }

        .image-counter {
            position: absolute;
            bottom: 15px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
        }

        .fullscreen-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.9);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }
        
        .fullscreen-btn:hover {
            background: #667eea;
            color: white;
        }
        
        /* Image Gallery Styles */
        .image-gallery-section {
            margin-top: 20px;
        }
        
        .image-thumbnails {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding: 10px 0;
            margin-top: 15px;
        }
        
        .image-thumbnail {
            flex-shrink: 0;
            width: 80px;
            height: 60px;
            border-radius: 6px;
            overflow: hidden;
            cursor: pointer;
            border: 3px solid transparent;
            transition: all 0.3s ease;
        }
        
        .image-thumbnail:hover,
        .image-thumbnail.active {
            border-color: #667eea;
            transform: scale(1.05);
        }
        
        .image-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .category-images-section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-top: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .category-images-section h3 {
            font-size: 20px;
            color: #1f2937;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .category-group {
            margin-bottom: 25px;
        }
        
        .category-group:last-child {
            margin-bottom: 0;
        }
        
        .category-title {
            font-size: 16px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .category-images-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 12px;
        }
        
        .category-image-item {
            aspect-ratio: 4/3;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            position: relative;
            transition: transform 0.3s ease;
        }
        
        .category-image-item:hover {
            transform: scale(1.03);
        }
        
        .category-image-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .category-image-item::after {
            content: '🔍';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0,0,0,0.6);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .category-image-item:hover::after {
            opacity: 1;
        }
        
        /* Lightbox Styles */
        .lightbox {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.95);
            z-index: 10000;
            align-items: center;
            justify-content: center;
            padding: 20px;
            box-sizing: border-box;
        }
        
        .lightbox.active {
            display: flex;
        }
        
        .lightbox-content {
            max-width: 95%;
            max-height: 95%;
            position: relative;
        }
        
        .lightbox-content img {
            max-width: 100%;
            max-height: 90vh;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 10px 50px rgba(0,0,0,0.5);
            cursor: zoom-in;
            transition: transform 0.3s ease;
        }
        
        .lightbox-content img.zoomed {
            cursor: zoom-out;
            transform: scale(2);
            cursor: grab;
        }
        
        .lightbox-content img.zoomed.dragging {
            cursor: grabbing;
        }
        
        .lightbox-content.zoomed-container {
            overflow: hidden;
            max-width: 100vw;
            max-height: 100vh;
        }
        
        .lightbox-zoom-hint {
            position: absolute;
            bottom: 60px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            opacity: 0.8;
            pointer-events: none;
        }
        
        .lightbox-close {
            position: absolute;
            top: -40px;
            right: 0;
            background: none;
            border: none;
            color: white;
            font-size: 32px;
            cursor: pointer;
            padding: 10px;
            transition: transform 0.2s;
        }
        
        .lightbox-close:hover {
            transform: scale(1.2);
        }
        
        .lightbox-caption {
            color: white;
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
        }
        
        .lightbox-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            font-size: 36px;
            cursor: pointer;
            padding: 15px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
            z-index: 10001;
        }
        
        .lightbox-nav:hover {
            background: rgba(255,255,255,0.4);
            transform: translateY(-50%) scale(1.1);
        }
        
        .lightbox-prev {
            left: 20px;
        }
        
        .lightbox-next {
            right: 20px;
        }
        
        .lightbox-counter {
            color: rgba(255,255,255,0.7);
            text-align: center;
            margin-top: 8px;
            font-size: 13px;
        }
        
        @media (max-width: 768px) {
            .lightbox-nav {
                font-size: 24px;
                padding: 10px 15px;
            }
            
            .lightbox-prev {
                left: 10px;
            }
            
            .lightbox-next {
                right: 10px;
            }
        }
        
        .property-main {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .property-title {
            font-size: 32px;
            margin-bottom: 10px;
            color: #1f2937;
        }
        
        .property-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #6b7280;
            font-size: 14px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            background: #d1fae5;
            color: #065f46;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .price {
            font-size: 28px;
            font-weight: bold;
            color: #667eea;
            margin: 20px 0;
        }
        
        .description {
            color: #4b5563;
            line-height: 1.8;
            margin: 20px 0;
            word-wrap: break-word;
            font-size: 15px;
            background: #f9fafb;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }
        
        .description strong {
            display: block;
            margin-bottom: 10px;
        }
        
        .property-features {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin: 30px 0;
            padding: 20px 0;
            border-top: 2px solid #e5e7eb;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .feature {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .feature-icon {
            font-size: 24px;
        }
        
        .feature-text {
            display: flex;
            flex-direction: column;
        }
        
        .feature-label {
            font-size: 12px;
            color: #6b7280;
        }
        
        .feature-value {
            font-weight: 600;
            color: #1f2937;
        }
        
        .sidebar {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            height: fit-content;
        }
        
        .sidebar h3 {
            font-size: 20px;
            margin-bottom: 20px;
            color: #1f2937;
        }
        
        .contact-info {
            margin-bottom: 30px;
        }
        
        .contact-item {
            display: flex;
            gap: 12px;
            margin-bottom: 15px;
            padding: 12px;
            background: #f3f4f6;
            border-radius: 6px;
        }
        
        .contact-item-icon {
            font-size: 20px;
            min-width: 24px;
        }
        
        .contact-item-text {
            display: flex;
            flex-direction: column;
        }
        
        .contact-item-label {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 3px;
        }
        
        .contact-item-value {
            font-weight: 600;
            color: #1f2937;
            word-break: break-all;
        }
        
        .contact-link {
            color: #667eea;
            text-decoration: none;
            transition: color 0.3s ease;
            font-size: 13px;
        }
        
        .contact-link:hover {
            color: #4f46e5;
        }
        
        .inquiry-form {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #e5e7eb;
        }
        
        .inquiry-form h4 {
            font-size: 16px;
            margin-bottom: 15px;
            color: #1f2937;
        }
        
        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
            resize: vertical;
            min-height: 100px;
            margin-bottom: 15px;
        }
        
        textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .submit-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .success-message {
            background-color: #d1fae5;
            color: #065f46;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 14px;
            border-left: 4px solid #06b6d4;
        }
        
        .error-message {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 14px;
            border-left: 4px solid #dc2626;
        }
        
        .login-prompt {
            background: #dbeafe;
            color: #0c2d48;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .login-prompt a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        /* Map Section Styles */
        .map-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-top: 30px;
        }
        
        .map-section h2 {
            font-size: 24px;
            color: #1f2937;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        #property-map {
            width: 100%;
            height: 400px;
            border-radius: 10px;
            border: 2px solid #e5e7eb;
        }
        
        .map-address {
            margin-top: 15px;
            padding: 15px;
            background: #f3f4f6;
            border-radius: 8px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        
        .map-address-icon {
            font-size: 24px;
            color: #667eea;
        }
        
        .map-address-text {
            flex: 1;
        }
        
        .map-address-label {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 4px;
        }
        
        .map-address-value {
            font-weight: 600;
            color: #1f2937;
            line-height: 1.5;
        }
        
        .get-directions-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .get-directions-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        /* Nearby Places Section */
        .nearby-places-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-top: 30px;
        }
        
        .nearby-places-section h2 {
            font-size: 24px;
            color: #1f2937;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .nearby-categories {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .nearby-category {
            background: #f9fafb;
            border-radius: 12px;
            padding: 20px;
            border: 2px solid #e5e7eb;
            transition: all 0.3s ease;
        }
        
        .nearby-category:hover {
            border-color: #667eea;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.15);
        }
        
        .category-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
        }
        
        .category-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .category-icon.education {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        }
        
        .category-icon.transport {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        
        .category-icon.health {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }
        
        .category-icon.shopping {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }
        
        .category-icon.food {
            background: linear-gradient(135deg, #ec4899 0%, #db2777 100%);
        }
        
        .category-icon.recreation {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        }
        
        .category-title {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
        }
        
        .category-places {
            list-style: none;
        }
        
        .category-places li {
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .category-places li:last-child {
            border-bottom: none;
        }
        
        .place-name {
            font-weight: 500;
            color: #374151;
        }
        
        .place-distance {
            font-size: 13px;
            color: #6b7280;
            background: #e5e7eb;
            padding: 4px 10px;
            border-radius: 20px;
        }
        
        .loading-nearby {
            text-align: center;
            padding: 40px;
            color: #6b7280;
        }
        
        .loading-nearby .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #e5e7eb;
            border-top-color: #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .no-location-message {
            text-align: center;
            padding: 30px;
            background: #fef3c7;
            border-radius: 10px;
            color: #92400e;
        }
        
        .no-location-message .icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        /* EMI Calculator Styles */
        .emi-calculator {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #e5e7eb;
        }
        
        .emi-calculator h4 {
            font-size: 18px;
            margin-bottom: 20px;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .emi-input-group {
            margin-bottom: 15px;
        }
        
        .emi-input-group label {
            display: block;
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 6px;
            font-weight: 500;
        }
        
        .emi-input-group input {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .emi-input-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .emi-slider {
            width: 100%;
            margin-top: 8px;
            accent-color: #667eea;
        }
        
        .emi-result {
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            text-align: center;
        }
        
        .emi-amount {
            font-size: 28px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .emi-label {
            font-size: 12px;
            color: #6b7280;
        }
        
        .emi-breakdown {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 15px;
        }
        
        .emi-breakdown-item {
            background: white;
            padding: 10px;
            border-radius: 6px;
            text-align: center;
        }
        
        .emi-breakdown-value {
            font-weight: 600;
            color: #1f2937;
            font-size: 14px;
        }
        
        .emi-breakdown-label {
            font-size: 11px;
            color: #6b7280;
        }
        
        /* Compare Button Styles */
        .compare-btn {
            width: 100%;
            padding: 12px;
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 15px;
        }
        
        .compare-btn:hover {
            background: #667eea;
            color: white;
        }
        
        .compare-btn.added {
            background: #10b981;
            border-color: #10b981;
            color: white;
        }
        
        /* Comparison Bar */
        .comparison-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.15);
            padding: 15px 20px;
            z-index: 1000;
            display: none;
            animation: slideUp 0.3s ease;
        }
        
        .comparison-bar.show {
            display: block;
        }
        
        @keyframes slideUp {
            from {
                transform: translateY(100%);
            }
            to {
                transform: translateY(0);
            }
        }
        
        .comparison-bar-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }
        
        .comparison-items {
            display: flex;
            gap: 15px;
            flex: 1;
        }
        
        .comparison-item {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f3f4f6;
            padding: 8px 12px;
            border-radius: 8px;
            min-width: 200px;
        }
        
        .comparison-item-img {
            width: 50px;
            height: 50px;
            border-radius: 6px;
            object-fit: cover;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }
        
        .comparison-item-info {
            flex: 1;
            min-width: 0;
        }
        
        .comparison-item-title {
            font-weight: 600;
            font-size: 13px;
            color: #1f2937;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .comparison-item-price {
            font-size: 12px;
            color: #667eea;
            font-weight: 600;
        }
        
        .comparison-item-remove {
            background: #ef4444;
            color: white;
            border: none;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .comparison-slot-empty {
            min-width: 200px;
            height: 66px;
            border: 2px dashed #e5e7eb;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #9ca3af;
            font-size: 13px;
        }
        
        .comparison-actions {
            display: flex;
            gap: 10px;
        }
        
        .compare-now-btn {
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .compare-now-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .compare-now-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .clear-compare-btn {
            padding: 12px 20px;
            background: white;
            color: #ef4444;
            border: 2px solid #ef4444;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .clear-compare-btn:hover {
            background: #ef4444;
            color: white;
        }
        
        /* Comparison Modal */
        .comparison-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 2000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .comparison-modal.show {
            display: flex;
        }
        
        .comparison-modal-content {
            background: white;
            border-radius: 15px;
            max-width: 1100px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            animation: modalFadeIn 0.3s ease;
        }
        
        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        .comparison-modal-header {
            padding: 20px 25px;
            border-bottom: 2px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            background: white;
            z-index: 10;
        }
        
        .comparison-modal-header h2 {
            font-size: 22px;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .close-modal-btn {
            background: none;
            border: none;
            font-size: 28px;
            color: #6b7280;
            cursor: pointer;
            padding: 5px;
            transition: color 0.3s ease;
        }
        
        .close-modal-btn:hover {
            color: #1f2937;
        }
        
        .comparison-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .comparison-table th,
        .comparison-table td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .comparison-table th {
            background: #f9fafb;
            font-weight: 600;
            color: #6b7280;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            width: 150px;
        }
        
        .comparison-table td {
            font-size: 14px;
            color: #1f2937;
        }
        
        .comparison-property-header {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, #667eea10 0%, #764ba210 100%);
        }
        
        .comparison-property-img {
            width: 120px;
            height: 80px;
            border-radius: 8px;
            object-fit: cover;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
            margin: 0 auto 10px;
        }
        
        .comparison-property-title {
            font-weight: 600;
            color: #1f2937;
            font-size: 15px;
            margin-bottom: 5px;
        }
        
        .comparison-property-price {
            color: #667eea;
            font-weight: 700;
            font-size: 18px;
        }
        
        .comparison-highlight {
            background: #d1fae5;
            color: #065f46;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 12px;
        }
        
        .footer {
            background: #1f2937;
            color: white;
            padding: 40px;
            text-align: center;
            margin-top: 60px;
        }
        
        /* Like Button Styles */
        .like-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 25px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .like-btn:hover {
            border-color: #ef4444;
            transform: scale(1.05);
        }
        
        .like-btn.liked {
            background: #fef2f2;
            border-color: #ef4444;
        }
        
        .like-icon {
            font-size: 20px;
            transition: transform 0.3s ease;
        }
        
        .like-btn:hover .like-icon {
            transform: scale(1.2);
        }
        
        .like-count {
            font-weight: 600;
            color: #374151;
        }
        
        /* Contact Popup for Non-logged Users */
        .contact-popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
        }
        
        .contact-popup-overlay.show {
            display: flex;
        }
        
        .contact-popup {
            background: white;
            padding: 30px;
            border-radius: 15px;
            max-width: 400px;
            width: 90%;
            text-align: center;
            animation: popupFadeIn 0.3s ease;
        }
        
        @keyframes popupFadeIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
        
        .contact-popup h3 {
            font-size: 24px;
            margin-bottom: 15px;
            color: #1f2937;
        }
        
        .contact-popup p {
            color: #6b7280;
            margin-bottom: 25px;
        }
        
        .contact-popup .btn-login {
            display: inline-block;
            padding: 14px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin: 5px;
            transition: all 0.3s ease;
        }
        
        .contact-popup .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .contact-popup .btn-close {
            display: inline-block;
            padding: 14px 30px;
            background: #f3f4f6;
            color: #6b7280;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            margin: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .contact-popup .btn-close:hover {
            background: #e5e7eb;
        }
        
        @media (max-width: 768px) {
            .property-detail-container {
                grid-template-columns: 1fr;
            }
            
            .property-features {
                grid-template-columns: 1fr;
            }
            
            .property-title {
                font-size: 24px;
            }
            
            .price {
                font-size: 24px;
            }
            
            .comparison-bar-content {
                flex-direction: column;
                gap: 15px;
            }
            
            .comparison-items {
                flex-direction: column;
                width: 100%;
            }
            
            .comparison-item,
            .comparison-slot-empty {
                min-width: 100%;
            }
            
            .comparison-actions {
                width: 100%;
                justify-content: center;
            }
            
            .comparison-modal-content {
                margin: 10px;
                max-height: 95vh;
            }
            
            .comparison-table th,
            .comparison-table td {
                padding: 10px;
                font-size: 12px;
            }
            
            .comparison-table th {
                width: 100px;
            }
            
            .comparison-property-img {
                width: 80px;
                height: 60px;
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
            <?php if ($is_logged_in): ?>
                <a href="index.php">Home</a>
                <a href="user-dashboard.php">My Properties</a>
                <a href="includes/logout.php" class="logout-btn">Logout</a>
            <?php else: ?>
                <a href="index.php" class="nav-btn">Home</a>
                <a href="search.php" class="nav-btn">Search</a>
                <a href="login.php" class="login-btn">Log in</a>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="container">
        <a href="index.php" class="back-link">← Back to Listings</a>
        
        <div class="property-detail-container">
            <div>
                <?php 
                // Determine main display image - featured image or first uploaded image
                $main_image = $property['image_url'] ?? '';
                $all_images = [];
                
                // Add featured image first
                if (!empty($property['image_url'])) {
                    $all_images[] = ['url' => $property['image_url'], 'category' => 'featured', 'caption' => 'Featured Image'];
                }
                
                // Add all category images
                foreach ($property_images as $img) {
                    $all_images[] = ['url' => $img['image_url'], 'category' => $img['image_category'], 'caption' => $image_category_names[$img['image_category']] ?? 'Other'];
                    if (empty($main_image)) {
                        $main_image = $img['image_url'];
                    }
                }
                ?>
                
                <div class="property-image" id="mainImageContainer" style="background: #f3f4f6; cursor: pointer;" onclick="openLightboxAtCurrentIndex()">
                    <?php if (!empty($main_image)): ?>
                        <img id="mainImage" src="<?php echo htmlspecialchars($main_image); ?>" alt="<?php echo htmlspecialchars($property['title'] . ' - ' . ucfirst($property['property_type']) . ' property in ' . $property['city'] . ', ' . number_format($property['area_sqft']) . ' sq ft'); ?>" loading="eager" fetchpriority="high" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div style="width: 100%; height: 100%; display: none; align-items: center; justify-content: center; color: #999; font-size: 48px; background: #f3f4f6;">📷</div>
                    <?php else: ?>
                        <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #999; font-size: 48px;" role="img" aria-label="Property image not available">📷</div>
                    <?php endif; ?>
                    
                    <?php if (count($all_images) > 1): ?>
                    <!-- Navigation Arrows -->
                    <button class="image-nav-btn prev" onclick="event.stopPropagation(); navigateImage(-1)" title="Previous Image">❮</button>
                    <button class="image-nav-btn next" onclick="event.stopPropagation(); navigateImage(1)" title="Next Image">❯</button>
                    <div class="image-counter"><span id="currentImageIndex">1</span> / <?php echo count($all_images); ?></div>
                    <?php endif; ?>
                </div>
                
                <?php if (count($all_images) > 1): ?>
                <!-- Image Thumbnails -->
                <div class="image-thumbnails">
                    <?php foreach ($all_images as $index => $img): ?>
                        <div class="image-thumbnail <?php echo $index === 0 ? 'active' : ''; ?>" onclick="changeMainImage('<?php echo htmlspecialchars($img['url']); ?>', this)">
                            <img src="<?php echo htmlspecialchars($img['url']); ?>" alt="<?php echo htmlspecialchars($img['caption']); ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <div class="property-main">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                        <div>
                            <h1 class="property-title"><?php echo htmlspecialchars($property['title']); ?></h1>
                            <?php if (!empty($property['building_name'])): ?>
                                <div class="building-name" style="font-size: 16px; color: #667eea; margin-bottom: 8px; font-weight: 500;">
                                    🏢 <?php echo htmlspecialchars($property['building_name']); ?>
                                </div>
                            <?php endif; ?>
                            <div class="property-meta">
                                <span class="meta-item">📍 <?php echo htmlspecialchars($property['city']); ?></span>
                                <span class="status-badge"><?php echo ucfirst(str_replace('_', ' ', $property['status'])); ?></span>
                                <span class="meta-item">🆔 Property ID: <?php echo $property['id']; ?></span>
                            </div>
                        </div>
                        <!-- Like Button -->
                        <button type="button" class="like-btn <?php echo $is_liked ? 'liked' : ''; ?>" id="likeBtn" onclick="toggleLike()">
                            <span class="like-icon"><?php echo $is_liked ? '❤️' : '🤍'; ?></span>
                            <span class="like-count" id="likeCount"><?php echo $like_count; ?></span>
                        </button>
                    </div>
                    
                    <div class="price"><?php echo formatIndianPrice($property['price']); ?></div>
                    
                    <div class="description">
                        <strong style="display: block; margin-bottom: 10px; color: #1f2937; font-size: 16px;">📝 Description:</strong>
                        <?php echo nl2br(htmlspecialchars(trim($property['description']))); ?>
                    </div>
                    
                    <div class="property-features">
                        <div class="feature">
                            <div class="feature-icon">📏</div>
                            <div class="feature-text">
                                <div class="feature-label">Area</div>
                                <div class="feature-value"><?php echo number_format($property['area_sqft']); ?> sq ft</div>
                            </div>
                        </div>
                        
                        <div class="feature">
                            <div class="feature-icon">🏗️</div>
                            <div class="feature-text">
                                <div class="feature-label">Type</div>
                                <div class="feature-value"><?php echo ucfirst($property['property_type']); ?></div>
                            </div>
                        </div>
                        
                        <?php if ($property['bedrooms']): ?>
                        <div class="feature">
                            <div class="feature-icon">🛏️</div>
                            <div class="feature-text">
                                <div class="feature-label">Bedrooms</div>
                                <div class="feature-value"><?php echo $property['bedrooms']; ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($property['bathrooms']): ?>
                        <div class="feature">
                            <div class="feature-icon">🚿</div>
                            <div class="feature-text">
                                <div class="feature-label">Bathrooms</div>
                                <div class="feature-value"><?php echo $property['bathrooms']; ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (isset($property['pre_lease']) && $property['pre_lease'] == 'yes'): ?>
                        <div class="feature">
                            <div class="feature-icon">📋</div>
                            <div class="feature-text">
                                <div class="feature-label">Pre-Lease</div>
                                <div class="feature-value" style="color: #10b981;">Yes</div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($property['possession_date'])): ?>
                        <div class="feature">
                            <div class="feature-icon">📅</div>
                            <div class="feature-text">
                                <div class="feature-label">Possession Date</div>
                                <div class="feature-value"><?php echo date('M Y', strtotime($property['possession_date'])); ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php 
                    $is_commercial = in_array($property['property_type'], ['commercial', 'shop', 'office']);
                    if ($is_commercial && (!empty($property['workstations']) || !empty($property['cabins']) || !empty($property['conference_rooms']))): 
                    ?>
                    <div class="property-features" style="margin-top: 15px; border-top: 1px solid #eee; padding-top: 15px;">
                        <h4 style="width: 100%; margin-bottom: 10px; color: #667eea; font-size: 14px;">🏢 Commercial Specifications</h4>
                        
                        <?php if (!empty($property['workstations']) && $property['workstations'] > 0): ?>
                        <div class="feature">
                            <div class="feature-icon">💻</div>
                            <div class="feature-text">
                                <div class="feature-label">Workstations</div>
                                <div class="feature-value"><?php echo $property['workstations']; ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($property['cabins']) && $property['cabins'] > 0): ?>
                        <div class="feature">
                            <div class="feature-icon">🚪</div>
                            <div class="feature-text">
                                <div class="feature-label">Cabins</div>
                                <div class="feature-value"><?php echo $property['cabins']; ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($property['conference_rooms']) && $property['conference_rooms'] > 0): ?>
                        <div class="feature">
                            <div class="feature-icon">🎤</div>
                            <div class="feature-text">
                                <div class="feature-label">Conference Rooms</div>
                                <div class="feature-value"><?php echo $property['conference_rooms']; ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($property['meeting_rooms']) && $property['meeting_rooms'] > 0): ?>
                        <div class="feature">
                            <div class="feature-icon">👥</div>
                            <div class="feature-text">
                                <div class="feature-label">Meeting Rooms</div>
                                <div class="feature-value"><?php echo $property['meeting_rooms']; ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (isset($property['pantry']) && $property['pantry'] == 'yes'): ?>
                        <div class="feature">
                            <div class="feature-icon">🍳</div>
                            <div class="feature-text">
                                <div class="feature-label">Pantry</div>
                                <div class="feature-value" style="color: #10b981;">Available</div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Amenities Section -->
                <?php if (!empty($amenities)): ?>
                <div class="amenities-section">
                    <h3>✨ Amenities & Features</h3>
                    <div class="amenities-grid">
                        <?php 
                        $category_names = [
                            'basic' => 'Basic Amenities',
                            'safety' => 'Safety',
                            'convenience' => 'Convenience',
                            'recreation' => 'Recreation',
                            'luxury' => 'Luxury'
                        ];
                        
                        foreach ($amenities as $category => $items): 
                        ?>
                            <div class="amenity-category">
                                <h4 style="color: #667eea; margin-bottom: 10px; font-size: 14px;">
                                    <?php echo $category_names[$category] ?? ucfirst($category); ?>
                                </h4>
                                <ul style="list-style: none; padding: 0;">
                                    <?php foreach ($items as $amenity): ?>
                                        <li style="padding: 8px 0; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center;">
                                            <span style="font-size: 18px; margin-right: 10px;">
                                                <?php echo !empty($amenity['icon']) && strlen($amenity['icon']) < 10 ? htmlspecialchars($amenity['icon']) : '✓'; ?>
                                            </span>
                                            <span style="color: #374151; font-size: 14px;">
                                                <?php echo htmlspecialchars($amenity['name']); ?>
                                            </span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <style>
                        .amenities-grid {
                            display: grid;
                            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                            gap: 20px;
                            margin-top: 15px;
                        }
                        .amenity-category {
                            padding: 15px;
                            background: #f9fafb;
                            border-radius: 8px;
                            border-left: 4px solid #667eea;
                        }
                        @media (max-width: 768px) {
                            .amenities-grid {
                                grid-template-columns: 1fr;
                            }
                        }
                    </style>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($property_images)): ?>
                <!-- Category Images Gallery -->
                <div class="category-images-section">
                    <h3>📸 Property Images Gallery</h3>
                    <?php
                    // Group images by category
                    $images_by_category = [];
                    foreach ($property_images as $img) {
                        $cat = $img['image_category'];
                        if (!isset($images_by_category[$cat])) {
                            $images_by_category[$cat] = [];
                        }
                        $images_by_category[$cat][] = $img;
                    }
                    
                    // Display images by category
                    foreach ($images_by_category as $category => $images):
                        $cat_name = $image_category_names[$category] ?? ucfirst($category);
                    ?>
                    <div class="category-group">
                        <div class="category-title"><?php echo htmlspecialchars($cat_name); ?> (<?php echo count($images); ?>)</div>
                        <div class="category-images-grid">
                            <?php foreach ($images as $img): ?>
                            <div class="category-image-item" onclick="openLightbox('<?php echo htmlspecialchars($img['image_url']); ?>', '<?php echo htmlspecialchars($cat_name); ?>')">
                                <img src="<?php echo htmlspecialchars($img['image_url']); ?>" alt="<?php echo htmlspecialchars($cat_name . ' - ' . $property['title']); ?>" loading="lazy">
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- Map Section -->
                <div class="map-section">
                    <h2>📍 Property Location</h2>
                    <?php 
                    // Default coordinates for demonstration (Mumbai)
                    $latitude = !empty($property['latitude']) ? $property['latitude'] : 19.0760;
                    $longitude = !empty($property['longitude']) ? $property['longitude'] : 72.8777;
                    $address = !empty($property['address']) ? $property['address'] : $property['city'] . ', Maharashtra, India';
                    $landmark = !empty($property['landmark']) ? $property['landmark'] : '';
                    ?>
                    <div id="property-map"></div>
                    <div class="map-address">
                        <div class="map-address-icon">🏠</div>
                        <div class="map-address-text">
                            <div class="map-address-label">Property Address</div>
                            <div class="map-address-value">
                                <?php echo htmlspecialchars($address); ?>
                                <?php if ($landmark): ?>
                                    <br><small style="color: #6b7280;">Landmark: <?php echo htmlspecialchars($landmark); ?></small>
                                <?php endif; ?>
                            </div>
                            <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo $latitude; ?>,<?php echo $longitude; ?>" target="_blank" class="get-directions-btn">
                                🧭 Get Directions
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Nearby Places Section -->
                <div class="nearby-places-section">
                    <h2>🏙️ Nearby Places & Amenities</h2>
                    
                    <?php 
                    $has_valid_location = (!empty($property['latitude']) && !empty($property['longitude']) && 
                                          $property['latitude'] != 0 && $property['longitude'] != 0);
                    ?>
                    
                    <?php if ($has_valid_location): ?>
                    <div class="nearby-categories" id="nearbyCategories">
                        <div class="loading-nearby">
                            <div class="spinner"></div>
                            <p>Loading nearby places...</p>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="no-location-message">
                        <div class="icon">📍</div>
                        <p><strong>Location coordinates not available</strong></p>
                        <p>Nearby places information will be displayed once the property owner adds accurate location coordinates.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="sidebar">
                <h3>Contact Us</h3>
                
                <div class="contact-info">
                    <!-- Owner name from database commented out -->
                    <!-- <?php if ($property['owner_name']): ?>
                    <div class="contact-item">
                        <div class="contact-item-icon">👤</div>
                        <div class="contact-item-text">
                            <div class="contact-item-label">Owner</div>
                            <div class="contact-item-value"><?php echo htmlspecialchars($property['owner_name']); ?></div>
                        </div>
                    </div>
                    <?php endif; ?> -->
                    
                    <!-- Fixed Name: Jeetu Parsani -->
                    <div class="contact-item">
                        <div class="contact-item-icon">👤</div>
                        <div class="contact-item-text">
                            <div class="contact-item-label">Name</div>
                            <div class="contact-item-value">Jeetu Parsani</div>
                        </div>
                    </div>
                    
                    <?php 
                    // Default contact details for JD Realty
                    $display_email = 'info@jdrealtyinvestment.com';
                    $display_phone = '7507991499';
                    ?>
                    <div class="contact-item">
                        <div class="contact-item-icon">✉️</div>
                        <div class="contact-item-text">
                            <div class="contact-item-label">Email</div>
                            <div class="contact-item-value">
                                <a href="mailto:<?php echo htmlspecialchars($display_email); ?>" class="contact-link">
                                    <?php echo htmlspecialchars($display_email); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-item-icon">📞</div>
                        <div class="contact-item-text">
                            <div class="contact-item-label">Phone</div>
                            <div class="contact-item-value">
                                <a href="tel:<?php echo htmlspecialchars($display_phone); ?>" class="contact-link">
                                    <?php echo htmlspecialchars($display_phone); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="inquiry-form">
                    <h4>📧 Send an Inquiry</h4>
                    
                    <?php if ($inquiry_success): ?>
                        <div class="success-message"><?php echo $inquiry_success; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($inquiry_error): ?>
                        <div class="error-message"><?php echo $inquiry_error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <?php if (!$is_logged_in): ?>
                            <!-- Guest user fields -->
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #374151;">Your Name <span style="color: #dc2626;">*</span></label>
                                <input type="text" name="guest_name" placeholder="Enter your full name" required style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px;">
                            </div>
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #374151;">Your Email <span style="color: #dc2626;">*</span></label>
                                <input type="email" name="guest_email" placeholder="Enter your email address" required style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px;">
                            </div>
                        <?php endif; ?>
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #374151;">Your Phone <?php echo $is_logged_in ? '(optional)' : '<span style="color: #dc2626;">*</span>'; ?></label>
                            <input type="tel" name="phone" placeholder="Enter your phone number" <?php echo !$is_logged_in ? 'required' : ''; ?> style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px;">
                        </div>
                        <textarea name="message" placeholder="Write your inquiry message here... (e.g., I am interested in this property, please contact me)" required></textarea>
                        <button type="submit" name="send_inquiry" class="submit-btn">📤 Send Inquiry</button>
                        <p style="font-size: 12px; color: #6b7280; margin-top: 10px; text-align: center;">Your inquiry will be sent to JD Realty team</p>
                        <?php if (!$is_logged_in): ?>
                            <p style="font-size: 12px; color: #667eea; margin-top: 8px; text-align: center;">
                                Already have an account? <a href="login.php" style="font-weight: 600;">Login here</a>
                            </p>
                        <?php endif; ?>
                    </form>
                </div>
                
                <!-- Add to Compare Button -->
                <button type="button" class="compare-btn" id="compareBtn" onclick="toggleCompare()">
                    <span id="compareBtnIcon">⚖️</span>
                    <span id="compareBtnText">Add to Compare</span>
                </button>
                
                <!-- EMI Calculator -->
                <div class="emi-calculator">
                    <h4>🏦 EMI Calculator</h4>
                    
                    <div class="emi-input-group">
                        <label>Loan Amount (₹)</label>
                        <input type="number" id="loanAmount" value="<?php echo round($property['price'] * 0.8); ?>" min="100000" max="100000000">
                        <input type="range" class="emi-slider" id="loanSlider" min="100000" max="<?php echo $property['price']; ?>" value="<?php echo round($property['price'] * 0.8); ?>" step="100000">
                    </div>
                    
                    <div class="emi-input-group">
                        <label>Interest Rate (% per annum)</label>
                        <input type="number" id="interestRate" value="8.5" min="5" max="20" step="0.1">
                        <input type="range" class="emi-slider" id="rateSlider" min="5" max="15" value="8.5" step="0.1">
                    </div>
                    
                    <div class="emi-input-group">
                        <label>Loan Tenure (Years)</label>
                        <input type="number" id="loanTenure" value="20" min="1" max="30">
                        <input type="range" class="emi-slider" id="tenureSlider" min="1" max="30" value="20">
                    </div>
                    
                    <div class="emi-result">
                        <div class="emi-label">Monthly EMI</div>
                        <div class="emi-amount" id="emiAmount">₹0</div>
                        <div class="emi-breakdown">
                            <div class="emi-breakdown-item">
                                <div class="emi-breakdown-value" id="totalInterest">₹0</div>
                                <div class="emi-breakdown-label">Total Interest</div>
                            </div>
                            <div class="emi-breakdown-item">
                                <div class="emi-breakdown-value" id="totalPayment">₹0</div>
                                <div class="emi-breakdown-label">Total Payment</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Property Comparison Bar -->
    <div class="comparison-bar" id="comparisonBar">
        <div class="comparison-bar-content">
            <div class="comparison-items" id="comparisonItems">
                <div class="comparison-slot-empty">+ Add Property</div>
                <div class="comparison-slot-empty">+ Add Property</div>
                <div class="comparison-slot-empty">+ Add Property</div>
            </div>
            <div class="comparison-actions">
                <button class="clear-compare-btn" onclick="clearComparison()">Clear All</button>
                <button class="compare-now-btn" id="compareNowBtn" onclick="showComparison()" disabled>Compare Now</button>
            </div>
        </div>
    </div>
    
    <!-- Comparison Modal -->
    <div class="comparison-modal" id="comparisonModal">
        <div class="comparison-modal-content">
            <div class="comparison-modal-header">
                <h2>⚖️ Property Comparison</h2>
                <button class="close-modal-btn" onclick="closeComparisonModal()">&times;</button>
            </div>
            <table class="comparison-table" id="comparisonTable">
                <!-- Comparison data will be inserted here -->
            </table>
        </div>
    </div>
    
    <!-- Contact Popup for Non-Logged Users -->
    <?php if (!$is_logged_in): ?>
    <div class="contact-popup-overlay" id="contactPopup" onclick="if(event.target === this) closeContactPopup()">
        <div class="contact-popup" style="position: relative;">
            <button onclick="closeContactPopup()" style="position: absolute; top: 10px; right: 10px; background: none; border: none; font-size: 24px; cursor: pointer; color: #6b7280; transition: color 0.3s;">&times;</button>
            <div style="font-size: 48px; margin-bottom: 15px;">👋</div>
            <h3>Interested in this property?</h3>
            <p style="color: #6b7280; margin-bottom: 20px;">Login to contact the owner, send inquiries, and save your favorite properties.</p>
            <a href="login.php?redirect=property-details.php?id=<?php echo $property_id; ?>" class="btn-login">Login to Continue</a>
            <a href="signup.php" class="btn-login" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">Create Free Account</a>
            <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #e5e7eb;">
                <button class="btn-close" onclick="closeContactPopup()" style="background: none; border: none; color: #667eea; font-weight: 600; cursor: pointer; font-size: 14px;">
                    ✕ Continue browsing without login
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php include('includes/footer.php'); ?>
    
    <!-- Google Maps Initialization -->
    <script>
        // Property coordinates
        const propertyLat = <?php echo $latitude; ?>;
        const propertyLng = <?php echo $longitude; ?>;
        const hasValidLocation = <?php echo $has_valid_location ? 'true' : 'false'; ?>;
        
        // Calculate distance between two points in km
        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371; // Earth's radius in km
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                      Math.sin(dLon/2) * Math.sin(dLon/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return R * c;
        }
        
        // Format distance for display
        function formatDistance(km) {
            if (km < 1) {
                return Math.round(km * 1000) + ' m';
            }
            return km.toFixed(1) + ' km';
        }
        
        // Fetch nearby places from OpenStreetMap Overpass API
        async function fetchNearbyPlaces(lat, lng, category, tags, radius = 2000) {
            const query = `
                [out:json][timeout:25];
                (
                    ${tags.map(tag => `node${tag}(around:${radius},${lat},${lng});`).join('\n')}
                    ${tags.map(tag => `way${tag}(around:${radius},${lat},${lng});`).join('\n')}
                );
                out center 10;
            `;
            
            try {
                const response = await fetch('https://overpass-api.de/api/interpreter', {
                    method: 'POST',
                    body: query
                });
                const data = await response.json();
                
                const places = data.elements
                    .filter(el => el.tags && el.tags.name)
                    .map(el => {
                        const elLat = el.lat || (el.center && el.center.lat);
                        const elLng = el.lon || (el.center && el.center.lon);
                        return {
                            name: el.tags.name,
                            distance: calculateDistance(lat, lng, elLat, elLng),
                            lat: elLat,
                            lng: elLng
                        };
                    })
                    .sort((a, b) => a.distance - b.distance)
                    .slice(0, 5);
                
                return places;
            } catch (error) {
                console.error('Error fetching ' + category + ':', error);
                return [];
            }
        }
        
        // Category configurations
        const categoryConfig = {
            education: {
                icon: '🎓',
                color: 'education',
                title: 'Education',
                tags: ['["amenity"="school"]', '["amenity"="college"]', '["amenity"="university"]', '["amenity"="kindergarten"]'],
                radius: 3000
            },
            transport: {
                icon: '🚇',
                color: 'transport',
                title: 'Transport',
                tags: ['["railway"="station"]', '["station"="subway"]', '["amenity"="bus_station"]', '["public_transport"="station"]'],
                radius: 5000
            },
            health: {
                icon: '🏥',
                color: 'health',
                title: 'Healthcare',
                tags: ['["amenity"="hospital"]', '["amenity"="clinic"]', '["amenity"="pharmacy"]', '["amenity"="doctors"]'],
                radius: 3000
            },
            shopping: {
                icon: '🛒',
                color: 'shopping',
                title: 'Shopping',
                tags: ['["shop"="mall"]', '["shop"="supermarket"]', '["shop"="department_store"]', '["amenity"="marketplace"]'],
                radius: 3000
            },
            food: {
                icon: '🍽️',
                color: 'food',
                title: 'Food & Dining',
                tags: ['["amenity"="restaurant"]', '["amenity"="cafe"]', '["amenity"="fast_food"]'],
                radius: 2000
            },
            recreation: {
                icon: '🎭',
                color: 'recreation',
                title: 'Recreation',
                tags: ['["leisure"="park"]', '["leisure"="fitness_centre"]', '["amenity"="cinema"]', '["leisure"="sports_centre"]'],
                radius: 3000
            }
        };
        
        // Generate category HTML
        function generateCategoryHTML(category, config, places) {
            let placesHTML = '';
            
            if (places.length === 0) {
                placesHTML = '<li><span class="place-name" style="color: #9ca3af;">No places found nearby</span></li>';
            } else {
                placesHTML = places.map(place => `
                    <li>
                        <span class="place-name">${place.name}</span>
                        <span class="place-distance">${formatDistance(place.distance)}</span>
                    </li>
                `).join('');
            }
            
            return `
                <div class="nearby-category">
                    <div class="category-header">
                        <div class="category-icon ${config.color}">${config.icon}</div>
                        <div class="category-title">${config.title}</div>
                    </div>
                    <ul class="category-places">
                        ${placesHTML}
                    </ul>
                </div>
            `;
        }
        
        // Load all nearby places
        async function loadNearbyPlaces() {
            if (!hasValidLocation) return;
            
            const container = document.getElementById('nearbyCategories');
            if (!container) return;
            
            let allHTML = '';
            
            for (const [category, config] of Object.entries(categoryConfig)) {
                try {
                    const places = await fetchNearbyPlaces(propertyLat, propertyLng, category, config.tags, config.radius);
                    allHTML += generateCategoryHTML(category, config, places);
                } catch (error) {
                    console.error('Error loading ' + category, error);
                    allHTML += generateCategoryHTML(category, config, []);
                }
            }
            
            container.innerHTML = allHTML;
        }
        
        // Initialize the map with Google Maps
        document.addEventListener('DOMContentLoaded', function() {
            const propertyTitle = "<?php echo addslashes($property['title']); ?>";
            const propertyCity = "<?php echo addslashes($property['city']); ?>";
            const propertyAddress = "<?php echo addslashes($address); ?>";
            
            // Create the Google Map
            const mapElement = document.getElementById('property-map');
            const propertyLocation = { lat: propertyLat, lng: propertyLng };
            
            const map = new google.maps.Map(mapElement, {
                center: propertyLocation,
                zoom: 16,
                mapTypeId: 'roadmap',
                styles: [
                    {
                        "featureType": "poi.business",
                        "stylers": [{ "visibility": "simplified" }]
                    }
                ],
                mapTypeControl: true,
                mapTypeControlOptions: {
                    style: google.maps.MapTypeControlStyle.DROPDOWN_MENU,
                    position: google.maps.ControlPosition.TOP_RIGHT
                },
                streetViewControl: true,
                fullscreenControl: true,
                zoomControl: true
            });
            
            // Custom marker with property icon
            const marker = new google.maps.Marker({
                position: propertyLocation,
                map: map,
                title: propertyTitle,
                animation: google.maps.Animation.DROP,
                icon: {
                    url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                        <svg xmlns="http://www.w3.org/2000/svg" width="50" height="60" viewBox="0 0 50 60">
                            <defs>
                                <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" style="stop-color:#667eea"/>
                                    <stop offset="100%" style="stop-color:#764ba2"/>
                                </linearGradient>
                            </defs>
                            <path d="M25 0C11.2 0 0 11.2 0 25c0 17.5 25 35 25 35s25-17.5 25-35C50 11.2 38.8 0 25 0z" fill="url(#grad)" stroke="white" stroke-width="2"/>
                            <circle cx="25" cy="22" r="12" fill="white"/>
                            <text x="25" y="27" text-anchor="middle" font-size="16">🏠</text>
                        </svg>
                    `),
                    scaledSize: new google.maps.Size(50, 60),
                    anchor: new google.maps.Point(25, 60)
                }
            });
            
            // Info window for the marker
            const infoWindow = new google.maps.InfoWindow({
                content: `
                    <div style="padding: 15px; max-width: 280px;">
                        <h3 style="margin: 0 0 8px 0; font-size: 16px; color: #1f2937;">${propertyTitle}</h3>
                        <p style="margin: 0 0 8px 0; color: #6b7280; font-size: 13px;">📍 ${propertyAddress}</p>
                        <div style="display: flex; gap: 8px; margin-top: 10px;">
                            <a href="https://www.google.com/maps/dir/?api=1&destination=${propertyLat},${propertyLng}" 
                               target="_blank" 
                               style="flex: 1; padding: 8px 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 6px; font-size: 12px; text-align: center; font-weight: 600;">
                                🧭 Get Directions
                            </a>
                            <a href="https://www.google.com/maps/@${propertyLat},${propertyLng},3a,75y,90t/data=!3m6!1e1!3m4!1s!2e0!7i16384!8i8192" 
                               target="_blank" 
                               style="flex: 1; padding: 8px 12px; background: #f3f4f6; color: #374151; text-decoration: none; border-radius: 6px; font-size: 12px; text-align: center; font-weight: 600;">
                                🚶 Street View
                            </a>
                        </div>
                    </div>
                `
            });
            
            // Open info window on marker click
            marker.addListener('click', function() {
                infoWindow.open(map, marker);
            });
            
            // Open info window by default
            infoWindow.open(map, marker);
            
            // Add a circle to show the area
            const circle = new google.maps.Circle({
                strokeColor: '#667eea',
                strokeOpacity: 0.8,
                strokeWeight: 2,
                fillColor: '#667eea',
                fillOpacity: 0.1,
                map: map,
                center: propertyLocation,
                radius: 300
            });
            
            // Load nearby places (with delay to avoid overwhelming the API)
            setTimeout(loadNearbyPlaces, 500);
        });
        
        // ============ EMI Calculator ============
        function calculateEMI() {
            const P = parseFloat(document.getElementById('loanAmount').value) || 0;
            const r = (parseFloat(document.getElementById('interestRate').value) || 8.5) / 12 / 100;
            const n = (parseFloat(document.getElementById('loanTenure').value) || 20) * 12;
            
            if (P <= 0 || r <= 0 || n <= 0) {
                document.getElementById('emiAmount').textContent = '₹0';
                document.getElementById('totalInterest').textContent = '₹0';
                document.getElementById('totalPayment').textContent = '₹0';
                return;
            }
            
            const emi = P * r * Math.pow(1 + r, n) / (Math.pow(1 + r, n) - 1);
            const totalPayment = emi * n;
            const totalInterest = totalPayment - P;
            
            document.getElementById('emiAmount').textContent = '₹' + Math.round(emi).toLocaleString('en-IN');
            document.getElementById('totalInterest').textContent = '₹' + Math.round(totalInterest).toLocaleString('en-IN');
            document.getElementById('totalPayment').textContent = '₹' + Math.round(totalPayment).toLocaleString('en-IN');
        }
        
        // Sync sliders with inputs
        document.getElementById('loanAmount').addEventListener('input', function() {
            document.getElementById('loanSlider').value = this.value;
            calculateEMI();
        });
        document.getElementById('loanSlider').addEventListener('input', function() {
            document.getElementById('loanAmount').value = this.value;
            calculateEMI();
        });
        
        document.getElementById('interestRate').addEventListener('input', function() {
            document.getElementById('rateSlider').value = this.value;
            calculateEMI();
        });
        document.getElementById('rateSlider').addEventListener('input', function() {
            document.getElementById('interestRate').value = this.value;
            calculateEMI();
        });
        
        document.getElementById('loanTenure').addEventListener('input', function() {
            document.getElementById('tenureSlider').value = this.value;
            calculateEMI();
        });
        document.getElementById('tenureSlider').addEventListener('input', function() {
            document.getElementById('loanTenure').value = this.value;
            calculateEMI();
        });
        
        // Calculate EMI on page load
        calculateEMI();
        
        // ============ Property Comparison ============
        const currentProperty = {
            id: <?php echo $property['id']; ?>,
            title: "<?php echo addslashes($property['title']); ?>",
            price: <?php echo $property['price']; ?>,
            priceFormatted: "<?php echo formatIndianPrice($property['price']); ?>",
            city: "<?php echo addslashes($property['city']); ?>",
            area: <?php echo $property['area_sqft']; ?>,
            type: "<?php echo ucfirst($property['property_type']); ?>",
            bedrooms: "<?php echo $property['bedrooms'] ?: 'N/A'; ?>",
            bathrooms: "<?php echo $property['bathrooms'] ?: 'N/A'; ?>",
            image: "<?php echo !empty($property['image_url']) ? addslashes($property['image_url']) : ''; ?>",
            pricePerSqft: Math.round(<?php echo $property['price']; ?> / <?php echo $property['area_sqft']; ?>)
        };
        
        // Get comparison list from localStorage
        function getComparisonList() {
            const list = localStorage.getItem('propertyComparison');
            return list ? JSON.parse(list) : [];
        }
        
        // Save comparison list to localStorage
        function saveComparisonList(list) {
            localStorage.setItem('propertyComparison', JSON.stringify(list));
        }
        
        // Check if property is in comparison
        function isInComparison(id) {
            return getComparisonList().some(p => p.id === id);
        }
        
        // Toggle compare
        function toggleCompare() {
            let list = getComparisonList();
            const btn = document.getElementById('compareBtn');
            const btnText = document.getElementById('compareBtnText');
            const btnIcon = document.getElementById('compareBtnIcon');
            
            if (isInComparison(currentProperty.id)) {
                // Remove from comparison
                list = list.filter(p => p.id !== currentProperty.id);
                btn.classList.remove('added');
                btnText.textContent = 'Add to Compare';
                btnIcon.textContent = '⚖️';
            } else {
                // Add to comparison (max 3)
                if (list.length >= 3) {
                    alert('You can compare up to 3 properties at a time. Please remove one first.');
                    return;
                }
                list.push(currentProperty);
                btn.classList.add('added');
                btnText.textContent = 'Added to Compare';
                btnIcon.textContent = '✓';
            }
            
            saveComparisonList(list);
            updateComparisonBar();
        }
        
        // Update comparison bar
        function updateComparisonBar() {
            const list = getComparisonList();
            const bar = document.getElementById('comparisonBar');
            const itemsContainer = document.getElementById('comparisonItems');
            const compareNowBtn = document.getElementById('compareNowBtn');
            
            if (list.length > 0) {
                bar.classList.add('show');
            } else {
                bar.classList.remove('show');
            }
            
            // Generate items HTML
            let html = '';
            for (let i = 0; i < 3; i++) {
                if (list[i]) {
                    const p = list[i];
                    html += `
                        <div class="comparison-item">
                            <div class="comparison-item-img">
                                ${p.image ? `<img src="${p.image}" style="width:100%;height:100%;object-fit:cover;border-radius:6px;">` : '🏠'}
                            </div>
                            <div class="comparison-item-info">
                                <div class="comparison-item-title">${p.title}</div>
                                <div class="comparison-item-price">${p.priceFormatted}</div>
                            </div>
                            <button class="comparison-item-remove" onclick="removeFromComparison(${p.id})">×</button>
                        </div>
                    `;
                } else {
                    html += '<div class="comparison-slot-empty">+ Add Property</div>';
                }
            }
            itemsContainer.innerHTML = html;
            
            // Enable/disable compare button
            compareNowBtn.disabled = list.length < 2;
            
            // Update button state
            const btn = document.getElementById('compareBtn');
            const btnText = document.getElementById('compareBtnText');
            const btnIcon = document.getElementById('compareBtnIcon');
            
            if (isInComparison(currentProperty.id)) {
                btn.classList.add('added');
                btnText.textContent = 'Added to Compare';
                btnIcon.textContent = '✓';
            } else {
                btn.classList.remove('added');
                btnText.textContent = 'Add to Compare';
                btnIcon.textContent = '⚖️';
            }
        }
        
        // Remove from comparison
        function removeFromComparison(id) {
            let list = getComparisonList().filter(p => p.id !== id);
            saveComparisonList(list);
            updateComparisonBar();
        }
        
        // Clear all comparison
        function clearComparison() {
            saveComparisonList([]);
            updateComparisonBar();
        }
        
        // Show comparison modal
        function showComparison() {
            const list = getComparisonList();
            if (list.length < 2) {
                alert('Please add at least 2 properties to compare.');
                return;
            }
            
            const modal = document.getElementById('comparisonModal');
            const table = document.getElementById('comparisonTable');
            
            // Find best values for highlighting
            const lowestPrice = Math.min(...list.map(p => p.price));
            const largestArea = Math.max(...list.map(p => p.area));
            const lowestPricePerSqft = Math.min(...list.map(p => p.pricePerSqft));
            
            let html = `
                <tr>
                    <th></th>
                    ${list.map(p => `
                        <td class="comparison-property-header">
                            <div class="comparison-property-img">
                                ${p.image ? `<img src="${p.image}" style="width:100%;height:100%;object-fit:cover;border-radius:8px;">` : '🏠'}
                            </div>
                            <div class="comparison-property-title">${p.title}</div>
                            <div class="comparison-property-price">${p.priceFormatted}</div>
                        </td>
                    `).join('')}
                </tr>
                <tr>
                    <th>📍 Location</th>
                    ${list.map(p => `<td>${p.city}</td>`).join('')}
                </tr>
                <tr>
                    <th>🏗️ Property Type</th>
                    ${list.map(p => `<td>${p.type}</td>`).join('')}
                </tr>
                <tr>
                    <th>💰 Price</th>
                    ${list.map(p => `<td>${p.priceFormatted} ${p.price === lowestPrice ? '<span class="comparison-highlight">Best Price</span>' : ''}</td>`).join('')}
                </tr>
                <tr>
                    <th>📏 Area</th>
                    ${list.map(p => `<td>${p.area.toLocaleString('en-IN')} sq ft ${p.area === largestArea ? '<span class="comparison-highlight">Largest</span>' : ''}</td>`).join('')}
                </tr>
                <tr>
                    <th>💵 Price/sq ft</th>
                    ${list.map(p => `<td>₹${p.pricePerSqft.toLocaleString('en-IN')} ${p.pricePerSqft === lowestPricePerSqft ? '<span class="comparison-highlight">Best Value</span>' : ''}</td>`).join('')}
                </tr>
                <tr>
                    <th>🛏️ Bedrooms</th>
                    ${list.map(p => `<td>${p.bedrooms}</td>`).join('')}
                </tr>
                <tr>
                    <th>🚿 Bathrooms</th>
                    ${list.map(p => `<td>${p.bathrooms}</td>`).join('')}
                </tr>
                <tr>
                    <th>🔗 View Details</th>
                    ${list.map(p => `<td><a href="property-details.php?id=${p.id}" style="color: #667eea; font-weight: 600;">View Property →</a></td>`).join('')}
                </tr>
            `;
            
            table.innerHTML = html;
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        
        // Close comparison modal
        function closeComparisonModal() {
            document.getElementById('comparisonModal').classList.remove('show');
            document.body.style.overflow = '';
        }
        
        // Close modal on outside click
        document.getElementById('comparisonModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeComparisonModal();
            }
        });
        
        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeComparisonModal();
                closeContactPopup();
            }
        });
        
        // Initialize comparison bar on page load
        updateComparisonBar();
        
        // ============ Like/Favorite Functionality ============
        const isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
        
        function toggleLike() {
            if (!isLoggedIn) {
                showContactPopup();
                return;
            }
            
            const btn = document.getElementById('likeBtn');
            const icon = btn.querySelector('.like-icon');
            const count = document.getElementById('likeCount');
            
            fetch('property-details.php?id=<?php echo $property_id; ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'toggle_favorite=1'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.liked) {
                        btn.classList.add('liked');
                        icon.textContent = '❤️';
                        count.textContent = parseInt(count.textContent) + 1;
                    } else {
                        btn.classList.remove('liked');
                        icon.textContent = '🤍';
                        count.textContent = Math.max(0, parseInt(count.textContent) - 1);
                    }
                } else {
                    alert(data.message);
                }
            })
            .catch(err => {
                console.error('Error:', err);
            });
        }
        
        // ============ Contact Popup Functions ============
        function showContactPopup() {
            const popup = document.getElementById('contactPopup');
            if (popup) {
                popup.classList.add('show');
                document.body.style.overflow = 'hidden';
            }
        }
        
        function closeContactPopup() {
            const popup = document.getElementById('contactPopup');
            if (popup) {
                popup.classList.remove('show');
                document.body.style.overflow = '';
            }
        }
        
        // Show popup when clicking contact without login
        <?php if (!$is_logged_in): ?>
        document.addEventListener('DOMContentLoaded', function() {
            // Show contact popup automatically after 2 seconds for non-logged users
            setTimeout(function() {
                // Check if popup was already dismissed in this session
                if (!sessionStorage.getItem('contactPopupDismissed_<?php echo $property_id; ?>')) {
                    showContactPopup();
                }
            }, 2000);
            
            // Intercept clicks on contact items for non-logged users
            const contactItems = document.querySelectorAll('.contact-item a, .contact-link');
            contactItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    showContactPopup();
                });
            });
        });
        
        // Remember when user closes the popup
        function closeContactPopup() {
            const popup = document.getElementById('contactPopup');
            if (popup) {
                popup.classList.remove('show');
                document.body.style.overflow = '';
                // Remember this popup was dismissed for this property in this session
                sessionStorage.setItem('contactPopupDismissed_<?php echo $property_id; ?>', 'true');
            }
        }
        <?php else: ?>
        function closeContactPopup() {
            const popup = document.getElementById('contactPopup');
            if (popup) {
                popup.classList.remove('show');
                document.body.style.overflow = '';
            }
        }
        <?php endif; ?>
        
        // ============ Lightbox Functions ============
        // All images array for lightbox navigation
        const allLightboxImages = [
            <?php 
            $lightbox_images = [];
            // Add featured image
            if (!empty($property['image_url'])) {
                $lightbox_images[] = ['url' => $property['image_url'], 'caption' => 'Featured Image'];
            }
            // Add category images
            foreach ($property_images as $img) {
                $cat_name = $image_category_names[$img['image_category']] ?? 'Other';
                $lightbox_images[] = ['url' => $img['image_url'], 'caption' => $cat_name];
            }
            // Output as JavaScript array
            foreach ($lightbox_images as $index => $img) {
                echo ($index > 0 ? ',' : '') . '{"url":"' . addslashes($img['url']) . '","caption":"' . addslashes($img['caption']) . '"}';
            }
            ?>
        ];
        
        let currentLightboxIndex = 0;
        
        function openLightbox(imageUrl, title) {
            if (!imageUrl) return;
            
            // Find index of the clicked image
            currentLightboxIndex = allLightboxImages.findIndex(img => img.url === imageUrl);
            if (currentLightboxIndex === -1) currentLightboxIndex = 0;
            
            showLightboxImage();
            
            const lightbox = document.getElementById('imageLightbox');
            lightbox.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        // Open lightbox at current viewed image index
        function openLightboxAtCurrentIndex() {
            currentLightboxIndex = currentImageIndex;
            showLightboxImage();
            
            const lightbox = document.getElementById('imageLightbox');
            lightbox.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function showLightboxImage() {
            const lightboxImg = document.getElementById('lightboxImage');
            const lightboxCaption = document.getElementById('lightboxCaption');
            const lightboxCounter = document.getElementById('lightboxCounter');
            
            if (allLightboxImages.length > 0 && allLightboxImages[currentLightboxIndex]) {
                lightboxImg.src = allLightboxImages[currentLightboxIndex].url;
                lightboxCaption.textContent = allLightboxImages[currentLightboxIndex].caption;
                lightboxCounter.textContent = (currentLightboxIndex + 1) + ' / ' + allLightboxImages.length;
            }
        }
        
        function navigateLightbox(direction) {
            currentLightboxIndex += direction;
            
            // Loop around
            if (currentLightboxIndex >= allLightboxImages.length) {
                currentLightboxIndex = 0;
            } else if (currentLightboxIndex < 0) {
                currentLightboxIndex = allLightboxImages.length - 1;
            }
            
            showLightboxImage();
        }
        
        function closeLightbox() {
            const lightbox = document.getElementById('imageLightbox');
            const lightboxImg = document.getElementById('lightboxImage');
            lightbox.classList.remove('active');
            if (lightboxImg) {
                lightboxImg.classList.remove('zoomed');
                lightboxImg.style.transformOrigin = 'center center';
            }
            document.body.style.overflow = '';
            isZoomed = false;
        }
        
        // Zoom functionality for lightbox
        let isZoomed = false;
        let isDragging = false;
        let startX, startY;
        
        function toggleZoom(e) {
            const img = document.getElementById('lightboxImage');
            if (!img) return;
            isZoomed = !isZoomed;
            
            if (isZoomed) {
                // Calculate click position for zoom origin
                const rect = img.getBoundingClientRect();
                const x = ((e.clientX - rect.left) / rect.width) * 100;
                const y = ((e.clientY - rect.top) / rect.height) * 100;
                img.style.transformOrigin = x + '% ' + y + '%';
                img.classList.add('zoomed');
            } else {
                img.classList.remove('zoomed');
                img.style.transformOrigin = 'center center';
            }
        }
        
        // Initialize zoom events after DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            const lightboxImg = document.getElementById('lightboxImage');
            if (!lightboxImg) return;
            
            // Double-click to zoom
            lightboxImg.addEventListener('dblclick', function(e) {
                e.stopPropagation();
                toggleZoom(e);
            });
            
            // Pan/drag when zoomed
            lightboxImg.addEventListener('mousedown', function(e) {
                if (!isZoomed) return;
                isDragging = true;
                this.classList.add('dragging');
                startX = e.clientX;
                startY = e.clientY;
                e.preventDefault();
            });
            
            document.addEventListener('mousemove', function(e) {
                if (!isDragging || !isZoomed) return;
                const img = document.getElementById('lightboxImage');
                if (!img) return;
                const dx = e.clientX - startX;
                const dy = e.clientY - startY;
                
                // Get current origin
                const origin = img.style.transformOrigin.split(' ');
                let currentX = parseFloat(origin[0]) || 50;
                let currentY = parseFloat(origin[1]) || 50;
                
                // Adjust origin based on drag (inverted for natural feel)
                currentX = Math.max(0, Math.min(100, currentX - dx * 0.1));
                currentY = Math.max(0, Math.min(100, currentY - dy * 0.1));
                
                img.style.transformOrigin = currentX + '% ' + currentY + '%';
                
                startX = e.clientX;
                startY = e.clientY;
            });
            
            document.addEventListener('mouseup', function() {
                isDragging = false;
                const img = document.getElementById('lightboxImage');
                if (img) img.classList.remove('dragging');
            });
            
            // Touch support for mobile zoom
            let lastTap = 0;
            lightboxImg.addEventListener('touchend', function(e) {
                const currentTime = new Date().getTime();
                const tapLength = currentTime - lastTap;
                if (tapLength < 300 && tapLength > 0) {
                    // Double tap detected
                    e.preventDefault();
                    const touch = e.changedTouches[0];
                    toggleZoom({ clientX: touch.clientX, clientY: touch.clientY, stopPropagation: function(){} });
                }
                lastTap = currentTime;
            });
        });
        
        // Change main image when clicking thumbnail
        function changeMainImage(imageUrl, thumbnail) {
            const mainImage = document.getElementById('mainImage');
            if (mainImage) {
                mainImage.src = imageUrl;
            }
            
            // Update active thumbnail
            document.querySelectorAll('.image-thumbnail').forEach((t, index) => {
                t.classList.remove('active');
                if (t === thumbnail) {
                    currentImageIndex = index;
                    updateImageCounter();
                }
            });
            thumbnail.classList.add('active');
        }

        // Image navigation with arrow buttons
        let currentImageIndex = 0;
        const allImageUrls = [<?php 
            $urls = [];
            foreach ($all_images as $img) {
                $urls[] = "'" . htmlspecialchars($img['url'], ENT_QUOTES) . "'";
            }
            echo implode(',', $urls);
        ?>];

        function navigateImage(direction) {
            if (allImageUrls.length <= 1) return;
            
            currentImageIndex += direction;
            
            // Loop around
            if (currentImageIndex >= allImageUrls.length) {
                currentImageIndex = 0;
            } else if (currentImageIndex < 0) {
                currentImageIndex = allImageUrls.length - 1;
            }
            
            // Update main image
            const mainImage = document.getElementById('mainImage');
            if (mainImage) {
                mainImage.src = allImageUrls[currentImageIndex];
            }
            
            // Update active thumbnail
            const thumbnails = document.querySelectorAll('.image-thumbnail');
            thumbnails.forEach((t, index) => {
                t.classList.toggle('active', index === currentImageIndex);
            });
            
            // Scroll thumbnail into view
            if (thumbnails[currentImageIndex]) {
                thumbnails[currentImageIndex].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            }
            
            updateImageCounter();
        }

        function updateImageCounter() {
            const counter = document.getElementById('currentImageIndex');
            if (counter) {
                counter.textContent = currentImageIndex + 1;
            }
        }
        
        // Keyboard navigation for lightbox and main image
        document.addEventListener('keydown', function(e) {
            const lightbox = document.getElementById('imageLightbox');
            if (lightbox && lightbox.classList.contains('active')) {
                if (e.key === 'Escape') {
                    closeLightbox();
                } else if (e.key === 'ArrowLeft') {
                    navigateLightbox(-1);
                } else if (e.key === 'ArrowRight') {
                    navigateLightbox(1);
                }
            } else {
                // Navigate main image with arrow keys when lightbox is closed
                if (e.key === 'ArrowLeft') {
                    navigateImage(-1);
                } else if (e.key === 'ArrowRight') {
                    navigateImage(1);
                }
            }
        });
    </script>
    
    <!-- Image Lightbox -->
    <div id="imageLightbox" class="lightbox" onclick="if(event.target === this) closeLightbox()">
        <button class="lightbox-nav lightbox-prev" onclick="event.stopPropagation(); navigateLightbox(-1)">&#10094;</button>
        <div class="lightbox-content">
            <button class="lightbox-close" onclick="closeLightbox()">&times;</button>
            <img id="lightboxImage" src="" alt="Property Image">
            <p id="lightboxCaption" class="lightbox-caption"></p>
            <p id="lightboxCounter" class="lightbox-counter"></p>
            <p class="lightbox-zoom-hint">Double-click to zoom</p>
        </div>
        <button class="lightbox-nav lightbox-next" onclick="event.stopPropagation(); navigateLightbox(1)">&#10095;</button>
    </div>
</body>
</html>
