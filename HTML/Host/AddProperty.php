<?php
// Ensure session is started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once '../../connect.php';

// Check if host_id is set in session
if (!isset($_SESSION['host_id'])) {
    // Redirect to login page or show an error if host_id is not available
    echo "<script>alert('Please log in as a host to add a property.'); window.location.href = '../Guest/login.php';</script>"; // Adjusted path
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $nest_Name = $_POST['property_name']; // Maps to 'title'
    $state = trim($_POST['state']); // Get state from new combobox
    $district = trim($_POST['district']); // Get district from new combobox
    $category = $_POST['category']; // Maps to 'categories'
    $description = $_POST['property_description']; // Maps to 'description'
    $price = floatval($_POST['price']); // Maps to 'price_per_night', converted to float
    $guests = intval($_POST['guests']); 
    $amenities = isset($_POST['amenities']) ? implode(', ', $_POST['amenities']) : ''; 

    // Get host_Id from session
    $host_Id = $_SESSION['host_id']; // This is correct for retrieving from session.

    
    function generateUniqueHomestayId($conn) {
        $prefix = 'HS'; // Homestay prefix
        do {
            // Generate a random string (e.g., 8 characters)
            $randomString = substr(md5(uniqid(mt_rand(), true)), 0, 8);
            $newId = $prefix . $randomString;
            // Check if it already exists in the database
            $stmt = $conn->prepare("SELECT homestay_id FROM homestay WHERE homestay_id = ?");
            $stmt->bind_param("s", $newId);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows == 0) {
                $stmt->close();
                return $newId;
            }
            $stmt->close();
        } while (true);
    }
    $homestay_id = generateUniqueHomestayId($conn);


    // Default values for other fields based on the database schema
    $homestay_status = 'pending'; // Maps to 'homestay_status' (or approval_status)
    $total_click = 0; // Maps to 'total_click'.
    $isBan = 0; // Maps to 'isBan' (0 for not banned, 1 for banned)

    // Directory for image uploads
    $uploadDir = 'uploads/'; // Path relative to current script (CreateNest.php)
    // Create upload directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) { // 0777 grants full permissions
            echo "<script>alert('Failed to create upload directory. Check server permissions.');</script>";
            exit();
        }
    }

    // Initialize variables for picture paths
    $picture1 = '';
    $picture2 = '';
    $picture3 = '';
    $uploadedFilePaths = [];
    $requiredImagesCount = 3; // Number of images required

    // Process each image file individually based on their input names
    $imageInputNames = ['image1', 'image2', 'image3']; // These match the 'name' attributes in the HTML
    foreach ($imageInputNames as $index => $fieldName) {
        // Check if the file was uploaded successfully and no errors occurred
        if (isset($_FILES[$fieldName]) && $_FILES[$fieldName]['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES[$fieldName]['tmp_name']; // Temporary file path
            $fileName = basename($_FILES[$fieldName]['name']); // Original file name
            // Create a unique target path to avoid overwriting files
            // Ensure unique name even if multiple hosts upload files with same names
            $targetPath = $uploadDir . $homestay_id . "_img" . ($index + 1) . "_" . time() . "_" . $fileName;

            // Move the uploaded file from temporary location to target directory
            if (move_uploaded_file($tmp_name, $targetPath)) {
                // Store the path relative to the web root or a consistent base for retrieval later.
                // Assuming 'uploads/' is directly under the 'host' directory, 
                // and your 'ViewNest.php' is also in 'host'.
                // If 'uploads' is parallel to 'host', adjust path in ViewNest.php accordingly.
                $uploadedFilePaths[$index] = $targetPath; 
            } else {
                // Error moving file
                echo "<script>alert('Error uploading file for " . htmlspecialchars($fieldName) . ". Please try again.');</script>";
                exit(); // Stop execution if an upload fails
            }
        } else if (isset($_FILES[$fieldName]) && $_FILES[$fieldName]['error'] !== UPLOAD_ERR_NO_FILE) {
            // Handle other file upload errors
            $errorMessage = '';
            switch ($_FILES[$fieldName]['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $errorMessage = 'File is too large.';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errorMessage = 'File was only partially uploaded.';
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $errorMessage = 'Missing a temporary folder.';
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $errorMessage = 'Failed to write file to disk.';
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $errorMessage = 'A PHP extension stopped the file upload.';
                    break;
                default:
                    $errorMessage = 'Unknown upload error.';
            }
            echo "<script>alert('Upload error for " . htmlspecialchars($fieldName) . ": " . $errorMessage . "');</script>";
            exit();
        } else {
            // File not uploaded (UPLOAD_ERR_NO_FILE) or not set, push empty string
            $uploadedFilePaths[$index] = '';
        }
    }

    // Verify that all required images have been uploaded
    // Use a loop to check each specific required file input
    for ($i = 0; $i < $requiredImagesCount; $i++) {
        if (empty($uploadedFilePaths[$i])) {
            echo "<script>alert('Please upload all 3 required images to proceed. Missing image " . ($i + 1) . "');</script>";
            exit(); // Stop execution if not enough images are uploaded
        }
    }

    // Assign paths to respective variables for database insertion
    $picture1 = $uploadedFilePaths[0];
    $picture2 = $uploadedFilePaths[1];
    $picture3 = $uploadedFilePaths[2];


   // Prepare SQL INSERT statement with updated column names
// Aligning with 'homestay_status' and 'isBan' as per the schema, and 'max_guests'
$sql = "INSERT INTO homestay
             (homestay_id, host_id, title, description, district, state, price_per_night, amenities, categories, homestay_status, picture1, picture2, picture3, total_click, isBan, max_guests)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"; // 
// Prepare and bind parameters to prevent SQL injection
$stmt = $conn->prepare($sql); // 
if ($stmt === false) {
    echo "<script>alert('Error preparing statement: " . $conn->error . "');</script>"; // 
    exit();
}

// Default values for other fields based on the database schema
$homestay_status_int = 0; // Assuming 'pending' translates to 0 for TINYINT(1) 
$total_click = 0; // Maps to 'total_click'. 
$isBan = 0; // Maps to 'isBan' (0 for not banned, 1 for banned) 

// Corrected bind_param call
$stmt->bind_param(
    "ssssssdssisssiii", // Corrected: 9th 'i' for category changed to 's'. 10th 's' for homestay_status changed to 'i'.
    $homestay_id,     // Generated unique ID
    $host_Id,
    $nest_Name,
    $description,
    $district,
    $state,
    $price,
    $amenities,
    $category,          // This will now be bound as a string 's'
    $homestay_status_int, // This will now be bound as an integer 'i'
    $picture1,
    $picture2,
    $picture3,
    $total_click,
    $isBan,
    $guests
);

// Execute the statement
if ($stmt->execute()) { // 
    // Success: Redirect to ViewNest.php
    echo "<script>alert('Homestay added successfully!'); window.location.href = 'ViewNest.php';</script>"; // 
} else {
    // Error executing statement
    echo "<script>alert('Error adding nest: " . $stmt->error . "');</script>"; // 
    // Optionally, log the error to server error logs
    error_log("Error adding nest: " . $stmt->error); // 
}

// Close the statement
$stmt->close();
$conn->close(); //
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Create Nest - StayNest Host</title>
  <link rel="stylesheet" href="css/AddProperty.css" />
  <link rel="stylesheet" href="css/hostheadersheet.css" />
</head>
<body>

<form method="POST" enctype="multipart/form-data">
<div class="property-container">

  <div class="header-bar">
    <a href="ViewNest.php" class="back-button" style="text-decoration: none; color: inherit;">
      <img src="../../assets/back_button.png" alt="Back" class="back-icon" />
      Back
    </a>
  </div>

  <div class="property-name-section">
    <textarea class="property-name-input" name="property_name" placeholder="Your Property Name" rows="1" required></textarea>
    
    <div class="location-selection">
        <div class="booking-field">
            <label for="state-select">State</label>
            <select id="state-select" name="state" required>
                <option value="">Select a State</option>
            </select>
        </div>
        <div class="booking-field">
            <label for="district-select">District</label>
            <select id="district-select" name="district" disabled required>
                <option value="">Select a District</option>
            </select>
        </div>
    </div>

    <div class="booking-field category-field">
      <label for="category-select">Categories</label>
      <select id="category-select" name="category" required>
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

  <div class="image-gallery">
    <div id="imageBox0" class="image-box main-image shadow" data-index="0">
      <div class="overlay-content add-image-overlay">
        <img src="../../assets/add_white.png" alt="Add" class="add-icon" />
        <span class="add-text">Add Image</span>
      </div>
      <input type="file" name="image1" id="uploadImage0" accept="image/*" style="display: none;" required />
      <img id="previewImage0" src="" alt="Property Image 1" class="preview-image" />
    </div>

    <div class="side-images">
      <div id="imageBox1" class="image-box side-image shadow" data-index="1">
        <div class="overlay-content add-image-overlay">
          <img src="../../assets/add_white.png" alt="Add" class="add-icon" />
          <span class="add-text">Add Image</span>
        </div>
        <input type="file" name="image2" id="uploadImage1" accept="image/*" style="display: none;" disabled required />
        <img id="previewImage1" src="" alt="Property Image 2" class="preview-image" />
      </div>

      <div id="imageBox2" class="image-box side-image shadow" data-index="2">
        <div class="overlay-content add-image-overlay">
          <img src="../../assets/add_white.png" alt="Add" class="add-icon" />
          <span class="add-text">Add Image</span>
        </div>
        <input type="file" name="image3" id="uploadImage2" accept="image/*" style="display: none;" disabled required />
        <img id="previewImage2" src="" alt="Property Image 3" class="preview-image" />
      </div>
    </div>
  </div>

  <div class="amenities-box shadow">
    <p class="section-title">What did you offer</p>
    <div class="amenities-list">
      <?php
      $amenities = [
        "Wifi", "Parking", "Kitchen", "Pool", "TV",
        "Workspace", "Washer", "HairDryer", "Dryer", "Aircond"
      ];
      foreach ($amenities as $a) {
        echo '<label class="amenity-item">
                <input type="checkbox" name="amenities[]" value="' . htmlspecialchars($a) . '" />
                <img src="../../assets/Property/' . strtolower(str_replace(' ', '', $a)) . '.png" class="amenity-icon" />
                <span>' . htmlspecialchars($a) . '</span>
              </label>';
      }
      ?>
    </div>
  </div>

  <div class="capacity-price-box shadow">
    <p class="section-title">Property Details</p>
    <div class="input-group">
      <label for="price-input">Price Per Night (RM)</label>
      <input type="number" id="price-input" class="price-input" name="price" placeholder="e.g., 150" required min="1" />
    </div>
    <div class="input-group">
      <label for="guests-select">Max Guests</label>
      <select id="guests-select" name="guests" required>
        <option value="1">1 Guest</option>
        <option value="2">2 Guests</option>
        <option value="3">3 Guests</option>
        <option value="4">4 Guests</option>
        <option value="5">5 Guests</option>
        <option value="6">6 Guests</option>
        <option value="7">7 Guests</option>
        <option value="8">8 Guests</option>
        <option value="9">9 Guests</option>
        <option value="10">10+ Guests</option>
      </select>
    </div>
  </div>


  <div class="description-box shadow">
    <img id="descriptionBG" src="../../assets/accommodation-deluxeRoom.jpg" alt="BG" class="description-img" />
    <div class="description-tint"></div>
    <div class="description-overlay">
      <p class="section-title">Your Description</p>
      <textarea class="property-description" name="property_description" placeholder="Write about your property..." rows="1" required></textarea>
    </div>
  </div>
</div>

<div class="bottom-save-button-container">
  <button type="submit" class="bottom-save-button">Save Property</button>
</div>

</form>

<script>
  // Auto-resize and button group logic (kept from original, but simplified)
  const nameInput = document.querySelector('.property-name-input');
  const descInput = document.querySelector('.property-description');

  [nameInput, descInput].forEach(input => {
    input.addEventListener('input', () => {
      input.style.height = 'auto';
      input.style.height = `${input.scrollHeight}px`;
    });
  });

  window.addEventListener('load', () => {
    [nameInput, descInput].forEach(input => {
      input.style.height = 'auto';
      input.style.height = `${input.scrollHeight}px`;
    });
    // Initialize image upload functionality on page load
    initializeImageUploads();
    populateStates(); // Call the new function to populate states
  });

  // --- Image Upload Logic ---
  const imageBoxes = document.querySelectorAll('.image-box'); // All clickable image container divs
  const fileInputs = document.querySelectorAll('.image-gallery input[type="file"]'); // All hidden file inputs
  const previewImages = document.querySelectorAll('.image-gallery img.preview-image'); // All img elements for previews
  const descriptionBG = document.getElementById('descriptionBG'); // The description background image

  let currentUploadIndex = 0; // Tracks which image slot is next to be filled

  /**
   * Initializes the image upload functionality, setting up event listeners
   * and managing the state (active, filled, disabled) of image boxes.
   */
  function initializeImageUploads() {
    imageBoxes.forEach((box, index) => {
      const fileInput = fileInputs[index];
      const previewImg = previewImages[index];
      const overlay = box.querySelector('.add-image-overlay');

      // Set initial state for each box
      if (index === 0) {
        box.classList.add('active-upload'); // First box is active initially
      } else {
        box.classList.add('disabled'); // Others are disabled
      }

      // Hide the preview image and show overlay initially
      previewImg.style.display = 'none'; // Ensure preview image is hidden
      overlay.style.display = 'flex'; // Ensure overlay is visible

      // Add click listener to each image box
      box.addEventListener('click', () => {
        // Only allow clicking the current active box
        if (index === currentUploadIndex) {
          fileInput.click(); // Trigger the hidden file input click
        } else if (index < currentUploadIndex) {
          // Optional: If you want to allow re-uploading an already filled box,
          // you would enable fileInput.click() here and update the corresponding image.
          // For now, it logs a message to reinforce the order constraint.
          console.log(`Image box ${index + 1} is already filled.`);
        } else {
          // Prevents clicking boxes out of order
          console.log(`Please upload image ${currentUploadIndex + 1} first.`);
        }
      });

      // Listen for file selection changes on the hidden file input
      fileInput.addEventListener('change', function() {
        const file = this.files[0]; // Get the selected file
        if (file) {
          const reader = new FileReader(); // Create a FileReader to read the file
          reader.onload = e => {
            // Set the source of the preview image
            previewImg.src = e.target.result;
            previewImg.style.display = 'block'; // Show the image
            overlay.style.display = 'none'; // Hide the "Add Image" overlay
            
            // Update box classes
            box.classList.remove('active-upload'); // Remove active state from current box
            box.classList.remove('disabled'); // Ensure disabled is removed if it was present
            box.classList.add('filled'); // Mark current box as filled

            // If this is the first image, set it as the background for the description box
            if (index === 0) {
              descriptionBG.src = e.target.result;
            }

            // Move to the next upload index
            currentUploadIndex++;

            // Enable the next box if it exists within the bounds
            if (currentUploadIndex < imageBoxes.length) {
              const nextBox = imageBoxes[currentUploadIndex];
              const nextFileInput = fileInputs[currentUploadIndex];

              nextBox.classList.add('active-upload'); // Set next box as active
              nextBox.classList.remove('disabled'); // Enable clicks on the next box
              nextFileInput.disabled = false; // Enable the next hidden file input
            } else {
              console.log("All required images have been uploaded!");
              // You might want to enable the save button here or show a final message
            }
          };
          reader.readAsDataURL(file); // Read the file as a Data URL
        }
      });
    });
  }

  // --- Malaysia State and District Logic ---
  const malaysiaLocations = {
    "Johor": ["Johor Bahru", "Batu Pahat", "Kluang", "Kota Tinggi", "Kulai", "Mersing", "Muar", "Pontian", "Segamat", "Tangkak"],
    "Kedah": ["Alor Setar", "Baling", "Bandar Baharu", "Kubang Pasu", "Kulim", "Langkawi", "Padang Terap", "Pendang", "Sik", "Yan", "Pokok Sena"],
    "Kelantan": ["Kota Bharu", "Bachok", "Dabong", "Gua Musang", "Jeli", "Kuala Krai", "Machang", "Pasir Mas", "Pasir Puteh", "Tanah Merah", "Tumpat"],
    "Melaka": ["Alor Gajah", "Jasin", "Melaka Tengah"],
    "Negeri Sembilan": ["Seremban", "Jempol", "Jelebu", "Kuala Pilah", "Port Dickson", "Rembau", "Tampin"],
    "Pahang": ["Kuantan", "Bentong", "Bera", "Cameron Highlands", "Jerantut", "Kuala Lipis", "Maran", "Pekan", "Raub", "Rompin", "Temerloh"],
    "Penang": ["George Town", "Butterworth", "Bukit Mertajam", "Nibong Tebal", "Balik Pulau"],
    "Perak": ["Ipoh", "Batang Padang", "Hilir Perak", "Kuala Kangsar", "Larut, Matang dan Selama", "Manjung", "Muallim", "Kinta", "Kerian", "Perak Tengah", "Tapah", "Kampar"],
    "Perlis": ["Kangar"],
    "Sabah": ["Kota Kinabalu", "Beaufort", "Beluran", "Keningau", "Kudat", "Lahad Datu", "Papar", "Penampang", "Putatan", "Ranau", "Sandakan", "Semporna", "Tawau", "Tongod", "Kuala Penyu", "Nabawan", "Sipitang", "Telupid", "Tuaran", "Kota Belud", "Kunak"],
    "Sarawak": ["Kuching", "Bintulu", "Kapit", "Limbang", "Miri", "Mukah", "Samarahan", "Sibu", "Sri Aman", "Betong", "Sarikei", "Serian"],
    "Selangor": ["Petaling Jaya", "Klang", "Gombak", "Hulu Langat", "Hulu Selangor", "Kuala Langat", "Kuala Selangor", "Sabak Bernam", "Sepang"],
    "Terengganu": ["Kuala Terengganu", "Besut", "Dungun", "Kemaman", "Marang", "Setiu", "Hulu Terengganu"],
    "Kuala Lumpur": ["Kuala Lumpur"],
    "Labuan": ["Labuan"],
    "Putrajaya": ["Putrajaya"]
  };

  const stateSelect = document.getElementById('state-select');
  const districtSelect = document.getElementById('district-select');

  function populateStates() {
    for (const state in malaysiaLocations) {
      const option = document.createElement('option');
      option.value = state;
      option.textContent = state;
      stateSelect.appendChild(option);
    }
  }

  stateSelect.addEventListener('change', () => {
    const selectedState = stateSelect.value;
    // Clear existing districts
    districtSelect.innerHTML = '<option value="">Select a District</option>';

    if (selectedState) {
      const districts = malaysiaLocations[selectedState];
      districts.forEach(district => {
        const option = document.createElement('option');
        option.value = district;
        option.textContent = district;
        districtSelect.appendChild(option);
      });
      districtSelect.disabled = false; // Enable district combobox
    } else {
      districtSelect.disabled = true; // Disable if no state is selected
    }
  });

</script>

</body>
</html>