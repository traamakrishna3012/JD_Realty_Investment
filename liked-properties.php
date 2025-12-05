<?php
include('includes/config.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=liked-properties.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle unlike/remove from favorites
if (isset($_GET['unlike']) && is_numeric($_GET['unlike'])) {
    $property_id = intval($_GET['unlike']);
    $conn->query("DELETE FROM property_favorites WHERE user_id=$user_id AND property_id=$property_id");
    header("Location: liked-properties.php?message=Property removed from favorites");
    exit();
}

// Get liked properties
$sql = "SELECT p.*, u.name as owner_name, pf.created_at as liked_at 
        FROM property_favorites pf 
        JOIN properties p ON pf.property_id = p.id 
        LEFT JOIN users u ON p.created_by = u.id 
        WHERE pf.user_id = $user_id 
        ORDER BY pf.created_at DESC";
$result = $conn->query($sql);
$liked_properties = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $liked_properties[] = $row;
    }
}

// Get user details
$user = $conn->query("SELECT * FROM users WHERE id=$user_id")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="View your liked and favorite properties - JD Realty & Investment">
    <meta name="robots" content="noindex, follow">
    <title>My Liked Properties - JD Realty & Investment</title>
    <link rel="stylesheet" href="css/style.css?v=20251203c">
    <link rel="icon" type="image/x-icon" href="images/jd-logo.svg">
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

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 32px;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-header p {
            color: #6b7280;
            margin-top: 5px;
        }

        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 30px;
            border-radius: 15px;
            text-align: center;
        }

        .stats-card .number {
            font-size: 36px;
            font-weight: bold;
        }

        .stats-card .label {
            font-size: 14px;
            opacity: 0.9;
        }

        .success-message {
            background-color: #d1fae5;
            color: #065f46;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #10b981;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .properties-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }

        .property-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            position: relative;
        }

        .property-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .property-image {
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
            position: relative;
        }

        .property-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .liked-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: white;
            color: #ef4444;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
        }

        .status-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-available {
            background: #d1fae5;
            color: #065f46;
        }

        .status-sold {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-under_construction {
            background: #fef3c7;
            color: #92400e;
        }

        .property-info {
            padding: 20px;
        }

        .property-type {
            color: #667eea;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .property-title {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 10px;
            line-height: 1.4;
        }

        .property-details {
            display: flex;
            gap: 15px;
            color: #6b7280;
            font-size: 13px;
            margin-bottom: 15px;
        }

        .property-details span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .property-price {
            font-size: 22px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 15px;
        }

        .liked-date {
            font-size: 12px;
            color: #9ca3af;
            margin-bottom: 15px;
        }

        .card-actions {
            display: flex;
            gap: 10px;
        }

        .btn-view {
            flex: 1;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s ease;
        }

        .btn-view:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-unlike {
            padding: 12px 20px;
            background: #fee2e2;
            color: #ef4444;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-unlike:hover {
            background: #ef4444;
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        .empty-state .icon {
            font-size: 80px;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 24px;
            color: #1f2937;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #6b7280;
            margin-bottom: 25px;
        }

        .empty-state a {
            display: inline-block;
            padding: 14px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .empty-state a:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
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
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
            }

            .page-header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }

            .properties-grid {
                grid-template-columns: 1fr;
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
            <a href="search.php">Search</a>
            <a href="user-dashboard.php">My Properties</a>
            <a href="liked-properties.php" style="color: #ef4444;">❤️ Liked</a>
            <a href="includes/logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <div>
                <h1>❤️ My Liked Properties</h1>
                <p>Welcome, <?php echo htmlspecialchars($user['name']); ?>! Here are all the properties you've liked.</p>
            </div>
            <div class="stats-card">
                <div class="number"><?php echo count($liked_properties); ?></div>
                <div class="label">Liked Properties</div>
            </div>
        </div>

        <?php if (isset($_GET['message'])): ?>
            <div class="success-message">
                ✅ <?php echo htmlspecialchars($_GET['message']); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($liked_properties)): ?>
            <div class="properties-grid">
                <?php foreach ($liked_properties as $property): ?>
                    <div class="property-card">
                        <div class="property-image">
                            <?php if (!empty($property['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($property['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($property['title']); ?>"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div style="display: none; width: 100%; height: 100%; align-items: center; justify-content: center; font-size: 48px;">🏠</div>
                            <?php else: ?>
                                🏠
                            <?php endif; ?>
                            <span class="liked-badge">❤️</span>
                            <span class="status-badge status-<?php echo str_replace(' ', '_', $property['status']); ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $property['status'])); ?>
                            </span>
                        </div>
                        <div class="property-info">
                            <div class="property-type"><?php echo ucfirst($property['property_type']); ?></div>
                            <h3 class="property-title"><?php echo htmlspecialchars($property['title']); ?></h3>
                            <div class="property-details">
                                <span>📍 <?php echo htmlspecialchars($property['city']); ?></span>
                                <span>📐 <?php echo number_format($property['area_sqft']); ?> sq ft</span>
                            </div>
                            <div class="property-price"><?php echo formatIndianPrice($property['price']); ?></div>
                            <div class="liked-date">
                                ❤️ Liked on <?php echo date('M d, Y', strtotime($property['liked_at'])); ?>
                            </div>
                            <div class="card-actions">
                                <a href="property-details.php?id=<?php echo $property['id']; ?>" class="btn-view">View Details</a>
                                <button class="btn-unlike" onclick="if(confirm('Remove from favorites?')) location.href='liked-properties.php?unlike=<?php echo $property['id']; ?>'">
                                    💔
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="icon">💔</div>
                <h3>No Liked Properties Yet</h3>
                <p>You haven't liked any properties yet. Browse our listings and click the heart icon to save properties you're interested in!</p>
                <a href="search.php">Browse Properties</a>
            </div>
        <?php endif; ?>
    </div>

    <?php include('includes/footer.php'); ?>
</body>
</html>
