<?php include '../../Header_Footer/Header.php'; ?>
<?php include("connect.php");

$editing = false;
$nest = [];

if (isset($_GET['nest_Id'])) {
  $editing = true;
  $nest_Id = intval($_GET['nest_Id']);
  $stmt = $conn->prepare("SELECT * FROM homestay WHERE nest_Id = ?");
  $stmt->bind_param("i", $nest_Id);
  $stmt->execute();
  $result = $stmt->get_result();
  $nest = $result->fetch_assoc();
  $stmt->close();
}

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

  $picture1 = $nest['picture1'] ?? '';
  $picture2 = $nest['picture2'] ?? '';
  $picture3 = $nest['picture3'] ?? '';

  $uploadDir = '../../uploads/';
  if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

  if (!empty($_FILES['images']['name'][0])) {
    for ($i = 0; $i < 3; $i++) {
      $tmp_name = $_FILES['images']['tmp_name'][$i] ?? '';
      $fileName = $_FILES['images']['name'][$i] ?? '';
      if ($tmp_name && $fileName) {
        $targetPath = $uploadDir . time() . "_$i" . "_" . basename($fileName);
        if (move_uploaded_file($tmp_name, $targetPath)) {
          $relativePath = str_replace('../../', '', $targetPath);
          if ($i === 0) $picture1 = $relativePath;
          if ($i === 1) $picture2 = $relativePath;
          if ($i === 2) $picture3 = $relativePath;
        }
      }
    }
  }

  if ($editing) {
    $stmt = $conn->prepare("UPDATE homestay SET nest_Name=?, nest_Description=?, district=?, state=?, price_PerNight=?, categories=?, amenities=?, picture1=?, picture2=?, picture3=? WHERE nest_Id=?");
    $stmt->bind_param("ssssssssssi", $nest_Name, $description, $district, $state, $price, $category, $amenities, $picture1, $picture2, $picture3, $nest_Id);
    if ($stmt->execute()) {
      echo "<script>alert('Saved successfully!'); window.location.href = 'ViewNest.php';</script>";
    } else {
      echo "<script>alert('Error: " . $stmt->error . "');</script>";
    }
    $stmt->close();
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit Nest - StayNest Host</title>
  <link rel="stylesheet" href="../../CSS/EditNest.css" />
  <link rel="stylesheet" href="/StayNest/Header_Footer/css/Header.css" />
</head>
<body>
<form method="POST" enctype="multipart/form-data">
  <div class="property-container">
    <div class="header-bar">
      <a href="ViewNest.php" class="back-button" style="text-decoration: none; color: inherit;">
        <img src="../../assets/back_button.png" alt="Back" class="back-icon" />
        Back
      </a>
      <div class="button-group" id="buttonGroup">
        <button type="reset" class="cancel-btn">✖</button>
        <button type="submit" class="save-btn" id="saveBtn"><?= $editing ? 'Update' : 'Save' ?></button>
      </div>
    </div>

    <div class="property-name-section">
      <textarea class="property-name-input" name="property_name" rows="1"><?= $editing ? htmlspecialchars($nest['nest_Name']) : '' ?></textarea>
      <textarea class="property-city-input" name="property_city" rows="1"><?= $editing ? htmlspecialchars($nest['district'] . ', ' . $nest['state']) : '' ?></textarea>

      <div class="booking-field" style="margin-bottom: 80px;">
        <label>Categories</label>
        <select name="category" required>
          <?php
          $categories = ["shared", "luxury", "minimalist", "pet friendly", "family friendly", "antique"];
          foreach ($categories as $c) {
            $selected = ($editing && $nest['categories'] == $c) ? 'selected' : '';
            echo "<option value=\"$c\" $selected>" . ucfirst($c) . "</option>";
          }
          ?>
        </select>
      </div>
    </div>

    <div class="image-gallery">
      <div id="mainImageBox" class="main-image shadow image-overlay" onclick="document.getElementById('uploadImages').click()">
        <div class="overlay-content" id="addImageOverlay">
          <img src="../../assets/add_white.png" alt="Add" class="add-icon" />
          <span class="add-text">Add Image</span>
        </div>
        <input type="file" name="images[]" id="uploadImages" multiple accept="image/*" style="display: none;" />
      </div>
      <div class="side-images">
        <div class="image-wrapper shadow">
          <img id="sideImage1" src="../../assets/accommodation-deluxeRoom.jpg" alt="Side 1" />
          <div class="image-tint"></div>
        </div>
        <div class="image-wrapper shadow" style="position: relative;">
          <img id="sideImage2" src="../../assets/accommodation-deluxeRoom.jpg" alt="Side 2" />
          <div class="image-tint"></div>
          <div id="moreImagesLabel" style="position:absolute; inset:0; display:none; align-items:center; justify-content:center; font-size:26px; font-weight:bold; color:white; z-index:2;"></div>
        </div>
      </div>
    </div>

    <div class="amenities-box shadow">
      <p class="section-title">What did you offer</p>
      <div class="amenities-list">
        <?php
        $list = ["Wifi", "Free Parking", "Kitchen", "Pool", "Smart TV", "Personal Workspace", "Washer", "Hair Dryer", "Dryer", "Aircond"];
        $existingAmenities = $editing ? explode(',', $nest['amenities']) : [];
        foreach ($list as $a) {
          $checked = in_array($a, array_map('trim', $existingAmenities)) ? 'checked' : '';
          echo '<label class="amenity-item">
                  <input type="checkbox" name="amenities[]" value="' . $a . '" ' . $checked . ' />
                  <img src="../../assets/' . strtolower(str_replace(' ', '', $a)) . '.png" class="amenity-icon" />
                  <span>' . $a . '</span>
                </label>';
        }
        ?>
      </div>
    </div>

    <div class="description-box shadow">
      <img id="descriptionBG" src="../../assets/accommodation-deluxeRoom.jpg" alt="BG" class="description-img" />
      <div class="description-tint"></div>
      <div class="description-overlay">
        <p class="section-title">Your Description</p>
        <textarea class="property-description" name="property_description" placeholder="Write about your property..." rows="1"><?= $editing ? htmlspecialchars($nest['nest_Description']) : '' ?></textarea>
      </div>
    </div>

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
            <?php for ($i = 1; $i <= 5; $i++) echo "<option value=\"$i\">$i Guest" . ($i > 1 ? "s" : "") . "</option>"; ?>
          </select>
        </div>
      </div>
      <div class="price-book">
        <input type="number" class="price-input" name="price" placeholder="RM" value="<?= $editing ? $nest['price_PerNight'] : '' ?>" />
        <button type="submit" class="book-btn"><?= $editing ? 'Update' : 'Book' ?></button>
      </div>
    </div>
  </div>
</form>

<script>
  const inputs = document.querySelectorAll('textarea, input, select');
  const buttonGroup = document.getElementById('buttonGroup');
  const original = {};

  window.addEventListener('load', () => {
    inputs.forEach(i => {
      if (i.type === 'checkbox') return;
      original[i.name] = i.value;
      i.style.height = 'auto';
      i.style.height = `${i.scrollHeight}px`;
      i.addEventListener('input', () => {
        i.style.height = 'auto';
        i.style.height = `${i.scrollHeight}px`;
        checkChanges();
      });
    });
    document.querySelectorAll('input[type="checkbox"]').forEach(cb => {
      cb.addEventListener('change', checkChanges);
    });
    checkChanges();
  });

  document.getElementById('uploadImages').addEventListener('change', () => {
    original.imageChanged = true;
    checkChanges();
  });

  function checkChanges() {
    let changed = false;
    inputs.forEach(i => {
      if (i.type !== 'checkbox' && i.value !== original[i.name]) changed = true;
    });
    const currentAmenities = Array.from(document.querySelectorAll('input[name="amenities[]"]')).filter(i => i.checked).map(i => i.value).join(',');
    const originalAmenities = (original['amenities[]'] || '').split(',').join(',');
    if (currentAmenities !== originalAmenities) changed = true;
    if (original.imageChanged) changed = true;
    buttonGroup.classList.toggle('visible', changed);
  }

  <?php if ($editing): ?>
    <?php if (!empty($nest['picture1'])): ?>
      document.getElementById('mainImageBox').style.backgroundImage = "url('../../<?= $nest['picture1'] ?>')";
      document.getElementById('addImageOverlay').style.display = 'none';
      document.getElementById('descriptionBG').src = "../../<?= $nest['picture1'] ?>";
    <?php endif; ?>
    <?php if (!empty($nest['picture2'])): ?>
      document.getElementById('sideImage1').src = "../../<?= $nest['picture2'] ?>";
    <?php endif; ?>
    <?php if (!empty($nest['picture3'])): ?>
      document.getElementById('sideImage2').src = "../../<?= $nest['picture3'] ?>";
    <?php endif; ?>
  <?php endif; ?>
</script>

</body>
</html>
