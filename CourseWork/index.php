<?php
session_start();
$loggedIn = isset($_SESSION['Uusername']);  // Check if user is logged in
$profilePic = "unknown.svg"; // Default profile picture

// Database connection
$con = mysqli_connect("localhost:3306", "chiyaforsure", "556143");
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}
mysqli_select_db($con, "auctionxpress");

// Update auctionStatus to 'Payment Pending' if deadline has passed and it's still active
$updateQuery = "UPDATE auctions 
                SET auctionStatus = 'Payment Pending' 
                WHERE auctionDeadline < NOW() AND auctionStatus = 'Active'";
mysqli_query($con, $updateQuery);

// Fetch only approved and active auctions that are not closed
$query = "SELECT a.AuctionID, a.Title, a.Description, a.StartedBidAmount, MIN(i.Image) as Image 
          FROM auctions a 
          JOIN auctionimage i ON a.AuctionID = i.AuctionID 
          WHERE a.auctionApproval = 'approved' 
          AND a.auctionStatus != 'closed' 
          GROUP BY a.AuctionID";

$result = mysqli_query($con, $query);
$auctions = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $auctions[] = $row;
    }
}

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

mysqli_close($con);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AuctionXpress</title>
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

    <div class="banner">
    <div class="banner-images">
        <div class="banner-item">
            <img class="img-banner" src="nb1.jpg" alt="Banner 1">
        </div>
        <div class="banner-item">
            <img class="img-banner" src="nb3.jpg" alt="Banner 2">
        </div>
        <div class="banner-item">
            <img class="img-banner" src="nb2.jpg" alt="Banner 3">
        </div>
    </div>
    <div class="dots-container">
        <div class="dot active"></div>
        <div class="dot"></div>
        <div class="dot"></div>
    </div>
</div>
<h1 style="padding-left: 35px;margin-bottom: 20px;">New Auctions for You</h1>
    <div class="container" style="margin-top: 0; padding-top: 0;">
    <div class="row">
        <?php if (count($auctions) > 0): ?>
            <?php foreach ($auctions as $auction): ?>
                <div class="col-md-3">
                    <div class="card" onclick="navigateToItem(<?php echo $auction['AuctionID']; ?>)">
                        <img class="card-img-top" src="data:image/jpeg;base64,<?php echo base64_encode($auction['Image']); ?>" alt="<?php echo htmlspecialchars($auction['Title']); ?>">
                        <div class="card-body">
                            <p class="card-title"><?php echo htmlspecialchars($auction['Title']); ?></p>
                            <p class="card-description"><?php echo htmlspecialchars($auction['Description']); ?></p>
                            <p class="card-price">Starting at: Rs.<?php echo number_format($auction['StartedBidAmount']); ?>/=</p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-auctions">
                <p>No active auctions available at the moment. Please check back later!</p>
            </div>
        <?php endif; ?>
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
    <script>
document.addEventListener('DOMContentLoaded', function() {
    const banner = document.querySelector('.banner-images');
    const dots = document.querySelectorAll('.dot');
    let currentSlide = 0;
    let slideInterval;

    // Function to update the active slide
    function goToSlide(slideIndex) {
        banner.style.transform = `translateX(-${slideIndex * 100}%)`; // Move banner correctly
        banner.style.transition = "transform 0.5s ease-in-out"; // Smooth transition
        
        // Update active dot
        dots.forEach((dot, index) => {
            dot.classList.toggle('active', index === slideIndex);
        });

        currentSlide = slideIndex;
    }

    // Dot click event
    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            clearInterval(slideInterval); // Stop auto-sliding
            goToSlide(index);
            startSlideShow();
        });
    });

    // Auto slideshow function
    function startSlideShow() {
        slideInterval = setInterval(() => {
            currentSlide = (currentSlide + 1) % dots.length;
            goToSlide(currentSlide);
        }, 5000); // Change slide every 5 seconds
    }

    // Start the slideshow
    startSlideShow();
});
</script>
</body>
</html>