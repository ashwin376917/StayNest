<?php
session_start();
include("../../connect.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['card_number']) && isset($_POST['cvv'])) {

    // Step 1: Collect form data
    $method = $_POST['payment_method'] ?? '';
    $card = preg_replace('/\D/', '', $_POST['card_number'] ?? '');
    $cvv = preg_replace('/\D/', '', $_POST['cvv'] ?? '');
    $amount = $_POST['total_price'] ?? 100.00;
    $checkin = $_POST['checkin'];
    $checkout = $_POST['checkout']; 
    $homestay_id = $_POST['homestay_id'];
    $guests = $_POST['guests'];
    $user_id = $_SESSION['guest_id']; // Make sure session has guest_id
    $today = date("Y-m-d");

    // Step 2: Validate
    if (strlen($card) !== 16 || strlen($cvv) !== 3) {
        header("Location: PaymentFailed.html");
        exit();
    }

    // Step 3: Create booking
    
    $booking_id = 'B' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    $booking_sql = "INSERT INTO BOOKING (booking_id, guest_id, homestay_id, booking_date, check_in_date, check_out_date, booking_status)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($booking_sql);
    $booking_status = 1;
    $stmt->bind_param("ssssssi", $booking_id, $user_id, $homestay_id, $today, $checkin, $checkout, $booking_status);
    $stmt->execute();

    // Step 4: Create payment
    $payment_id = 'P' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    $payment_sql = "INSERT INTO PAYMENT (payment_id, booking_id, payment_date, amount, payment_status)
                    VALUES (?, ?, ?, ?, ?)";
    $stmt2 = $conn->prepare($payment_sql);
    $payment_status = 1;
    $stmt2->bind_param("sssdi", $payment_id, $booking_id, $today, $amount, $payment_status);
    $stmt2->execute();

    // Step 5: Redirect
    header("Location: PaymentSuccess.html");
    exit();
}
?>
   

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>StayNest | Payment Gateway</title>
  <link rel="stylesheet" href="../../CSS/Guest/PaymentGateaway.css?v7" />
</head>
<body>
  <div class="container">
    <div class="left">
    <div class="top-bar">
      <img src="../../assets/staynest_logo.png" alt="StayNest Logo" />
      <span class="WebName">Payment</span>
    </div>


      <form class="form-box" method="post" action="">
        <h2>Payment Method</h2>

        <select name="payment_method" required>
          <option value="">-- Select Payment Method --</option>
          <option value="card">Debit / Credit Card</option>
          <!-- <option value="fpx">FPX</option>
          <option value="ewallet">E-Wallet</option> -->
        </select>

        <input type="text" name="card_number" placeholder="Card Number" required maxlength="16" />
        <input type="text" name="cvv" placeholder="CVV" required maxlength="3" />

        <input type="hidden" name="homestay_id" value="<?= isset($_POST['homestay_id']) ? htmlspecialchars($_POST['homestay_id']) : '' ?>">
        <input type="hidden" name="checkin" value="<?= isset($_POST['checkin']) ? htmlspecialchars($_POST['checkin']) : '' ?>">
        <input type="hidden" name="checkout" value="<?= isset($_POST['checkout']) ? htmlspecialchars($_POST['checkout']) : '' ?>">
        <input type="hidden" name="guests" value="<?= isset($_POST['guests']) ? htmlspecialchars($_POST['guests']) : '' ?>">
        <input type="hidden" name="total_price" value="<?= isset($_POST['total_price']) ? htmlspecialchars($_POST['total_price']) : '' ?>">


        <button type="button" class="pay" id="pay-button" class="pay">Pay</button>
        <button type="button" class="cancel" onclick="window.history.back();">Cancel</button>

      </form>
    </div>

    <div class="right"></div>
  </div>
  <script src="../../JS/Guest/PaymentGateaway.js?v=1"></script>

</body>
</html>
