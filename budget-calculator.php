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
    <meta name="description" content="Budget Calculator - Find properties within your budget. Calculate your home buying capacity based on income and savings with JD Realty.">
    <meta name="keywords" content="budget calculator, property budget, home buying budget, affordability calculator, JD Realty">
    <meta property="og:title" content="Budget Calculator | JD Realty & Investment">
    <meta property="og:description" content="Calculate your property budget based on income and savings.">
    <meta property="og:type" content="website">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?php echo defined('SITE_URL') ? SITE_URL : 'https://jdrealtyinvestment.com'; ?>/budget-calculator.php">
    <link rel="icon" type="image/x-icon" href="images/jd-logo.svg">
    <title>Budget Calculator | JD Realty & Investment</title>
    <link rel="stylesheet" href="css/style.css?v=20251203c">
    
    <!-- Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebApplication",
        "name": "Budget Calculator",
        "description": "Calculate your property buying budget",
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
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
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
        
        .form-group label small {
            font-weight: normal;
            color: #6b7280;
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
        .form-group select {
            width: 100%;
            padding: 15px 15px 15px 35px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-group select {
            padding-left: 15px;
        }
        
        .form-group input[type="number"]:focus,
        .form-group select:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
        }
        
        .form-group input[type="range"] {
            width: 100%;
            padding: 0;
            height: 8px;
            -webkit-appearance: none;
            background: linear-gradient(to right, #10b981 0%, #10b981 50%, #e5e7eb 50%, #e5e7eb 100%);
            border-radius: 5px;
            border: none;
        }
        
        .form-group input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 24px;
            height: 24px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(16, 185, 129, 0.4);
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
            color: #10b981;
            margin-top: 5px;
            font-size: 14px;
        }
        
        .calculate-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.4);
        }
        
        .result-section {
            display: flex;
            flex-direction: column;
        }
        
        .result-card {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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
        
        .budget-amount {
            font-size: 42px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .budget-label {
            font-size: 14px;
            opacity: 0.8;
        }
        
        .affordability-meter {
            background: #f9fafb;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .meter-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .meter-header h4 {
            font-size: 16px;
            color: #1f2937;
        }
        
        .meter-percentage {
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 14px;
        }
        
        .meter-percentage.safe {
            background: #d1fae5;
            color: #065f46;
        }
        
        .meter-percentage.moderate {
            background: #fef3c7;
            color: #92400e;
        }
        
        .meter-percentage.risky {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .meter-bar {
            height: 12px;
            background: #e5e7eb;
            border-radius: 6px;
            overflow: hidden;
        }
        
        .meter-fill {
            height: 100%;
            border-radius: 6px;
            transition: width 0.5s ease;
        }
        
        .meter-fill.safe {
            background: linear-gradient(90deg, #10b981, #34d399);
        }
        
        .meter-fill.moderate {
            background: linear-gradient(90deg, #f59e0b, #fbbf24);
        }
        
        .meter-fill.risky {
            background: linear-gradient(90deg, #ef4444, #f87171);
        }
        
        .meter-labels {
            display: flex;
            justify-content: space-between;
            margin-top: 8px;
            font-size: 12px;
            color: #6b7280;
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
        }
        
        .breakdown-label {
            color: #6b7280;
        }
        
        .breakdown-value {
            font-weight: 600;
            color: #1f2937;
        }
        
        .search-properties-btn {
            display: block;
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            text-align: center;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            margin-top: 20px;
            transition: all 0.3s ease;
        }
        
        .search-properties-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .tips-section {
            margin-top: 40px;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        }
        
        .tips-section h3 {
            font-size: 20px;
            color: #1f2937;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .tips-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .tip-card {
            background: #f9fafb;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #10b981;
        }
        
        .tip-card h4 {
            font-size: 16px;
            color: #1f2937;
            margin-bottom: 8px;
        }
        
        .tip-card p {
            font-size: 14px;
            color: #6b7280;
            line-height: 1.5;
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
            <h1>💰 Budget Calculator</h1>
            <p>Calculate how much property you can afford based on your income and savings</p>
        </div>
        
        <div class="calculator-container">
            <div class="calculator-form">
                <h2>📋 Enter Your Financial Details</h2>
                
                <div class="form-group">
                    <label>Monthly Income (₹) <small>After tax</small></label>
                    <div class="input-wrapper">
                        <span class="currency">₹</span>
                        <input type="number" id="monthlyIncome" value="100000" min="10000" max="10000000">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Monthly Expenses (₹) <small>Excluding rent</small></label>
                    <div class="input-wrapper">
                        <span class="currency">₹</span>
                        <input type="number" id="monthlyExpenses" value="40000" min="0" max="10000000">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Available Down Payment (₹)</label>
                    <div class="input-wrapper">
                        <span class="currency">₹</span>
                        <input type="number" id="downPayment" value="1000000" min="0" max="100000000">
                    </div>
                    <input type="range" id="downPaymentSlider" min="100000" max="50000000" value="1000000" step="50000">
                    <div class="range-labels">
                        <span>₹1 Lac</span>
                        <span>₹5 Cr</span>
                    </div>
                    <div class="slider-value" id="downPaymentDisplay">₹10,00,000</div>
                </div>
                
                <div class="form-group">
                    <label>Existing EMI (₹) <small>Car loan, personal loan, etc.</small></label>
                    <div class="input-wrapper">
                        <span class="currency">₹</span>
                        <input type="number" id="existingEmi" value="0" min="0" max="1000000">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Expected Interest Rate (%)</label>
                    <div class="input-wrapper">
                        <input type="number" id="interestRate" value="8.5" min="5" max="15" step="0.1" style="padding-left: 15px;">
                        <span class="suffix">%</span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Loan Tenure (Years)</label>
                    <select id="loanTenure">
                        <option value="10">10 Years</option>
                        <option value="15">15 Years</option>
                        <option value="20" selected>20 Years</option>
                        <option value="25">25 Years</option>
                        <option value="30">30 Years</option>
                    </select>
                </div>
                
                <button class="calculate-btn" onclick="calculateBudget()">Calculate Budget</button>
            </div>
            
            <div class="result-section">
                <div class="result-card">
                    <h3>Your Maximum Property Budget</h3>
                    <div class="budget-amount" id="budgetResult">₹62,50,000</div>
                    <div class="budget-label">You can afford a property up to this price</div>
                </div>
                
                <div class="affordability-meter">
                    <div class="meter-header">
                        <h4>EMI Affordability</h4>
                        <span class="meter-percentage safe" id="affordabilityLabel">40% of Income</span>
                    </div>
                    <div class="meter-bar">
                        <div class="meter-fill safe" id="affordabilityFill" style="width: 40%;"></div>
                    </div>
                    <div class="meter-labels">
                        <span>Safe (≤40%)</span>
                        <span>Moderate (40-50%)</span>
                        <span>Risky (>50%)</span>
                    </div>
                </div>
                
                <div class="breakdown">
                    <h4>Budget Breakdown</h4>
                    <div class="breakdown-item">
                        <span class="breakdown-label">Maximum Loan Amount</span>
                        <span class="breakdown-value" id="maxLoanAmount">₹52,50,000</span>
                    </div>
                    <div class="breakdown-item">
                        <span class="breakdown-label">Your Down Payment</span>
                        <span class="breakdown-value" id="yourDownPayment">₹10,00,000</span>
                    </div>
                    <div class="breakdown-item">
                        <span class="breakdown-label">Estimated Monthly EMI</span>
                        <span class="breakdown-value" id="estimatedEmi">₹45,000</span>
                    </div>
                    <div class="breakdown-item">
                        <span class="breakdown-label">Available for EMI</span>
                        <span class="breakdown-value" id="availableForEmi">₹60,000</span>
                    </div>
                </div>
                
                <a href="search.php" class="search-properties-btn" id="searchLink">
                    🔍 Search Properties Within Budget
                </a>
            </div>
        </div>
        
        <div class="tips-section">
            <h3>💡 Smart Home Buying Tips</h3>
            <div class="tips-grid">
                <div class="tip-card">
                    <h4>20% Down Payment</h4>
                    <p>Aim for at least 20% down payment to avoid higher interest rates and reduce your EMI burden.</p>
                </div>
                <div class="tip-card">
                    <h4>40% EMI Rule</h4>
                    <p>Keep your total EMIs (including new home loan) under 40% of your monthly income for financial stability.</p>
                </div>
                <div class="tip-card">
                    <h4>Emergency Fund</h4>
                    <p>Maintain 6-12 months of EMI as emergency fund before purchasing a property.</p>
                </div>
                <div class="tip-card">
                    <h4>Hidden Costs</h4>
                    <p>Budget for registration, stamp duty, maintenance, and interior costs - typically 10-15% of property value.</p>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="footer">
        <p>&copy; 2025 JD Realty & Investment. All rights reserved.</p>
        <nav style="margin-top: 10px;">
            <a href="index.php" style="color: #9ca3af; margin: 0 10px; text-decoration: none;">Home</a> |
            <a href="emi-calculator.php" style="color: #9ca3af; margin: 0 10px; text-decoration: none;">EMI Calculator</a> |
            <a href="area-converter.php" style="color: #9ca3af; margin: 0 10px; text-decoration: none;">Area Converter</a>
        </nav>
    </footer>
    
    <script>
        const downPaymentInput = document.getElementById('downPayment');
        const downPaymentSlider = document.getElementById('downPaymentSlider');
        const downPaymentDisplay = document.getElementById('downPaymentDisplay');
        
        function formatIndianCurrency(num) {
            const x = Math.round(num).toString().split('.');
            let lastThree = x[0].substring(x[0].length - 3);
            const otherNumbers = x[0].substring(0, x[0].length - 3);
            if (otherNumbers !== '') {
                lastThree = ',' + lastThree;
            }
            return '₹' + otherNumbers.replace(/\B(?=(\d{2})+(?!\d))/g, ',') + lastThree;
        }
        
        // Down Payment sync
        downPaymentSlider.addEventListener('input', function() {
            downPaymentInput.value = this.value;
            downPaymentDisplay.textContent = formatIndianCurrency(this.value);
            calculateBudget();
        });
        
        downPaymentInput.addEventListener('input', function() {
            downPaymentSlider.value = this.value;
            downPaymentDisplay.textContent = formatIndianCurrency(this.value);
            calculateBudget();
        });
        
        // Auto-calculate on input change
        document.querySelectorAll('input, select').forEach(input => {
            input.addEventListener('change', calculateBudget);
        });
        
        function calculateBudget() {
            const monthlyIncome = parseFloat(document.getElementById('monthlyIncome').value);
            const monthlyExpenses = parseFloat(document.getElementById('monthlyExpenses').value);
            const downPayment = parseFloat(document.getElementById('downPayment').value);
            const existingEmi = parseFloat(document.getElementById('existingEmi').value);
            const interestRate = parseFloat(document.getElementById('interestRate').value);
            const loanTenure = parseInt(document.getElementById('loanTenure').value);
            
            // Calculate available amount for EMI (40% of income - existing EMI)
            const maxEmiCapacity = monthlyIncome * 0.5; // 50% max
            const safeEmiCapacity = monthlyIncome * 0.4; // 40% safe
            const availableForEmi = safeEmiCapacity - existingEmi;
            
            // Calculate maximum loan amount using reverse EMI formula
            const monthlyRate = interestRate / 12 / 100;
            const months = loanTenure * 12;
            
            // Reverse EMI formula: P = EMI * [(1+R)^N - 1] / [R * (1+R)^N]
            const maxLoanAmount = availableForEmi * (Math.pow(1 + monthlyRate, months) - 1) / (monthlyRate * Math.pow(1 + monthlyRate, months));
            
            // Total budget = loan + down payment
            const totalBudget = maxLoanAmount + downPayment;
            
            // Calculate EMI affordability percentage
            const emiPercentage = ((availableForEmi + existingEmi) / monthlyIncome) * 100;
            
            // Update results
            document.getElementById('budgetResult').textContent = formatIndianCurrency(totalBudget);
            document.getElementById('maxLoanAmount').textContent = formatIndianCurrency(maxLoanAmount);
            document.getElementById('yourDownPayment').textContent = formatIndianCurrency(downPayment);
            document.getElementById('estimatedEmi').textContent = formatIndianCurrency(availableForEmi);
            document.getElementById('availableForEmi').textContent = formatIndianCurrency(monthlyIncome - monthlyExpenses - existingEmi);
            
            // Update affordability meter
            const affordabilityLabel = document.getElementById('affordabilityLabel');
            const affordabilityFill = document.getElementById('affordabilityFill');
            
            const displayPercentage = Math.round(emiPercentage);
            affordabilityLabel.textContent = displayPercentage + '% of Income';
            affordabilityFill.style.width = Math.min(displayPercentage, 100) + '%';
            
            // Set status class
            affordabilityLabel.className = 'meter-percentage';
            affordabilityFill.className = 'meter-fill';
            
            if (emiPercentage <= 40) {
                affordabilityLabel.classList.add('safe');
                affordabilityFill.classList.add('safe');
            } else if (emiPercentage <= 50) {
                affordabilityLabel.classList.add('moderate');
                affordabilityFill.classList.add('moderate');
            } else {
                affordabilityLabel.classList.add('risky');
                affordabilityFill.classList.add('risky');
            }
            
            // Update search link with max price
            document.getElementById('searchLink').href = 'search.php?max_price=' + Math.round(totalBudget);
        }
        
        // Initial calculation
        calculateBudget();
    </script>
</body>
</html>
