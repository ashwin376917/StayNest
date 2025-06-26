<?php
// Start the session (this is crucial to access session variables)
session_start();

// Unset all session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session
session_destroy();

// Redirect to the login page or homepage after logout
// Adjust the path below to where you want the user to go after logging out
header("Location: ../Home/login.php"); // Assuming login.php is in the same directory
exit();
?>