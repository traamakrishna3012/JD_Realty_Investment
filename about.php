<?php
include('includes/config.php');

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: about.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="About JD Realty & Investment - A trusted real estate platform dedicated to helping you find your perfect property. Learn about our mission, vision, and core values.">
    <meta name="keywords" content="about us, real estate company, JD Realty, property investment, trusted realtor, real estate services">
    <meta property="og:title" content="About JD Realty & Investment">
    <meta property="og:description" content="Learn about JD Realty & Investment - Your trusted real estate partner">
    <meta property="og:type" content="website">
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <meta name="author" content="JD Realty & Investment">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="About JD Realty & Investment">
    <meta name="twitter:description" content="Learn about JD Realty & Investment - Your trusted real estate partner in Thane">
    <meta property="og:url" content="<?php echo defined('SITE_URL') ? SITE_URL : 'https://jdrealtyinvestment.com'; ?>/about.php">
    <meta property="og:image" content="<?php echo defined('SITE_URL') ? SITE_URL : 'https://jdrealtyinvestment.com'; ?>/images/jd-logo.svg">
    <link rel="canonical" href="<?php echo defined('SITE_URL') ? SITE_URL : 'https://jdrealtyinvestment.com'; ?>/about.php">
    <link rel="icon" type="image/x-icon" href="images/jd-logo.svg">
    <title>About JD Realty & Investment - Trusted Real Estate Company in Thane</title>
    <link rel="stylesheet" href="css/style.css?v=20251203c">
    
    <!-- Structured Data - About Page -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "AboutPage",
        "name": "About JD Realty & Investment",
        "description": "Learn about JD Realty & Investment - A trusted real estate platform dedicated to helping you find your perfect property",
        "url": "<?php echo defined('SITE_URL') ? SITE_URL : 'https://jdrealtyinvestment.com'; ?>/about.php",
        "mainEntity": {
            "@type": "Organization",
            "name": "JD Realty & Investment",
            "founder": [
                {
                    "@type": "Person",
                    "name": "Mr. Jeetender Parasni",
                    "jobTitle": "Real Estate Professional"
                },
                {
                    "@type": "Person",
                    "name": "Mr. Dinesh Mittal",
                    "jobTitle": "Chartered Accountant"
                }
            ],
            "foundingLocation": {
                "@type": "Place",
                "name": "Thane, Maharashtra, India"
            }
        }
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
                "name": "About Us",
                "item": "<?php echo defined('SITE_URL') ? SITE_URL : 'https://jdrealtyinvestment.com'; ?>/about.php"
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
            color: #1f2937;
        }
        
        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #d97706;
        }
        
        .logo span {
            color: #4f46e5;
        }
        
        .tagline {
            color: #6b7280;
            font-size: 11px;
            font-weight: 600;
        }
        
        .nav-links {
            display: flex;
            gap: 30px;
            align-items: center;
        }
        
        .nav-links a {
            color: #1f2937;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .nav-links a:hover {
            color: #d97706;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-name {
            color: #6b7280;
            font-size: 14px;
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
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .about-header {
            text-align: center;
            margin-bottom: 60px;
        }
        
        .about-header h1 {
            font-size: 42px;
            margin-bottom: 15px;
            color: #1f2937;
        }
        
        .about-header p {
            font-size: 18px;
            color: #6b7280;
        }
        
        .about-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 60px;
        }
        
        .about-section {
            line-height: 1.8;
        }
        
        .about-section h2 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #1f2937;
        }
        
        .about-section p {
            font-size: 16px;
            color: #6b7280;
            margin-bottom: 15px;
        }
        
        .about-section ul {
            margin-left: 20px;
        }
        
        .about-section li {
            font-size: 16px;
            color: #6b7280;
            margin-bottom: 12px;
        }
        
        .about-section strong {
            color: #1f2937;
        }
        
        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin: 60px 0;
        }
        
        .value-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        
        .value-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .value-card h3 {
            font-size: 20px;
            margin-bottom: 15px;
            color: #1f2937;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        
        .value-card p {
            color: #6b7280;
            font-size: 15px;
            line-height: 1.6;
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
                flex-direction: row;
                gap: 8px;
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
                padding: 6px 10px;
                font-size: 11px;
                white-space: nowrap;
            }
            
            .logout-btn {
                padding: 6px 10px;
                font-size: 11px;
                white-space: nowrap;
            }
            
            .about-content {
                grid-template-columns: 1fr;
            }
            
            .about-header h1 {
                font-size: 32px;
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
        <a href="index.php" class="logo-section" style="text-decoration: none; display: flex; align-items: center; gap: 12px;">
            <img src="images/jd-logo.svg?v=20251203" alt="JD Realty & Investment" style="height: 60px; width: 60px;">
            <span style="font-size: 24px; font-weight: bold; color: #d4a84b; text-shadow: 1px 1px 2px rgba(0,0,0,0.3);">JD Realty Investment</span>
        </a>
        
        <?php if ($is_logged_in): ?>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="about.php">About Us</a>
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
    
    <div class="container">
        <div class="about-header">
            <h1>About JD Realty & Investment</h1>
            <p>Building Trust, One Property at a Time</p>
        </div>
        
        <div class="about-content">
            <div class="about-section">
                <h2>Who We Are</h2>
                <p><strong>Welcome to JD Realty & Investment</strong>, a trusted name in real estate built on integrity, expertise, and personalized service. Founded by <strong>Mr. Jeetender Parasni</strong>, a seasoned real estate professional with years of on-ground experience, and <strong>Mr. Dinesh Mittal</strong>, an accomplished Chartered Accountant with deep financial insight, our agency brings together the best of both worlds — <strong>property expertise and financial precision</strong>.</p>
                <p>Based in <strong>Thane, Maharashtra</strong>, we specialize in helping families, investors, and businesses find their perfect spaces — from premium residential apartments and villas to commercial offices and investment properties.</p>
            </div>
            
            <div class="about-section">
                <h2>Our Vision & Mission</h2>
                <p><strong>Our Vision:</strong> To become Thane's most trusted and customer-focused real estate advisory, recognized for ethical practices, personalized service, and long-lasting client relationships.</p>
                <ul>
                    <li><strong>Simplify</strong> the real estate experience through expert advice, data-driven insights, and transparent dealings</li>
                    <li>Provide clients with <strong>profitable and secure real estate opportunities</strong> in Thane and beyond</li>
                    <li>Deliver value at every step — from property selection to post-purchase support</li>
                    <li>Build a brand that stands for <strong>trust, professionalism, and innovation</strong> in real estate services</li>
                </ul>
            </div>
        </div>
        
        <h2 style="font-size: 32px; text-align: center; margin: 60px 0 40px;">Our Core Values</h2>
        
        <div class="values-grid">
            <div class="value-card">
                <h3>🛡️ Integrity</h3>
                <p>Honesty and transparency in every transaction. We believe in building relationships based on trust and ethical practices.</p>
            </div>
            
            <div class="value-card">
                <h3>💼 Commitment</h3>
                <p>Client satisfaction is our ultimate goal. We go the extra mile to ensure every client is delighted with their experience.</p>
            </div>
            
            <div class="value-card">
                <h3>🎯 Expertise</h3>
                <p>In-depth market knowledge with sound financial guidance. Our team brings professional insights from both real estate and finance sectors.</p>
            </div>
            
            <div class="value-card">
                <h3>💡 Innovation</h3>
                <p>Adopting modern tools and trends to deliver smarter real estate solutions that meet today's digital expectations.</p>
            </div>
            
            <div class="value-card">
                <h3>🤝 Relationships</h3>
                <p>Building lifelong connections, not just one-time deals. Every client becomes a part of our growing family.</p>
            </div>
            
            <div class="value-card">
                <h3>🎁 Value</h3>
                <p>Best ROI, Best Prices, and Best Appreciation. We help you get the right investment options with the best possible returns.</p>
            </div>
        </div>
    </div>
    
    <?php include('includes/footer.php'); ?>
</body>
</html>
