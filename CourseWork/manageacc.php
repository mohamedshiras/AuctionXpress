<?php
session_start();
$con = mysqli_connect("localhost:3306", "chiyaforsure", "556143", "auctionxpress");

if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

// Default profile picture
$profilePic = "unknown.svg";

// Check if the user is logged in
if (!isset($_SESSION['Uusername'])) {
    header("Location: cwlogin.php");
    exit();
}

// Get username from session
$username = $_SESSION['Uusername'];

// Fetch user details
$query = "SELECT userFullName, userEmail, profilePicture FROM users WHERE userUsername = ?";
$stmt = $con->prepare($query);
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($fullName, $email, $profileImageData);
$stmt->fetch();
$stmt->close();

// If user has a profile picture, use it
if ($profileImageData) {
    $profilePic = "data:image/jpeg;base64," . base64_encode($profileImageData);
}

// Fetch auctions with Payment Pending status where current user is the highest bidder
$pendingPaymentsQuery = "
    SELECT a.* 
    FROM auctions a
    JOIN bidtable b ON a.AuctionID = b.AuctionID
    WHERE a.AuctionStatus = 'Payment Pending' 
    AND b.bidAmount = (
        SELECT MAX(bidAmount) 
        FROM bidtable 
        WHERE AuctionID = a.AuctionID
    )
    AND b.userID = (
        SELECT userID 
        FROM users 
        WHERE userUsername = ?
    )
    ORDER BY a.auctionDeadline ASC";

$stmt = $con->prepare($pendingPaymentsQuery);
$stmt->bind_param("s", $username);
$stmt->execute();
$pendingPaymentsResult = $stmt->get_result();
$pendingPayments = [];

if ($pendingPaymentsResult) {
    while ($row = $pendingPaymentsResult->fetch_assoc()) {
        $pendingPayments[] = $row;
    }
}
$stmt->close();

// Handle profile picture delete
if (isset($_POST['deleteProfilePic'])) {
    $updateQuery = "UPDATE users SET profilePicture = NULL WHERE userUsername = ?";
    $stmt = $con->prepare($updateQuery);
    $stmt->bind_param("s", $username);
    
    if ($stmt->execute()) {
        $profilePic = "unknown.svg"; // Reset to default
        echo "<script>alert('Profile picture removed successfully.'); window.location='manageacc.php';</script>";
    } else {
        echo "<script>alert('Error removing profile picture.');</script>";
    }
    $stmt->close();
}

// Handle profile picture upload submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['saveProfilePic'])) {
    // Only handle profile picture upload here
    if (isset($_FILES['profilePic']) && $_FILES['profilePic']['size'] > 0) {
        $imageData = file_get_contents($_FILES['profilePic']['tmp_name']);
        
        $updateQuery = "UPDATE users SET profilePicture = ? WHERE userUsername = ?";
        $stmt = $con->prepare($updateQuery);
        $stmt->bind_param("ss", $imageData, $username);
        
        if ($stmt->execute()) {
            echo "<script>alert('Profile picture updated successfully.'); window.location='manageacc.php';</script>";
        } else {
            echo "<script>alert('Error updating profile picture.');</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('No image selected.');</script>";
    }
}

// Handle account information update submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['updateAccount'])) {
    $newFullName = $_POST['fullName'];
    $newEmail = $_POST['email'];
    $newPassword = $_POST['password'];
    
    // Update account information without touching the profile picture
    if (!empty($newPassword)) {
        $updateQuery = "UPDATE users SET userFullName = ?, userEmail = ?, userPassword = ? WHERE userUsername = ?";
        $stmt = $con->prepare($updateQuery);
        $stmt->bind_param("ssss", $newFullName, $newEmail, $newPassword, $username);
    } else {
        $updateQuery = "UPDATE users SET userFullName = ?, userEmail = ? WHERE userUsername = ?";
        $stmt = $con->prepare($updateQuery);
        $stmt->bind_param("sss", $newFullName, $newEmail, $username);
    }

    if ($stmt->execute()) {
        echo "<script>alert('Account updated successfully.'); window.location='manageacc.php';</script>";
    } else {
        echo "<script>alert('Error updating account.');</script>";
    }
    $stmt->close();
}

$con->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="auction.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Manage Account - AuctionXpress</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Additional styles for the form */
        .form-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 20px;
            width: 90%;
            max-width: 1200px;
            margin: 50px auto;
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
        }

        .profile-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
        }

        .profile-picture-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin-bottom: 15px;
            border-radius: 50%;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .profile-picture {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-picture-controls {
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: 100%;
            max-width: 300px;
        }

        .file-upload {
            position: relative;
            overflow: hidden;
            margin: 10px 0;
            text-align: center;
        }

        .file-upload input[type="file"] {
            position: absolute;
            font-size: 100px;
            opacity: 0;
            right: 0;
            top: 0;
            cursor: pointer;
        }

        .upload-btn, .delete-btn{
            display: inline-block;
            background-color: #1E3E62;
            color: white;
            font-size: 12px;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            width: 100%;
            text-align: center;
        }

        .delete-btn {
            background-color: #dc3545;
        }

        .delete-btn:hover {
            background-color: #c82333;
        }

        .upload-btn:hover {
            background-color: #15304d;
        }

        .form-label {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            border-color: #1E3E62;
            outline: none;
            box-shadow: 0 0 5px rgba(30, 62, 98, 0.5);
        }

        .pending-payments-section {
    width: 90%;
    max-width: 1200px;
    margin: 30px auto;
    background-color: #fff;
    border-radius: 10px;
    padding: 20px;
}

.payment-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

.payment-table th, 
.payment-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #e0e0e0;
}

.payment-table th {
    background-color: #f8f9fa;
    font-weight: bold;
}

.payment-table tr:hover {
    background-color: #f1f1f1;
}

.payment-btn {
    padding: 8px 12px;
    font-size: 14px;
    background-color: #28a745;
}

.payment-btn:hover {
    background-color: #218838;
}

.table-responsive {
    overflow-x: auto;
}
    </style>
</head>
<body>

<nav class="navbar">
    <div class="nav-wrapper">
        <a class="navbar-brand" href="index.php">
            <img src="auctionx.png" alt="AuctionXpress Logo" class="nav-logo">
        </a>
        <ul class="navbar-nav">
            <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
            <li class="nav-item"><a class="nav-link" href="aboutus.php">About us</a></li>
            <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
            <li class="nav-item"><a class="nav-link" href="#">Catagory</a></li>
        </ul>
        <div class="nav-buttons">
            <button class="button" id="postAuctionbutton">Post Auction</button>
            <button class="button" id="logoutbutton">Log Out</button>
            <div class="profile-wrapper">
                 <img id="pp" class="profile-pic" src="<?php echo htmlspecialchars($profilePic); ?>" alt="Profile Picture" onclick="redirectToManageAccount()">
            </div>
        </div>
    </div>
</nav>

<div class="container">
    <h2 style="margin-left: 210px;">Manage Account</h2>
    <div class="form-container">
        <!-- Profile Picture Section -->
        <div class="profile-section">
            <div class="profile-picture-container">
                <img class="profile-picture" src="<?php echo htmlspecialchars($profilePic); ?>" alt="Profile Picture">
            </div>
            <div class="profile-picture-controls">
                <form method="post" action="manageacc.php" enctype="multipart/form-data">
                    <div class="file-upload">
                        <label for="profilePicInput" class="upload-btn">
                            <i class="fas fa-upload"></i> Upload New
                        </label>
                        <input type="file" id="profilePicInput" name="profilePic" accept="image/*" onchange="displayFileName()">
                    </div>
                    <div id="fileName" style="margin: 5px 0; font-size: 14px; text-align: center;"></div>
                    <button type="submit" name="saveProfilePic" class="upload-btn">
                        <i class="fas fa-save"></i> Save Profile Picture
                    </button>
                </form>
                
                <form method="post" action="manageacc.php" onsubmit="return confirm('Are you sure you want to delete your profile picture?');">
                    <input type="hidden" name="deleteProfilePic" value="1">
                    <button type="submit" class="delete-btn">
                        <i class="fas fa-trash"></i> Delete Profile Picture
                    </button>
                </form>
            </div>
        </div>

        <!-- Account Information Form -->
        <form method="post" action="manageacc.php" style="padding-top: 40px;">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" readonly>
            </div>
            <div class="mb-3">
                <label for="fullName" class="form-label">Full Name</label>
                <input type="text" class="form-control" id="fullName" name="fullName" value="<?php echo htmlspecialchars($fullName); ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" readonly>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">New Password</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Enter new password if you wish to change">
            </div>
            <center><button type="submit" name="updateAccount" class="button">Update Account</button></center>
        </form>
    </div>
</div>

<div class="pending-payments-section">
    <h3>Pending Payments</h3>
    <?php if (count($pendingPayments) > 0): ?>
        <div class="table-responsive">
            <table class="payment-table">
                <thead>
                    <tr>
                        <th>Auction ID</th>
                        <th>Title</th>
                        <th>Initial Bid</th>
                        <th>Category</th>
                        <th>Posted Date</th>
                        <th>Deadline</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingPayments as $payment): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($payment['AuctionID']); ?></td>
                            <td><?php echo htmlspecialchars($payment['Title']); ?></td>
                            <td>Rs.<?php echo number_format($payment['StartedBidAmount'], 0); ?></td>
                            <td><?php echo htmlspecialchars($payment['Category']); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($payment['AuctionPostedTime'])); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($payment['auctionDeadline'])); ?></td>
                            <td>
                                <button class="button payment-btn" onclick="window.location.href='https://buy.stripe.com/test_00g9C54x8dzAakM4gg'">Process Payment</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p>No pending payments found.</p>
    <?php endif; ?>
</div>

<div class="footer">
    <div class="footer-columns">
        <div class="footer-column">
            <h3>Features</h3>
            <ul>
                <li><a href="#">How To Bid</a></li>
                <li><a href="#">Auction Rules</a></li>
                <li><a href="#">Popular Categories</a></li>
                <li><a href="#">Seller Guidelines</a></li>
            </ul>
        </div>
        <div class="footer-column">
            <h3>Who we are</h3>
            <ul>
                <li><a href="#">Our Story</a></li>
                <li><a href="#">Meet The Team</a></li>
                <li><a href="#">Careers</a></li>
                <li><a href="#">Media Center</a></li>
            </ul>
        </div>
        <div class="footer-column">
            <h3>Need help?</h3>
            <ul>
                <li><a href="contact.php">Contact Us</a></li>
                <li><a href="#">FAQs</a></li>
                <li><a href="#">Terms & Conditions</a></li>
                <li><a href="#">Privacy Policy</a></li>
            </ul>
        </div>
        <div class="footer-column">
            <h3>Contact us</h3>
            <div class="contact-info">
                <p><i class="fas fa-phone"></i> +94 (77) 500-6727</p>
                <p><i class="fas fa-envelope"></i> auctionxpress.official@gmail.com</p>
            </div>
            <div class="get-in-touch">
                <h3>Get in touch with us</h3>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#"><i class="fab fa-whatsapp"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="tagline">Your Trusted Online Auction Marketplace</div>
        <div class="copyright">
            © 2025 AuctionXpress Ltd. - All rights reserved.
        </div>
        <div class="footer-links">
            <a href="#">Site Map</a>
            <a href="#">Privacy Policy</a>
            <a href="#">Cookie Policy</a>
        </div>
    </div>
</div>

<script>
function displayFileName() {
    var input = document.getElementById('profilePicInput');
    var fileNameDisplay = document.getElementById('fileName');
    if (input.files.length > 0) {
        fileNameDisplay.textContent = input.files[0].name;
    } else {
        fileNameDisplay.textContent = '';
    }
}

function redirectToManageAccount() {
    window.location.href = 'manageacc.php';
}
</script>

<script src="auction.js"></script>
</body>
</html>