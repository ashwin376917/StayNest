<?php
session_start();

// Basic session check for logged-in user
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['guest_id'])) {
    header('Location: login.php'); // Redirect if not logged in or guest_id missing
    exit();
}

require_once '../../connect.php'; // Adjust path if necessary

$guestId = $_SESSION['guest_id'];
$name = '';
$email = '';
$phoneNumber = '';
$picture = '';
$message = '';
$error = '';

// --- 1. Fetch User Info ---
try {
    $stmt = $conn->prepare("SELECT guest_name, guest_email, guest_phone_number, guest_profile_picture FROM guest WHERE guest_id = ?");
    if (!$stmt) {
        throw new Exception("Prepare statement failed: " . $conn->error);
    }
    $stmt->bind_param("s", $guestId);
    $stmt->execute();
    $stmt->bind_result($name, $email, $phoneNumber, $picture);
    $stmt->fetch();
    $stmt->close();

    
    $pictureDisplay = (!empty($picture) && file_exists('../../' . $picture))
                      ? '../../' . htmlspecialchars($picture)
                      : '../../assets/default_profile.png';

} catch (Exception $e) {
    $error = 'Could not load your data: ' . $e->getMessage();
    // Log the actual error for debugging, but show a user-friendly message
    error_log("Error fetching user data: " . $e->getMessage());
}

// --- 2. Handle Profile Update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $newName = trim($_POST['name'] ?? '');
    $newEmail = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $newPhone = trim($_POST['phone_number'] ?? '');
    $newPicturePath = $picture; // Start with the existing picture path

    // Input validation
    if (!$newName || !$newEmail || !$newPhone) {
        $error = 'Please fill out all required fields.';
    } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email is not valid.';
    } else {
        // Handle file upload
        if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
            $uploadBaseDir = '../../uploads/profile_pictures/'; 
            
            if (!is_dir($uploadBaseDir)) {
                if (!mkdir($uploadBaseDir, 0755, true)) {
                    $error = 'Failed to create upload directory.';
                }
            }

            if (!$error) { 
                $tmp_name = $_FILES['picture']['tmp_name'];
                $file_name = basename($_FILES['picture']['name']);
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];

                if (in_array($file_ext, $allowed_exts)) {
                    $unique_file_name = uniqid('img_') . ".$file_ext";
                    // Store the path relative to the site root in the database
                    $dbPicturePath = 'uploads/profile_pictures/' . $unique_file_name;
                    $target_file_path = $uploadBaseDir . $unique_file_name;

                    if (move_uploaded_file($tmp_name, $target_file_path)) {
                        // If there was an old picture and it's not the default, delete it
                        $oldPicturePathForUnlink = '../../' . $picture; // Path for unlink, relative to profile.php
                        if (!empty($picture) && file_exists($oldPicturePathForUnlink) && strpos($picture, 'default_profile.png') === false) {
                            @unlink($oldPicturePathForUnlink); // @ suppresses errors if file doesn't exist
                        }
                        $newPicturePath = $dbPicturePath; // This path will be stored in the DB
                    } else {
                        $error = 'Failed to move uploaded image.';
                        error_log("Failed to move uploaded file from $tmp_name to $target_file_path. Error: " . $_FILES['picture']['error']);
                    }
                } else {
                    $error = 'Only JPG, JPEG, PNG, GIF files are allowed.';
                }
            }
        }

        // Proceed with database updates if no errors so far
        if (!$error) {
            $conn->begin_transaction(); // Start transaction (good practice even for single table update)

            try {
                // Update guest table only
                $updateGuestStmt = $conn->prepare("UPDATE guest SET guest_name = ?, guest_email = ?, guest_phone_number = ?, guest_profile_picture = ? WHERE guest_id = ?");
                if (!$updateGuestStmt) {
                    throw new Exception("Prepare guest update failed: " . $conn->error);
                }
                $updateGuestStmt->bind_param("sssss", $newName, $newEmail, $newPhone, $newPicturePath, $guestId);

                if (!$updateGuestStmt->execute()) {
                    throw new Exception("Guest update failed: " . $updateGuestStmt->error);
                }
                $updateGuestStmt->close();

                $conn->commit(); // Commit the transaction if all updates succeed

                // Update session variables
                $_SESSION['name'] = $newName;
                $_SESSION['email'] = $newEmail;
                // Re-fetch the updated picture path for immediate display
                $picture = $newPicturePath;
                $pictureDisplay = '../../' . htmlspecialchars($picture); // Update for immediate display

                header("Location: profile.php?message=" . urlencode("Profile updated successfully!"));
                exit();

            } catch (Exception $e) {
                $conn->rollback(); // Rollback changes if any error occurs
                $error = 'Database update failed: ' . $e->getMessage();
                error_log("Transaction error: " . $e->getMessage()); // Log detailed error
            }
        }
    }
}

// --- 3. Handle Logout ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php"); // Redirect to login page
    exit();
}

// Get message/error from URL (after redirect)
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
}
if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StayNest - My Account</title>
    <link rel="stylesheet" href="css/profilesheet.css">
    <link rel="stylesheet" href="css/homeheadersheet.css">
    <link rel="stylesheet" href="../../include/css/footer.css">
    <style>
        /* Optional: Add basic styling for messages here if not in profilesheet.css */
        .profile-message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
        }
        .profile-message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .profile-message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
<header>
    <?php include("homeheader.html"); ?> </header>

<div class="profile-container">
    <div class="profile-card">
        <div class="profile-header">
            <div class="profile-picture-container">
                <img src="<?php echo $pictureDisplay; ?>" alt="User Profile Picture" class="profile-picture-preview">
            </div>
            <h2><?php echo htmlspecialchars($name); ?></h2>
            <p><?php echo htmlspecialchars($email); ?></p>
        </div>

        <?php if ($message): ?>
            <div class="profile-message success"><?php echo $message; ?></div>
        <?php elseif ($error): ?>
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
                <label for="picture">Change Picture</label>
                <input type="file" id="picture" name="picture" accept="image/*">
            </div>
            <button type="submit" name="update" class="save-changes-button">Save Changes</button>
        </form>

        <form action="profile.php" method="POST" class="sign-out-form">
            <button type="submit" name="logout" class="sign-out-button">Log Out</button>
        </form>
    </div>
</div>

<div>
    <?php include "../../include/Footer.html"; ?> </div>

<script>
    document.getElementById('picture').addEventListener('change', function(event) {
        const [file] = event.target.files;
        if (file) {
            document.querySelector('.profile-picture-preview').src = URL.createObjectURL(file);
        }
    });
</script>

</body>
</html>