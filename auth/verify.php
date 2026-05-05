<?php
session_start();
require_once "../include/db.php";

if(!isset($_SESSION['reset_email'])){
header("Location: forgot_password.php");
exit;
}

$email=$_SESSION['reset_email'];
$msg="";
$success="";

if(isset($_SESSION['otp_sent'])){
$success="<span class='otp-success'>New OTP sent.</span>";
unset($_SESSION['otp_sent']);
}

if($_SERVER['REQUEST_METHOD']==='POST'){

$d1=$_POST['d1'] ?? '';
$d2=$_POST['d2'] ?? '';
$d3=$_POST['d3'] ?? '';
$d4=$_POST['d4'] ?? '';
$d5=$_POST['d5'] ?? '';

$otp=$d1.$d2.$d3.$d4.$d5;

if(strlen($otp)!==5){
$msg="Please enter the complete verification code.";
}else{

$stmt=$conn->prepare("SELECT id FROM users WHERE email=? AND reset_token=? LIMIT 1");
$stmt->bind_param("ss",$email,$otp);
$stmt->execute();
$res=$stmt->get_result();

if(!$res->num_rows){
$msg="The verification code you entered is incorrect.";
}else{

$_SESSION['otp_verified']=true;
$_SESSION['reset_email']=$email;

header("Location: reset_password.php");
exit;

}

}

}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Verify Code</title>

<link rel="stylesheet" href="../css/auth/auth_login.css">
<link rel="stylesheet" href="../css/auth/auth_verify.css">
<link rel="stylesheet" href="../mobile_css/auth_mobile_view_verify.css" media="(max-width:600px)">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>

<body>

<div class="login-wrapper">
<div class="auth-container">

<a href="forgot_password.php" class="back-btn">
<img src="../img/auth_img/back.png">
</a>

<h2 class="fp-title">Verify OTP</h2>

<p class="verify-text">
Please Enter Your OTP Number Sent to<br>
<span class="email"><?php echo $email; ?></span>
</p>

<form method="POST" id="verifyForm">

<div class="otp-box">
<input maxlength="1" name="d1" required autofocus inputmode="numeric" pattern="[0-9]*">
<input maxlength="1" name="d2" required inputmode="numeric" pattern="[0-9]*">
<input maxlength="1" name="d3" required inputmode="numeric" pattern="[0-9]*">
<input maxlength="1" name="d4" required inputmode="numeric" pattern="[0-9]*">
<input maxlength="1" name="d5" required inputmode="numeric" pattern="[0-9]*">
</div>

<div class="otp-row">

<p class="otp-error" id="msgBox"><?php echo $msg ?: $success; ?></p>

<a href="#" class="resend-link" id="resendBtn">
Resend OTP
</a>

</div>

<button type="submit" id="verifyBtn" disabled>
<span class="btn-text">Verify</span>
<div class="dots"><span>.</span><span>.</span><span>.</span></div>
</button>

</form>

</div>
</div>

<script>
const form=document.getElementById("verifyForm");
const btn=document.getElementById("verifyBtn");
const inputs=document.querySelectorAll(".otp-box input");
const resendBtn=document.getElementById("resendBtn");
const msgBox=document.getElementById("msgBox");

let cooldown=0;
let timer=null;

window.onload=()=>{
inputs[0].focus();
autoHideMessage();
checkCooldown();
};

function isComplete(){
return [...inputs].every(i=>i.value!=="");
}

inputs.forEach((input,index)=>{

input.addEventListener("input",()=>{

input.value=input.value.replace(/[^0-9]/g,"");

if(input.value && index<inputs.length-1){
inputs[index+1].focus();
}

inputs.forEach(i=>{
i.classList.remove("error");
i.classList.remove("success");
});

if(isComplete()){
btn.classList.add("loading");
btn.disabled=true;

setTimeout(()=>{
form.submit();
},1200);
}

});

input.addEventListener("keydown",(e)=>{
if(e.key==="Backspace" && input.value==="" && index>0){
inputs[index-1].focus();
}
});

});

<?php if($msg): ?>
inputs.forEach(i=>{
i.classList.add("error");
i.value="";
});
btn.classList.remove("loading");
btn.disabled=true;
inputs[0].focus();
<?php endif; ?>

function autoHideMessage(){
if(msgBox.innerText.trim()!==""){
setTimeout(()=>{
msgBox.innerHTML="";
},5000);
}
}

resendBtn.addEventListener("click",async(e)=>{
e.preventDefault();

if(resendBtn.classList.contains("disabled")){
return;
}

resendBtn.innerHTML='Sending<span id="dots">.</span>';
resendBtn.classList.add("disabled");

let dotCount=1;
let dotInterval=setInterval(()=>{
dotCount = (dotCount % 3) + 1;
document.getElementById("dots").innerText=".".repeat(dotCount);
},500);

try{

let res=await fetch("resend_otp.php");
let data=await res.text();

clearInterval(dotInterval);

msgBox.innerHTML="<span class='otp-success'>New OTP sent.</span>";
autoHideMessage();

startCooldown(60);

}catch(err){

clearInterval(dotInterval);

msgBox.innerHTML="<span class='otp-error'>Failed to resend OTP.</span>";
autoHideMessage();

resendBtn.classList.remove("disabled");
resendBtn.innerText="Resend OTP";

}

});

function startCooldown(seconds){

cooldown=seconds;
localStorage.setItem("otpCooldownEnd",Date.now()+(seconds*1000));

runCooldown();

}

function checkCooldown(){

let end=localStorage.getItem("otpCooldownEnd");

if(end){

let remaining=Math.floor((end-Date.now())/1000);

if(remaining>0){
startCooldown(remaining);
}
}

}

function runCooldown(){

resendBtn.classList.add("disabled");

timer=setInterval(()=>{
cooldown--;
resendBtn.innerText=`Resend OTP (${cooldown})`;

if(cooldown<=0){
clearInterval(timer);
resendBtn.innerText="Resend OTP";
resendBtn.classList.remove("disabled");
localStorage.removeItem("otpCooldownEnd");
}
},1000);

}
</script>

</body>
</html>