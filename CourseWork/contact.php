<?php
session_start();
$loggedIn = isset($_SESSION['Uusername']);  // Check if user is logged in
$profilePic = "unknown.svg"; // Default profile picture

$con = mysqli_connect("localhost:3306", "chiyaforsure", "556143");
if (!$con) {
   die("Connection failed: " . mysqli_connect_error());
}
mysqli_select_db($con, "auctionxpress");

if ($loggedIn) {
    $username = $_SESSION['Uusername'];
    $query = "SELECT profilePicture FROM users WHERE userUsername = ?";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $profileImageData);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if ($profileImageData) {
        $profilePic = "data:image/jpeg;base64," . base64_encode($profileImageData);
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contact Us - AuctionXpress</title>
    <link rel="shortcut icon" href="auction.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
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
                <?php if (!$loggedIn): ?>
                    <button class="button" id="loginbutton">Log In</button>
                    <button class="button" id="signupbutton">Sign Up</button>
                <?php else: ?>
                    <button class="button" id="postAuctionbutton">Post Auction</button>
                <button class="button" id="logoutbutton">Log Out</button>
                <div class="profile-wrapper">
                     <img id="pp" class="profile-pic" src="<?php echo $profilePic; ?>" alt="Profile Picture" onclick="redirectToManageAccount()">
                </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

<div class="container" style="align-content: center; padding-left: 200px;">
    <div class="row">
        <div class="col">
            <pre class="pre">
AuctionXpress Pvt Ldt
Customer Staffs - M Shiras
                            <a href="mailto:mchiya1003@gmail.com">mchiya1003@gmail.com</a>
                            M Zafri
                            <a href="mailto:zafri@gmail.com">zafri@gmail.com</a>
                            KDH Vinudi
                            <a href="mailto:vinudi@gmail.com">vinudi@gmail.com</a>
                            F Aasifa
                            <a href="mailto:aasifa@gmail.com">aasifa@gmail.com</a>
Address - NIBM Galle 
New Matara Road, Galle, Sri Lanka<br>
Tel - +94-77-500-6727
            </pre>
        </div>

        <div class="col-5" style="margin-left: 80px;">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3854.7389894813696!2d80.22312358802002!3d6.036923532622406!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3ae173b12f4deb15%3A0x31ccbc33eb91d2ac!2sNIBM%20Galle%20Regional%20Centre!5e0!3m2!1sen!2slk!4v1730274069330!5m2!1sen!2slk" width="700" height="300" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
    </div>
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
<script src="auction.js"></script>
</body>
</html>