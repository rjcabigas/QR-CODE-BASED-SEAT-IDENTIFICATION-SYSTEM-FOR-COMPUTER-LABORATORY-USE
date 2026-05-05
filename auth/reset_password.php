<?php
session_start();
require_once "../include/db.php";

if(!isset($_SESSION['reset_email']) || !isset($_SESSION['otp_verified'])){
header("Location: forgot_password.php");
exit;
}

$msg="";

if($_SERVER['REQUEST_METHOD']==='POST'){

$new=$_POST['new_password'] ?? '';
$confirm=$_POST['confirm_password'] ?? '';
$email=$_SESSION['reset_email'];

if(!$new || !$confirm){
$msg="Fill all fields";
}else{

$stmt=$conn->prepare("SELECT password FROM users WHERE email=? LIMIT 1");
$stmt->bind_param("s",$email);
$stmt->execute();
$user=$stmt->get_result()->fetch_assoc();

if(!$user){
$msg="User not found";
}
elseif($new!==$confirm){
$msg="Password not match";
}
elseif(strlen($new)<6){
$msg="Password must be at least 6 characters";
}
elseif(!preg_match('/^[A-Z]/',$new) || !preg_match('/\d/',$new)){
$msg="First letter uppercase + must contain letters & numbers";
}
elseif(password_verify($new,$user['password'])){
$msg="Use different password";
}
else{

$hashed=password_hash($new,PASSWORD_DEFAULT);

$stmt=$conn->prepare("UPDATE users SET password=?, reset_token=NULL, reset_expire=NULL WHERE email=?");
$stmt->bind_param("ss",$hashed,$email);

if(!$stmt->execute()){
$msg="Reset failed";
}else{

unset($_SESSION['reset_email']);
unset($_SESSION['otp_verified']);

header("Location: login.php");
exit;

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
<title>Reset Password</title>

<link rel="stylesheet" href="../css/auth/auth_login.css">
<link rel="stylesheet" href="../css/auth/auth_reset_password.css">
<link rel="stylesheet" href="../mobile_css/auth_mobile_view_reset_password.css" media="(max-width:600px)">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

</head>

<body>

<div class="login-wrapper">
<div class="auth-container">

<a href="#" onclick="history.back(); return false;" class="back-btn">
<img src="../img/auth_img/back.png">
</a>

<h2 class="reset-title">Create New Password</h2>

<p class="reset-text">
Your New Password Must Be Different from<br>
Previously Used Password.
</p>

<form method="POST" id="resetForm">

<input type="password" id="newPass" name="new_password" placeholder="New Password:" required>
<p id="ruleMsg"></p>

<input type="password" id="confirmPass" name="confirm_password" placeholder="Confirm Password:" required>
<p id="passMsg"></p>
<p id="serverMsg" style="color:red;font-size:10px;text-align:left;"></p>

<div class="showpass">
<img src="../img/auth_img/box.png" id="showIcon">
<span>Show Password</span>
</div>

<button type="submit" name="save" id="saveBtn" disabled>
<span class="btn-text">Save</span>
<div class="dots"><span>.</span><span>.</span><span>.</span></div>
</button>

</form>

<?php if($msg): ?>
<script>
document.getElementById("newPass").classList.add("input-error");
document.getElementById("confirmPass").classList.add("input-error");
document.getElementById("serverMsg").innerText="<?php echo $msg; ?>";
document.getElementById("saveBtn").disabled = true;
</script>
<?php endif; ?>

</div>
</div>

<script>
const icon=document.getElementById('showIcon');
const pass1=document.getElementById('newPass');
const pass2=document.getElementById('confirmPass');
const msg=document.getElementById('passMsg');
const ruleMsg=document.getElementById('ruleMsg');
const btn=document.getElementById("saveBtn");
const form=document.getElementById("resetForm");

let visible=false;

icon.addEventListener('click',()=>{
visible=!visible;
icon.src=visible?"../img/auth_img/check.png":"../img/auth_img/box.png";
pass1.type=visible?"text":"password";
pass2.type=visible?"text":"password";
});

function validate(){
const val1 = pass1.value;
const val2 = pass2.value;

const isValidFormat = /^[A-Z]/.test(val1) && /[\d]/.test(val1);
const isMatch = val1 === val2;

btn.disabled = !(val1 && val2 && isValidFormat && isMatch);
}

pass2.addEventListener('input',()=>{
if(pass2.value===""){
msg.innerHTML="";
pass2.classList.remove("input-error","input-success");
validate();
return;
}

if(pass1.value!==pass2.value){
msg.innerHTML="Password does not match";
msg.style.color="red";
pass2.classList.add("input-error");
pass2.classList.remove("input-success");
}else{
msg.innerHTML="Password match";
msg.style.color="green";
pass2.classList.add("input-success");
pass2.classList.remove("input-error");
setTimeout(()=>msg.innerHTML="",2000);
}

validate();
});

pass1.addEventListener('input',()=>{
const val=pass1.value;
if(val===""){
ruleMsg.innerHTML="";
pass1.classList.remove("input-error","input-success");
validate();
return;
}

if(!/^[A-Z]/.test(val)||!/[\d]/.test(val)){
ruleMsg.innerHTML="First letter uppercase + must contain letters & numbers";
ruleMsg.style.color="red";
pass1.classList.add("input-error");
pass1.classList.remove("input-success");
}else{
ruleMsg.innerHTML="Password meets security standards";
ruleMsg.style.color="green";
pass1.classList.add("input-success");
pass1.classList.remove("input-error");
setTimeout(()=>ruleMsg.innerHTML="",2000);
}

validate();
});

pass1.addEventListener('input',()=>document.getElementById("serverMsg").innerHTML="");
pass2.addEventListener('input',()=>document.getElementById("serverMsg").innerHTML="");

form.addEventListener("submit",()=>{
btn.classList.add("loading");
});
</script>

</body>
</html>