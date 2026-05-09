<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/UserManager.php';

// Security: Admin only
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    exit("Unauthorized");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // $conn comes from your config/database.php
    $userManager = new UserManager($conn);

    $userData = [
        'fullname' => $_POST['fullname'],
        'email'    => $_POST['email'],
        'role'     => $_POST['role'],
        'password' => $_POST['password']
    ];

    try {
        if ($userManager->createNewUser($userData)) {
            // Success: Redirect to usermanagement.php with a success message
            header("Location: ../../usermanagement.php?status=created");
            exit();
        } else {
            throw new Exception("Insert failed.");
        }
    } catch (Exception $e) {
        // Error: Redirect back with error message
        header("Location: create.php?error=" . urlencode($e->getMessage()));
        exit();
    }
}
