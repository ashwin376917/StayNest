<?php
$servername = "localhost:3301";
$username = "root";
$password = "abc12345";
$dbname = "staynest";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// echo "Connected successfully";
?>
