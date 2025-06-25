<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit();
}

require_once '../../connect.php';

$guestId = $_SESSION['guest_id'];
$name = '';
$email = '';
$phoneNumber = '';
$picture = '';
$message = '';
$error = '';

// Fetch user info
try {
    $stmt = $conn->prepare("SELECT guest_name, guest_email, guest_phone_number, guest_profile_picture FROM guest WHERE guest_id = ?");
    $stmt->bind_param("s", $guestId);
    $stmt->execute();
    $stmt->bind_result($name, $email, $phoneNumber, $picture);
    $stmt->fetch();
    $stmt->close();
} catch (Exception $e) {
    $error = 'Could not load your data.';
}

$pictureDisplay = $picture ? '../../' . $picture : '../../assets/default_profile.png';

// Update info
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $newName = trim($_POST['name'] ?? '');
    $newEmail = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $newPhone = trim($_POST['phone_number'] ?? '');
    $newPicturePath = $picture;

    if (!$newName || !$newEmail || !$newPhone) {
        $error = 'Please fill out all fields.';
    } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email is not valid.';
    } else {
        $uploadDir = 'uploads/profile_pictures/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
            $tmp = $_FILES['picture']['tmp_name'];
            $nameFile = basename($_FILES['picture']['name']);
            $ext = strtolower(pathinfo($nameFile, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($ext, $allowed)) {
                $newNameFile = uniqid('img_') . ".$ext";
                $newPath = $uploadDir . $newNameFile;

                if (move_uploaded_file($tmp, $newPath)) {
                    if ($picture && file_exists('../../' . $picture) && strpos($picture, 'default_profile.png') === false) {
                        unlink('../../' . $picture);
                    }
                    $newPicturePath = $newPath;
                    $pictureDisplay = '../../' . $newPicturePath;
                } else {
                    $error = 'Image upload failed.';
                }
            } else {
                $error = 'Image must be jpg, png, gif.';
            }
        }

        if (!$error) {
            try {
                // Update guest table
                $update = $conn->prepare("UPDATE guest SET guest_name = ?, guest_email = ?, guest_phone_number = ?, guest_profile_picture = ? WHERE guest_id = ?");
                $update->bind_param("sssss", $newName, $newEmail, $newPhone, $newPicturePath, $guestId);
                
                if ($update->execute()) {
                    // Update host table if email exists
                    $hostCheck = $conn->prepare("SELECT host_id FROM host WHERE host_email = ?");
                    $hostCheck->bind_param("s", $email); // Old email to find the correct host record
                    $hostCheck->execute();
                    $hostCheck->store_result();

                    if ($hostCheck->num_rows > 0) {
                        $updateHost = $conn->prepare("UPDATE host SET host_name = ?, host_email = ?, host_phone_number = ? WHERE host_email = ?");
                        $updateHost->bind_param("ssss", $newName, $newEmail, $newPhone, $email);
                        $updateHost->execute();
                        $updateHost->close();
                    }
                    $hostCheck->close();

                    $_SESSION['name'] = $newName;
                    $_SESSION['email'] = $newEmail;
                    header("Location: profile.php?message=" . urlencode("Updated successfully"));
                    exit();
                } else {
                    $error = 'Update failed.';
                }
                $update->close();
            } catch (Exception $e) {
                $error = 'Something went wrong.';
            }
        }
    }
}

// Sign out
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

// Get message from URL
if (isset($_GET['message'])) $message = htmlspecialchars($_GET['message']);
if (isset($_GET['error'])) $error = htmlspecialchars($_GET['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>StayNest - My Account</title>
    <link rel="stylesheet" href="../../HTML/Home/css/profilesheet.css">
    <link rel="stylesheet" href="../../CSS/Guest/GuestHeader.css?v=4">
</head>
<body>
<header>
   <?php include('../../HTML/Guest/GuestHeader.php'); ?>
</header>
<div class="profile-container">
    <div class="profile-card">
        <div class="profile-header">
            <div class="profile-picture-container">
                <img src="<?php echo htmlspecialchars($pictureDisplay); ?>" alt="User Picture" class="profile-picture-preview">
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
                <label>Name</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone_number" value="<?php echo htmlspecialchars($phoneNumber); ?>" required>
            </div>
            <div class="form-group">
                <label>Change Picture</label>
                <input type="file" name="picture" accept="image/*">
            </div>
            <button type="submit" name="update" class="save-changes-button">Save</button>
        </form>

        <form action="profile.php" method="POST" class="sign-out-form">
            <button type="submit" name="logout" class="sign-out-button">Log Out</button>
        </form>
    </div>
</div>
<div>
    <?php include "../../Header_Footer/Footer.html"; ?>
</div>
</body>
</html>
