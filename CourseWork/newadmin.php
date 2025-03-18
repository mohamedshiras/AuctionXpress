<?php
session_start();
$con = mysqli_connect("localhost:3306", "chiyaforsure", "556143", "auctionxpress");

if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if the user is logged in and is an admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true || !isset($_SESSION['admin_id'])) {
    header("Location: cwlogin.php");
    exit();
}

// Handle new admin creation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['username'], $_POST['password'], $_POST['name'], $_POST['telephone'])) {
    $username = mysqli_real_escape_string($con, $_POST['username']);
    $password = mysqli_real_escape_string($con, $_POST['password']); // Store password as plain text
    $name = mysqli_real_escape_string($con, $_POST['name']);
    $telephone = mysqli_real_escape_string($con, $_POST['telephone']);

    // Insert new admin into the database
    $query = "INSERT INTO admin (admin_name, username, password, telephone) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "ssss", $name, $username, $password, $telephone);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Redirect back to admin view
    header("Location: adminview.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <h2>Add New Admin</h2>
    <form method="post">
        <label for="name">Name:</label>
        <input type="text" name="name" required>
        <br>
        <label for="telephone">Telephone:</label>
        <input type="text" name="telephone" required>
        <br>
        <label for="username">Username:</label>
        <input type="text" name="username" required>
        <br>
        <label for="password">Password:</label>
        <input type="password" name="password" required>
        <br>
        <button type="submit" class="button">Create Admin</button>
    </form>
</div>

</body>
</html>

<?php
mysqli_close($con);
?>