<?php
session_start();
require_once "../include/db.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require "../PHPMailer/src/Exception.php";
require "../PHPMailer/src/PHPMailer.php";
require "../PHPMailer/src/SMTP.php";

if(!isset($_SESSION['user_id'])) exit("unauthorized");

$id = intval($_SESSION['user_id']);

$email = isset($_POST['email']) ? trim($_POST['email']) : '';

if(empty($email)) exit("no_email");

$otp = rand(1000, 9999);

$expire = date("Y-m-d H:i:s", strtotime("+5 minutes"));

mysqli_query($conn, "
UPDATE users 
SET reset_token='$otp', reset_expire='$expire'
WHERE id=$id
");

$mail = new PHPMailer(true);

try {

    $mail->isSMTP();
    $mail->Host = "smtp.gmail.com";
    $mail->SMTPAuth = true;
    $mail->Username = "qrseatid.system@gmail.com";
    $mail->Password = "prsf xafj coub twts";
    $mail->SMTPSecure = "ssl";
    $mail->Port = 465;

    $mail->setFrom("qrseatid.system@gmail.com", "BPC COMLAB");
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = 'OTP Reset';
    $mail->Body = "
        <h2>Password Reset</h2>
        <p>Your OTP:</p>
        <h1 style='letter-spacing:3px;'>$otp</h1>
        <p>This code expires in 5 minutes.</p>
    ";

    $mail->send();

    echo "sent";

} catch (Exception $e) {

    echo "failed";

}
?>