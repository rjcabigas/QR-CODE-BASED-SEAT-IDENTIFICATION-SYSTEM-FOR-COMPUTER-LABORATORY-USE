document.addEventListener("DOMContentLoaded", () => {

const newPass = document.getElementById("newPassword");
const confirmPass = document.getElementById("confirmPassword");

const newCard = document.getElementById("newPassCard");
const confirmCard = document.getElementById("confirmPassCard");

const passMsg = document.getElementById("passwordMsg");
const confirmMsg = document.getElementById("confirmMsg");

const updateBtn = document.getElementById("updatePassBtn");
const btnText = document.getElementById("btnText");

const passForm = document.querySelector('form[method="POST"]');

if(!newPass || !confirmPass) return;

function hasMix(v){
    return /[\d%*&#]/.test(v);
}

function startsUpper(v){
    return /^[A-Z]/.test(v);
}

function resetCard(card,msg){
    card.className = "info-card";
    msg.className = "pass-msg";
}

function checkEnable(){

    if(
        newCard.classList.contains("strong") &&
        confirmCard.classList.contains("match")
    ){
        updateBtn.disabled = false;
    }else{
        updateBtn.disabled = true;
    }

}

newPass.addEventListener("input", ()=>{

let val = newPass.value;
let v = val.length;

resetCard(newCard,passMsg);

if(v === 0){

    passMsg.textContent = "";
    confirmPass.dispatchEvent(new Event("input"));
    checkEnable();
    return;
}

if(!startsUpper(val)){

    passMsg.textContent = "Use uppercase first";

    newCard.classList.add("weak");
    passMsg.classList.add("weak");

}

else if(v < 5){

    passMsg.textContent = "Weak Password";

    newCard.classList.add("weak");
    passMsg.classList.add("weak");

}

else if(v >= 5 && v < 8){

    passMsg.textContent = "Medium Strength Password";

    newCard.classList.add("medium");
    passMsg.classList.add("medium");

}

else if(v >= 8 && !hasMix(val)){

    passMsg.textContent = "Add numbers or symbols (@$^%*&#)";

    newCard.classList.add("medium");
    passMsg.classList.add("medium");

}

else if(v >= 8 && hasMix(val)){

    passMsg.innerHTML =
    `Verified Strong Password <img src="../img/student_img/verified.png">`;

    newCard.classList.add("strong");
    passMsg.classList.add("strong");

}

confirmPass.dispatchEvent(new Event("input"));
checkEnable();

});

confirmPass.addEventListener("input", ()=>{

resetCard(confirmCard,confirmMsg);

if(confirmPass.value === ""){

    confirmMsg.textContent = "";
    checkEnable();
    return;

}

if(confirmPass.value === newPass.value){

    confirmMsg.innerHTML =
    `Password Match <img src="../img/student_img/verified.png">`;

    confirmCard.classList.add("match");
    confirmMsg.classList.add("match");

}

else{

    confirmMsg.textContent = "Password Does Not Match!";

    confirmCard.classList.add("nomatch");
    confirmMsg.classList.add("nomatch");

}

checkEnable();

});

if(passForm){

passForm.addEventListener("submit",(e)=>{

    e.preventDefault();

    updateBtn.disabled = true;

    let dots = 0;

    const interval = setInterval(()=>{

        dots++;

        if(dots > 3) dots = 1;

        btnText.textContent = "Updating" + ".".repeat(dots);

    },600);

    setTimeout(()=>{
        passForm.submit();
    },3000);

});

}

});