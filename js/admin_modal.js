const deleteModal = document.getElementById("deleteModal");
const delete_id = document.getElementById("delete_id");
const deleteTitle = document.getElementById("deleteTitle");

window.openDelete = function(id,name){
if(deleteModal && delete_id && deleteTitle){
deleteModal.classList.add("show-modal");
delete_id.value = id;
deleteTitle.innerHTML = "Archive " + name + "?";
}
}

window.closeDelete = function(){
if(deleteModal){
deleteModal.classList.remove("show-modal");
}
}

if(window.location.search.includes("edit")){
history.replaceState({},'',location.pathname);
}

setTimeout(()=>{
let m=document.getElementById("updateMsg");
if(m) m.remove();
},2000);

/* STUDENT MODAL */

const openModal = document.getElementById("openModal");
const studentModal = document.getElementById("studentModal");

if(openModal && studentModal){
openModal.onclick = () => studentModal.classList.add("show-modal");
}

if(studentModal){
studentModal.addEventListener("click",e=>{
if(e.target===studentModal){
studentModal.classList.remove("show-modal");
}
});
}

document.addEventListener("DOMContentLoaded",()=>{

document.querySelectorAll(".check-toggle").forEach((img,index)=>{

img.addEventListener("click",function(){

if(index===0){

const checked=this.src.includes("box.png");

document.querySelectorAll(".check-toggle").forEach(i=>{
i.src=checked
? "../img/admin_img/check.png"
: "../img/admin_img/box.png";
});

}else{

this.src=this.src.includes("box.png")
? "../img/admin_img/check.png"
: "../img/admin_img/box.png";

}

});

});

const studentListBtn=document.querySelector(".studentlist-btn");

if(studentListBtn){
studentListBtn.addEventListener("click",()=>{
window.location=location.pathname;
});
}

const form=document.querySelector("#studentModal form");
const updateBtn=document.querySelector("button[name='update_student']");

if(form && updateBtn){

const inputs=form.querySelectorAll("input, select");

let originalData={};

inputs.forEach(input=>{
originalData[input.name]=input.value;
});

form.addEventListener("input",()=>{

let changed=false;

inputs.forEach(input=>{
if(originalData[input.name]!==input.value){
changed=true;
}
});

updateBtn.disabled=!changed;

});

}

});

const filter=document.getElementById("filterSelect");

if(filter){
filter.addEventListener("change",()=>{
window.location="?filter="+filter.value;
});
}

const customFilter=document.querySelector(".custom-filter");

if(customFilter){

customFilter.addEventListener("click",function(e){
e.stopPropagation();
this.classList.toggle("active");
});

document.addEventListener("click",(e)=>{
if(!customFilter.contains(e.target)){
customFilter.classList.remove("active");
}
});

}

const searchInput=document.getElementById("studentSearch");

if(searchInput){

searchInput.addEventListener("keyup",function(){

let value=this.value.toLowerCase();

let rows=document.querySelectorAll(".student-table tbody tr:not(#noResult)");

let visible=0;

rows.forEach(row=>{

let name=row.children[1]?.innerText.toLowerCase()||"";
let id=row.children[2]?.innerText.toLowerCase()||"";
let course=row.children[3]?.innerText.toLowerCase()||"";
let email=row.children[8]?.innerText.toLowerCase()||"";

if(
name.includes(value) ||
id.includes(value) ||
course.includes(value) ||
email.includes(value)
){
row.style.display="";
visible++;
}else{
row.style.display="none";
}

});

let tbody=document.querySelector(".student-table tbody");
let empty=document.getElementById("noResult");

if(visible===0){

if(!empty){
tbody.insertAdjacentHTML("beforeend",
`<tr id="noResult"><td colspan="10" class="empty-row">No student found</td></tr>`
);
}

}else{

if(empty) empty.remove();

}

});

}

function goFilter(type,value){
location="?"+type+"="+encodeURIComponent(value);
}