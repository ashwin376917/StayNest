<?php
session_start(); // Make sure session is started
include_once '../../connect.php';

$homestay_id = $_GET['homestay_id'] ?? '';
$checkin = $_GET['checkin'] ?? '';
$checkout = $_GET['checkout'] ?? '';
$guests = $_GET['guests'] ?? '';

if (!$homestay_id || !$checkin || !$checkout || !$guests) {
    die("Missing required booking data.");
}

// Fetch homestay data
$sql = "SELECT * FROM homestay WHERE homestay_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $homestay_id);
$stmt->execute();
$result = $stmt->get_result();
$homestay = $result->fetch_assoc();

if (!$homestay) {
    die("Homestay not found.");
}

// Calculate duration and total
$in = new DateTime($checkin);
$out = new DateTime($checkout);
$interval = $in->diff($out);
$nights = $interval->days;
$total = $nights * $homestay['price_per_night'];

// Set session flag to allow payment gateway access
$_SESSION['can_access_payment'] = true;
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Booking Summary</title>
  <link rel="stylesheet" href="../../CSS/Guest/GuestBookingPreview.css">
  <link rel="stylesheet" href="../../CSS/Guest/GuestHeader.css?v=4">
</head>

<body>
   <a href="javascript:history.back()" class="back-button">
   <img src="../../assets/back_button.png" alt="Back" />
   Back
   </a>

  <div class="preview-container">
  <div class="preview-inner">
    <h2>Booking Summary</h2>
    <p><strong>Homestay :</strong> <?= htmlspecialchars($homestay['title']) ?></p>
    <p><strong>Address :</strong> <?= htmlspecialchars($homestay['address']) ?></p>
    <p><strong>Guests :</strong> <?= htmlspecialchars($guests) ?></p>
    <p><strong>Check-in :</strong> <?= htmlspecialchars($checkin) ?></p>
    <p><strong>Check-out :</strong> <?= htmlspecialchars($checkout) ?></p>
    <p><strong>Nights :</strong> <?= $nights ?></p>
    <p><strong>Total Price :</strong> RM <?= number_format($total, 2) ?></p>


    <!-- Confirm button -->
   <form action="PaymentGateaway.php" method="post">
  <input type="hidden" name="homestay_id" value="<?= $homestay_id ?>">
  <input type="hidden" name="checkin" value="<?= $checkin ?>">
  <input type="hidden" name="checkout" value="<?= $checkout ?>">
  <input type="hidden" name="guests" value="<?= $guests ?>">
  <input type="hidden" name="total_price" value="<?= $total ?>">
  <button type="submit">Confirm & Pay</button>
</form>

    <!-- <a href="javascript:history.back()">← Modify Booking</a> -->
  </div>
</div>
</body>
</html>
