const otpBoxes=document.querySelectorAll("#otpBoxes input");
const otpStatus=document.getElementById("otpStatus");
const otpContainer=document.getElementById("otpBoxes");

function getOTP(){
let otp="";
otpBoxes.forEach(box=>{otp+=box.value});
return otp;
}

otpBoxes.forEach((input,index)=>{

input.addEventListener("input",function(){

if(this.value.length===1&&index<otpBoxes.length-1){
otpBoxes[index+1].focus();
}

let otp=getOTP();

if(otp.length===4){

otpStatus.innerHTML="Verifying OTP<span class='loading-dots'></span>";
otpStatus.className="loading";

setTimeout(function(){

fetch("verify_otp.php",{
method:"POST",
headers:{"Content-Type":"application/x-www-form-urlencoded"},
body:"otp="+otp
})
.then(res=>res.text())
.then(data=>{

if(data.trim()==="valid"){

otpVerified=true;

otpContainer.classList.remove("otp-error");
otpContainer.classList.add("otp-success");

otpBoxes.forEach(box=>{
box.disabled=true;
});

otpStatus.innerHTML="OTP Verified Successfully";
otpStatus.className="otp-success-msg";

setTimeout(function(){

otpStatus.innerHTML="";
newPass.disabled=false;
confirmPass.disabled=false;
newPass.focus();
checkPassword();

},2000);

}else{

otpContainer.classList.remove("otp-success");
otpContainer.classList.add("otp-error");

otpStatus.innerHTML="Invalid OTP";
otpStatus.className="otp-error-msg";

otpBoxes.forEach(box=>box.value="");
otpBoxes[0].focus();

}

});

},2000);

}

});

});

let otpVerified=false;

const updateBtn=document.querySelector(".update-btn");
const matchMsg=document.getElementById("newPassError");
const confirmMsg=document.getElementById("confirmError");

const newPassField=newPass.closest(".password-field");
const confirmPassField=confirmPass.closest(".password-field");

newPass.addEventListener("input",checkPassword);
confirmPass.addEventListener("input",checkPassword);

function hasMix(v){
return /[\d@#$%^&]/.test(v);
}

function startsUpper(v){
return /^[A-Z]/.test(v);
}

function checkPassword(){

if(!otpVerified){
updateBtn.disabled=true;
return;
}

let val=newPass.value;
let len=val.length;

newPassField.classList.remove("weak","medium","strong");
confirmPassField.classList.remove("match","nomatch");

matchMsg.classList.remove("weak","medium","strong");
confirmMsg.classList.remove("match","nomatch");

if(len===0){
updateBtn.disabled=true;
return;
}

if(!startsUpper(val)){

matchMsg.innerHTML="Use uppercase first";
matchMsg.classList.add("weak");
newPassField.classList.add("weak");
updateBtn.disabled=true;

}

else if(len<5){

matchMsg.innerHTML="Weak Password";
matchMsg.classList.add("weak");
newPassField.classList.add("weak");
updateBtn.disabled=true;

}

else if(len>=5&&len<8){

matchMsg.innerHTML="Medium Strength Password";
matchMsg.classList.add("medium");
newPassField.classList.add("medium");
updateBtn.disabled=true;

}

else if(len>=8&&!hasMix(val)){

matchMsg.innerHTML="Add numbers or symbols (@#$%^&)";
matchMsg.classList.add("medium");
newPassField.classList.add("medium");
updateBtn.disabled=true;

}

else{

matchMsg.innerHTML="Verified Strong Password ✓";
matchMsg.classList.add("strong");
newPassField.classList.add("strong");

if(confirmPass.value===val&&confirmPass.value!==""){

confirmMsg.innerHTML="Password match ✓";
confirmMsg.classList.add("match");
confirmPassField.classList.add("match");

updateBtn.disabled=false;

}else{

updateBtn.disabled=true;

}

}

if(confirmPass.value!==""&&confirmPass.value!==val){

confirmMsg.innerHTML="Password does not match";
confirmMsg.classList.add("nomatch");
confirmPassField.classList.add("nomatch");

updateBtn.disabled=true;

}

}

updateBtn.addEventListener("click",function(){

const password=newPass.value;

updateBtn.innerHTML="Updating<span class='loading-dots'></span>";
updateBtn.disabled=true;

fetch("update_password.php",{
method:"POST",
headers:{"Content-Type":"application/x-www-form-urlencoded"},
body:"password="+encodeURIComponent(password)+"&otp="+encodeURIComponent(getOTP())
})
.then(res=>res.text())
.then(data=>{

console.log("Server response:",data);

forcePasswordChange = false;

setTimeout(function(){

window.location.href="../auth/logout.php";

},1000);

});

});