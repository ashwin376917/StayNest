<?php
session_start();
include("../../connect.php"); // Adjust path as necessary

// Redirect if not logged in as a guest or host
if (!isset($_SESSION['guest_id'])) {
    header("Location: ../Guest/login.php");
    exit();
}

$loggedInGuestId = $_SESSION['guest_id'];
$hostId = null;
$hostApprovalStatus = 0;

// Fetch host_id and approval status
$stmt = $conn->prepare("SELECT host_id, isApprove FROM host WHERE guest_id = ?");
if ($stmt === false) {
    error_log("Prepare statement failed (host approval check): " . $conn->error);
    echo "<script>alert('Database error during host status check.'); window.location.href = '../../HTML/Guest/AfterLoginHomepage.php';</script>";
    exit();
}
$stmt->bind_param("s", $loggedInGuestId);
$stmt->execute();
$stmt->bind_result($hostId, $hostApprovalStatus);
$stmt->fetch();
$stmt->close();

// If not an approved host, redirect or show a message
if (is_null($hostId) || $hostApprovalStatus != 2) {
    // You might want a more user-friendly redirect or message here
    header("Location: ViewNest.php"); // Redirect to ViewNest if not an approved host
    exit();
}

// Initialize analytics data
$totalEarnings = 0;
$totalGuests = 0;
$averageRating = "N/A"; // Default to N/A if no rating data is available

// --- Calculate Total Earnings and Total Guests ---
// Sum of price_per_night from homestay table, multiplied by number of nights
// Sum of guest_count from booking table
$earningsGuestsQuery = "
    SELECT 
        SUM(h.price_per_night * DATEDIFF(b.check_out_date, b.check_in_date)) AS total_earnings,
        SUM(b.guest_count) AS total_guests
    FROM booking b
    JOIN homestay h ON b.homestay_id = h.homestay_id
    WHERE h.host_id = ? AND b.booking_status = 1
"; // Changed b.booking_status = 'completed' to b.booking_status = 1 based on tinyint(1) type in schema

$stmt = $conn->prepare($earningsGuestsQuery);
if ($stmt === false) {
    error_log("Prepare statement failed (earnings/guests): " . $conn->error);
} else {
    $stmt->bind_param("s", $hostId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $totalEarnings = $row['total_earnings'] ?? 0;
        $totalGuests = $row['total_guests'] ?? 0;
    }
    $stmt->close();
}

// --- Calculate Average Rating ---
// Fetch rating from the 'review' table (assuming singular name), joining through 'booking' and 'homestay'
$ratingQuery = "
    SELECT AVG(r.rating) AS avg_rating
    FROM review r  -- Changed from 'reviews' to 'review'
    JOIN booking b ON r.booking_id = b.booking_id
    JOIN homestay h ON b.homestay_id = h.homestay_id
    WHERE h.host_id = ?
";

$stmt = $conn->prepare($ratingQuery);
if ($stmt === false) {
    error_log("Prepare statement failed (average rating): " . $conn->error);
} else {
    $stmt->bind_param("s", $hostId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if (!is_null($row['avg_rating'])) {
            $averageRating = number_format($row['avg_rating'], 2); // Format to 2 decimal places
        }
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Host Analytics - StayNest</title>
    <link rel="stylesheet" href="../../include/css/footer.css">
    <link rel="stylesheet" href="css/hostheadersheet.css"/>
    <link rel="stylesheet" href="css/hostanalytics.css"/>
</head>
<body>
    <?php include 'hostheader.html'; // Adjust path as necessary ?>

    <div class="container">
        <div class="left">
            <div class="host-content">
                <h1 class="dashboard-title">Host Analytics Dashboard</h1>

                <div class="analytics-grid">
                    <div class="analytics-card">
                        <h2>Total Earnings</h2>
                        <p class="metric">RM <?= number_format($totalEarnings, 2) ?></p>
                    </div>
                    <div class="analytics-card">
                        <h2>Total Guests</h2>
                        <p class="metric"><?= number_format($totalGuests) ?></p>
                    </div>
                    <div class="analytics-card">
                        <h2>Average Rating</h2>
                        <p class="metric"><?= $averageRating ?> / 5</p>
                        <p class="note">(Calculated from guest reviews)</p>
                    </div>
                </div>

                <div class="info-section">
                    <h3>How Analytics are Calculated:</h3>
                    <ul>
                        <li><strong>Total Earnings:</strong> Sum of (price per night * number of nights) for all bookings marked as 'completed' for your homestays.</li>
                        <li><strong>Total Guests:</strong> Sum of 'guest_count' for all bookings marked as 'completed' for your homestays.</li>
                        <li><strong>Average Rating:</strong> Average rating from all reviews submitted for your homestays.</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="right"></div>
    </div>

    <?php include "../../include/Footer.html"; // Adjust path as necessary ?>
</body>
</html>
