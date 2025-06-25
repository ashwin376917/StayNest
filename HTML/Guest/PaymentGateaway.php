<?php
session_start();
include("../../connect.php");

date_default_timezone_set('Asia/Kuala_Lumpur');

if (!isset($_SESSION['guest_id'])) {
    die("Guest not logged in. Session lost.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['card_number']) && isset($_POST['cvv'])) {

    $method = $_POST['payment_method'] ?? '';
    $card = preg_replace('/\D/', '', $_POST['card_number'] ?? '');
    $cvv = preg_replace('/\D/', '', $_POST['cvv'] ?? '');
    $amount = $_POST['total_price'] ?? 100.00;
    $checkin = $_POST['checkin'];
    $checkout = $_POST['checkout']; 
    $homestay_id = $_POST['homestay_id'];
    $guests = intval($_POST['guests']);
    $user_id = $_SESSION['guest_id'];
    $today = date("Y-m-d H:i:s");

    if (!ctype_digit($card) || strlen($card) !== 16 || !ctype_digit($cvv) || strlen($cvv) !== 3) {
        header("Location: PaymentFailed.php?homestay_id={$homestay_id}&checkin={$checkin}&checkout={$checkout}&guests={$guests}");
        exit();
    }

    $booking_id = 'B' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    $booking_sql = "INSERT INTO booking (booking_id, guest_id, homestay_id, booking_date, check_in_date, check_out_date, booking_status, guest_count)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($booking_sql);
    $booking_status = 1; // Assuming 1 means Confirmed
    $stmt->bind_param("ssssssii", $booking_id, $user_id, $homestay_id, $today, $checkin, $checkout, $booking_status, $guests);
    $stmt->execute();

    $payment_id = 'P' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    $payment_sql = "INSERT INTO payment (payment_id, booking_id, payment_date, amount)
                    VALUES (?, ?, ?, ?)";
    $stmt2 = $conn->prepare($payment_sql);
    $stmt2->bind_param("sssd", $payment_id, $booking_id, $today, $amount);
    $stmt2->execute();

    header("Location: PaymentSuccess.html");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>StayNest | Payment Gateway</title>
  <link rel="stylesheet" href="../../CSS/Guest/PaymentGateaway.css?v4" />
</head>
<body>
<div class="container">
  <div class="left">
    <div class="top-bar">
      <img src="../../assets/staynest_logo.png" alt="StayNest Logo" />
      <span class="WebName">StayNest</span>
    </div>

    <form class="form-box" method="post" action="">
      <h2>Payment Method</h2>

      <select name="payment_method" required>
        <option value="">-- Select Payment Method --</option>
        <option value="card">Debit / Credit Card</option>
      </select>

      <input type="text" name="card_number" placeholder="Card Number" required maxlength="16" />
      <input type="text" name="cvv" placeholder="CVV" required maxlength="3" />

      <input type="hidden" name="homestay_id" value="<?= htmlspecialchars($_POST['homestay_id'] ?? '') ?>">
      <input type="hidden" name="checkin" value="<?= htmlspecialchars($_POST['checkin'] ?? '') ?>">
      <input type="hidden" name="checkout" value="<?= htmlspecialchars($_POST['checkout'] ?? '') ?>">
      <input type="hidden" name="guests" value="<?= htmlspecialchars($_POST['guests'] ?? '') ?>">
      <input type="hidden" name="total_price" value="<?= htmlspecialchars($_POST['total_price'] ?? '') ?>">

      <button type="submit" class="pay" id="pay-button">Pay</button>
      <button type="button" class="cancel" onclick="window.history.back();">Cancel</button>
    </form>
  </div>
  <div class="right"></div>
</div>
<script src="../../JS/Guest/PaymentGateaway.js?v=1"></script>
</body>
</html>
