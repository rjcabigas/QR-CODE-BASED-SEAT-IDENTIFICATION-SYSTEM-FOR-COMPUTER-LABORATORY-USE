let forcePasswordChange = true;

function toggleMenu(){
const menu=document.getElementById("profileMenu");
const arrow=document.querySelector(".dropdown");

if(menu.style.display==="flex"){
menu.style.display="none";
arrow.classList.remove("rotate");
}else{
menu.style.display="flex";
arrow.classList.add("rotate");
}
}

window.onclick=function(event){

const profile = event.target.closest('.profile');
const menu=document.getElementById("profileMenu");
const arrow=document.querySelector(".dropdown");

if(!profile){
menu.style.display="none";
arrow.classList.remove("rotate");
}

}

function openPasswordModal(){
document.getElementById("passwordModal").style.display="flex";
}

window.addEventListener("click",function(e){

if(forcePasswordChange) return;

const modal=document.getElementById("passwordModal");

if(e.target===modal){
modal.style.display="none";
}

});

const otpInputs=document.querySelectorAll("#otpBoxes input");

otpInputs.forEach((input,index)=>{

input.addEventListener("input",function(){
if(this.value.length===1&&index<otpInputs.length-1){
otpInputs[index+1].focus();
}
});

input.addEventListener("keydown",function(e){
if(e.key==="Backspace"&&this.value===""&&index>0){
otpInputs[index-1].focus();
}
});

});

const toggleIcon=document.getElementById("passToggleIcon");
const newPass=document.getElementById("newPass");
const confirmPass=document.getElementById("confirmPass");

let visible=false;

toggleIcon.addEventListener("click",function(){

visible=!visible;

if(visible){
toggleIcon.src="../img/mis_img/check.png";
newPass.type="text";
confirmPass.type="text";
}else{
toggleIcon.src="../img/mis_img/box.png";
newPass.type="password";
confirmPass.type="password";
}

});

const emailIcon=document.getElementById("emailIcon");
const emailInput=document.getElementById("emailInput");
const emailStatus=document.getElementById("emailStatus");

let editingEmail=false;

emailIcon.addEventListener("click",function(){

editingEmail=!editingEmail;

if(editingEmail){

emailIcon.src="../img/mis_img/save.png";
emailInput.disabled=false;
emailInput.focus();
emailInput.select();

emailStatus.className="";
emailStatus.innerHTML="";

}else{

const newEmail=emailInput.value;

emailStatus.className="loading show";
emailStatus.innerHTML="Updating your email<span class='loading-dots'></span>";

fetch("update_admin_email.php",{
method:"POST",
headers:{"Content-Type":"application/x-www-form-urlencoded"},
body:"email="+encodeURIComponent(newEmail)
})
.then(res=>res.text())
.then(data=>{

if(data.trim()==="success"){

emailStatus.className="success show";
emailStatus.innerHTML="Email updated. OTP sent to your email.";

emailInput.dataset.original=newEmail;
emailIcon.src="../img/mis_img/update_email.png";
emailInput.disabled=true;

otpInputs.forEach(input=>{input.disabled=false});
otpInputs[0].focus();

}else{

emailStatus.className="error show";
emailStatus.innerHTML="Failed to update email.";

}

setTimeout(function(){
emailStatus.className="";
emailStatus.innerHTML="";
},3000);

});

}

});

const resendOTP=document.getElementById("resendOTP");
let cooldown=false;

resendOTP.addEventListener("click",function(){

if(cooldown) return;

let email=emailInput.value;

resendOTP.innerHTML="Sending<span class='loading-dots'></span>";

fetch("send_otp.php",{
method:"POST",
headers:{"Content-Type":"application/x-www-form-urlencoded"},
body:"email="+encodeURIComponent(email)
})
.then(res=>res.text())
.then(data=>{

if(data.trim()==="sent"){

startCooldown();

}else{

resendOTP.innerHTML="Failed";

setTimeout(()=>{
resendOTP.innerHTML="Resend OTP";
},3000);

}

});

});

function startCooldown(){

cooldown=true;
let time=60;

resendOTP.style.pointerEvents="none";

let timer=setInterval(function(){

resendOTP.innerHTML="Resend OTP ("+time+"s)";
time--;

if(time<0){
clearInterval(timer);
resendOTP.innerHTML="Resend OTP";
resendOTP.style.pointerEvents="auto";
cooldown=false;
}

},1000);

}

const toggle = document.getElementById("feedbackToggle");
const modal = document.getElementById("dateModal");
const calendarDates = document.getElementById("calendarDates");
const monthYear = document.getElementById("monthYear");
const confirmBtn = document.getElementById("confirmDate");

confirmBtn.disabled = true;

let currentDate = new Date();
let selectedDate = null;

function showToast(message){
    const toast = document.getElementById("toast");
    toast.innerText = message;
    toast.classList.add("show");

    setTimeout(()=>{
        toast.classList.remove("show");
    }, 2500);
}

document.getElementById("feedbackToggle").addEventListener("change", function(){

    if(this.checked){
        modal.style.display = "flex";
        renderCalendar(currentDate);
    } else {

        // 🔥 kung naka ON tapos pinindot → ibalik sa ON
        this.checked = true;

        modal.style.display = "flex";
        renderCalendar(currentDate);

    }

});

function renderCalendar(date){
    calendarDates.innerHTML = "";
    confirmBtn.disabled = true;

    let year = date.getFullYear();
    let month = date.getMonth();

    let firstDay = new Date(year, month, 1).getDay();
    let daysInMonth = new Date(year, month+1, 0).getDate();

    monthYear.innerText = date.toLocaleString("default", {month:"long"}) + " " + year;

for(let i=0;i<firstDay;i++){
    let empty = document.createElement("div");
    empty.classList.add("empty"); // optional
    calendarDates.appendChild(empty);
}

    for(let d=1; d<=daysInMonth; d++){
        let day = document.createElement("div");
        day.innerText = d;

        day.onclick = function(){
            document.querySelectorAll(".calendar-dates div").forEach(el=>el.classList.remove("active"));
            day.classList.add("active");
            selectedDate = `${year}-${String(month+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
            confirmBtn.disabled = false;
        };

        let fullDate = `${year}-${String(month+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;

if(savedDateFromDB === fullDate){
    day.classList.add("active");
    selectedDate = fullDate;
    confirmBtn.disabled = false;
}

        calendarDates.appendChild(day);
    }
}

document.getElementById("prevMonth").onclick = ()=>{
    currentDate.setMonth(currentDate.getMonth()-1);
    renderCalendar(currentDate);
};

document.getElementById("nextMonth").onclick = ()=>{
    currentDate.setMonth(currentDate.getMonth()+1);
    renderCalendar(currentDate);
};

confirmBtn.addEventListener("click", function(){

    if(selectedDate){

        fetch("../mis/dashboard.php", {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded"},
            body: "save_date=" + selectedDate
        })
        .then(res => res.text())
        .then(() => {

            return fetch("toggle_feedback.php", {
                method: "POST",
                headers: {"Content-Type": "application/x-www-form-urlencoded"},
                body: "status=1"
            });

        })
.then(() => {

    toggle.checked = true;

    modal.style.display = "none"; // 🔥 close agad

    showToast("Date set successfully");

    setTimeout(()=>{
        location.reload();
    }, 2000);
});

    }

});

toggle.checked = (feedbackEnabled == 1);

if(savedDateFromDB){
    renderCalendar(currentDate);
}

window.addEventListener("click", function(e){

    const modal = document.getElementById("dateModal");
    const box = document.querySelector(".date-box");

    if(modal.style.display === "flex"){
        if(!box.contains(e.target)){
            modal.style.display = "none";
            const toggle = document.getElementById("feedbackToggle");
if(toggle && !savedDateFromDB && feedbackEnabled != 1){
    toggle.checked = false;
}
        }
    }

});

