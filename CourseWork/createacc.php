<?php
session_start();
ob_start();

$con = mysqli_connect("localhost:3306", "chiyaforsure", "556143");
mysqli_select_db($con, "auctionxpress");

if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

$error_message = ""; // Initialize error message variable

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullName = $_POST['fullname'];
    $address = $_POST['address'];
    $number = $_POST['number'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm-password'];

    // Check if username already exists
    $checkQuery = "SELECT * FROM users WHERE userUsername = '$username'";
    $result = mysqli_query($con, $checkQuery);

    if (mysqli_num_rows($result) > 0) {
        $error_message = "Username already exists. Please choose a different one.";
    } elseif ($password !== $confirmPassword) {
        $error_message = "Password and Confirm Password do not match.";
    } else {
        // Insert the new user
        $sql = "INSERT INTO users (userFullName, userAddress, userContact, userEmail, userUsername, userPassword) 
                VALUES ('$fullName', '$address', '$number', '$email', '$username', '$password')";

        if (mysqli_query($con, $sql)) {
            $_SESSION['username'] = $username;
            header("Location: index.php");
            exit;
        } else {
            $error_message = "Error creating account: " . mysqli_error($con);
        }
    }
}

mysqli_close($con);
ob_end_flush();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create an Account - AuctionXpress</title>
    <link rel="shortcut icon" href="auction.png" type="image/x-icon">
    <style>
        body {
            background: url('loginbg.jpg') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Poppins', sans-serif;        }
        .container {
            background-color: white;
            margin: 70px;
            padding: 20px 50px;
            border-radius: 20px;
            opacity: 90%;
            width: 1000px;
        }
        .container img {
            width: 350px;
            margin: 0 auto 20px;
            display: block;
        }
        input[type=text], input[type=password] {
            width: 95%;
            padding: 12px 20px;
            margin: 8px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }
        .button {
            justify-content: flex-end;
            font-size: 14px;
            font-weight: bold;
            background-color: #1E3E62;
            color: white;
            padding: 10px 15px;
            margin-bottom: 5px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .button:hover {
            background-color: orange;
            color: black;
            transform: translateY(-2px);
        }
        .container a {
            color: dodgerblue;
            font-size: 14px;
            text-align: center;
            display: block;
            margin: 10px;
        }
        .row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .col {
            flex: 100%;
        }
        .label {
            font-size: 14px;
        }
        .error-label {
            color: #ff0000;
            font-size: 14px;
            margin: 5px 0;
            display: block;
        }
        .error-text {
            color: #ff0000;
            font-size: 12px;
            margin-top: 2px;
            display: none;
        }
        .spinner {
            border: 4px solid rgba(255, 101, 0, 0.3);
            border-top: 4px solid #FF6500;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
            display: none;
        }
        @keyframes spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col">
        <img src="auctionx.png">
    </div>
    <div class="col">
        <h1>Sign Up</h1>
        <?php if (!empty($error_message)): ?>
            <div class="error-label"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <form id="signupform" action="#" method="post">
            <label class="label" for="fullname">Full Name</label><br>
            <input class="input" type="text" id="fullname" style="width: 97.5%;" name="fullname" placeholder="Your fullname" required>
            
            <div class="row">
                <div class="col">
                    <label class="label">Address</label>
                </div>
                <div class="col">
                    <label class="label">Contact Number</label>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <input class="input" type="text" id="address" name="address" placeholder="Your address" required>
                </div>
                <div class="col">
                    <input class="input" type="text" id="number" name="number" placeholder="7xxxxxxxx" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col">
                    <label class="label" for="username">Username</label>
                </div>
                <div class="col">
                    <label class="label" for="email">Email</label>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <input class="input" type="text" id="username" name="username" placeholder="Your username" required>
                </div>
                <div class="col">
                    <input class="input" type="text" id="email" name="email" placeholder="Your email" required>
                    <div id="emailError" class="error-text">Please enter a valid email address</div>
                </div>
            </div>
            
            <div class="row">
                <div class="col">
                    <label class="label" for="password">Password</label>
                </div>
                <div class="col">
                    <label class="label" for="confirm-password">Confirm Password</label>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <input class="input" type="password" id="password" name="password" placeholder="Your password" required>
                </div>
                <div class="col">
                    <input class="input" type="password" id="confirm-password" name="confirm-password" placeholder="Confirm password" required>
                    <div id="passwordError" class="error-text">Passwords do not match</div>
                </div>
            </div>
            
            <br><center><button class="button" id="createbutton" type="submit">Create Account</button></center>
            <a href="cwlogin.php">Already have an account? Sign in</a>
        </form>
        <div class="spinner"></div>
    </div>
</div>
</div>
<script src="auction.js"></script>
</body>
</html>