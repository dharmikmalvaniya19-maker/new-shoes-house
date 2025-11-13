<?php
session_start();
session_unset(); // Unset all session variables
session_destroy(); // Destroy the session

// Redirect to home page or login page
header("Location: index.php"); // Adjust this path if your index.html is elsewhere
exit;
?>