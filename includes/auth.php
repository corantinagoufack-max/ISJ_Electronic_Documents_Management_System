<?php
// includes/auth.php
session_start();
require_once '../config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    $selected_role = $_POST['role'];

    // 1. Fetch ALL users matching this email (since email is no longer unique)
    $stmt = $conn->prepare("SELECT id, fullname, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    $authenticated_user = null;
    $role_mismatch = false;

    // 2. Loop through results to find the specific account matching the password
    while ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            // Found the right password, now check the role
            if ($user['role'] === $selected_role) {
                $authenticated_user = $user;
                break; // Stop looking, we found the perfect match
            } else {
                $role_mismatch = true;
            }
        }
    }

    // 3. Handle the login outcome
    if ($authenticated_user) {
        // Success: Set session variables
        $_SESSION['user_id'] = $authenticated_user['id'];
        $_SESSION['user_name'] = $authenticated_user['fullname'];
        $_SESSION['user_role'] = $authenticated_user['role'];

        header("Location: ../dashboard.php");
        exit();
    } else {
        // Decide which error to show
        if ($role_mismatch) {
            header("Location: ../login.php?error=role_mismatch");
        } else {
            header("Location: ../login.php?error=invalid_credentials");
        }
        exit();
    }
} else {
    header("Location: ../login.php");
    exit();
}
