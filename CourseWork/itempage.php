<?php
session_start();
$loggedIn = isset($_SESSION['Uusername']);
$profilePic = "unknown.svg"; // Default profile picture

// Database connection
$con = mysqli_connect("localhost:3306", "chiyaforsure", "556143");
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}
mysqli_select_db($con, "auctionxpress");

// Get auction ID from URL
$auctionId = isset($_GET['auctionID']) ? (int)$_GET['auctionID'] : 0;

// Check if payment process has been initiated
$paymentInitiated = isset($_GET['payment_initiated']) && $_GET['payment_initiated'] == 'true';

// Update auction status to "Closed" and set AuctionOveredTime when payment is initiated
if ($auctionId > 0 && $paymentInitiated) {
    $currentTime = date('Y-m-d H:i:s');
    $updateQuery = "UPDATE auctions SET 
                    AuctionStatus = 'Closed', 
                    AuctionOveredTime = ? 
                    WHERE AuctionID = ?";
                    
    $stmt = mysqli_prepare($con, $updateQuery);
    mysqli_stmt_bind_param($stmt, "si", $currentTime, $auctionId);
    
    if (mysqli_stmt_execute($stmt)) {
        // Success
        $_SESSION['payment_success'] = true;
    } else {
        // Error
        $_SESSION['payment_error'] = mysqli_error($con);
    }
    mysqli_stmt_close($stmt);
}

// Fetch auction details
$query = "SELECT Title, Description, StartedBidAmount, Category, AuctionStatus, 
          AuctionPostedTime, FinalizedBidAmount, auctionDeadline 
          FROM auctions WHERE AuctionID = $auctionId";
$result = mysqli_query($con, $query);
$auction = mysqli_fetch_assoc($result);

// Fetch all auction images for the carousel
$imageQuery = "SELECT Image FROM auctionimage WHERE AuctionID = $auctionId";
$imageResult = mysqli_query($con, $imageQuery);
$images = [];
while ($imageRow = mysqli_fetch_assoc($imageResult)) {
    $images[] = $imageRow['Image'];
}

// Fetch bid history
$bidHistoryQuery = "SELECT b.bidAmount, b.bidDateAndTime, u.userUsername, u.userID 
                   FROM bidtable b 
                   JOIN users u ON b.userID = u.userID 
                   WHERE b.AuctionID = $auctionId 
                   ORDER BY b.bidAmount DESC, b.bidDateAndTime ASC";
$bidHistoryResult = mysqli_query($con, $bidHistoryQuery);

// Get highest bidder information directly from database
$highestBidder = null;
$currentUserId = null;
$isHighestBidder = false;
$highestBidAmount = 0;

// Query to get the highest bidder
$highestBidQuery = "SELECT u.userID, u.userUsername, b.bidAmount 
                    FROM bidtable b 
                    JOIN users u ON b.userID = u.userID 
                    WHERE b.AuctionID = $auctionId 
                    ORDER BY b.bidAmount DESC, b.bidDateAndTime ASC 
                    LIMIT 1";
$highestBidResult = mysqli_query($con, $highestBidQuery);

if ($highestBidResult && mysqli_num_rows($highestBidResult) > 0) {
    $highestBidData = mysqli_fetch_assoc($highestBidResult);
    $highestBidder = $highestBidData['userID'];
    $highestBidAmount = $highestBidData['bidAmount'];
}

// Get current user's ID
if ($loggedIn) {
    $username = $_SESSION['Uusername'];
    $userQuery = "SELECT userID FROM users WHERE userUsername = ?";
    $stmt = mysqli_prepare($con, $userQuery);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $userId);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    $currentUserId = $userId;
    
    // Check if current user is the highest bidder
    $isHighestBidder = ($currentUserId == $highestBidder);
}

// Check if auction has ended - DEFINE THIS BEFORE the bid submission handling
$auctionEnded = false;
if (isset($auction['auctionDeadline'])) {
    $deadline = new DateTime($auction['auctionDeadline']);
    $now = new DateTime();
    $auctionEnded = ($now > $deadline);

    // If the auction has ended, update the auction status to 'Payment Pending'
    if ($auctionEnded && $auction['AuctionStatus'] !== 'Payment Pending' && $auction['AuctionStatus'] !== 'Closed') {
        $updateQuery = "UPDATE auctions SET AuctionStatus = 'Payment Pending' WHERE AuctionID = ?";
        $stmt = mysqli_prepare($con, $updateQuery);
        mysqli_stmt_bind_param($stmt, "i", $auctionId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // Refresh auction data after update
        $refreshQuery = "SELECT AuctionStatus FROM auctions WHERE AuctionID = $auctionId";
        $refreshResult = mysqli_query($con, $refreshQuery);
        $refreshData = mysqli_fetch_assoc($refreshResult);
        $auction['AuctionStatus'] = $refreshData['AuctionStatus'];
    }
}

// Handle bid submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $loggedIn && !$auctionEnded) {
    $bidAmount = isset($_POST['bid_amount']) ? (float)$_POST['bid_amount'] : 0;

    // Validate bid amount
    if ($bidAmount > $auction['StartedBidAmount']) {
        $username = $_SESSION['Uusername'];
        
        // Get user ID
        $userQuery = "SELECT userID FROM users WHERE userUsername = ?";
        $stmt = mysqli_prepare($con, $userQuery);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $userID);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        // Insert bid into the database
        $insertBidQuery = "INSERT INTO bidtable (AuctionID, userID, bidAmount, bidDateAndTime) VALUES (?, ?, ?, NOW())";
        $insertStmt = mysqli_prepare($con, $insertBidQuery);
        mysqli_stmt_bind_param($insertStmt, "iid", $auctionId, $userID, $bidAmount);
        
        if (mysqli_stmt_execute($insertStmt)) {
            echo "<script>alert('Bid placed successfully!');</script>";
        } else {
            echo "<script>alert('Error placing bid. Please try again.');</script>";
        }
        mysqli_stmt_close($insertStmt);
    } else {
        echo "<script>alert('Bid amount must be greater than the current bid.');</script>";
    }
}

// Fetch user profile picture if logged in
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
    <title><?php echo htmlspecialchars($auction['Title']); ?> - AuctionXpress</title>
    <link rel="shortcut icon" href="auction.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Container styles */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            margin-top: 150px;
        }

        /* Product details styling */
        .item-title {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .item-description {
            font-size: 1rem;
            margin-bottom: 20px;
            color: #555;
        }

        .item-details {
            margin-bottom: 20px;
            padding: 10px;
        }

        .item-details p {
            margin: 5px 0;
        }

        .textbox {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        
        #countdown {
            color: #ff0000;
            font-weight: bold;
            font-size: 1.1em;
        }

        /* Main layout row */
        .row {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            margin-bottom: 40px;
        }

        /* Thumbnail column */
        .col-thumbnails {
            width: 200px;
            margin-right: 15px;
        }

        .thumbnail-container {
            width: 70px;
            height: 70px;
            margin-bottom: 10px;
            border: 1px solid #e8e8e8;
            border-radius: 4px;
            overflow: hidden;
            cursor: pointer;
            transition: border 0.2s ease;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .thumbnail-container.active-thumbnail {
            border: 2px solid #0654ba;
        }

        .thumbnail-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        /* Main image column */
        .col-main-image {
            width: 400px;
            position: relative;
        }

        .image-carousel {
            width: 100%;
            height: 500px;
            position: relative;
            border-radius: 8px;
            overflow: hidden;
        }

        .carousel-image {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: none;
            padding: 20px;
        }

        .carousel-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background-color: rgba(255, 255, 255, 0.8);
            color: #333;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
        }

        .carousel-arrow:hover {
            background-color: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .left-arrow {
            left: 10px;
        }

        .right-arrow {
            right: 10px;
        }

        .fullscreen-button {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: rgba(255, 255, 255, 0.8);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
        }

        .fullscreen-button:hover {
            background-color: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        /* Content column */
        .col-content {
            flex: 1;
            padding-left: 30px;
            min-width: 300px;
        }

        /* Bid history styles */
        .bid-history {
            margin-top: 30px;
            border-top: 1px solid #eee;
            padding-top: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            text-align: center;
            width: 100%;
        }

        .bid-table {
            width: 60%;
            margin: 0 auto;
            margin-bottom: 100px;
            border-collapse: collapse;
        }

        .bid-table th,
        .bid-table td {
            padding: 10px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }

        .bid-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .bid-table tr:hover {
            background-color: #f8f9fa;
        }

        /* Payment button */
        .paybutton {
            color: white;
            background-color: #4CAF50;
            padding: 9px 50px;
            text-align: center;
            border-radius: 5px;
            border: none;
            font-size: 16px;
            display: inline-block;
            margin-top: 0;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
        }
        
        .paybutton:hover {
            transform: scale(1.05);
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
        }
        
        /* Auction status styles */
        .auction-status {
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
            display: inline-block;
        }
        
        .status-active {
            background-color: #e6f7ff;
            color: #0066cc;
        }
        
        .status-ended {
            background-color: #f9e6e6;
            color: #cc0000;
        }
        
        .status-pending {
            background-color: #fff3e6;
            color: #cc6600;
        }
        
        /* Winner notice */
        .winner-notice {
            background-color: #e6ffe6;
            color: #008800;
            padding: 10px;
            width: 60%; 
            max-width: 500px; 
            border-radius: 5px;
            text-align: center;
            display: block; 
            margin: 20px auto; 
            font-weight: bold;
            font-size: 1.1em;
            border: 1px solid #aaddaa;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(0, 136, 0, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(0, 136, 0, 0); }
            100% { box-shadow: 0 0 0 0 rgba(0, 136, 0, 0); }
        }

        /* Responsive adjustments */
        @media (max-width: 992px) {
            .col-main-image {
                width: 350px;
            }
            
            .col-content {
                padding-left: 20px;
            }
            
            .bid-table {
                width: 80%;
            }
        }
        
        @media (max-width: 768px) {
            .row {
                flex-direction: column;
            }
            
            .col-thumbnails {
                width: 100%;
                display: flex;
                flex-wrap: wrap;
                margin-right: 0;
                margin-bottom: 15px;
            }
            
            .thumbnail-container {
                margin-right: 10px;
            }
            
            .col-main-image {
                width: 100%;
                margin-bottom: 20px;
            }
            
            .image-carousel {
                height: 400px;
            }
            
            .col-content {
                padding-left: 0;
            }
            
            .bid-table {
                width: 100%;
            }
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

<div class="container">
    <?php if ($auction): ?>
        <div class="row">
            <!-- Thumbnails on the left -->
            <div class="col-thumbnails">
                <?php if (count($images) > 0): ?>
                    <?php foreach ($images as $index => $img): ?>
                        <div class="thumbnail-container <?php echo ($index === 0) ? 'active-thumbnail' : ''; ?>" 
                             onclick="showImage(<?php echo $index; ?>)">
                            <img src="data:image/jpeg;base64,<?php echo base64_encode($img); ?>" 
                                 alt="Thumbnail <?php echo $index + 1; ?>" 
                                 class="thumbnail-image">
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Main image display -->
            <div class="col-main-image">
                <div class="image-carousel">
                    <?php if (count($images) > 0): ?>
                        <?php foreach ($images as $index => $img): ?>
                            <img src="data:image/jpeg;base64,<?php echo base64_encode($img); ?>" 
                                 alt="Item Image <?php echo $index + 1; ?>" 
                                 class="carousel-image" 
                                 id="carousel-image-<?php echo $index; ?>"
                                 style="<?php echo ($index === 0) ? 'display: block;' : ''; ?>">
                        <?php endforeach; ?>
                        
                        <button class="carousel-arrow left-arrow" onclick="changeImage(-1)">❮</button>
                        <button class="carousel-arrow right-arrow" onclick="changeImage(1)">❯</button>
                        
                        <!-- Fullscreen button like in eBay -->
                        <button class="fullscreen-button" onclick="openFullscreen()">
                            <i class="fas fa-expand"></i>
                        </button>
                    <?php else: ?>
                        <img src="placeholder.png" alt="No Image Available" class="item-image">
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Content column -->
            <div class="col-content">
                <h1 class="item-title"><?php echo htmlspecialchars($auction['Title']); ?></h1>
                <p class="item-description"><?php echo htmlspecialchars($auction['Description']); ?></p>
                <div class="item-details">
                    <p><strong>Category:</strong> <?php echo htmlspecialchars($auction['Category'] ?? 'Uncategorized'); ?></p>
                    <p><strong>Initial Amount:</strong> Rs.<?php echo number_format($auction['StartedBidAmount'], 0); ?></p>
                    <p><strong>Status:</strong> 
                        <?php
                        $statusClass = '';
                        switch($auction['AuctionStatus']) {
                            case 'Payment Pending':
                                $statusClass = 'status-pending';
                                break;
                            case 'Closed':
                                $statusClass = 'status-ended';
                                break;
                            default:
                                $statusClass = 'status-active';
                        }
                        ?>
                        <span class="auction-status <?php echo $statusClass; ?>">
                            <?php echo htmlspecialchars($auction['AuctionStatus']); ?>
                        </span>
                    </p>
                    <p><strong>Posted:</strong> <?php echo date('F j, Y g:i A', strtotime($auction['AuctionPostedTime'])); ?></p>
                    <p><strong>Time Remaining:</strong> <span id="countdown"></span></p>
                </div>
                
                <?php if ($loggedIn && !$auctionEnded && $auction['AuctionStatus'] !== 'Closed'): ?>
                    <form method="POST" action="#">
                        <input type="hidden" name="auction_id" value="<?php echo $auctionId; ?>">
                        <input class="textbox" type="number" name="bid_amount" 
                               min="<?php echo $auction['StartedBidAmount'] + 50; ?>" 
                               step="1" 
                               placeholder="Rs. <?php echo number_format($auction['StartedBidAmount'] + 50); ?>">
                        <button type="submit" class="button">Place Bid</button>
                    </form>
                <?php elseif ($loggedIn && ($auctionEnded || $auction['AuctionStatus'] === 'Closed')): ?>
                    <p style="color: red; font-weight: bold;">Bidding has ended for this auction.</p>
                <?php elseif (!$loggedIn): ?>
                    <p><a href="cwlogin.php" id="loginLink" style="color: blue; text-decoration: underline;">Log in</a> to place a bid.</p>
                <?php endif; ?>
            </div> 
        </div>
    <?php else: ?>
        <div class="alert alert-warning">
            Auction not found or no longer available.
        </div>
    <?php endif; ?>
</div>

<?php if ($auction['AuctionStatus'] === 'Payment Pending' && $isHighestBidder): ?>
    <div class="winner-notice">
        Congratulations! You are the highest bidder for this auction.
    </div>
<?php endif; ?>

<?php if ($loggedIn && $auction['AuctionStatus'] === 'Payment Pending' && $isHighestBidder): ?>
    <div style="text-align: center; width: 100%;">
        <button type="button" class="paybutton" onclick="initiatePayment()">Continue Payment</button>
    </div>
<?php endif; ?>

<div class="bid-history">
    <h3>Bid Summary</h3>
    <table class="bid-table">
        <thead>
            <tr>
                <th>Bidder</th>
                <th>Amount</th>
                <th>Date & Time</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($bid = mysqli_fetch_assoc($bidHistoryResult)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($bid['userUsername']); ?></td>
                    <td>Rs.<?php echo number_format($bid['bidAmount']); ?></td>
                    <td><?php echo date('M j, Y g:i A', strtotime($bid['bidDateAndTime'])); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
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
// Define variables
let currentImageIndex = 0;
const totalImages = <?php echo count($images); ?>;

// Define a function to handle page reload logic
function handleAuctionEnd() {
    if (!sessionStorage.getItem('auctionEndedReloaded')) {
        sessionStorage.setItem('auctionEndedReloaded', 'true');
        window.location.reload();
    }
}

// Function to initiate payment and update auction status
function initiatePayment() {
    // First, mark the payment as initiated by updating the URL
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('payment_initiated', 'true');
    
    // Redirect to payment page with the updated URL
    window.location.href = 'https://buy.stripe.com/test_00g9C54x8dzAakM4gg?auctionID=<?php echo $auctionId; ?>&redirect_url=' + 
        encodeURIComponent(currentUrl.toString());
}

// Update countdown timer
function updateCountdown() {
    const deadlineStr = '<?php echo addslashes($auction['auctionDeadline']); ?>';
    const deadline = new Date(deadlineStr).getTime();
    const now = new Date().getTime();
    const timeLeft = deadline - now;
    
    let countdownDisplay = '';
    
    if (timeLeft <= 0) {
        countdownDisplay = 'Auction ended';
        document.getElementById('countdown').style.color = '#cc0000';
        
        // Remove bid form if auction has ended
        const bidForm = document.querySelector('form[method="POST"]');
        if (bidForm) {
            const messageElem = document.createElement('p');
            messageElem.style.color = 'red';
            messageElem.style.fontWeight = 'bold';
            messageElem.textContent = 'Bidding has ended for this auction.';
            bidForm.parentNode.replaceChild(messageElem, bidForm);
        }
        
        // Immediately reload the page when auction ends to show winner message
        handleAuctionEnd();
    } else {
        const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
        const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
        
        countdownDisplay = `${days}d ${hours}h ${minutes}m ${seconds}s`;
        
        // If auction is about to end in the next few seconds, prepare for reload
        if (timeLeft <= 5000 && timeLeft > 0) {
            // Set a timeout to reload the page exactly when the
            const exactTimeout = setTimeout(function() {
                handleAuctionEnd();
            }, timeLeft + 100); // Add 100ms buffer
        }
        
        // Clear the reloaded flag if auction is active (if someone manually refreshes before end)
        if (timeLeft > 10000) { // Only clear if more than 10 seconds remaining
            sessionStorage.removeItem('auctionEndedReloaded');
        }
    }
    
    document.getElementById('countdown').innerHTML = countdownDisplay;
}

// Function to show a specific image by index
function showImage(index) {
    // Hide all images
    const images = document.querySelectorAll('.carousel-image');
    images.forEach(img => img.style.display = 'none');
    
    // Update thumbnails
    const thumbnails = document.querySelectorAll('.thumbnail-container');
    thumbnails.forEach(thumb => thumb.classList.remove('active-thumbnail'));
    
    // Show the selected image and activate its thumbnail
    currentImageIndex = index;
    images[currentImageIndex].style.display = 'block';
    thumbnails[currentImageIndex].classList.add('active-thumbnail');
}

// Function to change image based on direction (-1 for previous, 1 for next)
function changeImage(direction) {
    currentImageIndex = (currentImageIndex + direction + totalImages) % totalImages;
    showImage(currentImageIndex);
}

// Function to open the current image in fullscreen
function openFullscreen() {
    const currentImg = document.getElementById(`carousel-image-${currentImageIndex}`);
    currentImg.requestFullscreen();
 }

// Navigate images with keyboard
document.addEventListener('keydown', function(e) {
    if (e.key === 'ArrowLeft') {
        changeImage(-1);
    } else if (e.key === 'ArrowRight') {
        changeImage(1);
    }
});


// Initialize countdown timer and update every second
updateCountdown();
setInterval(updateCountdown, 1000);
</script>
</body>
</html>