<?php
session_start();
include_once '../../connect.php';

if (!isset($_SESSION['guest_id'])) {
    header("Location: ../../HTML/Home/login.php");
    exit();
}

$homestay_id = trim($_GET['homestay_id'] ?? '');
if (!$homestay_id) {
    die("Missing homestay ID.");
}

// --- MODIFIED LOGIC START ---
// Determine Back Button behavior based on the 'from' parameter or session data
$back_link = 'GuestDashboard.php'; // Default link
$back_text = 'Back to Listings';   // Default text

if (isset($_GET['from']) && $_GET['from'] === 'bookings') {
    // If coming from the booking management page
    $back_link = 'BookingManagement.php';
    $back_text = 'Back to My Bookings';
} elseif (isset($_GET['from']) && $_GET['from'] === 'homepage') {
    // If coming from the AfterLoginHomepage (Most Popular section)
    $back_link = 'AfterLoginHomepage.php';
    $back_text = 'Back to Homepage';
} elseif (isset($_SESSION['last_query'])) {
    // If coming from a search results page (and no specific 'from' parameter was set)
    // The SearchResult.php page or its handler should set $_SESSION['last_query']
    // with the appropriate query string (e.g., 'query=melaka' or 'category=Family+Friendly')
    $back_link = 'SearchResult.php?query=' . urlencode($_SESSION['last_query']);
    $back_text = 'Back to Search Results'; // More specific text for search
}
// --- MODIFIED LOGIC END ---

$sql = "SELECT * FROM homestay WHERE homestay_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $homestay_id);
$stmt->execute();
$result = $stmt->get_result();
$homestay = $result->fetch_assoc();
$stmt->close();

if (!$homestay) {
    die("Homestay not found.");
}

// Update total_click for the homestay
$updateClickSql = "UPDATE homestay SET total_click = total_click + 1 WHERE homestay_id = ?";
$updateClickStmt = $conn->prepare($updateClickSql);
if ($updateClickStmt) {
    $updateClickStmt->bind_param("s", $homestay_id);
    $updateClickStmt->execute();
    $updateClickStmt->close();
}

// Fetch booked dates for the homestay
$booked_dates = [];
$sql_dates = "SELECT check_in_date, check_out_date FROM booking WHERE homestay_id = ? AND booking_status = 1";
$stmt = $conn->prepare($sql_dates);
$stmt->bind_param("s", $homestay_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $start = new DateTime($row['check_in_date']);
    $end = new DateTime($row['check_out_date']);
    $interval = new DateInterval('P1D');
    
    // Include all days within the booking range, including check-in but excluding check-out if it's the next day
    // The DatePeriod for $end is exclusive, so we add 1 day to include the end date itself if it's booked
    $period = new DatePeriod($start, $interval, $end->modify('+1 day')); // Corrected for inclusive end date

    foreach ($period as $date) {
        $booked_dates[] = $date->format('Y-m-d');
    }
}
$stmt->close();

$booked_dates = array_values(array_unique($booked_dates));

// --- Start of NEW Review Fetching Logic ---
$reviews = [];
// Join review, booking, and guest tables to get review details and guest name
$sql_reviews = "
    SELECT 
        r.rating, 
        r.comment, 
        r.review_date,
        g.guest_name
    FROM review r
    JOIN booking b ON r.booking_id = b.booking_id
    JOIN guest g ON r.guest_id = g.guest_id
    WHERE b.homestay_id = ?
    ORDER BY r.review_date DESC"; // Order by most recent review

$stmt_reviews = $conn->prepare($sql_reviews);
if ($stmt_reviews) {
    $stmt_reviews->bind_param("s", $homestay_id);
    $stmt_reviews->execute();
    $result_reviews = $stmt_reviews->get_result();
    while ($row_review = $result_reviews->fetch_assoc()) {
        $reviews[] = $row_review;
    }
    $stmt_reviews->close();
}
// --- End of NEW Review Fetching Logic ---

$view_only = isset($_GET['viewonly']);
$offered_amenities = !empty($homestay['amenities']) ? array_map('trim', explode(',', $homestay['amenities'])) : [];
$all_possible_amenities = ["Wifi", "Parking", "Kitchen", "Pool", "SmartTV", "PersonalWorkspace", "Washer", "HairDryer", "Dryer", "Aircond"];
$image_base_path = '/StayNest/HTML/Host/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($homestay['title']) ?> - StayNest</title>
    <link rel="stylesheet" href="../Host/css/AddProperty.css">
    <link rel="stylesheet" href="../Guest/css/ViewPropertyDetail.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <style>
        .booked-date a {
            background-color: #f8d7da !important;
            color: #a00 !important;
        }
    </style>
</head>
<body>
<div class="property-container">
    <div class="header-bar">
        <div class="back-button" 
             onclick="window.location.href='<?= htmlspecialchars($back_link) ?>'">
            <img src="../../assets/back_button.png" alt="Back" class="back-icon">
            <?= htmlspecialchars($back_text) ?>
        </div>
    </div>

    <div class="property-name-section">
        <h1 class="property-name-display"> <?= htmlspecialchars($homestay['title']) ?> </h1>
        <p class="property-location-display"> <?= htmlspecialchars($homestay['state']) ?>, <?= htmlspecialchars($homestay['district']) ?> </p>
        <p class="property-category-display"> Category: <?= htmlspecialchars($homestay['categories']) ?> </p>
    </div>

    <div class="image-gallery">
        <div class="image-box main-image shadow filled">
            <img src="<?= $image_base_path . htmlspecialchars($homestay['picture1']) ?>" alt="Main Image" class="preview-image" style="display: block;">
        </div>
        <div class="side-images">
            <div class="image-box side-image shadow filled">
                <img src="<?= $image_base_path . htmlspecialchars($homestay['picture2']) ?>" alt="Side Image 1" class="preview-image" style="display: block;">
            </div>
            <div class="image-box side-image shadow filled">
                <img src="<?= $image_base_path . htmlspecialchars($homestay['picture3']) ?>" alt="Side Image 2" class="preview-image" style="display: block;">
            </div>
        </div>
    </div>

    <div class="amenities-box shadow">
        <p class="section-title">What this place offers</p>
        <div class="amenities-list">
            <?php foreach ($all_possible_amenities as $amenity): if (in_array($amenity, $offered_amenities)): ?>
                <div class="amenity-display-item">
                    <img src="../../assets/Property/<?= strtolower(str_replace(' ', '', $amenity)) ?>.png" class="amenity-icon" alt="<?= htmlspecialchars($amenity) ?>">
                    <span><?= htmlspecialchars($amenity) ?></span>
                </div>
            <?php endif; endforeach; ?>
        </div>
    </div>

    <div class="description-box shadow">
        <img src="<?= $image_base_path . htmlspecialchars($homestay['picture1']) ?>" class="description-img" alt="Description Background">
        <div class="description-tint"></div>
        <div class="description-overlay">
            <p class="section-title">About this place</p>
            <p class="property-description"> <?= nl2br(htmlspecialchars($homestay['description'])) ?> </p>
        </div>
    </div>

    <div class="reviews-section">
        <p class="section-title">Guest Reviews</p>
        <?php if (!empty($reviews)): ?>
            <div class="reviews-list">
                <?php foreach ($reviews as $review): ?>
                    <div class="review-item shadow">
                        <div class="review-header">
                            <span class="guest-name"><?= htmlspecialchars($review['guest_name']) ?></span>
                            <span class="review-date"><?= date('F j, Y', strtotime($review['review_date'])) ?></span>
                        </div>
                        <div class="review-rating">
                            <?php 
                            $full_stars = floor($review['rating']);
                            $has_half_star = ($review['rating'] - $full_stars) >= 0.5; // Check for half star
                            $empty_stars = 5 - ceil($review['rating']); // Calculate remaining empty stars
                            
                            for ($i = 0; $i < $full_stars; $i++): ?>
                                <span class="star filled">&#9733;</span>
                            <?php endfor; ?>
                            <?php if ($has_half_star): ?>
                                <span class="star half-filled">&#9733;</span> <?php endif; ?>
                            <?php for ($i = 0; $i < $empty_stars; $i++): ?>
                                <span class="star empty">&#9734;</span>
                            <?php endfor; ?>
                            <span class="rating-number"><?= (int)$review['rating'] ?>/5</span>                        </div>
                        <p class="review-comment"><?= nl2br(htmlspecialchars($review['comment'])) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="no-reviews">No reviews yet for this homestay.</p>
        <?php endif; ?>
    </div>
    </div>

<?php if (!$view_only): ?>
<div class="floating-booking-box">
    <div class="booking-inputs">
        <div class="booking-field">
            <label for="checkIn">Check-in</label>
            <div class="input-with-icon">
                <input type="text" id="checkIn" placeholder="Check-in" required>
                <img src="../../assets/calendar.png" class="calendar-icon" id="checkInIcon">
            </div>
        </div>
        <div class="booking-field">
            <label for="checkOut">Check-out</label>
            <div class="input-with-icon">
                <input type="text" id="checkOut" placeholder="Check-out" required>
                <img src="../../assets/calendar.png" class="calendar-icon" id="checkOutIcon">
            </div>
        </div>
        <div class="booking-field">
            <label for="guests">Guests</label>
            <select id="guests" required>
                <option value="" selected disabled>Select Guests</option>
                <?php for ($i = 1; $i <= $homestay['max_guests']; $i++): ?>
                    <option value="<?= $i ?>"> <?= $i ?> Guest<?= $i > 1 ? 's' : '' ?> </option>
                <?php endfor; ?>
            </select>
        </div>
    </div>

    <div class="price-book">
        <div class="price-placeholder" id="propertyPrice" data-price="<?= htmlspecialchars($homestay['price_per_night']) ?>">
            RM <?= htmlspecialchars($homestay['price_per_night']) ?> / night
        </div>
        <form id="bookingForm" action="GuestBookingPreview.php" method="get">
            <input type="hidden" name="homestay_id" value="<?= htmlspecialchars($homestay['homestay_id']) ?>">
            <input type="hidden" name="checkin" id="formCheckIn">
            <input type="hidden" name="checkout" id="formCheckOut">
            <input type="hidden" name="guests" id="formGuests">
            <button type="submit" class="book-btn">BOOK NOW</button>
        </form>
    </div>
</div>
<?php endif; ?>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script>
$(document).ready(function () {
    const bookedDates = <?= json_encode($booked_dates) ?>;
    const pricePerNight = parseFloat($("#propertyPrice").data("price"));

    function disableBooked(date) {
        const formatted = $.datepicker.formatDate('yy-mm-dd', date);
        if (bookedDates.includes(formatted)) {
            return [false, "booked-date", "Booked"];
        }
        return [true, ""];
    }

    $("#checkIn").datepicker({
        minDate: +1,
        dateFormat: "yy-mm-dd",
        beforeShowDay: disableBooked,
        onSelect: function (selectedDate) {
            const minCheckout = new Date(selectedDate);
            minCheckout.setDate(minCheckout.getDate() + 1);

            const maxCheckout = new Date(minCheckout.getTime());
            maxCheckout.setDate(maxCheckout.getDate() + 14);

            $("#checkOut").datepicker("option", "minDate", minCheckout);
            $("#checkOut").datepicker("option", "maxDate", maxCheckout);

            // Clear check-out if it becomes invalid after new check-in
            const currentCheckOut = $("#checkOut").val();
            if (currentCheckOut && new Date(currentCheckOut) <= new Date(selectedDate)) {
                $("#checkOut").val("");
            }

            updatePrice();
        }
    });

    $("#checkOut").datepicker({
        dateFormat: "yy-mm-dd",
        beforeShowDay: disableBooked,
        onSelect: updatePrice
    });

    $("#checkOutIcon").on("click", function () {
        if (!$("#checkIn").val()) {
            alert("Please select check-in date first.");
        } else {
            $("#checkOut").datepicker("show");
        }
    });

    $("#checkOut").on("focus", function () {
        if (!$("#checkIn").val()) {
            $(this).blur();
            alert("Please select check-in date first.");
        }
    });

    $("#checkInIcon").on("click", function () {
        $("#checkIn").datepicker("show");
    });

    function updatePrice() {
        const checkInVal = $("#checkIn").val();
        const checkOutVal = $("#checkOut").val();

        if (checkInVal && checkOutVal) {
            const inDate = new Date(checkInVal);
            const outDate = new Date(checkOutVal);
            if (outDate > inDate) {
                const diffTime = outDate - inDate;
                const diffDays = diffTime / (1000 * 60 * 60 * 24);
                const total = diffDays * pricePerNight;
                $("#propertyPrice").text(`RM ${total.toFixed(2)} total (${diffDays} night${diffDays > 1 ? 's' : ''})`);
                return;
            }
        }
        $("#propertyPrice").text(`RM ${pricePerNight.toFixed(2)} / night`);
    }

    $("#bookingForm").on("submit", function (e) {
        const checkIn = $("#checkIn").val();
        const checkOut = $("#checkOut").val();
        const guests = $("#guests").val();

        if (!checkIn || !checkOut || new Date(checkOut) <= new Date(checkIn)) {
            e.preventDefault();
            alert("Please select valid check-in and check-out dates.");
            return;
        }

        let isRangeBooked = false;
        const startDate = new Date(checkIn);
        const endDate = new Date(checkOut);
        // Loop from check-in up to (but not including) check-out
        for (let d = new Date(startDate); d < endDate; d.setDate(d.getDate() + 1)) {
            const formattedDate = $.datepicker.formatDate('yy-mm-dd', d);
            if (bookedDates.includes(formattedDate)) {
                isRangeBooked = true;
                break;
            }
        }

        if (isRangeBooked) {
            e.preventDefault();
            alert("Part or all of your selected dates are already booked. Please choose different dates.");
            return;
        }

        if (!guests) {
            e.preventDefault();
            alert("Please select the number of guests.");
            return;
        }

        $("#formCheckIn").val(checkIn);
        $("#formCheckOut").val(checkOut);
        $("#formGuests").val(guests);
    });
});
</script>

</body>
</html>