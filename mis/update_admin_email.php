<?php
session_start();
require_once "../include/db.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require "../PHPMailer/src/Exception.php";
require "../PHPMailer/src/PHPMailer.php";
require "../PHPMailer/src/SMTP.php";

if(isset($_POST['email']) && isset($_SESSION['user_id'])){

    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $id = $_SESSION['user_id'];

    $updateEmail = mysqli_query($conn,"UPDATE users SET email='$email' WHERE id=$id");

    if(!$updateEmail){
        echo "db_failed";
        exit;
    }

    $_SESSION['email'] = $email;

    $otp = rand(1000,9999);
    $expire = date("Y-m-d H:i:s", strtotime("+5 minutes"));

    $updateOTP = mysqli_query($conn,"
        UPDATE users 
        SET reset_token='$otp', reset_expire='$expire'
        WHERE id=$id
    ");

    if(!$updateOTP){
        echo "otp_failed";
        exit;
    }

    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();
        $mail->Host = "smtp.gmail.com";
        $mail->SMTPAuth = true;
        $mail->Username = "qrseat.system@gmail.com";

        $mail->Password = "vuagtmrwjcrcgtil";

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        $mail->setFrom("qrseat.system@gmail.com", "BPC COMLAB");
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'OTP Reset';
        $mail->Body = "
            <h2>Password Reset</h2>
            <p>Your OTP:</p>
            <h1 style='letter-spacing:3px;'>$otp</h1>
            <p>This code expires in 5 minutes.</p>
        ";

        if($mail->send()){
            echo "success";
        } else {
            echo "email_failed";
        }

    } catch (Exception $e) {
        echo "Mailer Error: " . $mail->ErrorInfo;
    }
}
?>