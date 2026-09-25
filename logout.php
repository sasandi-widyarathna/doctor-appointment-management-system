<?php
// Access the session
session_start();

// Remove all session variables
$_SESSION = array();

// Completely destroy the session and its cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Redirect the user to the index page
header("Location: index.php");
exit;
?>