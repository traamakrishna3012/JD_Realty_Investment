<?php
include('includes/config.php');

$message = '';
$message_type = '';
$valid_token = false;
$token = $_GET['token'] ?? '';

// Verify token
if (!empty($token)) {
    $stmt = $conn->prepare("SELECT pr.*, u.email, u.name FROM password_resets pr 
                            JOIN users u ON pr.user_id = u.id 
                            WHERE pr.token = ? AND pr.expiry > NOW() AND pr.used = 0");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $valid_token = true;
        $reset_data = $result->fetch_assoc();
    } else {
        $message = 'This password reset link is invalid or has expired. Please request a new one.';
        $message_type = 'error';
    }
    $stmt->close();
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($password) || empty($confirm_password)) {
        $message = 'Please fill in all fields.';
        $message_type = 'error';
    } elseif (strlen($password) < 6) {
        $message = 'Password must be at least 6 characters long.';
        $message_type = 'error';
    } elseif ($password !== $confirm_password) {
        $message = 'Passwords do not match.';
        $message_type = 'error';
    } else {
        // Update password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $reset_data['user_id']);
        
        if ($stmt->execute()) {
            // Mark token as used
            $conn->query("UPDATE password_resets SET used = 1 WHERE token = '$token'");
            
            $message = 'Your password has been reset successfully! You can now login with your new password.';
            $message_type = 'success';
            $valid_token = false; // Hide the form
        } else {
            $message = 'An error occurred. Please try again.';
            $message_type = 'error';
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
    <meta name="description" content="Reset your JD Realty account password.">
    <title>Reset Password - JD Realty & Investment</title>
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

        .reset-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            max-width: 450px;
            width: 100%;
        }

        .reset-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }

        .reset-header .icon {
            font-size: 60px;
            margin-bottom: 15px;
        }

        .reset-header h1 {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .reset-header p {
            opacity: 0.9;
            font-size: 14px;
        }

        .reset-form {
            padding: 40px 30px;
        }

        .form-group {
            margin-bottom: 20px;
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

        .password-strength {
            margin-top: 8px;
            font-size: 12px;
        }

        .strength-bar {
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            margin-top: 5px;
            overflow: hidden;
        }

        .strength-bar-fill {
            height: 100%;
            width: 0;
            transition: all 0.3s ease;
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
            margin-top: 10px;
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

        .user-info {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .user-info p {
            color: #6b7280;
            font-size: 14px;
        }

        .user-info strong {
            color: #1f2937;
        }

        .password-requirements {
            background: #fef3c7;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #92400e;
        }

        .password-requirements ul {
            margin: 8px 0 0 20px;
        }

        .password-requirements li {
            margin: 3px 0;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <div class="icon">🔑</div>
            <h1>Reset Password</h1>
            <p>Create a new secure password</p>
        </div>
        
        <div class="reset-form">
            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($valid_token): ?>
                <div class="user-info">
                    <p>Resetting password for: <strong><?php echo htmlspecialchars($reset_data['email']); ?></strong></p>
                </div>
                
                <div class="password-requirements">
                    <strong>⚠️ Password Requirements:</strong>
                    <ul>
                        <li>At least 6 characters long</li>
                        <li>Use a mix of letters, numbers & symbols</li>
                    </ul>
                </div>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="password">🔒 New Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter new password" required minlength="6" onkeyup="checkStrength(this.value)">
                        <div class="password-strength">
                            <span id="strengthText">Password strength</span>
                            <div class="strength-bar">
                                <div class="strength-bar-fill" id="strengthBar"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">🔒 Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required minlength="6">
                    </div>
                    
                    <button type="submit" class="submit-btn">Reset Password</button>
                </form>
            <?php elseif ($message_type !== 'success'): ?>
                <div class="back-link" style="border: none; padding: 0; margin: 0;">
                    <a href="forgot-password.php">← Request New Reset Link</a>
                </div>
            <?php endif; ?>
            
            <div class="back-link">
                <a href="login.php">← Back to Login</a>
            </div>
        </div>
    </div>
    
    <script>
        function checkStrength(password) {
            const bar = document.getElementById('strengthBar');
            const text = document.getElementById('strengthText');
            let strength = 0;
            
            if (password.length >= 6) strength += 25;
            if (password.length >= 8) strength += 25;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 25;
            if (/\d/.test(password)) strength += 15;
            if (/[^a-zA-Z0-9]/.test(password)) strength += 10;
            
            bar.style.width = strength + '%';
            
            if (strength < 30) {
                bar.style.background = '#ef4444';
                text.textContent = 'Weak password';
                text.style.color = '#ef4444';
            } else if (strength < 60) {
                bar.style.background = '#f59e0b';
                text.textContent = 'Medium password';
                text.style.color = '#f59e0b';
            } else if (strength < 80) {
                bar.style.background = '#10b981';
                text.textContent = 'Strong password';
                text.style.color = '#10b981';
            } else {
                bar.style.background = '#059669';
                text.textContent = 'Very strong password';
                text.style.color = '#059669';
            }
        }
    </script>
</body>
</html>
