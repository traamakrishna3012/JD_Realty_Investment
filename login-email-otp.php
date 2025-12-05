<?php
include('includes/config.php');

$error = '';
$success = '';
$step = 'email'; // email, otp, or done
$email = '';

// Include email configuration
require_once 'includes/email-config.php';

// Function to send OTP via Email using PHPMailer directly
function sendOtpEmail($to_email, $otp, $userName) {
    $subject = "Your JD Realty Login OTP: $otp";
    
    $body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 500px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
            .otp-box { background: white; padding: 25px; margin: 20px 0; border-radius: 10px; text-align: center; border: 2px dashed #667eea; }
            .otp-code { font-size: 36px; font-weight: bold; letter-spacing: 8px; color: #667eea; }
            .footer { background: #333; color: white; padding: 15px; text-align: center; font-size: 12px; border-radius: 0 0 10px 10px; }
            .warning { color: #dc2626; font-size: 12px; margin-top: 15px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>JD Realty & Investment</h2>
                <p>Login Verification</p>
            </div>
            <div class='content'>
                <p>Hello" . ($userName ? " <strong>$userName</strong>" : "") . ",</p>
                <p>You requested to login to your JD Realty account. Use the OTP below to complete your login:</p>
                
                <div class='otp-box'>
                    <p style='margin-bottom: 10px; color: #6b7280;'>Your One-Time Password</p>
                    <div class='otp-code'>$otp</div>
                </div>
                
                <p>This OTP is valid for <strong>10 minutes</strong>.</p>
                <p class='warning'>Do not share this OTP with anyone. JD Realty will never ask for your OTP.</p>
            </div>
            <div class='footer'>
                <p>This email was sent by JD Realty & Investment</p>
                <p>If you didn't request this, please ignore this email.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Use PHPMailer directly for better error handling
    $phpmailer_path = dirname(__FILE__) . '/phpmailer/PHPMailer.php';
    
    if (file_exists($phpmailer_path)) {
        require_once dirname(__FILE__) . '/phpmailer/PHPMailer.php';
        require_once dirname(__FILE__) . '/phpmailer/SMTP.php';
        require_once dirname(__FILE__) . '/phpmailer/Exception.php';
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = SMTP_PORT;
            
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            $mail->Timeout = 30;
            $mail->setFrom(FROM_EMAIL, FROM_NAME);
            $mail->addAddress($to_email);
            
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = "Your JD Realty OTP is: $otp. Valid for 10 minutes.";
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("OTP Email Error to $to_email: " . $mail->ErrorInfo);
            return false;
        }
    }
    
    return false;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Step 1: Send OTP to Email
    if (isset($_POST['send_otp'])) {
        $email = $conn->real_escape_string(trim($_POST['email']));
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } else {
            // Check if user exists with this email
            $sql = "SELECT * FROM users WHERE email='$email'";
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                // Generate 6-digit OTP
                $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                
                // Save OTP to database
                $update_sql = "UPDATE users SET otp='$otp', otp_expiry='$otp_expiry' WHERE id={$user['id']}";
                $conn->query($update_sql);
                
                // Store email in session for verification
                $_SESSION['otp_email'] = $email;
                $_SESSION['otp_user_id'] = $user['id'];
                
                // Try to send OTP via Email
                $email_sent = sendOtpEmail($email, $otp, $user['name']);
                
                if ($email_sent) {
                    // Email sent successfully
                    $step = 'otp';
                    $success = "OTP sent to your email address <strong>" . htmlspecialchars($email) . "</strong>. Please check your inbox (and spam folder).";
                } else {
                    // Email sending failed
                    $step = 'otp';
                    $error = "Could not send OTP email. Please try again or contact support.";
                }
                
            } else {
                $error = "❌ No account found with this email address.<br><br>
                         <strong>OTP Login is only for registered users.</strong><br>
                         Please <a href='signup.php' style='color: #667eea;'>Sign Up</a> first or use <a href='login.php' style='color: #667eea;'>Password Login</a>.";
            }
        }
    }
    
    // Step 2: Verify OTP
    if (isset($_POST['verify_otp'])) {
        $entered_otp = $conn->real_escape_string(trim($_POST['otp']));
        // Try session first, then fall back to POST data
        $user_id = !empty($_SESSION['otp_user_id']) ? $_SESSION['otp_user_id'] : (!empty($_POST['user_id']) ? intval($_POST['user_id']) : 0);
        $verify_email = !empty($_SESSION['otp_email']) ? $_SESSION['otp_email'] : (!empty($_POST['user_email']) ? $_POST['user_email'] : '');
        
        // DEBUG - Remove after fixing
        error_log("OTP Verify - Entered: $entered_otp, User ID: $user_id, Email: $verify_email");
        
        if (empty($user_id) && empty($verify_email)) {
            $error = "Session expired. Please try again.";
            $step = 'email';
        } else {
            // Verify OTP by email (most reliable since session is not working)
            $verified = false;
            $user = null;
            
            // Use PHP time instead of MySQL NOW() to avoid timezone mismatch
            $current_time = date('Y-m-d H:i:s');
            
            // Try by email first (more reliable)
            if (!empty($verify_email)) {
                $verify_email_escaped = $conn->real_escape_string($verify_email);
                $sql = "SELECT * FROM users WHERE email='$verify_email_escaped' AND otp='$entered_otp' AND otp_expiry > '$current_time'";
                error_log("OTP SQL: $sql");
                $result = $conn->query($sql);
                if ($result && $result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                    $verified = true;
                }
            }
            
            // If not verified by email, try by user_id
            if (!$verified && !empty($user_id)) {
                $sql = "SELECT * FROM users WHERE id=$user_id AND otp='$entered_otp' AND otp_expiry > '$current_time'";
                $result = $conn->query($sql);
                if ($result && $result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                    $verified = true;
                }
            }
            
            if ($verified && $user) {
                // Clear OTP
                $conn->query("UPDATE users SET otp=NULL, otp_expiry=NULL WHERE id={$user['id']}");
                
                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                
                // Clear OTP session
                unset($_SESSION['otp_email']);
                unset($_SESSION['otp_user_id']);
                
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
                $email = $verify_email;
                // Restore session values for retry
                if (!empty($user_id)) $_SESSION['otp_user_id'] = $user_id;
                if (!empty($verify_email)) $_SESSION['otp_email'] = $verify_email;
            }
        }
    }
    
    // Resend OTP
    if (isset($_POST['resend_otp'])) {
        $user_id = !empty($_SESSION['otp_user_id']) ? $_SESSION['otp_user_id'] : (!empty($_POST['resend_user_id']) ? intval($_POST['resend_user_id']) : 0);
        $email = !empty($_SESSION['otp_email']) ? $_SESSION['otp_email'] : (!empty($_POST['resend_email']) ? $_POST['resend_email'] : '');
        
        if ($user_id && $email) {
            // Store in session again
            $_SESSION['otp_user_id'] = $user_id;
            $_SESSION['otp_email'] = $email;
            
            // Get user info
            $user_result = $conn->query("SELECT name FROM users WHERE id=$user_id");
            $user = $user_result->fetch_assoc();
            
            $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            
            $conn->query("UPDATE users SET otp='$otp', otp_expiry='$otp_expiry' WHERE id=$user_id");
            
            // Try to send via Email
            $email_sent = sendOtpEmail($email, $otp, $user['name']);
            
            if ($email_sent) {
                $step = 'otp';
                $success = "New OTP sent to your email!";
            } else {
                $step = 'otp';
                $error = "Could not send OTP email. Please try again.";
            }
        } else {
            $step = 'email';
            $error = "Session expired. Please enter your email again.";
        }
    }
}

// Check if already in OTP step
if (isset($_SESSION['otp_user_id']) && $step == 'email') {
    $step = 'otp';
    $email = $_SESSION['otp_email'] ?? '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Login with Email OTP - JD Realty & Investment">
    <meta name="robots" content="noindex, follow">
    <title>Login with Email OTP - JD Realty & Investment</title>
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
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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
            height: 100px;
            max-width: 280px;
            margin-bottom: 15px;
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

        input[type="email"],
        input[type="text"] {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
        }

        input:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
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
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .submit-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.4);
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
            color: #10b981;
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
            color: #10b981;
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
            color: #10b981;
        }

        .email-icon {
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

        <?php if ($step == 'email'): ?>
            <!-- Step 1: Enter Email -->
            <div class="email-icon">📧</div>
            <h2>Login with Email OTP</h2>
            <p class="subtitle">Enter your registered email address<br><small style="color: #10b981;">✅ No SMS required - OTP sent to email</small></p>

            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="Enter your registered email" required
                           value="<?php echo htmlspecialchars($email); ?>">
                </div>
                <button type="submit" name="send_otp" class="submit-btn">📤 Send OTP to Email</button>
            </form>
            
            <p style="text-align: center; font-size: 12px; color: #6b7280; margin-top: 15px;">
                Don't have an account? <a href="signup.php" style="color: #10b981;">Sign Up first</a>
            </p>

            <div class="divider"><span>OR</span></div>

            <div class="alt-login">
                <a href="login.php">Login with Email & Password</a>
            </div>

        <?php elseif ($step == 'otp'): ?>
            <!-- Step 2: Enter OTP -->
            <div class="email-icon">🔐</div>
            <h2>Verify OTP</h2>
            <p class="subtitle">Enter the 6-digit code sent to<br><strong><?php echo htmlspecialchars($_SESSION['otp_email'] ?? $email); ?></strong></p>

            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" id="otpForm">
                <!-- Hidden fields for session backup -->
                <?php 
                $form_user_id = !empty($_SESSION['otp_user_id']) ? $_SESSION['otp_user_id'] : '';
                $form_email = !empty($_SESSION['otp_email']) ? $_SESSION['otp_email'] : $email;
                ?>
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($form_user_id); ?>">
                <input type="hidden" name="user_email" value="<?php echo htmlspecialchars($form_email); ?>">
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
                    <input type="hidden" name="resend_user_id" value="<?php echo htmlspecialchars($form_user_id); ?>">
                    <input type="hidden" name="resend_email" value="<?php echo htmlspecialchars($form_email); ?>">
                    <button type="submit" name="resend_otp" class="resend-btn">Resend OTP</button>
                </form>
                <div class="timer" id="timer">Resend available in <span id="countdown">60</span>s</div>
            </div>

            <a href="login-email-otp.php" class="back-link" onclick="<?php unset($_SESSION['otp_email']); unset($_SESSION['otp_user_id']); ?>">← Change Email Address</a>

        <?php endif; ?>

        <a href="index.php" class="back-link">← Back to Home</a>
    </div>

    <script>
        // OTP Input Handler
        const otpInputs = document.querySelectorAll('.otp-input');
        const otpValue = document.getElementById('otpValue');

        if (otpInputs.length > 0) {
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
        }

        function updateOtpValue() {
            let otp = '';
            otpInputs.forEach(input => {
                otp += input.value;
            });
            if (otpValue) {
                otpValue.value = otp;
            }
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
