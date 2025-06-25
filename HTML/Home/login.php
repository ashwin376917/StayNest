<?php
session_start();
require_once '../../connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    if ($conn->connect_error) {
        $error = "Database connection failed.";
    } elseif (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            // 1. Check Guest Table
            $stmt = $conn->prepare("SELECT guest_id, guest_name, guest_email, guest_password FROM guest WHERE guest_email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows === 1) {
                $stmt->bind_result($guestId, $guestName, $dbEmail, $hashedPassword);
                $stmt->fetch();

                if (password_verify($password, $hashedPassword)) {
                    $_SESSION['loggedin'] = true;
                    $_SESSION['user_role'] = 'guest';
                    $_SESSION['guest_id'] = $guestId;
                    $_SESSION['name'] = $guestName;
                    $_SESSION['email'] = $dbEmail;

                    header('Location: ../../HTML/Guest/AfterLoginHomepage.php');
                    exit();
                }
            }
            $stmt->close();

            // 2. Check Admin Table
            $stmt = $conn->prepare("SELECT admin_id, admin_name, admin_email, admin_password FROM admin WHERE admin_email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows === 1) {
                $stmt->bind_result($adminId, $adminName, $dbEmail, $hashedPassword);
                $stmt->fetch();

                if (password_verify($password, $hashedPassword)) {
                    $_SESSION['loggedin'] = true;
                    $_SESSION['user_role'] = 'admin';
                    $_SESSION['admin_id'] = $adminId;
                    $_SESSION['name'] = $adminName;
                    $_SESSION['email'] = $dbEmail;

                    header('Location: ../../HTML/Admin/AdminDashboard.php');
                    exit();
                }
            }
            $stmt->close();

            // 3. If not found in both tables
            $error = 'Invalid email or password.';

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
          <input type="email" name="email" placeholder="Email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"/>
          <input type="password" name="password" placeholder="Password" required />
          <!-- <div class="forgot-password">
            <a href="forgotpass.php">Forgot password?</a>
          </div> -->
          <button type="submit">Sign In</button>
        </form>

        
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
