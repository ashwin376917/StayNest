<?php
// Collect booking data from previous page
$homestay_id = $_GET['homestay_id'] ?? '';
$checkin = $_GET['checkin'] ?? '';
$checkout = $_GET['checkout'] ?? '';
$guests = $_GET['guests'] ?? '';

// Validate basic presence of data
if (!$homestay_id || !$checkin || !$checkout || !$guests) {
    die("Missing booking information for redirect.");
}

// Build redirect URL
$redirectURL = "GuestBookingPreview.php?homestay_id={$homestay_id}&checkin={$checkin}&checkout={$checkout}&guests={$guests}";
?>

<!DOCTYPE html>
<html>
<head>
  <title>Payment Failed</title>
  <meta http-equiv="refresh" content="1;url=<?= $redirectURL ?>">
  <style>
    body { 
      text-align: center; 
      padding-top: 100px; 
      font-family: Arial; 
      background: #ffe6e6; 
    }
    h1 { 
      color: red; 
    }
  </style>
</head>
<body>
  <h1>Payment Failed!</h1>
  <p>Redirecting back to Booking Summary...</p>
</body>
</html>
