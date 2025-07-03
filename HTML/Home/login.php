<?php
session_start();
require_once '../../connect.php'; // Your database connection file

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
            $stmt = $conn->prepare("SELECT guest_id, guest_name, guest_email, guest_password, isBan FROM guest WHERE guest_email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows === 1) {
                $stmt->bind_result($guestId, $guestName, $dbEmail, $hashedPassword, $isBan);
                $stmt->fetch();

                if (password_verify($password, $hashedPassword)) {
                    if ($isBan == 1) {
                        $error = 'Your account has been suspended. Please contact support.';
                    } else {
                        // User is not banned, proceed with guest login
                        $_SESSION['loggedin'] = true;
                        $_SESSION['user_role'] = 'guest';
                        $_SESSION['guest_id'] = $guestId;
                        $_SESSION['name'] = $guestName;
                        $_SESSION['email'] = $dbEmail;

                        // NEW LOGIC: Check if this guest is also a host and set host_id in session
                        $hostStmt = $conn->prepare("SELECT host_id FROM host WHERE guest_id = ?");
                        if ($hostStmt === false) {
                            error_log("Prepare statement failed (host check during login): " . $conn->error);
                            // Log error but don't stop login, just won't set host_id for now
                        } else {
                            $hostStmt->bind_param("s", $guestId); // 's' for string ID
                            $hostStmt->execute();
                            $hostStmt->store_result();
                            if ($hostStmt->num_rows === 1) {
                                $hostStmt->bind_result($hostId);
                                $hostStmt->fetch();
                                $_SESSION['host_id'] = $hostId; // Set host_id if guest is also a host
                                $_SESSION['user_role'] = 'host'; // Update role to host
                            }
                            $hostStmt->close();
                        }

                        header('Location: ../../HTML/Guest/AfterLoginHomepage.php');
                        exit();
                    }
                } else {
                    $error = 'Invalid email or password.'; // Password didn't match
                }
            }
            $stmt->close(); // Close statement for guest table

            // If no error yet (meaning it wasn't a guest or password didn't match), check admin table
            if (empty($error)) {
                // 2. Check Admin Table
                $stmt = $conn->prepare("SELECT admin_id, admin_name, admin_email, admin_password FROM admin WHERE admin_email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows === 1) {
                    $stmt->bind_result($adminId, $adminName, $dbEmail, $dbPassword);
                    $stmt->fetch();

                    if ($password === $dbPassword) { // Direct comparison for plain text (consider hashing admin passwords too!)
                        $_SESSION['loggedin'] = true;
                        $_SESSION['user_role'] = 'admin';
                        $_SESSION['admin_id'] = $adminId;
                        $_SESSION['name'] = $adminName;
                        $_SESSION['email'] = $dbEmail;

                        header('Location: ../../HTML/Admin/ViewUserList.php');
                        exit();
                    } else {
                        $error = 'Invalid email or password.'; // Password didn't match
                    }
                } else {
                    // 3. If not found in both tables or password didn't match
                    $error = 'Invalid email or password.';
                }
                $stmt->close(); // Close statement for admin table
            }


        } catch (Exception $e) {
            $error = 'An error occurred during login. Please try again later.';
            // Log the actual error for debugging
            error_log("Login error: " . $e->getMessage());
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
                    <div style="color: red; margin-bottom: 15px; text-align: center;"><?php echo $error; ?></div>
                <?php endif; ?>

                <form action="login.php" method="POST">
                    <input type="email" name="email" placeholder="Email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"/>
                    <input type="password" name="password" placeholder="Password" required />
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