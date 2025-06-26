<?php
$servername = "localhost";
$username = "serverhandlertest";
$password = ""; // no password as you said
$dbname = "staynest";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Optional success message (you can comment this out)
// echo "Connected successfully!";
?>
