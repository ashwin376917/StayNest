<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit();
}

require_once 'connect.php'; // Your database connection

$guestId = $_SESSION['guest_id']; // Get guest_id from session
$name = '';
$email = '';
$phoneNumber = '';
$profilePicture = ''; // Default or path from DB

$message = ''; // For success messages
$error = '';   // For error messages

// Fetch user data
if ($conn->connect_error) {
    $error = "Database connection failed.";
} else {
    try {
        $stmt = $conn->prepare("SELECT name, email, phone_number, profile_picture FROM guest WHERE guest_id = ?");
        $stmt->bind_param("s", $guestId);
        $stmt->execute();
        $stmt->bind_result($name, $email, $phoneNumber, $profilePicture);
        $stmt->fetch();
        $stmt->close();

        // Set a default profile picture if none is found
        $profilePictureDisplay = !empty($profilePicture) ? $profilePicture : 'HomeAsset/default_profile.jpg';

    } catch (Exception $e) {
        $error = 'Could not load profile data. Please try again later.';
    }
}

// Handle form submission for profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $newName = trim($_POST['name'] ?? '');
    $newEmail = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $newPhoneNumber = trim($_POST['phone_number'] ?? '');

    // Validate inputs
    if (empty($newName) || empty($newEmail) || empty($newPhoneNumber)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Handle profile picture upload
        $uploadPath = $profilePicture; // Keep existing path by default
        $uploadDir = '../uploads/profile_pictures/'; // Directory to save profile pictures
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true); // Create directory if it doesn't exist
        }

        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['profile_picture']['tmp_name'];
            $fileName = basename($_FILES['profile_picture']['name']);
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($fileExtension, $allowedExtensions)) {
                $newFileName = uniqid('profile_') . '.' . $fileExtension;
                $newFilePath = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $newFilePath)) {
                    // Delete old picture if it exists and is not the default
                    if (!empty($profilePicture) && file_exists($profilePicture) && strpos($profilePicture, 'placehold.co') === false) {
                        unlink($profilePicture);
                    }
                    $uploadPath = $newFilePath; // Update path for DB
                    $profilePictureDisplay = $newFilePath; // Update for display
                } else {
                    $error = 'Failed to upload new profile picture.';
                }
            } else {
                $error = 'Invalid file type. Only JPG, JPEG, PNG, GIF are allowed.';
            }
        }

        // Update database
        if (empty($error)) { // Only proceed if no file upload errors
            try {
                $updateStmt = $conn->prepare("UPDATE guest SET name = ?, email = ?, phone_number = ?, profile_picture = ? WHERE guest_id = ?");
                $updateStmt->bind_param("sssss", $newName, $newEmail, $newPhoneNumber, $uploadPath, $guestId);

                if ($updateStmt->execute()) {
                    $message = 'Profile updated successfully!';
                    // Update session variables if name or email changed
                    $_SESSION['name'] = $newName;
                    $_SESSION['email'] = $newEmail;
                    // Re-fetch current data to display the latest updates
                    header('Location: profile.php?message=' . urlencode($message)); // Redirect to clear POST data
                    exit();
                } else {
                    $error = 'Error updating profile.';
                }
                $updateStmt->close();
            } catch (Exception $e) {
                $error = 'An error occurred during profile update. Please try again.';
            }
        }
    }
}

// Handle sign out
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sign_out'])) {
    session_unset(); // Unset all session variables
    session_destroy(); // Destroy the session
    header('Location: login.php'); // Redirect to login page
    exit();
}

// Check for messages passed via GET after redirect
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
}
if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
}

// Re-fetch data if page was redirected after update
if (empty($message) && empty($error) && !isset($_POST['update_profile'])) {
    if ($conn->connect_error) {
        $error = "Database connection failed.";
    } else {
        try {
            $stmt = $conn->prepare("SELECT name, email, phone_number, profile_picture FROM guest WHERE guest_id = ?");
            $stmt->bind_param("s", $guestId);
            $stmt->execute();
            $stmt->bind_result($name, $email, $phoneNumber, $profilePicture);
            $stmt->fetch();
            $stmt->close();
            $profilePictureDisplay = !empty($profilePicture) ? $profilePicture : 'https://placehold.co/100x100/aabbcc/ffffff?text=No+Pic';
        } catch (Exception $e) {
            $error = 'Could not reload profile data.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StayNest - Profile</title>
    <link rel="stylesheet" href="css/profilesheet.css"> 
    <link rel="stylesheet" href="../css/footer.css">
</head>
<body>
    <div class="profile-container">
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-picture-container">
                    <img src="<?php echo htmlspecialchars($profilePictureDisplay); ?>" alt="Profile Picture" class="profile-picture-preview">
                </div>
                <h2><?php echo htmlspecialchars($name); ?></h2>
                <p><?php echo htmlspecialchars($email); ?></p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="profile-message success"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="profile-message error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="profile.php" method="POST" enctype="multipart/form-data" class="profile-form">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>

                <div class="form-group">
                    <label for="phone_number">Phone Number</label>
                    <input type="text" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($phoneNumber); ?>" required>
                </div>

                <div class="form-group">
                    <label for="profile_picture">Change Profile Picture</label>
                    <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
                </div>

                <button type="submit" name="update_profile" class="save-changes-button">Save Changes</button>
            </form>

            <form action="profile.php" method="POST" class="sign-out-form">
                <button type="submit" name="sign_out" class="sign-out-button">Sign Out</button>
            </form>
        </div>
    </div>
</body>

<?php
    include "../Footer.html";
?>
</html>