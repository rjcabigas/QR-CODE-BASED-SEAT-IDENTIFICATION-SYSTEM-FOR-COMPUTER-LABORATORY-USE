<?php
session_start();
require_once "../include/db.php";

$emailErr="";
$passErr="";

if($_SERVER['REQUEST_METHOD']==='POST'){

$login=strtolower(trim($_POST['email'] ?? ''));
$password=$_POST['password'] ?? '';

if(!$login){
$emailErr="Please enter email or student ID.";
}

if(!$password){
$passErr="Please enter your password.";
}

if(!$emailErr && !$passErr){

$stmt=$conn->prepare("
SELECT * FROM users
WHERE role='student'
AND (
email=?
OR email=(SELECT email FROM students WHERE student_id=? LIMIT 1)
)
LIMIT 1
");

$stmt->bind_param("ss",$login,$login);
$stmt->execute();
$result=$stmt->get_result();

if(!$result->num_rows){
$emailErr="Student account not found.";
}else{

$user=$result->fetch_assoc();

if(!password_verify($password,$user['password'])){
$passErr="Invalid password.";
}else{

session_regenerate_id(true);

$_SESSION['user_id']=$user['id'];
$_SESSION['fullname']=$user['fullname'];
$_SESSION['role']=$user['role'];
$_SESSION['email']=$user['email'];

$stmt2=$conn->prepare("SELECT student_id FROM students WHERE email=? LIMIT 1");
$stmt2->bind_param("s",$user['email']);
$stmt2->execute();
$res2=$stmt2->get_result();

if($res2->num_rows){
$stud=$res2->fetch_assoc();
$_SESSION['student_id']=$stud['student_id'];
}

header("Location: ../student/dashboard.php");
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
<title>Student Login</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/auth/auth_login.css">
<link rel="stylesheet" href="../mobile_css/auth_mobile_view_login.css" media="(max-width:600px)">
</head>

<body>

<div class="login-wrapper">
<div class="login-box">

<h2 class="desktop-title">Welcome, Student</h2>

<form method="POST" id="loginForm">

<div class="input-group">
<input type="text" name="email" id="email" placeholder="Email or Student ID"
value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
<div class="field-msg" id="emailMsg"><?php echo $emailErr; ?></div>
</div>

<div class="input-group">
<input type="password" id="password" name="password" placeholder="Password">
<div class="field-msg" id="passMsg"><?php echo $passErr; ?></div>
</div>

<div class="show-row">
<div class="showpass">
<img src="../img/auth_img/box.png" id="boxIcon">
<span>Show password</span>
</div>

<a href="../auth/forgot_password.php?from=student" class="forgot">Forgot Password?</a>
</div>

<button type="submit" id="loginBtn" disabled>
<span class="btn-text">Log in</span>
<div class="dots"><span>.</span><span>.</span><span>.</span></div>
</button>

</form>

</div>
</div>

<script>
const email=document.getElementById("email");
const pass=document.getElementById("password");
const icon=document.getElementById("boxIcon");
const btn=document.getElementById("loginBtn");
const form=document.getElementById("loginForm");
const emailMsg=document.getElementById("emailMsg");
const passMsg=document.getElementById("passMsg");

let checked=false;

icon.addEventListener("click",()=>{
checked=!checked;
icon.src=checked?"../img/auth_img/check.png":"../img/auth_img/box.png";
pass.type=checked?"text":"password";
});

email.addEventListener("input",()=>{
email.value=email.value.toLowerCase();
validate();
});

pass.addEventListener("input",()=>validate());

function validate(){
btn.disabled = email.value==="" || pass.value==="";
}

form.addEventListener("submit",function(e){
e.preventDefault();
btn.classList.add("loading");
btn.disabled=true;
setTimeout(()=>{ this.submit(); },800);
});
</script>

</body>
</html>