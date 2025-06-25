<?php
session_start();
include_once '../../connect.php';
date_default_timezone_set('Asia/Kuala_Lumpur');

if (!isset($_SESSION['guest_id'])) {
    header("Location: ../../HTML/Home/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'])) {
    $booking_id = $_POST['booking_id'];
    $guest_id = $_SESSION['guest_id'];

    // Verify booking belongs to logged-in guest and status is still confirmed
    $check_sql = "SELECT booking_date, booking_status FROM booking WHERE booking_id = ? AND guest_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ss", $booking_id, $guest_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows === 0) {
        die("Invalid booking or unauthorized access.");
    }

    $booking = $result->fetch_assoc();

    if ($booking['booking_status'] != 1) { // Only allow cancel if status is Confirmed
        die("This booking cannot be cancelled.");
    }

    // Check time difference (allow within 1 hour)
    $booking_time = new DateTime($booking['booking_date'], new DateTimeZone('Asia/Kuala_Lumpur'));
    $now = new DateTime('now', new DateTimeZone('Asia/Kuala_Lumpur'));
    $diff_seconds = $now->getTimestamp() - $booking_time->getTimestamp();
    $hours_diff = $diff_seconds / 3600;

    if ($hours_diff < 0 || $hours_diff > 1) {
        die("Booking can only be cancelled within 1 hour of booking.");
    }

    // Update status to Cancelled (status = 3)
    $update_sql = "UPDATE booking SET booking_status = 3 WHERE booking_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("s", $booking_id);
    $update_stmt->execute();

    header("Location: BookingManagement.php");
    exit();
} else {
    die("Invalid request.");
}
?>
