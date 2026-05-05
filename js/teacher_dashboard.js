document.addEventListener("DOMContentLoaded", () => {

const $ = s => document.querySelector(s);
const $$ = s => document.querySelectorAll(s);

const nextBtn = $(".next-btn");
const subjectPanel = $(".subject-panel");
const sectionPanel = $("#sectionPanel");
const backBtn = $("#backBtn");
const myClassBtn = $("#myClassBtn");
const classModal = $("#classModal");
const dotBtn = $("#dotBtn");
const dotPanel = $("#dotPanel");
const typedName = $("#typedName");
const timeOverlay = $("#timeOverlay");

if(typeof SETUP_REQUIRED !== "undefined" && SETUP_REQUIRED){
    classModal.classList.add("active");

    classModal.addEventListener("click", e=>{
        if(e.target === classModal) e.stopPropagation();
    });
}

if(classModal && !SETUP_REQUIRED){
    classModal.addEventListener("click", e=>{
        if(e.target === classModal){
            classModal.classList.remove("active");
        }
    });
}

nextBtn?.addEventListener("click", ()=>{
    subjectPanel.style.display="none";
    sectionPanel.classList.add("active");
});

backBtn?.addEventListener("click", ()=>{
    sectionPanel.classList.remove("active");
    subjectPanel.style.display="flex";
});

myClassBtn?.addEventListener("click", ()=>{
    classModal.classList.add("active");
    dotPanel.classList.remove("active");
});

dotBtn?.addEventListener("click", e=>{
    e.stopPropagation();
    dotPanel.classList.toggle("active");
});

document.addEventListener("click", e=>{
    if(dotBtn && dotPanel &&
       !dotBtn.contains(e.target) &&
       !dotPanel.contains(e.target)){
        dotPanel.classList.remove("active");
    }
});

if(typedName && typeof username !== "undefined"){

let i=0, del=false;

(function loop(){

if(!del){
typedName.textContent=username.substring(0,++i);
if(i===username.length) setTimeout(()=>del=true,1200);
}else{
typedName.textContent=username.substring(0,--i);
if(i===0) del=false;
}

setTimeout(loop,del?60:90);

})();

}

$$(".subject-pill").forEach(pill=>{

if(typeof SESSION_ACTIVE_SUBJECT !== "undefined" &&
pill.dataset.value === SESSION_ACTIVE_SUBJECT){
pill.classList.add("active");
}

pill.addEventListener("click",()=>{

$$(".subject-pill").forEach(p=>p.classList.remove("active"));

pill.classList.add("active");

fetch("set_active_subject.php",{
method:"POST",
headers:{'Content-Type':'application/x-www-form-urlencoded'},
body:"subject="+encodeURIComponent(pill.dataset.value)
});

});

});

function setActiveSection(value){

fetch("set_active_section.php",{
method:"POST",
headers:{'Content-Type':'application/x-www-form-urlencoded'},
body:"section="+encodeURIComponent(value)
});

}


let tempActive=null;

$$(".section-pill").forEach(pill=>{

pill.addEventListener("click",()=>{

$$(".section-pill").forEach(p=>p.classList.remove("active"));

pill.classList.add("active");

tempActive=pill;

setActiveSection(pill.dataset.value);

});

});

const customSelect=$("#customSelect");
const selected=customSelect?.querySelector(".select-selected");
const options=customSelect?.querySelectorAll(".select-option");
const hiddenInput=$("#sectionSelect");
const tags=$("#sectionTags");
const saveBtn=$(".save-btn");

selected?.addEventListener("click",()=>{
customSelect.classList.toggle("active");
});


function formatSection(text){

const parts=text.split(" - ");

if(parts.length===3){

const course=parts[0];
const yearNum=parts[1].match(/\d+/)[0];
const sec=parts[2];

return course+"-"+yearNum+sec;

}

return text;

}


options?.forEach(option=>{

option.addEventListener("click",()=>{

const raw=option.dataset.value;
if(!raw) return;

const formatted=formatSection(raw);

const exists=[...$$(".section-pill")]
.some(p=>p.dataset.value===formatted);

if(exists){
customSelect.classList.remove("active");
return;
}

selected.childNodes[0].nodeValue=option.innerText+" ";
hiddenInput.value=formatted;

const pill=document.createElement("div");
pill.className="section-pill";
pill.dataset.value=formatted;
pill.innerText=formatted;

const remove=document.createElement("img");
remove.src="../img/teacher_img/remove.png";
remove.className="remove-tag";

remove.addEventListener("click",e=>{

e.stopPropagation();

pill.remove();

option.classList.remove("disabled-option");

fetch("remove_section.php",{
method:"POST",
headers:{'Content-Type':'application/x-www-form-urlencoded'},
body:"section="+encodeURIComponent(formatted)
});

});

pill.appendChild(remove);

pill.addEventListener("click",()=>{

$$(".section-pill").forEach(p=>p.classList.remove("active"));

pill.classList.add("active");

tempActive=pill;

setActiveSection(formatted);

});

tags.appendChild(pill);

tempActive=pill;

option.classList.add("disabled-option");

customSelect.classList.remove("active");

});

});


document.addEventListener("click",e=>{
if(customSelect && !customSelect.contains(e.target)){
customSelect.classList.remove("active");
}
});

let isSaving = false;

saveBtn?.addEventListener("click",()=>{

if(isSaving) return; // 🔥 prevent double click
isSaving = true;

saveBtn.disabled = true; // optional UX

const sections=[...$$(".section-pill")].map(p=>p.dataset.value);

if(!sections.length){
alert("Select section");
isSaving = false;
saveBtn.disabled = false;
return;
}

if(tempActive) setActiveSection(tempActive.dataset.value);

const subjects=[...$$("input[name='subjects[]']")]
.map(i=>({
name:i.value,
start:i.dataset.start||'',
end:i.dataset.end||''
}))
.filter(o=>o.name!="");

fetch("save_subjects.php",{
method:"POST",
headers:{'Content-Type':'application/x-www-form-urlencoded'},
body:
sections.map(s=>"sections[]="+encodeURIComponent(s)).join("&")+
"&"+
subjects.map(s=>
"subjects[]="+encodeURIComponent(s.name)+
"&start_times[]="+encodeURIComponent(s.start)+
"&end_times[]="+encodeURIComponent(s.end)
).join("&")
})
.then(r=>r.text())
.then(res=>{
if(res.trim()=="ok"){
location.reload();
}
})
.finally(()=>{
isSaving = false;
saveBtn.disabled = false;
});

});

const addBtn=$("#addSubjectBtn");
const container=$("#subjectContainer");

addBtn?.addEventListener("click",function addRow(){

$$(".add-icon").forEach(icon=>icon.remove());

const wrapper=document.createElement("div");
wrapper.className="subject-input";

wrapper.innerHTML=`
<img src="../img/teacher_img/add_sub.png" class="add-icon">
<input type="text" name="subjects[]" placeholder="Add Subject:" class="subjectInput">
<img src="../img/teacher_img/time.png" class="time-icon">
`;

container.appendChild(wrapper);

wrapper.querySelector(".add-icon").addEventListener("click",addRow);

wrapper.querySelector("input").focus();

});

document.addEventListener("click",e=>{

if(e.target.classList.contains("time-icon")){

const input=e.target.previousElementSibling;

window.activeSubjectInput=input;

const rect=e.target.getBoundingClientRect();

timeOverlay.style.display="block";

timeOverlay.style.top=(rect.top+window.scrollY-10)+"px";
timeOverlay.style.left=(rect.right+window.scrollX+10)+"px";

$("#startTime").value=input.dataset.start||'';
$("#endTime").value=input.dataset.end||'';

}

});


document.addEventListener("click",e=>{

const panel=$(".time-panel");

if(timeOverlay?.style.display==="block"){

if(!panel.contains(e.target) &&
!e.target.classList.contains("time-icon")){

timeOverlay.style.display="none";

}

}

});

});