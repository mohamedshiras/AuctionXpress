<?php
session_start();
$loggedIn = isset($_SESSION['Uusername']);  // Check if user is logged in
$profilePic = "unknown.svg"; // Default profile picture

// Only connect to database and fetch profile picture if user is logged in
if ($loggedIn) {
    // Database connection
    $con = mysqli_connect("localhost:3306", "chiyaforsure", "556143");
    if (!$con) {
        die("Connection failed: " . mysqli_connect_error());
    }
    mysqli_select_db($con, "auctionxpress");

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
    
    mysqli_close($con);
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>About Us - AuctionXpress</title>
    <link rel="shortcut icon" href="auction.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Additional styles for About Us page */
        .about-container {
            padding: 40px;
            max-width: 1200px;
            margin: 0 auto;
            margin-top: 55px;
        }
        
        .about-hero {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.7)), url('bn.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 80px 40px;
            text-align: center;
            border-radius: 8px;
            margin-bottom: 40px;
        }
        
        .about-hero h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }
        
        .about-hero p {
            font-size: 1.2rem;
            max-width: 800px;
            margin: 0 auto;
            line-height: 1.6;
        }
        
        .about-section {
            margin-bottom: 60px;
        }
        
        .about-section h2 {
            color: #1a355a;
            font-size: 1.8rem;
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 10px;
        }
        
        .about-section h2:after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 80px;
            height: 3px;
            background-color: #FF6B35;
        }
        
        .about-section p {
            line-height: 1.6;
            color: #444;
            margin-bottom: 20px;
        }
        
        .mission-vision {
            display: flex;
            gap: 30px;
            margin-top: 30px;
        }
        
        .mission, .vision {
            flex: 1;
            background-color: #f9f9f9;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .mission h3, .vision h3 {
            color: #1a355a;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }
        
        .team-section {
            margin-top: 60px;
        }
        
        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }
        
        .team-member {
            text-align: center;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .team-member:hover {
            transform: translateY(-10px);
        }
        
        .member-pic {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin: 30px auto 20px;
            background-color: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 50px;
            color: #008CBA;
            overflow: hidden;
        }
        
        .member-info {
            padding: 20px;
        }
        
        .member-info h3 {
            margin-bottom: 5px;
            color: #333;
        }
        
        .member-info p {
            color: #666;
            margin-bottom: 15px;
        }
        
        .benefits-list {
            list-style: none;
            padding: 0;
            margin-top: 30px;
        }
        
        .benefit-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 25px;
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .benefit-icon {
            font-size: 1.5rem;
            color: #FF6B35;
            margin-right: 20px;
        }
        
        .benefit-content h3 {
            margin-top: 0;
            margin-bottom: 10px;
            color: #333;
        }
        
        .benefit-content p {
            margin: 0;
            color: #555;
        }
        
        @media (max-width: 768px) {
            .mission-vision {
                flex-direction: column;
            }
            
            .about-hero h1 {
                font-size: 2rem;
            }
            
            .about-hero p {
                font-size: 1rem;
            }
            
            .about-container {
                padding: 20px;
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
                <li class="nav-item"><a class="nav-link active" href="#">About us</a></li>
                <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Category</a></li>
            </ul>
            <div class="nav-buttons">
                <?php if (!isset($_SESSION['Uusername'])): ?>
                    <button class="button" id="loginbutton">Log In</button>
                    <button class="button" id="signupbutton">Sign Up</button>
                <?php else: ?>
                    <button class="button" id="postAuctionbutton">Post Auction</button>
                    <button class="button" id="logoutbutton">Log Out</button>
                    <div class="profile-wrapper">
                         <img id="pp" class="profile-pic" src="<?php echo isset($profilePic) ? $profilePic : 'unknown.svg'; ?>" alt="Profile Picture" onclick="redirectToManageAccount()">
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="about-container">
        <div class="about-hero">
            <h1>Transforming Traditional Auctions to Automated</h1>
            <p>AuctionXpress is an innovative online auction platform that brings buyers and sellers together from across the country on a single, secure, and user-friendly platform</p>
        </div>

        <div class="about-section">
            <h2>Our Story</h2>
            <p>AuctionXpress was developed with the vision of revolutionizing the traditional auction experience. We identified inherent problems in conventional auction methods - time constraints, geographical limitations, and accessibility issues - and set out to create a solution that brings the auction experience to the fingertips of aspiring bidders and sellers.</p>
            
            <p>Founded by a team of passionate software engineering students, AuctionXpress has grown from a project idea into a comprehensive auction management system that connects buyers and sellers across the country, providing a seamless and efficient platform for online auctions.</p>
            
            <div class="mission-vision">
                <div class="mission">
                    <h3><i class="fas fa-bullseye"></i> Our Mission</h3>
                    <p>To connect product sellers and bidders nationwide on a single platform and provide an alternative bidding policy that saves time and money while ensuring transparency, security, and efficiency in the auction process.</p>
                </div>
                <div class="vision">
                    <h3><i class="fas fa-eye"></i> Our Vision</h3>
                    <p>To transform the traditional auction landscape into a fully automated, accessible, and paperless experience that empowers both buyers and sellers to engage in fair and transparent transactions from anywhere, at any time.</p>
                </div>
            </div>
        </div>

        <div class="about-section">
            <h2>Why Choose AuctionXpress?</h2>
            <ul class="benefits-list">
                <li class="benefit-item">
                    <div class="benefit-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="benefit-content">
                        <h3>24/7 Accessibility</h3>
                        <p>Participate in auctions anytime, anywhere - eliminating the need to travel or adhere to fixed auction schedules.</p>
                    </div>
                </li>
                <li class="benefit-item">
                    <div class="benefit-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="benefit-content">
                        <h3>Verified Accounts</h3>
                        <p>All users undergo a verification process by our team, ensuring a secure and trustworthy auction environment.</p>
                    </div>
                </li>
                <li class="benefit-item">
                    <div class="benefit-icon">
                        <i class="fas fa-globe"></i>
                    </div>
                    <div class="benefit-content">
                        <h3>Wider Reach</h3>
                        <p>Connect with buyers and sellers from across the country, expanding your market beyond geographical constraints.</p>
                    </div>
                </li>
                <li class="benefit-item">
                    <div class="benefit-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="benefit-content">
                        <h3>Paperless Transactions</h3>
                        <p>All documents and receipts are digital and accessible from anywhere, contributing to environmental sustainability.</p>
                    </div>
                </li>
                <li class="benefit-item">
                    <div class="benefit-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="benefit-content">
                        <h3>Direct Communication</h3>
                        <p>Bidders can communicate directly with sellers after auction completion, facilitating smooth transactions.</p>
                    </div>
                </li>
            </ul>
        </div>

        <div class="about-section team-section">
            <h2>Meet Our Team</h2>
            <p>AuctionXpress was developed by a talented team of software engineering students passionate about creating innovative solutions for real-world problems.</p>
            
            <div class="team-grid">
                <div class="team-member">
                    <div class="member-pic">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="member-info">
                        <h3>M R M Shiras</h3>
                        <p>GADSE233F-040</p>
                    </div>
                </div>
                <div class="team-member">
                    <div class="member-pic">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="member-info">
                        <h3>M A F Aasifa</h3>
                        <p>GADSE233F-036</p>
                    </div>
                </div>
                <div class="team-member">
                    <div class="member-pic">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="member-info">
                        <h3>M Z M Zafri</h3>
                        <p>GADSE233F-006</p>
                    </div>
                </div>
                <div class="team-member">
                    <div class="member-pic">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="member-info">
                        <h3>K D H Vinudi</h3>
                        <p>GADSE233F-045</p>
                    </div>
                </div>
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
    <script>
        // Login, Signup, and other button redirects
        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('loginbutton')) {
                document.getElementById('loginbutton').addEventListener('click', function() {
                    window.location.href = 'login.php';
                });
            }
            
            if (document.getElementById('signupbutton')) {
                document.getElementById('signupbutton').addEventListener('click', function() {
                    window.location.href = 'signup.php';
                });
            }
            
            if (document.getElementById('postAuctionbutton')) {
                document.getElementById('postAuctionbutton').addEventListener('click', function() {
                    window.location.href = 'postauction.php';
                });
            }
            
            if (document.getElementById('logoutbutton')) {
                document.getElementById('logoutbutton').addEventListener('click', function() {
                    window.location.href = 'logout.php';
                });
            }
        });
        
        function redirectToManageAccount() {
            window.location.href = 'manageaccount.php';
        }
    </script>
</body>
</html>