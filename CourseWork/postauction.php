<?php
session_start();
$profilePic = "unknown.svg"; // Default profile picture
$con = mysqli_connect("localhost:3306", "chiyaforsure", "556143");
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}
mysqli_select_db($con, "auctionxpress");

// Check if the user is logged in
if (!isset($_SESSION['Uusername'])) {
    header("Location: cwlogin.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $startingPrice = $_POST['startingPrice'];
    $category = $_POST['category'];
    $duration = $_POST['duration']; // Get duration value
    $username = $_SESSION['Uusername'];

    // Calculate auction deadline
    $deadline = date('Y-m-d H:i:s', strtotime("+{$duration} hours"));

    // Retrieve userID from the users table based on the username
    $userQuery = "SELECT userID FROM users WHERE userUsername = '$username'";
    $userResult = mysqli_query($con, $userQuery);
    if ($userResult && mysqli_num_rows($userResult) > 0) {
        $userRow = mysqli_fetch_assoc($userResult);
        $userID = $userRow['userID'];

        // Insert auction details with deadline
        $query = "INSERT INTO auctions (Title, Description, StartedBidAmount, Category, auctionApproval, userID, auctionDeadline) 
                  VALUES ('$title', '$description', '$startingPrice', '$category', 'Pending', '$userID', '$deadline')";

        if (mysqli_query($con, $query)) {
            // Get the last inserted AuctionID
            $auctionID = mysqli_insert_id($con);

            // Handle image uploads and insert into AuctionImage table
            for ($i = 0; $i < 5; $i++) {
                if (!empty($_FILES["image$i"]["name"])) {
                    $fileName = basename($_FILES["image$i"]["name"]);
                    $imageFileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                    // Allow only img, jpg, and png files
                    if (in_array($imageFileType, ['img', 'jpg', 'png', 'jpeg'])) {
                        $imageData = addslashes(file_get_contents($_FILES["image$i"]["tmp_name"]));

                        // Insert image details into auctionimage table
                        $query = "INSERT INTO auctionimage (AuctionID, Image) 
                                  VALUES ('$auctionID', '$imageData')";

                        if (!mysqli_query($con, $query)) {
                            echo "<script>alert('Error saving image $i to the database: " . mysqli_error($con) . "');</script>";
                        }
                    } else {
                        echo "<script>alert('Only IMG, JPG, and PNG files are allowed for image $i.');</script>";
                    }
                }
            }

            echo "<script>alert('Auction posted successfully!');</script>";
        } else {
            echo "<script>alert('Error posting auction: " . mysqli_error($con) . "');</script>";
        }
    } else {
        echo "<script>alert('Error retrieving userID for the logged-in user.');</script>";
    }
}

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="shortcut icon" href="auction.png" type="image/x-icon">
    <title>Post Auction - AuctionXpress</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Enhanced styles for the form */
        body {
            background-color: #f9f9f9;
        }
        
        .container {
            padding: 30px 15px;
        }
        
        .container h2 {
            font-size: 2.5rem;
            margin-left: 180px;
            margin-bottom: 20px;
            letter-spacing: 1px;
            position: relative;
        }
        
        .container h2:after {
            content: "";
            display: block;
            width: 86%;
            height: 4px;
            background: #1E3E62;
        }
        
        .form-container {
            width: 80%;
            margin: 30px auto;
            padding: 30px;
        }
        .button-form {
            padding: 10px 50px;
            ont-size: 0.8rem;
            background-color: #FF6B35;
            color: white;
            font-weight: bold;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
                }

        .button-form:hover {
            background-color: #1a355a;
            transform: translateY(-2px);
        }
        
        .mb-3 {
            margin-bottom: 25px;
        }
        
        .form-label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 1.1rem;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            transition: all 0.3s;
            font-size: 1rem;
        }
        
        .form-control:focus {
            border-color: #1E3E62;
            outline: none;
            box-shadow: 0 0 0 3px rgba(30, 62, 98, 0.2);
            background-color: #fff;
        }
        
        input[type="file"].form-control {
            padding: 8px;
            margin-bottom: 10px;
        }
        
        .file-upload-container {
            margin-bottom: 20px;
        }
        
        .image-preview {
            border: 2px dashed #ccc;
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
            text-align: center;
            background-color: #f8f9fa;
        }
        
        .image-preview i {
            font-size: 2rem;
            color: #1E3E62;
            margin-bottom: 10px;
        }
        
        .button-group {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }
        
        /* Image upload styling */
        .image-upload-section {
            margin-bottom: 25px;
        }
        
        .image-upload-title {
            font-weight: bold;
            color: #1E3E62;
            margin-bottom: 15px;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
        }
        
        .image-upload-title i {
            margin-right: 8px;
        }
        
        .image-upload-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .upload-box {
            border: 2px dashed #ccc;
            border-radius: 8px;
            height: 120px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .upload-box:hover {
            border-color: #1E3E62;
            background-color: rgba(30, 62, 98, 0.05);
        }
        
        .upload-box i {
            font-size: 2rem;
            color: #1E3E62;
            margin-bottom: 8px;
        }
        
        .upload-box span {
            font-size: 0.9rem;
            color: #666;
        }
        
        .upload-box input[type="file"] {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            opacity: 0;
            cursor: pointer;
        }
        
        /* Hint text */
        .hint-text {
            font-size: 0.85rem;
            color: #666;
            margin-top: 5px;
            font-style: italic;
        }
        
        /* Category icons */
        .category-icon {
            margin-right: 8px;
            color: #1E3E62;
        }
        
        /* Duration styling */
        .duration-options {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        
        .duration-option {
            flex: 1;
            min-width: 100px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .duration-option:hover {
            border-color: #1E3E62;
            background-color: rgba(30, 62, 98, 0.05);
        }
        
        .duration-option.selected {
            border-color: #1E3E62;
            background-color: rgba(30, 62, 98, 0.1);
        }
        
        .duration-option .time {
            font-weight: bold;
            font-size: 1.1rem;
            color: #1E3E62;
        }
        
        .duration-option .label {
            font-size: 0.8rem;
            color: #666;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .form-container {
                padding: 20px;
                width: 95%;
            }
            
            .button-group {
                flex-direction: column;
                gap: 10px;
            }
            
            .button {
                width: 100%;
            }
            
            .duration-options {
                grid-template-columns: 1fr 1fr;
            }
            
            .container h2 {
                font-size: 2rem;
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
            <button class="button" id="logoutbutton">Log Out</button>
            <div class="profile-wrapper">
                <img id="pp" class="profile-pic" src="<?php echo $profilePic; ?>" alt="Profile Picture" onclick="redirectToManageAccount()">
            </div>
        </div>
    </div>
</nav>

<div class="container">
    <h2>Post Your Auction</h2>
    <div class="form-container">
        <form method="post" enctype="multipart/form-data" action="postauction.php" id="auctionForm">
            <div class="mb-3">
                <label for="title" class="form-label"><i class="fas fa-tag category-icon"></i>Item Title</label>
                <input type="text" class="form-control" id="title" name="title" placeholder="Enter a descriptive title for your item" required>
                <div class="hint-text">A clear, specific title will attract more bidders</div>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label"><i class="fas fa-align-left category-icon"></i>Description</label>
                <textarea class="form-control" id="description" name="description" rows="3" placeholder="Provide detailed information about your item" required></textarea>
                <div class="hint-text">Include condition, specifications, dimensions, and any other relevant details</div>
            </div>
            
            <div class="mb-3">
                <label for="category" class="form-label"><i class="fas fa-folder category-icon"></i>Category</label>
                <select class="form-control" id="category" name="category" required>
                    <option value="">Select a category</option>
                    <option value="Electronics">Electronics</option>
                    <option value="Fashion">Fashion</option>
                    <option value="Home & Garden">Home & Garden</option>
                    <option value="Art & Collectibles">Art & Collectibles</option>
                    <option value="Sports & Outdoors">Sports & Outdoors</option>
                    <option value="Vehicles">Vehicles</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="startingPrice" class="form-label"><i class="fas fa-rupee-sign category-icon"></i>Starting Price</label>
                <input type="number" class="form-control" id="startingPrice" name="startingPrice" step="0.01" placeholder="0.00" required>
                <div class="hint-text">Set a competitive starting price to attract initial bids</div>
            </div>
            
            <div class="mb-3">
                <label for="duration" class="form-label"><i class="fas fa-clock category-icon"></i>Auction Duration</label>
                <select class="form-control" id="duration" name="duration" required>
                    <option value="">Select auction duration</option>
                    <option value="6">6 hours</option>
                    <option value="12">12 hours</option>
                    <option value="24">1 day</option>
                    <option value="48">2 days</option>
                    <option value="72">3 days</option>
                    <option value="96">4 days</option>
                    <option value="120">5 days</option>
                    <option value="144">6 days</option>
                </select>
                <div class="hint-text">Longer durations give more potential bidders a chance to see your auction</div>
            </div>
            
            <div class="mb-3 image-upload-section">
                <div class="form-label"><i class="fas fa-images category-icon"></i> Upload Images (Max 5)</div>
                <div class="hint-text">High-quality images from multiple angles will increase your chances of selling</div>
                
                <div class="image-upload-grid">
                    <?php for ($i = 0; $i < 5; $i++): ?>
                        <div class="upload-box">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <span>Image <?php echo $i+1; ?></span>
                            <input type="file" name="image<?php echo $i; ?>" accept="image/*" onchange="previewImage(this, <?php echo $i; ?>)">
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
            
            <div class="button-group">
                <button type="button" class="button-form" onclick="clearForm()">Clear Form</button>
                <button type="submit" class="button-form">Submit Auction</button>
            </div>
        </form>
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
// Image preview functionality
function previewImage(input, index) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const uploadBox = input.parentElement;
            uploadBox.style.backgroundImage = `url(${e.target.result})`;
            uploadBox.style.backgroundSize = 'cover';
            uploadBox.style.backgroundPosition = 'center';
            
            // Hide the icon and text
            const icon = uploadBox.querySelector('i');
            const span = uploadBox.querySelector('span');
            if (icon) icon.style.display = 'none';
            if (span) span.style.display = 'none';
            
            // Add a remove button
            if (!uploadBox.querySelector('.remove-btn')) {
                const removeBtn = document.createElement('div');
                removeBtn.className = 'remove-btn';
                removeBtn.innerHTML = '<i class="fas fa-times"></i>';
                removeBtn.style.position = 'absolute';
                removeBtn.style.top = '5px';
                removeBtn.style.right = '5px';
                removeBtn.style.backgroundColor = 'rgba(255, 255, 255, 0.8)';
                removeBtn.style.borderRadius = '50%';
                removeBtn.style.width = '24px';
                removeBtn.style.height = '24px';
                removeBtn.style.display = 'flex';
                removeBtn.style.alignItems = 'center';
                removeBtn.style.justifyContent = 'center';
                removeBtn.style.cursor = 'pointer';
                removeBtn.style.color = '#ff4444';
                
                removeBtn.onclick = function(e) {
                    e.stopPropagation();
                    input.value = '';
                    uploadBox.style.backgroundImage = '';
                    icon.style.display = 'block';
                    span.style.display = 'block';
                    uploadBox.removeChild(removeBtn);
                };
                
                uploadBox.appendChild(removeBtn);
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Form clear functionality
function clearForm() {
    if (confirm('Are you sure you want to clear the form?')) {
        document.getElementById('auctionForm').reset();
        
        // Reset image previews
        const uploadBoxes = document.querySelectorAll('.upload-box');
        uploadBoxes.forEach(box => {
            box.style.backgroundImage = '';
            
            const icon = box.querySelector('i');
            const span = box.querySelector('span');
            if (icon) icon.style.display = 'block';
            if (span) span.style.display = 'block';
            
            const removeBtn = box.querySelector('.remove-btn');
            if (removeBtn) box.removeChild(removeBtn);
        });
    }
}
</script>
</body>
</html>