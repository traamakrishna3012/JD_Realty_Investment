<?php
include('includes/config.php');

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);

// Build search query with prepared-like escaping
$where_conditions = ["status='available'", "approval_status='approved'"];

if (!empty($_GET['city'])) {
    $city = $conn->real_escape_string($_GET['city']);
    $where_conditions[] = "city='$city'";
}

if (!empty($_GET['type'])) {
    $type = $conn->real_escape_string($_GET['type']);
    $where_conditions[] = "property_type='$type'";
}

if (!empty($_GET['category'])) {
    $category = $conn->real_escape_string($_GET['category']);
    $where_conditions[] = "category='$category'";
}

if (!empty($_GET['purpose'])) {
    $purpose = $conn->real_escape_string($_GET['purpose']);
    if ($purpose == 'buy' || $purpose == 'rent') {
        $where_conditions[] = "listing_type='$purpose'";
    }
}

if (!empty($_GET['min_price'])) {
    $min_price = intval($_GET['min_price']);
    $where_conditions[] = "price >= $min_price";
}

if (!empty($_GET['max_price'])) {
    $max_price = intval($_GET['max_price']);
    $where_conditions[] = "price <= $max_price";
}

if (!empty($_GET['pre_lease'])) {
    $pre_lease = $conn->real_escape_string($_GET['pre_lease']);
    $where_conditions[] = "pre_lease='$pre_lease'";
}

$where_clause = implode(' AND ', $where_conditions);

// Pagination
$per_page = 12;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $per_page;

// Get total count - optimized
$count_result = $conn->query("SELECT COUNT(*) as total FROM properties WHERE $where_clause");
$total = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total / $per_page);

// Get properties - optimized with specific columns
$sql = "SELECT id, title, price, city, area_sqft, bedrooms, bathrooms, property_type, category, image_url, listing_type, pre_lease, address 
        FROM properties WHERE $where_clause ORDER BY created_at DESC LIMIT $offset, $per_page";
$result = $conn->query($sql);
$properties = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Fetch distinct cities for location filter
$cities_result = $conn->query("SELECT DISTINCT city FROM properties WHERE status='available' AND approval_status='approved' AND city IS NOT NULL AND city != '' ORDER BY city ASC");
$available_cities = [];
if ($cities_result) {
    while ($row = $cities_result->fetch_assoc()) {
        $available_cities[] = $row['city'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="Search properties on JD Realty & Investment. Filter by location, type, and price to find your ideal property. Browse available residential, commercial, and plot listings.">
    <meta name="keywords"
        content="property search, find properties, real estate listings, apartments for sale, commercial property, residential plot, search properties online">
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <meta name="author" content="JD Realty & Investment">
    <meta property="og:title" content="Search Properties - JD Realty & Investment">
    <meta property="og:description" content="Search and filter properties by location, type, and price">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo defined('SITE_URL') ? SITE_URL : 'https://jdrealtyinvestment.com'; ?>/search.php">
    <meta property="og:image" content="<?php echo defined('SITE_URL') ? SITE_URL : 'https://jdrealtyinvestment.com'; ?>/images/jd-logo.svg">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="Search Properties - JD Realty & Investment">
    <meta name="twitter:description" content="Find your ideal property with advanced search filters">
    <link rel="canonical" href="<?php echo defined('SITE_URL') ? SITE_URL : 'https://jdrealtyinvestment.com'; ?>/search.php">
    <link rel="icon" type="image/x-icon" href="images/jd-logo.svg">
    <title>Search Properties - Find Real Estate Online | JD Realty & Investment</title>
    <link rel="stylesheet" href="css/style.css?v=20251203c">
    
    <!-- Structured Data - Search Results -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "SearchResultsPage",
        "name": "Property Search Results",
        "description": "Search and filter properties by location, type, and price on JD Realty & Investment",
        "url": "<?php echo defined('SITE_URL') ? SITE_URL : 'https://jdrealtyinvestment.com'; ?>/search.php"
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
                "item": "<?php echo defined('SITE_URL') ? SITE_URL : 'https://jdrealtyinvestment.com'; ?>/"
            },
            {
                "@type": "ListItem",
                "position": 2,
                "name": "Search Properties",
                "item": "<?php echo defined('SITE_URL') ? SITE_URL : 'https://jdrealtyinvestment.com'; ?>/search.php"
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

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .page-header {
            margin-bottom: 40px;
        }

        .page-header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .page-header p {
            color: #6b7280;
        }

        .search-filters {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #1f2937;
            font-size: 14px;
        }

        .filter-group input,
        .filter-group select {
            padding: 10px;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            font-size: 14px;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-search {
            flex: 1;
            padding: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-search:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-reset {
            padding: 10px 20px;
            background: #e5e7eb;
            color: #1f2937;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-reset:hover {
            background: #d1d5db;
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .results-count {
            color: #6b7280;
            font-size: 14px;
        }

        .properties-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .property-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .property-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .property-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
        }

        .property-info {
            padding: 20px;
        }

        .property-type {
            color: #d97706;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .property-title {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin: 10px 0;
        }

        .property-details {
            display: flex;
            justify-content: space-between;
            color: #6b7280;
            font-size: 14px;
            margin: 15px 0;
        }

        .property-price {
            font-size: 20px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 15px;
        }

        .btn-view {
            width: 100%;
            padding: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
            display: block;
        }

        .btn-view:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
            color: #6b7280;
        }

        .empty-state h3 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #1f2937;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 40px;
        }

        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            text-decoration: none;
            color: #667eea;
            transition: all 0.3s ease;
        }

        .pagination a:hover {
            background: #667eea;
            color: white;
        }

        .pagination span.current {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .footer {
            background: #1f2937;
            color: white;
            padding: 40px;
            text-align: center;
            margin-top: 60px;
        }

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
            
            .filter-grid {
                grid-template-columns: 1fr;
            }

            .properties-grid {
                grid-template-columns: 1fr;
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
        <div class="page-header">
            <h1>Search Properties</h1>
            <p>Find your perfect property using our advanced search filters</p>
        </div>

        <div class="search-filters">
            <form method="GET" action="search.php">
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="city">City/Location</label>
                        <select id="city" name="city">
                            <option value="">All Locations</option>
                            <?php foreach ($available_cities as $city_name): ?>
                                <option value="<?php echo htmlspecialchars($city_name); ?>" <?php echo isset($_GET['city']) && $_GET['city'] == $city_name ? 'selected' : ''; ?>><?php echo htmlspecialchars($city_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="type">Property Type</label>
                        <select id="type" name="type">
                            <option value="">All Types</option>
                            <option value="residential" <?php echo isset($_GET['type']) && $_GET['type'] == 'residential' ? 'selected' : ''; ?>>Residential</option>
                            <option value="commercial" <?php echo isset($_GET['type']) && $_GET['type'] == 'commercial' ? 'selected' : ''; ?>>Commercial</option>
                            <option value="plot" <?php echo isset($_GET['type']) && $_GET['type'] == 'plot' ? 'selected' : ''; ?>>Plot</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="category">Category</label>
                        <select id="category" name="category">
                            <option value="">All Categories</option>
                            <option value="1bhk" <?php echo isset($_GET['category']) && $_GET['category'] == '1bhk' ? 'selected' : ''; ?>>1 BHK</option>
                            <option value="2bhk" <?php echo isset($_GET['category']) && $_GET['category'] == '2bhk' ? 'selected' : ''; ?>>2 BHK</option>
                            <option value="3bhk" <?php echo isset($_GET['category']) && $_GET['category'] == '3bhk' ? 'selected' : ''; ?>>3 BHK</option>
                            <option value="4bhk" <?php echo isset($_GET['category']) && $_GET['category'] == '4bhk' ? 'selected' : ''; ?>>4 BHK</option>
                            <option value="shop" <?php echo isset($_GET['category']) && $_GET['category'] == 'shop' ? 'selected' : ''; ?>>Shop</option>
                            <option value="office" <?php echo isset($_GET['category']) && $_GET['category'] == 'office' ? 'selected' : ''; ?>>Office</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="purpose">Purpose</label>
                        <select id="purpose" name="purpose">
                            <option value="">Buy / Rent</option>
                            <option value="buy" <?php echo isset($_GET['purpose']) && $_GET['purpose'] == 'buy' ? 'selected' : ''; ?>>Buy</option>
                            <option value="rent" <?php echo isset($_GET['purpose']) && $_GET['purpose'] == 'rent' ? 'selected' : ''; ?>>Rent</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="pre_lease">Pre-Lease</label>
                        <select id="pre_lease" name="pre_lease">
                            <option value="">All</option>
                            <option value="yes" <?php echo isset($_GET['pre_lease']) && $_GET['pre_lease'] == 'yes' ? 'selected' : ''; ?>>Pre-Leased Only</option>
                            <option value="no" <?php echo isset($_GET['pre_lease']) && $_GET['pre_lease'] == 'no' ? 'selected' : ''; ?>>Not Pre-Leased</option>
                        </select>
                    </div>
                </div>

                <div class="filter-buttons">
                    <button type="submit" class="btn-search">Search</button>
                    <a href="search.php" class="btn-reset">Clear Filters</a>
                </div>
            </form>
        </div>

        <?php if (!empty($properties)): ?>
            <div class="results-header">
                <div class="results-count">
                    Found <strong><?php echo $total; ?></strong> properties
                </div>
            </div>

            <div class="properties-grid">
                <?php foreach ($properties as $property): ?>
                    <div class="property-card">
                        <div class="property-image" style="display: flex; align-items: center; justify-content: center; background: #f3f4f6;">
                            <?php if (!empty($property['image_url']) && file_exists($property['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($property['image_url']); ?>"
                                    alt="<?php echo htmlspecialchars($property['title'] . ' - ' . ucfirst($property['property_type']) . ' for sale in ' . $property['city']); ?>"
                                    loading="lazy"
                                    style="max-width: 100%; max-height: 100%; object-fit: contain;">
                            <?php else: ?>
                                <div
                                    style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #999; font-size: 32px;"
                                    role="img" aria-label="Property placeholder image">
                                    📍</div>
                            <?php endif; ?>
                        </div>
                        <div class="property-info">
                            <div class="property-type"><?php echo ucfirst($property['property_type']); ?></div>
                            <h3 class="property-title"><?php echo htmlspecialchars($property['title']); ?></h3>

                            <div class="property-details">
                                <span><?php echo htmlspecialchars($property['city']); ?></span>
                                <span><?php echo number_format($property['area_sqft']); ?> sq ft</span>
                            </div>

                            <div class="property-price"><?php echo formatIndianPrice($property['price']); ?></div>

                            <a href="property-details.php?id=<?php echo $property['id']; ?>" class="btn-view">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php
                    // Previous page
                    if ($page > 1) {
                        $prev_url = "search.php?page=" . ($page - 1);
                        if (!empty($_GET['city']))
                            $prev_url .= "&city=" . urlencode($_GET['city']);
                        if (!empty($_GET['type']))
                            $prev_url .= "&type=" . urlencode($_GET['type']);
                        if (!empty($_GET['category']))
                            $prev_url .= "&category=" . urlencode($_GET['category']);
                        echo "<a href='$prev_url'>← Previous</a>";
                    }

                    // Page numbers
                    for ($i = 1; $i <= $total_pages; $i++) {
                        $page_url = "search.php?page=$i";
                        if (!empty($_GET['city']))
                            $page_url .= "&city=" . urlencode($_GET['city']);
                        if (!empty($_GET['type']))
                            $page_url .= "&type=" . urlencode($_GET['type']);
                        if (!empty($_GET['category']))
                            $page_url .= "&category=" . urlencode($_GET['category']);

                        if ($i == $page) {
                            echo "<span class='current'>$i</span>";
                        } else {
                            echo "<a href='$page_url'>$i</a>";
                        }
                    }

                    // Next page
                    if ($page < $total_pages) {
                        $next_url = "search.php?page=" . ($page + 1);
                        if (!empty($_GET['city']))
                            $next_url .= "&city=" . urlencode($_GET['city']);
                        if (!empty($_GET['type']))
                            $next_url .= "&type=" . urlencode($_GET['type']);
                        if (!empty($_GET['category']))
                            $next_url .= "&category=" . urlencode($_GET['category']);
                        echo "<a href='$next_url'>Next →</a>";
                    }
                    ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-state">
                <h3>No Properties Found</h3>
                <p>We couldn't find any properties matching your criteria. Try adjusting your search filters.</p>
            </div>
        <?php endif; ?>
    </div>

    <?php include('includes/footer.php'); ?>

    <script>
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
