<?php
include('includes/config.php');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = $conn->real_escape_string($_POST['phone']);

    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password) || empty($phone)) {
        $error = "All fields are required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long!";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $error = "Phone number must be 10 digits!";
    } else {
        // Check if email already exists
        $check_email = "SELECT id FROM users WHERE email='$email'";
        $result = $conn->query($check_email);

        if ($result->num_rows > 0) {
            $error = "Email already registered!";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // Insert user
            $sql = "INSERT INTO users (name, email, password, phone, role) 
                    VALUES ('$name', '$email', '$hashed_password', '$phone', 'user')";

            if ($conn->query($sql) === TRUE) {
                $success = "Registration successful! Redirecting to login...";
                header("refresh:2; url=login.php");
            } else {
                $error = "Registration failed: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="Create a free account on JD Realty & Investment. Register now to list properties and browse real estate listings online.">
    <meta name="keywords" content="sign up, register, create account, real estate account, join JD Realty">
    <meta name="robots" content="noindex, follow">
    <title>Sign Up - Create Your JD Realty Account</title>
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

        .signup-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 50px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 500px;
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

        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="tel"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus,
        input[type="tel"]:focus {
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

        .success-message {
            background-color: #d1fae5;
            color: #065f46;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #06b6d4;
        }

        .password-requirements {
            background-color: #f3f4f6;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #6b7280;
        }

        .password-requirements ul {
            margin-left: 20px;
            margin-top: 8px;
        }

        .password-requirements li {
            margin: 4px 0;
        }

        .signup-btn {
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

        .signup-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .signup-btn:active {
            transform: translateY(0);
        }

        .login-link {
            text-align: center;
            margin-top: 25px;
            color: #6b7280;
            font-size: 14px;
        }

        .login-link a {
            color: #4f46e5;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .login-link a:hover {
            color: #6366f1;
        }
    </style>
</head>

<body>
    <div class="signup-container">
        <div class="logo-section" style="display: flex; align-items: center; justify-content: center; gap: 12px;">
            <img src="images/jd-logo.svg?v=20251203" alt="JD Realty & Investment" style="width: 60px; height: 60px;">
            <span style="font-size: 22px; font-weight: bold; color: #d4a84b;">JD Realty Investment</span>
        </div>

        <h1>Create Account</h1>
        <p class="subtitle">Join us to explore amazing properties</p>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                <strong>Error:</strong> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="success-message">
                <strong>Success!</strong> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" onsubmit="return validateForm()">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" required placeholder="Enter your full name"
                    value="<?php echo isset($_POST['name']) ? $_POST['name'] : ''; ?>">
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required placeholder="Enter your email"
                    value="<?php echo isset($_POST['email']) ? $_POST['email'] : ''; ?>">
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" required placeholder="Enter 10-digit phone number"
                    value="<?php echo isset($_POST['phone']) ? $_POST['phone'] : ''; ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Enter a strong password">
                <div class="password-requirements">
                    <strong>Password Requirements:</strong>
                    <ul>
                        <li>At least 6 characters</li>
                        <li>Use a mix of letters and numbers</li>
                    </ul>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required
                    placeholder="Re-enter your password">
            </div>

            <button type="submit" class="signup-btn">Create Account</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="login.php">Sign In</a>
        </div>
    </div>

    <script>
        function validateForm() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const phone = document.getElementById('phone').value;

            if (password.length < 6) {
                alert('Password must be at least 6 characters long!');
                return false;
            }

            if (password !== confirmPassword) {
                alert('Passwords do not match!');
                return false;
            }

            if (!/^[0-9]{10}$/.test(phone)) {
                alert('Phone number must be exactly 10 digits!');
                return false;
            }

            return true;
        }
    </script>
    
</body>

</html>
