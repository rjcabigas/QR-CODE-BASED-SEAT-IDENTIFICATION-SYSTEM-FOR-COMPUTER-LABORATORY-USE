const addPc = document.getElementById("addPcBtn");
const pcContainer = document.getElementById("pcContainer");
const deletePcBtn = document.getElementById("deletePcBtn");
const qrBtn = document.getElementById("qrBtn");

let selectedPc = null;
let currentPage = 1;
const limit = 45;

function showEmptyPC(){

pcContainer.innerHTML=`
<div class="empty-wrapper pc-empty">
<img src="../img/mis_img/no_new_pc.png">
<h3>No pc created yet</h3>
<p>Newly added PCs will be displayed here.</p>
</div>`;

qrBtn.disabled=true;

}

function getMissingPCs(){

const pcs=[...document.querySelectorAll(".pc-box")].map(b=>{
return parseInt(b.querySelector("span").innerText.replace("PC ",""));
});

if(pcs.length===0) return [];

const max=Math.max(...pcs);
const set=new Set(pcs);
const missing=[];

for(let i=1;i<=max;i++){
if(!set.has(i)) missing.push(i);
}

return missing;

}

function showMissingModal(missing){

if(document.querySelector(".pc-modal")) return;

let html=`<div class="pc-modal">
<div class="pc-modal-box">
<h3>Select PC to restore</h3>
<div class="pc-missing-list">`;

missing.forEach(n=>{
html+=`<button data-num="${n}">PC ${String(n).padStart(2,"0")}</button>`;
});

html+=`</div>
<button class="close-modal">Cancel</button>
</div>
</div>`;

document.body.insertAdjacentHTML("beforeend",html);

document.querySelectorAll(".pc-missing-list button").forEach(btn=>{

btn.onclick=()=>{

const n=parseInt(btn.dataset.num);

fetch("",{
method:"POST",
headers:{'Content-Type':'application/x-www-form-urlencoded'},
body:"restore_pc="+window.selectedComlab+"&pc_number="+n
})
.then(r=>r.text())
.then(()=>{

document.querySelector(".pc-modal")?.remove();
loadPCs();

const toast=document.createElement("div");
toast.className="toast-message toast-success";
toast.innerText=`PC ${String(n).padStart(2,"0")} was successfully restored`;

document.body.appendChild(toast);

setTimeout(()=>{
toast.classList.add("show");
},10);

setTimeout(()=>{
toast.classList.remove("show");
setTimeout(()=>toast.remove(),300);
},2500);

})
.catch(err=>console.error(err));

};

});

document.querySelector(".close-modal").onclick=()=>{
document.querySelector(".pc-modal")?.remove();
};

}

function addNewPC(){

fetch("",{
method:"POST",
headers:{'Content-Type':'application/x-www-form-urlencoded'},
body:"add_pc="+window.selectedComlab
})
.then(r=>r.text())
.then(()=>{

    fetch("",{
        method:"POST",
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:"load_pc="+window.selectedComlab+"&page=1"
    })
    .then(r=>r.json())
    .then(data=>{

        const total = data.total;
        currentPage = Math.ceil(total / limit);

        loadPCs();

    });

})
.catch(err=>console.error(err));

}

addPc.onclick=()=>{

if(!window.selectedComlab){

const toast = document.createElement("div");
toast.className = "toast-message";
toast.innerText = "Select Comlab first";

document.body.appendChild(toast);

setTimeout(()=>{
toast.classList.add("show");
},10);

setTimeout(()=>{
toast.classList.remove("show");
setTimeout(()=>toast.remove(),300);
},2500);

return;
}

const missing=getMissingPCs();

if(missing.length>0){
showMissingModal(missing);
return;
}

addNewPC();

};

function loadPCs(){

if(!window.selectedComlab){
showEmptyPC();
return;
}

selectedPc=null;
updateDeleteState();

pcContainer.innerHTML="";

fetch("",{
method:"POST",
headers:{'Content-Type':'application/x-www-form-urlencoded'},
body:"load_pc="+window.selectedComlab+"&page="+currentPage
})
.then(r=>r.json())
.then(data=>{

const pcs = data.pcs;
const total = data.total;

if(!Array.isArray(pcs) || pcs.length===0){

if(currentPage > 1){
    currentPage--;
    loadPCs();
    return;
}

showEmptyPC();

const prevBtn = document.getElementById("prevBtn");
const nextBtn = document.getElementById("nextBtn");
const pageIndicator = document.getElementById("pageIndicator");

pageIndicator.innerText = "00";

document.querySelector(".pagination").style.display = "none";

return;
}

pcs.sort((a,b)=>a-b);

let formattedPage = currentPage.toString().padStart(2, '0');
document.getElementById("pageIndicator").innerText = formattedPage;

const prevBtn = document.getElementById("prevBtn");
const nextBtn = document.getElementById("nextBtn");
const pageIndicator = document.getElementById("pageIndicator");

if(total >= 40){

    document.querySelector(".pagination").style.display = "flex";

    prevBtn.style.display = "flex";
    nextBtn.style.display = "flex";
    pageIndicator.style.display = "block";

    prevBtn.disabled = currentPage === 1;
    nextBtn.disabled = (currentPage * limit) >= total;

}else{

    document.querySelector(".pagination").style.display = "none";

    prevBtn.style.display = "none";
    nextBtn.style.display = "none";
    pageIndicator.style.display = "none";

}
qrBtn.disabled=false;

pcs.forEach(n=>{

const num=String(n).padStart(2,"0");

const box=document.createElement("div");
box.className="pc-box";

box.innerHTML=`
<img src="../img/mis_img/pc.png">
<span>PC ${num}</span>
`;

pcContainer.appendChild(box);

});

})
.catch(err=>console.error(err));

}

pcContainer.addEventListener("click",(e)=>{

const box=e.target.closest(".pc-box");
if(!box) return;

e.stopPropagation();

box.classList.toggle("pc-selected");

updateDeleteState();

});

deletePcBtn.onclick=()=>{

const selected = document.querySelectorAll(".pc-box.pc-selected");

if(selected.length === 0 || !window.selectedComlab) return;

let titleText = "";

if(selected.length === 1){
    const name = selected[0].querySelector("span").innerText;
    titleText = `Delete ${name}?`;
}else{
    titleText = `Delete ${selected.length} selected PC(s)?`;
}

document.querySelector(".delete-modal")?.remove();

const modal=`
<div class="delete-modal">
<div class="delete-box">

<div class="delete-content">
<div class="delete-title">${titleText}</div>
<div class="delete-message">Are you sure you want to delete this?</div>
</div>

<div class="delete-footer">
<button class="delete-cancel">Cancel</button>
<button class="delete-confirm">Yes</button>
</div>

</div>
</div>
`;

document.body.insertAdjacentHTML("beforeend",modal);

const m=document.querySelector(".delete-modal");

m.querySelector(".delete-cancel").onclick=()=>{
m.remove();
};

m.querySelector(".delete-confirm").onclick=()=>{

Promise.all([...selected].map(box => {

    const name = box.querySelector("span").innerText;
    const pcNumber = parseInt(name.replace("PC ",""));

    return fetch("",{
        method:"POST",
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:"delete_pc="+window.selectedComlab+"&pc_number="+pcNumber
    });

}))
.then(() => {
    loadPCs();
})
.catch(err=>console.error(err));

m.remove();

const toast=document.createElement("div");
toast.className="toast-message toast-error";
toast.innerText=`${selected.length} PC(s) successfully deleted`;

document.body.appendChild(toast);

setTimeout(()=>{
toast.classList.add("show");
},10);

setTimeout(()=>{
toast.classList.remove("show");
setTimeout(()=>toast.remove(),300);
},2500);

};

};

function updateDeleteState(){
deletePcBtn.disabled = document.querySelectorAll(".pc-box.pc-selected").length === 0;
}

document.getElementById("prevBtn").addEventListener("click", () => {
    if(currentPage > 1){
        currentPage--;
        loadPCs();
    }
});

document.getElementById("nextBtn").addEventListener("click", () => {
    currentPage++;
    loadPCs();
});

document.addEventListener("click", (e) => {

    if(
    e.target.closest("#pcContainer") ||
    e.target.closest(".delete-modal") ||
    e.target.closest("#deletePcBtn")
) return;

    document.querySelectorAll(".pc-box")
    .forEach(box => box.classList.remove("pc-selected"));

    updateDeleteState();

});