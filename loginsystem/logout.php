<?php
// Session suru karne
session_start();

// Sagle session variables kadhun takne
$_SESSION = array();

// Jar session cookie asel tar ti pan expire karne
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Session purna pane nashta karne
session_destroy();

// User la login page var (index.php) redirect karne
header("Location: index.php");
exit();
?>