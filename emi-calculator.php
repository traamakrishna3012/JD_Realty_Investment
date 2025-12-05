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
    <meta name="description" content="EMI Calculator - Calculate your home loan EMI instantly. Plan your property purchase with JD Realty's easy-to-use mortgage calculator.">
    <meta name="keywords" content="EMI calculator, home loan calculator, mortgage calculator, property loan, JD Realty">
    <meta property="og:title" content="EMI Calculator | JD Realty & Investment">
    <meta property="og:description" content="Calculate your monthly EMI for home loans. Plan your dream property purchase.">
    <meta property="og:type" content="website">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?php echo defined('SITE_URL') ? SITE_URL : 'https://jdrealtyinvestment.com'; ?>/emi-calculator.php">
    <link rel="icon" type="image/x-icon" href="images/jd-logo.svg">
    <title>EMI Calculator | JD Realty & Investment</title>
    <link rel="stylesheet" href="css/style.css?v=20251203c">
    
    <!-- Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebApplication",
        "name": "EMI Calculator",
        "description": "Calculate home loan EMI instantly",
        "applicationCategory": "FinanceApplication",
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
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ec 100%);
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
            max-width: 1200px;
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
        
        .calculator-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }
        
        .calculator-form h2 {
            font-size: 24px;
            color: #1f2937;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 10px;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-wrapper .currency {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-weight: 600;
            color: #6b7280;
        }
        
        .input-wrapper .suffix {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-weight: 600;
            color: #6b7280;
        }
        
        .form-group input[type="number"],
        .form-group input[type="range"] {
            width: 100%;
            padding: 15px 15px 15px 35px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-group input[type="number"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        
        .form-group input[type="range"] {
            padding: 0;
            height: 8px;
            -webkit-appearance: none;
            background: linear-gradient(to right, #667eea 0%, #667eea 50%, #e5e7eb 50%, #e5e7eb 100%);
            border-radius: 5px;
            border: none;
        }
        
        .form-group input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 24px;
            height: 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(102, 126, 234, 0.4);
        }
        
        .range-labels {
            display: flex;
            justify-content: space-between;
            margin-top: 8px;
            color: #6b7280;
            font-size: 12px;
        }
        
        .slider-value {
            text-align: center;
            font-weight: 600;
            color: #667eea;
            margin-top: 5px;
            font-size: 14px;
        }
        
        .calculate-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .calculate-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .result-section {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .result-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 40px;
            color: white;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .result-card h3 {
            font-size: 18px;
            opacity: 0.9;
            margin-bottom: 10px;
        }
        
        .emi-amount {
            font-size: 48px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .emi-label {
            font-size: 14px;
            opacity: 0.8;
        }
        
        .breakdown {
            background: #f9fafb;
            border-radius: 15px;
            padding: 25px;
        }
        
        .breakdown h4 {
            font-size: 18px;
            color: #1f2937;
            margin-bottom: 20px;
        }
        
        .breakdown-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .breakdown-item:last-child {
            border-bottom: none;
            font-weight: 600;
            color: #667eea;
        }
        
        .breakdown-label {
            color: #6b7280;
        }
        
        .breakdown-value {
            font-weight: 600;
            color: #1f2937;
        }
        
        /* Chart */
        .chart-container {
            margin-top: 30px;
        }
        
        .donut-chart {
            width: 200px;
            height: 200px;
            margin: 0 auto 20px;
            position: relative;
        }
        
        .chart-legend {
            display: flex;
            justify-content: center;
            gap: 30px;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 4px;
        }
        
        .legend-color.principal {
            background: #667eea;
        }
        
        .legend-color.interest {
            background: #f59e0b;
        }
        
        .footer {
            background: #1f2937;
            color: white;
            padding: 40px;
            text-align: center;
            margin-top: 60px;
        }
        
        @media (max-width: 768px) {
            .calculator-container {
                grid-template-columns: 1fr;
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
            <h1>🏦 EMI Calculator</h1>
            <p>Calculate your monthly home loan EMI and plan your property purchase wisely</p>
        </div>
        
        <div class="calculator-container">
            <div class="calculator-form">
                <h2>📊 Enter Loan Details</h2>
                
                <div class="form-group">
                    <label>Loan Amount (₹)</label>
                    <div class="input-wrapper">
                        <span class="currency">₹</span>
                        <input type="number" id="loanAmount" value="5000000" min="100000" max="100000000">
                    </div>
                    <input type="range" id="loanAmountSlider" min="100000" max="100000000" value="5000000" step="100000">
                    <div class="range-labels">
                        <span>₹1 Lac</span>
                        <span>₹10 Cr</span>
                    </div>
                    <div class="slider-value" id="loanAmountDisplay">₹50,00,000</div>
                </div>
                
                <div class="form-group">
                    <label>Interest Rate (% per annum)</label>
                    <div class="input-wrapper">
                        <input type="number" id="interestRate" value="8.5" min="1" max="20" step="0.1" style="padding-left: 15px;">
                        <span class="suffix">%</span>
                    </div>
                    <input type="range" id="interestRateSlider" min="5" max="20" value="8.5" step="0.1">
                    <div class="range-labels">
                        <span>5%</span>
                        <span>20%</span>
                    </div>
                    <div class="slider-value" id="interestRateDisplay">8.5%</div>
                </div>
                
                <div class="form-group">
                    <label>Loan Tenure (Years)</label>
                    <div class="input-wrapper">
                        <input type="number" id="loanTenure" value="20" min="1" max="30" style="padding-left: 15px;">
                        <span class="suffix">Years</span>
                    </div>
                    <input type="range" id="loanTenureSlider" min="1" max="30" value="20">
                    <div class="range-labels">
                        <span>1 Year</span>
                        <span>30 Years</span>
                    </div>
                    <div class="slider-value" id="loanTenureDisplay">20 Years</div>
                </div>
                
                <button class="calculate-btn" onclick="calculateEMI()">Calculate EMI</button>
            </div>
            
            <div class="result-section">
                <div class="result-card">
                    <h3>Your Monthly EMI</h3>
                    <div class="emi-amount" id="emiResult">₹43,391</div>
                    <div class="emi-label">per month</div>
                </div>
                
                <div class="breakdown">
                    <h4>Payment Breakdown</h4>
                    <div class="breakdown-item">
                        <span class="breakdown-label">Principal Amount</span>
                        <span class="breakdown-value" id="principalAmount">₹50,00,000</span>
                    </div>
                    <div class="breakdown-item">
                        <span class="breakdown-label">Total Interest</span>
                        <span class="breakdown-value" id="totalInterest">₹54,13,840</span>
                    </div>
                    <div class="breakdown-item">
                        <span class="breakdown-label">Total Amount Payable</span>
                        <span class="breakdown-value" id="totalPayable">₹1,04,13,840</span>
                    </div>
                </div>
                
                <div class="chart-container">
                    <canvas id="emiChart" width="200" height="200"></canvas>
                    <div class="chart-legend">
                        <div class="legend-item">
                            <div class="legend-color principal"></div>
                            <span>Principal</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color interest"></div>
                            <span>Interest</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="footer">
        <p>&copy; 2025 JD Realty & Investment. All rights reserved.</p>
        <nav style="margin-top: 10px;">
            <a href="index.php" style="color: #9ca3af; margin: 0 10px; text-decoration: none;">Home</a> |
            <a href="budget-calculator.php" style="color: #9ca3af; margin: 0 10px; text-decoration: none;">Budget Calculator</a> |
            <a href="area-converter.php" style="color: #9ca3af; margin: 0 10px; text-decoration: none;">Area Converter</a>
        </nav>
    </footer>
    
    <script>
        // Sync sliders with inputs
        const loanAmountInput = document.getElementById('loanAmount');
        const loanAmountSlider = document.getElementById('loanAmountSlider');
        const loanAmountDisplay = document.getElementById('loanAmountDisplay');
        
        const interestRateInput = document.getElementById('interestRate');
        const interestRateSlider = document.getElementById('interestRateSlider');
        const interestRateDisplay = document.getElementById('interestRateDisplay');
        
        const loanTenureInput = document.getElementById('loanTenure');
        const loanTenureSlider = document.getElementById('loanTenureSlider');
        const loanTenureDisplay = document.getElementById('loanTenureDisplay');
        
        function formatIndianCurrency(num) {
            const x = num.toString().split('.');
            let lastThree = x[0].substring(x[0].length - 3);
            const otherNumbers = x[0].substring(0, x[0].length - 3);
            if (otherNumbers !== '') {
                lastThree = ',' + lastThree;
            }
            return '₹' + otherNumbers.replace(/\B(?=(\d{2})+(?!\d))/g, ',') + lastThree;
        }
        
        // Loan Amount sync
        loanAmountSlider.addEventListener('input', function() {
            loanAmountInput.value = this.value;
            loanAmountDisplay.textContent = formatIndianCurrency(this.value);
            calculateEMI();
        });
        
        loanAmountInput.addEventListener('input', function() {
            loanAmountSlider.value = this.value;
            loanAmountDisplay.textContent = formatIndianCurrency(this.value);
            calculateEMI();
        });
        
        // Interest Rate sync
        interestRateSlider.addEventListener('input', function() {
            interestRateInput.value = this.value;
            interestRateDisplay.textContent = this.value + '%';
            calculateEMI();
        });
        
        interestRateInput.addEventListener('input', function() {
            interestRateSlider.value = this.value;
            interestRateDisplay.textContent = this.value + '%';
            calculateEMI();
        });
        
        // Loan Tenure sync
        loanTenureSlider.addEventListener('input', function() {
            loanTenureInput.value = this.value;
            loanTenureDisplay.textContent = this.value + ' Years';
            calculateEMI();
        });
        
        loanTenureInput.addEventListener('input', function() {
            loanTenureSlider.value = this.value;
            loanTenureDisplay.textContent = this.value + ' Years';
            calculateEMI();
        });
        
        function calculateEMI() {
            const principal = parseFloat(loanAmountInput.value);
            const annualRate = parseFloat(interestRateInput.value);
            const years = parseInt(loanTenureInput.value);
            
            const monthlyRate = annualRate / 12 / 100;
            const months = years * 12;
            
            // EMI formula: [P x R x (1+R)^N]/[(1+R)^N-1]
            const emi = principal * monthlyRate * Math.pow(1 + monthlyRate, months) / (Math.pow(1 + monthlyRate, months) - 1);
            
            const totalPayable = emi * months;
            const totalInterest = totalPayable - principal;
            
            // Update display
            document.getElementById('emiResult').textContent = formatIndianCurrency(Math.round(emi));
            document.getElementById('principalAmount').textContent = formatIndianCurrency(principal);
            document.getElementById('totalInterest').textContent = formatIndianCurrency(Math.round(totalInterest));
            document.getElementById('totalPayable').textContent = formatIndianCurrency(Math.round(totalPayable));
            
            // Draw chart
            drawChart(principal, totalInterest);
        }
        
        function drawChart(principal, interest) {
            const canvas = document.getElementById('emiChart');
            const ctx = canvas.getContext('2d');
            const centerX = canvas.width / 2;
            const centerY = canvas.height / 2;
            const radius = 80;
            
            // Clear canvas
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            const total = principal + interest;
            const principalAngle = (principal / total) * 2 * Math.PI;
            
            // Draw principal arc
            ctx.beginPath();
            ctx.moveTo(centerX, centerY);
            ctx.arc(centerX, centerY, radius, -Math.PI / 2, -Math.PI / 2 + principalAngle);
            ctx.closePath();
            ctx.fillStyle = '#667eea';
            ctx.fill();
            
            // Draw interest arc
            ctx.beginPath();
            ctx.moveTo(centerX, centerY);
            ctx.arc(centerX, centerY, radius, -Math.PI / 2 + principalAngle, -Math.PI / 2 + 2 * Math.PI);
            ctx.closePath();
            ctx.fillStyle = '#f59e0b';
            ctx.fill();
            
            // Draw inner circle (donut hole)
            ctx.beginPath();
            ctx.arc(centerX, centerY, radius * 0.6, 0, 2 * Math.PI);
            ctx.fillStyle = 'white';
            ctx.fill();
            
            // Draw percentage text
            const principalPercent = Math.round((principal / total) * 100);
            ctx.font = 'bold 20px Segoe UI';
            ctx.fillStyle = '#1f2937';
            ctx.textAlign = 'center';
            ctx.fillText(principalPercent + '%', centerX, centerY - 5);
            ctx.font = '12px Segoe UI';
            ctx.fillStyle = '#6b7280';
            ctx.fillText('Principal', centerX, centerY + 15);
        }
        
        // Initial calculation
        calculateEMI();
    </script>
</body>
</html>
