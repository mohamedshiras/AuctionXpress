<?php
session_start();
ob_start();
$con = mysqli_connect("localhost:3306", "chiyaforsure", "556143");
mysqli_select_db($con, "auctionxpress");
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}
$error_message = ""; // Initialize error message variable

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['username']) && isset($_POST['password'])) {
    $Uusername = $_POST["username"];
    $password = $_POST['password'];
    
    // Use prepared statement to prevent SQL injection
    $sql = "SELECT userPassword, accountApproval FROM users WHERE userUsername = ?";
    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "s", $Uusername);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        // Check account status
        switch($user['accountApproval']) {
            case 'pending approval':
                $error_message = "Your account is pending approval. Please wait for administrator approval.";
                break;
                
            case 'blocked':
                $error_message = "Your account has been blocked. Please contact the administrator.";
                break;
                
            case 'active':
                // If account is active, check password
                if ($password == $user['userPassword']) {
                    $_SESSION['Uusername'] = $Uusername;
                    header("Location: index.php");
                    exit();
                } else {
                    $error_message = "Invalid username or password!";
                }
                break;
                
            default:
                $error_message = "Invalid account status. Please contact the administrator.";
        }
    } else {
        $error_message = "Invalid username or password!";
    }
    
    mysqli_stmt_close($stmt);
}
mysqli_close($con);
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Login - AuctionXpress</title>
    <link rel="shortcut icon" href="auction.png" type="image/x-icon">
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background: url('loginbg.jpg') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0; 
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <img src="auctionx.png" height="150">
        <form class="login-form" id="loginForm" action="#" method="post">
            <div class="row"><label>Username</label></div>
            <div class="row"><input type="text" class="input-field" name="username" placeholder="Username" required></div>
            <div class="row"><label>Password</label></div>
            <div class="row" style="margin-bottom: 10px;"><input type="password" class="input-field" name="password" placeholder="Password" required><br><br></div>
            <?php if (!empty($error_message)): ?>
                <div class="error-label"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <button type="submit" class="button">Login</button><br>
            <a href="createacc.php" class="a-href">Create an Account</a>
        </form>
        <div class="spinner"></div>
    </div>
<script src="auction.js"></script>
</body>
</html>