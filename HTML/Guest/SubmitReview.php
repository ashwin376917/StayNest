<?php
session_start();
include_once '../../connect.php'; // Ensure this path is correct for your setup

date_default_timezone_set('Asia/Kuala_Lumpur'); // Set timezone to Malaysia

// Check if guest is logged in, redirect if not
if (!isset($_SESSION['guest_id'])) {
    header("Location: ../../HTML/Home/login.php");
    exit();
}

$guest_id = $_SESSION['guest_id'];

// Check if booking_id is provided in the URL
if (!isset($_GET['booking_id'])) {
    echo "<script>alert('Missing booking ID.'); window.location.href='BookingManagement.php';</script>";
    exit();
}

$booking_id = $_GET['booking_id'];

// Validate that the booking belongs to the logged-in guest
$sql = "SELECT b.booking_id, h.title FROM booking b
        JOIN homestay h ON b.homestay_id = h.homestay_id
        WHERE b.booking_id = ? AND b.guest_id = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("ss", $booking_id, $guest_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

if (!$booking) {
    echo "<script>alert('Invalid booking or unauthorized access.'); window.location.href='BookingManagement.php';</script>";
    exit();
}

// Prevent multiple reviews: Check if a review already exists for this booking by this guest
$check_sql = "SELECT * FROM review WHERE guest_id = ? AND booking_id = ?";
$check_stmt = $conn->prepare($check_sql);

if ($check_stmt === false) {
    die("Prepare failed: " . $conn->error);
}

$check_stmt->bind_param("ss", $guest_id, $booking_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    echo "<script>alert('You have already submitted a review for this booking.'); window.location.href='BookingManagement.php';</script>";
    exit();
}

// Handle form submission when the user submits the review
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = $_POST['rating'] ?? '';
    // Trim whitespace and truncate comment to 100 characters to match DB schema
    $comment = substr(trim($_POST['comment'] ?? ''), 0, 100);

    // Validate inputs
    if ($rating === '' || $comment === '') {
        $error = "Please provide both rating and comment (max 100 characters)."; // Updated error message
    } else {
        // Generate a unique review ID
        $review_id = uniqid('R');
        $review_date = date('Y-m-d'); // Current date for review_date

        // SQL to insert the new review
        $insert_sql = "INSERT INTO review (review_id, guest_id, booking_id, rating, comment, review_date)
                       VALUES (?, ?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);

        if ($insert_stmt === false) {
            die("Prepare failed: " . $conn->error);
        }

        // Bind parameters: s=string, d=double/float, s=string
        // review_id (string), guest_id (string), booking_id (string), rating (float), comment (string), review_date (string)
        $insert_stmt->bind_param("sssdss", $review_id, $guest_id, $booking_id, $rating, $comment, $review_date);

        if ($insert_stmt->execute()) {
            echo "<script>alert('Review submitted successfully.'); window.location.href='BookingManagement.php';</script>";
            exit();
        } else {
            // Log the error for debugging purposes
            error_log("Failed to submit review: " . $insert_stmt->error);
            $error = "Failed to submit review. Please try again. (Database Error)";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit Review</title>
    <style>
    @font-face {
        font-family: 'NType';
        src: url('../../assets/NType-Regular.ttf') format('opentype');
    }
    @font-face {
        font-family: 'Archivo';
        src: url('../../assets/archivo/Archivo-Regular.ttf') format('truetype');
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Archivo', sans-serif;
        background: #fff;
        color: #111;
    }

    .header-bar {
        display: flex;
        align-items: center;
        margin: 20px;
    }

    .back-button {
        font-size: 16px;
        font-weight: bold;
        display: flex;
        align-items: center;
        gap: 6px;
        cursor: pointer;
    }

    .review-form {
        max-width: 600px;
        margin: 50px auto;
        background: #fff;
        padding: 40px;
        border-radius: 10px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }

    .review-form h2 {
        margin-bottom: 30px;
        text-align: center;
        font-family: 'NType', sans-serif;
        font-size: 28px;
    }

    .star {
        font-size: 32px;
        cursor: pointer;
        color: #ddd;
        transition: color 0.2s;
    }

    .star.filled {
        color: gold;
    }

    .review-form textarea {
        width: 100%;
        height: 120px;
        padding: 12px;
        margin-top: 15px;
        border: 1px solid #ccc;
        border-radius: 6px;
        resize: vertical;
        font-family: 'Archivo', sans-serif;
        font-size: 14px;
    }

    .review-form button {
        margin-top: 25px;
        padding: 12px 20px;
        background-color: #2196F3;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: bold;
        width: 100%;
        font-family: 'Archivo', sans-serif;
    }

    .error {
        color: red;
        margin-top: 10px;
        text-align: center;
    }
    </style>
</head>
<body>

<div class="header-bar">
    <div class="back-button" onclick="window.location.href='BookingManagement.php'">
        &lt; Back
    </div>
</div>

<div class="review-form">
    <h2>Review for <?= htmlspecialchars($booking['title']) ?></h2>
    <?php if (isset($error)) : ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post">
        <label>Rating:</label>
        <div id="star-container" style="margin-bottom: 20px;">
            <span class="star" data-value="1">&#9733;</span>
            <span class="star" data-value="2">&#9733;</span>
            <span class="star" data-value="3">&#9733;</span>
            <span class="star" data-value="4">&#9733;</span>
            <span class="star" data-value="5">&#9733;</span>
        </div>
        <input type="hidden" name="rating" id="rating" required>

        <label for="comment">Comment:</label>
        <textarea name="comment" id="comment" maxlength="100" required></textarea>

        <button type="submit">Submit Review</button>
    </form>
</div>

<script>
const stars = document.querySelectorAll('.star');
const ratingInput = document.getElementById('rating');

stars.forEach(star => {
    star.addEventListener('click', () => {
        const rating = star.getAttribute('data-value');
        ratingInput.value = rating;

        stars.forEach(s => s.classList.remove('filled'));
        for (let i = 0; i < rating; i++) {
            stars[i].classList.add('filled');
        }
    });
});
</script>

</body>
</html>