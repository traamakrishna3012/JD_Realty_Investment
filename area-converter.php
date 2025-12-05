<?php
include('includes/config.php');

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Area Converter - Convert between square feet, square meters, acres, hectares, and more. Free property area conversion tool by JD Realty.">
    <meta name="keywords" content="area converter, square feet to square meter, sqft to sqm, acres to hectares, property area calculator, JD Realty">
    <meta property="og:title" content="Area Converter | JD Realty & Investment">
    <meta property="og:description" content="Convert property area between different units instantly.">
    <meta property="og:type" content="website">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?php echo defined('SITE_URL') ? SITE_URL : 'https://jdrealtyinvestment.com'; ?>/area-converter.php">
    <link rel="icon" type="image/x-icon" href="images/jd-logo.svg">
    <title>Area Converter | JD Realty & Investment</title>
    <link rel="stylesheet" href="css/style.css?v=20251203c">
    
    <!-- Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebApplication",
        "name": "Area Converter",
        "description": "Convert property area between different units",
        "applicationCategory": "UtilityApplication",
        "operatingSystem": "Web Browser",
        "offers": {
            "@type": "Offer",
            "price": "0",
            "priceCurrency": "INR"
        }
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
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            min-height: 100vh;
        }
        
        .navbar {
            background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.15);
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 24px;
            font-weight: bold;
            color: #d97706;
            text-decoration: none;
        }
        
        .logo span {
            color: #ffffff;
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
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .page-header h1 {
            font-size: 36px;
            color: #1f2937;
            margin-bottom: 10px;
        }
        
        .page-header p {
            color: #6b7280;
            font-size: 18px;
        }
        
        .converter-container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }
        
        .converter-main {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 20px;
            align-items: center;
            margin-bottom: 40px;
        }
        
        .converter-input {
            background: #f9fafb;
            padding: 25px;
            border-radius: 15px;
            border: 2px solid #e5e7eb;
            transition: all 0.3s ease;
        }
        
        .converter-input:focus-within {
            border-color: #f59e0b;
            box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.1);
        }
        
        .converter-input label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .converter-input input[type="number"] {
            width: 100%;
            padding: 15px;
            border: none;
            background: white;
            border-radius: 8px;
            font-size: 24px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 15px;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .converter-input input[type="number"]:focus {
            outline: none;
        }
        
        .converter-input select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
            color: #374151;
            background: white;
            cursor: pointer;
        }
        
        .converter-input select:focus {
            outline: none;
            border-color: #f59e0b;
        }
        
        .swap-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .swap-btn:hover {
            transform: rotate(180deg);
            box-shadow: 0 5px 20px rgba(245, 158, 11, 0.4);
        }
        
        .quick-conversions {
            margin-top: 40px;
        }
        
        .quick-conversions h3 {
            font-size: 20px;
            color: #1f2937;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .conversion-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .conversion-card {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .conversion-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(245, 158, 11, 0.2);
        }
        
        .conversion-card .from-value {
            font-size: 24px;
            font-weight: bold;
            color: #92400e;
        }
        
        .conversion-card .equals {
            font-size: 14px;
            color: #6b7280;
            margin: 5px 0;
        }
        
        .conversion-card .to-value {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
        }
        
        .unit-info {
            margin-top: 40px;
            background: #f9fafb;
            padding: 30px;
            border-radius: 15px;
        }
        
        .unit-info h3 {
            font-size: 20px;
            color: #1f2937;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .unit-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .unit-table th,
        .unit-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .unit-table th {
            background: #f3f4f6;
            font-weight: 600;
            color: #374151;
        }
        
        .unit-table tr:hover {
            background: #fef3c7;
        }
        
        .unit-table td:first-child {
            font-weight: 600;
            color: #1f2937;
        }
        
        .unit-table td:nth-child(2) {
            color: #6b7280;
        }
        
        .footer {
            background: #1f2937;
            color: white;
            padding: 40px;
            text-align: center;
            margin-top: 60px;
        }
        
        @media (max-width: 768px) {
            .converter-main {
                grid-template-columns: 1fr;
            }
            
            .swap-btn {
                margin: 10px auto;
                transform: rotate(90deg);
            }
            
            .swap-btn:hover {
                transform: rotate(270deg);
            }
            
            .navbar {
                padding: 15px 20px;
            }
            
            .nav-links {
                display: none;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="logo" style="display: flex; align-items: center; gap: 10px; text-decoration: none;">
            <img src="images/jd-logo.svg?v=20251203" alt="JD Realty & Investment" style="width: 50px; height: 50px;">
            <span style="font-size: 20px; font-weight: bold; color: #d4a84b;">JD Realty Investment</span>
        </a>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="search.php">Search</a>
            <?php if ($is_logged_in): ?>
                <a href="user-dashboard.php">Dashboard</a>
                <a href="includes/logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php" class="login-btn">Login</a>
            <?php endif; ?>
        </div>
    </nav>
    
    <div class="container">
        <div class="page-header">
            <h1>📐 Area Converter</h1>
            <p>Convert property area between different measurement units instantly</p>
        </div>
        
        <div class="converter-container">
            <div class="converter-main">
                <div class="converter-input">
                    <label>From</label>
                    <input type="number" id="fromValue" value="1000" min="0" step="any" oninput="convert()">
                    <select id="fromUnit" onchange="convert()">
                        <option value="sqft" selected>Square Feet (sq ft)</option>
                        <option value="sqm">Square Meters (sq m)</option>
                        <option value="sqyd">Square Yards (sq yd)</option>
                        <option value="acre">Acres</option>
                        <option value="hectare">Hectares</option>
                        <option value="gunta">Guntha</option>
                        <option value="bigha">Bigha</option>
                        <option value="ground">Ground</option>
                        <option value="cent">Cent</option>
                        <option value="marla">Marla</option>
                        <option value="kanal">Kanal</option>
                    </select>
                </div>
                
                <button class="swap-btn" onclick="swapUnits()" title="Swap units">⇄</button>
                
                <div class="converter-input">
                    <label>To</label>
                    <input type="number" id="toValue" value="92.90" readonly>
                    <select id="toUnit" onchange="convert()">
                        <option value="sqft">Square Feet (sq ft)</option>
                        <option value="sqm" selected>Square Meters (sq m)</option>
                        <option value="sqyd">Square Yards (sq yd)</option>
                        <option value="acre">Acres</option>
                        <option value="hectare">Hectares</option>
                        <option value="gunta">Guntha</option>
                        <option value="bigha">Bigha</option>
                        <option value="ground">Ground</option>
                        <option value="cent">Cent</option>
                        <option value="marla">Marla</option>
                        <option value="kanal">Kanal</option>
                    </select>
                </div>
            </div>
            
            <div class="quick-conversions">
                <h3>⚡ Quick Reference</h3>
                <div class="conversion-grid">
                    <div class="conversion-card" onclick="quickConvert(1, 'sqft', 'sqm')">
                        <div class="from-value">1 Sq Ft</div>
                        <div class="equals">=</div>
                        <div class="to-value">0.0929 Sq M</div>
                    </div>
                    <div class="conversion-card" onclick="quickConvert(1, 'sqyd', 'sqft')">
                        <div class="from-value">1 Sq Yard</div>
                        <div class="equals">=</div>
                        <div class="to-value">9 Sq Ft</div>
                    </div>
                    <div class="conversion-card" onclick="quickConvert(1, 'acre', 'sqft')">
                        <div class="from-value">1 Acre</div>
                        <div class="equals">=</div>
                        <div class="to-value">43,560 Sq Ft</div>
                    </div>
                    <div class="conversion-card" onclick="quickConvert(1, 'hectare', 'acre')">
                        <div class="from-value">1 Hectare</div>
                        <div class="equals">=</div>
                        <div class="to-value">2.471 Acres</div>
                    </div>
                    <div class="conversion-card" onclick="quickConvert(1, 'gunta', 'sqft')">
                        <div class="from-value">1 Guntha</div>
                        <div class="equals">=</div>
                        <div class="to-value">1,089 Sq Ft</div>
                    </div>
                    <div class="conversion-card" onclick="quickConvert(1, 'bigha', 'sqft')">
                        <div class="from-value">1 Bigha</div>
                        <div class="equals">=</div>
                        <div class="to-value">27,225 Sq Ft</div>
                    </div>
                    <div class="conversion-card" onclick="quickConvert(1, 'ground', 'sqft')">
                        <div class="from-value">1 Ground</div>
                        <div class="equals">=</div>
                        <div class="to-value">2,400 Sq Ft</div>
                    </div>
                    <div class="conversion-card" onclick="quickConvert(1, 'kanal', 'sqft')">
                        <div class="from-value">1 Kanal</div>
                        <div class="equals">=</div>
                        <div class="to-value">5,445 Sq Ft</div>
                    </div>
                </div>
            </div>
            
            <div class="unit-info">
                <h3>📚 Unit Information</h3>
                <table class="unit-table">
                    <thead>
                        <tr>
                            <th>Unit</th>
                            <th>Common Usage</th>
                            <th>Value in Sq Ft</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Square Feet (sq ft)</td>
                            <td>Most common unit in India</td>
                            <td>1</td>
                        </tr>
                        <tr>
                            <td>Square Meter (sq m)</td>
                            <td>International standard</td>
                            <td>10.764</td>
                        </tr>
                        <tr>
                            <td>Square Yard (sq yd)</td>
                            <td>Used in North India</td>
                            <td>9</td>
                        </tr>
                        <tr>
                            <td>Acre</td>
                            <td>Agricultural land</td>
                            <td>43,560</td>
                        </tr>
                        <tr>
                            <td>Hectare</td>
                            <td>Large plots, international</td>
                            <td>107,639</td>
                        </tr>
                        <tr>
                            <td>Guntha</td>
                            <td>Maharashtra, Karnataka</td>
                            <td>1,089</td>
                        </tr>
                        <tr>
                            <td>Bigha</td>
                            <td>Varies by state (UP, Bihar)</td>
                            <td>27,225</td>
                        </tr>
                        <tr>
                            <td>Ground</td>
                            <td>Tamil Nadu</td>
                            <td>2,400</td>
                        </tr>
                        <tr>
                            <td>Cent</td>
                            <td>Kerala, Tamil Nadu</td>
                            <td>435.6</td>
                        </tr>
                        <tr>
                            <td>Marla</td>
                            <td>Punjab, Pakistan</td>
                            <td>272.25</td>
                        </tr>
                        <tr>
                            <td>Kanal</td>
                            <td>Punjab, Haryana</td>
                            <td>5,445</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <footer class="footer">
        <p>&copy; 2025 JD Realty & Investment. All rights reserved.</p>
        <nav style="margin-top: 10px;">
            <a href="index.php" style="color: #9ca3af; margin: 0 10px; text-decoration: none;">Home</a> |
            <a href="emi-calculator.php" style="color: #9ca3af; margin: 0 10px; text-decoration: none;">EMI Calculator</a> |
            <a href="budget-calculator.php" style="color: #9ca3af; margin: 0 10px; text-decoration: none;">Budget Calculator</a>
        </nav>
    </footer>
    
    <script>
        // Conversion factors to square feet
        const toSqFt = {
            sqft: 1,
            sqm: 10.7639,
            sqyd: 9,
            acre: 43560,
            hectare: 107639,
            gunta: 1089,
            bigha: 27225,
            ground: 2400,
            cent: 435.6,
            marla: 272.25,
            kanal: 5445
        };
        
        function convert() {
            const fromValue = parseFloat(document.getElementById('fromValue').value) || 0;
            const fromUnit = document.getElementById('fromUnit').value;
            const toUnit = document.getElementById('toUnit').value;
            
            // Convert to square feet first, then to target unit
            const sqFtValue = fromValue * toSqFt[fromUnit];
            const toValue = sqFtValue / toSqFt[toUnit];
            
            // Format the result
            let formattedValue;
            if (toValue >= 1000000) {
                formattedValue = toValue.toExponential(4);
            } else if (toValue >= 1) {
                formattedValue = toValue.toFixed(4).replace(/\.?0+$/, '');
            } else {
                formattedValue = toValue.toPrecision(4);
            }
            
            document.getElementById('toValue').value = formattedValue;
        }
        
        function swapUnits() {
            const fromUnit = document.getElementById('fromUnit');
            const toUnit = document.getElementById('toUnit');
            const fromValue = document.getElementById('fromValue');
            const toValue = document.getElementById('toValue');
            
            // Swap unit selections
            const tempUnit = fromUnit.value;
            fromUnit.value = toUnit.value;
            toUnit.value = tempUnit;
            
            // Swap values
            fromValue.value = toValue.value;
            
            // Recalculate
            convert();
        }
        
        function quickConvert(value, from, to) {
            document.getElementById('fromValue').value = value;
            document.getElementById('fromUnit').value = from;
            document.getElementById('toUnit').value = to;
            convert();
            
            // Scroll to converter
            document.querySelector('.converter-main').scrollIntoView({ behavior: 'smooth' });
        }
        
        // Initial conversion
        convert();
    </script>
</body>
</html>
