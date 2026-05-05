<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Account</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/auth/auth_signup.css">
<link rel="stylesheet" href="../mobile_css/auth_mobile_view_signup.css" media="(max-width:600px)">
</head>
<body>

<div class="signup-wrapper">
<div class="signup-box">

<h2 class="desktop-title">Create Account</h2>

<form id="signupForm">

<div class="input-group">
<input type="text" name="fullname" id="fullname" placeholder="Full name:" required>
<div id="nameMsg" class="field-msg"></div>
</div>

<div class="input-group">
<input type="email" name="email" id="email" placeholder="Email Address:" required>
<div id="emailMsg" class="field-msg"></div>
</div>

<div class="input-group">
<input type="password" id="password" name="password" placeholder="Create Password:" required>
<div id="passMsg" class="field-msg"></div>
</div>

<div class="input-group">
<input type="password" id="confirm" name="confirm" placeholder="Confirm Password:" required>
<div id="confirmMsg" class="field-msg"></div>
</div>

<div class="showpass">
<img src="../img/auth_img/box.png" id="boxIcon">
<span>Show password</span>
</div>

<div class="input-group">
<div class="subject-dropdown" id="roleDropdown">
    <span id="roleText">Select Role</span>
    <img src="../img/student_img/drop_down.png" id="roleIcon">

    <div class="dropdown-list" id="roleList" style="display:none;">
<div class="dropdown-item small" data-value="teacher">Teacher</div>
    </div>
</div>

<input type="hidden" name="role" id="role">
</div>

<button type="submit" id="signupBtn" disabled>
<span class="btn-text">Sign up</span>
<div class="dots"><span>.</span><span>.</span><span>.</span></div>
</button>

</form>

<p class="login">
Already have an Account?
<a href="../auth/login.php">Login here</a>
</p>

</div>
</div>

<script>
const form=document.getElementById("signupForm");
const fullname=document.getElementById("fullname");
const email=document.getElementById("email");
const pass=document.getElementById("password");
const confirm=document.getElementById("confirm");
const role=document.getElementById("role");
const btn=document.getElementById("signupBtn");

const nameMsg=document.getElementById("nameMsg");
const emailMsg=document.getElementById("emailMsg");
const passMsg=document.getElementById("passMsg");
const confirmMsg=document.getElementById("confirmMsg");
const icon=document.getElementById("boxIcon");

let checked=false;
let matchTimer=null;

icon.addEventListener("click",()=>{
checked=!checked;
icon.src=checked?"../img/auth_img/check.png":"../img/auth_img/box.png";
pass.type=checked?"text":"password";
confirm.type=checked?"text":"password";
});

fullname.addEventListener("input",()=>fullname.value=fullname.value.toUpperCase());
email.addEventListener("input",()=>email.value=email.value.toLowerCase());

function validate(){

let valid=true;

nameMsg.innerText="";
emailMsg.innerText="";
passMsg.innerText="";
confirmMsg.innerText="";

if(fullname.value!==""&&fullname.value.length<=9){
nameMsg.innerText="Please type your full name.";
valid=false;
}

const strongPass=/^(?=[A-Z])(?=.*\d)(?=.*[_#@!]).{6,}$/;

if(pass.value!==""){
if(!strongPass.test(pass.value)){
passMsg.innerText="Create strong password: Start with CAPITAL letter, include number and special (_ # @ !).";
valid=false;
}
}

if(confirm.value!==""){
if(pass.value!==confirm.value){
confirm.classList.add("input-error");
confirm.classList.remove("input-success");
confirmMsg.style.color="red";
confirmMsg.innerText="Passwords do not match.";
valid=false;
}else{
confirm.classList.remove("input-error");
confirm.classList.add("input-success");
confirmMsg.style.color="green";
confirmMsg.innerText="Password match";

clearTimeout(matchTimer);
matchTimer=setTimeout(()=>{
confirmMsg.innerText="";
},2000);
}
}

if(fullname.value===""||email.value===""||pass.value===""||confirm.value===""||role.value===""){
valid=false;
}

btn.disabled=!valid;
}

[fullname,email,pass,confirm,role].forEach(el=>{
el.addEventListener("input",validate);
el.addEventListener("change",validate);
});

form.addEventListener("submit",e=>{
e.preventDefault();

btn.classList.add("loading");
btn.disabled=true;

fetch("signup_process.php",{method:"POST",body:new FormData(form)})
.then(r=>r.json())
.then(d=>{
if(d.status==="error"){
btn.classList.remove("loading");
validate();
if(d.field==="email"){
emailMsg.innerText=d.message;
email.classList.add("input-error");
}
}else{
setTimeout(()=>{
window.location.href = d.redirect;
},1200);
}
});
});

const roleDropdown = document.getElementById("roleDropdown");
const roleList = document.getElementById("roleList");
const roleText = document.getElementById("roleText");
const roleInput = document.getElementById("role");

roleDropdown.addEventListener("click", ()=>{
    roleDropdown.classList.toggle("active");
    roleList.style.display = roleList.style.display === "block" ? "none" : "block";
});

document.querySelectorAll("#roleList .dropdown-item").forEach(item=>{
    item.addEventListener("click", (e)=>{
        e.stopPropagation();
        roleText.innerText = item.innerText;
        roleText.style.color="#000";
        roleInput.value = item.dataset.value;
        roleList.style.display = "none";
        roleDropdown.classList.remove("active");
        validate();
    });
});

document.addEventListener("click",(e)=>{
    if(!roleDropdown.contains(e.target)){
        roleList.style.display="none";
        roleDropdown.classList.remove("active");
    }
});
</script>

</body>
</html>