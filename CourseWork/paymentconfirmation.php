<?php
// Database connection
$con = mysqli_connect("localhost:3306", "chiyaforsure", "556143");
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}
mysqli_select_db($con, "auctionxpress");

?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Confirmation - AuctionXpress</title>
    <link rel="shortcut icon" href="auction.png" type="image/x-icon">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-wrapper">
            <a class="navbar-brand" href="index.php">
                <img src="auctionx.png" alt="AuctionXpress Logo" class="nav-logo">
            </a>
        </div>
    </nav>
    <div class="confirmation-container">
        <div class="confirmation-card">
            <i class="fas fa-check-circle confirmation-icon"></i><br><br>
            <h1>Payment Successful!</h1>
            <p>Thank you for your payment. Your transaction has been completed successfully.</p>
            <audio id="confirmation-sound" src="sound.mp3" preload="auto"></audio>
            <a href="index.php" class="button">Return to Homepage</a>
            <button id="play-sound" class="button sound-button">Play Confirmation Sound</button>
        </div>
    </div>
    <div class="footer">
        <div class="footer-bottom">
            <div class="tagline">Your Trusted Online Auction Marketplace</div>
            <div class="copyright">© 2025 AuctionXpress Ltd. - All rights reserved.</div>
        </div>
    </div>
    <style>
.confirmation-container {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 80vh;
}
.confirmation-card {
    text-align: center;
    background: #fff;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}
.button {
    margin-top: 20px;
    display: inline-block;
    padding: 10px 20px;
    background: #008CBA;
    color: #fff;
    border-radius: 5px;
    text-decoration: none;
    border: none;
    cursor: pointer;
    font-size: 16px;
}
.sound-button {
    background: #4CAF50;
    margin-left: 10px;
}
.confirmation-icon {
    color: green;
    font-size: 60px;
    border-radius: 50%;
    display: inline-block;
    animation: pop-in 0.5s ease-out, glow-border 0.8s infinite alternate;
}
/* Scale animation to make the icon pop in */
@keyframes pop-in {
    0% {
        transform: scale(0.5);
        opacity: 0;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}
/* Glowing border animation */
@keyframes glow-border {
    0% {
        box-shadow: 0 0 5px rgba(0, 255, 0, 0.5);
    }
    100% {
        box-shadow: 0 0 20px rgba(0, 255, 0, 1), 0 0 30px rgba(0, 255, 0, 0.8);
    }
}
    </style>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        // Get the audio element
        const sound = document.getElementById('confirmation-sound');
        
        // Try autoplay with muted state first (most browsers allow this)
        sound.muted = true;
        const autoplayPromise = sound.play();
        
        if (autoplayPromise !== undefined) {
            autoplayPromise
                .then(() => {
                    console.log("Muted autoplay successful");
                    // We can play muted, but we'll keep it muted until user clicks the button
                })
                .catch(error => {
                    console.log("Even muted autoplay failed:", error);
                });
        }
        
        // Set up play button
        const playButton = document.getElementById('play-sound');
        playButton.addEventListener('click', function() {
            // Unmute and play from beginning
            sound.currentTime = 0;
            sound.muted = false;
            
            const playPromise = sound.play();
            
            if (playPromise !== undefined) {
                playPromise
                    .then(() => {
                        console.log("Sound played successfully");
                        playButton.textContent = "Playing...";
                        
                        // Reset button after audio finishes
                        sound.onended = function() {
                            playButton.textContent = "Play Again";
                        };
                    })
                    .catch(error => {
                        console.log("Play failed:", error);
                        playButton.textContent = "Play Failed";
                    });
            }
        });
        
        // Add an alternative trigger for the sound when clicking the confirmation icon
        const confirmationIcon = document.querySelector('.confirmation-icon');
        confirmationIcon.style.cursor = 'pointer';
        confirmationIcon.addEventListener('click', function() {
            sound.currentTime = 0;
            sound.muted = false;
            sound.play().catch(error => console.log("Icon click play failed:", error));
        });
    });
    </script>
</body>
</html>