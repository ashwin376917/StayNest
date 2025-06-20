<?php
session_start();

require_once '../../connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $guestEmail = filter_input(INPUT_POST, 'guest_email', FILTER_SANITIZE_EMAIL);
    $guestPassword = $_POST['guest_password'] ?? '';

    if ($conn->connect_error) {
        $error = "Database connection failed.";
    } elseif (empty($guestEmail) || empty($guestPassword)) {
        $error = 'Please enter both email and password.';
    } elseif (!filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            $stmt = $conn->prepare("SELECT guest_id, guest_name, guest_email, guest_password FROM guest WHERE guest_email = ?");
            $stmt->bind_param("s", $guestEmail);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows === 1) {
                $stmt->bind_result($guestId, $guestName, $dbEmail, $hashedPassword);
                $stmt->fetch();

                if (password_verify($guestPassword, $hashedPassword)) {
                    $_SESSION['loggedin'] = true;
                    $_SESSION['guest_id'] = $guestId;
                    $_SESSION['guest_name'] = $guestName;
                    $_SESSION['guest_email'] = $dbEmail;

                    header('Location: ../Guest/Homepage.html');
                    exit();
                } else {
                    $error = 'Invalid email or password.';
                }
            } else {
                $error = 'Invalid email or password.';
            }

            $stmt->close();
        } catch (Exception $e) {
            $error = 'An error occurred during login. Please try again later.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>StayNest - Log In</title>

  <link rel="stylesheet" href="css/authsheet.css">
</head>
<body>

  <div class="container">
    <div class="left">

      <div class="top-bar">
        <div class="logo">
          <img src="../../assets/staynest_logo.png" alt="StayNest Logo" />
          <span>StayNest</span>
        </div>
        <div class="signin-link">
          Don't have an account? <a href="signup.php">Sign Up</a>
        </div>
      </div>

      <div class="form-box">
        <h2>Sign In</h2>

        <?php if (!empty($error)): ?>
            <div style="color: red; margin-bottom: 15px;"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
          <input type="email" name="guest_email" placeholder="Email" required value="<?php echo isset($_POST['guest_email']) ? htmlspecialchars($_POST['guest_email']) : ''; ?>"/>
          <input type="password" name="guest_password" placeholder="Password" required />
          <div class="forgot-password">
            <a href="forgotpass.php">Forgot password?</a>
          </div>
          <button type="submit">Sign In</button>
        </form>

        <div class="social-login">Or sign in with</div>
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
          By signing in, you agree with the<br />
          <a href="#">Terms of Use</a> & <a href="#">Privacy Policy</a>
        </div>
      </div>

    </div>

    <div class="right"></div>
  </div>

</body>
</html>
