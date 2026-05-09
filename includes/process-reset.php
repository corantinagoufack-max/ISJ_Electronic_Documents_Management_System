<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../config/database.php';
require_once '../vendors/phpmailer/Exception.php';
require_once '../vendors/phpmailer/PHPMailer.php';
require_once '../vendors/phpmailer/SMTP.php';

session_start();

if (isset($_POST['submit_reset'])) {
    $email = $conn->real_escape_string($_POST['email']);

    // FIXED: Changed full_name to fullname
    $result = $conn->query("SELECT id, fullname FROM users WHERE email = '$email'");

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // 2. Generate a 6-digit numeric code
        $code = strval(rand(100000, 999999));
        $expiry = date("Y-m-d H:i:s", strtotime('+15 minutes'));

        // 3. Store code in database (Using your existing reset_token column)
        $conn->query("UPDATE users SET reset_token = '$code', reset_expiry = '$expiry' WHERE email = '$email'");

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'corantin2005@gmail.com';
            $mail->Password   = 'tvch fxcv dyag bvgj';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // FIXED: Changed full_name to fullname
            $mail->setFrom('no-reply@isj-dms.edu', 'ISJ-DMS Security');
            $mail->addAddress($email, $user['fullname']);

            $mail->isHTML(true);
            $mail->Subject = 'Your Password Reset Code';
            $mail->Body    = "
                <div style='font-family: sans-serif; text-align: center; padding: 20px;'>
                    <h2>Password Reset Request</h2>
                    <p>Hello " . htmlspecialchars($user['fullname']) . ",</p>
                    <p>Use the following code to reset your password. It expires in 15 minutes:</p>
                    <div style='font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #0369a1; margin: 20px;'>$code</div>
                </div>";

            $mail->send();

            // Save email in session so we know who is verifying the code
            $_SESSION['reset_email'] = $email;
            header("Location: ../verify-password.php");
            exit();
        } catch (Exception $e) {
            header("Location: ../passwordreset.php?status=error");
        }
    } else {
        header("Location: ../passwordreset.php?status=notfound");
    }
    exit();
}
