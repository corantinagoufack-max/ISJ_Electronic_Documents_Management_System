<?php
// Start buffering to prevent "Headers already sent" errors
ob_start();
session_start();
require_once '../config/database.php';

if (isset($_POST['update_password'])) {
    // Sanitize inputs
    $token = $conn->real_escape_string($_POST['token']);
    $pass = $_POST['password'];
    $confirmPass = $_POST['confirm_password'];

    // 1. Validate Passwords Match
    if ($pass !== $confirmPass) {
        header("Location: ../passwordreset.php?status=mismatch&token=" . urlencode($token));
        exit();
    }

    // 2. Hash the new password
    $hashedPassword = password_hash($pass, PASSWORD_BCRYPT);

    // 3. Update the user
    // We use a prepared statement for security and to check affected rows
    $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE reset_token = ?");
    $stmt->bind_param("ss", $hashedPassword, $token);

    if ($stmt->execute()) {
        // Check if a row was actually updated
        if ($stmt->affected_rows > 0) {
            // SUCCESS
            ob_end_clean();
            header("Location: ../login.php?reset=success");
            exit();
        } else {
            // TOKEN EXPIRED OR INVALID
            ob_end_clean();
            header("Location: ../passwordreset.php?status=invalid_token");
            exit();
        }
    } else {
        // DATABASE ERROR
        ob_end_clean();
        header("Location: ../passwordreset.php?status=db_error");
        exit();
    }
} else {
    header("Location: ../login.php");
    exit();
}
