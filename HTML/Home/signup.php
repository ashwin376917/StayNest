<?php
require_once '../../connect.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $guestName = trim($_POST['name'] ?? '');
    $guestEmail = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $guestPhoneNumber = trim($_POST['phone_number'] ?? '');
    $guestPassword = $_POST['password'] ?? '';

    if ($conn->connect_error) {
        $error = "Database connection failed.";
    } elseif (empty($guestName) || empty($guestEmail) || empty($guestPhoneNumber) || empty($guestPassword)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($guestPassword) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } else {
        try {
            // Check if email already exists in guest table
            $stmt = $conn->prepare("SELECT COUNT(*) FROM guest WHERE guest_email = ?");
            $stmt->bind_param("s", $guestEmail);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();

            if ($count > 0) {
                $error = 'An account with this email already exists. Please sign in or use a different email.';
            } else {
                $guestId = 'G' . uniqid();
                $hostId = 'H' . uniqid();
                $hashedPassword = password_hash($guestPassword, PASSWORD_DEFAULT);

                // Insert into guest table
                $stmt = $conn->prepare(
                    "INSERT INTO guest (guest_id, guest_name, guest_email, guest_phone_number, guest_password) 
                    VALUES (?, ?, ?, ?, ?)"
                );
                $stmt->bind_param("sssss", $guestId, $guestName, $guestEmail, $guestPhoneNumber, $hashedPassword);
                
                if ($stmt->execute()) {
                    $stmt->close();

                    // Insert into host table
                    $stmt2 = $conn->prepare(
                        "INSERT INTO host (host_id, host_name, host_email, host_phone_number, host_password) 
                        VALUES (?, ?, ?, ?, ?)"
                    );
                    $stmt2->bind_param("sssss", $hostId, $guestName, $guestEmail, $guestPhoneNumber, $hashedPassword);

                    if ($stmt2->execute()) {
                        $stmt2->close();
                        header("Location: login.php");
                        exit;
                    } else {
                        $error = 'Guest created but failed to create host record.';
                    }
                } else {
                    $error = 'An error occurred during registration. Please try again later.';
                }
            }
        } catch (Exception $e) {
            $error = 'An error occurred during registration. Please try again later.';
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
