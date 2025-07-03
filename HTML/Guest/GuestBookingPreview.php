<?php
session_start();
include_once '../../connect.php';

$homestay_id = trim($_GET['homestay_id'] ?? '');
$checkin_str = trim($_GET['checkin'] ?? '');
$checkout_str = trim($_GET['checkout'] ?? '');
$guest_count = intval($_GET['guests'] ?? 0);

if (empty($homestay_id) || empty($checkin_str) || empty($checkout_str) || $guest_count <= 0) {
    header("Location: ViewPropertyDetail.php?homestay_id=" . urlencode($homestay_id) . "&error=missing_data");
    exit();
}

$sql_homestay = "SELECT title, price_per_night, max_guests FROM homestay WHERE homestay_id = ?";
$stmt_homestay = $conn->prepare($sql_homestay);
if (!$stmt_homestay) {
    error_log("Prepare failed for homestay fetch: " . $conn->error);
    header("Location: ViewPropertyDetail.php?homestay_id=" . urlencode($homestay_id) . "&error=db_error");
    exit();
}
$stmt_homestay->bind_param("s", $homestay_id);
$stmt_homestay->execute();
$result_homestay = $stmt_homestay->get_result();
$homestay = $result_homestay->fetch_assoc();
$stmt_homestay->close();

if (!$homestay) {
    header("Location: ViewPropertyDetail.php?homestay_id=" . urlencode($homestay_id) . "&error=homestay_not_found");
    exit();
}

$checkin_dt = DateTime::createFromFormat('Y-m-d', $checkin_str);
$checkout_dt = DateTime::createFromFormat('Y-m-d', $checkout_str);

if (!$checkin_dt || !$checkout_dt) {
    header("Location: ViewPropertyDetail.php?homestay_id=" . urlencode($homestay_id) . "&error=invalid_date_format");
    exit();
}

$today_dt = new DateTime(date('Y-m-d'));
$tomorrow_dt = (clone $today_dt)->modify('+1 day');

if ($checkin_dt < $tomorrow_dt) {
    header("Location: ViewPropertyDetail.php?homestay_id=" . urlencode($homestay_id) . "&error=checkin_past");
    exit();
}

if ($checkout_dt <= $checkin_dt) {
    header("Location: ViewPropertyDetail.php?homestay_id=" . urlencode($homestay_id) . "&error=invalid_dates");
    exit();
}

$interval_check = $checkin_dt->diff($checkout_dt);
if ($interval_check->days > 15) {
    header("Location: ViewPropertyDetail.php?homestay_id=" . urlencode($homestay_id) . "&error=booking_too_long");
    exit();
}

if ($guest_count > $homestay['max_guests']) {
    header("Location: ViewPropertyDetail.php?homestay_id=" . urlencode($homestay_id) . "&error=guest_capacity");
    exit();
}

$sql_check_availability = "
    SELECT COUNT(*) AS count 
    FROM booking 
    WHERE homestay_id = ? 
    AND booking_status = 1
    AND (
        (check_in_date < ? AND check_out_date > ?)
    )
";

$stmt_availability = $conn->prepare($sql_check_availability);
if (!$stmt_availability) {
    error_log("Prepare failed for availability check: " . $conn->error);
    header("Location: ViewPropertyDetail.php?homestay_id=" . urlencode($homestay_id) . "&error=db_error");
    exit();
}

$stmt_availability->bind_param("sss", $homestay_id, $checkout_str, $checkin_str);
$stmt_availability->execute();
$result_availability = $stmt_availability->get_result();
$row_availability = $result_availability->fetch_assoc();
$stmt_availability->close();

if ($row_availability['count'] > 0) {
    header("Location: ViewPropertyDetail.php?homestay_id=" . urlencode($homestay_id) . "&error=unavailable");
    exit();
}

$nights = $checkin_dt->diff($checkout_dt)->days;
$total = $nights * $homestay['price_per_night'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proceed_to_payment'])) {
    $_SESSION['can_access_payment'] = true;
    $_SESSION['booking_details'] = [
        'homestay_id' => $homestay_id,
        'checkin' => $checkin_str,
        'checkout' => $checkout_str,
        'guests' => $guest_count,
        'nights' => $nights,
        'total_price' => $total,
        'homestay_title' => $homestay['title'],
    ];

    header("Location: PaymentGateaway.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Booking Summary</title>
    <link rel="stylesheet" href="css/GuestBookingPreview.css">
    <link rel="stylesheet" href="../Home/css/homeheadersheet.css">
</head>
<body>
<div class="container">
    <div class="left">
        <div class="top-bar">
            <img src="../../assets/staynest_logo.png" alt="StayNest Logo">
            <span class="WebName">StayNest</span>
        </div>

        <div class="form-box">
            <h2>Booking Summary</h2>
            <p><strong>Homestay :</strong> <?= htmlspecialchars($homestay['title']) ?></p>
            <p><strong>Guests :</strong> <?= htmlspecialchars($guest_count) ?></p>
            <p><strong>Check-in :</strong> <?= htmlspecialchars($checkin_str) ?></p>
            <p><strong>Check-out :</strong> <?= htmlspecialchars($checkout_str) ?></p>
            <p><strong>Nights :</strong> <?= $nights ?></p>
            <p><strong>Total Price :</strong> RM <?= number_format($total, 2) ?></p>

            <form method="post">
                <button type="submit" class="pay" name="proceed_to_payment">Confirm & Pay</button>
            </form>

            <button class="cancel" onclick="window.location.href='ViewPropertyDetail.php?homestay_id=<?= urlencode($homestay_id) ?>'">Cancel</button>
        </div>
    </div>

    <div class="right"></div>
</div>
</body>
</html>
