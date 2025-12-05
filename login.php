<?php
include('includes/config.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    // Query to check user
    $sql = "SELECT * FROM users WHERE email='$email' AND role='user'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Verify password
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_name'] = $row['name'];
            $_SESSION['user_email'] = $row['email'];
            $_SESSION['user_role'] = 'user';

            header("Location: index.php");
            exit();
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "User not found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="Login to JD Realty & Investment. Access your account to manage properties, send inquiries, and more.">
    <meta name="keywords" content="login, account, real estate account">
    <meta name="robots" content="noindex, follow">
    <title>Login - JD Realty & Investment Account</title>
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
            border-radius: 10px;
            box-shadow: 0 10px 50px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 450px;
            padding: 50px 40px;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo-section {
            text-align: center;
            margin-bottom: 30px;
            background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
            padding: 25px 20px;
            border-radius: 10px;
            margin: -50px -40px 30px -40px;
            border-radius: 10px 10px 0 0;
        }

        .logo-section img {
            width: 150px;
            height: auto;
            max-height: 100px;
        }

        .logo {
            font-size: 28px;
            color: #d97706;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .logo span {
            color: #4f46e5;
        }

        .tagline {
            color: #6b7280;
            font-size: 13px;
            font-weight: 500;
        }

        h1 {
            font-size: 24px;
            color: #1f2937;
            margin-bottom: 10px;
            text-align: center;
        }

        .subtitle {
            text-align: center;
            color: #9ca3af;
            font-size: 14px;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            color: #374151;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .error-message {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #dc2626;
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            font-size: 13px;
        }

        .remember-forgot label {
            margin-bottom: 0;
            font-weight: 400;
            color: #6b7280;
        }

        .remember-forgot a {
            color: #4f46e5;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .remember-forgot a:hover {
            color: #6366f1;
        }

        .login-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .signup-link {
            text-align: center;
            margin-top: 25px;
            color: #6b7280;
            font-size: 14px;
        }

        .signup-link a {
            color: #4f46e5;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .signup-link a:hover {
            color: #6366f1;
        }

        .admin-link {
            text-align: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
        }

        .admin-link a {
            color: #d97706;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .admin-link a:hover {
            color: #b45309;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="logo-section" style="display: flex; align-items: center; justify-content: center; gap: 12px;">
            <img src="images/jd-logo.svg?v=20251203" alt="JD Realty & Investment" style="width: 60px; height: 60px;">
            <span style="font-size: 22px; font-weight: bold; color: #d4a84b;">JD Realty Investment</span>
        </div>

        <h1>Welcome Back</h1>
        <p class="subtitle">Sign in to your account</p>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                <strong>Error:</strong> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required placeholder="Enter your email">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Enter your password">
            </div>

            <div class="remember-forgot">
                <label>
                    <input type="checkbox" name="remember"> Remember me
                </label>
                <a href="forgot-password.php">Forgot Password?</a>
            </div>

            <button type="submit" class="login-btn">Sign In</button>
        </form>
        
        <div style="text-align: center; margin: 20px 0;">
            <span style="color: #9ca3af; font-size: 13px;">─────── OR ───────</span>
        </div>
        
        <a href="login-email-otp.php" style="display: block; width: 100%; padding: 12px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; text-align: center; text-decoration: none; transition: all 0.3s; margin-bottom: 10px;">
            📧 Login with Email OTP
        </a>
        
        <!-- Mobile OTP Login - Phase 2 Implementation
        <a href="login-otp.php" style="display: block; width: 100%; padding: 12px; background: #f3f4f6; color: #6b7280; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; text-align: center; text-decoration: none; transition: all 0.3s;">
            📱 Login with Mobile OTP
        </a>
        -->

        <div class="signup-link">
            Don't have an account? <a href="signup.php">Create one</a>
        </div>

        <div class="admin-link">
            <a href="admin/login.php">Admin Login →</a>
        </div>
    </div>

</body>

</html>
