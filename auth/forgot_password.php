<?php
session_start();
require_once "../include/db.php";

$from = $_GET['from'] ?? 'main';
$back = 'login.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require "../PHPMailer/src/Exception.php";
require "../PHPMailer/src/PHPMailer.php";
require "../PHPMailer/src/SMTP.php";

$msg = "";

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $email = strtolower(trim($_POST['email'] ?? ''));

    if(!$email){
        $msg = "Enter your email";
    }else{

        $stmt = $conn->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
        $stmt->bind_param("s",$email);
        $stmt->execute();
        $res = $stmt->get_result();

        if(!$res->num_rows){
            $msg = "Email not found";
        }else{

            $otp = random_int(10000,99999);
            $expire = date("Y-m-d H:i:s", strtotime("+5 minutes"));

            // Update OTP + expiration
            $upd = $conn->prepare("UPDATE users SET reset_token=?, reset_expire=? WHERE email=?");
            $upd->bind_param("sss",$otp,$expire,$email);

            if(!$upd->execute()){
                $msg = "Failed to generate OTP";
            }else{

                $mail = new PHPMailer(true);

                try{

                    // SMTP CONFIG
                    $mail->isSMTP();
                    $mail->Host = "smtp.gmail.com";
                    $mail->SMTPAuth = true;
                    $mail->Username = "qrseat.system@gmail.com";

                    // ✅ NO SPACES
                    $mail->Password = "vuagtmrwjcrcgtil";

                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port = 465;

                    // EMAIL SETTINGS
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
                        $_SESSION['reset_email'] = $email;
                        header("Location: verify.php");
                        exit;
                    }else{
                        $msg = "Email sending failed";
                    }

                }catch(Exception $e){
                    $msg = "Mailer Error: " . $mail->ErrorInfo;
                }

            }

        }

    }

}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Forgot Password</title>

<link rel="stylesheet" href="../css/auth/auth_login.css">
<link rel="stylesheet" href="../css/auth/auth_forgot_password.css">
<link rel="stylesheet" href="../mobile_css/auth_mobile_view_forgot_password.css" media="(max-width:600px)">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>

<body>

<div class="forgot-wrapper">
<div class="auth-container">

<a href="<?php echo $back; ?>" class="back-btn">
<img src="../img/auth_img/back.png">
</a>

<h2 class="fp-title">Forgot Password</h2>

<p class="forgot-text">
Provide your email to receive your verification code.
</p>

<form method="POST" onsubmit="showLoader()">

<div class="input-group">
<input type="email" name="email" id="emailInput" placeholder="Email Address:" required
class="<?php echo ($msg==='Email not found')?'input-error':''; ?>">

<div class="field-msg"><?php echo $msg; ?></div>
</div>

<button type="submit" id="sendBtn" disabled>
<span class="btn-text">Send</span>
<div class="dots">
<span>.</span><span>.</span><span>.</span>
</div>
</button>

</form>

</div>
</div>

<script>
const email = document.getElementById("emailInput");
const btn = document.getElementById("sendBtn");

function checkEmail(){
    btn.disabled = email.value.trim()==="";
}

email.addEventListener("input",()=>{
    email.classList.remove("input-error");
    document.querySelector(".field-msg").innerText="";
    checkEmail();
});

window.addEventListener("load",checkEmail);

function showLoader(){
    btn.classList.add("loading");
    btn.disabled=true;
}
</script>

</body>
</html>