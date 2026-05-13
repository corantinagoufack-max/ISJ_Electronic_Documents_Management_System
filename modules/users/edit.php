<?php
require_once '../../config/init.php';
require_once '../../includes/UserManager.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['user_role'] === 'Admin') {
    $userManager = new UserManager($conn);

    $id = $_POST['id'];
    $fullname = $_POST['fullname'] ?? '';

    // Check for duplicates
    if ($userManager->isNameTaken($fullname, $id)) {
        header("Location: ../../edit_user.php?id=$id&error=duplicate&value=" . urlencode($fullname));
        exit();
    }

    $data = [
        'fullname' => $fullname,
        'email'    => $_POST['email'] ?? '',
        'role'     => $_POST['role'] ?? ''
    ];

    if ($userManager->updateUser($id, $data)) {
        header("Location: ../../usermanagement.php?status=success");
    } else {
        header("Location: ../../usermanagement.php?status=error");
    }
    exit();
}
