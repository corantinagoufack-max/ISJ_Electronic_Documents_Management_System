<?php
// 1. Initialize the session
session_start();

// 2. Clear all session variables from memory
$_SESSION = array();

// 3. Clear the session cookie from the user's browser
// This ensures the session cannot be hijacked or restored easily
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 4. Destroy the session on the server side
session_destroy();

// 5. Redirect to login.php
// Since logout.php and login.php are in the same folder, 
// we use a direct path without "../"
header("Location: login.php?logout=success");
exit();
