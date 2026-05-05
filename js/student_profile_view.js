const personalSection = document.querySelector(".personal-info");
const securitySection = document.querySelector(".security-info");

const personalBtn = document.querySelector(".personal-toggle");
const securityBtn = document.querySelector(".security-toggle");

const passToggle = document.getElementById("togglePass");
const passIcon = document.getElementById("passIcon");
const passwords = document.querySelectorAll('input[type="password"]');

const openQR = document.getElementById("openQR");
const closeQR = document.getElementById("closeQR");
const qrOverlay = document.getElementById("qrOverlay");

const mainContent = document.getElementById("mainContent");
const backBtn = document.getElementById("backBtn");

if(backBtn){
backBtn.addEventListener("click", () => {
    window.location.href = "dashboard.php";
});
}

if(personalBtn){

personalBtn.addEventListener("click", () => {

    personalSection.classList.toggle("hide");
    personalBtn.classList.toggle("rotate");

    localStorage.setItem(
        "personalHidden",
        personalSection.classList.contains("hide")
    );

});

}

if(securityBtn){

securityBtn.addEventListener("click", () => {

    securitySection.classList.toggle("hide");
    securityBtn.classList.toggle("rotate");

    localStorage.setItem(
        "securityHidden",
        securitySection.classList.contains("hide")
    );

});

}

if(passToggle){

passToggle.addEventListener("click", () => {

    const isHidden = passwords[0].type === "password";

    passwords.forEach(p => {
        p.type = isHidden ? "text" : "password";
    });

    passIcon.src = isHidden
        ? "../img/student_img/check.png"
        : "../img/student_img/box.png";

});

}

if(openQR){

openQR.addEventListener("click", () => {

    qrOverlay.style.display = "flex";
    mainContent.classList.add("blur");

});

}

if(closeQR){

closeQR.addEventListener("click", () => {

    qrOverlay.style.display = "none";
    mainContent.classList.remove("blur");

});

}

window.addEventListener("load", () => {

    personalBtn.classList.add("no-transition");
    securityBtn.classList.add("no-transition");

    if(localStorage.getItem("personalHidden") === "true"){
        personalSection.classList.add("hide");
        personalBtn.classList.add("rotate");
    }

    if(localStorage.getItem("securityHidden") === "true"){
        securitySection.classList.add("hide");
        securityBtn.classList.add("rotate");
    }

    setTimeout(()=>{

        personalBtn.classList.remove("no-transition");
        securityBtn.classList.remove("no-transition");

    },50);

});

const fileInput = document.getElementById("fileInput");
const profilePic = document.getElementById("profilePic");
const cameraBtn = document.getElementById("cameraBtn");

if(profilePic) profilePic.onclick = ()=> fileInput.click();
if(cameraBtn) cameraBtn.onclick = ()=> fileInput.click();


let cropper;

const cropModal = document.getElementById("cropModal");
const cropImage = document.getElementById("cropImage");
const cropBtn = document.getElementById("cropBtn");
const profileLoading = document.getElementById("profileLoading");


fileInput?.addEventListener("change", function(){

    const file = this.files[0];

    if(!file) return;

    if(!file.type.startsWith("image/")){
        alert("Invalid image file");
        return;
    }

    const reader = new FileReader();

    reader.onload = ()=>{

        cropModal.style.display = "flex";
        cropImage.src = reader.result;

        if(cropper){
            cropper.destroy();
        }

        cropper = new Cropper(cropImage,{
            aspectRatio:1,
            viewMode:1,
            responsive:true,
            autoCropArea:1,
            dragMode:"move",
            background:false
        });

    };

    reader.readAsDataURL(file);

});

cropBtn?.addEventListener("click", () => {

    if(!cropper) return;

    const canvas = cropper.getCroppedCanvas({
        width: 400,
        height: 400
    });

    canvas.toBlob((blob) => {

        if(!blob){
            alert("Image processing failed");
            return;
        }

        const fd = new FormData();
        fd.append("profile", blob, "profile.png");

        fetch("upload_profile.php", {
            method: "POST",
            body: fd
        })
        .then(res => res.text())
        .then(data => {

            data = data.trim();

            if(data !== "success"){
                alert("Upload failed: " + data);
                return;
            }

            cropModal.style.display = "none";
            profileLoading.style.display = "flex";

            const newImageURL = URL.createObjectURL(blob);

            setTimeout(() => {
                profilePic.src = newImageURL;
                profileLoading.style.display = "none";
            }, 1200);

        })
        .catch(err => {
            console.error(err);
            alert("Network error");
        });

    }, "image/png");

});

document.querySelectorAll(".edit-icon").forEach(icon=>{

icon.addEventListener("click",()=>{

    const input = icon.parentElement.querySelector("input");

    if(!input) return;

    if(!input.hasAttribute("readonly")){

        const field = input.dataset.field;
        const value = input.value;

        const fd = new FormData();

        fd.append("field",field);
        fd.append("value",value);

        fetch("update_profile.php",{
            method:"POST",
            body:fd
        })

.then(()=>{

    input.setAttribute("readonly",true);
    icon.src="../img/student_img/update.png";

const toast = document.getElementById("toastSave");

if(toast){

    if(field === "email"){
        toast.textContent = "Email updated successfully";
    } else {
        toast.textContent = "Saved successfully!";
    }

    toast.classList.add("show");

    setTimeout(()=>{
        toast.classList.remove("show");
    },3000);
}

});

    }

    else{

        input.removeAttribute("readonly");

        if(input.dataset.field === "email"){
            input.type="text";
        }

        input.focus();

        setTimeout(()=>{

            const len=input.value.length;
            input.setSelectionRange(len,len);

        },1);

        icon.src="../img/student_img/save.png";

    }

});

});

const urlParams = new URLSearchParams(window.location.search);

if(urlParams.get("success") === "1"){

const toast = document.getElementById("toastSuccess");

if(toast){

toast.classList.add("show");

setTimeout(()=>{
toast.classList.remove("show");
},3000);

const cleanURL = window.location.pathname;
window.history.replaceState({}, document.title, cleanURL);

}

}