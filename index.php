<?php
include('includes/config.php');

// Get recent listings - optimized query with specific columns
$sql = "SELECT id, title, price, city, area_sqft, bedrooms, bathrooms, property_type, category, image_url, listing_type, pre_lease 
        FROM properties 
        WHERE status='available' AND approval_status='approved' 
        ORDER BY created_at DESC LIMIT 20";
$result = $conn->query($sql);
$properties = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Fetch distinct cities - optimized
$cities_result = $conn->query("SELECT DISTINCT city FROM properties WHERE status='available' AND approval_status='approved' AND city != '' ORDER BY city ASC");
$available_cities = $cities_result ? array_column($cities_result->fetch_all(MYSQLI_ASSOC), 'city') : [];

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="JD Realty & Investment - Find your perfect property. Browse residential, commercial, and plot properties. Trusted real estate platform for buying and selling properties online.">
    <meta name="keywords"
        content="real estate, properties for sale, apartments, villas, commercial property, residential plot, property listing, buy property, sell property, property investment">
    <meta name="author" content="JD Realty & Investment">
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <meta property="og:title" content="JD Realty & Investment - Real Estate Solutions">
    <meta property="og:description"
        content="Find your perfect property. Browse residential, commercial, and plot properties. Trusted real estate platform.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo defined('SITE_URL') ? SITE_URL : 'https://jdrealtyinvestment.com'; ?>/">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="JD Realty & Investment - Real Estate Solutions">
    <meta name="twitter:description" content="Find your perfect property on JD Realty & Investment">
    <link rel="canonical" href="<?php echo defined('SITE_URL') ? SITE_URL : 'https://jdrealtyinvestment.com'; ?>/">
    <link rel="icon" type="image/x-icon" href="images/jd-logo.svg">
    
    <!-- Additional Google SEO Meta Tags -->
    <meta name="author" content="JD Realty & Investment">
    <meta name="geo.region" content="IN-MH">
    <meta name="geo.placename" content="Thane, Maharashtra">
    <meta name="google-site-verification" content="YOUR_VERIFICATION_CODE_HERE">
    <meta name="format-detection" content="telephone=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <!-- Preconnect for Performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="https://images.unsplash.com">
    
    <!-- Preload critical resources -->
    <link rel="preload" href="css/style.css?v=20251203c" as="style">
    <link rel="preload" href="images/jd-logo.svg" as="image">
    
    <title>JD Realty & Investment - Real Estate Solutions | Buy Sell Properties Online</title>
    <link rel="stylesheet" href="css/style.css?v=20251203c">
    
    <!-- Structured Data - Organization Schema -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "RealEstateAgent",
        "name": "JD Realty & Investment",
        "url": "<?php echo defined('SITE_URL') ? SITE_URL : 'https://jdrealtyinvestment.com'; ?>/",
        "logo": "<?php echo defined('SITE_URL') ? SITE_URL : 'https://jdrealtyinvestment.com'; ?>/images/jd-logo.svg",
        "description": "Trusted real estate platform for buying and selling properties in Thane, Maharashtra",
        "address": {
            "@type": "PostalAddress",
            "addressLocality": "Thane",
            "addressRegion": "Maharashtra",
            "addressCountry": "IN"
        },
        "areaServed": {
            "@type": "GeoCircle",
            "geoMidpoint": {
                "@type": "GeoCoordinates",
                "latitude": 19.2183,
                "longitude": 72.9781
            },
            "geoRadius": "50000"
        },
        "priceRange": "₹₹₹",
        "openingHoursSpecification": {
            "@type": "OpeningHoursSpecification",
            "dayOfWeek": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"],
            "opens": "09:00",
            "closes": "18:00"
        }
    }
    </script>
    
    <!-- Structured Data - Website Search -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "JD Realty & Investment",
        "url": "<?php echo defined('SITE_URL') ? SITE_URL : 'https://jdrealtyinvestment.com'; ?>/",
        "potentialAction": {
            "@type": "SearchAction",
            "target": "<?php echo defined('SITE_URL') ? SITE_URL : 'https://jdrealtyinvestment.com'; ?>/search.php?city={search_term_string}",
            "query-input": "required name=search_term_string"
        }
    }
    </script>
    <style>
        /* Keyframe Animations */
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-40px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes scaleIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #1f2937;
        }

        .navbar {
            background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.15);
            padding: 12px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            animation: fadeInDown 0.6s ease;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo {
            font-size: 22px;
            font-weight: bold;
            color: #d97706;
        }

        .logo span {
            color: #ffffff;
        }

        .tagline {
            color: #9ca3af;
            font-size: 10px;
            font-weight: 600;
        }

        .nav-links {
            display: flex;
            gap: 25px;
            align-items: center;
        }

        .nav-links a {
            color: #ffffff;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .nav-links a:hover {
            color: #fbbf24;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-name {
            color: #6b7280;
            font-size: 13px;
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
        }
        
        .login-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white !important;
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            box-shadow: 0 2px 10px rgba(102, 126, 234, 0.3);
            transition: all 0.3s ease;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        /* Nav Button Style - Same as Login */
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

        /* Hero Section - Compact */
        .hero {
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.6)),
                url('https://images.unsplash.com/photo-1486325212027-8081e485255e?w=1200&q=80') center/cover;
            background-attachment: fixed;
            padding: 60px 40px 100px;
            text-align: center;
            position: relative;
            min-height: 400px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .hero h1 {
            font-size: 40px;
            margin-bottom: 10px;
            color: white;
            font-weight: 700;
            text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.7);
        }

        .hero p {
            font-size: 18px;
            color: #f0f0f0;
            margin-bottom: 25px;
            text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.7);
        }

        /* 99acres Style Search Tabs */
        .search-tabs {
            display: flex;
            justify-content: center;
            gap: 0;
            margin-bottom: 0;
        }

        .search-tab {
            padding: 12px 30px;
            background: rgba(255,255,255,0.9);
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            color: #6b7280;
            transition: all 0.3s ease;
            border-radius: 8px 8px 0 0;
            margin: 0 2px;
        }

        .search-tab.active {
            background: white;
            color: #667eea;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
        }

        .search-tab:hover {
            color: #667eea;
        }

        /* Compact Search Bar - 99acres Style */
        .compact-search {
            background: white;
            padding: 20px 25px;
            border-radius: 0 12px 12px 12px;
            max-width: 950px;
            width: 100%;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            animation: scaleIn 0.6s ease;
        }

        .search-row {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-input-wrapper {
            flex: 2;
            position: relative;
            min-width: 200px;
        }

        .search-input-wrapper input {
            width: 100%;
            padding: 14px 14px 14px 45px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .search-input-wrapper input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .search-input-wrapper .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 18px;
        }

        .search-select {
            padding: 14px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            min-width: 140px;
            cursor: pointer;
            background: white;
            transition: all 0.3s ease;
        }

        .search-select:focus {
            outline: none;
            border-color: #667eea;
        }

        .budget-dropdown {
            position: relative;
        }

        .budget-btn {
            padding: 14px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            background: white;
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 150px;
            justify-content: space-between;
        }

        .budget-panel {
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            padding: 20px;
            min-width: 300px;
            z-index: 100;
            display: none;
            margin-top: 5px;
        }

        .budget-panel.show {
            display: block;
        }

        .budget-inputs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .budget-inputs label {
            font-size: 12px;
            color: #6b7280;
            display: block;
            margin-bottom: 5px;
        }

        .budget-inputs input {
            width: 100%;
            padding: 10px;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            font-size: 14px;
        }

        .search-btn-main {
            padding: 14px 35px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .search-btn-main:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        /* Quick Filters */
        .quick-filters {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }

        .quick-filter {
            padding: 6px 14px;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #4b5563;
        }

        .quick-filter:hover, .quick-filter.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        /* Popular Tools Section */
        .tools-section {
            background: #f8fafc;
            padding: 50px 20px;
        }

        .tools-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-title {
            font-size: 26px;
            margin-bottom: 30px;
            color: #1f2937;
            text-align: center;
        }

        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .tool-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
        }

        .tool-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .tool-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .tool-info h4 {
            font-size: 15px;
            margin-bottom: 5px;
            color: #1f2937;
        }

        .tool-info p {
            font-size: 12px;
            color: #6b7280;
        }

        /* Explore Options */
        .explore-section {
            padding: 50px 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .explore-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
        }

        .explore-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .explore-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .explore-card img {
            width: 100%;
            height: 120px;
            object-fit: cover;
        }

        .explore-card .card-label {
            padding: 15px;
            text-align: center;
            font-weight: 600;
            color: #1f2937;
            font-size: 14px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .section {
            margin-bottom: 60px;
        }

        .section h2 {
            font-size: 26px;
            margin-bottom: 25px;
            color: #1f2937;
            position: relative;
            padding-bottom: 12px;
        }

        .section h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 2px;
        }

        .properties-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }

        .property-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: fadeInUp 0.6s ease forwards;
            opacity: 0;
        }

        .property-card:nth-child(1) { animation-delay: 0.1s; }
        .property-card:nth-child(2) { animation-delay: 0.2s; }
        .property-card:nth-child(3) { animation-delay: 0.3s; }
        .property-card:nth-child(4) { animation-delay: 0.4s; }

        .property-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .property-image {
            width: 100%;
            height: 180px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            overflow: hidden;
        }

        .property-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: #10b981;
            color: white;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }

        .property-info {
            padding: 18px;
        }

        .property-type {
            color: #d97706;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .property-desc {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            margin: 8px 0;
            line-height: 1.3;
        }

        .property-details {
            display: flex;
            justify-content: space-between;
            color: #6b7280;
            font-size: 13px;
            margin: 12px 0;
        }

        .property-price {
            font-size: 18px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 12px;
        }

        .contact-btn {
            width: 100%;
            padding: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s ease;
        }

        .contact-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }

        .empty-state h3 {
            font-size: 22px;
            margin-bottom: 10px;
            color: #1f2937;
        }

        /* Enhanced Footer */
        .footer {
            background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
            color: white;
            padding: 60px 40px 30px;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr 1.5fr;
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-section h3 {
            font-size: 16px;
            margin-bottom: 20px;
            color: white;
            position: relative;
            padding-bottom: 10px;
        }

        .footer-section h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 30px;
            height: 2px;
            background: #667eea;
        }

        .footer-section p {
            color: #9ca3af;
            font-size: 14px;
            line-height: 1.7;
        }

        .footer-section ul {
            list-style: none;
            padding: 0;
        }

        .footer-section ul li {
            margin-bottom: 10px;
        }

        .footer-section ul li a {
            color: #9ca3af;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .footer-section ul li a:hover {
            color: #667eea;
        }

        /* Social Media Icons */
        .social-icons {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }

        .social-icon {
            width: 40px;
            height: 40px;
            background: #374151;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            font-size: 18px;
            transition: all 0.3s ease;
        }

        .social-icon:hover {
            transform: translateY(-3px);
        }

        .social-icon.facebook:hover { background: #1877f2; }
        .social-icon.instagram:hover { background: linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888); }
        .social-icon.twitter:hover { background: #1da1f2; }
        .social-icon.youtube:hover { background: #ff0000; }
        .social-icon.linkedin:hover { background: #0077b5; }
        .social-icon.whatsapp:hover { background: #25d366; }

        .contact-info-footer {
            margin-top: 15px;
        }

        .contact-info-footer p {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            font-size: 14px;
            color: #9ca3af;
        }

        .footer-bottom {
            border-top: 1px solid #374151;
            padding-top: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .footer-bottom p {
            color: #6b7280;
            font-size: 13px;
        }

        .footer-bottom-links {
            display: flex;
            gap: 20px;
        }

        .footer-bottom-links a {
            color: #6b7280;
            text-decoration: none;
            font-size: 13px;
            transition: color 0.3s ease;
        }

        .footer-bottom-links a:hover {
            color: #667eea;
        }

        /* Mobile Responsive */
        @media (max-width: 1024px) {
            .footer-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 10px 15px;
                flex-wrap: nowrap;
                gap: 8px;
                overflow-x: auto;
            }
            
            .logo-section {
                flex-shrink: 0;
            }
            
            .logo-section img {
                height: 40px !important;
                width: 40px !important;
            }
            
            .logo-section span {
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
                padding: 6px 12px;
                font-size: 11px;
                white-space: nowrap;
            }
            
            .logout-btn {
                padding: 6px 12px;
                font-size: 11px;
                white-space: nowrap;
            }
            
            .user-menu {
                gap: 6px;
                flex-shrink: 0;
            }
            
            .user-name {
                display: none;
            }

            .hero {
                padding: 40px 15px 80px;
                min-height: 350px;
            }

            .hero h1 {
                font-size: 28px;
            }

            .search-row {
                flex-direction: column;
            }

            .search-input-wrapper,
            .search-select,
            .budget-btn,
            .search-btn-main {
                width: 100%;
            }

            .footer-grid {
                grid-template-columns: 1fr 1fr;
            }

            .footer-bottom {
                flex-direction: column;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .navbar {
                padding: 8px 10px;
            }
            
            .logo-section img {
                height: 35px !important;
                width: 35px !important;
            }
            
            .logo-section span {
                font-size: 12px !important;
            }
            
            .nav-btn {
                padding: 5px 8px;
                font-size: 10px;
            }
            
            .login-btn {
                padding: 5px 10px;
                font-size: 10px;
            }
            
            .logout-btn {
                padding: 5px 10px;
                font-size: 10px;
            }
            
            .footer-grid {
                grid-template-columns: 1fr;
            }

            .search-tabs {
                flex-wrap: wrap;
            }

            .search-tab {
                flex: 1;
                min-width: 80px;
                padding: 10px 15px;
                font-size: 12px;
            }
        }

        /* Logo text responsive - override */
        @media (max-width: 768px) {
            .logo-section span, .logo span {
                font-size: 14px !important;
            }
            .logo-section img, .logo img {
                height: 40px !important;
                width: 40px !important;
            }
        }
        @media (max-width: 480px) {
            .logo-section span, .logo span {
                font-size: 12px !important;
            }
            .logo-section img, .logo img {
                height: 35px !important;
                width: 35px !important;
            }
        }
    </style>
</head>

<body>
    <div class="navbar">
        <a href="index.php" class="logo-section" style="text-decoration: none; display: flex; align-items: center; gap: 12px;">
            <img src="images/jd-logo.svg?v=20251203" alt="JD Realty & Investment" style="height: 60px; width: 60px;">
            <span style="font-size: 24px; font-weight: bold; color: #d4a84b; text-shadow: 1px 1px 2px rgba(0,0,0,0.3);">JD Realty Investment</span>
        </a>

        <?php if ($is_logged_in): ?>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="search.php">Search</a>
            <a href="user-dashboard.php">My Properties</a>
        </div>
        <?php endif; ?>

        <div class="user-menu">
            <?php if ($is_logged_in): ?>
                <span class="user-name">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="?logout=true" class="logout-btn">Logout</a>
            <?php else: ?>
                <a href="index.php" class="nav-btn">Home</a>
                <a href="search.php" class="nav-btn">Search</a>
                <a href="login.php" class="login-btn">Log in</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="hero">
        <div style="max-width: 950px; width: 100%;">
            <h1>Find Your Perfect Property</h1>
            <p>Discover premium real estate opportunities with JD Realty & Investment</p>
            
            <!-- 99acres Style Search Tabs -->
            <div class="search-tabs">
                <button class="search-tab active" data-tab="buy" onclick="switchTab('buy')">Buy</button>
                <button class="search-tab" data-tab="rent" onclick="switchTab('rent')">Rent</button>
                <button class="search-tab" data-tab="residential" onclick="switchTab('residential')">Residential</button>
                <button class="search-tab" data-tab="commercial" onclick="switchTab('commercial')">Commercial</button>
                <button class="search-tab" data-tab="plots" onclick="switchTab('plots')">Plots/Land</button>
                <button class="search-tab" data-tab="preleased" onclick="switchTab('preleased')">Pre-Leased</button>
            </div>
            
            <!-- Compact Search Bar -->
            <div class="compact-search">
                <form method="GET" action="search.php" id="searchForm">
                    <input type="hidden" name="purpose" id="purposeInput" value="buy">
                    <input type="hidden" name="pre_lease" id="preLeaseInput" value="">
                    <div class="search-row">
                        <div class="search-input-wrapper">
                            <span class="search-icon">📍</span>
                            <select name="city" class="search-select" style="padding-left: 35px;">
                                <option value="">All Locations</option>
                                <?php foreach ($available_cities as $city_name): ?>
                                    <option value="<?php echo htmlspecialchars($city_name); ?>"><?php echo htmlspecialchars($city_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <select name="type" class="search-select">
                            <option value="">Property Type</option>
                            <option value="residential">Residential</option>
                            <option value="commercial">Commercial</option>
                            <option value="plot">Plot/Land</option>
                        </select>
                        
                        <select name="category" class="search-select">
                            <option value="">Category</option>
                            <option value="1bhk">1 BHK</option>
                            <option value="2bhk">2 BHK</option>
                            <option value="3bhk">3 BHK</option>
                            <option value="4bhk">4 BHK</option>
                            <option value="above4">4+ BHK</option>
                            <option value="shop">Shop</option>
                            <option value="office">Office</option>
                        </select>
                        
                        <div class="budget-dropdown">
                            <button type="button" class="budget-btn" onclick="toggleBudget()">
                                <span id="budgetLabel">Budget</span>
                                <span>▼</span>
                            </button>
                            <div class="budget-panel" id="budgetPanel">
                                <div class="budget-inputs">
                                    <div>
                                        <label>Min Price</label>
                                        <input type="number" name="min_price" id="minPrice" placeholder="₹ Min" value="0">
                                    </div>
                                    <div>
                                        <label>Max Price</label>
                                        <input type="number" name="max_price" id="maxPrice" placeholder="₹ Max" value="">
                                    </div>
                                </div>
                                <button type="button" onclick="applyBudget()" style="width:100%; margin-top:15px; padding:10px; background:#667eea; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600;">Apply</button>
                            </div>
                        </div>
                        
                        <button type="submit" class="search-btn-main">
                            🔍 Search
                        </button>
                    </div>
                    
                    <!-- Quick Filters -->
                    <div class="quick-filters">
                        <span class="quick-filter" onclick="setFilter('verified')">✓ Verified</span>
                        <span class="quick-filter" onclick="setFilter('photos')">📷 With Photos</span>
                        <span class="quick-filter" onclick="setFilter('owner')">👤 Owner</span>
                        <span class="quick-filter" onclick="setFilter('new')">🆕 New Launch</span>
                        <span class="quick-filter" onclick="setFilter('furnished')">🛋️ Furnished</span>
                        <span class="quick-filter" onclick="setFilter('ready')">🏠 Ready to Move</span>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Popular Tools Section -->
    <div class="tools-section">
        <div class="tools-container">
            <h2 class="section-title">🛠️ Popular Tools</h2>
            <div class="tools-grid">
                <a href="#emi-calculator" class="tool-card" onclick="openEMICalculator()">
                    <div class="tool-icon">🏦</div>
                    <div class="tool-info">
                        <h4>EMI Calculator</h4>
                        <p>Calculate your home loan EMI</p>
                    </div>
                </a>
                <a href="#budget-calculator" class="tool-card" onclick="openBudgetCalculator()">
                    <div class="tool-icon">💰</div>
                    <div class="tool-info">
                        <h4>Budget Calculator</h4>
                        <p>Check your affordability range</p>
                    </div>
                </a>
                <a href="#area-converter" class="tool-card" onclick="openAreaConverter()">
                    <div class="tool-icon">📐</div>
                    <div class="tool-info">
                        <h4>Area Converter</h4>
                        <p>Convert sq ft, sq m, acres, etc.</p>
                    </div>
                </a>
                <a href="#property-comparison" class="tool-card" onclick="openComparisonFromHome()">
                    <div class="tool-icon">⚖️</div>
                    <div class="tool-info">
                        <h4>Compare Properties</h4>
                        <p>Compare up to 3 properties</p>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- Explore Real Estate Options -->
    <div class="explore-section">
        <h2 class="section-title">🏘️ Explore Real Estate Options</h2>
        <div class="explore-grid">
            <a href="search.php?purpose=buy&type=residential" class="explore-card">
                <img src="https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=400&q=80" alt="Buying a Home">
                <div class="card-label">🏠 Buy a Home</div>
            </a>
            <a href="search.php?purpose=rent&type=residential" class="explore-card">
                <img src="https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=400&q=80" alt="Rent a Home">
                <div class="card-label">🏡 Rent a Home</div>
            </a>
            <a href="search.php?type=commercial" class="explore-card">
                <img src="https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=400&q=80" alt="Commercial">
                <div class="card-label">🏢 Commercial</div>
            </a>
            <a href="search.php?type=plot" class="explore-card">
                <img src="https://images.unsplash.com/photo-1500382017468-9049fed747ef?w=400&q=80" alt="Plots">
                <div class="card-label">🌳 Plots & Land</div>
            </a>
            <a href="list-property.php" class="explore-card">
                <img src="https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=400&q=80" alt="Sell Property">
                <div class="card-label">📝 Sell/Rent Property</div>
            </a>
        </div>
    </div>

    <div class="container">
        <div class="section" id="properties">
            <h2>Recent Listings</h2>
            <?php if (!empty($properties)): ?>
                <div class="properties-grid">
                    <?php foreach ($properties as $property): ?>
                        <div class="property-card">
                            <div class="property-image" style="height: 200px; display: flex; align-items: center; justify-content: center; background: #f3f4f6;">
                                <?php if (!empty($property['image_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($property['image_url']); ?>"
                                        alt="<?php echo htmlspecialchars($property['title'] . ' - ' . ucfirst($property['property_type']) . ' in ' . $property['city']); ?>"
                                        loading="lazy"
                                        style="max-width: 100%; max-height: 100%; object-fit: contain;"
                                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div style="width: 100%; height: 100%; display: none; align-items: center; justify-content: center; color: #999; font-size: 32px; background: #f3f4f6;">📍</div>
                                <?php else: ?>
                                    <div
                                        style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #999; font-size: 32px;"
                                        role="img" aria-label="Property placeholder image">
                                        📍</div>
                                <?php endif; ?>
                            </div>
                            <div class="property-info">
                                <div class="property-type"><?php echo ucfirst($property['property_type']); ?></div>
                                <div class="property-desc"><?php echo htmlspecialchars($property['title']); ?></div>
                                <div class="property-details">
                                    <span><?php echo htmlspecialchars($property['city']); ?></span>
                                    <span><?php echo number_format($property['area_sqft']); ?> sq ft</span>
                                </div>
                                <div class="property-price"><?php echo formatIndianPrice($property['price']); ?></div>
                                <button class="contact-btn"
                                    onclick="location.href='property-details.php?id=<?php echo $property['id']; ?>'">View
                                    Details</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No Properties Available Yet</h3>
                    <p>Properties will be displayed here soon. Check back later!</p>
                </div>
            <?php endif; ?>

            <!-- CTA Section -->
            <?php if ($is_logged_in): ?>
                <div
                    style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px; border-radius: 10px; margin-top: 40px; text-align: center; color: white;">
                    <h3 style="font-size: 24px; margin-bottom: 15px;">Ready to List Your Property?</h3>
                    <p style="margin-bottom: 20px;">Get your property in front of thousands of potential buyers and renters
                    </p>
                    <a href="list-property.php" class="contact-btn"
                        style="max-width: 200px; margin: 0 auto; display: block;">List Your Property</a>
                </div>
            <?php else: ?>
                <div style="background: #f3f4f6; padding: 40px; border-radius: 10px; margin-top: 40px; text-align: center;">
                    <h3 style="font-size: 24px; margin-bottom: 15px; color: #1f2937;">Want to List Your Property?</h3>
                    <p style="margin-bottom: 20px; color: #6b7280;">Sign in or create an account to start listing your
                        properties</p>
                    <a href="login.php"
                        style="padding: 12px 30px; background: #667eea; color: white; border-radius: 6px; text-decoration: none; font-weight: 600; display: inline-block; transition: all 0.3s ease;"
                        onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 5px 15px rgba(102, 126, 234, 0.4)'"
                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">Sign In / Sign Up</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include('includes/footer.php'); ?>

    <script>
        // Tab Switching
        function switchTab(tab) {
            document.querySelectorAll('.search-tab').forEach(t => t.classList.remove('active'));
            document.querySelector(`[data-tab="${tab}"]`).classList.add('active');
            document.getElementById('purposeInput').value = tab === 'rent' ? 'rent' : 'buy';
            document.getElementById('preLeaseInput').value = tab === 'preleased' ? 'yes' : '';
            
            // Update property type based on tab
            const typeSelect = document.querySelector('select[name="type"]');
            if (tab === 'residential') {
                typeSelect.value = 'residential';
            } else if (tab === 'commercial') {
                typeSelect.value = 'commercial';
            } else if (tab === 'plots') {
                typeSelect.value = 'plot';
            } else if (tab === 'preleased') {
                typeSelect.value = 'commercial'; // Pre-leased properties are typically commercial
            } else {
                typeSelect.value = '';
            }
        }

        // Budget Dropdown
        function toggleBudget() {
            document.getElementById('budgetPanel').classList.toggle('show');
        }

        function applyBudget() {
            const min = document.getElementById('minPrice').value || 0;
            const max = document.getElementById('maxPrice').value || '∞';
            const label = `₹${formatBudget(min)} - ₹${formatBudget(max)}`;
            document.getElementById('budgetLabel').textContent = label;
            document.getElementById('budgetPanel').classList.remove('show');
        }

        function formatBudget(val) {
            if (val === '∞' || val === '') return '∞';
            val = parseInt(val);
            if (val >= 10000000) return (val/10000000).toFixed(1) + ' Cr';
            if (val >= 100000) return (val/100000).toFixed(1) + ' L';
            if (val >= 1000) return (val/1000).toFixed(0) + ' K';
            return val;
        }

        // Close budget panel when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.budget-dropdown')) {
                document.getElementById('budgetPanel').classList.remove('show');
            }
        });

        // Quick Filters
        function setFilter(filter) {
            const el = event.target;
            el.classList.toggle('active');
        }

        // EMI Calculator Modal
        function openEMICalculator() {
            event.preventDefault();
            const modal = document.createElement('div');
            modal.id = 'emiModal';
            modal.innerHTML = `
                <div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999;">
                    <div style="background: white; padding: 30px; border-radius: 15px; max-width: 450px; width: 90%; max-height: 90vh; overflow-y: auto;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                            <h3 style="font-size: 20px; color: #1f2937;">🏦 EMI Calculator</h3>
                            <button onclick="document.getElementById('emiModal').remove()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #6b7280;">&times;</button>
                        </div>
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Loan Amount (₹)</label>
                            <input type="number" id="loanAmount" value="5000000" style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 16px;">
                        </div>
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Interest Rate (% per annum)</label>
                            <input type="number" id="interestRate" value="8.5" step="0.1" style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 16px;">
                        </div>
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Loan Tenure (Years)</label>
                            <input type="number" id="loanTenure" value="20" style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 16px;">
                        </div>
                        <button onclick="calculateEMI()" style="width: 100%; padding: 14px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer;">Calculate EMI</button>
                        <div id="emiResult" style="margin-top: 25px; padding: 20px; background: #f8fafc; border-radius: 10px; display: none;">
                            <div style="text-align: center;">
                                <p style="color: #6b7280; margin-bottom: 5px;">Monthly EMI</p>
                                <p id="emiAmount" style="font-size: 32px; font-weight: 700; color: #667eea;"></p>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 20px;">
                                <div style="text-align: center; padding: 15px; background: white; border-radius: 8px;">
                                    <p style="color: #6b7280; font-size: 12px;">Total Interest</p>
                                    <p id="totalInterest" style="font-weight: 600; color: #ef4444;"></p>
                                </div>
                                <div style="text-align: center; padding: 15px; background: white; border-radius: 8px;">
                                    <p style="color: #6b7280; font-size: 12px;">Total Payment</p>
                                    <p id="totalPayment" style="font-weight: 600; color: #10b981;"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        function calculateEMI() {
            const P = parseFloat(document.getElementById('loanAmount').value);
            const r = parseFloat(document.getElementById('interestRate').value) / 12 / 100;
            const n = parseFloat(document.getElementById('loanTenure').value) * 12;
            
            const emi = P * r * Math.pow(1 + r, n) / (Math.pow(1 + r, n) - 1);
            const totalPayment = emi * n;
            const totalInterest = totalPayment - P;
            
            document.getElementById('emiAmount').textContent = '₹' + Math.round(emi).toLocaleString('en-IN');
            document.getElementById('totalInterest').textContent = '₹' + Math.round(totalInterest).toLocaleString('en-IN');
            document.getElementById('totalPayment').textContent = '₹' + Math.round(totalPayment).toLocaleString('en-IN');
            document.getElementById('emiResult').style.display = 'block';
        }

        // Area Converter Modal
        function openAreaConverter() {
            event.preventDefault();
            const modal = document.createElement('div');
            modal.id = 'areaModal';
            modal.innerHTML = `
                <div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999;">
                    <div style="background: white; padding: 30px; border-radius: 15px; max-width: 450px; width: 90%;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                            <h3 style="font-size: 20px; color: #1f2937;">📐 Area Converter</h3>
                            <button onclick="document.getElementById('areaModal').remove()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #6b7280;">&times;</button>
                        </div>
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Enter Value</label>
                            <input type="number" id="areaValue" value="1000" oninput="convertArea()" style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 16px;">
                        </div>
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">From Unit</label>
                            <select id="fromUnit" onchange="convertArea()" style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 16px;">
                                <option value="sqft">Square Feet (sq ft)</option>
                                <option value="sqm">Square Meters (sq m)</option>
                                <option value="sqyd">Square Yards (sq yd)</option>
                                <option value="acre">Acres</option>
                                <option value="hectare">Hectares</option>
                                <option value="bigha">Bigha</option>
                            </select>
                        </div>
                        <div id="areaResults" style="background: #f8fafc; border-radius: 10px; padding: 20px;">
                            <h4 style="margin-bottom: 15px; color: #374151;">Converted Values:</h4>
                            <div id="conversionResults"></div>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            convertArea();
        }

        function convertArea() {
            const value = parseFloat(document.getElementById('areaValue').value) || 0;
            const from = document.getElementById('fromUnit').value;
            
            // Convert to sq ft first
            const toSqft = {
                sqft: 1,
                sqm: 10.7639,
                sqyd: 9,
                acre: 43560,
                hectare: 107639,
                bigha: 27225
            };
            
            const sqft = value * toSqft[from];
            
            const results = {
                'Square Feet': sqft.toFixed(2),
                'Square Meters': (sqft / 10.7639).toFixed(2),
                'Square Yards': (sqft / 9).toFixed(2),
                'Acres': (sqft / 43560).toFixed(4),
                'Hectares': (sqft / 107639).toFixed(4),
                'Bigha': (sqft / 27225).toFixed(4)
            };
            
            let html = '';
            for (const [unit, val] of Object.entries(results)) {
                html += `<div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e5e7eb;">
                    <span style="color: #6b7280;">${unit}</span>
                    <span style="font-weight: 600; color: #1f2937;">${parseFloat(val).toLocaleString('en-IN')}</span>
                </div>`;
            }
            document.getElementById('conversionResults').innerHTML = html;
        }

        // Budget Calculator
        function openBudgetCalculator() {
            event.preventDefault();
            const modal = document.createElement('div');
            modal.id = 'budgetModal';
            modal.innerHTML = `
                <div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999;">
                    <div style="background: white; padding: 30px; border-radius: 15px; max-width: 450px; width: 90%;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                            <h3 style="font-size: 20px; color: #1f2937;">💰 Budget Calculator</h3>
                            <button onclick="document.getElementById('budgetModal').remove()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #6b7280;">&times;</button>
                        </div>
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Monthly Income (₹)</label>
                            <input type="number" id="monthlyIncome" value="100000" style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 16px;">
                        </div>
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Existing EMIs (₹)</label>
                            <input type="number" id="existingEmi" value="0" style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 16px;">
                        </div>
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Down Payment Available (₹)</label>
                            <input type="number" id="downPayment" value="1000000" style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 16px;">
                        </div>
                        <button onclick="calculateBudget()" style="width: 100%; padding: 14px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer;">Calculate Budget</button>
                        <div id="budgetResult" style="margin-top: 25px; padding: 20px; background: #f8fafc; border-radius: 10px; display: none; text-align: center;">
                            <p style="color: #6b7280; margin-bottom: 5px;">You can afford a property worth</p>
                            <p id="affordableAmount" style="font-size: 32px; font-weight: 700; color: #10b981;"></p>
                            <p style="color: #6b7280; font-size: 13px; margin-top: 10px;">Based on 40% of income for EMI</p>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        function calculateBudget() {
            const income = parseFloat(document.getElementById('monthlyIncome').value);
            const existingEmi = parseFloat(document.getElementById('existingEmi').value);
            const downPayment = parseFloat(document.getElementById('downPayment').value);
            
            const availableForEmi = (income * 0.4) - existingEmi;
            const r = 8.5 / 12 / 100; // 8.5% interest
            const n = 20 * 12; // 20 years
            
            const loanAmount = availableForEmi * (Math.pow(1 + r, n) - 1) / (r * Math.pow(1 + r, n));
            const totalBudget = loanAmount + downPayment;
            
            document.getElementById('affordableAmount').textContent = '₹' + Math.round(totalBudget).toLocaleString('en-IN');
            document.getElementById('budgetResult').style.display = 'block';
        }

        // Format price for display
        function formatPrice(value) {
            return new Intl.NumberFormat('en-IN').format(value);
        }

        // Property Comparison from Home
        function openComparisonFromHome() {
            event.preventDefault();
            const comparisonList = JSON.parse(localStorage.getItem('propertyComparison') || '[]');
            
            const modal = document.createElement('div');
            modal.id = 'comparisonModal';
            
            let contentHtml = '';
            if (comparisonList.length === 0) {
                contentHtml = `
                    <div style="text-align: center; padding: 40px 20px;">
                        <div style="font-size: 64px; margin-bottom: 20px;">⚖️</div>
                        <h3 style="font-size: 20px; color: #1f2937; margin-bottom: 10px;">No Properties to Compare</h3>
                        <p style="color: #6b7280; margin-bottom: 25px;">Browse properties and click "Add to Compare" to start comparing.</p>
                        <a href="search.php" style="display: inline-block; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 8px; font-weight: 600;">Browse Properties</a>
                    </div>
                `;
            } else if (comparisonList.length === 1) {
                contentHtml = `
                    <div style="text-align: center; padding: 40px 20px;">
                        <div style="font-size: 64px; margin-bottom: 20px;">⚖️</div>
                        <h3 style="font-size: 20px; color: #1f2937; margin-bottom: 10px;">Add More Properties</h3>
                        <p style="color: #6b7280; margin-bottom: 15px;">You have 1 property in your comparison list. Add at least one more to compare.</p>
                        <div style="background: #f3f4f6; padding: 15px; border-radius: 10px; margin-bottom: 25px; text-align: left;">
                            <strong style="color: #1f2937;">${comparisonList[0].title}</strong>
                            <div style="color: #667eea; font-weight: 600;">${comparisonList[0].priceFormatted}</div>
                        </div>
                        <a href="search.php" style="display: inline-block; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 8px; font-weight: 600;">Add More Properties</a>
                    </div>
                `;
            } else {
                // Find best values
                const lowestPrice = Math.min(...comparisonList.map(p => p.price));
                const largestArea = Math.max(...comparisonList.map(p => p.area));
                const lowestPricePerSqft = Math.min(...comparisonList.map(p => p.pricePerSqft));
                
                contentHtml = `
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; min-width: 600px;">
                            <tr>
                                <th style="padding: 15px; background: #f9fafb; text-align: left; font-size: 13px; color: #6b7280; border-bottom: 1px solid #e5e7eb;"></th>
                                ${comparisonList.map(p => `
                                    <td style="padding: 20px; text-align: center; background: linear-gradient(135deg, #667eea10 0%, #764ba210 100%); border-bottom: 1px solid #e5e7eb;">
                                        <div style="width: 100px; height: 70px; border-radius: 8px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; color: white; font-size: 28px;">
                                            ${p.image ? `<img src="${p.image}" style="width:100%;height:100%;object-fit:cover;border-radius:8px;">` : '🏠'}
                                        </div>
                                        <div style="font-weight: 600; color: #1f2937; font-size: 14px; margin-bottom: 5px;">${p.title}</div>
                                        <div style="color: #667eea; font-weight: 700; font-size: 16px;">${p.priceFormatted}</div>
                                    </td>
                                `).join('')}
                            </tr>
                            <tr>
                                <th style="padding: 12px 15px; background: #f9fafb; text-align: left; font-size: 13px; color: #6b7280; border-bottom: 1px solid #e5e7eb;">📍 Location</th>
                                ${comparisonList.map(p => `<td style="padding: 12px 15px; border-bottom: 1px solid #e5e7eb; font-size: 14px;">${p.city}</td>`).join('')}
                            </tr>
                            <tr>
                                <th style="padding: 12px 15px; background: #f9fafb; text-align: left; font-size: 13px; color: #6b7280; border-bottom: 1px solid #e5e7eb;">🏗️ Type</th>
                                ${comparisonList.map(p => `<td style="padding: 12px 15px; border-bottom: 1px solid #e5e7eb; font-size: 14px;">${p.type}</td>`).join('')}
                            </tr>
                            <tr>
                                <th style="padding: 12px 15px; background: #f9fafb; text-align: left; font-size: 13px; color: #6b7280; border-bottom: 1px solid #e5e7eb;">💰 Price</th>
                                ${comparisonList.map(p => `<td style="padding: 12px 15px; border-bottom: 1px solid #e5e7eb; font-size: 14px;">${p.priceFormatted} ${p.price === lowestPrice ? '<span style="background:#d1fae5;color:#065f46;padding:2px 6px;border-radius:4px;font-size:11px;font-weight:600;">Best</span>' : ''}</td>`).join('')}
                            </tr>
                            <tr>
                                <th style="padding: 12px 15px; background: #f9fafb; text-align: left; font-size: 13px; color: #6b7280; border-bottom: 1px solid #e5e7eb;">📏 Area</th>
                                ${comparisonList.map(p => `<td style="padding: 12px 15px; border-bottom: 1px solid #e5e7eb; font-size: 14px;">${p.area.toLocaleString('en-IN')} sq ft ${p.area === largestArea ? '<span style="background:#d1fae5;color:#065f46;padding:2px 6px;border-radius:4px;font-size:11px;font-weight:600;">Largest</span>' : ''}</td>`).join('')}
                            </tr>
                            <tr>
                                <th style="padding: 12px 15px; background: #f9fafb; text-align: left; font-size: 13px; color: #6b7280; border-bottom: 1px solid #e5e7eb;">💵 Price/sq ft</th>
                                ${comparisonList.map(p => `<td style="padding: 12px 15px; border-bottom: 1px solid #e5e7eb; font-size: 14px;">₹${p.pricePerSqft.toLocaleString('en-IN')} ${p.pricePerSqft === lowestPricePerSqft ? '<span style="background:#d1fae5;color:#065f46;padding:2px 6px;border-radius:4px;font-size:11px;font-weight:600;">Best Value</span>' : ''}</td>`).join('')}
                            </tr>
                            <tr>
                                <th style="padding: 12px 15px; background: #f9fafb; text-align: left; font-size: 13px; color: #6b7280; border-bottom: 1px solid #e5e7eb;">🛏️ Bedrooms</th>
                                ${comparisonList.map(p => `<td style="padding: 12px 15px; border-bottom: 1px solid #e5e7eb; font-size: 14px;">${p.bedrooms}</td>`).join('')}
                            </tr>
                            <tr>
                                <th style="padding: 12px 15px; background: #f9fafb; text-align: left; font-size: 13px; color: #6b7280; border-bottom: 1px solid #e5e7eb;">🚿 Bathrooms</th>
                                ${comparisonList.map(p => `<td style="padding: 12px 15px; border-bottom: 1px solid #e5e7eb; font-size: 14px;">${p.bathrooms}</td>`).join('')}
                            </tr>
                            <tr>
                                <th style="padding: 12px 15px; background: #f9fafb; text-align: left; font-size: 13px; color: #6b7280;"></th>
                                ${comparisonList.map(p => `<td style="padding: 12px 15px;"><a href="property-details.php?id=${p.id}" style="color: #667eea; font-weight: 600; text-decoration: none;">View Details →</a></td>`).join('')}
                            </tr>
                        </table>
                    </div>
                    <div style="padding: 20px; border-top: 1px solid #e5e7eb; text-align: center;">
                        <button onclick="clearComparisonList()" style="padding: 10px 20px; background: white; color: #ef4444; border: 2px solid #ef4444; border-radius: 6px; font-weight: 600; cursor: pointer; margin-right: 10px;">Clear All</button>
                        <a href="search.php" style="display: inline-block; padding: 10px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 6px; font-weight: 600;">Add More</a>
                    </div>
                `;
            }
            
            modal.innerHTML = `
                <div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999; padding: 20px;">
                    <div style="background: white; border-radius: 15px; max-width: 900px; width: 100%; max-height: 90vh; overflow-y: auto;">
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 20px 25px; border-bottom: 1px solid #e5e7eb; position: sticky; top: 0; background: white; z-index: 10;">
                            <h3 style="font-size: 20px; color: #1f2937; display: flex; align-items: center; gap: 10px;">⚖️ Property Comparison</h3>
                            <button onclick="document.getElementById('comparisonModal').remove()" style="background: none; border: none; font-size: 28px; cursor: pointer; color: #6b7280;">&times;</button>
                        </div>
                        ${contentHtml}
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        function clearComparisonList() {
            localStorage.removeItem('propertyComparison');
            document.getElementById('comparisonModal').remove();
            openComparisonFromHome();
        }

        // Contact Popup for non-logged-in users
        function showContactPopup(propertyTitle, propertyId) {
            // Prevent background scrolling
            document.body.style.overflow = 'hidden';
            
            const modal = document.createElement('div');
            modal.id = 'contactPopup';
            modal.innerHTML = `
                <div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); display: flex; align-items: flex-start; justify-content: center; z-index: 9999; padding: 20px; overflow-y: auto;" onclick="if(event.target === this) { document.getElementById('contactPopup').remove(); document.body.style.overflow = 'auto'; }">
                    <div style="background: white; border-radius: 15px; max-width: 450px; width: 100%; overflow: hidden; box-shadow: 0 25px 50px rgba(0,0,0,0.25); position: relative; margin: auto;">
                        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; text-align: center; position: relative;">
                            <button onclick="document.getElementById('contactPopup').remove(); document.body.style.overflow = 'auto';" style="position: absolute; top: 10px; right: 10px; background: rgba(255,255,255,0.2); border: none; color: white; width: 30px; height: 30px; border-radius: 50%; font-size: 18px; cursor: pointer; display: flex; align-items: center; justify-content: center;">&times;</button>
                            <h3 style="color: white; font-size: 20px; margin-bottom: 5px;">📞 Contact Us</h3>
                            <p style="color: rgba(255,255,255,0.9); font-size: 13px;">Get details about this property</p>
                        </div>
                        <div style="padding: 20px; max-height: 60vh; overflow-y: auto;">
                            <p style="color: #6b7280; font-size: 13px; margin-bottom: 15px; text-align: center;">
                                <strong style="color: #1f2937;">${propertyTitle}</strong>
                            </p>
                            <form id="contactForm" onsubmit="submitContactForm(event, '${propertyId}')">
                                <div style="margin-bottom: 12px;">
                                    <label style="display: block; margin-bottom: 4px; font-weight: 600; color: #374151; font-size: 13px;">Your Name *</label>
                                    <input type="text" name="name" required placeholder="Enter your name" style="width: 100%; padding: 10px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                                </div>
                                <div style="margin-bottom: 12px;">
                                    <label style="display: block; margin-bottom: 4px; font-weight: 600; color: #374151; font-size: 13px;">Email *</label>
                                    <input type="email" name="email" required placeholder="Enter your email" style="width: 100%; padding: 10px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                                </div>
                                <div style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 4px; font-weight: 600; color: #374151; font-size: 13px;">Message</label>
                                    <textarea name="message" rows="2" placeholder="I am interested in this property..." style="width: 100%; padding: 10px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px; box-sizing: border-box; resize: vertical;">I am interested in this property. Please contact me with more details.</textarea>
                                </div>
                                <button type="submit" style="width: 100%; padding: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer;">
                                    Submit Inquiry
                                </button>
                            </form>
                            <div style="margin-top: 12px; text-align: center;">
                                <p style="color: #9ca3af; font-size: 11px;">Already have an account? <a href="login.php" style="color: #667eea; font-weight: 600;">Login</a> for full access</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        function submitContactForm(event, propertyId) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            formData.append('property_id', propertyId);
            
            fetch('submit-inquiry.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('contactPopup').innerHTML = `
                        <div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center; z-index: 9999; padding: 20px;">
                            <div style="background: white; border-radius: 15px; max-width: 400px; width: 100%; padding: 40px; text-align: center;">
                                <div style="width: 70px; height: 70px; background: #d1fae5; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 32px;">✓</div>
                                <h3 style="color: #065f46; font-size: 22px; margin-bottom: 10px;">Thank You!</h3>
                                <p style="color: #6b7280; margin-bottom: 25px;">Your inquiry has been submitted. Our team will contact you shortly.</p>
                                <button onclick="document.getElementById('contactPopup').remove()" style="padding: 12px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer;">Close</button>
                            </div>
                        </div>
                    `;
                } else {
                    alert(data.message || 'Something went wrong. Please try again.');
                }
            })
            .catch(error => {
                alert('Error submitting form. Please try again.');
            });
        }
    </script>
</body>

</html>
