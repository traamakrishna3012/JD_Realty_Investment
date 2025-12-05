<?php
include('includes/config.php');

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $message = 'Please enter your email address.';
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $message_type = 'error';
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT id, name, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Check if password_resets table exists, create if not
            $conn->query("CREATE TABLE IF NOT EXISTS password_resets (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                token VARCHAR(64) NOT NULL,
                expiry DATETIME NOT NULL,
                used TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_token (token),
                INDEX idx_expiry (expiry)
            )");
            
            // Delete old tokens for this user
            $stmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
            $stmt->bind_param("i", $user['id']);
            $stmt->execute();
            
            // Insert new token
            $stmt = $conn->prepare("INSERT INTO password_resets (user_id, token, expiry) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $user['id'], $token, $expiry);
            
            if ($stmt->execute()) {
                // Build reset link
                $reset_link = SITE_URL . "/reset-password.php?token=" . $token;
                
                // Try to send email
                $to = $email;
                $subject = "Password Reset - JD Realty & Investment";
                $email_message = "
                <html>
                <head>
                    <title>Password Reset</title>
                </head>
                <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                    <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                        <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; text-align: center; border-radius: 10px 10px 0 0;'>
                            <h1 style='color: white; margin: 0;'>JD Realty & Investment</h1>
                        </div>
                        <div style='background: #f9fafb; padding: 30px; border-radius: 0 0 10px 10px;'>
                            <h2 style='color: #1f2937;'>Password Reset Request</h2>
                            <p>Hello <strong>{$user['name']}</strong>,</p>
                            <p>We received a request to reset your password. Click the button below to create a new password:</p>
                            <div style='text-align: center; margin: 30px 0;'>
                                <a href='{$reset_link}' style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;'>Reset Password</a>
                            </div>
                            <p style='color: #6b7280; font-size: 14px;'>Or copy and paste this link in your browser:</p>
                            <p style='background: #e5e7eb; padding: 10px; border-radius: 5px; word-break: break-all; font-size: 12px;'>{$reset_link}</p>
                            <p style='color: #ef4444; font-size: 14px;'><strong>This link will expire in 1 hour.</strong></p>
                            <p style='color: #6b7280; font-size: 14px;'>If you didn't request this, please ignore this email.</p>
                            <hr style='border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;'>
                            <p style='color: #9ca3af; font-size: 12px; text-align: center;'>© " . date('Y') . " JD Realty & Investment. All rights reserved.</p>
                        </div>
                    </div>
                </body>
                </html>
                ";
                
                $headers = "MIME-Version: 1.0\r\n";
                $headers .= "Content-type: text/html; charset=UTF-8\r\n";
                $headers .= "From: JD Realty <noreply@jdrealtyinvestment.com>\r\n";
                $headers .= "Reply-To: support@jdrealtyinvestment.com\r\n";
                
                // Try to send email
                $mail_sent = @mail($to, $subject, $email_message, $headers);
                
                if ($mail_sent) {
                    $message = 'Password reset link has been sent to your email address. Please check your inbox (and spam folder).';
                    $message_type = 'success';
                } else {
                    // Email failed but still show the link for testing (remove in production)
                    $message = 'Password reset link has been generated. If you don\'t receive an email, <a href="' . $reset_link . '" style="color: #667eea;">click here to reset your password</a>.';
                    $message_type = 'success';
                }
            } else {
                $message = 'An error occurred. Please try again later.';
                $message_type = 'error';
            }
        } else {
            // Don't reveal if email exists or not (security)
            $message = 'If an account with that email exists, a password reset link has been sent.';
            $message_type = 'success';
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Forgot your password? Reset your JD Realty account password.">
    <title>Forgot Password - JD Realty & Investment</title>
    <link rel="icon" type="image/x-icon" href="images/jd-logo.svg">
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
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .forgot-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            max-width: 450px;
            width: 100%;
        }

        .forgot-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }

        .forgot-header .icon {
            font-size: 60px;
            margin-bottom: 15px;
        }

        .forgot-header h1 {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .forgot-header p {
            opacity: 0.9;
            font-size: 14px;
        }

        .forgot-form {
            padding: 40px 30px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-weight: 600;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
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
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.5;
        }

        .message.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }

        .message.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }

        .back-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #e5e7eb;
        }

        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .back-link a:hover {
            color: #764ba2;
        }

        .info-text {
            text-align: center;
            color: #6b7280;
            font-size: 13px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <div style="display: flex; align-items: center; justify-content: center; gap: 12px; margin-bottom: 20px;">
            <img src="images/jd-logo.svg?v=20251203" alt="JD Realty & Investment" style="width: 50px; height: 50px;">
            <span style="font-size: 20px; font-weight: bold; color: #d4a84b;">JD Realty Investment</span>
        </div>
        <div class="forgot-header">
            <div class="icon">🔐</div>
            <h1>Forgot Password?</h1>
            <p>Enter your email to reset your password</p>
        </div>
        
        <div class="forgot-form">
            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">📧 Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your registered email" required 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <button type="submit" class="submit-btn">Send Reset Link</button>
            </form>
            
            <p class="info-text">We'll send you a link to reset your password.</p>
            
            <div class="back-link">
                <a href="login.php">← Back to Login</a>
                &nbsp;&nbsp;|&nbsp;&nbsp;
                <a href="admin/login.php">Admin Login →</a>
            </div>
        </div>
    </div>
</body>
</html>
