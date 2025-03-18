<?php
session_start();
$con = mysqli_connect("localhost:3306", "chiyaforsure", "556143");
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}
mysqli_select_db($con, "auctionxpress");

$auctionId = isset($_GET['auctionID']) ? (int)$_GET['auctionID'] : 0;

$bidHistoryQuery = "SELECT b.bidAmount, b.bidDateAndTime, u.userUsername 
                   FROM bidtable b 
                   JOIN users u ON b.userID = u.userID 
                   WHERE b.AuctionID = ? 
                   ORDER BY b.bidDateAndTime DESC";

$stmt = mysqli_prepare($con, $bidHistoryQuery);
mysqli_stmt_bind_param($stmt, "i", $auctionId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

while ($bid = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($bid['userUsername']) . "</td>";
    echo "<td>Rs." . number_format($bid['bidAmount'], 0) . "</td>";
    echo "<td>" . date('M j, Y g:i A', strtotime($bid['bidDateAndTime'])) . "</td>";
    echo "</tr>";
}

mysqli_close($con);
?>