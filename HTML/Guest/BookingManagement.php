<?php
session_start();
include_once '../../connect.php';

date_default_timezone_set('Asia/Kuala_Lumpur');

if (!isset($_SESSION['guest_id'])) {
    header("Location: ../../HTML/Home/login.php");
    exit();
}

$guest_id = $_SESSION['guest_id'];
$today = date('Y-m-d');

$update_sql = "UPDATE booking SET booking_status = 2 WHERE guest_id = ? AND booking_status = 1 AND DATE(check_out_date) < ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("ss", $guest_id, $today);
$update_stmt->execute();

$update_begin_sql = "UPDATE booking SET booking_status = 4 WHERE guest_id = ? AND booking_status = 1 AND DATE(check_in_date) = ?";
$update_begin_stmt = $conn->prepare($update_begin_sql);
$update_begin_stmt->bind_param("ss", $guest_id, $today);
$update_begin_stmt->execute();

$update_stay_sql = "UPDATE booking SET booking_status = 2 WHERE guest_id = ? AND booking_status = 4 AND DATE(check_out_date) < ?";
$update_stay_stmt = $conn->prepare($update_stay_sql);
$update_stay_stmt->bind_param("ss", $guest_id, $today);
$update_stay_stmt->execute();

$sql = "SELECT b.*, h.title, p.amount FROM booking b 
        JOIN homestay h ON b.homestay_id = h.homestay_id 
        LEFT JOIN payment p ON b.booking_id = p.booking_id 
        WHERE b.guest_id = ? ORDER BY b.booking_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $guest_id);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}

function getStatusText($status) {
    switch ($status) {
        case 0: return "Pending";
        case 1: return "Confirmed";
        case 2: return "Completed";
        case 3: return "Cancelled";
        case 4: return "Stay Begun";
        default: return "Unknown";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Booking Management - Guest</title>
  <link rel="stylesheet" href="css/BookingManagement.css" />
  <link rel="stylesheet" href="css/GuestHeader.css">
  <link rel="stylesheet" href="../../include/css/footer.css">
  <style>
    html, body {
      height: 100%;
      margin: 0;
      padding: 0;
    }
    .page-container {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    .main-container {
      flex: 1;
    }
  </style>
</head>
<body>
<div class="page-container">
  <?php include('../../HTML/Guest/GuestHeader.php'); ?>

  <div class="main-container">
    <div class="booking-management">
      <h2>My Booking Management</h2>
      <table class="booking-table">
        <thead>
          <tr>
            <th>No</th>
            <th>Property Name</th>
            <th>Booking Date</th>
            <th>Check-in</th>
            <th>Check-out</th>
            <th>Payment Info</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
        <?php
        if (count($bookings) === 0) {
            echo "<tr><td colspan='8'>No bookings found.</td></tr>";
        } else {
            $count = 1;
            foreach ($bookings as $booking) {
                $can_cancel = false;
                $booking_time = new DateTime($booking['booking_date'], new DateTimeZone('Asia/Kuala_Lumpur'));
                $now = new DateTime('now', new DateTimeZone('Asia/Kuala_Lumpur'));
                $diff_seconds = $now->getTimestamp() - $booking_time->getTimestamp();
                $hours_diff = $diff_seconds / 3600;

                if ($hours_diff >= 0 && $hours_diff <= 1 && $booking['booking_status'] == 1) {
                    $can_cancel = true;
                }

                $payment_info = $booking['amount'] !== null
                    ? "Paid - RM " . number_format($booking['amount'], 2)
                    : "Pending";

                $status_text = getStatusText($booking['booking_status']);

                echo "<tr>";
                echo "<td>{$count}</td>";
                echo "<td>" . htmlspecialchars($booking['title']) . "</td>";
                echo "<td>{$booking['booking_date']}</td>";
                echo "<td>{$booking['check_in_date']}</td>";
                echo "<td>{$booking['check_out_date']}</td>";
                echo "<td>{$payment_info}</td>";
                echo "<td>{$status_text}</td>";
                echo "<td><div style='display:flex;gap:5px;'>";

                echo "<a href='ViewPropertyDetail.php?homestay_id={$booking['homestay_id']}&viewonly=1&from=bookings'>";
                echo "<button type='button' class='action-btn btn-view'>View Info</button>";
                echo "</a>";

                if ($booking['amount'] !== null && $booking['booking_status'] != 3) {
                    echo "<a href='PrintReceipt.php?booking_id={$booking['booking_id']}' target='_blank'>";
                    echo "<button type='button' class='action-btn btn-print'>Print Receipt</button>";
                    echo "</a>";
                }

                if ($can_cancel) {
                    echo "<form action='CancelBooking.php' method='post' onsubmit='return confirm(\"Are you sure you want to cancel this booking?\");'>";
                    echo "<input type='hidden' name='booking_id' value='{$booking['booking_id']}'>";
                    echo "<button type='submit' class='action-btn btn-cancel'>Cancel</button>";
                    echo "</form>";
                }

                if ($booking['booking_status'] == 2) {
                    echo "<a href='SubmitReview.php?booking_id={$booking['booking_id']}'>";
                    echo "<button type='button' class='action-btn btn-review'>Review</button>";
                    echo "</a>";
                }

                echo "</div></td>";
                echo "</tr>";
                $count++;
            }
        }
        ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php include "../../include/footer.html"; ?>
</div>

<script src="js/SearchHandler.js?v=1"></script>
</body>
</html>
