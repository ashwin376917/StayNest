<?php
// connect.php
$servername = "localhost";
$username = "root"; 
$password = "";    
$dbname = "staynest";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character set to UTF-8 for proper handling of characters
$conn->set_charset("utf8mb4");

// Set default timezone for PHP operations to Malaysia
date_default_timezone_set('Asia/Kuala_Lumpur');
?>