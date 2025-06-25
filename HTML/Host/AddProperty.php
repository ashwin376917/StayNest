<?php include '../../Header_Footer/Header.php'; ?>
<?php include("connect.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nest_Name = $_POST['property_name'];
  $location = $_POST['property_city'];
  $category = $_POST['category'];
  $description = $_POST['property_description'];
  $price = $_POST['price'];
  $amenities = isset($_POST['amenities']) ? implode(', ', $_POST['amenities']) : '';

  $parts = explode(',', $location);
  $district = trim($parts[0] ?? '');
  $state = trim($parts[1] ?? '');

  $host_Id = 1;
  $address = '';
  $rating = 0; 
  $status = 'pending';

  $uploadDir = '../../uploads/';
  if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
  }

  $picture1 = $picture2 = $picture3 = '';

  if (count($_FILES['images']['tmp_name']) >= 3) {
    for ($i = 0; $i < 3; $i++) {
      $tmp_name = $_FILES['images']['tmp_name'][$i];
      $fileName = basename($_FILES['images']['name'][$i]);
      $targetPath = $uploadDir . time() . "_$i" . "_" . $fileName;

      if (move_uploaded_file($tmp_name, $targetPath)) {
        $relativePath = str_replace('../../', '', $targetPath);
        if ($i === 0) $picture1 = $relativePath;
        if ($i === 1) $picture2 = $relativePath;
        if ($i === 2) $picture3 = $relativePath;
      }
    }

    $sql = "INSERT INTO homestay 
      (host_Id, nest_Name, nest_Description, address, district, state, price_PerNight, categories, rating_Average, nest_Status, amenities, picture1, picture2, picture3)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssssssssssss",
      $host_Id, $nest_Name, $description, $address, $district, $state,
      $price, $category, $rating, $status, $amenities, $picture1, $picture2, $picture3
    );

    if ($stmt->execute()) {
      echo "<script>alert('Nests added successfully!'); window.location.href = 'ViewNest.php';</script>";
    } else {
      echo "<script>alert('Error: " . $stmt->error . "');</script>";
    }

    $stmt->close();
  } else {
    echo "<script>alert('Please upload at least 3 images.');</script>";
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Create Nest - StayNest Host</title>
  <link rel="stylesheet" href="../../CSS/AddProperty.css" />
  <link rel="stylesheet" href="/StayNest/Header_Footer/css/Header.css" />
</head>
<body>

<form method="POST" enctype="multipart/form-data">
<div class="property-container">

  <!-- === HEADER BAR === -->
  <div class="header-bar">
    <a href="ViewNest.php" class="back-button" style="text-decoration: none; color: inherit;">
      <img src="../../assets/back_button.png" alt="Back" class="back-icon" />
      Back
    </a>
    <div class="button-group" id="buttonGroup">
      <button type="reset" class="cancel-btn">✖</button>
      <button type="submit" class="save-btn" id="saveBtn">Save</button>
    </div>
  </div>

  <!-- === PROPERTY NAME & CITY === -->
  <div class="property-name-section">
    <textarea class="property-name-input" name="property_name" placeholder="Your Property Name" rows="1"></textarea>
    <textarea class="property-city-input" name="property_city" placeholder="District, State" rows="1"></textarea>

    <!-- === CATEGORIES DROPDOWN === -->
    <div class="booking-field" style="margin-bottom: 80px;">
  <label>Categories</label>
  <select name="category" required>
    <option value="">Select a category</option>
    <option value="shared">Shared</option>
    <option value="luxury">Luxury</option>
    <option value="minimalist">Minimalist</option>
    <option value="pet friendly">Pet Friendly</option>
    <option value="family friendly">Family Friendly</option>
    <option value="antique">Antique</option>
  </select>
</div>

  </div>

  <!-- === IMAGE GALLERY === -->
  <div class="image-gallery">
    <div id="mainImageBox" class="main-image shadow image-overlay" onclick="document.getElementById('uploadImages').click()">
      <div class="overlay-content" id="addImageOverlay">
        <img src="../../assets/add_white.png" alt="Add" class="add-icon" />
        <span class="add-text">Add Image</span>
      </div>
      <input type="file" name="images[]" id="uploadImages" multiple accept="image/*" style="display: none;" required />
    </div>
    <div class="side-images">
      <div class="image-wrapper shadow">
        <img id="sideImage1" src="../../assets/accommodation-deluxeRoom.jpg" alt="Side 1" />
        <div class="image-tint"></div>
      </div>
      <div class="image-wrapper shadow" style="position: relative;">
        <img id="sideImage2" src="../../assets/accommodation-deluxeRoom.jpg" alt="Side 2" />
        <div class="image-tint"></div>
        <div id="moreImagesLabel" style="
          position: absolute;
          inset: 0;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 26px;
          font-weight: bold;
          color: white;
          z-index: 2;
          display: none;
        "></div>
      </div>
    </div>
  </div>

  <!-- === AMENITIES SECTION === -->
  <div class="amenities-box shadow">
    <p class="section-title">What did you offer</p>
    <div class="amenities-list">
      <?php
      $amenities = [
        "Wifi", "Free Parking", "Kitchen", "Pool", "Smart TV",
        "Personal Workspace", "Washer", "Hair Dryer", "Dryer", "Aircond"
      ];
      foreach ($amenities as $a) {
        echo '<label class="amenity-item">
                <input type="checkbox" name="amenities[]" value="' . $a . '" />
                <img src="../../assets/' . strtolower(str_replace(' ', '', $a)) . '.png" class="amenity-icon" />
                <span>' . $a . '</span>
              </label>';
      }
      ?>
    </div>
  </div>

  <!-- === DESCRIPTION SECTION === -->
  <div class="description-box shadow">
    <img id="descriptionBG" src="../../assets/accommodation-deluxeRoom.jpg" alt="BG" class="description-img" />
    <div class="description-tint"></div>
    <div class="description-overlay">
      <p class="section-title">Your Description</p>
      <textarea class="property-description" name="property_description" placeholder="Write about your property..." rows="1"></textarea>
    </div>
  </div>
</div>

<!-- === FLOATING BOOKING BOX === -->
<div class="floating-booking-box shadow">
  <div class="booking-inputs">
    <div class="booking-field">
      <label>Check-in</label>
      <input type="date" name="checkin" />
    </div>
    <div class="booking-field">
      <label>Check-out</label>
      <input type="date" name="checkout" />
    </div>
    <div class="booking-field">
      <label>Guests</label>
      <select name="guests">
        <option value="1">1 Guest</option>
        <option value="2">2 Guests</option>
        <option value="3">3 Guests</option>
        <option value="4">4 Guests</option>
        <option value="5">5 Guests</option>
      </select>
    </div>
  </div>
  <div class="price-book">
    <input type="number" class="price-input" name="price" placeholder="RM" />
    <button type="submit" class="book-btn">BOOK</button>
  </div>
</div>
</form>

<!-- === SCRIPT === -->
<script>
  const nameInput = document.querySelector('.property-name-input');
  const cityInput = document.querySelector('.property-city-input');
  const descInput = document.querySelector('.property-description');
  const buttonGroup = document.getElementById('buttonGroup');
  const originalState = { name: '', city: '', description: '' };

  function checkChanges() {
    const changed =
      nameInput.value.trim() !== originalState.name ||
      cityInput.value.trim() !== originalState.city ||
      descInput.value.trim() !== originalState.description;
    buttonGroup.classList.toggle('visible', changed);
  }

  [nameInput, cityInput, descInput].forEach(input => {
    input.addEventListener('input', () => {
      input.style.height = 'auto';
      input.style.height = `${input.scrollHeight}px`;
      checkChanges();
    });
  });

  window.addEventListener('load', () => {
    [nameInput, cityInput, descInput].forEach(input => {
      input.style.height = 'auto';
      input.style.height = `${input.scrollHeight}px`;
    });
  });

  const fileInput = document.getElementById('uploadImages');
  const mainImageBox = document.getElementById('mainImageBox');
  const overlay = document.getElementById('addImageOverlay');
  const sideImage1 = document.getElementById('sideImage1');
  const sideImage2 = document.getElementById('sideImage2');
  const moreLabel = document.getElementById('moreImagesLabel');
  const descBG = document.getElementById('descriptionBG');

  fileInput.addEventListener('change', function () {
    const files = fileInput.files;
    if (!files.length) return;

    mainImageBox.style.backgroundImage = '';
    overlay.style.display = 'flex';
    sideImage1.src = '../../assets/accommodation-deluxeRoom.jpg';
    sideImage2.src = '../../assets/accommodation-deluxeRoom.jpg';
    descBG.src = '../../assets/accommodation-deluxeRoom.jpg';
    moreLabel.style.display = 'none';
    moreLabel.textContent = '';

    if (files[0]) {
      const reader1 = new FileReader();
      reader1.onload = e => {
        mainImageBox.style.backgroundImage = `url(${e.target.result})`;
        mainImageBox.style.backgroundSize = 'cover';
        mainImageBox.style.backgroundPosition = 'center';
        overlay.style.display = 'none';
        descBG.src = e.target.result;
      };
      reader1.readAsDataURL(files[0]);
    }

    if (files[1]) {
      const reader2 = new FileReader();
      reader2.onload = e => sideImage1.src = e.target.result;
      reader2.readAsDataURL(files[1]);
    }

    if (files[2]) {
      const reader3 = new FileReader();
      reader3.onload = e => sideImage2.src = e.target.result;
      reader3.readAsDataURL(files[2]);
    }

    if (files.length > 3) {
      moreLabel.textContent = `+${files.length - 3}`;
      moreLabel.style.display = 'flex';
    }
  });
</script>

</body>
</html>
