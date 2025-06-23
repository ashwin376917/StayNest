<?php
include_once '../../connect.php';

// Debug connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Change from 'id' to 'homestay_id' to match your URL parameter
$homestay_id = trim($_GET['homestay_id'] ?? '');

// Debug the received ID
error_log("Searching for homestay_id: " . $homestay_id);

$sql = "SELECT * FROM homestay WHERE homestay_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $homestay_id);
$stmt->execute();
$result = $stmt->get_result();
$homestay = $result->fetch_assoc();

if (!$homestay) {
    die("Homestay not found (debug): Trying to find ".$homestay_id);
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>View Property - Guest</title>
  <link rel="stylesheet" href="../../CSS/Guest/ViewPropertyDetail.css" />
</head>
<body>

<div class="property-container">

  <!-- Header -->
  <div class="header-bar">
    <div class="back-button" onclick="window.history.back()">
      <img src="../../assets/back_button.png" alt="Back" class="back-icon" />
      Back to Listings
    </div>
  </div>

  <!-- Property Name and City -->
  <div class="property-name-section">
    <h1 id="propertyName"><?php echo htmlspecialchars($homestay['title']); ?></h1>
    <h3 id="propertyCity"><?php echo htmlspecialchars($homestay['address']); ?></h3>
  </div>

<!-- Image Gallery -->
<div class="image-gallery">
  <div class="main-image" id="mainImage">
     <img src="/StayNest/upload/<?= htmlspecialchars(basename($homestay['picture1'])) ?>" alt="Side Image 1" />
  </div>
  <div class="side-images">
    <div class="image-wrapper">
      <img src="/StayNest/upload/<?= htmlspecialchars(basename($homestay['picture2'])) ?>" alt="Side Image 1" />
    </div>
    <div class="image-wrapper">
      <img src="/StayNest/upload/<?= htmlspecialchars(basename($homestay['picture3'])) ?>" alt="Side Image 2" />
    </div>
  </div>
</div>

  <!-- Amenities Section -->
  <div class="amenities-box">
    <p class="section-title">What this place offers</p>
    <div class="amenities-list" id="amenitiesList">
      <?php
      $amenities = explode(",", $homestay['amenities']);
      foreach ($amenities as $item) {
        echo "<div class='amenity-item'>" . htmlspecialchars(trim($item)) . "</div>";
      }
      ?>
    </div>
  </div>

  <!-- Description Section -->
  <div class="description-box">
    <img src="../../<?php echo htmlspecialchars($homestay['picture1']); ?>" class="description-img" />
    <div class="description-tint"></div>
    <div class="description-overlay">
      <p class="section-title">About this place</p>
      <p id="propertyDescription"><?php echo htmlspecialchars($homestay['description']); ?></p>
    </div>
  </div>
</div>

<!-- Floating Booking Box -->
<div class="floating-booking-box">
  <div class="booking-inputs">
    <div class="booking-field">
      <label>Check-in</label>
      <input type="date" id="checkIn" />
    </div>
    <div class="booking-field">
      <label>Check-out</label>
      <input type="date" id="checkOut" />
    </div>
    <div class="booking-field">
      <label>Guests</label>
      <select>
        <option>1 Guest</option>
        <option>2 Guests</option>
        <option>3 Guests</option>
        <option>4 Guests</option>
        <option>5 Guests</option>
        <option>>5</option>
      </select>
    </div>
  </div>
  <div class="price-book">
    <div class="price-placeholder" id="propertyPrice" data-price="<?= htmlspecialchars($homestay['price_per_night']) ?>">
      RM <?= htmlspecialchars($homestay['price_per_night']) ?> / night
    </div>
    <form id="bookingForm" action="GuestBookingPreview.php" method="get">
  <input type="hidden" name="homestay_id" value="<?= htmlspecialchars($homestay['homestay_id']) ?>">
  <input type="hidden" name="checkin" id="formCheckIn" />
  <input type="hidden" name="checkout" id="formCheckOut" />
  <input type="hidden" name="guests" id="formGuests" />
  <button type="submit" class="book-btn">BOOK NOW</button>
</form>

  </div>
</div>

<!-- Link to External JS -->
<script src="../../JS/Guest/ViewPropertyDetail.js" defer></script>


</body>
</html>
