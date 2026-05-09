<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendors/phpmailer/Exception.php';
require __DIR__ . '/../vendors/phpmailer/PHPMailer.php';
require __DIR__ . '/../vendors/phpmailer/SMTP.php';
require '../config/database.php';

session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Collect and Sanitize Data
    $fullname = $conn->real_escape_string($_POST['fullname']);
    $email = $conn->real_escape_string($_POST['email']);
    $role = $conn->real_escape_string($_POST['role']); // Important: Get role from form
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $v_code = strval(rand(100000, 999999));

    try {
        // 2. Prepare Database Statement FIRST
        // Ensure your 'users' table has the 'role' column!
        $stmt = $conn->prepare("INSERT INTO users (fullname, email, password, role, verification_code, is_verified) VALUES (?, ?, ?, ?, ?, 0)");
        $stmt->bind_param("sssss", $fullname, $email, $password, $role, $v_code);

        if ($stmt->execute()) {
            // 3. Send Email via PHPMailer
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'corantin2005@gmail.com';
                $mail->Password   = 'tvch fxcv dyag bvgj'; // Your App Password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->setFrom('no-reply@isj-dms.org', 'ISJ-DMS Security');
                $mail->addAddress($email, $fullname);

                $mail->isHTML(true);
                $mail->Subject = 'Verify Your Academic Workspace';
                $mail->Body    = "<h2>Welcome to ISJ-DMS, $fullname!</h2>
                                  <p>You registered as: <b>$role</b></p>
                                  <p>Your verification code is: <b>$v_code</b></p>";

                $mail->send();

                $_SESSION['verify_email'] = $email;

                // Redirect to 2FA page (This is a GET request, so no Resubmission error!)
                header("Location: ../2fa-verify.php");
                exit();
            } catch (Exception $e) {
                // If mail fails, we still created the user, but redirect to login with info
                header("Location: ../login.php?error=mailfail");
                exit();
            }
        }
    } catch (mysqli_sql_exception $e) {
        // Handle duplicate email
        if ($e->getCode() == 1062) {
            header("Location: ../signup.php?error=exists&email=" . urlencode($email));
        } else {
            header("Location: ../signup.php?error=system");
        }
        exit();
    }
}
