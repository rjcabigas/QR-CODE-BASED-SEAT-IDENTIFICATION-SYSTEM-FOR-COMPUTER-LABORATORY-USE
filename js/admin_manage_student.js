document.addEventListener("DOMContentLoaded", () => {

const form = document.querySelector("#studentModal .modal-form");
const saveBtn = form ? form.querySelector(".save-btn") : null;

const fileInput = form ? form.querySelector('input[name="excel_file"]') : null;

const nameInput = form ? form.querySelector('[name="student_name"]') : null;
const idInput = form ? form.querySelector('[name="student_id"]') : null;
const courseInput = form ? form.querySelector('[name="course"]') : null;
const sectionInput = form ? form.querySelector('[name="section"]') : null;
const emailInput = form ? form.querySelector('[name="email"]') : null;

const yearSelect = form ? form.querySelector('[name="year"]') : null;
const genderSelect = form ? form.querySelector('[name="gender"]') : null;
const semesterSelect = form ? form.querySelector('[name="semester"]') : null;

const manualInputs = [
nameInput,idInput,emailInput,courseInput,sectionInput
];

let touched = { name:false, id:false };

function showError(input,message){
if(!input) return;
const error = input.parentElement.querySelector(".error-msg");
if(error){
error.textContent = message;
error.style.display="block";
input.classList.add("input-error");
}
}

function clearError(input){
if(!input) return;
const error = input.parentElement.querySelector(".error-msg");
if(error){
error.textContent = "";
error.style.display="none";
input.classList.remove("input-error");
}
}

function toggleRequired(state){
manualInputs.forEach(input=>{
if(!input) return;
if(state){
input.setAttribute("required","required");
}else{
input.removeAttribute("required");
}
});
}

[nameInput,courseInput].forEach(input=>{
if(input){
input.addEventListener("input",()=>{
input.value = input.value.toUpperCase();
});
}
});

if(sectionInput){
sectionInput.addEventListener("input",()=>{
sectionInput.value = sectionInput.value
.replace(/[^A-Za-z]/g,'')
.slice(0,1)
.toUpperCase();
});
}

if(emailInput){
emailInput.addEventListener("input",()=>{
emailInput.value = emailInput.value.toLowerCase();
});
}

if(idInput){
if(!idInput.value) idInput.value="MA";

idInput.addEventListener("input",()=>{
if(!idInput.value.startsWith("MA")) idInput.value="MA";
let numbers = idInput.value.slice(2).replace(/[^0-9]/g,'');
idInput.value="MA"+numbers;
});

idInput.addEventListener("keydown",(e)=>{
if(idInput.selectionStart<=2 && (e.key==="Backspace"||e.key==="Delete")){
e.preventDefault();
}
});
}

function validateAll(){

if(!saveBtn) return;

if(fileInput && fileInput.files.length > 0){
saveBtn.disabled = false;
return;
}

let valid=true;

if(nameInput && nameInput.value.trim().length<7){
if(touched.name){
showError(nameInput,"Please enter full name (minimum 7 letters).");
}
valid=false;
}else clearError(nameInput);

if(idInput && !/^MA\d+$/.test(idInput.value)){
if(touched.id){
showError(idInput,"Student ID must contain numbers only after MA.");
}
valid=false;
}else clearError(idInput);

if(courseInput && !courseInput.value.trim()) valid=false;
if(sectionInput && !sectionInput.value.trim()) valid=false;
if(yearSelect && !yearSelect.value) valid=false;
if(genderSelect && !genderSelect.value) valid=false;
if(semesterSelect && !semesterSelect.value) valid=false;
if(emailInput && !emailInput.value.trim()) valid=false;

saveBtn.disabled=!valid;
}

if(fileInput){
fileInput.addEventListener("change",()=>{
if(fileInput.files.length > 0){
toggleRequired(false);
saveBtn.disabled=false;
}else{
toggleRequired(true);
validateAll();
}
});
}

if(nameInput){
nameInput.addEventListener("blur",()=>{
touched.name=true;
validateAll();
});

nameInput.addEventListener("input",()=>{
clearError(nameInput);
validateAll();
});
}

if(idInput){
idInput.addEventListener("input",()=>{
touched.id=true;
validateAll();
});
}

if(courseInput) courseInput.addEventListener("input",validateAll);
if(sectionInput) sectionInput.addEventListener("input",validateAll);
if(yearSelect) yearSelect.addEventListener("change",validateAll);
if(genderSelect) genderSelect.addEventListener("change",validateAll);
if(semesterSelect) semesterSelect.addEventListener("change",validateAll);

if(emailInput){
emailInput.addEventListener("blur",()=>{

if(!emailInput.value.trim()) return;

fetch("check_student.php",{
method:"POST",
headers:{"Content-Type":"application/x-www-form-urlencoded"},
body:"email="+encodeURIComponent(emailInput.value)
})
.then(res=>res.text())
.then(data=>{

if(data==="exist"){
showError(emailInput,"Email already exists.");
saveBtn.disabled=true;
}else{
clearError(emailInput);
validateAll();
}

});

});
}

if(saveBtn) saveBtn.disabled=true;

const selectAll = document.getElementById("selectAll");
const checkboxes = document.querySelectorAll(".student-checkbox");

if(selectAll){
selectAll.addEventListener("change",function(){

checkboxes.forEach(cb=>{
cb.checked=this.checked;
});

});
}

const uploadInput = document.querySelector('input[type="file"]');
const fileText = document.querySelector(".file-text");

if(uploadInput && fileText){

uploadInput.addEventListener("change",function(){

const fileName = this.files.length ? this.files[0].name : "No chosen file..";

fileText.textContent = fileName;

});

}

if(form && saveBtn){

form.addEventListener("submit",function(){

if(document.activeElement === saveBtn){

const rect = saveBtn.getBoundingClientRect();

saveBtn.style.setProperty('--btn-w',rect.width+'px');
saveBtn.style.setProperty('--btn-h',rect.height+'px');

saveBtn.classList.add("loading");

saveBtn.style.pointerEvents="none";
saveBtn.style.opacity="0.7";

}

});

}

const msg = document.getElementById("updateMsg");

if(msg){

setTimeout(()=>{

msg.style.opacity="0";

setTimeout(()=>msg.remove(),300);

},2000);

if(window.history.replaceState){

const url = window.location.protocol+"//"+
window.location.host+
window.location.pathname;

window.history.replaceState({path:url},'',url);

}

}

// ✅ TOAST CLEAN URL (ADD MO ITO)
const toast = document.getElementById("toastArchive");

if(toast){

    toast.classList.add("show");

    setTimeout(()=>{
        toast.classList.remove("show");
    },3000);

    // 👉 ito yung fix para mawala pag refresh
    if(window.history.replaceState){
        const cleanURL = window.location.pathname;
        window.history.replaceState({}, document.title, cleanURL);
    }

}

});

const deleteBtn = document.getElementById("deleteSelected");
const checkboxes = document.querySelectorAll(".student-checkbox");
const selectAll = document.getElementById("selectAll");

function toggleDeleteButton(){
let checked = document.querySelectorAll(".student-checkbox:checked").length;

if(checked > 0){
deleteBtn.classList.remove("disabled");
}else{
deleteBtn.classList.add("disabled");
}
}

checkboxes.forEach(cb=>{
cb.addEventListener("change",toggleDeleteButton);
});

selectAll.addEventListener("change",function(){
checkboxes.forEach(cb=>{
cb.checked = selectAll.checked;
});
toggleDeleteButton();
});

toggleDeleteButton();

deleteBtn.addEventListener("click",function(){

let checked = document.querySelectorAll(".student-checkbox:checked");

if(checked.length === 0){
return;
}

if(confirm("Move selected students to archive?")){
document.querySelector("form").submit();
}

});

const downloadBtn = document.getElementById("downloadFormat");
const tooltipText = document.querySelector(".tooltip-text");

if(downloadBtn && tooltipText){

    const originalText = tooltipText.textContent;

    downloadBtn.addEventListener("click", function(){

        // change text
        tooltipText.textContent = "Downloading...";

        // show agad (kahit di naka-hover)
        tooltipText.style.visibility = "visible";
        tooltipText.style.opacity = "1";

        // disable button
        downloadBtn.classList.add("loading");

        // trigger download
        window.location.href = "?download_format=1";

        // balik sa original after 2s
        setTimeout(()=>{
            tooltipText.textContent = originalText;
            tooltipText.style.visibility = "hidden";
            tooltipText.style.opacity = "0";
            downloadBtn.classList.remove("loading");
        }, 2000);

    });

}