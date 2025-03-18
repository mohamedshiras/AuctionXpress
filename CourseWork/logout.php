<?php
session_start();     // Initialize the session
session_unset();     // Unset all session variables
session_destroy();   // Destroy the session
header("Location: index.php");  // Redirect to homepage
exit();
