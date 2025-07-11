<?php
session_start();
include_once '../../connect.php'; // Ensure this path is correct for your setup

// Set the timezone to Malaysia (Asia/Kuala_Lumpur)
date_default_timezone_set('Asia/Kuala_Lumpur');

// Redirect if guest is not logged in
if (!isset($_SESSION['guest_id'])) {
    header("Location: ../../HTML/Home/login.php");
    exit();
}

// Check if booking_id is provided in the URL
if (!isset($_GET['booking_id'])) {
    die("Booking ID is missing.");
}

$booking_id = $_GET['booking_id'];

// MODIFIED SQL query:
// - Added JOIN guest g ON b.guest_id = g.guest_id
// - Added g.guest_name to the SELECT clause
$sql = "SELECT b.*, h.title, h.district, h.state, p.amount, p.payment_date, p.payment_id, g.guest_name
        FROM booking b
        JOIN homestay h ON b.homestay_id = h.homestay_id
        LEFT JOIN payment p ON b.booking_id = p.booking_id
        JOIN guest g ON b.guest_id = g.guest_id  -- Joined guest table
        WHERE b.booking_id = ?";

$stmt = $conn->prepare($sql);

// Check if the prepare statement was successful
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("s", $booking_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

// Check if booking was found
if (!$booking) {
    die("Booking not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>StayNest Official Booking Receipt</title>
    <style>
        /* Styles for print and screen display */
        @page {
            size: A4 portrait;
            margin: 20mm 10mm 20mm 10mm;
        }

        body {
            font-family: Arial, sans-serif;
            width: 210mm; /* A4 width */
            min-height: 297mm; /* A4 height */
            margin: 0 auto;
            padding: 0;
            background: #fff;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
        }

        .receipt-container {
            width: 180mm; /* Slightly less than body width for padding/margin */
            padding: 10mm;
            border: 1px solid #000;
            box-sizing: border-box;
            margin-top: 25mm;
            margin-bottom: 10mm;
            page-break-after: avoid; /* Prevent page break inside the receipt */
        }

        .header {
            text-align: center;
            border-bottom: 1px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .header img {
            max-height: 50px;
        }

        h1 {
            margin: 10px 0 5px 0;
            font-size: 22px;
        }

        .receipt-section {
            margin-bottom: 10px;
        }

        .label {
            font-weight: bold;
            display: inline-block;
            width: 160px; /* Aligns labels */
        }

        .details-box {
            border: 1px solid #000;
            padding: 10px;
            margin-bottom: 15px;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 11px;
        }

        .print-btn {
            text-align: center;
            margin-top: 15px;
        }

        button {
            padding: 8px 18px;
            font-size: 14px;
            cursor: pointer;
        }

        /* Print-specific styles */
        @media print {
            .print-btn {
                display: none; /* Hide print button when printing */
            }
            body {
                border: none;
                margin: 0;
                padding: 0;
            }
            .receipt-container {
                border: none; /* Remove border in print for cleaner look */
                margin: 0;
                page-break-after: avoid;
            }
        }
    </style>
</head>
<body>

<div class="receipt-container">
    <div class="header">
        <img src="../../assets/staynest_logo.png" alt="StayNest Logo">
        <h1>StayNest Official Receipt</h1>
        <p>Thank you for booking with us!</p>
    </div>

    <div class="details-box">
        <div class="receipt-section"><span class="label">Booking ID:</span> <?= htmlspecialchars($booking['booking_id']) ?></div>
        <div class="receipt-section"><span class="label">Payment ID:</span> <?= htmlspecialchars($booking['payment_id'] ?? '-') ?></div>
        <div class="receipt-section"><span class="label">Guest Name:</span> <?= htmlspecialchars($booking['guest_name']) ?></div>
        <div class="receipt-section"><span class="label">Homestay:</span> <?= htmlspecialchars($booking['title']) ?></div>
        <div class="receipt-section"><span class="label">Address:</span> <?= htmlspecialchars($booking['district'] . ', ' . $booking['state']) ?></div>
        <div class="receipt-section"><span class="label">Check-in Date:</span> <?= htmlspecialchars($booking['check_in_date']) ?></div>
        <div class="receipt-section"><span class="label">Check-out Date:</span> <?= htmlspecialchars($booking['check_out_date']) ?></div>
        <div class="receipt-section"><span class="label">Total Guests:</span> <?= htmlspecialchars($booking['guest_count'] ?? '-') ?></div>
    </div>

    <div class="details-box">
    <?php if ($booking['amount'] !== null) : ?>
        <div class="receipt-section"><span class="label">Payment Amount:</span> RM <?= number_format($booking['amount'], 2) ?></div>
        <div class="receipt-section"><span class="label">Payment Date:</span> <?= htmlspecialchars($booking['payment_date']) ?></div>
    <?php else : ?>
        <div class="receipt-section"><span class="label">Payment:</span> Pending</div>
    <?php endif; ?>
    </div>

    <div class="footer">
        <p>This is a computer-generated receipt. No signature required.</p>
        <p>StayNest Homestay Booking &copy; <?= date('Y') ?></p>
    </div>

    <div class="print-btn">
        <button onclick="window.print()">Print Receipt</button>
    </div>
</div>

</body>
</html>