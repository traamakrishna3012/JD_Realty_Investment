<?php
include('../includes/config.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    
    // Query to check admin user
    $sql = "SELECT * FROM users WHERE email='$email' AND role='admin'";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $row['password'])) {
            $_SESSION['admin_id'] = $row['id'];
            $_SESSION['admin_name'] = $row['name'];
            $_SESSION['admin_email'] = $row['email'];
            $_SESSION['admin_role'] = 'admin';
            
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "Admin account not found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - JD Realty & Investment</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 15px 60px rgba(0, 0, 0, 0.3);
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
        
        .admin-badge {
            display: inline-block;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 8px 25px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
            width: auto;
            margin-bottom: 20px;
        }
        
        .badge-wrapper {
            text-align: center;
            margin-bottom: 0;
        }
        
        .logo-section {
            text-align: center;
            margin-bottom: 30px;
            background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
            padding: 25px 20px;
            margin-left: -40px;
            margin-right: -40px;
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
            color: #f5576c;
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
            border-color: #f5576c;
            box-shadow: 0 0 0 3px rgba(245, 87, 108, 0.1);
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
            color: #f5576c;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .remember-forgot a:hover {
            color: #e63d56;
        }
        
        .login-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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
            box-shadow: 0 5px 20px rgba(245, 87, 108, 0.4);
        }
        
        .login-btn:active {
            transform: translateY(0);
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
            color: #6b7280;
            font-size: 14px;
        }
        
        .back-link a {
            color: #f5576c;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        
        .back-link a:hover {
            color: #e63d56;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="badge-wrapper">
            <div class="admin-badge">🔐 ADMIN PANEL</div>
        </div>
        
        <div class="logo-section" style="display: flex; align-items: center; justify-content: center; gap: 12px;">
            <img src="../images/jd-logo.svg?v=20251203" alt="JD Realty & Investment" style="width: 60px; height: 60px;">
            <span style="font-size: 22px; font-weight: bold; color: #d4a84b;">JD Realty Investment</span>
        </div>
        
        <h1>Admin Login</h1>
        <p class="subtitle">Access the administration panel</p>
        
        <?php if (!empty($error)): ?>
            <div class="error-message">
                <strong>Error:</strong> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Admin Email</label>
                <input type="email" id="email" name="email" required placeholder="Enter admin email">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Enter password">
            </div>
            
            <div class="remember-forgot">
                <label>
                    <input type="checkbox" name="remember"> Remember me
                </label>
                <a href="../forgot-password.php">Forgot Password?</a>
            </div>
            
            <button type="submit" class="login-btn">Sign In to Admin Panel</button>
        </form>
        
        <div class="back-link">
            <a href="../login.php">← Back to User Login</a>
        </div>
    </div>
</body>
</html>
