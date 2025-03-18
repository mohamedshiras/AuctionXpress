<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
$con = mysqli_connect("localhost:3306", "chiyaforsure", "556143", "auctionxpress");

if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    // Redirect to login page if not logged in
    header("Location: adminlogin.php");
    exit();
}

// Retrieve the admin_id from the session
$adminID = $_SESSION['admin_id'];

// Handle user block/unblock (direct form submission - no AJAX)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if this is a block/unblock request
    if (isset($_POST['userID']) && (isset($_POST['blockUser']) || isset($_POST['unblockUser']))) {
        $userID = intval($_POST['userID']);
        
        // Determine action (block or unblock)
        if (isset($_POST['blockUser'])) {
            $newStatus = 'blocked';
            $actionType = 'block';
        } else {
            $newStatus = 'active';
            $actionType = 'unblock';
        }
        
        // Log the action
        error_log("Attempting to $actionType user: " . $userID);
        
        // Prepare and execute the query
        $query = "UPDATE users SET accountApproval = ? WHERE userID = ?";
        $stmt = mysqli_prepare($con, $query);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "si", $newStatus, $userID);
            $result = mysqli_stmt_execute($stmt);
            
            if ($result) {
                error_log("User $actionType successful: " . $userID);
                // Optionally set a session variable for success message
                $_SESSION['status_message'] = "User has been " . ($actionType == 'block' ? 'blocked' : 'unblocked') . " successfully.";
            } else {
                error_log("Failed to $actionType user: " . mysqli_error($con));
                $_SESSION['status_message'] = "Error: Failed to $actionType user.";
            }
            mysqli_stmt_close($stmt);
        } else {
            error_log("Statement preparation failed: " . mysqli_error($con));
            $_SESSION['status_message'] = "Error: Database query failed.";
        }
        
        // Redirect back to the search page with the same username for consistency
        if (isset($_GET['username'])) {
            header("Location: adminview.php?username=" . urlencode($_GET['username']));
        } else {
            header("Location: adminview.php");
        }
        exit();
    }
}

// Reset content type for regular requests
header('Content-Type: text/html; charset=utf-8');

// Handle auction approval/rejection
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['auctionID'], $_POST['action'])) {
    $auctionID = intval($_POST['auctionID']);
    $action = ($_POST['action'] === 'approve') ? 'Approved' : 'Rejected';

    $query = "UPDATE auctions SET auctionApproval = ?, admin_id = ? WHERE AuctionID = ?";
    $stmt = mysqli_prepare($con, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sii", $action, $adminID, $auctionID);
        $result = mysqli_stmt_execute($stmt);
        if (!$result) {
            error_log("Auction update failed: " . mysqli_error($con));
        }
        mysqli_stmt_close($stmt);
    } else {
        error_log("Auction statement preparation failed: " . mysqli_error($con));
    }

    header("Location: adminview.php");
    exit();
}

// Handle user approval/rejection
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['userID'], $_POST['userAction'])) {
    $userID = intval($_POST['userID']);
    $action = $_POST['userAction'];
    
    // Add error logging
    error_log("Attempting to update user status: " . $userID . ", Action: " . $action);
    
    // Set the account status based on action
    $accountStatus = ($action === 'approve') ? 'active' : 'rejected';
    
    $query = "UPDATE users SET accountApproval = ?, approvedAdminID = ? WHERE userID = ?";
    $stmt = mysqli_prepare($con, $query);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sii", $accountStatus, $adminID, $userID);
        $result = mysqli_stmt_execute($stmt);
        
        if ($result) {
            error_log("User  status updated successfully: " . $userID . " to " . $accountStatus);
        } else {
            error_log("User  update failed: " . mysqli_error($con));
        }
        mysqli_stmt_close($stmt);
    } else {
        error_log("User  statement preparation failed: " . mysqli_error($con));
    }
    
    header("Location: adminview.php");
    exit();
}

// Handle user search
$userInfo = null;
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['username'])) {
    $username = mysqli_real_escape_string($con, $_GET['username']);
    $query = "SELECT * FROM users WHERE userUsername = ?";
    $stmt = mysqli_prepare($con, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $userInfo = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
    } else {
        error_log("User search statement preparation failed: " . mysqli_error($con));
    }
}

// Fetch user's auctions with images
if ($userInfo) {
    $userID = $userInfo['userID'];
    $auctionsQuery = "
        SELECT a.*, ai.Image 
        FROM auctions a 
        LEFT JOIN auctionimage ai ON a.AuctionID = ai.AuctionID 
        WHERE a.userID = ?
    ";
    $auctionsStmt = mysqli_prepare($con, $auctionsQuery);
    if ($auctionsStmt) {
        mysqli_stmt_bind_param($auctionsStmt, "i", $userID);
        mysqli_stmt_execute($auctionsStmt);
        $auctionsResult = mysqli_stmt_get_result($auctionsStmt);
        $userAuctions = mysqli_fetch_all($auctionsResult, MYSQLI_ASSOC);
        mysqli_stmt_close($auctionsStmt);
    } else {
        error_log("Auctions statement preparation failed: " . mysqli_error($con));
    }
}

// Fetch pending auctions
$query = "SELECT a.*, u.userEmail FROM auctions a JOIN users u ON a.userID = u.userID WHERE a.auctionApproval = 'pending'";
$result = mysqli_query($con, $query);

// Fetch pending users
$pendingUsersQuery = "SELECT userID, userUsername, userFullName, userEmail, userContact FROM users WHERE accountApproval = 'pending approval'";
$pendingUsersResult = mysqli_query($con, $pendingUsersQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="shortcut icon" href="admin.png" type="image/x-icon">
    <title>AuctionXpress Admin Dashboard</title>
    <script type="text/javascript"
        src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js">
    </script>
    <script type="text/javascript">
        (function(){
            emailjs.init({
                publicKey: "2uxg5kqfxGqvQRk1y",
            });
        })();
    </script>
<style>
        :root {
            --primary-color: #1E3E62;
            --secondary-color: #f4f4f4;
            --accent-color: #FF6B35;
            --text-color: #333;
            --light-gray: #e0e0e0;
            --button-hover: #1a355a;
        }
        
        body {
            font-family: Arial, sans-serif;
            background-color: var(--secondary-color);
            margin: 0;
            padding: 0;
            color: var(--text-color);
        }
                
        .dashboard-container {
            display: flex;
            min-height: 100vh; /* Full height of the viewport */
        }

        .sidebar {
            width: 250px; /* Fixed width for the sidebar */
            background-color: #0a192f;
            color: white;
            padding: 20px 0;
            display: flex;
            flex-direction: column;
        }
        .main-content {
            flex: 1; /* Take the remaining space */
            padding: 20px;
            background-color: #f5f7fa;
            overflow-y: auto; /* Enable vertical scrolling */
        }

       .content-section {
            display: none;
            animation: fadeIn 0.3s;
            max-height: calc(89vh - 80px); /* Adjust based on header height */
            overflow-y: auto; /* Enable scrolling for content sections */
        }

        .content-section.active {
            display: block;
        }

        /* Optional: Style for auction items */
        .auction-item {
            margin-bottom: 15px;
            padding: 15px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
                
        .dashboard-header {
            background-color: white;
            padding: 15px 20px;
            border-bottom: 1px solid var(--light-gray);
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border-radius: 4px;
        }
        
        .search-container {
            max-width: 500px;
            margin: 20px auto;
        }
        
        .search-form {
            display: flex;
            gap: 8px;
        }
        
        .search-input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }
                
             .nav-item {
            width: 100%;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            color: #b4c1d8;
            background: none;
            border: none;
            text-align: left;
            text-decoration: none;
            transition: background-color 0.3s;
            margin-bottom: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
        }

        .nav-item:hover {
            background-color: #16284b;
            color: white;
        }

        .nav-item.active {
            background-color: var(--accent-color);
            color: white;
        }

        .nav-icon {
            margin-right: 12px;
            width: 20px;
            text-align: center;
        }
        
        .button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            font-size: 14px;
        }
        
        .button:hover {
            background-color: var(--button-hover);
        }
        
        .button-secondary {
            background-color: #6c757d;
        }
        
        .button-danger {
            background-color: #dc3545;
        }
        
        .button-success {
            background-color: #28a745;
        }
        
        .auction-item, .user-item {
            background-color: #fff;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .auction-title {
            font-size: 1.2em;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .auction-description, .user-info {
            margin-bottom: 10px;
            color: #555;
            font-size: 15px;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .content-section {
            display: none;
            animation: fadeIn 0.3s;
        }
        
        .content-section.active {
            display: block;
        }
        
        .user-search-results {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        
        .section-title {
            margin-top: 0;
            color: var(--primary-color);
            border-bottom: 2px solid var(--light-gray);
            padding-bottom: 10px;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            padding: 20px 0;
            margin-bottom: 20px;
            color: white;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .logout-container {
            margin-top: auto;
            padding: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        .notification {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .notification-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .notification .close-btn {
            cursor: pointer;
            background: none;
            border: none;
            font-size: 18px;
            color: inherit;
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="logo"><img style="width: 90px;" src="auctionx.png"></div>
        
        <div class="nav-item" onclick="activateSection('pending-auctions',)">
            <span class="nav-icon"><i class="fas fa-gavel"></i></span>
            <span>Auction Requests</span>
        </div>
        
        <div class="nav-item" onclick="activateSection('pending-users')">
            <span class="nav-icon"><i class="fas fa-users"></i></span>
            <span>Pending Users</span>
        </div>
        
        <div class="nav-item" onclick="activateSection('search-users')">
            <span class="nav-icon"><i class="fas fa-search"></i></span>
            <span>Search Users</span>
        </div>
        
        <div class="nav-item" onclick="activateSection('add-admin')">
            <span class="nav-icon"><i class="fas fa-user-plus"></i></span>
            <span>Add Admin</span>
        </div>
        
        <div class="logout-container">
            <form method="post" action="adminlogout.php">
                <button type="submit" class="button" style="width: 100%;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </form>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="main-content">
        <div class="dashboard-header">
            <h2>AuctionXpress Admin Dashboard</h2>
        </div>
        
        
        <!-- Pending Auctions Section -->
        <div id="pending-auctions" class="content-section">
            <h3 class="section-title">Pending Auction Requests</h3>
            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <div class="auction-item">
                        <div class="auction-title"><?php echo htmlspecialchars($row['Title']); ?></div>
                        <div class="auction-description"><?php echo htmlspecialchars($row['Description']); ?></div>
                        <!-- In your PHP file, change the form to use button type="button" -->
                        <form method="post" class="action-buttons">
                            <input type="hidden" name="auctionID" value="<?php echo $row['AuctionID']; ?>">
                            <button type="button" onclick="NotifyAuctionEmail(this, '<?php echo $row['AuctionID']; ?>', 'approve', '<?php echo $row['Title']; ?>', '<?php echo $row['Description']; ?>', '<?php echo $row['userEmail']; ?>')" class="button button-success">Approve</button>
                            <button type="button" onclick="NotifyAuctionEmail(this, '<?php echo $row['AuctionID']; ?>', 'reject', '<?php echo $row['Title']; ?>', '<?php echo $row['Description']; ?>', '<?php echo $row['userEmail']; ?>')" class="button button-danger">Reject</button>
                        </form>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No pending auctions at this time.</p>
            <?php endif; ?>
        </div>
        
<!-- Pending Users Section -->
<div id="pending-users" class="content-section">
    <h3 class="section-title">Pending User Approvals</h3>
    <?php if ($pendingUsersResult && mysqli_num_rows($pendingUsersResult) > 0): ?>
        <?php while ($user = mysqli_fetch_assoc($pendingUsersResult)): ?>
            <div class="user-item">
                <div class="user-info">
                    <strong>Username:</strong> <?php echo htmlspecialchars($user['userUsername']); ?><br>
                    <strong>Name:</strong> <?php echo htmlspecialchars($user['userFullName']); ?><br>
                    <strong>Email:</strong> <?php echo htmlspecialchars($user['userEmail']); ?><br>
                    <strong>Contact:</strong> <?php echo htmlspecialchars($user['userContact']); ?>
                </div>
                <div class="action-buttons">
                    <button type="button" onclick="NotifyAccountUpdate(this, '<?php echo $user['userID']; ?>', 'approve', '<?php echo htmlspecialchars($user['userUsername']); ?>', '<?php echo htmlspecialchars($user['userEmail']); ?>')" class="button button-success">Approve</button>
                    <button type="button" onclick="NotifyAccountUpdate(this, '<?php echo $user['userID']; ?>', 'reject', '<?php echo htmlspecialchars($user['userUsername']); ?>', '<?php echo htmlspecialchars($user['userEmail']); ?>')" class="button button-danger">Reject</button>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No pending users at this time.</p>
    <?php endif; ?>
</div>

        <!-- Add Admin Section -->
        <div id="add-admin" class="content-section">
            <h3 class="section-title">Add New Admin</h3>
            <div class="user-search-results">
                <form method="post" action="adminview.php">
                    <div class="form-group">
                        <label for="newAdminName">Full Name:</label>
                        <input type="text" id="newAdminName" name="newAdminName" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="newAdminTelephone">Telephone:</label>
                        <input type="text" id="newAdminTelephone" name="newAdminTelephone" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="newAdminUsername">Username:</label>
                        <input type="text" id="newAdminUsername" name="newAdminUsername" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="newAdminPassword">Password:</label>
                        <input type="password" id="newAdminPassword" name="newAdminPassword" required>
                    </div>
                    
                    <button type="submit" class="button button-success">Create Admin Account</button>
                </form>
            </div>
        </div>

        <!-- Search Users Section -->
        <div id="search-users" class="content-section">
            <h3 class="section-title">Search Users</h3>
            <div class="search-container">
                <form method="get" class="search-form">
                    <input type="text" name="username" placeholder="Search by Username" required class="search-input">
                    <button type="submit" class="button">Search</button>
                </form>
            </div>
            
            <?php if ($userInfo): ?>
            <div class="user-search-results">
                <h3>User Information</h3>
                <div class="user-info">
                    <p><strong>Username:</strong> <?php echo htmlspecialchars($userInfo['userUsername']); ?></p>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($userInfo['userFullName']); ?></p>
                    <p><strong>Telephone:</strong> <?php echo htmlspecialchars($userInfo['userContact']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($userInfo['userEmail']); ?></p>
                    <p><strong>Account Status:</strong> <?php echo htmlspecialchars($userInfo['accountApproval']); ?></p>
                </div>
                <form method="post" action="adminview.php<?php echo isset($_GET['username']) ? '?username=' . urlencode($_GET['username']) : ''; ?>">
                    <input type="hidden" name="userID" value="<?php echo intval($userInfo['userID']); ?>">
                    <?php if (strtolower(trim($userInfo['accountApproval'])) === 'blocked'): ?>
                        <button type="submit" name="unblockUser" value="1" class="button button-success">Unblock User</button>
                    <?php else: ?>
                        <button type="submit" name="blockUser" value="1" class="button button-danger">Block User</button>
                    <?php endif; ?>
                </form>

                <!-- Display User's Auctions -->
                <?php if (!empty($userAuctions)): ?>
                    <h3>User's Auctions</h3>
                    <div class="auctions-list">
                        <?php foreach ($userAuctions as $auction): ?>
                            <div class="auction-item">
                                <!-- Display Auction Image -->
                                <?php if (!empty($auction['Image'])): ?>
                                    <div class="auction-image">
                                        <img src="data:image/jpeg;base64,<?php echo base64_encode($auction['Image']); ?>" alt="Auction Image" style="max-width: 200px; height: auto;">
                                    </div>
                                <?php else: ?>
                                    <div class="auction-image">
                                        <img src="placeholder.png" alt="No Image Available" style="max-width: 200px; height: auto;">
                                    </div>
                                <?php endif; ?>
                                <div class="auction-title"><?php echo htmlspecialchars($auction['Title']); ?></div>
                                <div class="auction-description"><?php echo htmlspecialchars($auction['Description']); ?></div>
                                <div class="auction-details">
                                    <p><strong>Category:</strong> <?php echo htmlspecialchars($auction['Category']); ?></p>
                                    <p><strong>Starting Bid:</strong> Rs.<?php echo number_format($auction['StartedBidAmount'], 0); ?></p>
                                    <p><strong>Status:</strong> <?php echo htmlspecialchars($auction['AuctionStatus']); ?></p>
                                    <p><strong>Posted:</strong> <?php echo date('F j, Y g:i A', strtotime($auction['AuctionPostedTime'])); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No auctions found for this user.</p>
                <?php endif; ?>
                <?php elseif (isset($_GET['username'])): ?>
                    <div class="user-search-results">
                        <p>No user found with that username.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
<script src="admin.js"></script>
<script type="text/javascript">
    (function(){
        emailjs.init({
            publicKey: "2uxg5kqfxGqvQRk1y",
        });
        
        // Add this to log EmailJS status at initialization
        console.log("EmailJS initialized with public key");
    })();
</script>
</body>
</html>