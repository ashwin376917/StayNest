<?php
session_start();
include_once '../../connect.php';

// Ensure guest is logged in
if (!isset($_SESSION['guest_id'])) {
    header("Location: ../../HTML/Home/login.php");
    exit();
}

$guest_id = $_SESSION['guest_id'];

// Auto-update bookings to 'Completed' if check-out date has passed
$update_sql = "UPDATE booking SET booking_status = 2 WHERE guest_id = ? AND booking_status = 1 AND check_out_date < CURDATE()";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("s", $guest_id);
$update_stmt->execute();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Dashboard - Guest</title>
    <link rel="stylesheet" href="css/GuestHeader.css" />
    <link rel="stylesheet" href="css/GuestDashboard.css?v=7" />
    <link rel="stylesheet" href="../../include/css/footer.css" />
</head>
<body>
<header>
    <?php include('GuestHeader.php'); ?>
</header>

<div class="main-container">
    <div class="outer-wrapper">

        <div class="summary-row">
            <div class="summary-box">
                <div class="summary-number">
                    <?php
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM booking WHERE guest_id = ?");
                    $stmt->bind_param("s", $guest_id);
                    $stmt->execute();
                    $stmt->bind_result($total_orders);
                    $stmt->fetch();
                    echo $total_orders;
                    $stmt->close();
                    ?>
                </div>
                <div class="summary-content">
                    <img src="../../assets/MyBooking/order_icon.png" alt="Orders">
                    <span>Total Orders</span>
                </div>
            </div>

            <div class="summary-box">
                <div class="summary-number">
                    <?php
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM review WHERE guest_id = ?");
                    $stmt->bind_param("s", $guest_id);
                    $stmt->execute();
                    $stmt->bind_result($total_reviews);
                    $stmt->fetch();
                    echo $total_reviews;
                    $stmt->close();
                    ?>
                </div>
                <div class="summary-content">
                    <img src="../../assets/MyBooking/review_icon.png" alt="Reviews">
                    <span>Total Reviews</span>
                </div>
            </div>
        </div> <div class="layer-two">
            <div class="recent-orders">
                <h2>Recent Orders</h2>
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Property Name</th>
                            <th>Date Booked</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $conn->prepare("SELECT b.booking_id, h.title, b.booking_date, b.check_out_date, b.booking_status 
                                                FROM booking b 
                                                JOIN homestay h ON b.homestay_id = h.homestay_id 
                                                WHERE b.guest_id = ? 
                                                ORDER BY b.booking_date DESC");
                        $stmt->bind_param("s", $guest_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $no = 1;
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $no++ . "</td>";
                            echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                            echo "<td>" . $row['booking_date'] . "</td>";
                            echo "<td>";
                            if ($row['booking_status'] == 3) {
                                echo "Cancelled";
                            } elseif ($row['booking_status'] == 2 || $row['check_out_date'] < date("Y-m-d")) {
                                // Added a check here to ensure if it's status 1 but check_out_date is past
                                if ($row['booking_status'] == 1 && $row['check_out_date'] < date("Y-m-d")) {
                                    echo "Completed"; // If status is 1 but date is past, display as Completed
                                } else {
                                    echo "Completed"; // Status 2 is always Completed
                                }
                            } elseif ($row['booking_status'] == 1) {
                                echo "Confirmed";
                            } else {
                                echo "Pending";
                            }
                            echo "</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <div class="recent-reviews">
                <h2>Recent Reviews</h2>
                <?php
                $stmt = $conn->prepare("SELECT r.rating, r.comment, r.review_date, h.title AS property_name 
                                        FROM review r 
                                        JOIN booking b ON r.booking_id = b.booking_id 
                                        JOIN homestay h ON b.homestay_id = h.homestay_id 
                                        WHERE r.guest_id = ? 
                                        ORDER BY r.review_date DESC 
                                        LIMIT 5");
                $stmt->bind_param("s", $guest_id);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    echo "<div class='review-box'>";
                    for ($i = 1; $i <= 5; $i++) {
                        echo $i <= $row['rating'] ? "⭐" : "☆";
                    }
                    echo "<p><strong>" . htmlspecialchars($row['property_name']) . "</strong></p>";
                    echo "<p>" . htmlspecialchars($row['comment']) . "</p>";
                    echo "<p><em>" . $row['review_date'] . "</em></p>";
                    echo "</div>";
                }
                ?>
            </div>
        </div>

        </div>
</div>

<script src="../../JS/Guest/SearchHandler.js?v=1"></script>
<?php include "../../include/footer.html"; ?>
</body>
</html>