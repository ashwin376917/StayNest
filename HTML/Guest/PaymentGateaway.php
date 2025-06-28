<?php
session_start();
include("../../connect.php");

// Security check: Ensure user is logged in and has access to payment page
if (!isset($_SESSION['guest_id']) || !isset($_SESSION['booking_details']) || !isset($_SESSION['can_access_payment']) || !$_SESSION['can_access_payment']) {
    header("Location: GuestDashboard.php");
    exit();
}

// Retrieve booking details from session
$booking_details = $_SESSION['booking_details'];
$homestay_id = $booking_details['homestay_id'];
$checkin = $booking_details['checkin'];
$checkout = $booking_details['checkout'];
$guests = $booking_details['guests'];
$total_price = $booking_details['total_price'];
$user_id = $_SESSION['guest_id'];

// Handle POST request for payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $method = $_POST['payment_method'] ?? '';
    $raw_card_number = $_POST['card_number'] ?? '';
    $raw_cvv = $_POST['cvv'] ?? '';

    // Server-side validation
    if (!ctype_digit($raw_card_number) || strlen($raw_card_number) !== 16 || !ctype_digit($raw_cvv) || strlen($raw_cvv) !== 3) {
        header("Location: PaymentFailed.php?homestay_id=" . urlencode($homestay_id) . "&checkin=" . urlencode($checkin) . "&checkout=" . urlencode($checkout) . "&guests=" . urlencode($guests));
        $_SESSION['can_access_payment'] = true;
        exit();
    }

    $card = preg_replace('/\D/', '', $raw_card_number);
    $cvv = preg_replace('/\D/', '', $raw_cvv);

    $booking_id = 'B' . substr(uniqid(), -9);
    $payment_id = 'P' . substr(uniqid(), -9);
    $current_datetime = date("Y-m-d H:i:s");

    $conn->begin_transaction();

    try {
        $booking_sql = "INSERT INTO booking (booking_id, guest_id, homestay_id, booking_date, check_in_date, check_out_date, booking_status, guest_count)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_booking = $conn->prepare($booking_sql);
        if (!$stmt_booking) {
            throw new Exception("Booking statement preparation failed: " . $conn->error);
        }
        $booking_status = 1;
        $stmt_booking->bind_param("ssssssii", $booking_id, $user_id, $homestay_id, $current_datetime, $checkin, $checkout, $booking_status, $guests);
        $stmt_booking->execute();
        $stmt_booking->close();

        $payment_sql = "INSERT INTO payment (payment_id, booking_id, payment_date, amount)
                        VALUES (?, ?, ?, ?)";
        $stmt_payment = $conn->prepare($payment_sql);
        if (!$stmt_payment) {
            throw new Exception("Payment statement preparation failed: " . $conn->error);
        }
        $amount_float = (float)$total_price;
        $stmt_payment->bind_param("sssd", $payment_id, $booking_id, $current_datetime, $amount_float);
        $stmt_payment->execute();
        $stmt_payment->close();

        $conn->commit();

        unset($_SESSION['booking_details']);
        unset($_SESSION['can_access_payment']);

        header("Location: PaymentSuccess.html");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Payment/Booking failed: " . $e->getMessage());
        header("Location: PaymentFailed.php?homestay_id=" . urlencode($homestay_id) . "&checkin=" . urlencode($checkin) . "&checkout=" . urlencode($checkout) . "&guests=" . urlencode($guests));
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>StayNest | Payment Gateway</title>
    <link rel="stylesheet" href="../../HTML/Guest/css/PaymentGateaway.css?v4">
</head>
<body>
<div class="container">
    <div class="left">
        <div class="top-bar">
            <img src="../../assets/staynest_logo.png" alt="StayNest Logo">
            <span class="WebName">StayNest</span>
        </div>

        <form class="form-box" method="post" action="">
            <h2>Payment Method</h2>

            <p><strong>Homestay:</strong> <?= htmlspecialchars($booking_details['homestay_title'] ?? 'N/A') ?></p>
            <p><strong>Check-in:</strong> <?= htmlspecialchars($booking_details['checkin'] ?? 'N/A') ?></p>
            <p><strong>Check-out:</strong> <?= htmlspecialchars($booking_details['checkout'] ?? 'N/A') ?></p>
            <p><strong>Guests:</strong> <?= htmlspecialchars($booking_details['guests'] ?? 'N/A') ?></p>
            <p><strong>Total Price:</strong> RM <?= number_format($booking_details['total_price'] ?? 0, 2) ?></p>
            <br>

            <select name="payment_method" required>
                <option value="">-- Select Payment Method --</option>
                <option value="card">Debit / Credit Card</option>
            </select>

            <input type="text" name="card_number" placeholder="Card Number (16 digits)" required maxlength="16">
            <input type="text" name="cvv" placeholder="CVV (3 digits)" required maxlength="3">

            <button type="submit" class="pay" id="pay-button">Pay</button>
            <button type="button" class="cancel" onclick="window.history.back();">Cancel</button>
        </form>
    </div>
    <div class="right"></div>
</div>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const payButton = document.getElementById("pay-button");

    if (payButton) {
        payButton.addEventListener("click", function (e) {
            const card = document.querySelector('input[name="card_number"]').value.trim();
            const cvv = document.querySelector('input[name="cvv"]').value.trim();
            const method = document.querySelector('select[name="payment_method"]').value;

            const errors = [];

            if (method === "") {
                errors.push("Please select a payment method.");
            }
            if (card.length !== 16) {
                errors.push("Card number must be exactly 16 characters long.");
            }
            if (cvv.length !== 3) {
                errors.push("CVV must be exactly 3 characters long.");
            }

            if (errors.length > 0) {
                e.preventDefault();
                alert("Payment Failed:\n" + errors.join("\n"));
            }
        });
    }
});
</script>
</body>
</html>
