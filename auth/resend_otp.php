<?php
session_start();
require_once "../include/db.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require "../PHPMailer/src/Exception.php";
require "../PHPMailer/src/PHPMailer.php";
require "../PHPMailer/src/SMTP.php";

if(!isset($_SESSION['reset_email'])){
    header("Location: forgot_password.php");
    exit();
}

$email = $_SESSION['reset_email'];

$sql = "SELECT id FROM users WHERE email=? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s",$email);
$stmt->execute();
$res = $stmt->get_result();

if($res->num_rows == 1){

    $otp = rand(10000,99999);

    $update = $conn->prepare("UPDATE users SET reset_token=? WHERE email=?");
    $update->bind_param("ss",$otp,$email);
    $update->execute();

    $mail = new PHPMailer(true);

    try{

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
        $mail->Subject = "Your OTP Code";
        $mail->Body = "
            Your new OTP code is: <b>$otp</b><br><br>
            Please enter this code to verify your account.
        ";

        $mail->send();

        $_SESSION['otp_sent'] = true;

        header("Location: verify.php");
        exit();

    }catch(Exception $e){
        echo "Mailer Error: ".$mail->ErrorInfo;
        exit();
    }

}else{
    header("Location: forgot_password.php");
    exit();
}
?>