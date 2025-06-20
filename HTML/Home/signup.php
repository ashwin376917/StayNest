<?php

require_once 'connect.php'; // Ensure this path is correct for your setup

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phoneNumber = trim($_POST['phone_number'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($conn->connect_error) { // Check connection here too in case of late error
        $error = "Database connection failed.";
        error_log("Database connection failed inside signup.php: " . $conn->connect_error);
    } elseif (empty($fullName) || empty($email) || empty($phoneNumber) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } else {
        try {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM guest WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();

            if ($count > 0) {
                $error = 'An account with this email already exists. Please sign in or use a different email.';
            } else {
                $guestId = 'G' . uniqid() . bin2hex(random_bytes(4));

                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare(
                    "INSERT INTO guest (guest_id, name, email, phone_number, password)
                     VALUES (?, ?, ?, ?, ?)"
                );
                $stmt->bind_param("sssss", $guestId, $fullName, $email, $phoneNumber, $hashedPassword);

                if ($stmt->execute()) {
                    $message = 'Account created successfully!';
                    $_POST = array(); // Clear input values after successful registration
                } else {
                    $error = 'An error occurred during registration. Please try again later.';
                    echo "User registration execution error: $stmt->error";
                }
                $stmt->close();
            }

        } catch (Exception $e) {
            error_log("User registration error: " . $e->getMessage());
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
          <input type="text" name="full_name" placeholder="Full name" required value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"/>
          <input type="email" name="email" placeholder="Email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"/>
          <input type="text" name="phone_number" placeholder="Phone Number" required value="<?php echo isset($_POST['phone_number']) ? htmlspecialchars($_POST['phone_number']) : ''; ?>"/>
          <input type="password" name="password" placeholder="Password" required />
          <button type="submit">Sign up</button>
        </form>

        <div class="social-login">Or sign up with</div>
        <div class="social-icons">
          <button class="social-btn google-btn">
            <img src="HomeAsset/iconGoogle.png" alt="Google" />
          </button>
          <button class="social-btn facebook-btn">
            <img src="HomeAsset/iconFacebook.png" alt="Facebook" />
          </button>
          <button class="social-btn apple-btn">
            <img src="HomeAsset/iconApple.png" alt="Apple" />
          </button>
        </div>

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