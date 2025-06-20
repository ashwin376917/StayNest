<?php
session_start();
include("../../connect.php");

// Dummy booking ID (replace with real booking ID later)
$bookingID = "B001";

// Process payment on POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $method = $_POST['payment_method'] ?? '';
    $card = preg_replace('/\D/', '', $_POST['card_number'] ?? '');
    $cvv = preg_replace('/\D/', '', $_POST['cvv'] ?? '');
    $amount = $_POST['amount'] ?? 100.00; // Replace with actual value
    $date = date("Y-m-d");
    $status = 1;

    if (strlen($card) !== 16 || strlen($cvv) !== 3) {
        header("Location: PaymentFailed.html");
        exit();
    }

    $payment_id = 'P' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

    $sql = "INSERT INTO PAYMENT (payment_id, booking_id, payment_date, amount, payment_status)
            VALUES ('$payment_id', '$bookingID', '$date', '$amount', '$status')";

    if (mysqli_query($conn, $sql)) {
        header("Location: PaymentSuccess.html");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>StayNest | Payment Gateway</title>
  <link rel="stylesheet" href="../../CSS/Guest/PaymentGateaway.css" />
</head>
<body>
  <div class="container">
    <div class="left">
      <div class="top-bar">
        <div class="logo">
          <img src="../../assets/staynest_logo.png" alt="StayNest Logo" />
          <span>Payment - Guest</span>
        </div>
      </div>

      <form class="form-box" method="post" action="">
        <h2>Payment Method</h2>

        <select name="payment_method" required>
          <option value="">-- Select Payment Method --</option>
          <option value="card">Debit / Credit Card</option>
          <option value="fpx">FPX</option>
          <option value="ewallet">E-Wallet</option>
        </select>

        <input type="text" name="card_number" placeholder="Card Number" required maxlength="16" />
        <input type="text" name="cvv" placeholder="CVV" required maxlength="3" />
        <input type="hidden" name="amount" value="100.00" /> <!-- example amount -->

        <button type="submit">Pay</button>
      </form>
    </div>

    <div class="right"></div>
  </div>
</body>
</html>
