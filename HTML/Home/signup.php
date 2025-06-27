<?php
require_once '../../connect.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $guestName = trim($_POST['name'] ?? '');
    $guestEmail = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $guestPhoneNumber = trim($_POST['phone_number'] ?? '');
    $guestPassword = $_POST['password'] ?? '';

    // --- Phone Number Validation Function ---
    function isValidMalaysianPhoneNumber($phoneNumber) {
        $cleanedPhoneNumber = preg_replace('/[\s-]+/', '', $phoneNumber);

    
        $pattern = '/^(01[0-46-9]\d{7,8}|0[2-9]\d{7,9})$/';
        $pattern = '/^0\d{8,10}$/';

        return preg_match($pattern, $cleanedPhoneNumber);
    }


    if ($conn->connect_error) {
        $error = "Database connection failed.";
    } elseif (empty($guestName) || empty($guestEmail) || empty($guestPhoneNumber) || empty($guestPassword)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($guestPassword) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif (!isValidMalaysianPhoneNumber($guestPhoneNumber)) { // Added phone number validation
        $error = 'Please enter a valid Malaysian phone number (e.g., 0123456789 or 031234567).';
    }
    else {
        try {
            // Check if email already exists in guest table
            $stmt = $conn->prepare("SELECT COUNT(*) FROM guest WHERE guest_email = ?");
            $stmt->bind_param("s", $guestEmail);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();

            // Check if phone number already exists in guest table to prevent duplicate accounts
            $stmt_phone = $conn->prepare("SELECT COUNT(*) FROM guest WHERE guest_phone_number = ?");
            $stmt_phone->bind_param("s", $guestPhoneNumber);
            $stmt_phone->execute();
            $stmt_phone->bind_result($phoneCount);
            $stmt_phone->fetch();
            $stmt_phone->close();


            if ($count > 0) {
                $error = 'An account with this email already exists. Please sign in or use a different email.';
            } elseif ($phoneCount > 0) { // Check for duplicate phone number
                $error = 'An account with this phone number already exists. Please sign in or use a different phone number.';
            }
            else {
                $guestId = 'G' . uniqid(); // Generate guest ID
                $hostId = 'H' . uniqid();   // Generate host ID
                $hashedPassword = password_hash($guestPassword, PASSWORD_DEFAULT);

                // Start a transaction for atomicity
                $conn->begin_transaction();

                // 1. Insert into guest table
                $stmt_guest = $conn->prepare(
                    "INSERT INTO guest (guest_id, guest_name, guest_email, guest_phone_number, guest_password)
                    VALUES (?, ?, ?, ?, ?)"
                );
                $stmt_guest->bind_param("sssss", $guestId, $guestName, $guestEmail, $guestPhoneNumber, $hashedPassword);

                if ($stmt_guest->execute()) {
                    $stmt_guest->close();

                    // 2. Insert into host table with isApprove = 0 (default pending status)
                    $isApproveDefault = 0; // Default: Not approved
                    $bannedDefault = 0; // Default: Not banned

                    $stmt_host = $conn->prepare(
                        "INSERT INTO host (host_id, guest_id, isApprove, banned)
                        VALUES (?, ?, ?, ?)"
                    );
                    $stmt_host->bind_param("ssii", $hostId, $guestId, $isApproveDefault, $bannedDefault);

                    if ($stmt_host->execute()) {
                        $stmt_host->close();
                        $conn->commit(); // Commit the transaction
                        header("Location: login.php"); // Redirect to login after successful registration
                        exit;
                    } else {
                        $conn->rollback(); // Rollback if host insertion fails
                        $error = 'Guest created but failed to create host record.';
                    }
                } else {
                    $conn->rollback(); // Rollback if guest insertion fails
                    $error = 'An error occurred during registration. Please try again later.';
                }
            }
        } catch (Exception $e) {
            $conn->rollback(); // Ensure rollback on any exception
            // For debugging: error_log("Signup error: " . $e->getMessage());
            $error = 'An unexpected error occurred during registration. Please try again later.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>StayNest - Sign Up</title>
    <link rel="stylesheet" href="css/authsheet.css">
</head>
<body>

<div class="container">
    <div class="left">

      <div class="top-bar">
        <div class="logo">
          <img src="../../assets/staynest_logo.png" alt="StayNest Logo"/>
          <span>StayNest</span>
        </div>
        <div class="signin-link">
          Already have an account? <a href="login.php">Sign In</a>
        </div>
      </div>

      <div class="form-box">
        <h2>Create an account</h2>

        <?php if (!empty($message)): ?>
            <div style="color: green; margin-bottom: 15px;"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div style="color: red; margin-bottom: 15px;"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="signup.php" method="POST">
          <input type="text" name="name" placeholder="Full Name" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"/>
          <input type="email" name="email" placeholder="Email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"/>
          <input type="text" name="phone_number" placeholder="Phone Number" required value="<?php echo isset($_POST['phone_number']) ? htmlspecialchars($_POST['phone_number']) : ''; ?>"/>
          <input type="password" name="password" placeholder="Password" required />
          <button type="submit">Sign up</button>
        </form>

        <div class="terms">
          By signing up, you agree with the<br />
          <a href="#">Terms of Use</a> & <a href="#">Privacy Policy</a>
        </div>
      </div>

    </div>
    <div class="right"></div>
</div>

</body>
</html>