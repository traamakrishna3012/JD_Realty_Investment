<?php
include('includes/config.php');

$error = '';
$success = '';
$step = 'phone'; // phone, otp, or done
$phone = '';

// =============================================
// SMS API CONFIGURATION (Fast2SMS - Free Indian SMS API)
// =============================================
// Sign up at https://www.fast2sms.com/ to get your API key
// Add your API key below:
define('FAST2SMS_API_KEY', ''); // Add your Fast2SMS API key here

// Function to send OTP via Fast2SMS
function sendOtpSms($phone, $otp) {
    $api_key = FAST2SMS_API_KEY;
    
    // If no API key configured, return false
    if (empty($api_key)) {
        return false;
    }
    
    // Clean phone number (remove +91 or 91 prefix if present)
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($phone) == 12 && substr($phone, 0, 2) == '91') {
        $phone = substr($phone, 2);
    }
    
    // Fast2SMS API endpoint
    $url = "https://www.fast2sms.com/dev/bulkV2";
    
    $data = [
        'route' => 'otp',
        'variables_values' => $otp,
        'numbers' => $phone,
    ];
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_HTTPHEADER => [
            "authorization: $api_key",
            "Content-Type: application/x-www-form-urlencoded"
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    
    if ($err) {
        error_log("SMS Error: $err");
        return false;
    }
    
    $result = json_decode($response, true);
    return isset($result['return']) && $result['return'] == true;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Step 1: Send OTP
    if (isset($_POST['send_otp'])) {
        $phone = $conn->real_escape_string($_POST['phone']);
        
        // Validate phone number (Indian format)
        $phone_clean = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone_clean) == 10) {
            $phone_clean = '91' . $phone_clean;
        }
        
        if (strlen($phone_clean) != 12 || substr($phone_clean, 0, 2) != '91') {
            $error = "Please enter a valid 10-digit Indian mobile number.";
        } else {
            // Check if user exists with this phone - ONLY registered users allowed
            $phone_10digit = substr($phone_clean, 2); // Get 10 digit version
            $sql = "SELECT * FROM users WHERE phone='$phone' OR phone='$phone_clean' OR phone='$phone_10digit'";
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                // Generate 6-digit OTP
                $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                
                // Save OTP to database
                $update_sql = "UPDATE users SET otp='$otp', otp_expiry='$otp_expiry' WHERE id={$user['id']}";
                $conn->query($update_sql);
                
                // Store phone in session for verification
                $_SESSION['otp_phone'] = $phone;
                $_SESSION['otp_user_id'] = $user['id'];
                
                // Try to send OTP via SMS
                $sms_sent = sendOtpSms($phone_clean, $otp);
                
                if ($sms_sent) {
                    // SMS sent successfully
                    $step = 'otp';
                    $success = "OTP sent to your mobile number +91 " . substr($phone_clean, 2) . "!";
                } else {
                    // SMS API not configured or failed - show OTP for testing
                    $_SESSION['test_otp'] = $otp;
                    $step = 'otp';
                    $success = "OTP generated! (SMS API not configured - see test OTP below)";
                }
                
            } else {
                $error = "❌ No account found with this phone number.<br><br>
                         <strong>OTP Login is only for registered users.</strong><br>
                         Please <a href='signup.php' style='color: #667eea;'>Sign Up</a> first or use <a href='login.php' style='color: #667eea;'>Email Login</a>.";
            }
        }
    }
    
    // Step 2: Verify OTP
    if (isset($_POST['verify_otp'])) {
        $entered_otp = $conn->real_escape_string($_POST['otp']);
        $user_id = $_SESSION['otp_user_id'] ?? 0;
        
        if (empty($user_id)) {
            $error = "Session expired. Please try again.";
            $step = 'phone';
        } else {
            // Verify OTP
            $sql = "SELECT * FROM users WHERE id=$user_id AND otp='$entered_otp' AND otp_expiry > NOW()";
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                // Clear OTP
                $conn->query("UPDATE users SET otp=NULL, otp_expiry=NULL, phone_verified=1 WHERE id=$user_id");
                
                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                
                // Clear OTP session
                unset($_SESSION['otp_phone']);
                unset($_SESSION['otp_user_id']);
                unset($_SESSION['test_otp']);
                
                // Redirect based on role
                if ($user['role'] == 'admin') {
                    header("Location: admin/dashboard.php");
                } else {
                    header("Location: index.php");
                }
                exit();
            } else {
                $error = "Invalid or expired OTP. Please try again.";
                $step = 'otp';
                $phone = $_SESSION['otp_phone'] ?? '';
            }
        }
    }
    
    // Resend OTP
    if (isset($_POST['resend_otp'])) {
        $user_id = $_SESSION['otp_user_id'] ?? 0;
        $phone = $_SESSION['otp_phone'] ?? '';
        
        if ($user_id) {
            $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            
            $conn->query("UPDATE users SET otp='$otp', otp_expiry='$otp_expiry' WHERE id=$user_id");
            
            // Try to send via SMS
            $phone_clean = preg_replace('/[^0-9]/', '', $phone);
            if (strlen($phone_clean) == 10) {
                $phone_clean = '91' . $phone_clean;
            }
            
            $sms_sent = sendOtpSms($phone_clean, $otp);
            
            if ($sms_sent) {
                $step = 'otp';
                $success = "New OTP sent to your mobile!";
            } else {
                $_SESSION['test_otp'] = $otp;
                $step = 'otp';
                $success = "New OTP generated! (See test OTP below)";
            }
        } else {
            $step = 'phone';
            $error = "Session expired. Please enter your phone number again.";
        }
    }
}

// Check if already in OTP step
if (isset($_SESSION['otp_user_id']) && $step == 'phone') {
    $step = 'otp';
    $phone = $_SESSION['otp_phone'] ?? '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Login with OTP - JD Realty & Investment">
    <meta name="robots" content="noindex, follow">
    <title>Login with OTP - JD Realty & Investment</title>
    <link rel="stylesheet" href="css/style.css?v=20251203c">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 420px;
            padding: 50px 40px;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .logo-section {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-section img {
            width: 70px;
            height: 70px;
            margin-bottom: 15px;
        }

        .logo {
            font-size: 26px;
            color: #d97706;
            font-weight: bold;
        }

        .logo span {
            color: #4f46e5;
        }

        h2 {
            text-align: center;
            color: #1f2937;
            margin-bottom: 10px;
            font-size: 22px;
        }

        .subtitle {
            text-align: center;
            color: #6b7280;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
        }

        .phone-input-group {
            display: flex;
            gap: 10px;
        }

        .country-code {
            width: 70px;
            padding: 14px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 16px;
            background: #f9fafb;
            text-align: center;
            font-weight: 600;
        }

        input[type="tel"],
        input[type="text"] {
            flex: 1;
            padding: 14px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
        }

        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .otp-inputs {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .otp-input {
            width: 50px;
            height: 55px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .otp-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .submit-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #ef4444;
        }

        .success-message {
            background: #dcfce7;
            color: #166534;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #22c55e;
        }

        .test-otp {
            background: #fef3c7;
            color: #92400e;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
            border: 2px dashed #f59e0b;
        }

        .resend-section {
            text-align: center;
            margin-top: 20px;
        }

        .resend-btn {
            background: none;
            border: none;
            color: #667eea;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
        }

        .resend-btn:hover {
            text-decoration: underline;
        }

        .timer {
            color: #6b7280;
            font-size: 13px;
            margin-top: 5px;
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 25px 0;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e5e7eb;
        }

        .divider span {
            padding: 0 15px;
            color: #9ca3af;
            font-size: 13px;
        }

        .alt-login {
            text-align: center;
        }

        .alt-login a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .alt-login a:hover {
            text-decoration: underline;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 25px;
            color: #6b7280;
            text-decoration: none;
            font-size: 14px;
        }

        .back-link:hover {
            color: #667eea;
        }

        .phone-icon {
            font-size: 50px;
            text-align: center;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-section" style="display: flex; align-items: center; justify-content: center; gap: 12px;">
            <img src="images/jd-logo.svg?v=20251203" alt="JD Realty & Investment" style="width: 60px; height: 60px;">
            <span style="font-size: 22px; font-weight: bold; color: #d4a84b;">JD Realty Investment</span>
        </div>

        <?php if ($step == 'phone'): ?>
            <!-- Step 1: Enter Phone Number -->
            <div class="phone-icon">📱</div>
            <h2>Login with OTP</h2>
            <p class="subtitle">Enter your registered mobile number<br><small style="color: #f59e0b;">⚠️ Only for registered users</small></p>

            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Mobile Number</label>
                    <div class="phone-input-group">
                        <input type="text" class="country-code" value="+91" readonly>
                        <input type="tel" name="phone" placeholder="Enter 10-digit number" 
                               maxlength="10" pattern="[0-9]{10}" required
                               value="<?php echo htmlspecialchars($phone); ?>">
                    </div>
                </div>
                <button type="submit" name="send_otp" class="submit-btn">📤 Send OTP</button>
            </form>
            
            <p style="text-align: center; font-size: 12px; color: #6b7280; margin-top: 15px;">
                Don't have an account? <a href="signup.php" style="color: #667eea;">Sign Up first</a>
            </p>

            <div class="divider"><span>OR</span></div>

            <div class="alt-login">
                <a href="login.php">Login with Email & Password</a>
            </div>

        <?php elseif ($step == 'otp'): ?>
            <!-- Step 2: Enter OTP -->
            <div class="phone-icon">🔐</div>
            <h2>Verify OTP</h2>
            <p class="subtitle">Enter the 6-digit code sent to<br><strong><?php echo htmlspecialchars($_SESSION['otp_phone'] ?? $phone); ?></strong></p>

            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>

            <!-- Test OTP Display (Remove in Production!) -->
            <?php if (isset($_SESSION['test_otp'])): ?>
                <div class="test-otp">
                    🧪 <strong>Test OTP:</strong> <?php echo $_SESSION['test_otp']; ?><br>
                    <small>(Remove this in production)</small>
                </div>
            <?php endif; ?>

            <form method="POST" id="otpForm">
                <div class="form-group">
                    <label>Enter OTP</label>
                    <div class="otp-inputs">
                        <input type="text" class="otp-input" maxlength="1" data-index="0" autofocus>
                        <input type="text" class="otp-input" maxlength="1" data-index="1">
                        <input type="text" class="otp-input" maxlength="1" data-index="2">
                        <input type="text" class="otp-input" maxlength="1" data-index="3">
                        <input type="text" class="otp-input" maxlength="1" data-index="4">
                        <input type="text" class="otp-input" maxlength="1" data-index="5">
                    </div>
                    <input type="hidden" name="otp" id="otpValue">
                </div>
                <button type="submit" name="verify_otp" class="submit-btn">✓ Verify & Login</button>
            </form>

            <div class="resend-section">
                <form method="POST" style="display: inline;">
                    <button type="submit" name="resend_otp" class="resend-btn">Resend OTP</button>
                </form>
                <div class="timer" id="timer">Resend available in <span id="countdown">60</span>s</div>
            </div>

            <a href="login-otp.php" class="back-link" onclick="<?php unset($_SESSION['otp_phone']); unset($_SESSION['otp_user_id']); ?>">← Change Phone Number</a>

        <?php endif; ?>

        <a href="index.php" class="back-link">← Back to Home</a>
    </div>

    <script>
        // OTP Input Handler
        const otpInputs = document.querySelectorAll('.otp-input');
        const otpValue = document.getElementById('otpValue');

        otpInputs.forEach((input, index) => {
            input.addEventListener('input', (e) => {
                const value = e.target.value;
                
                // Only allow numbers
                e.target.value = value.replace(/[^0-9]/g, '');
                
                // Move to next input
                if (value && index < otpInputs.length - 1) {
                    otpInputs[index + 1].focus();
                }
                
                // Combine OTP values
                updateOtpValue();
            });

            input.addEventListener('keydown', (e) => {
                // Handle backspace
                if (e.key === 'Backspace' && !input.value && index > 0) {
                    otpInputs[index - 1].focus();
                }
            });

            // Handle paste
            input.addEventListener('paste', (e) => {
                e.preventDefault();
                const pasteData = e.clipboardData.getData('text').replace(/[^0-9]/g, '').slice(0, 6);
                
                pasteData.split('').forEach((char, i) => {
                    if (otpInputs[i]) {
                        otpInputs[i].value = char;
                    }
                });
                
                updateOtpValue();
                if (pasteData.length > 0) {
                    otpInputs[Math.min(pasteData.length, 5)].focus();
                }
            });
        });

        function updateOtpValue() {
            let otp = '';
            otpInputs.forEach(input => {
                otp += input.value;
            });
            otpValue.value = otp;
        }

        // Countdown Timer
        let countdown = 60;
        const countdownEl = document.getElementById('countdown');
        const timerEl = document.getElementById('timer');
        const resendBtn = document.querySelector('.resend-btn');

        if (countdownEl && resendBtn) {
            resendBtn.disabled = true;
            resendBtn.style.opacity = '0.5';

            const interval = setInterval(() => {
                countdown--;
                countdownEl.textContent = countdown;

                if (countdown <= 0) {
                    clearInterval(interval);
                    timerEl.style.display = 'none';
                    resendBtn.disabled = false;
                    resendBtn.style.opacity = '1';
                }
            }, 1000);
        }
    </script>
</body>
</html>
