<?php
session_start();
require __DIR__ . '/../config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, fullname, password, user_type, is_verified FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        // 1. Check if verified
        if ($user['is_verified'] == 0) {
            $_SESSION['verify_email'] = $email;
            header("Location: ../2fa-verify.php?error=unverified");
            exit();
        }

        // 2. Verify Password
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['fullname'];
            $_SESSION['role'] = $user['user_type'];

            // 3. Redirect based on User Type
            switch ($user['user_type']) {
                case 'admin':
                    header("Location: ../admin/dashboard.php");
                    break;
                case 'teacher':
                    header("Location: ../faculty/dashboard.php");
                    break;
                default:
                    header("Location: ../student/dashboard.php");
                    break;
            }
            exit();
        } else {
            header("Location: ../login.php?error=invalid_creds");
        }
    } else {
        header("Location: ../login.php?error=not_found");
    }
}
