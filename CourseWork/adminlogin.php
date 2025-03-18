<?php
session_start();
ob_start();

// Database configuration
$con = mysqli_connect("localhost:3306", "chiyaforsure", "556143");
mysqli_select_db($con, "auctionxpress");

if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

// Login processing
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['username']) && isset($_POST['password'])) {
    $username = mysqli_real_escape_string($con, $_POST["username"]);
    $password = mysqli_real_escape_string($con, $_POST["password"]); // Sanitize password input
    
    // Query to get admin information using prepared statement
    $stmt = mysqli_prepare($con, "SELECT admin_id, admin_name, password, username FROM admin WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        $admin = mysqli_fetch_assoc($result);
        
        // Verify the password directly
        if ($password === $admin['password']) {
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['admin_name'] = $admin['admin_name'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['is_admin'] = true;
            
            // Set session timeout
            $_SESSION['last_activity'] = time();
            
            header("Location: adminview.php");
            exit();
        } else {
            $error_message = "Invalid credentials";
        }
    } else {
        $error_message = "Invalid credentials";
    }
    mysqli_stmt_close($stmt);
}

mysqli_close($con);
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="admin.png" type="image/x-icon">
    <title>Admin Login</title>
    <style>
        body {
            background-color: #0a192f;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            font-family: 'Arial', sans-serif;
        }
        
        .login-container {
            background-color: #fff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .form-group label {
            font-weight: bold;
            color: #2C3E50;
        }
        
        .input-field {
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .button {
            background-color: #FF6B35;
            color: white;
            padding: 0.75rem;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .button:hover {
            background-color: #2980B9;
        }
        
        .error-message {
            color: #E74C3C;
            text-align: center;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Admin Login</h1>
        </div>
        
        <form class="login-form" id="adminLoginForm" method="post" action="">
            <?php if (isset($error_message)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="input-field" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="input-field" required>
            </div>
            
            <button type="submit" class="button">Login</button>
        </form>
    </div>
</body>
</html>